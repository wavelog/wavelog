<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Propagation_solar {
	private const BASE_URL = 'https://services.swpc.noaa.gov/json/solar-cycle/';
	private $path;

	public function __construct() {
		$CI = &get_instance();
		$this->path = $CI->paths->make_update_path('propagation/solar-cycle.json');
	}

	public function snapshot() {
		if (!is_file($this->path)) {
			throw new RuntimeException('Sunspot data has not been downloaded');
		}
		$data = $this->decode(file_get_contents($this->path));
		if (!isset($data['updated'], $data['months']) || !is_int($data['updated']) ||
			!is_array($data['months']) || !$data['months']) {
			throw new RuntimeException('Invalid cached sunspot data');
		}
		$data['stale'] = time() - $data['updated'] > 7 * 86400;
		return $data;
	}

	public function refresh() {
		$directory = dirname($this->path);
		if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
			throw new RuntimeException('Cannot create sunspot data directory');
		}
		$lock = fopen($this->path . '.lock', 'c');
		if ($lock === false) throw new RuntimeException('Cannot open sunspot update lock');
		try {
			if (!flock($lock, LOCK_EX | LOCK_NB)) {
				throw new RuntimeException('Sunspot update is already running');
			}
			$observed = $this->decode($this->download(self::BASE_URL . 'observed-solar-cycle-indices.json'));
			$predicted = $this->decode($this->download(self::BASE_URL . 'predicted-solar-cycle.json'));
			$months = self::monthly_values($observed, $predicted);
			$contents = json_encode(['updated' => time(), 'months' => $months], JSON_THROW_ON_ERROR);
			$temporary = tempnam($directory, '.solar-');
			if ($temporary === false) throw new RuntimeException('Cannot create sunspot update file');
			try {
				if (file_put_contents($temporary, $contents) !== strlen($contents) ||
					!chmod($temporary, 0644) || !rename($temporary, $this->path)) {
					throw new RuntimeException('Cannot install sunspot data');
				}
			} finally {
				if (is_file($temporary) && !unlink($temporary)) {
					log_message('error', 'Cannot remove sunspot temporary file: ' . $temporary);
				}
			}
			return count($months);
		} finally {
			fclose($lock);
		}
	}

	public static function monthly_values($observed, $predicted) {
		$months = [];
		foreach ([['rows' => $predicted, 'field' => 'predicted_ssn', 'kind' => 'predicted'],
			['rows' => $observed, 'field' => 'smoothed_ssn', 'kind' => 'observed']] as $source) {
			if (!is_array($source['rows']) || !array_is_list($source['rows']) || !$source['rows']) {
				throw new RuntimeException('Invalid solar-cycle response');
			}
			$count = 0;
			foreach ($source['rows'] as $row) {
				if (!is_array($row) || !isset($row['time-tag'], $row[$source['field']]) ||
					!is_string($row['time-tag']) || !preg_match('/^\d{4}-(0[1-9]|1[0-2])$/D', $row['time-tag']) ||
					!is_numeric($row[$source['field']])) {
					throw new RuntimeException('Invalid solar-cycle record');
				}
				$value = (float)$row[$source['field']];
				if ($value === -1.0) continue; // NOAA's missing smoothed-value sentinel.
				if (!is_finite($value) || $value < 0 || $value > 1000) {
					throw new RuntimeException('Invalid solar-cycle sunspot number');
				}
				$months[$row['time-tag']] = ['value' => $value, 'ssn' => (int)round($value), 'kind' => $source['kind']];
				$count++;
			}
			if ($count === 0) throw new RuntimeException('Solar-cycle response has no usable smoothed values');
		}
		ksort($months);
		return $months;
	}

	private function decode($contents) {
		try {
			return json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
		} catch (JsonException $error) {
			throw new RuntimeException('Invalid solar-cycle JSON', 0, $error);
		}
	}

	protected function download($url) {
		$curl = curl_init($url);
		try {
			curl_setopt_array($curl, [
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_USERAGENT => 'Wavelog Updater',
				CURLOPT_CONNECTTIMEOUT => 10,
				CURLOPT_TIMEOUT => 30,
				CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
				CURLOPT_SSL_VERIFYPEER => true,
				CURLOPT_SSL_VERIFYHOST => 2,
			]);
			$contents = curl_exec($curl);
			$status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
			if ($contents === false || $status !== 200) {
				throw new RuntimeException('Solar-cycle download failed (HTTP ' . $status . '): ' . curl_error($curl));
			}
			return $contents;
		} finally {
			curl_close($curl);
		}
	}
}
