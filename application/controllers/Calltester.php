<?php
use Wavelog\Dxcc\Dxcc;

require_once APPPATH . '../src/Dxcc/Dxcc.php';

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Calltester extends CI_Controller {
	function __construct() {
		parent::__construct();

		$this->load->model('user_model');
		if(!$this->user_model->authorize(99)) { $this->session->set_flashdata('error', __("You're not allowed to do that!")); redirect('dashboard'); }
	}


	public function index() {
        set_time_limit(3600);

        // Starting clock time in seconds
        $start_time = microtime(true);

		$this->load->model('stations');

		$data['station_profile'] = $this->stations->all_of_user();

		$footerData = [];
		$footerData['scripts'] = [
			'assets/js/sections/calltester.js?' . filemtime(realpath(__DIR__ . "/../../assets/js/sections/calltester.js"))
		];

		$data['page_title'] = __("Call Tester");
		$this->load->view('interface_assets/header', $data);
		$this->load->view('calltester/index');
		$this->load->view('interface_assets/footer', $footerData);
	}

	function doDxccCheck() {
		set_time_limit(3600);
		$de = $this->input->post('de', true);
		$compare = $this->input->post('compare', true);

		if ($compare == "true") {
			$result = $this->doClassCheck($de);
			$result2 = $this->doDxccCheckModel($de);

			return $this->compareDxccChecks($result, $result2);
		}

		$result = $this->doClassCheck($de);
		$this->loadView($result);
	}

	/* Uses DXCC Class. Much faster */
	function doClassCheck($de) {
		ini_set('memory_limit', '-1');
		$i = 0;
		$result = array();

		$callarray = $this->getQsos($de);

		// Starting clock time in seconds
		$start_time = microtime(true);
		$dxccobj = new Dxcc(null);

		foreach ($callarray->result() as $call) {

            $i++;
			$dxcc = $dxccobj->dxcc_lookup($call->col_call, $call->date);

            $dxcc['adif'] = (isset($dxcc['adif'])) ? $dxcc['adif'] : 0;
            $dxcc['entity'] = (isset($dxcc['entity'])) ? $dxcc['entity'] : 'None';

            if ($call->col_dxcc != $dxcc['adif']) {
                $result[] = array(
                                'callsign'          => $call->col_call,
								'qso_date'          => $call->date,
								'station_profile'   => $call->station_profile_name,
                                'existing_dxcc'     => $call->col_country,
                                'existing_adif'     => $call->col_dxcc,
                                'result_country'    => ucwords(strtolower($dxcc['entity']), "- (/"),
                                'result_adif'       => $dxcc['adif'],
								'id' 			    => $call->col_primary_key,
                            );
            }
        }

        // End clock time in seconds
        $end_time = microtime(true);

        // Calculate script execution time
        $execution_time = ($end_time - $start_time);

        $data['execution_time'] = $execution_time;
        $data['calls_tested'] = $i;
		$data['result'] = $result;

		return $data;
	}

	/* Uses the normal dxcc lookup, which is slow */
	function doDxccCheckModel($de) {
		$i = 0;
		$result = array();

		$callarray = $this->getQsos($de);

		// Starting clock time in seconds
		$start_time = microtime(true);

		foreach ($callarray->result() as $call) {
            $i++;
            $dxcc = $this->dxcc_lookup($call->col_call, $call->date);

            $dxcc['adif'] = (isset($dxcc['adif'])) ? $dxcc['adif'] : 0;
            $dxcc['entity'] = (isset($dxcc['entity'])) ? $dxcc['entity'] : 0;

            if ($call->col_dxcc != $dxcc['adif']) {
                $result[] = array(
                                'callsign'          => $call->col_call,
								'qso_date'          => $call->date,
								'station_profile'   => $call->station_profile_name,
                                'existing_dxcc'     => $call->col_country,
                                'existing_adif'     => $call->col_dxcc,
                                'result_country'    => ucwords(strtolower($dxcc['entity']), "- (/"),
                                'result_adif'       => $dxcc['adif'],
								'id' 			    => $call->col_primary_key,
                            );
            }
        }

        // End clock time in seconds
        $end_time = microtime(true);

        // Calculate script execution time
        $execution_time = ($end_time - $start_time);

        $data['execution_time'] = $execution_time;
        $data['calls_tested'] = $i;
		$data['result'] = $result;

		return $data;
	}

	function loadView($data) {
		$this->load->view('calltester/result', $data);
	}

	function compareDxccChecks($result, $result2) {
		// Convert arrays to comparable format using callsign, qso_date, and id as unique keys
		$classCheckItems = [];
		$modelCheckItems = [];

		// Create associative arrays for easier comparison
		foreach ($result['result'] as $item) {
			$key = $item['callsign'] . '|' . $item['qso_date'] . '|' . $item['id'];
			$classCheckItems[$key] = $item;
		}

		foreach ($result2['result'] as $item) {
			$key = $item['callsign'] . '|' . $item['qso_date'] . '|' . $item['id'];
			$modelCheckItems[$key] = $item;
		}

		// Find items that are in class check but not in model check
		$onlyInClass = array_diff_key($classCheckItems, $modelCheckItems);

		// Find items that are in model check but not in class check
		$onlyInModel = array_diff_key($modelCheckItems, $classCheckItems);

		// Prepare comparison data
		$comparisonData = [];
		$comparisonData['class_execution_time'] = $result['execution_time'];
		$comparisonData['model_execution_time'] = $result2['execution_time'];
		$comparisonData['class_calls_tested'] = $result['calls_tested'];
		$comparisonData['model_calls_tested'] = $result2['calls_tested'];
		$comparisonData['class_total_issues'] = count($result['result']);
		$comparisonData['model_total_issues'] = count($result2['result']);
		$comparisonData['only_in_class'] = $onlyInClass;
		$comparisonData['only_in_model'] = $onlyInModel;
		$comparisonData['common_issues'] = array_intersect_key($classCheckItems, $modelCheckItems);

		$this->load->view('calltester/comparison_result', $comparisonData);
	}

	function getQsos($station_id) {
		ini_set('memory_limit', '-1');
		$sql = 'select distinct col_country, col_call, col_dxcc, date(col_time_on) date, station_profile.station_profile_name, col_primary_key
			from ' . $this->config->item('table_name') . '
			join station_profile on ' . $this->config->item('table_name') . '.station_id = station_profile.station_id
			where station_profile.user_id = ?';
		$params[] = $this->session->userdata('user_id');

		if ($station_id && is_numeric($station_id)) {
			$sql .= ' and ' . $this->config->item('table_name') . '.station_id = ?';
			$params[] = $station_id;
		}

		$sql .= ' order by station_profile.station_profile_name asc, date desc';

        $query = $this->db->query($sql, $params);

		return $query;
	}

    function array_to_table($table) {
        echo '<style>
        table {
            font-family: Arial, Helvetica, sans-serif;
            border-collapse: collapse;
            width: 100%;
        }

        table td, table th {
            border: 1px solid #ddd;
            padding: 4px;
        }

        table tr:nth-child(even){background-color: #f2f2f2;}

        table tr:hover {background-color: #ddd;}

        table th {
            padding-top: 4px;
            padding-bottom: 4px;
            text-align: left;
            background-color: #04AA6D;
            color: white;
        }
        </style> ';

       echo '<table>';

       // Table header
        foreach ($table[0] as $key=>$value) {
            echo "<th>".$key."</th>";
        }

        // Table body
        foreach ($table as $value) {
            echo "<tr>";
            foreach ($value as $val) {
                    echo "<td>".$val."</td>";
            }
            echo "</tr>";
        }
       echo "</table>";
    }

    function csv() {
        set_time_limit(3600);

        // Starting clock time in seconds
        $start_time = microtime(true);

        $file = 'uploads/calls.csv';
        $handle = fopen($file,"r");

        $data = fgetcsv($handle,1000,","); // Skips firsts line, usually that is the header
        $data = fgetcsv($handle,1000,",");

        $result = array();

        $i = 0;

        do {
            if ($data[0]) {
                // COL_CALL,COL_DXCC,COL_TIME_ON
                $i++;

                $dxcc = $this->dxcc_lookup($data[0], $data[2]);

                $dxcc['adif'] = (isset($dxcc['adif'])) ? $dxcc['adif'] : 0;
                $dxcc['entity'] = (isset($dxcc['entity'])) ? $dxcc['entity'] : 0;

                $data[1] = $data[1] == "NULL" ? 0 : $data[1];

                if ($data[1] != $dxcc['adif']) {
                    $result[] = array(
                                    'Callsign'          => $data[0],
                                    'Expected country'  => '',
                                    'Expected adif'     => $data[1],
                                    'Result country'    => ucwords(strtolower($dxcc['entity']), "- (/"),
                                    'Result adif'       => $dxcc['adif'],
                                );
                }
            }
        } while ($data = fgetcsv($handle,1000,","));

        // End clock time in seconds
        $end_time = microtime(true);

        // Calculate script execution time
        $execution_time = ($end_time - $start_time);

        echo " Execution time of script = ".$execution_time." sec <br/>";
        echo $i . " calls tested. <br/>";
        $count = 0;

        if ($result) {
            $this->array_to_table($result);
        }
    }

    /*
     * Uses check_dxcc_table - written to check if that function works
     */
    function csv2() {
        set_time_limit(3600);

        // Starting clock time in seconds
        $start_time = microtime(true);

        $file = 'uploads/calls.csv';
        $handle = fopen($file,"r");

        $data = fgetcsv($handle,1000,","); // Skips firsts line, usually that is the header
        $data = fgetcsv($handle,1000,",");

        $result = array();

        $i = 0;

        do {
            if ($data[0]) {
                // COL_CALL,COL_DXCC,COL_TIME_ON
                $i++;

                $dxcc = $this->check_dxcc_table($data[0], $data[2]);

                $data[1] = $data[1] == "NULL" ? 0 : $data[1];

                if ($data[1] != $dxcc[0]) {
                    $result[] = array(
                                    'Callsign'          => $data[0],
                                    'Expected country'  => '',
                                    'Expected adif'     => $data[1],
                                    'Result country'    => ucwords(strtolower($dxcc[1]), "- (/"),
                                    'Result adif'       => $dxcc[0],
                                );
                }
            }
        } while ($data = fgetcsv($handle,1000,","));

        // End clock time in seconds
        $end_time = microtime(true);

        // Calculate script execution time
        $execution_time = ($end_time - $start_time);

        echo " Execution time of script = ".$execution_time." sec <br/>";
        echo $i . " calls tested. <br/>";
        $count = 0;

        if ($result) {
            $this->array_to_table($result);
        }
    }

    function call() {
        $testarray = array();

		$testarray[] = array(
            'Callsign'  => 'WJ7R/C6A',
            'Country'   => 'Bahamas',
            'Adif'      => 60,
            'Date'      => $date = date('Y-m-d', time())
        );

		$testarray[] = array(
            'Callsign'  => 'WJ7R/KH6',
            'Country'   => 'Hawaii',
            'Adif'      => 110,
            'Date'      => $date = date('Y-m-d', time())
        );

		$testarray[] = array(
            'Callsign'  => 'WJ7R/C6',
            'Country'   => 'Bahamas',
            'Adif'      => 60,
            'Date'      => $date = date('Y-m-d', time())
        );

        $testarray[] = array(
            'Callsign'  => 'VE3EY/VP9',
            'Country'   => 'Bermuda',
            'Adif'      => 64,
            'Date'      => $date = date('Y-m-d', time())
        );

        $testarray[] = array(
            'Callsign'  => 'VP2MDG',
            'Country'   => 'Montserrat',
            'Adif'      => 96,
            'Date'      => $date = date('Y-m-d', time())
        );

        $testarray[] = array(
            'Callsign'  => 'VP2EY',
            'Country'   => 'Anguilla',
            'Adif'      => 12,
            'Date'      => $date = date('Y-m-d', time())
        );

        $testarray[] = array(
            'Callsign'  => 'VP2VI',
            'Country'   => 'British Virgin Islands.',
            'Adif'      => 65,
            'Date'      => $date = date('Y-m-d', time())
        );

        $testarray[] = array(
            'Callsign'  => 'VP2V/AA7V',
            'Country'   => 'British Virgin Islands',
            'Adif'      => 65,
            'Date'      => $date = date('Y-m-d', time())
        );

        $testarray[] = array(
            'Callsign'  => 'W8LR/R',
            'Country'   => 'United States Of America',
            'Adif'      => 291,
            'Date'      => $date = date('Y-m-d', time())
        );

        $testarray[] = array(
            'Callsign'  => 'SO1FH',
            'Country'   => 'Poland',
            'Adif'      => 269,
            'Date'      => $date = date('Y-m-d', time())
        );

        $testarray[] = array(
            'Callsign'  => 'KZ1H/PP',
            'Country'   => 'Brazil',
            'Adif'      => 108,
            'Date'      => $date = date('Y-m-d', time())
        );

        $testarray[] = array(
            'Callsign'  => 'K1KW/AM',
            'Country'   => 'None',
            'Adif'      => 0,
            'Date'      => $date = date('Y-m-d', time())
        );

        $testarray[] = array(
            'Callsign'  => 'K1KW/MM',
            'Country'   => 'None',
            'Adif'      => 0,
            'Date'      => $date = date('Y-m-d', time())
        );

        $testarray[] = array(
            'Callsign'  => 'TF/DL2NWK/P',
            'Country'   => 'Iceland',
            'Adif'      => 242,
            'Date'      => $date = date('Y-m-d', time())
        );

        $testarray[] = array(
            'Callsign'  => 'OZ1ALS/A',
            'Country'   => 'Denmark',
            'Adif'      => 221,
            'Date'      => $date = date('Y-m-d', time())
        );

        $testarray[] = array(
            'Callsign'  => 'LA1K',
            'Country'   => 'Norway',
            'Adif'      => 266,
            'Date'      => $date = date('Y-m-d', time())
        );

        $testarray[] = array(
            'Callsign'  => 'K1KW/M',
            'Country'   => 'United States Of America',
            'Adif'      => 291,
            'Date'      => $date = date('Y-m-d', time())
        );

        $testarray[] = array(
            'Callsign'  => 'TF/DL2NWK/M',
            'Country'   => 'Iceland',
            'Adif'      => 242,
            'Date'      => $date = date('Y-m-d', time())
        );

        $testarray[] = array(
            'Callsign'  => 'TF/DL2NWK/MM',
            'Country'   => 'None',
            'Adif'      => 0,
            'Date'      => $date = date('Y-m-d', time())
        );

        $testarray[] = array(
            'Callsign'  => 'TF/DL2NWK/P',
            'Country'   => 'Iceland',
            'Adif'      => 242,
            'Date'      => $date = date('Y-m-d', time())
        );

        $testarray[] = array(
            'Callsign'  => '2M0SQL/P',
            'Country'   => 'Scotland',
            'Adif'      => 279,
            'Date'      => $date = date('Y-m-d', time())
        );

        $testarray[] = array(
            'Callsign'  => 'FT8WW',
            'Country'   => 'Crozet Island',
            'Adif'      => 41,
            'Date'      => '2023-03-14'
        );

        $testarray[] = array(
            'Callsign'  => 'RV0AL/0/P',
            'Country'   => 'Asiatic Russia',
            'Adif'      => 15,
            'Date'      => $date = date('Y-m-d', time())
        );

        $testarray[] = array(
            'Callsign'  => 'OH/DJ1YFK',
            'Country'   => 'Finland',
            'Adif'      => 224,
            'Date'      => $date = date('Y-m-d', time())
        );

        $testarray[] = array(
            'Callsign'  => 'N6TR/7',
            'Country'   => 'United States Of America',
            'Adif'      => 291,
            'Date'      => $date = date('Y-m-d', time())
        );

        $testarray[] = array(
            'Callsign'  => 'KH0CW',
            'Country'   => 'United States Of America',
            'Adif'      => 291,
            'Date'      => $date = date('Y-m-d', time())
        );

        $testarray[] = array(
            'Callsign'  => 'R2FM/P',
            'Country'   => 'kaliningrad',
            'Adif'      => 126,
            'Date'      => $date = date('Y-m-d', time())
        );

        $testarray[] = array(
            'Callsign'  => 'R2FM',
            'Country'   => 'kaliningrad',
            'Adif'      => 126,
            'Date'      => $date = date('Y-m-d', time())
        );

        $testarray[] = array(
            'Callsign'  => 'FT5XO',
            'Country'   => 'Kerguelen Island',
            'Adif'      => 131,
            'Date'      => '2005-03-20'
        );

        $testarray[] = array(
            'Callsign'  => 'VP8CTR',
            'Country'   => 'Antarctica',
            'Adif'      => 13,
            'Date'      => '1997-02-07'
        );

        $testarray[] = array(
            'Callsign'  => 'FO0AAA',
            'Country'   => 'Clipperton',
            'Adif'      => 36,
            'Date'      => '2000-03-02'
        );

        $testarray[] = array(
            'Callsign'  => 'CX/PR8KW',
            'Country'   => 'Uruguay',
            'Adif'      => 144,
            'Date'      => $date = date('Y-m-d', time())
        );

        $testarray[] = array(
            'Callsign'  => 'IQ3MV/LH',
            'Country'   => 'Italy',
            'Adif'      => 248,
            'Date'      => $date = date('Y-m-d', time())
        );

        $testarray[] = array(
            'Callsign'  => 'LA1K/QRP',
            'Country'   => 'Norway',
            'Adif'      => 266,
            'Date'      => $date = date('Y-m-d', time())
        );

        $testarray[] = array(
            'Callsign'  => 'LA1K/LGT',
            'Country'   => 'Norway',
            'Adif'      => 266,
            'Date'      => $date = date('Y-m-d', time())
        );

        $testarray[] = array(
            'Callsign'  => 'SM1K/LH',
            'Country'   => 'Sweden',
            'Adif'      => 284,
            'Date'      => $date = date('Y-m-d', time())
        );

		$testarray[] = array(
            'Callsign'  => 'KG4W',
            'Country'   => 'United States Of America',
            'Adif'      => 291,
            'Date'      => $date = date('Y-m-d', time())
        );

		$testarray[] = array(
            'Callsign'  => 'KG4WW',
            'Country'   => 'Guantanamo Bay',
            'Adif'      => 105,
            'Date'      => $date = date('Y-m-d', time())
        );

		$testarray[] = array(
            'Callsign'  => 'KG4WWW',
            'Country'   => 'United States Of America',
            'Adif'      => 291,
            'Date'      => $date = date('Y-m-d', time())
        );

		$testarray[] = array(
            'Callsign'  => 'JA0JHQ/VK9X',
            'Country'   => 'Christmas Island',
            'Adif'      => 35,
            'Date'      => '2015-05-08'
        );

		$testarray[] = array(
            'Callsign'  => 'D5M',
            'Country'   => 'Liberia',
            'Adif'      => 434,
            'Date'      => '2025-12-14'
        );

		$testarray[] = array(
            'Callsign'  => 'AT44I',
            'Country'   => 'Antarctica',
            'Adif'      => 13,
            'Date'      => '2025-12-16'
        );

		$testarray[] = array(
            'Callsign'  => 'PT7BZ/PY0F',
            'Country'   => 'Fernando De Noronha',
            'Adif'      => 56,
            'Date'      => $date = date('Y-m-d', time())
        );

		$testarray[] = array(
            'Callsign'  => 'VP6A',
            'Country'   => 'Ducie Island',
            'Adif'      => 513,
            'Date'      => '2023-06-21'
        );

		$testarray[] = array(
            'Callsign'  => '9M1Z',
            'Country'   => 'East Malaysia',
            'Adif'      => 46,
            'Date'      => '2024-06-24'
        );

		$testarray[] = array(
            'Callsign'  => 'VK2/W7BRS',
            'Country'   => 'Lord Howe Island',
            'Adif'      => 147,
            'Date'      => '2024-07-18'
        );

		$testarray[] = array(
            'Callsign'  => 'G4SGX/6Y',
            'Country'   => 'Jamaica',
            'Adif'      => 82,
            'Date'      => $date = date('Y-m-d', time())
        );

		$testarray[] = array(
            'Callsign'  => 'DX0JP',
            'Country'   => 'Spratly Islands',
            'Adif'      => 247,
            'Date'      => '2007-02-08'
        );

		$testarray[] = array(
            'Callsign'  => 'AU7JCB',
            'Country'   => 'India',
            'Adif'      => 324,
            'Date'      => '2007-02-08'
        );

		$testarray[] = array(
            'Callsign'  => 'N2JBY/4X',
            'Country'   => 'Israel',
            'Adif'      => 336,
            'Date'      => $date = date('Y-m-d', time())
        );

		$testarray[] = array(
            'Callsign'  => 'KH5K',
            'Country'   => 'Invalid',
            'Adif'      => 0,
            'Date'      => '1993-03-13'
        );

		$testarray[] = array(
            'Callsign'  => 'HB/DK9TA',
            'Country'   => 'Switzerland',
            'Adif'      => 287,
            'Date'      => $date = date('Y-m-d', time())
        );

		$testarray[] = array(
            'Callsign'  => 'OE5DI/500',
            'Country'   => 'Austria',
            'Adif'      => 206,
            'Date'      => $date = date('Y-m-d', time())
        );

		$testarray[] = array(
            'Callsign'  => 'YI6SUL',
            'Country'   => 'Invalid',
            'Adif'      => 0,
            'Date'      => $date = date('Y-m-d', time())
        );

		$testarray[] = array(
            'Callsign'  => '3DA8/DF8LY/P',
            'Country'   => 'Kingdom Of Eswatini',
            'Adif'      => 468,
            'Date'      => $date = date('Y-m-d', time())
        );

		$testarray[] = array(
            'Callsign'  => '3X/DL5DAB',
            'Country'   => 'Invalid',
            'Adif'      => 0,
            'Date'      => $date = date('Y-m-d', time())
        );

		$testarray[] = array(
            'Callsign'  => '3X/DL5DA',
            'Country'   => 'Guinea',
            'Adif'      => 107,
            'Date'      => $date = date('Y-m-d', time())
        );

		$testarray[] = array(
            'Callsign'  => 'KN5H/6YA',
            'Country'   => 'Jamaica',
            'Adif'      => 82,
            'Date'      => $date = date('Y-m-d', time())
        );

		$testarray[] = array(
            'Callsign'  => 'DL2AAZ/6Y5',
            'Country'   => 'Jamaica',
            'Adif'      => 82,
            'Date'      => $date = date('Y-m-d', time())
        );

		$testarray[] = array(
            'Callsign'  => '6Y5WJ',
            'Country'   => 'Jamaica',
            'Adif'      => 82,
            'Date'      => $date = date('Y-m-d', time())
        );

		$testarray[] = array(
            'Callsign'  => 'R20RRC/0',
            'Country'   => 'Asiatic Russia',
            'Adif'      => 15,
            'Date'      => $date = date('Y-m-d', time())
        );

		$testarray[] = array(
            'Callsign'  => 'G4KJV/2K/P',
            'Country'   => 'England',
            'Adif'      => 223,
            'Date'      => $date = date('Y-m-d', time())
        );

		$testarray[] = array(
            'Callsign'  => 'VP8ADR/40',
            'Country'   => 'Falkland Islands',
            'Adif'      => 141,
            'Date'      => $date = date('Y-m-d', time())
        );

		$testarray[] = array(
			'Callsign'  => 'VP8ADR/400',
			'Country'   => 'Falkland Islands',
			'Adif'      => 141,
			'Date'      => $date = date('Y-m-d', time())
		);

		$testarray[] = array(
            'Callsign'  => 'LU7CC/E',
            'Country'   => 'Argentina',
            'Adif'      => 100,
            'Date'      => $date = date('Y-m-d', time())
        );

		$testarray[] = array(
            'Callsign'  => 'FR/F6KDF/T',
            'Country'   => 'Tromelin Island',
            'Adif'      => 276,
            'Date'      => '1999-08-04'
        );

		$testarray[] = array(
            'Callsign'  => 'A6050Y/5',
            'Country'   => 'United Arab Emirates',
            'Adif'      => 391,
            'Date'      => $date = date('Y-m-d', time())
        );

		 $testarray[] = array(
			'Callsign'  => '9H5G/C6A',
			'Country'   => 'Bahamas', 
			'Adif'      => 60, 
			'Date'      => $date = date('Y-m-d', time())
		);

		$testarray[] = array(
			'Callsign'  => 'A45XR/0',
			'Country'   => 'Oman', 
			'Adif'      => 370, 
			'Date'      => $date = date('Y-m-d', time())
		);

		$testarray[] = array(
			'Callsign'  => 'RAEM',
			'Country'   => 'Asiatic Russia', 
			'Adif'      => 54, 
			'Date'      => $date = date('Y-m-d', time())
		);

		$testarray[] = array(
			'Callsign'  => 'DJ1YFK/VE1',
			'Country'   => 'Canada', 
			'Adif'      => 1, 
			'Date'      => $date = date('Y-m-d', time())
		);

		$testarray[] = array(
			'Callsign'  => 'HD1QRC90',
			'Country'   => 'Ecuador', 
			'Adif'      => 120, 
			'Date'      => $date = date('Y-m-d', time())
		);

		$testarray[] = array(
			'Callsign'  => 'PJ6A',
			'Country'   => 'Saba & St. Eustatius', 
			'Adif'      => 519, 
			'Date'      => $date = date('Y-m-d', time())
		);

		$testarray[] = array(
			'Callsign'  => 'PJ4D',
			'Country'   => 'Bonaire', 
			'Adif'      => 520, 
			'Date'      => $date = date('Y-m-d', time())
		);

		$testarray[] = array(
			'Callsign'  => '4X50CZ/SK',
			'Country'   => 'Israel', 
			'Adif'      => 336, 
			'Date'      => $date = date('Y-m-d', time())
		);

		$testarray[] = array(
			'Callsign'  => 'RK3BY/0',
			'Country'   => 'Asiatic Russia', 
			'Adif'      => 15, 
			'Date'      => $date = date('Y-m-d', time())
		);

		$testarray[] = array(
			'Callsign'  => 'IU0KNS/ERA',
			'Country'   => 'Italy', 
			'Adif'      => 248, 
			'Date'      => $date = date('Y-m-d', time())
		);

		$testarray[] = array(
			'Callsign'  => 'IU8BPS/AWD',
			'Country'   => 'Italy', 
			'Adif'      => 248, 
			'Date'      => $date = date('Y-m-d', time())
		);

		$testarray[] = array(
			'Callsign'  => 'IK7XNF/GIRO',
			'Country'   => 'Italy', 
			'Adif'      => 248, 
			'Date'      => $date = date('Y-m-d', time())
		);

		$testarray[] = array(
			'Callsign'  => 'VJ5A',
			'Country'   => 'Australia', 
			'Adif'      => 150, 
			'Date'      => $date = date('Y-m-d', time())
		);

		$testarray[] = array(
			'Callsign'  => 'VL2IG',
			'Country'   => 'Australia', 
			'Adif'      => 150, 
			'Date'      => $date = date('Y-m-d', time())
		);

        set_time_limit(3600);

        // Starting clock time in seconds

        $start_time = microtime(true);

        $result = array();

        $i = 0;

		$dxccobj = new Dxcc(null);

        foreach ($testarray as $call) {
			$i++;

			$dxcc = $dxccobj->dxcc_lookup($call['Callsign'], $call['Date']);

			$dxcc['adif'] = (isset($dxcc['adif'])) ? $dxcc['adif'] : 0;
			$dxcc['entity'] = (isset($dxcc['entity'])) ? $dxcc['entity'] : 'None';

			$result[] = array(
							'Callsign'          => $call['Callsign'],
							'Date'              => $call['Date'],
							'Expected country'  => $call['Country'],
							'Expected adif'     => $call['Adif'],
							'Result country'    => ucwords(strtolower($dxcc['entity']), "- (/"),
							'Result adif'       => $dxcc['adif'],
							'Passed'            => ($call['Adif'] == $dxcc['adif']) ? 'Yes' : 'No',
						);
        }

        // End clock time in seconds
        $end_time = microtime(true);

        // Calculate script execution time
        $execution_time = ($end_time - $start_time);

		$data['result'] = $result;
		$data['execution_time'] = $execution_time;
		$data['calls_tested'] = $i;

		$data['page_title'] = __("Callsign Tester");
		$this->load->view('interface_assets/header', $data);
		$this->load->view('calltester/call');
		$this->load->view('interface_assets/footer');
    }

	public function dxcc_lookup($call, $date) {

		$date = date("Y-m-d", strtotime($date));
		$csadditions = '/^X$|^D$|^T$|^P$|^R$|^B$|^A$|^M$|^LH$|^L$|^J$|^SK$/';

		$dxcc_exceptions = $this->db->select('`entity`, `adif`, `cqz`,`cont`,`long`,`lat`')
			->where('`call`', $call)
			->where('(start <= ', $date)
			->or_where('start is null)', NULL, false)
			->where('(end >= ', $date)
			->or_where('end is null)', NULL, false)
			->get('dxcc_exceptions');
		if ($dxcc_exceptions->num_rows() > 0) {
			$row = $dxcc_exceptions->row_array();
			return $row;
		} else {

			if (preg_match('/(^KG4)[A-Z09]{3}/', $call)) {       // KG4/ and KG4 5 char calls are Guantanamo Bay. If 4 or 6 char, it is USA
				$call = "K";
			} elseif (preg_match('/(^OH\/)|(\/OH[1-9]?$)/', $call)) {   # non-Aland prefix!
				$call = "OH";                                             # make callsign OH = finland
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
					if ($prefix) {
						$prefix = substr($prefix, 0, -1); # Remove the / at the end
					}
					if ($suffix) {
						$suffix = substr($suffix, 1); # Remove the / at the beginning
					};
					if (preg_match($csadditions, $suffix)) {
						if ($prefix) {
							$call = $prefix;
						} else {
							$call = $callsign;
						}
					} else {
						$result = $this->wpx($call, 1);                       # use the wpx prefix instead
						if ($result == '') {
							$row['adif'] = 0;
							$row['cont'] = '';
							$row['entity'] = '- NONE -';
							$row['ituz'] = 0;
							$row['cqz'] = 0;
							$row['long'] = '0';
							$row['lat'] = '0';
							return $row;
						} else {
							$call = $result . "AA";
						}
					}
				}
			}

			$len = strlen($call);
			$dxcc_array = [];

			// Fetch all candidates in one shot instead of looping
			$dxcc_result = $this->db->query("SELECT `dxcc_prefixes`.`record`, `dxcc_prefixes`.`call`, `dxcc_prefixes`.`entity`, `dxcc_prefixes`.`adif`, `dxcc_prefixes`.`cqz`, `dxcc_entities`.`ituz`, `dxcc_prefixes`.`cont`, `dxcc_prefixes`.`long`, `dxcc_prefixes`.`lat`, `dxcc_prefixes`.`start`, `dxcc_prefixes`.`end`
			    FROM `dxcc_prefixes`
			    LEFT JOIN `dxcc_entities` ON `dxcc_entities`.`adif` = `dxcc_prefixes`.`adif`
			    WHERE ? like concat(`call`,'%')
			    and `dxcc_prefixes`.`call` like ?
			    AND (`dxcc_prefixes`.`start` <= ?  OR `dxcc_prefixes`.`start` is null)
			    AND (`dxcc_prefixes`.`end` >= ?  OR `dxcc_prefixes`.`end` is null) order by length(`call`) desc limit 1", array($call, substr($call, 0, 1) . '%', $date, $date));

			foreach ($dxcc_result->result_array() as $row) {
				$dxcc_array[$row['call']] = $row;
			}

			// query the table, removing a character from the right until a match
			for ($i = $len; $i > 0; $i--) {
				//printf("searching for %s\n", substr($call, 0, $i));
				if (array_key_exists(substr($call, 0, $i), $dxcc_array)) {
					$row = $dxcc_array[substr($call, 0, $i)];
					// $row = $dxcc_result->row_array();
					return $row;
				}
			}
		}

		return array(
			'adif' => 0,
			'cqz' => 0,
			'ituz' => 0,
			'long' => '',
			'lat' => '',
			'entity' => 'None',
		);
	}

	function wpx($testcall, $i) {
		$prefix = '';
		$a = '';
		$b = '';
		$c = '';

		$lidadditions = '/^QRP$|^LGT$/';
		$csadditions = '/^X$|^D$|^T$|^P$|^R$|^B$|^A$|^M$|^LH$|^L$|^J$|^SK$/';
		$noneadditions = '/^MM$|^AM$/';

		# First check if the call is in the proper format, A/B/C where A and C
		# are optional (prefix of guest country and P, MM, AM etc) and B is the
		# callsign. Only letters, figures and "/" is accepted, no further check if the
		# callsign "makes sense".
		# 23.Apr.06: Added another "/X" to the regex, for calls like RV0AL/0/P
		# as used by RDA-DXpeditions....

		if (preg_match_all('/^((\d|[A-Z])+\/)?((\d|[A-Z]){3,})(\/(\d|[A-Z])+)?(\/(\d|[A-Z])+)?$/', $testcall, $matches)) {

			# Now $1 holds A (incl /), $3 holds the callsign B and $5 has C
			# We save them to $a, $b and $c respectively to ensure they won't get
			# lost in further Regex evaluations.
			$a = $matches[1][0];
			$b = $matches[3][0];
			$c = $matches[5][0];

			if ($a) {
				$a = substr($a, 0, -1); # Remove the / at the end
			}
			if ($c) {
				$c = substr($c, 1); # Remove the / at the beginning
			};

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
				if (preg_match($lidadditions, $b)) {        # check if $b is a lid-addition
					$b = $a;
					$a = null;                              # $a goes to $b, delete lid-add
				} elseif ((preg_match('/\d[A-Z]+$/', $a)) && (preg_match('/\d$/', $b) || preg_match('/^[A-Z]\d[A-Z]$/', $b))) {   # check for call in $a
					$temp = $b;
					$b = $a;
					$a = $temp;
				}
			}

			# *** Added later ***  The check didn't make sure that the callsign
			# contains a letter. there are letter-only callsigns like RAEM, but not
			# figure-only calls.

			if (preg_match('/^[0-9]+$/', $b)) {            # Callsign only consists of numbers. Bad!
				return null;            # exit, undef
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
					preg_match('/(.+\d)[A-Z]*/', $b, $matches);     # Prefix is all but the last
					$prefix = $matches[1];                          # Letters
				} else {                                            # Case 1.2, no number
					$prefix = substr($b, 0, 2) . "0";               # first two + 0
				}
			} elseif (($a == null) && (isset($c))) {                # Case 2, CALL/X
				if (preg_match('/^(\d)/', $c)) {                    # Case 2.1, number
					preg_match('/(.+\d)[A-Z]*/', $b, $matches);     # regular Prefix in $1
					# Here we need to find out how many digits there are in the
					# prefix, because for example A45XR/0 is A40. If there are 2
					# numbers, the first is not deleted. If course in exotic cases
					# like N66A/7 -> N7 this brings the wrong result of N67, but I
					# think that's rather irrelevant cos such calls rarely appear
					# and if they do, it's very unlikely for them to have a number
					# attached.   You can still edit it by hand anyway..
					if (preg_match('/^([A-Z]\d{2,})$/', $matches[1])) {        # e.g. A45   $c = 0
						$prefix = $matches[1] . $c;  # ->   A40
					} else {                         # Otherwise cut all numbers
						preg_match('/(.*[A-Z])\d+/', $matches[1], $match); # Prefix w/o number in $1
						$prefix = $match[1] . $c; # Add attached number
					}
				} elseif (preg_match($csadditions, $c)) {
					preg_match('/(.+\d)[A-Z]*/', $b, $matches);     # Known attachment -> like Case 1.1
					$prefix = $matches[1];
				} elseif (preg_match($noneadditions, $c)) {
					return '';
				} elseif (preg_match('/^\d\d+$/', $c)) {            # more than 2 numbers -> ignore
					preg_match('/(.+\d)[A-Z]* /', $b, $matches);    # see above
					$prefix = $matches[1][0];
				} else {                                            # Must be a Prefix!
					if (preg_match('/\d$/', $c)) {                  # ends in number -> good prefix
						$prefix = $c;
					} else {                                        # Add Zero at the end
						$prefix = $c . "0";
					}
				}
			} elseif (($a) && (preg_match($noneadditions, $c))) {                # Case 2.1, X/CALL/X ie TF/DL2NWK/MM - DXCC none
				return '';
			} elseif ($a) {
				# $a contains the prefix we want
				if (preg_match('/\d$/', $a)) {                      # ends in number -> good prefix
					$prefix = $a;
				} else {                                            # add zero if no number
					$prefix = $a . "0";
				}
			}
			# In very rare cases (right now I can only think of KH5K and KH7K and FRxG/T
			# etc), the prefix is wrong, for example KH5K/DJ1YFK would be KH5K0. In this
			# case, the superfluous part will be cropped. Since this, however, changes the
			# DXCC of the prefix, this will NOT happen when invoked from with an
			# extra parameter $_[1]; this will happen when invoking it from &dxcc.

			if (preg_match('/(\w+\d)[A-Z]+\d/', $prefix, $matches) && $i == null) {
				$prefix = $matches[1][0];
			}
			return $prefix;
		} else {
			return '';
		}
	}

	/*
     * Check the dxcc_prefixes table and return (dxcc, country)
     */
	public function check_dxcc_table($call, $date) {

		$date = date("Y-m-d", strtotime($date));
		$csadditions = '/^X$|^D$|^T$|^P$|^R$|^B$|^A$|^M$|^LH$|^L$|^J$|^SK$/';

		$dxcc_exceptions = $this->db->select('`entity`, `adif`, `cqz`, `cont`')
			->where('`call`', $call)
			->where('(start <= ', $date)
			->or_where('start is null)', NULL, false)
			->where('(end >= ', $date)
			->or_where('end is null)', NULL, false)
			->get('dxcc_exceptions');

		if ($dxcc_exceptions->num_rows() > 0) {
			$row = $dxcc_exceptions->row_array();
			return array($row['adif'], $row['entity'], $row['cqz'], $row['cont']);
		}
		if (preg_match('/(^KG4)[A-Z09]{3}/', $call)) {      // KG4/ and KG4 5 char calls are Guantanamo Bay. If 4 or 6 char, it is USA
			$call = "K";
		} elseif (preg_match('/(^OH\/)|(\/OH[1-9]?$)/', $call)) {   # non-Aland prefix!
			$call = "OH";                                             # make callsign OH = finland
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
				if ($prefix) {
					$prefix = substr($prefix, 0, -1); # Remove the / at the end
				}
				if ($suffix) {
					$suffix = substr($suffix, 1); # Remove the / at the beginning
				};
				if (preg_match($csadditions, $suffix)) {
					if ($prefix) {
						$call = $prefix;
					} else {
						$call = $callsign;
					}
				} else {
					$result = $this->wpx($call, 1);                       # use the wpx prefix instead
					if ($result == '') {
						$row['adif'] = 0;
						$row['entity'] = '- NONE -';
						$row['cqz'] = 0;
						$row['cont'] = '';
						return array($row['adif'], $row['entity'], $row['cqz'], $row['cont']);
					} else {
						$call = $result . "AA";
					}
				}
			}
		}

		$len = strlen($call);
		$dxcc_array = [];
		// Fetch all candidates in one shot instead of looping
		$dxcc_result = $this->db->query("SELECT `call`, `entity`, `adif`, `cqz`, `cont`
		    FROM `dxcc_prefixes`
		    WHERE ? like concat(`call`,'%')
		    and `call` like ?
		    AND (`start` <= ?  OR start is null)
		    AND (`end` >= ?  OR end is null) order by length(`call`) desc limit 1", array($call, substr($call, 0, 1) . '%', $date, $date));

		foreach ($dxcc_result->result_array() as $row) {
			$dxcc_array[$row['call']] = $row;
		}

		// query the table, removing a character from the right until a match
		for ($i = $len; $i > 0; $i--) {
			//printf("searching for %s\n", substr($call, 0, $i));
			if (array_key_exists(substr($call, 0, $i), $dxcc_array)) {
				$row = $dxcc_array[substr($call, 0, $i)];
				// $row = $dxcc_result->row_array();
				return array($row['adif'], $row['entity'], $row['cqz'], $row['cont']);
			}
		}

		return array("Not Found", "Not Found");
	}
}
