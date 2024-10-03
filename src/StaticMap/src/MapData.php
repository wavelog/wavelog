<?php

namespace DantSu\OpenStreetMapStaticAPI;


use DantSu\OpenStreetMapStaticAPI\Utils\GeographicConverter;

/**
 * DantSu\OpenStreetMapStaticAPI\MapData convert latitude and longitude to image pixel position.
 *
 * @package DantSu\OpenStreetMapStaticAPI
 * @author Franck Alary
 * @access public
 * @see https://github.com/DantSu/php-osm-static-api Github page of this project
 */
class MapData
{
    /**
     * Convert longitude and zoom to horizontal OpenStreetMap tile number and pixel position.
     * @param float $lon Longitude
     * @param int $zoom Zoom
     * @param int $tileSize Tile size
     * @return int[] OpenStreetMap tile id and pixel position of the given longitude and zoom
     */
    public static function lngToXTile(float $lon, int $zoom, int $tileSize): array
    {
        $x = ($lon + 180) / 360 * \pow(2, $zoom);
        $tile = \floor($x);
        return [
            'id' => $tile,
            'position' => \round($tileSize * ($x - $tile))
        ];
    }

    /**
     * Convert latitude and zoom to vertical OpenStreetMap tile number and pixel position.
     * @param float $lat Latitude
     * @param int $zoom Zoom
     * @param int $tileSize Tile size
     * @return int[] OpenStreetMap tile id and pixel position of the given latitude and zoom
     */
    public static function latToYTile(float $lat, int $zoom, int $tileSize): array
    {
        $y = (1 - \log(\tan(\deg2rad($lat)) + 1 / \cos(\deg2rad($lat))) / M_PI) / 2 * \pow(2, $zoom);
        $tile = \floor($y);
        return [
            'id' => $tile,
            'position' => \round($tileSize * ($y - $tile))
        ];
    }

    /**
     * Convert horizontal OpenStreetMap tile number ad zoom to longitude.
     * @param int $id Horizontal OpenStreetMap tile id
     * @param int $position Horizontal pixel position on tile
     * @param int $zoom Zoom
     * @param int $tileSize Tile size
     * @return float Longitude of the given OpenStreetMap tile id and zoom
     */
    public static function xTileToLng(int $id, int $position, int $zoom, int $tileSize): float
    {
        return ($id + $position / $tileSize) / \pow(2, $zoom) * 360 - 180;
    }

    /**
     * Convert vertical OpenStreetMap tile number and zoom to latitude.
     * @param int $id Vertical OpenStreetMap tile id
     * @param int $position Vertical pixel position on tile
     * @param int $zoom Zoom
     * @param int $tileSize Tile size
     * @return float Latitude of the given OpenStreetMap tile id and zoom
     */
    public static function yTileToLat(int $id, int $position, int $zoom, int $tileSize): float
    {
        return \rad2deg(\atan(\sinh(M_PI * (1 - 2 * ($id + $position / $tileSize) / \pow(2, $zoom)))));
    }

    /**
     * Transform array of LatLng to bounding box
     *
     * @param LatLng[] $points
     * @return LatLng[]
     */
    public static function getBoundingBoxFromPoints(array $points): array
    {
        $minLat = 360;
        $maxLat = -360;
        $minLng = 360;
        $maxLng = -360;
        foreach ($points as $point) {
            if ($point->getLat() < $minLat) {
                $minLat = $point->getLat();
            }
            if ($point->getLat() > $maxLat) {
                $maxLat = $point->getLat();
            }
            if ($point->getLng() < $minLng) {
                $minLng = $point->getLng();
            }
            if ($point->getLng() > $maxLng) {
                $maxLng = $point->getLng();
            }
        }
        return [new LatLng($maxLat, $minLng), new LatLng($minLat, $maxLng)];
    }

