<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*

	Data lookup functions used within Wavelog

*/

class Lookup extends CI_Controller {


	function __construct()
	{
		parent::__construct();

		$this->load->model('user_model');
		if(!$this->user_model->authorize(2)) { $this->session->set_flashdata('error', __("You're not allowed to do that!")); redirect('dashboard'); }
	}

	public function index()
	{
		$data['page_title'] = __("Quick Lookup");
		$this->load->model('logbook_model');
		$data['dxcc'] = $this->logbook_model->fetchDxcc();
		$data['iota'] = $this->logbook_model->fetchIota();
		$this->load->view('lookup/index', $data);
	}

	public function search() {
		$this->load->model('logbooks_model');
		$logbooks_locations_array = $this->logbooks_model->list_logbook_relationships($this->session->userdata('active_station_logbook'));

		$location_list = "'".implode("','",$logbooks_locations_array)."'";

		$this->load->model('lookup_model');

		$data['type'] = xss_clean($this->input->post('type'));

		if ($data['type'] == "lotw") {
			$this->load->model('logbook_model');
			$data['callsign'] = xss_clean($this->input->post('lotw'));
			$data['lotw_lastupload'] = $this->logbook_model->check_last_lotw($data['callsign']);

			$this->load->view('lookup/lotwuser', $data);
		} else {
			$this->load->model('bands');

			$data['bands'] = $this->bands->get_worked_bands(xss_clean($this->input->post('type')));


			$data['dxcc'] = xss_clean($this->input->post('dxcc'));
			$data['was']  = xss_clean($this->input->post('was'));
			$data['sota'] = xss_clean($this->input->post('sota'));
			$data['grid'] = xss_clean($this->input->post('grid'));
			$data['iota'] = xss_clean($this->input->post('iota'));
			$data['cqz']  = xss_clean($this->input->post('cqz'));
			$data['wwff'] = xss_clean($this->input->post('wwff'));
			$data['location_list'] = $location_list;

			$data['result'] = $this->lookup_model->getSearchResult($data);
			$this->load->view('lookup/result', $data);
		}

	}

	public function scp() {
		session_write_close();
		$uppercase_callsign = strtoupper($this->input->post('callsign', TRUE) ?? '');

		// SCP results from logbook
		$this->load->model('logbook_model');

		$arCalls = array();

		$query = $this->logbook_model->get_callsigns($uppercase_callsign);

		foreach ($query->result() as $row)
	    {
	    	if (in_array($row->COL_CALL, $arCalls) == false)
			{
					$arCalls[] = str_replace('0', 'Ø', $row->COL_CALL);
			}
	    }

		// SCP results from Club Log master scp db
		$file = 'updates/clublog_scp.txt';

		if (is_readable($file)) {
			$lines = file($file, FILE_IGNORE_NEW_LINES);
			$input = preg_quote($uppercase_callsign, '~');
			$result = preg_grep('~' . $input . '~', $lines, 0);
			foreach ($result as &$value) {
				if (in_array($value, $arCalls) == false)
				{
					$arCalls[] = str_replace('0', 'Ø', $value);
				}
			}
		} else {
			$src = 'assets/resources/clublog_scp.txt';
			if (copy($src, $file)) {
				$this->scp();
			} else {
				log_message('error', 'Failed to copy source file ('.$src.') to new location. Check if this path has the right permission: '.$file);
			}
		}

		// SCP results from master scp https://www.supercheckpartial.com
		$file = 'updates/MASTER.SCP';

		if (is_readable($file)) {
			$lines = file($file, FILE_IGNORE_NEW_LINES);
			$input = preg_quote($uppercase_callsign, '~');
			$result = preg_grep('~' . $input . '~', $lines, 0);
			foreach ($result as &$value) {
				if (in_array($value, $arCalls) == false)
				{
					$arCalls[] = str_replace('0', 'Ø', $value);
				}
			}
		} else {
			$src = 'assets/resources/MASTER.SCP';
			if (copy($src, $file)) {
				$this->scp();
			} else {
				log_message('error', 'Failed to copy source file ('.$src.') to new location. Check if this path has the right permission: '.$file);
			}
		}

		sort($arCalls);

		foreach ($arCalls as $strCall)
		{
			echo " " . $strCall . " ";
		}

	}

	public function dok($call) {
		session_write_close();

		if($call) {
			$uppercase_callsign = strtoupper($call);
		}

		// DOK results from logbook
		$this->load->model('logbook_model');

		$query = $this->logbook_model->get_dok($uppercase_callsign);

		if ($query->row()) {
			echo $query->row()->COL_DARC_DOK;
		}
	}

	public function get_state_list() {
		$this->load->library('subdivisions');

		$dxcc = xss_clean($this->input->post('dxcc'));
		$states_result = $this->subdivisions->get_state_list($dxcc);
		$subdivision_name = $this->subdivisions->get_primary_subdivision_name($dxcc);

		if ($states_result->num_rows() > 0) {
			$states_array = $states_result->result_array();
				$result = array(
				'status' => 'ok',
				'subdivision_name' => $subdivision_name,
				'data' => $states_array
			);
			header('Content-Type: application/json');
			echo json_encode($result);
		} else {
			header('Content-Type: application/json');
			echo json_encode(array('status' => 'No States for this DXCC in Database'));
		}
	}


    public function get_county() {
        $json = [];

        if(!empty($this->security->xss_clean($this->input->get("query")))) {
            $county = $this->security->xss_clean($this->input->get("state"));
            $cleanedcounty = explode('(', $county);
            $cleanedcounty = trim($cleanedcounty[0]);

            $file = 'assets/json/US_counties.csv';

            if (is_readable($file)) {
                $lines = file($file, FILE_IGNORE_NEW_LINES);
                $input = preg_quote($cleanedcounty, '~');
                $reg = '~^'. $input .'(.*)$~';
                $result = preg_grep($reg, $lines);
                $json = [];
                $i = 0;
                foreach ($result as &$value) {
                    $county = explode(',', $value);
                    // Limit to 100 as to not slowdown browser too much
                    if (count($json) <= 100) {
                        $json[] = ["name"=>$county[1]];
                    }
                }
            }
        }

        header('Content-Type: application/json');
        echo json_encode($json);
    }

}
