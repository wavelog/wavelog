<?php
/**
 * Ported from gpredict to PHP by Bill Shupp.  License: GPL 2.
 */

require_once realpath(__DIR__ . "/../Predict/Exception.php");

/**
 * Predict_TLE
 *
 * All routines for parsing and validating NORAD two line element sets
 */
class Predict_TLE
{
    public $header;     /* Header line of TLE file */
    public $line1;      /* Line 1 of TLE */
    public $line2;      /* Line 2 of TLE */
    public $epoch;      /*!< Epoch Time in NORAD TLE format YYDDD.FFFFFFFF */
    public $epoch_year; /*!< Epoch: year */
    public $epoch_day;  /*!< Epoch: day of year */
    public $epoch_fod;  /*!< Epoch: Fraction of day. */
    public $xndt2o;     /*!< 1. time derivative of mean motion */
    public $xndd6o;     /*!< 2. time derivative of mean motion */
    public $bstar;      /*!< Bstar drag coefficient. */
    public $xincl;      /*!< Inclination */
    public $xnodeo;     /*!< R.A.A.N. */
    public $eo;         /*!< Eccentricity */
    public $omegao;     /*!< argument of perigee */
    public $xmo;        /*!< mean anomaly */
    public $xno;        /*!< mean motion */

    public $catnr;      /*!< Catalogue Number.  */
    public $elset;      /*!< Element Set number. */
    public $revnum;     /*!< Revolution Number at epoch. */

    public $sat_name;   /*!< Satellite name string. */
    public $idesg;      /*!< International Designator. */
    public $status;     /*!< Operational status. */

    /* values needed for squint calculations */
    public $xincl1;
    public $xnodeo1;
    public $omegao1;


    /* Converts the strings in a raw two-line element set  */
    /* to their intended numerical values. No processing   */
    /* of these values is done, e.g. from deg to rads etc. */
    /* This is done in the select_ephemeris() function.    */
    public function __construct($header = null, $line1 = null, $line2 = null)
    {
        // Empty shell for factory (fromOmmJson); keeps existing call sites working.
        if ($line1 === null && $line2 === null) {
            $this->header = $header ?? '';
            return;
        }

        if (!$this->Good_Elements($line1, $line2)) {
            throw new Predict_Exception('Invalid TLE contents');
        }

        $this->header = $header;
        $this->line1  = $line1;
        $this->line2  = $line2;

        /** Decode Card 1 **/
        /* Satellite's catalogue number */
        $this->catnr = (int) substr($line1, 2, 5);

        /* International Designator for satellite */
        $this->idesg = substr($line1, 9, 8);

        /* Epoch time; this is the complete, unconverted epoch. */
        /* Replace spaces with 0 before casting, as leading spaces are allowed */
        $this->epoch = (float) str_replace(' ', '0', substr($line1, 18, 14));

        /* Now, convert the epoch time into year, day
           and fraction of day, according to:

           YYDDD.FFFFFFFF
        */

        // Adjust for 2 digit year through 2056
        $this->epoch_year = (int) substr($line1, 18, 2);
        if ($this->epoch_year > 56) {
            $this->epoch_year = $this->epoch_year + 1900;
        } else {
            $this->epoch_year = $this->epoch_year + 2000;
        }

        /* Epoch day */
        $this->epoch_day = (int) substr($line1, 20, 3);

        /* Epoch fraction of day */
        $this->epoch_fod = (float) substr($line1, 23, 9);


        /* Satellite's First Time Derivative */
        $this->xndt2o = (float) substr($line1, 33, 10);

        /* Satellite's Second Time Derivative */
        $this->xndd6o = (float) (substr($line1, 44, 1) . '.' . substr($line1, 45, 5) . 'E' . substr($line1, 50, 2));

        /* Satellite's bstar drag term
           FIXME: How about buff[0] ????
        */
        $this->bstar = (float) (substr($line1, 53, 1) . '.' . substr($line1, 54, 5) . 'E' . substr($line1, 59, 2));

        /* Element Number */
        $this->elset = (int) substr($line1, 64, 4);

        /** Decode Card 2 **/
        /* Satellite's Orbital Inclination (degrees) */
        $this->xincl = (float) substr($line2, 8, 8);

        /* Satellite's RAAN (degrees) */
        $this->xnodeo = (float) substr($line2, 17, 8);

        /* Satellite's Orbital Eccentricity */
        $this->eo = (float) ('.' . substr($line2, 26, 7));

        /* Satellite's Argument of Perigee (degrees) */
        $this->omegao = (float) substr($line2, 34, 8);

        /* Satellite's Mean Anomaly of Orbit (degrees) */
        $this->xmo = (float) substr($line2, 43, 8);

        /* Satellite's Mean Motion (rev/day) */
        $this->xno = (float) substr($line2, 52, 11);

        /* Satellite's Revolution number at epoch */
        $this->revnum = (float) substr($line2, 63, 5);
    }

