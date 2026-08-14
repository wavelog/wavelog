<?php

namespace DantSu\PHPImageEditor;

class Geometry2D
{
    public static function degrees0to360(float $angle): float
    {
        while ($angle < 0 || $angle >= 360) {
            if ($angle < 0) $angle += 360;
            elseif ($angle >= 360) $angle -= 360;
        }
        return $angle;
    }

    public static function getDstXY(float $originX, float $originY, float $angle, float $length): array
    {
        $angle = 360 - $angle;
        return [
            'x' => $originX + \cos($angle * M_PI / 180) * $length,
            'y' => $originY + \sin($angle * M_PI / 180) * $length
        ];
    }

    public static function getDstXYRounded(float $originX, float $originY, float $angle, float $length): array
    {
        $xy = Geometry2D::getDstXY($originX, $originY, $angle, $length);
        return [
            'x' => \round($xy['x']),
            'y' => \round($xy['y'])
        ];
    }

    public static function getAngleAndLengthFromPoints(float $originX, float $originY, float $dstX, float $dstY): array
    {
        $width = $dstX - $originX;
        $height = $dstY - $originY;
        $diameter = \sqrt(\pow($width, 2) + \pow($height, 2));

        if($width == 0) {
            $angle = 90;
        } elseif ($height == 0) {
            $angle = 0;
        } else {
            $angle = \atan2(\abs($height), \abs($width)) * 180.0 / M_PI;
        }

        if($width < 0 && $height < 0) {
            $angle += 180;
        } elseif ($width < 0) {
            $angle = 180 - $angle;
        } elseif ($height < 0) {
            $angle = 360 - $angle;
        }

        return [
            'angle' => 360 - $angle,
            'length' => $diameter
        ];
    }
}
