<?php

namespace DantSu\OpenStreetMapStaticAPI;

use DantSu\OpenStreetMapStaticAPI\Interfaces\Draw;
use DantSu\PHPImageEditor\Image;

/**
 * DantSu\OpenStreetMapStaticAPI\Line draw line on the map.
 *
 * @package DantSu\OpenStreetMapStaticAPI
 * @author Franck Alary
 * @access public
 * @see https://github.com/DantSu/php-osm-static-api Github page of this project
 */
class Line implements Draw
{
    /**
     * @var string
     */
    private $color = '000000';
    /**
     * @var int
     */
    private $weight = 1;
    /**
     * @var LatLng[]
     */
    private $points = [];

    /**
     * Line constructor.
     * @param string $color Hexadecimal string color
     * @param int $weight pixel weight of the line
     */
    public function __construct(string $color, int $weight)
    {
        $this->color = \str_replace('#', '', $color);
        $this->weight = $weight;
    }

    /**
     * Add a latitude and longitude to the multi-points line
     * @param LatLng $latLng Latitude and longitude to add
     * @return $this Fluent interface
     */
    public function addPoint(LatLng $latLng): Line
    {
        $this->points[] = $latLng;
        return $this;
    }

    /**
     * Draw the line on the map image.
     *
     * @see https://github.com/DantSu/php-image-editor See more about DantSu\PHPImageEditor\Image
     *
     * @param Image $image The map image (An instance of DantSu\PHPImageEditor\Image)
     * @param MapData $mapData Bounding box of the map
     * @return $this Fluent interface
     */
    public function draw(Image $image, MapData $mapData): Line
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

        foreach ($cPoints as $k => $point) {
            if (isset($cPoints[$k - 1])) {
                $image->drawLine($cPoints[$k - 1]->getX(), $cPoints[$k - 1]->getY(), $point->getX(), $point->getY(), $this->weight, $this->color);
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
