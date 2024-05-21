<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Cron expression parser and validator
 *
 * @author René Pollesch
 * edited by HB9HIL 04/2024
 * 
 * Source: https://github.com/poliander/cron
 * Lic: GnuGPL 3
 */
class CronExpression {
    /**
     * Weekday name look-up table
     */
    private const WEEKDAY_NAMES = [
        'sun' => 0,
        'mon' => 1,
        'tue' => 2,
        'wed' => 3,
        'thu' => 4,
        'fri' => 5,
        'sat' => 6
    ];

    /**
     * Month name look-up table
     */
    private const MONTH_NAMES = [
        'jan' => 1,
        'feb' => 2,
        'mar' => 3,
        'apr' => 4,
        'may' => 5,
        'jun' => 6,
        'jul' => 7,
        'aug' => 8,
        'sep' => 9,
        'oct' => 10,
        'nov' => 11,
        'dec' => 12
    ];

    /**
     * Value boundaries
     */
    private const VALUE_BOUNDARIES = [
        0 => [
            'min' => 0,
            'max' => 59,
            'mod' => 1
        ],
        1 => [
            'min' => 0,
            'max' => 23,
            'mod' => 1
        ],
        2 => [
            'min' => 1,
            'max' => 31,
            'mod' => 1
        ],
        3 => [
            'min' => 1,
            'max' => 12,
            'mod' => 1
        ],
        4 => [
            'min' => 0,
            'max' => 7,
            'mod' => 0
        ]
    ];

    /**
     * @expression look-up table
     */
    private const SPECIAL_EXPRESSIONS = [
        '@yearly' => '0 0 1 1 *',
        '@annually' => '0 0 1 1 *',
        '@monthly' => '0 0 1 * *',
        '@weekly' => '0 0 * * 0',
        '@daily' => '0 0 * * *',
        '@midnight' => '0 0 * * *',
        '@hourly' => '0 * * * *'
    ];
    

    /**
     * @var DateTimeZone|null
     */
    protected readonly ?DateTimeZone $timeZone;

    /**
     * @var array|null
     */
    protected readonly ?array $registers;

    /**
     * @var string
     */
    protected readonly string $expression;

    /**
     * @param string $expression a cron expression, e.g. "* * * * *"
     * @param DateTimeZone|null $timeZone time zone objectstring $expression, DateTimeZone $timeZone = null
     */
    public function __construct($data) {
        $this->timeZone = $data['timeZone'];
        $this->expression = $data['expression'];

        try {
            $this->registers = $this->parse($data['expression']);
        } catch (Exception $e) {
            $this->registers = null;
        }
    }

    /**
     * Whether current cron expression has been parsed successfully
     *
     * @return bool
     */
    public function isValid(): bool {
        return null !== $this->registers;
    }

    /**
     * Match either "now", a given date/time object or a timestamp against current cron expression
     *
     * @param mixed $when a DateTime object, a timestamp (int), or "now" if not set
     * @return bool
     * @throws Exception
     */
    public function isMatching($when = null): bool {
        if (false === ($when instanceof DateTimeInterface)) {
            $when = (new DateTime())->setTimestamp($when === null ? time() : $when);
        }

        if ($this->timeZone !== null) {
            $when->setTimezone($this->timeZone);
        }

        return $this->isValid() && $this->match(sscanf($when->format('i G j n w'), '%d %d %d %d %d'));
    }

    /**
     * Calculate next matching timestamp
     *
     * @param mixed $start a DateTime object, a timestamp (int) or "now" if not set
     * @return int|bool next matching timestamp, or false on error
     * @throws Exception
     */
    public function getNext($start = null) {
        if ($this->isValid()) {
            $next = $this->toDateTime($start);

            do {
                $pos = sscanf($next->format('i G j n Y w'), '%d %d %d %d %d %d');
            } while ($this->increase($next, $pos));

            return $next->getTimestamp();
        }

        return false;
    }

    /**
     * @param mixed $start a DateTime object, a timestamp (int) or "now" if not set
     * @return DateTime
     */
    private function toDateTime($start): DateTime {
        if ($start instanceof DateTimeInterface) {
            $next = $start;
        } elseif ((int)$start > 0) {
            $next = new DateTime('@' . $start);
        } else {
            $next = new DateTime('@' . time());
        }

        $next->setTimestamp($next->getTimeStamp() - $next->getTimeStamp() % 60);
        $next->setTimezone($this->timeZone ?: new DateTimeZone(date_default_timezone_get()));

        if ($this->isMatching($next)) {
            $next->modify('+1 minute');
        }

        return $next;
    }

    /**
     * Increases the timestamp in step sizes depending on which segment(s) of the cron pattern are matching.
     * Returns FALSE if the cron pattern is matching and thus no further cycle is required.
     *
     * @param DateTimeInterface $next
     * @param array $pos
     * @return bool
     */
    private function increase(DateTimeInterface $next, array $pos): bool {
        switch (true) {
            case false === isset($this->registers[3][$pos[3]]):
                // next month, reset day/hour/minute
                $next->setTime(0, 0);
                $next->setDate($pos[4], $pos[3], 1);
                $next->modify('+1 month');
                return true;

            case false === (isset($this->registers[2][$pos[2]]) && isset($this->registers[4][$pos[5]])):
                // next day, reset hour/minute
                $next->setTime(0, 0);
                $next->modify('+1 day');
                return true;

            case false === isset($this->registers[1][$pos[1]]):
                // next hour, reset minute
                $next->setTime($pos[1], 0);
                $next->modify('+1 hour');
                return true;

            case false === isset($this->registers[0][$pos[0]]):
                // next minute
                $next->modify('+1 minute');
                return true;

            default:
                // all segments are matching
                return false;
        }
    }

