<?php

namespace Wavelog\Dxcc;

class Dxcc {
	protected $dxcc = array();
	protected $dxccexceptions = array();

	protected $csadditions = '/^(?:X|D|T|P|R|B|A|M|LH|L|J|SK)$/';
	protected $lidadditions = '/^(?:QRP|LGT|2K)$/';
	protected $noneadditions = '/^(?:MM|AM)$/';

	function __construct($date)	{
		$this->read_data($date);
	}

	/**
	 * Helper method to return a standard "NONE" result
	 */
	private function noneResult() {
		return [
			'adif' => 0,
			'entity' => '- NONE -',
			'cqz' => 0,
			'long' => '0',
			'lat' => '0',
			'cont' => null
		];
	}

	/**
	 * Helper method to log DXCC-related errors
	 */
	private function logError($message, $context = []) {
		log_message("Error", "DXCC Error: " . $message . (empty($context) ? '' : ' - Context: ' . json_encode($context)));
	}

	/**
	 * Helper method to check if a date falls within a date range
	 */
	private function isDateInRange($date, $startDate, $endDate) {
		if ($startDate === null && $endDate === null) return true;
		if ($startDate === null) return $date <= $endDate;
		if ($endDate === null) return $date >= $startDate;
		return $date >= $startDate && $date <= $endDate;
	}

	public function dxcc_lookup($call, $date) {
		// Ensure callsign is uppercase for pattern matching
		$call = strtoupper($call);

		if (isset($this->dxccexceptions[$call])) {
			$exceptions = $this->dxccexceptions[$call];

			// Loop through all exceptions for this call
			foreach ($exceptions as $exception) {
				if ($this->isDateInRange($date, $exception['start'], $exception['end']))
					return $exception;
			}
        }

		if (preg_match('/(^KG4)[A-Z09]{3}/', $call)) {       // KG4/ and KG4 5 char calls are Guantanamo Bay. If 4 or 6 char, it is USA
			$call = "K";
		} elseif (preg_match('/(^OH\/)|(\/OH[1-9]?$)/', $call)) {   # non-Aland prefix!
			$call = "OH";                                             # make callsign OH = finland
		} elseif (preg_match('/(^HB\/)|(\/HB[1-9]?$)/', $call)) {   # non-Liechtenstein prefix!
			$call = "HB";                                             # make callsign HB = Switzerland
		} elseif (preg_match('/(^CX\/)|(\/CX[1-9]?$)/', $call)) {   # non-Antarctica prefix!
			$call = "CX";                                             # make callsign CX = Uruguay
		} elseif (preg_match('/(^3D2R)|(^3D2.+\/R)/', $call)) {     # seems to be from Rotuma
			$call = "3D2/R";                                          # will match with Rotuma
		} elseif (preg_match('/^3D2C/', $call)) {                   # seems to be from Conway Reef
			$call = "3D2/C";                                          # will match with Conway
		} elseif (preg_match('/(^LZ\/)|(\/LZ[1-9]?$)/', $call)) {   # LZ/ is LZ0 by DXCC but this is VP8h
			$call = "LZ";
		} elseif (preg_match('/(^KG4)[A-Z09]{2}/', $call)) {
			$call = "KG4";
		} elseif (preg_match('/(^KG4)[A-Z09]{1}/', $call)) {
			$call = "K";
		} elseif (preg_match('/\w\/\w/', $call)) {
			if (preg_match_all('/^((\d|[A-Z])+\/)?((\d|[A-Z]){3,})(\/(\d|[A-Z])+)?(\/(\d|[A-Z])+)?$/', $call, $matches)) {
				$prefix = $matches[1][0];
				$callsign = $matches[3][0];
				$suffix = $matches[5][0];

				if (!$callsign) {
					$this->logError('Failed to parse callsign', [
						'call' => $call,
						'date' => $date,
						'matches' => $matches
					]);
				}
				if ($prefix) {
					$prefix = substr($prefix, 0, -1); # Remove the / at the end
				}
				if ($suffix) {
					$suffix = substr($suffix, 1); # Remove the / at the beginning
				};
				if (preg_match($this->csadditions, $suffix)) {
					if ($prefix) {
						$call = $prefix;
					} else {
						$call = $callsign;
					}
				} else {
					$result = $this->wpx($prefix, $callsign, $suffix, 1, $call);                       # use the wpx prefix instead
					if ($result == '') {
						return $this->noneResult();
					} else {
						$call = $result . "AA";
					}
				}
			}
		}

		$len = strlen($call);

		// query the table, removing a character from the right until a match
		for ($i = $len; $i > 0; $i--){
			$prefix = substr($call, 0, $i);

			if (isset($this->dxcc[$prefix])) {
				$dxccEntries = $this->dxcc[$prefix];

				// Loop through all entries for this call prefix
				foreach ($dxccEntries as $dxccEntry) {
					if ($this->isDateInRange($date, $dxccEntry['start'], $dxccEntry['end']))
						return $dxccEntry;
				}
			}
		}

		return $this->noneResult();
	}

