<?php

namespace DantSu\OpenStreetMapStaticAPI;


use DantSu\OpenStreetMapStaticAPI\Interfaces\Draw;
use DantSu\PHPImageEditor\Image;

/**
 * DantSu\OpenStreetMapStaticAPI\Polygon draw polygon on the map.
 *
 * @package DantSu\OpenStreetMapStaticAPI
 * @author Franck Alary
 * @access public
 * @see https://github.com/DantSu/php-osm-static-api Github page of this project
 */
class Polygon implements Draw
{
    /**
     * @var string
     */
    private $strokeColor = '000000';
    /**
     * @var int
     */
    private $strokeWeight = 1;
    /**
     * @var string
     */
    private $fillColor = '000000';
    /**
     * @var LatLng[]
     */
    private $points = [];

    /**
     * Polygon constructor.
     * @param string $strokeColor Hexadecimal string color
     * @param int $strokeWeight pixel weight of the line
     * @param string $fillColor Hexadecimal string color
     */
    public function __construct(string $strokeColor, int $strokeWeight, string $fillColor)
    {
        $this->strokeColor = \str_replace('#', '', $strokeColor);
        $this->strokeWeight = $strokeWeight > 0 ? $strokeWeight : 0;
        $this->fillColor = \str_replace('#', '', $fillColor);
    }

    /**
     * Add a latitude and longitude to the polygon
     * @param LatLng $latLng Latitude and longitude to add
     * @return $this Fluent interface
     */
    public function addPoint(LatLng $latLng): Polygon
    {
        $this->points[] = $latLng;
        return $this;
    }

    /**
     * Draw the polygon on the map image.
     *
     * @see https://github.com/DantSu/php-image-editor See more about DantSu\PHPImageEditor\Image
     *
     * @param Image $image The map image (An instance of DantSu\PHPImageEditor\Image)
     * @param MapData $mapData Bounding box of the map
     * @return $this Fluent interface
     */
    public function draw(Image $image, MapData $mapData): Polygon
    {
        /**
         * @var $cPoints XY[]
         */
        $cPoints = \array_map(
            function (LatLng $p) use ($mapData) {
                return $mapData->convertLatLngToPxPosition($p);
            },
            $this->points
        );

        $image->pasteOn(
            Image::newCanvas($image->getWidth(), $image->getHeight())
                ->drawPolygon(
                    \array_reduce(
                        $cPoints,
                        function (array $acc, XY $p) {
                            $acc[] = $p->getX();
                            $acc[] = $p->getY();
                            return $acc;
                        },
                        []
                    ),
                    $this->fillColor
                ),
            0,
            0
        );

        if ($this->strokeWeight > 0) {
            foreach ($cPoints as $k => $point) {
                $pK = $k - 1;
                if (!isset($cPoints[$pK])) {
                    $pK = \count($cPoints) - 1;
                }
                $image->drawLine($cPoints[$pK]->getX(), $cPoints[$pK]->getY(), $point->getX(), $point->getY(), $this->strokeWeight, $this->strokeColor);
            }
        }

        return $this;
    }

    /**
     * Get bounding box of the shape
     * @return LatLng[]
     */
    public function getBoundingBox(): array
    {
        return MapData::getBoundingBoxFromPoints($this->points);
    }
}
