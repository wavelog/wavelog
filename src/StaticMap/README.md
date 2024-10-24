[![Packagist](https://img.shields.io/packagist/dt/DantSu/php-osm-static-api.svg)](https://packagist.org/packages/DantSu/php-osm-static-api)
[![Latest Stable Version](https://poser.pugx.org/DantSu/php-osm-static-api/v/stable)](https://packagist.org/packages/DantSu/php-osm-static-api)
[![GitHub license](https://img.shields.io/github/license/DantSu/php-osm-static-api.svg)](https://github.com/DantSu/php-osm-static-api/blob/master/LICENSE)

# PHP OpenStreetMap Static API
https://github.com/DantSu/php-osm-static-api

PHP library to easily get static image from OpenStreetMap with markers, lines, circles and polygons.

This project uses the [Tile Server](https://wiki.openstreetmap.org/wiki/Tile_servers) of the OpenStreetMap Foundation which runs entirely on donated resources, see [Tile Usage Policy](https://operations.osmfoundation.org/policies/tiles/) for more information.

## ✨ Supporting

⭐ Star this repository to support this project. You will contribute to increase the visibility of this library 🙂

## Installation

Install this library easily with composer :

```cmd
composer require dantsu/php-osm-static-api
```

## How to use

### Generate OpenStreetMap static image with markers and polygon :

```php
use \Wavelog\StaticMapImage\OpenStreetMap;
use \Wavelog\StaticMapImage\LatLng;
use \Wavelog\StaticMapImage\Polygon;
use \Wavelog\StaticMapImage\Markers;

\header('Content-type: image/png');
(new OpenStreetMap(new LatLng(44.351933, 2.568113), 17, 600, 400))
    ->addMarkers(
        (new Markers(__DIR__ . '/resources/marker.png'))
            ->setAnchor(Markers::ANCHOR_CENTER, Markers::ANCHOR_BOTTOM)
            ->addMarker(new LatLng(44.351933, 2.568113))
            ->addMarker(new LatLng(44.351510, 2.570020))
            ->addMarker(new LatLng(44.351873, 2.566250))
    )
    ->addDraw(
        (new Polygon('FF0000', 2, 'FF0000DD'))
            ->addPoint(new LatLng(44.351172, 2.571092))
            ->addPoint(new LatLng(44.352097, 2.570045))
            ->addPoint(new LatLng(44.352665, 2.568107))
            ->addPoint(new LatLng(44.352887, 2.566503))
            ->addPoint(new LatLng(44.352806, 2.565972))
            ->addPoint(new LatLng(44.351517, 2.565672))
    )
    ->getImage()
    ->displayPNG();
```

![Exported OpenStreetMap image](./src/samples/resources/sample1.png)

### Align and zoom the map to drawings and markers :

- `->fitToDraws(int $padding = 0)`
- `->fitToMarkers(int $padding = 0)`
- `->fitToDrawsAndMarkers(int $padding = 0)`
- `->fitToPoints(LatLng[] $points, int $padding = 0)`

`$padding` sets the amount of padding in the borders of the map that shouldn't be accounted for when setting the view to fit bounds. This can be positive or negative according to your needs.

```php
use \Wavelog\StaticMapImage\OpenStreetMap;
use \Wavelog\StaticMapImage\LatLng;
use \Wavelog\StaticMapImage\Polygon;
use \Wavelog\StaticMapImage\Markers;

\header('Content-type: image/png');
(new OpenStreetMap(new LatLng(0, 0), 0, 600, 400))
    ->addMarkers(
        (new Markers(__DIR__ . '/resources/marker.png'))
            ->setAnchor(Markers::ANCHOR_CENTER, Markers::ANCHOR_BOTTOM)
            ->addMarker(new LatLng(44.351933, 2.568113))
            ->addMarker(new LatLng(44.351510, 2.570020))
            ->addMarker(new LatLng(44.351873, 2.566250))
    )
    ->addDraw(
        (new Polygon('FF0000', 2, 'FF0000DD'))
            ->addPoint(new LatLng(44.351172, 2.571092))
            ->addPoint(new LatLng(44.352097, 2.570045))
            ->addPoint(new LatLng(44.352665, 2.568107))
            ->addPoint(new LatLng(44.352887, 2.566503))
            ->addPoint(new LatLng(44.352806, 2.565972))
            ->addPoint(new LatLng(44.351517, 2.565672))
    )
    ->fitToDraws(10)
    ->getImage()
    ->displayPNG();
```

## Documentation

| Class | Description |
|---    |---          |
| [Circle](./docs/classes/DantSu/OpenStreetMapStaticAPI/Circle.md) | Wavelog\StaticMapImage\Circle draw circle on the map.|
| [LatLng](./docs/classes/DantSu/OpenStreetMapStaticAPI/LatLng.md) | Wavelog\StaticMapImage\LatLng define latitude and longitude for map, lines, markers.|
| [Line](./docs/classes/DantSu/OpenStreetMapStaticAPI/Line.md) | Wavelog\StaticMapImage\Line draw line on the map.|
| [MapData](./docs/classes/DantSu/OpenStreetMapStaticAPI/MapData.md) | Wavelog\StaticMapImage\MapData convert latitude and longitude to image pixel position.|
| [Markers](./docs/classes/DantSu/OpenStreetMapStaticAPI/Markers.md) | Wavelog\StaticMapImage\Markers display markers on the map.|
| [OpenStreetMap](./docs/classes/DantSu/OpenStreetMapStaticAPI/OpenStreetMap.md) | Wavelog\StaticMapImage\OpenStreetMap is a PHP library created for easily get static image from OpenStreetMap with markers, lines, polygons and circles.|
| [Polygon](./docs/classes/DantSu/OpenStreetMapStaticAPI/Polygon.md) | Wavelog\StaticMapImage\Polygon draw polygon on the map.|
| [TileLayer](./docs/classes/DantSu/OpenStreetMapStaticAPI/TileLayer.md) | Wavelog\StaticMapImage\TileLayer define tile server url and related configuration|
| [XY](./docs/classes/DantSu/OpenStreetMapStaticAPI/XY.md) | Wavelog\StaticMapImage\XY define X and Y pixel position for map, lines, markers.|


## Contributing

Please fork this repository and contribute back using pull requests.

Any contributions, large or small, major features, bug fixes, are welcomed and appreciated but will be thoroughly reviewed.

