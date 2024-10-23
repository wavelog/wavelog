<?php

namespace Wavelog\StaticMapImage;

use Wavelog\StaticMapImage\Interfaces\Draw;
use DantSu\PHPImageEditor\Image;

/**
 * Wavelog\StaticMapImage\OpenStreetMap is a PHP library created for easily get static image from OpenStreetMap with markers, lines, polygons and circles.
 *
 * @package Wavelog\StaticMapImage
 * @author Franck Alary
 * @access public
 * @see https://github.com/DantSu/php-osm-static-api Github page of this project
 */
class OpenStreetMap
{
    /**
     * Create new instance of OpenStreetMap.
     * @param LatLng $centerMap Latitude and longitude of the map center
     * @param int $zoom Zoom
     * @param int $imageWidth Width of the generated map image
     * @param int $imageHeight Height of the generated map image
     * @param TileLayer $tileLayer Tile server configuration, defaults to OpenStreetMaps tile server
     * @param int $tileSize Tile size in pixels
     */
    public static function createFromLatLngZoom(LatLng $centerMap, int $zoom, int $imageWidth, int $imageHeight, TileLayer $tileLayer = null, int $tileSize = 256): OpenStreetMap
    {
        return new OpenStreetMap($centerMap, $zoom, $imageWidth, $imageHeight, $tileLayer, $tileSize);
    }

    /**
     * Create new instance of OpenStreetMap.
     * @param LatLng $topLeft Latitude and longitude of the map top left
     * @param LatLng $bottomRight Latitude and longitude of the map bottom right
     * @param int $padding Padding to add before top left and after bottom right position.
     * @param int $imageWidth Width of the generated map image
     * @param int $imageHeight Height of the generated map image
     * @param TileLayer $tileLayer Tile server configuration, defaults to OpenStreetMaps tile server
     * @param int $tileSize Tile size in pixels
     * @return OpenStreetMap
     */
    public static function createFromBoundingBox(LatLng $topLeft, LatLng $bottomRight, int $padding, int $imageWidth, int $imageHeight, TileLayer $tileLayer = null, int $tileSize = 256): OpenStreetMap
    {
        if ($tileLayer === null) {
            $tileLayer = TileLayer::defaultTileLayer();
        }

        $latLngZoom = MapData::getCenterAndZoomFromBoundingBox($topLeft, $bottomRight, $padding, $imageWidth, $imageHeight, $tileSize);
        return new OpenStreetMap($latLngZoom['center'], $latLngZoom['zoom'], $imageWidth, $imageHeight, $tileLayer, $tileSize);
    }

    /**
     * @var MapData Data about the generated map (bounding box, size, OSM tile ids...)
     */
    protected $mapData;
    /**
     * @var TileLayer[] Array of TileLayer instances
     */
    protected $layers = [];
    /**
     * @var Markers[] Array of Markers instances
     */
    protected $markers = [];
    /**
     * @var Draw[] Array of Line instances
     */
    protected $draws = [];

    /**
     * OpenStreetMap constructor.
     * @param LatLng $centerMap Latitude and longitude of the map center
     * @param int $zoom Zoom
     * @param int $imageWidth Width of the generated map image
     * @param int $imageHeight Height of the generated map image
     * @param TileLayer $tileLayer Tile server configuration, defaults to OpenStreetMaps tile server
     * @param int $tileSize Tile size in pixels
     */
    public function __construct(LatLng $centerMap, int $zoom, int $imageWidth, int $imageHeight, TileLayer $tileLayer = null, int $tileSize = 256)
    {
        if ($tileLayer === null) {
            $tileLayer = TileLayer::defaultTileLayer();
        }

        $this->mapData = new MapData($centerMap, $tileLayer->checkZoom($zoom), new XY($imageWidth, $imageHeight), $tileSize);
        $this->layers = [$tileLayer];
    }

    /**
     * Add tile layer to the map
     * @param TileLayer $layer An instance of TileLayer
     * @return $this Fluent interface
     */
    public function addLayer(TileLayer $layer)
    {
        $this->layers[] = $layer;
        return $this;
    }

    /**
     * Add markers on the map
     * @param Markers $markers An instance of Markers
     * @return $this Fluent interface
     */
    public function addMarkers(Markers $markers)
    {
        $this->markers[] = $markers;
        return $this;
    }

    /**
     * Add a line on the map
     * @param Draw $draw An instance of Line
     * @return $this Fluent interface
     */
    public function addDraw(Draw $draw)
    {
        $this->draws[] = $draw;
        return $this;
    }

    /**
     * Fit map to draws.
     *
     * @param int $padding Padding in pixel
     * @return $this Fluent interface
     */
    public function fitToDraws(int $padding = 0)
    {
        $points = [];
        foreach ($this->draws as $draw) {
            $points = \array_merge($points, $draw->getBoundingBox());
        }
        return $this->fitToPoints($points, $padding);
    }

    /**
     * Fit map to markers.
     *
     * @param int $padding Padding in pixel
     * @return $this Fluent interface
     */
    public function fitToMarkers(int $padding = 0)
    {
        $points = [];
        foreach ($this->markers as $markers) {
            $points = \array_merge($points, $markers->getBoundingBox());
        }
        return $this->fitToPoints($points, $padding);
    }

    /**
     * Fit map to draws and markers.
     *
     * @param int $padding Padding in pixel
     * @return $this Fluent interface
     */
    public function fitToMarkersAndDraws(int $padding = 0)
    {
        $points = [];
        foreach ($this->draws as $draw) {
            $points = \array_merge($points, $draw->getBoundingBox());
        }
        foreach ($this->markers as $markers) {
            $points = \array_merge($points, $markers->getBoundingBox());
        }
        return $this->fitToPoints($points, $padding);
    }

