<?php
class Update extends CI_Controller {

	/*
		Controls Updating Elements of Wavelog
		Functions:
			dxcc - imports the latest clublog cty.xml data
			lotw_users - imports lotw users
	*/

    function __construct()
	{
		parent::__construct();

		if (ENVIRONMENT == 'maintenance' && $this->session->userdata('user_id') == '') {
            echo __("Maintenance Mode is active. Try again later.")."\n";
			redirect('user/login');
		}
	}

	public function index()
	{
        $this->load->model('user_model');
		if(!$this->user_model->authorize(2)) { $this->session->set_flashdata('error', __("You're not allowed to do that!")); redirect('dashboard'); }

	    $data['page_title'] = __("Updates");
	    $this->load->view('interface_assets/header', $data);
	    $this->load->view('update/index');
	    $this->load->view('interface_assets/footer');

	}

	/*
	* Load the DXCC entities
	*/
	public function dxcc_entities($xml_data = null) {
		// Ensure the Paths library is loaded
		if (!$this->load->is_loaded('Paths')) {
			$this->load->library('Paths');
		}

		// Load XML data if not provided
		if ($xml_data === null) {
			$xml_data = simplexml_load_file($this->paths->make_update_path("cty.xml"));
		}

		$a_data = [];
		$batch_size = 100; // Batch size for database insertion
		$count = 0;

		foreach ($xml_data->entities->entity as $entity) {
			$a_data[] = [
				'adif' => isset($entity->adif) ? (int) $entity->adif : 0,
				'name' => isset($entity->cqz) ? (string) $entity->name : (string) $entity->entity,
				'prefix' => isset($entity->cqz) ? (string) $entity->prefix : (string) $entity->call,
				'ituz' => isset($entity->ituz) ? (float) $entity->ituz : 0,
				'cqz' => isset($entity->cqz) ? (int) $entity->cqz : 0,
				'cont' => isset($entity->cont) ? (string) $entity->cont : '',
				'long' => isset($entity->long) ? (float) $entity->long : 0,
				'lat' => isset($entity->lat) ? (float) $entity->lat : 0,
				'start' => isset($entity->start) ? date('Y-m-d H:i:s', strtotime($entity->start)) : null,
				'end' => isset($entity->end) ? date('Y-m-d H:i:s', strtotime($entity->end)) : null,
			];

			$count++;

			// Insert in batches for better performance
			if ($count % $batch_size === 0) {
				$this->db->insert_batch('dxcc_entities', $a_data);
				$a_data = []; // Clear batch data
				$this->update_status(__("Preparing DXCC-Entries: ") . $count);
			}
		}

		// Add the final special entity
		$a_data[] = [
			'adif' => 0,
			'name' => '- NONE - (e.g. /MM, /AM)',
			'prefix' => '',
			'ituz' => 0,
			'cqz' => 0,
			'cont' => '',
			'long' => 0,
			'lat' => 0,
			'start' => null,
			'end' => null,
		];

		// Insert remaining data
		if (!empty($a_data)) {
			$this->db->insert_batch('dxcc_entities', $a_data);
		}

		$this->update_status(); // Final status update
		return $count;
	}

	/*
     * Load the dxcc prefixes
     */
	public function dxcc_exceptions($xml_data = null) {
		// Ensure the Paths library is loaded
		if (!$this->load->is_loaded('Paths')) {
			$this->load->library('Paths');
		}

		// Load XML data if not provided
		if ($xml_data === null) {
			$xml_data = simplexml_load_file($this->paths->make_update_path("cty.xml"));
		}

		$a_data = [];
		$batch_size = 100; // Batch size for efficient database inserts
		$count = 0;

		foreach ($xml_data->exceptions->exception as $record) {
			$a_data[] = [
				'record' => (int) $record->attributes()->record,
				'call' => (string) $record->call,
				'entity' => (string) $record->entity,
				'adif' => (int) $record->adif,
				'cqz' => (int) $record->cqz,
				'cont' => (string) $record->cont,
				'long' => (float) $record->long,
				'lat' => (float) $record->lat,
				'start' => (!empty($record->start) && strtotime($record->start)) ? date('Y-m-d H:i:s', strtotime($record->start))  : null,
				'end' => $record->end ? date('Y-m-d H:i:s', strtotime($record->end)) : null,
			];

			$count++;

			// Insert in batches for better performance
			if ($count % $batch_size === 0) {
				$this->db->insert_batch('dxcc_exceptions', $a_data);
				$a_data = []; // Clear batch data
				$this->update_status(__("Preparing DXCC Exceptions: ") . $count);
			}
		}

		// Insert any remaining records
		if (!empty($a_data)) {
			$this->db->insert_batch('dxcc_exceptions', $a_data);
		}

		$this->update_status(); // Final status update
		return $count;
	}

