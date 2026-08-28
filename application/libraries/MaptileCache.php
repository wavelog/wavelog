<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class MaptileCache {

	public const TILE_TTL = 2592000;

	public const DEFAULT_SERVER = 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';

	public static function key(string $template, int $z, int $x, int $y): string {
		return 'tile:' . md5($template) . ':' . $z . ':' . $x . ':' . $y;
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
		$CI->load->driver('cache', [
			'adapter' => $CI->config->item('cache_adapter') ?? 'file',
			'backup' => $CI->config->item('cache_backup') ?? 'file',
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
}