    /**
     * Fit map to an array of points.
     *
     * @param LatLng[] $points LatLng points
     * @param int $padding Padding in pixel
     * @return $this Fluent interface
     */
    public function fitToPoints(array $points, int $padding = 0)
    {
        $outputSize = $this->mapData->getOutputSize();
        $tileSize = $this->mapData->getTileSize();
        $boundingBox = MapData::getBoundingBoxFromPoints($points);
        $latLngZoom = MapData::getCenterAndZoomFromBoundingBox($boundingBox[0], $boundingBox[1], $padding, $outputSize->getX(), $outputSize->getY(), $tileSize);
        $this->mapData = new MapData($latLngZoom['center'], $this->layers[0]->checkZoom($latLngZoom['zoom']), $outputSize, $tileSize);
        return $this;
    }

    /**
     * Get data about the generated map (bounding box, size, OSM tile ids...)
     * @see https://github.com/DantSu/php-osm-static-api/blob/master/docs/classes/DantSu/OpenStreetMapStaticAPI/MapData.md See more about MapData
     * @return MapData data about the generated map (bounding box, size, OSM tile ids...)
     */
    public function getMapData(): MapData
    {
        return $this->mapData;
    }

    /**
     * Get only the map image.
     * @see https://github.com/DantSu/php-image-editor See more about DantSu\PHPImageEditor\Image
     * @return Image An instance of DantSu\PHPImageEditor\Image
     */
    protected function getMapImage($centerMap): Image
    {
        $imgSize = $this->mapData->getOutputSize();
        $startX = $this->mapData->getMapCropTopLeft()->getX() * -1;
        $startY = $this->mapData->getMapCropTopLeft()->getY() * -1;

        $image = Image::newCanvas($imgSize->getX(), $imgSize->getY());
        $tileSize = $this->mapData->getTileSize();

        foreach ($this->layers as $tileLayer) {
            $yTile = $this->mapData->getTileTopLeft()->getY();
            for ($y = $startY; $y < $imgSize->getY(); $y += $tileSize) {
                $xTile = $this->mapData->getTileTopLeft()->getX();
                for ($x = $startX; $x < $imgSize->getX(); $x += $tileSize) {
                    $xTileWrapped = $this->wrapLongitudeTile($xTile);
                    $image->pasteOn(
                        $tileLayer->getTile($xTileWrapped, $yTile, $this->mapData->getZoom(), $tileSize, $centerMap),
                        $x,
                        $y
                    );
                    ++$xTile;
                }
                ++$yTile;
            }
        }
        return $image;
    }

    /**
     * Wrap the longitude tile.
     * @param int $xTile The x tile
     * @return int The wrapped x tile
     */
    protected function wrapLongitudeTile($xTile)
    {
        $maxTile = pow(2, $this->mapData->getZoom());
        return ($xTile % $maxTile + $maxTile) % $maxTile;
    }

    /**
     * Draw OpenStreetMap attribution at the right bottom of the image
     * @param Image $image The image of the map
     * @return Image The image of the map with attribution
     */
    protected function drawAttribution(Image $image): Image
    {
        $margin = 5;

        // Anonymous function to render the attribution text
        $attribution = function (Image $image, $margin): array {
            // Collect attribution text without HTML tags (strip tags from each layer)
            $attributionText = \implode(
                ' - ',
                \array_map(function ($layer) {
                    // Strip HTML tags (like <a>) from the attribution text
                    return strip_tags($layer->getAttributionText());
                }, $this->layers)
            );

            // Draw the plain text onto the image and get its bounding box
            return $image->writeTextAndGetBoundingBox(
                $attributionText,
                __DIR__ . '/resources/font.ttf',  // Font path
                10,  // Font size
                '0078A8',  // Text color
                $margin,  // X margin
                $margin,  // Y margin
                Image::ALIGN_LEFT,  // Horizontal alignment
                Image::ALIGN_TOP  // Vertical alignment
            );
        };

        // Calculate bounding box for the attribution text
        $bbox = $attribution(Image::newCanvas(1, 1), $margin);

        // Create a new canvas for the attribution background
        $imageAttribution = Image::newCanvas($bbox['bottom-right']['x'] + $margin, $bbox['bottom-right']['y'] + $margin);
        // Draw a semi-transparent rectangle for the attribution background
        $imageAttribution->drawRectangle(0, 0, $imageAttribution->getWidth(), $imageAttribution->getHeight(), 'FFFFFF33');
        // Write the attribution text on the new canvas
        $attribution($imageAttribution, $margin);

        // Paste the attribution canvas onto the main image (bottom-right corner)
        return $image->pasteOn($imageAttribution, Image::ALIGN_RIGHT, Image::ALIGN_BOTTOM);
    }

    /**
     * Get the map image with markers and lines.
     *
     * @see https://github.com/DantSu/php-image-editor See more about DantSu\PHPImageEditor\Image
     * @return Image An instance of DantSu\PHPImageEditor\Image
     */
    public function getImage($centerMap = '00'): Image
    {
        $image = $this->getMapImage($centerMap);

        foreach ($this->draws as $line) {
            $line->draw($image, $this->mapData);
        }

        foreach ($this->markers as $markers) {
            $markers->draw($image, $this->mapData);
        }

        return $this->drawAttribution($image);
    }
}