	/*
     * Load the dxcc prefixes
     */
	public function dxcc_prefixes($xml_data = null) {
		// Load the cty file
		if (!$this->load->is_loaded('Paths')) {
			$this->load->library('Paths');
		}

		// Load XML data if not provided
		if ($xml_data === null) {
			$xml_data = simplexml_load_file($this->paths->make_update_path("cty.xml"));
		}

		$a_data = [];
		$batch_size = 100; // Insert in batches of 100 for efficiency
		$count = 0;

		foreach ($xml_data->prefixes->prefix as $record) {
			$a_data[] = [
				'record' => (int) $record->attributes()->record,
				'call' => (string) $record->call,
				'entity' => (string) $record->entity,
				'adif' => (int) $record->adif,
				'cqz' => (int) $record->cqz,
				'cont' => (string) $record->cont,
				'long' => (float) $record->long,
				'lat' => (float) $record->lat,
				'start' => (!empty($record->start) && strtotime($record->start)) ? date('Y-m-d H:i:s', strtotime($record->start))  : null,
				'end' => $record->end ? date('Y-m-d H:i:s', strtotime($record->end)) : null,
			];

			$count++;

			// Insert in batches to avoid memory overload
			if ($count % $batch_size === 0) {
				$this->db->insert_batch('dxcc_prefixes', $a_data);
				$a_data = []; // Clear the batch array
				$this->update_status(__("Preparing DXCC Prefixes: ") . $count);
			}
		}

		// Insert any remaining records
		if (!empty($a_data)) {
			$this->db->insert_batch('dxcc_prefixes', $a_data);
		}

		$this->update_status(); // Clear the status message
		return $count;
	}

	// Updates the DXCC & Exceptions from the Club Log Cty.xml file.
	public function dxcc() {

		if(!$this->load->is_loaded('Paths')) {
        	$this->load->library('Paths');
		}

        // set the last run in cron table for the correct cron id
        $this->load->model('cron_model');
        $this->cron_model->set_last_run($this->router->class.'_'.$this->router->method);

        $this->update_status("Downloading file");

        // give it 10 minutes...
        set_time_limit(600);

        // Load Migration data if any.
        $this->load->library('migration');
        $this->fix_migrations();
        $this->migration->latest();

        // Download latest file.
        $url = "https://cdn.clublog.org/cty.php?api=608df94896cb9c5421ae748235492b43815610c9";

        $gz = gzopen($url, 'r');
        if ($gz === FALSE) {
			$msg = "FAILED: Could not download data from clublog.org. Trying alternative URL.";
            $this->update_status($msg);
            log_message('error', $msg);

			$alt_url = "https://github.com/wavelog/dxcc_data/raw/refs/heads/master/cty.xml.gz";
			$gz = gzopen($alt_url, 'r');

			if ($gz === FALSE) {
				$msg = "FAILED: Could not download dxcc data. Please check your internet connection.";
				$this->update_status($msg);
				log_message('error', $msg);
				exit();
			} else {
				$msg = "Downloaded data successfully from alternative URL (github).";
				$this->update_status($msg);
				log_message('debug', $msg);
			}
        }

        $data = "";
        while (!gzeof($gz)) {
        $data .= gzgetc($gz);
        }
        gzclose($gz);

        if (file_put_contents($this->paths->make_update_path("cty.xml"), $data) === FALSE) {
            $this->update_status("FAILED: Could not write to cty.xml file");
			log_message('error', 'DXCC UPDATE FAILED: Could not write to cty.xml file');
            exit();
        }

        // Clear the tables, ready for new data
        $this->db->empty_table("dxcc_entities");
        $this->db->empty_table("dxcc_exceptions");
        $this->db->empty_table("dxcc_prefixes");
        $this->update_status();

        // Parse the three sections of the file and update the tables
        $this->db->trans_start();
		$xml_data = simplexml_load_file($this->paths->make_update_path("cty.xml"));
        $this->dxcc_exceptions($xml_data);
        $this->dxcc_entities($xml_data);
        $this->dxcc_prefixes($xml_data);
		$sql = "update dxcc_entities
		join dxcc_temp on dxcc_entities.adif = dxcc_temp.adif
		set dxcc_entities.ituz = dxcc_temp.ituz;";
		$this->db->query($sql);
        $this->db->trans_complete();

        $this->update_status(__("DONE"));

		echo 'success';
	}

