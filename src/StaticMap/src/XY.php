<?php

namespace DantSu\OpenStreetMapStaticAPI;

/**
 * DantSu\OpenStreetMapStaticAPI\XY define X and Y pixel position for map, lines, markers...
 *
 * @package DantSu\OpenStreetMapStaticAPI
 * @author Franck Alary
 * @access public
 * @see https://github.com/DantSu/php-osm-static-api Github page of this project
 */
class XY
{
    /**
     * @var int X
     */
    private $x = 0;
    /**
     * @var int Y
     */
    private $y = 0;

    /**
     * XY constructor.
     * @param int $x X
     * @param int $y Y
     */
    public function __construct(int $x, int $y)
    {
        $this->x = $x;
        $this->y = $y;
    }

    /**
     * Get X
     * @return int X
     */
    public function getX(): int
    {
        return $this->x;
    }

    /**
     * Get Y
     * @return int Y
     */
    public function getY(): int
    {
        return $this->y;
    }
}