	function wpx($prefix, $callsign, $suffix, $i, $testcall) {
		$pfx = '';

		$a = $prefix;
		$b = $callsign;
		$c = $suffix ?? '';

		# In some cases when there is no part A but B and C, and C is longer than 2
		# letters, it happens that $a and $b get the values that $b and $c should
		# have. This often happens with liddish callsign-additions like /QRP and
		# /LGT, but also with calls like DJ1YFK/KP5. ~/.yfklog has a line called
		# "lidadditions", which has QRP and LGT as defaults. This sorts out half of
		# the problem, but not calls like DJ1YFK/KH5. This is tested in a second
		# try: $a looks like a call (.\d[A-Z]) and $b doesn't (.\d), they are
		# swapped. This still does not properly handle calls like DJ1YFK/KH7K where
		# only the OP's experience says that it's DJ1YFK on KH7K.
		if (!$c && $a && $b) {                          # $a and $b exist, no $c
			if (preg_match($this->lidadditions, $b) || preg_match('/^[0-9]+$/', $b)) {        # check if $b is a lid-addition
				$b = $a;
				$a = null;                              # $a goes to $b, delete lid-add
			} elseif ((preg_match('/\d[A-Z]+$/', $a)) && (preg_match('/\d$/', $b) || preg_match('/^[A-Z]\d[A-Z]$/', $b) || preg_match('/^\d[A-Z]+$/', $b))) {
				$temp = $b;
				$b = $a;
				$a = $temp;
			}
			# Additional check: if $a looks like a full callsign (longer than typical prefix)
			# and $b looks like a country prefix (short, with digit), swap them
			# This handles cases like JA0JHQ/VK9X where VK9X should be the prefix
			elseif (strlen($a) >= 5 && preg_match('/^\d?[A-Z]+\d[A-Z]+$/', $a) && strlen($b) <= 5 && preg_match('/^[A-Z]+\d[A-Z]*$/', $b)) {
				$temp = $b;
				$b = $a;
				$a = $temp;
			}
		}

		if (preg_match('/^[0-9]+$/', $b)) {            # Callsign only consists of numbers. Bad!
			return null;
		}

		if (preg_match('/^[0-9]{2,}$/', $c)) {            # If suffix consists of two or more digits -> ignore suffix, To catch callsigns like VP8ADR/40
			$c = null;
		}

		if (preg_match('/^[A-Z]{1}$/', $c ?? '')) {            # If suffix consists of exactly one letter -> ignore suffix, To catch callsigns like LU7CC/E
			$c = null;
		}

		# Depending on these values we have to determine the prefix.
		# Following cases are possible:
		#
		# 1.    $a and $c undef --> only callsign, subcases
		# 1.1   $b contains a number -> everything from start to number
		# 1.2   $b contains no number -> first two letters plus 0
		# 2.    $a undef, subcases:
		# 2.1   $c is only a number -> $a with changed number
		# 2.2   $c is /P,/M,/MM,/AM -> 1.
		# 2.3   $c is something else and will be interpreted as a Prefix
		# 3.    $a is defined, will be taken as PFX, regardless of $c

		if (($a == null) && ($c == null)) {                     # Case 1
			if (preg_match('/\d/', $b)) {                       # Case 1.1, contains number
				if (!preg_match('/(.+\d)[A-Z]*/', $b, $matches)) {
					$this->logError('preg_match failed to extract prefix from callsign', [
						'testcall' => $testcall,
						'b' => $b
					]);
					return '';
				}
				$pfx = $matches[1];                          # Letters
			} else {                                            # Case 1.2, no number
				$pfx = substr($b, 0, 2) . "0";               # first two + 0
			}
		} elseif (($a == null) && ($c != null && $c != '')) {    # Case 2, CALL/X
			if (preg_match($this->lidadditions, $c)) {        # check if $b is a lid-addition
				$pfx = $b;
			} else if (preg_match('/^(\d)/', $c)) {                    # Case 2.1, starts with digit
				# Check if $c is a full country prefix (like 6YA, 6Y, etc.) not just a number
				# A country prefix has the pattern: digit + letters (like 6YA, 6Y)
				# NOT just a single digit
				if (strlen($c) > 1 && preg_match('/^\d[A-Z]+$/', $c)) {
					# This is a country prefix starting with a digit (like 6YA, 6Y)
					# Use it directly - it's already a valid prefix
					$pfx = $c;
				} elseif (strlen($c) > 1 && preg_match('/^[A-Z]+\d[A-Z]*$/', $c)) {
					# Country prefix starting with letters (like W1, K2, etc.)
					$pfx = $c;
				} else {
					# Single digit, replace the digit in the base call                # Case 2.1, starts with digit
					preg_match('/(.+\d)[A-Z]*/', $b, $matches);     # regular Prefix in $1
					# Here we need to find out how many digits there are in the
					# prefix, because for example A45XR/0 is A40. If there are 2
					# numbers, the first is not deleted. If course in exotic cases
					# like N66A/7 -> N7 this brings the wrong result of N67, but I
					# think that's rather irrelevant cos such calls rarely appear
					# and if they do, it's very unlikely for them to have a number
					# attached.   You can still edit it by hand anyway..
					if (!isset($matches[1]) || $matches[1] === null) {
						$this->logError('preg_match failed to capture prefix in $b', [
							'testcall' => $testcall,
							'b' => $b,
							'c' => $c,
							'matches' => $matches
						]);
						return '';
					}
					if (preg_match('/^([A-Z]\d{2,})$/', $matches[1])) {        # e.g. A45   $c = 0
						$pfx = $matches[1] . $c;  # ->   A40
					} else {                         # Otherwise cut all numbers
						if (!preg_match('/(.*[A-Z])\d+/', $matches[1], $match)) {
							$this->logError('preg_match failed to extract prefix without number', [
								'testcall' => $testcall,
								'matches1' => $matches[1],
								'b' => $b,
								'c' => $c
							]);
							return '';
						}
						$pfx = $match[1] . $c; # Add attached number
					}
				}
			} elseif (preg_match($this->csadditions, $c)) {
				if (!preg_match('/(.+\d)[A-Z]*/', $b, $matches)) {
					$this->logError('preg_match failed for csadditions case', [
						'testcall' => $testcall,
						'b' => $b,
						'c' => $c
					]);
					return '';
				}
				$pfx = $matches[1];
			} elseif (preg_match($this->noneadditions, $c)) {
				return '';
			} elseif (preg_match('/^\d\d+$/', $c)) {             # more than 2 numbers -> ignore
				if (!preg_match('/(.+\d)[A-Z]*/', $b, $matches)) {
					$this->logError('preg_match failed for multi-digit case', [
						'testcall' => $testcall,
						'b' => $b,
						'c' => $c
					]);
					return '';
				}
				$pfx = $matches[1][0];
			} else {                                            # Must be a Prefix!
				# Check if $c looks like a country prefix
				# Pattern: starts with digit followed by letters (6YA, 6Y) OR has digit in it
				if (preg_match('/^\d[A-Z]+$/', $c)) {           # Starts with digit, has letters after (6YA, 6Y, etc.)
					$pfx = $c;                               # Already a valid prefix
				} elseif (preg_match('/\d$/', $c)) {            # ends in number -> good prefix
					$pfx = $c;
				} elseif (preg_match('/\d/', $c)) {             # contains digit but doesn't end with one
					$pfx = $c . "0";                         # Add zero at end
				} else {                                        # No digit, add zero
					$pfx = $c . "0";
				}
			}
		} elseif (($a) && (preg_match($this->noneadditions, $c ?? ''))) {                # Case 2.1, X/CALL/X ie TF/DL2NWK/MM - DXCC none
			return '';
		} elseif ($a) {
			# $a contains the prefix we want
			if (preg_match('/\d$/', $a)) {                      # ends in number -> good prefix
				$pfx = $a;
			} else {
				$pfx = $a . "0";
			}
		}
		# In very rare cases (right now I can only think of KH5K and KH7K and FRxG/T
		# etc), the prefix is wrong, for example KH5K/DJ1YFK would be KH5K0. In this
		# case, the superfluous part will be cropped. Since this, however, changes the
		# DXCC of the prefix, this will NOT happen when invoked from with an
		# extra parameter $_[1]; this will happen when invoking it from &dxcc.

		if (preg_match('/(\w+\d)[A-Z]+\d/', $pfx, $matches) && $i == null) {
			if (!isset($matches[1][0])) {
				$this->logError('preg_match failed to extract prefix in rare case', [
					'testcall' => $testcall,
					'prefix' => $pfx,
					'matches' => $matches
				]);
			} else {
				$pfx = $matches[1][0];
			}
		}
		return $pfx;
	}

