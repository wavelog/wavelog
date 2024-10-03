<?php

namespace DantSu\OpenStreetMapStaticAPI;

use DantSu\PHPImageEditor\Image;

/**
 * DantSu\OpenStreetMapStaticAPI\TileLayer define tile server url and related configuration
 *
 * @package DantSu\OpenStreetMapStaticAPI
 * @author Stephan Strate <hello@stephan.codes>
 * @access public
 * @see https://github.com/DantSu/php-osm-static-api Github page of this project
 */
class TileLayer
{

    /**
     * Default tile server. OpenStreetMaps with related attribution text
     * @return TileLayer default tile server
     */
    public static function defaultTileLayer(): TileLayer
    {
        return new TileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', '© OpenStreetMap contributors');
    }

    /**
     * @var string Tile server url, defaults to OpenStreetMap tile server
     */
    protected $url;

    /**
     * @var string Tile server attribution according to license
     */
    protected $attributionText;

    /**
     * @var string[] Tile server subdomains
     */
    protected $subdomains;

    /**
     * @var float Opacity
     */
    protected $opacity = 1;

    /*
     * @var int Max zoom value
     */
    protected $maxZoom = 20;

    /*
     * @var int Min zoom value
     */
    protected $minZoom = 0;


    /**
     * @array $curlOptions Array of curl options
     */
    protected $curlOptions = [];

    /**
     * @bool $failCurlOnError If true, curl will throw an exception on error.
     */
    protected $failCurlOnError = false;

    /**
     * TileLayer constructor
     * @param string $url tile server url with placeholders (`x`, `y`, `z`, `r`, `s`)
     * @param string $attributionText tile server attribution text
     * @param string $subdomains tile server subdomains
     * @param array $curlOptions Array of curl options
     * @param bool $failCurlOnError If true, curl will throw an exception on error.
     */
    public function __construct(string $url, string $attributionText, string $subdomains = 'abc', array $curlOptions = [], bool $failCurlOnError = false)
    {
        $this->url = $url;
        $this->attributionText = $attributionText;
        $this->subdomains = \str_split($subdomains);
        $this->curlOptions = $curlOptions;
        $this->failCurlOnError = $failCurlOnError;
    }

    /**
     * Set opacity of the layer
     * @param float $opacity Opacity value (0 to 1)
     * @return $this Fluent interface
     */
    public function setOpacity(float $opacity)
    {
        $this->opacity = $opacity;
        return $this;
    }

    /**
     * Set a max zoom value
     * @param int $maxZoom
     * @return $this Fluent interface
     */
    public function setMaxZoom(int $maxZoom)
    {
        $this->maxZoom = $maxZoom;
        return $this;
    }

    /**
     * Get max zoom value
     * @return int
     */
    public function getMaxZoom(): int
    {
        return $this->maxZoom;
    }

    /**
     * Set a min zoom value
     * @param int $minZoom
     * @return $this Fluent interface
     */
    public function setMinZoom(int $minZoom)
    {
        $this->minZoom = $minZoom;
        return $this;
    }

    /**
     * Get min zoom value
     * @return int
     */
    public function getMinZoom(): int
    {
        return $this->minZoom;
    }

    /**
     * Check if zoom value is between min zoom and max zoom
     * @param int $zoom Zoom value to be checked
     * @return int
     */
    public function checkZoom(int $zoom): int
    {
        return \min(\max($zoom, $this->minZoom), $this->maxZoom);
    }

    /**
     * Get tile url for coordinates and zoom level
     * @param int $x x coordinate
     * @param int $y y coordinate
     * @param int $z zoom level
     * @return string tile url
     */
    public function getTileUrl(int $x, int $y, int $z): string
    {
        return \str_replace(
            ['{r}', '{s}', '{x}', '{y}', '{z}'],
            ['', $this->getSubdomain($x, $y), $x, $y, $z],
            $this->url
        );
    }

    /**
     * Select subdomain of tile server to prevent rate limiting on remote server
     * @param int $x x coordinate
     * @param int $y y coordinate
     * @return string selected subdomain
     * @see https://github.com/Leaflet/Leaflet/blob/main/src/layer/tile/TileLayer.js#L233 Leaflet implementation
     */
    protected function getSubdomain(int $x, int $y): string
    {
        return $this->subdomains[\abs($x + $y) % \sizeof($this->subdomains)];
    }

    /**
     * Get attribution text
     * @return string Attribution text
     */
    public function getAttributionText(): string
    {
        return $this->attributionText;
    }

    /**
     * Get an image tile
     * @param float $x
     * @param float $y
     * @param int $z
     * @param int $tileSize
     * @return Image Image instance containing the tile
     * @throws \Exception
     */
    public function getTile(float $x, float $y, int $z, int $tileSize): Image
    {
        if($this->opacity == 0) {
            return Image::newCanvas($tileSize, $tileSize);
        }

        $tile = Image::fromCurl($this->getTileUrl($x, $y, $z),$this->curlOptions, $this->failCurlOnError);

        if($this->opacity > 0 && $this->opacity < 1) {
            $tile->setOpacity($this->opacity);
        }

        return $tile;
    }
}
