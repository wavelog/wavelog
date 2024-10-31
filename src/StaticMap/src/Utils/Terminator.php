<?php

class Terminator {
    private $resolution;

    public function __construct($resolution = 2) {
        $this->resolution = $resolution;
    }

    public function getTerminatorCoordinates($time = null) {
        $date = $time ? new DateTime($time) : new DateTime();
        $julianDay = $this->julian($date);
        $gst = $this->GMST($julianDay);

        $sunEclPos = $this->sunEclipticPosition($julianDay);
        $eclObliq = $this->eclipticObliquity($julianDay);
        $sunEqPos = $this->sunEquatorialPosition($sunEclPos['lambda'], $eclObliq);

        $latLng = [];
        for ($i = 0; $i <= 720 * $this->resolution; $i++) {
            $lng = -360 + $i / $this->resolution;
            $ha = $this->hourAngle($lng, $sunEqPos, $gst);
            $latLng[] = [$this->latitude($ha, $sunEqPos), $lng];
        }

        if ($sunEqPos['delta'] < 0) {
            array_unshift($latLng, [90, -360]);
            array_push($latLng, [90, 360]);
        } else {
            array_unshift($latLng, [-90, -360]);
            array_push($latLng, [-90, 360]);
        }

        return $latLng;
    }

    private function julian($date) {
        // Julian date calculation
        return ($date->getTimestamp() / 86400) + 2440587.5;
    }

    private function GMST($julianDay) {
        $d = $julianDay - 2451545.0;
        return fmod(18.697374558 + 24.06570982441908 * $d, 24);
    }

    private function sunEclipticPosition($julianDay) {
        $n = $julianDay - 2451545.0;
        $L = fmod(280.460 + 0.9856474 * $n, 360);
        $g = fmod(357.528 + 0.9856003 * $n, 360);
        $lambda = $L + 1.915 * sin(deg2rad($g)) + 0.02 * sin(deg2rad(2 * $g));
        $R = 1.00014 - 0.01671 * cos(deg2rad($g)) - 0.0014 * cos(deg2rad(2 * $g));
        return ['lambda' => $lambda, 'R' => $R];
    }

    private function eclipticObliquity($julianDay) {
        $n = $julianDay - 2451545.0;
        $T = $n / 36525;
        return 23.43929111 - $T * (46.836769 / 3600 - $T * (0.0001831 / 3600 + $T * (0.00200340 / 3600 - $T * (0.576e-6 / 3600 - $T * 4.34e-8 / 3600))));
    }

    private function sunEquatorialPosition($sunEclLng, $eclObliq) {
        $alpha = rad2deg(atan(cos(deg2rad($eclObliq)) * tan(deg2rad($sunEclLng))));
        $delta = rad2deg(asin(sin(deg2rad($eclObliq)) * sin(deg2rad($sunEclLng))));
        $lQuadrant = floor($sunEclLng / 90) * 90;
        $raQuadrant = floor($alpha / 90) * 90;
        $alpha = $alpha + ($lQuadrant - $raQuadrant);
        return ['alpha' => $alpha, 'delta' => $delta];
    }

    private function hourAngle($lng, $sunPos, $gst) {
        $lst = $gst + $lng / 15;
        return $lst * 15 - $sunPos['alpha'];
    }

    private function latitude($ha, $sunPos) {
        return rad2deg(atan(-cos(deg2rad($ha)) / tan(deg2rad($sunPos['delta']))));
    }
}