    /**
     * Build from CCSDS OMM JSON (CelesTrak gp.php?FORMAT=JSON).
     * Accepts single object, list, or JSON string.
     * @throws Predict_Exception on invalid JSON or missing mean elements
     */
    public static function fromOmmJson($omm)
    {
        if (is_string($omm)) {
            $decoded = json_decode(trim($omm), true);
            if (!is_array($decoded)) {
                throw new Predict_Exception('Invalid OMM JSON');
            }
            $omm = $decoded;
        }

        if (isset($omm[0]) && is_array($omm[0])) {
            $omm = $omm[0];
        }

        $required = array(
            'EPOCH', 'MEAN_MOTION', 'ECCENTRICITY', 'INCLINATION',
            'RA_OF_ASC_NODE', 'ARG_OF_PERICENTER', 'MEAN_ANOMALY',
        );
        foreach ($required as $key) {
            if (!array_key_exists($key, $omm)) {
                throw new Predict_Exception('OMM missing key: ' . $key);
            }
        }

        $t = new Predict_TLE();

        $name          = isset($omm['OBJECT_NAME']) ? $omm['OBJECT_NAME'] : '';
        $t->header     = $name;
        $t->sat_name   = $name;
        $t->catnr      = isset($omm['NORAD_CAT_ID']) ? (int) $omm['NORAD_CAT_ID'] : 0;
        $t->idesg      = isset($omm['OBJECT_ID']) ? $omm['OBJECT_ID'] : '';

        // ISO-8601 UTC -> NORAD epoch fields (epoch_day is 1-based).
        try {
            $dt = new DateTime($omm['EPOCH'], new DateTimeZone('UTC'));
        } catch (Exception $e) {
            throw new Predict_Exception('Invalid OMM EPOCH: ' . $omm['EPOCH']);
        }

        $t->epoch_year = (int) $dt->format('Y');
        $t->epoch_day  = (int) $dt->format('z') + 1;

        $hours   = (int) $dt->format('H');
        $minutes = (int) $dt->format('i');
        $secs    = (int) $dt->format('s');
        $micros  = (int) $dt->format('u');
        $tod     = $hours * 3600 + $minutes * 60 + $secs + ($micros / 1000000.0);
        $t->epoch_fod = $tod / 86400.0;

        // Reconstruct YYDDD.FFFFFFFF for parity with TLE parser (propagator doesn't use it).
        $yy       = $dt->format('y');
        $t->epoch = (float) ($yy . sprintf('%03d', $t->epoch_day)) + $t->epoch_fod;

        // Mean elements: same units as TLE parser output.
        $t->xno    = (float) $omm['MEAN_MOTION'];
        $t->xndt2o = isset($omm['MEAN_MOTION_DOT'])  ? (float) $omm['MEAN_MOTION_DOT']  : 0.0;
        $t->xndd6o = isset($omm['MEAN_MOTION_DDOT']) ? (float) $omm['MEAN_MOTION_DDOT'] : 0.0;
        $t->bstar  = isset($omm['BSTAR'])            ? (float) $omm['BSTAR']            : 0.0;
        $t->eo     = (float) $omm['ECCENTRICITY'];
        $t->xincl  = (float) $omm['INCLINATION'];
        $t->xnodeo = (float) $omm['RA_OF_ASC_NODE'];
        $t->omegao = (float) $omm['ARG_OF_PERICENTER'];
        $t->xmo    = (float) $omm['MEAN_ANOMALY'];

        $t->elset  = isset($omm['ELEMENT_SET_NO']) ? (int) $omm['ELEMENT_SET_NO'] : 0;
        $t->revnum = isset($omm['REV_AT_EPOCH'])   ? (int) $omm['REV_AT_EPOCH']   : 0;
        $t->status = isset($omm['EPHEMERIS_TYPE']) ? (int) $omm['EPHEMERIS_TYPE'] : 0;

        $t->xincl1  = null;
        $t->xnodeo1 = null;
        $t->omegao1 = null;

        return $t;
    }

    /* Calculates the checksum mod 10 of a line from a TLE set and */
    /* returns true if it compares with checksum in column 68, else false.*/
    /* tle_set is a character string holding the two lines read    */
    /* from a text file containing NASA format Keplerian elements. */
    /* NOTE!!! The stuff about two lines is not quite true.
       The function assumes that tle_set[0] is the begining
       of the line and that there are 68 elements - see the consumer
    */
    public function Checksum_Good($tle_set)
    {
        if (strlen($tle_set) < 69) {
            return false;
        }

        $checksum = 0;

        for ($i = 0; $i < 68; $i++) {
            if (($tle_set[$i] >= '0') && ($tle_set[$i] <= '9')) {
                $value = $tle_set[$i] - '0';
            } else if ($tle_set[$i] == '-' ) {
                $value = 1;
            } else {
                $value = 0;
            }

            $checksum += $value;
        }

        $checksum   %= 10;
        $check_digit = $tle_set[68] - '0';

        return $checksum == $check_digit;
    }