	/*
    * Read DXCC data from the database or cache
    */
    function read_data($date = NULL) {
		$CI = &get_instance();
		$CI->load->driver('cache', ['adapter' => 'file']);

		// DXCC Exceptions
		$cache_key = 'dxcc_exceptions_' . ($date ?? 'all');

		// Cache check - early return
		if ($cached = $CI->cache->get($cache_key)) {
			$this->dxccexceptions = $cached;
		} else {
			$sql = "SELECT `call`, `entity`, `adif`, `cqz`, `cont`, `long`, `lat`, `start`, `end` FROM dxcc_exceptions";
			$binding = [];

			if ($date !== NULL) {
				$sql .= " WHERE (start <= ? OR start IS NULL) AND (end >= ? OR end IS NULL)";
				$binding = [$date, $date];
			}

			$sql .= " ORDER BY start DESC, end DESC";

			$this->dxccexceptions = [];
			foreach ($CI->db->query($sql, $binding)->result() as $dxcce) {
                $this->dxccexceptions[$dxcce->call][] = [
                    'adif' => $dxcce->adif,
                    'cont' => $dxcce->cont,
                    'entity' => $dxcce->entity,
                    'cqz' => $dxcce->cqz,
					'start' => $dxcce->start ?? NULL,
					'end' => $dxcce->end ?? NULL,
                    'long' => $dxcce->long,
                    'lat' => $dxcce->lat
                ];
            }

			$CI->cache->save($cache_key, $this->dxccexceptions, 14400); // Cache for 4 hours
		}

		// DXCC Prefixes
		$cache_key = 'dxcc_prefixes_' . ($date ?? 'all');

		if ($CI->cache->get($cache_key)) {
			$this->dxcc = $CI->cache->get($cache_key);
		} else {
			$binding = [];
			$sql = "SELECT `call`, `entity`, `adif`, `cqz`, `cont`, `long`, `lat`, `start`, `end` FROM dxcc_prefixes";
			if ($date !== NULL) {
				$sql .= " WHERE (start <= ? OR start IS NULL)
						AND (end >= ? OR end IS NULL)";
				$binding[] = $date;
				$binding[] = $date;
			}
			$sql .= " ORDER BY start DESC, end DESC";
			$dxcc_result = $CI->db->query($sql, $binding);

			if ($dxcc_result->num_rows() > 0){
				foreach ($dxcc_result->result() as $dx) {
					$this->dxcc[$dx->call][] = [
						'adif' => $dx->adif,
						'cont' => $dx->cont,
						'entity' => $dx->entity,
						'cqz' => $dx->cqz,
						'start' => $dx->start ?? NULL,
						'end' => $dx->end ?? NULL,
						'long' => $dx->long,
						'lat' => $dx->lat
					];
				}
			}
			$CI->cache->save($cache_key, $this->dxcc, 14400); // Cache for 4 hours
		}
    }
}
