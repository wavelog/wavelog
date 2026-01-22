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
            //$dxcc = $this->logbook_model->dxcc_lookup($call->col_call, $call->date);
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

	/* Uses Logbook_model and the normal dxcc lookup, which is slow */
	function doDxccCheckModel($de) {
		$this->load->model('logbook_model');
		$i = 0;
		$result = array();

		$callarray = $this->getQsos($de);

		// Starting clock time in seconds
		$start_time = microtime(true);

		foreach ($callarray->result() as $call) {
            $i++;
            $dxcc = $this->logbook_model->dxcc_lookup($call->col_call, $call->date);

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

		$this->load->model('logbook_model');

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

                $dxcc = $this->logbook_model->dxcc_lookup($data[0], $data[2]);

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

		$this->load->model('logbook_model');

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

                $dxcc = $this->logbook_model->check_dxcc_table($data[0], $data[2]);

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

        set_time_limit(3600);

        // Starting clock time in seconds
        $start_time = microtime(true);

		$this->load->model('logbook_model');

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
}
