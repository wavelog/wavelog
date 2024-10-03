<?php

namespace DantSu\OpenStreetMapStaticAPI\Interfaces;


use DantSu\OpenStreetMapStaticAPI\MapData;
use DantSu\PHPImageEditor\Image;

interface Draw
{
    public function getBoundingBox(): array;

    public function draw(Image $image, MapData $mapData);
}
