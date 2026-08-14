<?php

namespace Wavelog\StaticMapImage;

use DantSu\PHPImageEditor\Image;


/**
 * Wavelog\StaticMapImage\Markers display markers on the map.
 *
 * @package Wavelog\StaticMapImage
 * @author Franck Alary
 * @access public
 * @see https://github.com/DantSu/php-osm-static-api Github page of this project
 */
class Markers {
    const ANCHOR_LEFT = 'left';
    const ANCHOR_CENTER = 'center';
    const ANCHOR_RIGHT = 'right';
    const ANCHOR_TOP = 'top';
    const ANCHOR_MIDDLE = 'middle';
    const ANCHOR_BOTTOM = 'bottom';

    /**
     * @var Image Image of the marker
     */
    private $image;
    /**
     * @var bool Wrap around the globe or not
     */
    private $wrap;
    /**
     * @var string|int Horizontal anchor of the marker image
     */
    private $horizontalAnchor = Markers::ANCHOR_CENTER;
    /**
     * @var string|int Vertical anchor of the marker image
     */
    private $verticalAnchor = Markers::ANCHOR_MIDDLE;
    /**
     * @var LatLng[] Latitudes and longitudes of the markers
     */
    private $coordinates = [];

    public function __construct($pathImage, $wrap = false) {
        $this->wrap = $wrap;
        $this->image = Image::fromPath($pathImage);
    }

    /**
     * Add a marker on the map.
     * @param LatLng $coordinate Latitude and longitude of the marker
     * @return $this Fluent interface
     */
    public function addMarker(LatLng $coordinate): Markers {
        $this->coordinates[] = $coordinate;
        return $this;
    }

    /**
     * Resize the marker image.
     * 
     * @param int $width Width of the marker
     * @param int $height Height of the marker
     * @return $this Fluent interface
     * 
     */

    public function resizeMarker(int $width, int $height): Markers {
        $this->image = $this->image->resize($width, $height);
        return $this;
    }

    /**
     * Define the anchor point of the image marker.
     * @param int|string $horizontalAnchor Horizontal anchor in pixel or you can use `Markers::ANCHOR_LEFT`, `Markers::ANCHOR_CENTER`, `Markers::ANCHOR_RIGHT`
     * @param int|string $verticalAnchor Vertical anchor in pixel or you can use `Markers::ANCHOR_TOP`, `Markers::ANCHOR_MIDDLE`, `Markers::ANCHOR_BOTTOM`
     * @return $this Fluent interface
     */
    public function setAnchor($horizontalAnchor, $verticalAnchor): Markers {
        $this->horizontalAnchor = $horizontalAnchor;
        $this->verticalAnchor = $verticalAnchor;
        return $this;
    }

    /**
     * Draw markers on the image map.
     *
     * @see https://github.com/DantSu/php-image-editor See more about DantSu\PHPImageEditor\Image
     * @param Image $image The map image (An instance of DantSu\PHPImageEditor\Image)
     * @param MapData $mapData Bounding box of the map
     * @return $this Fluent interface
     */
    public function draw(Image $image, MapData $mapData): Markers {
        $imageMarginLeft = $this->horizontalAnchor;
        $offsetX = 1;
        $offsetY = 6;

        switch ($imageMarginLeft) {
            case Markers::ANCHOR_LEFT:
                $imageMarginLeft = 0;
                break;
            case Markers::ANCHOR_CENTER:
                $imageMarginLeft = $this->image->getWidth() / 2;
                break;
            case Markers::ANCHOR_RIGHT:
                $imageMarginLeft = $this->image->getWidth();
                break;
        }

        $imageMarginTop = $this->verticalAnchor;
        switch ($imageMarginTop) {
            case Markers::ANCHOR_TOP:
                $imageMarginTop = 0;
                break;
            case Markers::ANCHOR_MIDDLE:
                $imageMarginTop = $this->image->getHeight() / 2;
                break;
            case Markers::ANCHOR_BOTTOM:
                $imageMarginTop = $this->image->getHeight();
                break;
        }

        foreach ($this->coordinates as $coordinate) {
            $xy = $mapData->convertLatLngToPxPosition($coordinate);

            if ($this->wrap) {
                $coordinate_west = new LatLng($coordinate->getLat(), $coordinate->getLng() - 360);
                $coordinate_east = new LatLng($coordinate->getLat(), $coordinate->getLng() + 360);
                $xy_west = $mapData->convertLatLngToPxPosition($coordinate_west);
                $xy_east = $mapData->convertLatLngToPxPosition($coordinate_east);
            }

            $image->pasteOn($this->image, $xy->getX() + $offsetX - $imageMarginLeft, $xy->getY() + $offsetY - $imageMarginTop);

            // pasteOn again for wrapping left and right
            if ($this->wrap) {
                $image->pasteOn($this->image, $xy_west->getX() + $offsetX - $imageMarginLeft, $xy_west->getY() + $offsetY - $imageMarginTop);
                $image->pasteOn($this->image, $xy_east->getX() + $offsetX - $imageMarginLeft, $xy_east->getY() + $offsetY - $imageMarginTop);
            }

            // free memory
            unset($xy, $coordinate_west, $coordinate_east, $xy_west, $xy_east);
        }

        return $this;
    }

    /**
     * Get bounding box of markers
     * @return LatLng[]
     */
    public function getBoundingBox(): array {
        return MapData::getBoundingBoxFromPoints($this->coordinates);
    }
}