	public function update_status($done=""){

        if(!$this->load->is_loaded('Paths')) {
        	$this->load->library('Paths');
		}

		if ($done != "Downloading file"){
			// Check that everything is done?
			if ($done == ""){
				$done = __("Updating...");
			}
			$html = $done."<br/>";
			$html .= __("Dxcc Entities:")." ".$this->db->count_all('dxcc_entities')."<br/>";
			$html .= __("Dxcc Exceptions:")." ".$this->db->count_all('dxcc_exceptions')."<br/>";
			$html .= __("Dxcc Prefixes:")." ".$this->db->count_all('dxcc_prefixes')."<br/>";
		} else {
			$html = $done."....<br/>";
		}

		file_put_contents($this->paths->make_update_path("status.html"), $html);
	}


	private function fix_migrations(){
        $res = $this->db->query("SELECT version FROM migrations");
        if ($res->num_rows() >0){
            $row = $res->row();
            $version = $row->version;

            if ($version < 7){
                $this->db->query("UPDATE migrations SET version=7");
            }
        }
	}

	public function check_missing_dxcc($all = false){
		$this->load->model('user_model');
		if (!$this->user_model->authorize(99)) {
			$this->session->set_flashdata('error', __("You're not allowed to do that!"));
			redirect('dashboard');
		}

		$this->load->model('logbook_model');
        $this->logbook_model->check_missing_dxcc_id($all);
	}

	public function check_missing_continent() {
		$this->load->model('user_model');
		if (!$this->user_model->authorize(99)) {
			$this->session->set_flashdata('error', __("You're not allowed to do that!"));
			redirect('dashboard');
		}

		$this->load->model('logbook_model');
		$this->logbook_model->check_missing_continent();
	}

	public function update_distances($all = false) {
		$this->load->model('user_model');
		if (!$this->user_model->authorize(99)) {
			$this->session->set_flashdata('error', __("You're not allowed to do that!"));
			redirect('dashboard');
		}

		$this->load->model('logbook_model');
		$this->logbook_model->update_distances($all);
	}

	public function check_missing_grid($all = false){
		$this->load->model('user_model');
		if (!$this->user_model->authorize(99)) {
			$this->session->set_flashdata('error', __("You're not allowed to do that!"));
			redirect('dashboard');
		}

	    $this->load->model('logbook_model');
        $this->logbook_model->check_missing_grid_id($all);
	}

    public function update_clublog_scp() {

        $this->load->model('Update_model');
        $result = $this->Update_model->clublog_scp();
        if($this->session->userdata('user_type') == '99') {
			if (substr($result, 0, 4) == 'DONE') {
				$this->session->set_flashdata('success', __("SCP Update complete. Result: ") . "'" . $result . "'");
			} else {
				$this->session->set_flashdata('error', __("SCP Update failed. Result: ") . "'" . $result . "'");
			}
			redirect('debug');
		} else {
        	echo $result;
		}
    }

    public function download_lotw_users() {
        $this->lotw_users();
    }