    /**
     * Get center and zoom from two points.
     *
     * @param LatLng $topLeft
     * @param LatLng $bottomRight
     * @param int $padding
     * @param int $imageWidth
     * @param int $imageHeight
     * @param int $tileSize
     * @return array center : LatLng, zoom : int
     */
    public static function getCenterAndZoomFromBoundingBox(LatLng $topLeft, LatLng $bottomRight, int $padding, int $imageWidth, int $imageHeight, int $tileSize): array
    {
        $zoom = 20;
        $padding *= 2;
        $topTilePos = MapData::latToYTile($topLeft->getLat(), $zoom, $tileSize);
        $bottomTilePos = MapData::latToYTile($bottomRight->getLat(), $zoom, $tileSize);
        $leftTilePos = MapData::lngToXTile($topLeft->getLng(), $zoom, $tileSize);
        $rightTilePos = MapData::lngToXTile($bottomRight->getLng(), $zoom, $tileSize);
        $pxZoneWidth = ($rightTilePos['id'] - $leftTilePos['id']) * $tileSize + $rightTilePos['position'] - $leftTilePos['position'];
        $pxZoneHeight = ($bottomTilePos['id'] - $topTilePos['id']) * $tileSize + $bottomTilePos['position'] - $topTilePos['position'];

        return [
            'center' => GeographicConverter::getCenter($topLeft, $bottomRight),
            'zoom' => \intval(
                \floor(
                    \log(
                        \min(
                            1,
                            ($imageHeight - $padding) / $pxZoneHeight,
                            ($imageWidth - $padding) / $pxZoneWidth
                        ) * \pow(2, $zoom)
                    ) / 0.69314
                )
            )
        ];
    }

    /**
     * @var int zoom
     */
    private $zoom;
    /**
     * @var int tile size
     */
    private $tileSize;
    /**
     * @var XY Width and height of the image in pixel
     */
    private $outputSize;
    /**
     * @var XY top left tile numbers
     */
    private $tileTopLeft;
    /**
     * @var XY bottom right tile numbers
     */
    private $tileBottomRight;
    /**
     * @var XY left and top pixels to crop to fit final image size
     */
    private $mapCropTopLeft;
    /**
     * @var XY bottom and right pixels to crop to fit final image size
     */
    private $mapCropBottomRight;
    /**
     * @var LatLng Latitude and longitude of top left image
     */
    private $latLngTopLeft;
    /**
     * @var LatLng Latitude and longitude of top right image
     */
    private $latLngTopRight;
    /**
     * @var LatLng Latitude and longitude of bottom left image
     */
    private $latLngBottomLeft;
    /**
     * @var LatLng Latitude and longitude of bottom right image
     */
    private $latLngBottomRight;


    /**
     * @param LatLng $centerMap
     * @param int $zoom
     * @param XY $outputSize
     * @param int $tileSize
     */
    public function __construct(LatLng $centerMap, int $zoom, XY $outputSize, int $tileSize)
    {
        $this->zoom = $zoom;
        $this->outputSize = $outputSize;
        $this->tileSize = $tileSize;

        $x = static::lngToXTile($centerMap->getLng(), $zoom, $this->tileSize);
        $y = static::latToYTile($centerMap->getLat(), $zoom, $this->tileSize);

        $startX = \floor($outputSize->getX() / 2 - $x['position']);
        $startY = \floor($outputSize->getY() / 2 - $y['position']);


        $rightSize = $outputSize->getX() - $startX;
        $bottomSize = $outputSize->getY() - $startY;

        $this->mapCropTopLeft = new XY(
            $startX < 0 ? \abs($startX) : ($startX % $this->tileSize == 0 ? 0 : $this->tileSize - $startX % $this->tileSize),
            $startY < 0 ? \abs($startY) : ($startY % $this->tileSize == 0 ? 0 : $this->tileSize - $startY % $this->tileSize)
        );
        $this->mapCropBottomRight = new XY(
            ($rightSize % $this->tileSize == 0 ? 0 : $this->tileSize - $rightSize % $this->tileSize),
            ($bottomSize % $this->tileSize == 0 ? 0 : $this->tileSize - $bottomSize % $this->tileSize)
        );
        $this->tileTopLeft = new XY(
            $x['id'] - \ceil($startX / $this->tileSize),
            $y['id'] - \ceil($startY / $this->tileSize)
        );
        $this->tileBottomRight = new XY(
            $x['id'] - 1 + \ceil($rightSize / $this->tileSize),
            $y['id'] - 1 + \ceil($bottomSize / $this->tileSize)
        );

        $this->latLngTopLeft = new LatLng(
            static::yTileToLat($this->tileTopLeft->getY(), $this->mapCropTopLeft->getY(), $zoom, $this->tileSize),
            static::xTileToLng($this->tileTopLeft->getX(), $this->mapCropTopLeft->getX(), $zoom, $this->tileSize)
        );
        $this->latLngTopRight = new LatLng(
            static::yTileToLat($this->tileTopLeft->getY(), $this->mapCropTopLeft->getY(), $zoom, $this->tileSize),
            static::xTileToLng($this->tileBottomRight->getX(), $this->tileSize - $this->mapCropBottomRight->getX(), $zoom, $this->tileSize)
        );
        $this->latLngBottomLeft = new LatLng(
            static::yTileToLat($this->tileBottomRight->getY(), $this->tileSize - $this->mapCropBottomRight->getY(), $zoom, $this->tileSize),
            static::xTileToLng($this->tileTopLeft->getX(), $this->mapCropTopLeft->getX(), $zoom, $this->tileSize)
        );
        $this->latLngBottomRight = new LatLng(
            static::yTileToLat($this->tileBottomRight->getY(), $this->tileSize - $this->mapCropBottomRight->getY(), $zoom, $this->tileSize),
            static::xTileToLng($this->tileBottomRight->getX(), $this->tileSize - $this->mapCropBottomRight->getX(), $zoom, $this->tileSize)
        );
    }


