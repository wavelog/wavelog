<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Propagation_data {
	private $manifest;
	private $directory;

	public function __construct() {
		$CI = &get_instance();
		$this->manifest = json_decode(file_get_contents(APPPATH . 'config/propagation-data.json'), true, 512, JSON_THROW_ON_ERROR);
		$this->directory = $CI->paths->make_update_path('propagation/' . $this->manifest['revision']);
	}

	public function is_ready() {
		foreach ($this->manifest['files'] as $name => $expected) {
			if (!$this->valid_file($this->directory . '/' . $name, $expected)) {
				return false;
			}
		}
		return true;
	}

	public function revision() {
		return $this->manifest['revision'];
	}

	public function verified_path($name) {
		if (!is_string($name) || !isset($this->manifest['files'][$name])) {
			throw new InvalidArgumentException('Unknown propagation reference file');
		}
		$path = $this->directory . '/' . $name;
		if (!$this->valid_file($path, $this->manifest['files'][$name])) {
			throw new RuntimeException('Missing or damaged propagation reference data: ' . $name);
		}
		return $path;
	}

	public function install() {
		if (!is_dir($this->directory) && !mkdir($this->directory, 0755, true) && !is_dir($this->directory)) {
			throw new RuntimeException('Cannot create propagation reference data directory');
		}
		$lock = fopen($this->directory . '/.install.lock', 'c');
		if ($lock === false) {
			throw new RuntimeException('Cannot open propagation reference data lock');
		}
		try {
			if (!flock($lock, LOCK_EX | LOCK_NB)) {
				throw new RuntimeException('Propagation reference data installation is already running');
			}
			$downloaded = 0;
			foreach ($this->manifest['files'] as $name => $expected) {
				$destination = $this->directory . '/' . $name;
				if ($this->valid_file($destination, $expected)) {
					continue;
				}
				$temporary = tempnam($this->directory, '.download-');
				if ($temporary === false) {
					throw new RuntimeException('Cannot create propagation download file');
				}
				try {
					$this->download($this->manifest['upstreamDirectory'] . rawurlencode($name), $temporary);
					if (!$this->valid_file($temporary, $expected)) {
						throw new RuntimeException('Propagation reference size or SHA-256 mismatch: ' . $name);
					}
					if (!chmod($temporary, 0644) || !rename($temporary, $destination)) {
						throw new RuntimeException('Cannot install propagation reference file: ' . $name);
					}
					$downloaded++;
				} finally {
					if (is_file($temporary) && !unlink($temporary)) {
						log_message('error', 'Cannot remove propagation temporary download: ' . $temporary);
					}
				}
			}
			return $downloaded;
		} finally {
			fclose($lock);
		}
	}

	private function valid_file($path, $expected) {
		clearstatcache(true, $path);
		return is_file($path) && filesize($path) === $expected['bytes'] &&
			hash_file('sha256', $path) === $expected['sha256'];
	}

	protected function download($url, $destination) {
		$stream = fopen($destination, 'wb');
		if ($stream === false) {
			throw new RuntimeException('Cannot open propagation download destination');
		}
		$curl = curl_init($url);
		try {
			curl_setopt_array($curl, [
				CURLOPT_FILE => $stream,
				CURLOPT_USERAGENT => 'Wavelog Updater',
				CURLOPT_CONNECTTIMEOUT => 15,
				CURLOPT_TIMEOUT => 120,
				CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
				CURLOPT_SSL_VERIFYPEER => true,
				CURLOPT_SSL_VERIFYHOST => 2,
			]);
			$success = curl_exec($curl);
			$status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
			if ($success === false || $status !== 200) {
				throw new RuntimeException('Propagation reference download failed (HTTP ' . $status . '): ' . curl_error($curl));
			}
		} finally {
			curl_close($curl);
			fclose($stream);
		}
	}
}
