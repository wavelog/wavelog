<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class MaptileCache {

	public const TILE_TTL = 2592000;

	public const CONFIG_TTL = 60;

	public const CONFIG_KEY = 'maptile:config';

	public const DEFAULT_SERVER = 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';

	public static function key(string $template, int $z, int $x, int $y): string {
		return 'tile:' . hash('xxh128', $template) . ':' . $z . ':' . $x . ':' . $y;
	}

	// Same rotation as Leaflet's own TileLayer.getSubdomain() and Wavelog\StaticMapImage\TileLayer::getSubdomain()
	public static function subdomain(string $subdomains, int $x, int $y): string {
		$letters = str_split($subdomains) ?: ['a'];
		return $letters[abs($x + $y) % count($letters)];
	}

	private static function driver() {
		static $cache = null;
		if ($cache !== null) {
			return $cache;
		}
		$CI = &get_instance();
		$cache_adapter = $CI->config->item('cache_adapter') ?? 'file';
		$cache_backup = $CI->config->item('cache_backup') ?? 'file';
		// apcu has a default limit of 32MB which is too small for storing map tiles
		// so we fall back to file. redis is also a good option if available.
		$CI->load->driver('cache', [
			'adapter' => $cache_adapter === 'apcu' ? 'file' : $cache_adapter,
			'backup' => $cache_backup === 'apcu' ? 'file' : $cache_backup,
			'key_prefix' => $CI->config->item('cache_key_prefix') ?? '',
		]);
		$cache = $CI->cache;
		return $cache;
	}

	public static function get(string $template, int $z, int $x, int $y): string|false {
		return self::driver()->get(self::key($template, $z, $x, $y));
	}

	public static function save(string $template, int $z, int $x, int $y, string $png): bool {
		return self::driver()->save(self::key($template, $z, $x, $y), $png, self::TILE_TTL);
	}

	public static function config(): array {
		$cache = self::driver();
		$config = $cache->get(self::CONFIG_KEY);
		if ($config === false) {
			$CI = &get_instance();
			$CI->load->is_loaded('options_model') ?: $CI->load->model('options_model');
			$config = [
				$CI->options_model->item('map_tile_server') ?? self::DEFAULT_SERVER,
				$CI->options_model->item('map_tile_subdomains') ?? 'abc',
			];
			$cache->save(self::CONFIG_KEY, $config, self::CONFIG_TTL);
		}
		return $config;
	}

	public static function flush_config(): void {
		self::driver()->delete(self::CONFIG_KEY);
	}

	public static function client_url(): string {
		static $url = null;
		if ($url === null) {
			[$upstream, $subdomains] = self::config();
			$version = substr(hash('xxh128', $upstream . $subdomains), 0, 8);
			$url = site_url('tiles/{z}/{x}/{y}.png?provider=' . $version);
		}
		return $url;
	}
}
