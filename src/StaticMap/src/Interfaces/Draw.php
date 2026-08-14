<?php

namespace Wavelog\StaticMapImage\Interfaces;


use Wavelog\StaticMapImage\MapData;
use DantSu\PHPImageEditor\Image;

interface Draw
{
    public function getBoundingBox(): array;

    public function draw(Image $image, MapData $mapData);
}