    /**
     * Get latitude and longitude of top left image
     * @return LatLng Latitude and longitude of top left image
     */
    public function getLatLngTopLeft(): LatLng
    {
        return $this->latLngTopLeft;
    }

    /**
     * Get latitude and longitude of top right image
     * @return LatLng Latitude and longitude of top right image
     */
    public function getLatLngTopRight(): LatLng
    {
        return $this->latLngTopRight;
    }

    /**
     * Get latitude and longitude of bottom left image
     * @return LatLng Latitude and longitude of bottom left image
     */
    public function getLatLngBottomLeft(): LatLng
    {
        return $this->latLngBottomLeft;
    }

    /**
     * Get latitude and longitude of bottom right image
     * @return LatLng Latitude and longitude of bottom right image
     */
    public function getLatLngBottomRight(): LatLng
    {
        return $this->latLngBottomRight;
    }

    /**
     * Get width and height of the image in pixel
     * @return XY Width and height of the image in pixel
     */
    public function getOutputSize(): XY
    {
        return $this->outputSize;
    }

    /**
     * Get the zoom
     * @return int zoom
     */
    public function getZoom(): int
    {
        return $this->zoom;
    }

    /**
     * Get tile size
     * @return int tile size
     */
    public function getTileSize(): int
    {
        return $this->tileSize;
    }


    /**
     * Get top left tile numbers
     * @return XY top left tile numbers
     */
    public function getTileTopLeft(): XY
    {
        return $this->tileTopLeft;
    }

    /**
     * Get bottom right tile numbers
     * @return XY bottom right tile numbers
     */
    public function getTileBottomRight(): XY
    {
        return $this->tileBottomRight;
    }

    /**
     * Get top left crop pixels
     * @return XY top left crop pixels
     */
    public function getMapCropTopLeft(): XY
    {
        return $this->mapCropTopLeft;
    }

    /**
     * Get bottom right crop pixels
     * @return XY bottom right crop pixels
     */
    public function getMapCropBottomRight(): XY
    {
        return $this->mapCropBottomRight;
    }


    /**
     * Convert a latitude and longitude to a XY pixel position in the image
     * @param LatLng $latLng Latitude and longitude to be converted
     * @return XY Pixel position of latitude and longitude in the image
     */
    public function convertLatLngToPxPosition(LatLng $latLng): XY
    {
        $x = static::lngToXTile($latLng->getLng(), $this->zoom, $this->tileSize);
        $y = static::latToYTile($latLng->getLat(), $this->zoom, $this->tileSize);

        return new XY(
            ($x['id'] - $this->tileTopLeft->getX()) * $this->tileSize - $this->mapCropTopLeft->getX() + $x['position'],
            ($y['id'] - $this->tileTopLeft->getY()) * $this->tileSize - $this->mapCropTopLeft->getY() + $y['position']
        );
    }


}
