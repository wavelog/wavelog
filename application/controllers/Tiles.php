<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Tiles extends CI_Controller {

	private const TILE_TTL = 604800;
	private const DEFAULT_TILE_SERVER = 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';

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

		$this->load->driver('cache', [
			'adapter' => $this->config->item('cache_adapter') ?? 'file',
			'backup' => $this->config->item('cache_backup') ?? 'file',
			'key_prefix' => $this->config->item('cache_key_prefix') ?? '',
		]);

		$this->load->model('options_model');
		$upstream = $this->options_model->item('map_tile_server') ?? self::DEFAULT_TILE_SERVER;

		$key = 'tile:' . md5($upstream) . ':' . $z . ':' . $x . ':' . $y;

		$png = $this->cache->get($key);

		if ($png === false) {
			$png = $this->fetch_upstream($upstream, $x, $y, $z);
			if ($png === null) {
				$this->bail(404);
			}
			$this->cache->save($key, $png, self::TILE_TTL);
		}

		$this->output
			->set_header('Content-Type: image/png')
			->set_header('Cache-Control: public, max-age=' . self::TILE_TTL)
			->set_output($png);
	}

	private function require_session_cookie(): void {
		$name = $this->config->item('sess_cookie_name') ?? 'ci_session';
		if ($this->input->cookie($name) === null) {
			$this->bail(404);
		}
	}

	private function fetch_upstream(string $template, int $x, int $y, int $z): ?string {
		$url = str_replace(['{s}', '{r}', '{x}', '{y}', '{z}'], ['a', '', $x, $y, $z], $template);

		if (str_starts_with($url, base_url())) {
			$url = str_replace(['{s}', '{r}', '{x}', '{y}', '{z}'], ['a', '', $x, $y, $z], self::DEFAULT_TILE_SERVER);
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