    /**
     * @param array $segments
     * @return bool
     */
    private function match(array $segments): bool {
        foreach ($this->registers as $i => $item) {
            if (isset($item[(int)$segments[$i]]) === false) {
                return false;
            }
        }

        return true;
    }

    /**
     * Parse whole cron expression
     *
     * @param string $expression
     * @return array
     * @throws Exception
     */
    private function parse(string $expression): array {
        $segments = preg_split('/\s+/', trim($expression));

        if (is_array($segments) && sizeof($segments) === 5) {
            $registers = array_fill(0, 5, []);

            foreach ($segments as $index => $segment) {
                $this->parseSegment($registers[$index], $index, $segment);
            }

            $this->validateDate($registers);

            if (isset($registers[4][7])) {
                $registers[4][0] = true;
            }

            return $registers;

        } else if (strpos($expression, '@') === 0) {

            $special = trim($expression);

            if (isset(self::SPECIAL_EXPRESSIONS[$special])) {

                $special_expression = self::SPECIAL_EXPRESSIONS[$special];
                return $this->parse($special_expression);

            }
        }
        throw new Exception('invalid number of segments');
    }

    /**
     * Parse one segment of a cron expression
     *
     * @param array $register
     * @param int $index
     * @param string $segment
     * @throws Exception
     */
    private function parseSegment(array &$register, int $index, string $segment): void {
        $allowed = [false, false, false, self::MONTH_NAMES, self::WEEKDAY_NAMES];

        // month names, weekdays
        if ($allowed[$index] !== false && isset($allowed[$index][strtolower($segment)])) {
            // cannot be used together with lists or ranges
            $register[$allowed[$index][strtolower($segment)]] = true;
        } else {
            // split up current segment into single elements, e.g. "1,5-7,*/2" => [ "1", "5-7", "*/2" ]
            foreach (explode(',', $segment) as $element) {
                $this->parseElement($register, $index, $element);
            }
        }
    }

    /**
     * @param array $register
     * @param int $index
     * @param string $element
     * @throws Exception
     */
    private function parseElement(array &$register, int $index, string $element): void {
        $step = 1;
        $segments = explode('/', $element);

        if (sizeof($segments) > 1) {
            $this->validateStepping($segments, $index);

            $element = (string)$segments[0];
            $step = (int)$segments[1];
        }

        if (is_numeric($element)) {
            $this->validateValue($element, $index, $step);
            $register[intval($element)] = true;
        } else {
            $this->parseRange($register, $index, $element, $step);
        }
    }

    /**
     * Parse range of values, e.g. "5-10"
     *
     * @param array $register
     * @param int $index
     * @param string $range
     * @param int $stepping
     * @throws Exception
     */
    private function parseRange(array &$register, int $index, string $range, int $stepping): void {
        if ($range === '*') {
            $rangeArr = [self::VALUE_BOUNDARIES[$index]['min'], self::VALUE_BOUNDARIES[$index]['max']];
        } else {
            $rangeArr = explode('-', $range);
        }

        $this->validateRange($rangeArr, $index);
        $this->fillRange($register, $index, $rangeArr, $stepping);
    }

    /**
     * @param array $register
     * @param int $index
     * @param array $range
     * @param int $stepping
     */
    private function fillRange(array &$register, int $index, array $range, int $stepping): void {
        $boundary = self::VALUE_BOUNDARIES[$index]['max'] + self::VALUE_BOUNDARIES[$index]['mod'];
        $length = $range[1] - $range[0];

        for ($i = 0; $i <= $length; $i += $stepping) {
            $register[($range[0] + $i) % $boundary] = true;
        }
    }

    /**
     * Validate whether a given range of values exceeds allowed value boundaries
     *
     * @param array $range
     * @param int $index
     * @throws Exception
     */
    private function validateRange(array $range, int $index): void {
        if (sizeof($range) !== 2) {
            throw new Exception('invalid range notation');
        }

        foreach ($range as $value) {
            $this->validateValue($value, $index);
        }

        if ($range[0] > $range[1]) {
            throw new Exception('lower value in range is larger than upper value');
        }
    }

    /**
     * @param string $value
     * @param int $index
     * @param int $step
     * @throws Exception
     */
    private function validateValue(string $value, int $index, int $step = 1): void {
        if (false === ctype_digit($value)) {
            throw new Exception('non-integer value');
        }

        if (
            intval($value) < self::VALUE_BOUNDARIES[$index]['min'] ||
            intval($value) > self::VALUE_BOUNDARIES[$index]['max']
        ) {
            throw new Exception('value out of boundary');
        }

        if ($step !== 1) {
            throw new Exception('invalid combination of value and stepping notation');
        }
    }

    /**
     * @param array $segments
     * @param int $index
     * @throws Exception
     */
    private function validateStepping(array $segments, int $index): void {
        if (sizeof($segments) !== 2) {
            throw new Exception('invalid stepping notation');
        }

        if ((int)$segments[1] < 1 || (int)$segments[1] > self::VALUE_BOUNDARIES[$index]['max']) {
            throw new Exception('stepping out of allowed range');
        }
    }

    /**
     * @param array $segments
     * @throws Exception
     */
    private function validateDate(array $segments): void {
        $year = date('Y');

        for ($y = 0; $y < 27; $y++) {
            foreach (array_keys($segments[3]) as $month) {
                foreach (array_keys($segments[2]) as $day) {
                    if (false === checkdate($month, $day, $year + $y)) {
                        continue;
                    }

                    if (false === isset($segments[date('w', strtotime(sprintf('%d-%d-%d', $year + $y, $month, $day)))])) {
                        continue;
                    }

                    return;
                }
            }
        }

        throw new Exception('no date ever can match the given combination of day/month/weekday');
    }
}