    public function lotw_users() {

        $this->load->model('Update_model');
        $result = $this->Update_model->lotw_users();
        if($this->session->userdata('user_type') == '99') {
			if (substr($result, 0, 7) == 'Records') {
				$this->session->set_flashdata('success', __("LoTW Users Update complete. Result: ") . "'" . $result . "'");
			} else {
				$this->session->set_flashdata('error', __("LoTW Users Update failed. Result: ") . "'" . $result . "'");
			}
			redirect('debug');
		} else {
        	echo $result;
		}
    }

    /*
     * Used for autoupdating the DOK file which is used in the QSO entry dialog for autocompletion.
     */
    public function update_dok() {

        $this->load->model('Update_model');
        $result = $this->Update_model->dok();
		if($this->session->userdata('user_type') == '99') {
			if (substr($result, 0, 4) == 'DONE') {
				$this->session->set_flashdata('success', __("DOK Update complete. Result: ") . "'" . $result . "'");
			} else {
				$this->session->set_flashdata('error', __("DOK Update failed. Result: ") . "'" . $result . "'");
			}
			redirect('debug');
		} else {
        	echo $result;
		}
    }

    /*
     * Used for autoupdating the SOTA file which is used in the QSO entry dialog for autocompletion.
     */
    public function update_sota() {

        $this->load->model('Update_model');
        $result = $this->Update_model->sota();
        if($this->session->userdata('user_type') == '99') {
			if (substr($result, 0, 4) == 'DONE') {
				$this->session->set_flashdata('success', __("SOTA Update complete. Result: ") . "'" . $result . "'");
			} else {
				$this->session->set_flashdata('error', __("SOTA Update failed. Result: ") . "'" . $result . "'");
			}
			redirect('debug');
		} else {
        	echo $result;
		}
    }

    /*
     * Pulls the WWFF directory for autocompletion in QSO dialogs
     */
    public function update_wwff() {

        $this->load->model('Update_model');
        $result = $this->Update_model->wwff();
        if($this->session->userdata('user_type') == '99') {
			if (substr($result, 0, 4) == 'DONE') {
				$this->session->set_flashdata('success', __("WWFF Update complete. Result: ") . "'" . $result . "'");
			} else {
				$this->session->set_flashdata('error', __("WWFF Update failed. Result: ") . "'" . $result . "'");
			}
			redirect('debug');
		} else {
        	echo $result;
		}
    }

    public function update_pota() {

        $this->load->model('Update_model');
        $result = $this->Update_model->pota();
        if($this->session->userdata('user_type') == '99') {
			if (substr($result, 0, 4) == 'DONE') {
				$this->session->set_flashdata('success', __("POTA Update complete. Result: ") . "'" . $result . "'");
			} else {
				$this->session->set_flashdata('error', __("POTA Update failed. Result: ") . "'" . $result . "'");
			}
			redirect('debug');
		} else {
        	echo $result;
		}
    }

    public function update_tle($returnpath = 'debug') {
        $this->load->model('Update_model');
        $result = $this->Update_model->tle();
        if($this->session->userdata('user_type') == '99') {
			if (substr($result, 0, 4) == 'This') {
				$this->session->set_flashdata('success', __("TLE Update complete. Result: ") . "'" . $result . "'");
			} else {
				$this->session->set_flashdata('error', __("TLE Update failed. Result: ") . "'" . $result . "'");
			}
			redirect($returnpath);
		} else {
        	echo $result;
		}
    }

    public function update_lotw_sats() {
       $this->load->model('Update_model');
       $bodyData['satupdates'] = $this->Update_model->lotw_sats();
       $data['page_title'] = __("LoTW SAT Update");
       $this->load->view('interface_assets/header', $data);
       $this->load->view('lotw/satupdate', $bodyData);
       $this->load->view('interface_assets/footer');
    }

	function version_check() {
		// set the last run in cron table for the correct cron id
		$this->load->model('cron_model');
		$this->cron_model->set_last_run($this->router->class . '_' . $this->router->method);

		$this->load->model('Update_model');
		$this->Update_model->update_check();
	}
}
?>
