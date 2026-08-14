<?php

namespace Wavelog\StaticMapImage;


use Wavelog\StaticMapImage\Interfaces\Draw;
use DantSu\PHPImageEditor\Image;

/**
 * Wavelog\StaticMapImage\Polygon draw polygon on the map.
 *
 * @package Wavelog\StaticMapImage
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
     * @var bool wrap around the world or not
     */
    private $wrap;

    /**
     * Polygon constructor.
     * @param string $strokeColor Hexadecimal string color
     * @param int $strokeWeight pixel weight of the line
     * @param string $fillColor Hexadecimal string color
     */
    public function __construct(string $strokeColor, int $strokeWeight, string $fillColor, bool $wrap = false)
    {
        $this->strokeColor = \str_replace('#', '', $strokeColor);
        $this->strokeWeight = $strokeWeight > 0 ? $strokeWeight : 0;
        $this->fillColor = \str_replace('#', '', $fillColor);
        $this->wrap = $wrap;
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
     * Get all Points of the polygon
     * @return array of points
     */

    public function getPoints(): array
    {
        return $this->points;
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

        // Funktion zum Zeichnen des Polygons, um Wiederholungen zu reduzieren
        $drawPolygon = function (array $points) use ($image) {
            $image->pasteOn(
                Image::newCanvas($image->getWidth(), $image->getHeight())
                    ->drawPolygon(
                        \array_reduce(
                            $points,
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
        };

        // Zeichne das ursprüngliche Polygon
        $drawPolygon($cPoints);

        if ($this->wrap) {
            // Zeichne das Polygon links und rechts des Bildes
            $width = $image->getWidth();
            $shiftedLeft = \array_map(function (XY $p) use ($width) {
                return new XY($p->getX() - $width, $p->getY());
            }, $cPoints);
            
            $shiftedRight = \array_map(function (XY $p) use ($width) {
                return new XY($p->getX() + $width, $p->getY());
            }, $cPoints);
            
            $drawPolygon($shiftedLeft);
            $drawPolygon($shiftedRight);
        }

        if ($this->strokeWeight > 0) {
            // Zeichne die Linien zwischen den Punkten für das Hauptpolygon
            foreach ($cPoints as $k => $point) {
                $pK = $k - 1;
                if (!isset($cPoints[$pK])) {
                    $pK = \count($cPoints) - 1;
                }
                $image->drawLine($cPoints[$pK]->getX(), $cPoints[$pK]->getY(), $point->getX(), $point->getY(), $this->strokeWeight, $this->strokeColor);
            }

            // Zeichne die Linien für die linken und rechten Kopien des Polygons, falls Wrap aktiv ist
            if ($this->wrap) {
                foreach ([$shiftedLeft, $shiftedRight] as $shiftedPoints) {
                    foreach ($shiftedPoints as $k => $point) {
                        $pK = $k - 1;
                        if (!isset($shiftedPoints[$pK])) {
                            $pK = \count($shiftedPoints) - 1;
                        }
                        $image->drawLine($shiftedPoints[$pK]->getX(), $shiftedPoints[$pK]->getY(), $point->getX(), $point->getY(), $this->strokeWeight, $this->strokeColor);
                    }
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
}