    /* Carries out various checks on a TLE set to verify its validity */
    /* $line1 is the first line of the TLE, $line2 is the second line */
    /* from a text file containing NASA format Keplerian elements. */
    public function Good_Elements($line1, $line2)
    {
        /* Verify checksum of both lines of a TLE set */
        if (!$this->Checksum_Good($line1) || !$this->Checksum_Good($line2)) {
            return false;
        }

        /* Check the line number of each line */
        if (($line1[0] != '1') || ($line2[0] != '2')) {
            return false;
        }

        /* Verify that Satellite Number is same in both lines */
        if (strncmp($line1[2], $line2[2], 5) != 0) {
            return false;
        }

        /* Check that various elements are in the right place */
        if (($line1[23] != '.') ||
            ($line1[34] != '.') ||
            ($line2[11] != '.') ||
            ($line2[20] != '.') ||
            ($line2[37] != '.') ||
            ($line2[46] != '.') ||
            ($line2[54] != '.') ||
            (strncmp(substr($line1, 61), ' 0 ', 3) != 0)) {

            return false;
        }

        return true;
    }

    /**
     * A function to allow checksum creation of a line.  This is driven by
     * the fact that some TLEs from SpaceTrack are missing checksum numbers.
     * You can use this to create a checksum for a line, but you should
     * probably have confidence that the TLE data itself is good.  YMMV.
     *
     * @throws Predict_Exception if the line is not exactly 68 chars
     * @return string
     */
    static public function createChecksum($line)
    {
        if (strlen($line) != 68) {
            throw Predict_Exception('Invalid line, needs to e 68 chars');
        }

        $checksum = 0;

        for ($i = 0; $i < 68; $i++) {
            if (($line[$i] >= '0') && ($line[$i] <= '9')) {
                $value = (int) $line[$i];
            } else if ($line[$i] == '-' ) {
                $value = 1;
            } else {
                $value = 0;
            }

            $checksum += $value;
        }

        $checksum %= 10;

        return $checksum;
    }

    /**
     * Serialize to two-line TLE (inverse of parser). Render-time shim for TLE-only consumers.
     * Catalog numbers >99999 wrap modulo (cosmetic only). Orbit stays correct because OMM
     * supplies the mean elements plus B* (drag) that SGP4 consumes.
     */
    public function toTwolineTle()
    {
        $cat5  = sprintf('%05d', ((int) $this->catnr) % 100000);
        $class = 'U';
        $idesg = substr(str_pad((string) ($this->idesg ?? ''), 8, ' '), 0, 8);
        $yy    = sprintf('%02d', ((int) $this->epoch_year) % 100);
        $ddd   = sprintf('%03d', (int) $this->epoch_day);
        $frac  = sprintf('%08d', (int) round((float) ($this->epoch_fod ?? 0.0) * 100000000.0));
        $ndot  = $this->formatDotField((float) $this->xndt2o);
        $nddot = $this->formatExpField((float) $this->xndd6o);
        $bstar = $this->formatExpField((float) $this->bstar);
        $eph   = (string) ((int) ($this->status ?? 0));
        $elset = sprintf('%4d', ((int) ($this->elset ?? 0)) % 10000);

        $line1 = '1 ' . $cat5 . $class . ' ' . $idesg . ' ' . $yy . $ddd . '.' . $frac
               . ' ' . $ndot . ' ' . $nddot . ' ' . $bstar . ' ' . $eph . ' ' . $elset;

        $ecc = sprintf('%07d', (int) round((float) $this->eo * 10000000.0));
        $no  = sprintf('%11.8f', (float) $this->xno);
        $rev = sprintf('%5d', ((int) ($this->revnum ?? 0)) % 100000);

        $line2 = '2 ' . $cat5 . ' ' . sprintf('%8.4f', (float) $this->xincl) . ' '
               . sprintf('%8.4f', (float) $this->xnodeo) . ' ' . $ecc . ' '
               . sprintf('%8.4f', (float) $this->omegao) . ' '
               . sprintf('%8.4f', (float) $this->xmo) . ' ' . $no . $rev;

        return $this->finalizeLine($line1) . "\n" . $this->finalizeLine($line2);
    }

    /** Pad/trim to 68 chars and append mod-10 checksum. */
    private function finalizeLine($line)
    {
        if (strlen($line) < 68) {
            $line = str_pad($line, 68, ' ');
        } elseif (strlen($line) > 68) {
            $line = substr($line, 0, 68);
        }
        return $line . self::createChecksum($line);
    }

    /** TLE fixed-point derivative field: sign + . + 8 digits. */
    private function formatDotField($value)
    {
        $sign   = ($value < 0) ? '-' : ' ';
        $digits = (int) round(abs($value) * 100000000.0);
        return $sign . '.' . sprintf('%08d', $digits % 100000000);
    }

    /** TLE mantissa-exponential field (nddot, BSTAR): sign + 5 mantissa + exp. */
    private function formatExpField($value)
    {
        if ($value == 0.0) {
            return ' 00000-0';
        }
        $sign = ($value < 0) ? '-' : ' ';
        $abs  = abs($value);
        $e    = (int) floor(log10($abs)) + 1;
        $mant = (int) round($abs * pow(10.0, 5 - $e));
        if ($mant >= 100000) { $mant -= 90000; $e += 1; }
        $expSign = ($e >= 0) ? '+' : '-';
        return $sign . sprintf('%05d', $mant) . $expSign . (string) min(abs($e), 9);
    }
}
