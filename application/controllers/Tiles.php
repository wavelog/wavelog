<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Tiles extends CI_Controller {

	public function __construct() {
		parent::__construct();
		// no session stuff here, so directly release the session lock
		session_write_close();
	}


	public function _remap() {
		$this->require_session_cookie();

		if (!preg_match('#^tiles/(\d{1,2})/(\d{1,6})/(\d{1,6})\.png$#', $this->uri->uri_string(), $m)) {
			$this->bail(404);
		}

		$z = (int) $m[1];
		$x = (int) $m[2];
		$y = (int) $m[3];

		if ($z > 19 || $x >= (1 << $z) || $y >= (1 << $z)) {
			$this->bail(404);
		}

		$this->load->library('MaptileCache');
		[$upstream, $subdomains] = MaptileCache::config();

		$png = MaptileCache::get($upstream, $z, $x, $y);

		if ($png === false) {
			$png = $this->fetch_upstream($upstream, $subdomains, $x, $y, $z);
			if ($png === null) {
				$this->bail(404);
			}
			MaptileCache::save($upstream, $z, $x, $y, $png);
		}

		$this->output
			->set_header('Content-Type: image/png')
			->set_header('Cache-Control: public, max-age=' . MaptileCache::TILE_TTL)
			->set_header('Pragma: public')
			->set_header('Expires: ' . gmdate('D, d M Y H:i:s', time() + MaptileCache::TILE_TTL) . ' GMT')
			->set_output($png);
	}

	private function require_session_cookie(): void {
		$name = $this->config->item('sess_cookie_name') ?? 'ci_session';
		if ($this->input->cookie($name) === null) {
			$this->bail(404);
		}
	}

	private function fetch_upstream(string $template, string $subdomains, int $x, int $y, int $z): ?string {
		$url = str_replace(['{s}', '{r}', '{x}', '{y}', '{z}'], [MaptileCache::subdomain($subdomains, $x, $y), '', $x, $y, $z], $template);

		if (str_starts_with($url, base_url())) {
			$url = str_replace(['{s}', '{r}', '{x}', '{y}', '{z}'], [MaptileCache::subdomain('abc', $x, $y), '', $x, $y, $z], MaptileCache::DEFAULT_SERVER);
		}

		$ch = curl_init($url);
		curl_setopt_array($ch, [
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_CONNECTTIMEOUT => 4,
			CURLOPT_TIMEOUT => 8,
			CURLOPT_USERAGENT => 'WavelogTileProxy/1.0',
		]);
		$body = curl_exec($ch);
		$status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		if ($body === false || $status !== 200 || strncmp($body, "\x89PNG", 4) !== 0) {
			return null;
		}

		return $body;
	}

	private function bail(int $status): void {
		$this->output->set_status_header($status)->_display();
		exit;
	}
}
