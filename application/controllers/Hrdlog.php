<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
	Controller to interact with the hrdlog.net API
*/

class Hrdlog extends CI_Controller {

    /*
     * Upload QSO to hrdlog.net
     * When called from the url wavelog/hrdlog/upload, the function loops through all station_id's with a hrdlog code defined.
     * All QSOs not previously uploaded, will then be uploaded, one at a time
     */

	function __construct() {
		parent::__construct();

		if (ENVIRONMENT == 'maintenance' && $this->session->userdata('user_id') == '') {
			echo __("Maintenance Mode is active. Try again later.")."\n";
			redirect('user/login');
		}
	}

    public function upload() {

		$this->load->model('Hrdlog_model');
		$this->Hrdlog_model->upload();
        
    }

    /*
     * Used for displaying the uid for manually selecting log for upload to hrdlog
     */
    public function export() {
        $this->load->model('stations');

        $data['page_title'] = "HRDlog.net Logbook";

        $data['station_profiles'] = $this->stations->all_of_user();
        $data['station_profile'] = $this->stations->stations_with_hrdlog_code();

        $this->load->view('interface_assets/header', $data);
        $this->load->view('hrdlog/export');
        $this->load->view('interface_assets/footer');
    }

    /*
     * Used for ajax-function when selecting log for upload to hrdlog
     */
    public function upload_station() {
	    if (!($this->config->item('disable_manual_hrdlog'))) {
			$this->load->model('stations');
			$this->load->model('Hrdlog_model');
			
		    $this->Hrdlog_model->setOptions();

		    $postData = $this->input->post();

		    $this->load->model('logbook_model');
		    $result = $this->logbook_model->exists_hrdlog_credentials($postData['station_id']);
		    $hrdlog_username = $result->hrdlog_username;
		    $hrdlog_code = $result->hrdlog_code;
		    header('Content-type: application/json');
		    $result = $this->Hrdlog_model->mass_upload_qsos($postData['station_id'], $hrdlog_username, $hrdlog_code);
		    if ($result['status'] == 'OK') {
			    $stationinfo = $this->stations->stations_with_hrdlog_code();
			    $info = $stationinfo->result();

			    $data['status'] = 'OK';
			    $data['info'] = $info;
			    $data['infomessage'] =  sprintf(_ngettext("%d QSO is now uploaded to HRDlog", "%d QSOs are now uploaded to HRDlog", $result['count']), $result['count']);
			    $data['errormessages'] = $result['errormessages'];
			    echo json_encode($data);
		    } else {
			    $data['status'] = 'Error';
			    $data['info'] = __("No QSOs found to upload.");
			    $data['errormessages'] = $result['errormessages'];
			    echo json_encode($data);
		    }
	    } else {
		    redirect('dashboard');
	    }
    }

    public function mark_hrdlog() {
        // Set memory limit to unlimited to allow heavy usage
        ini_set('memory_limit', '-1');

        $station_id = $this->security->xss_clean($this->input->post('station_profile'));

        $this->load->model('adif_data');
        $this->load->model('logbook_model');

        $data['qsos'] = $this->adif_data->export_custom($this->input->post('from'), $this->input->post('to'), $station_id);

		if (isset($data['qsos'])) {
			foreach ($data['qsos']->result() as $qso)
			{
				$this->logbook_model->mark_hrdlog_qsos_sent($qso->COL_PRIMARY_KEY);
			}
		}

        $this->load->view('interface_assets/header', $data);
        $this->load->view('hrdlog/mark_hrdlog', $data);
        $this->load->view('interface_assets/footer');
    }
}
