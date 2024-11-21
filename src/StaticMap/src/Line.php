<?php

namespace Wavelog\StaticMapImage;

require('./src/StaticMap/src/Interfaces/Draw.php');
require('./src/StaticMap/src/Utils/GeographicConverter.php');
use Wavelog\StaticMapImage\Interfaces\Draw;
use DantSu\PHPImageEditor\Image;
use Wavelog\StaticMapImage\LatLng;
use Wavelog\StaticMapImage\MapData;
use Wavelog\StaticMapImage\Utils\GeographicConverter;

/**
 * Wavelog\StaticMapImage\Line draw line on the map.
 *
 * @package Wavelog\StaticMapImage
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
     * @var bool wrap around the world or not
     */
    private $wrap;

    /**
     * Line constructor.
     * @param string $color Hexadecimal string color
     * @param int $weight pixel weight of the line
     */
    public function __construct(string $color, int $weight, bool $wrap = false)
    {
        $this->color = \str_replace('#', '', $color);
        $this->weight = $weight;
        $this->wrap = $wrap;
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
                return $mapData->convertLatLngToPxPosition($p, !$this->wrap);
            },
            $this->points
        );

        foreach ((array) $cPoints as $k => $point) {
            if (isset($cPoints[$k - 1])) {
                $image->drawLine($cPoints[$k - 1]->getX(), $cPoints[$k - 1]->getY(), $point->getX(), $point->getY(), $this->weight, $this->color);

                // do the same left and right if $wrap is disabled. 'Lines' are special here
                if ($this->wrap) {
                    $image->drawLine($cPoints[$k - 1]->getX() + $image->getWidth(), $cPoints[$k - 1]->getY(), $point->getX() + $image->getWidth(), $point->getY(), $this->weight, $this->color);
                    $image->drawLine($cPoints[$k - 1]->getX() - $image->getWidth(), $cPoints[$k - 1]->getY(), $point->getX() - $image->getWidth(), $point->getY(), $this->weight, $this->color);
                }
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

    /**
     * Geodesic points between two coordinates
     * 
     * @param LatLng $start
     * @param LatLng $end
     * @param bool $wrapping
     * 
     * @return LatLng[]
     */
    public function geodesicPoints(LatLng $start, LatLng $end, bool $wrapping): \Generator {
        $totalDistance = GeographicConverter::latLngToMeters($start, $end);
        $distanceInterval = 100000;
        $currentDistance = 0;

        $lastPoint = $start;
        yield $start;

        while ($currentDistance + $distanceInterval < $totalDistance) {
            $currentDistance += $distanceInterval;
            
            $angle = GeographicConverter::getBearing($lastPoint, $end);
            $nextPoint = GeographicConverter::metersToLatLng($lastPoint, $distanceInterval, $angle);

            if ($wrapping) {
                if ($nextPoint->getLng() < 1 && $lastPoint->getLng() > 1) {
                    $nextPoint->setLng($nextPoint->getLng() + 360);
                }
                if ($nextPoint->getLng() > 1 && $lastPoint->getLng() < 1) {
                    $nextPoint->setLng($nextPoint->getLng() - 360);
                }
            }

            yield $nextPoint;

            $lastPoint = $nextPoint;
        }

        if ($lastPoint && $end->getLng() < -1 && $lastPoint->getLng() > 1) {
            $end->setLng($end->getLng() + 360);
        }
        if ($lastPoint && $end->getLng() > 1 && $lastPoint->getLng() < -1) {
            $end->setLng($end->getLng() - 360);
        }

        yield $end;
    }
}
