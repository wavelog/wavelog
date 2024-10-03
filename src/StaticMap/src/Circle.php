<?php

namespace DantSu\OpenStreetMapStaticAPI;


use DantSu\OpenStreetMapStaticAPI\Interfaces\Draw;
use DantSu\OpenStreetMapStaticAPI\Utils\GeographicConverter;
use DantSu\PHPImageEditor\Geometry2D;
use DantSu\PHPImageEditor\Image;

/**
 * DantSu\OpenStreetMapStaticAPI\Circle draw circle on the map.
 *
 * @package DantSu\OpenStreetMapStaticAPI
 * @author Franck Alary
 * @access public
 * @see https://github.com/DantSu/php-osm-static-api Github page of this project
 */
class Circle implements Draw
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
     * @var LatLng
     */
    private $center = null;

    /**
     * @var LatLng
     */
    private $edge = null;

    /**
     * Circle constructor.
     *
     * @param LatLng $center Latitude and longitude of the circle center
     * @param string $strokeColor Hexadecimal string color
     * @param int $strokeWeight pixel weight of the line
     * @param string $fillColor Hexadecimal string color
     */
    public function __construct(LatLng $center, string $strokeColor, int $strokeWeight, string $fillColor)
    {
        $this->center = $center;
        $this->edge = $center;
        $this->strokeColor = \str_replace('#', '', $strokeColor);
        $this->strokeWeight = $strokeWeight > 0 ? $strokeWeight : 0;
        $this->fillColor = \str_replace('#', '', $fillColor);
    }

    /**
     * Set a latitude and longitude to define the radius of the circle.
     *
     * @param LatLng $edge Latitude and longitude of the edge point of a circle
     * @return $this Fluent interface
     */
    public function setEdgePoint(LatLng $edge): Circle
    {
        $this->edge = $edge;
        return $this;
    }

    /**
     * Set the radius of the circle in meters.
     *
     * @param float $radius radius of a circle in meters
     * @return $this Fluent interface
     */
    public function setRadius(float $radius): Circle
    {
        $this->edge = GeographicConverter::metersToLatLng($this->center, $radius, 45);
        return $this;
    }

    /**
     * Draw the circle on the map image.
     *
     * @see https://github.com/DantSu/php-image-editor See more about DantSu\PHPImageEditor\Image
     *
     * @param Image $image The map image (An instance of DantSu\PHPImageEditor\Image)
     * @param MapData $mapData Bounding box of the map
     * @return $this Fluent interface
     */
    public function draw(Image $image, MapData $mapData): Circle
    {
        $center = $mapData->convertLatLngToPxPosition($this->center);
        $edge = $mapData->convertLatLngToPxPosition($this->edge);

        $angleAndLenght = Geometry2D::getAngleAndLengthFromPoints($center->getX(), $center->getY(), $edge->getX(), $edge->getY());
        $length = \round($angleAndLenght['length'] + $this->strokeWeight / 2);

        $dImage = Image::newCanvas($image->getWidth(), $image->getHeight());

        if ($this->strokeWeight > 0) {
            $dImage->drawCircle($center->getX(), $center->getY(), $length * 2, $this->strokeColor);
        }

        $dImage->drawCircle($center->getX(), $center->getY(), ($length - $this->strokeWeight) * 2, $this->fillColor);

        $image->pasteOn($dImage, 0, 0);
        return $this;
    }


    /**
     * Get bounding box of the shape
     * @return LatLng[]
     */
    public function getBoundingBox(): array
    {
        $distance = GeographicConverter::latLngToMeters($this->center, $this->edge) * 1.4142;
        return [
            GeographicConverter::metersToLatLng($this->center,  $distance, 315),
            GeographicConverter::metersToLatLng($this->center,  $distance, 135)
        ];
    }
}
