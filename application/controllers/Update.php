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
     * Load the dxcc entities
     */
	public function dxcc_entities() {

        // Load the cty file
        if(!$this->load->is_loaded('Paths')) {
        	$this->load->library('Paths');
		}
		$xml_data = simplexml_load_file($this->paths->make_update_path("cty.xml"));

		//$xml_data->entities->entity->count();

		$count = 0;
		$a_data=[];
		foreach ($xml_data->entities->entity as $entity) {
			$startinfo = strtotime($entity->start);
			$endinfo = strtotime($entity->end);

			$start_date = ($startinfo) ? date('Y-m-d H:i:s',$startinfo) : null;
			$end_date = ($endinfo) ? date('Y-m-d H:i:s',$endinfo) : null;

			if(!$entity->cqz) {
				$data = array(
					'prefix' => (string) $entity->call,
					'name' =>  (string) $entity->entity,
				);
			} else {
				$data = array(
					'adif' => (int) $entity->adif,
					'name' =>  (string) $entity->name,
					'prefix' => (string)  $entity->prefix,
					'ituz' => (float) $entity->ituz,
					'cqz' => (int) $entity->cqz,
					'cont' => (string) $entity->cont,
					'long' => (float) $entity->long,
					'lat' => (float) $entity->lat,
					'start' => $start_date,
					'end' => $end_date,
				);
			}

			array_push($a_data,$data);
			$count += 1;
			if ($count % 10  == 0)
				$this->update_status(__("Preparing DXCC-Entries: ").$count);
		}
		$this->db->insert_batch('dxcc_entities', $a_data);

		$this->update_status();
		return $count;
	}

    /*
     * Load the dxcc exceptions
     */
	public function dxcc_exceptions() {

        // Load the cty file
        if(!$this->load->is_loaded('Paths')) {
        	$this->load->library('Paths');
		}
		$xml_data = simplexml_load_file($this->paths->make_update_path("cty.xml"));

		$count = 0;
		$a_data=[];
		foreach ($xml_data->exceptions->exception as $record) {
			$startinfo = strtotime($record->start);
			$endinfo = strtotime($record->end);

			$start_date = ($startinfo) ? date('Y-m-d H:i:s',$startinfo) : null;
			$end_date = ($endinfo) ? date('Y-m-d H:i:s',$endinfo) : null;

			$data = array(
				'record' => (int) $record->attributes()->record,
				'call' => (string) $record->call,
				'entity' =>  (string) $record->entity,
				'adif' => (int) $record->adif,
				'cqz' => (int) $record->cqz,
				'cont' => (string) $record->cont,
				'long' => (float) $record->long,
				'lat' => (float) $record->lat,
				'start' => $start_date,
				'end' => $end_date,
			);

			array_push($a_data,$data);
			$count += 1;
			if ($count % 10  == 0)
				$this->update_status(__("Preparing DXCC Exceptions: ").$count);
		}
		$this->db->insert_batch('dxcc_exceptions', $a_data);

		$this->update_status();
		return $count;
	}

    /*
     * Load the dxcc prefixes
     */
	public function dxcc_prefixes() {

		// Load the cty file
        if(!$this->load->is_loaded('Paths')) {
        	$this->load->library('Paths');
		}
		$xml_data = simplexml_load_file($this->paths->make_update_path("cty.xml"));

		$count = 0;
		$a_data=[];
		foreach ($xml_data->prefixes->prefix as $record) {
			$startinfo = strtotime($record->start);
			$endinfo = strtotime($record->end);

			$start_date = ($startinfo) ? date('Y-m-d H:i:s',$startinfo) : null;
			$end_date = ($endinfo) ? date('Y-m-d H:i:s',$endinfo) : null;

			$data = array(
				'record' => (int) $record->attributes()->record,
				'call' => (string) $record->call,
				'entity' =>  (string) $record->entity,
				'adif' => (int) $record->adif,
				'cqz' => (int) $record->cqz,
				'cont' => (string) $record->cont,
				'long' => (float) $record->long,
				'lat' => (float) $record->lat,
				'start' => $start_date,
				'end' => $end_date,
			);

			array_push($a_data,$data);
			$count += 1;
			if ($count % 10  == 0)
				$this->update_status(__("Preparing DXCC Prefixes: ").$count);
		}
		$this->db->insert_batch('dxcc_prefixes', $a_data);

		//print("$count prefixes processed");
		$this->update_status();
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
            $this->update_status("FAILED: Could not download from clublog.org");
            log_message('error', 'FAILED: Could not download exceptions from clublog.org');
            exit();
        }

        $data = "";
        while (!gzeof($gz)) {
        $data .= gzgetc($gz);
        }
        gzclose($gz);

        if (file_put_contents($this->paths->make_update_path("cty.xml"), $data) === FALSE) {
            $this->update_status("FAILED: Could not write to cty.xml file");
            exit();
        }

        // Clear the tables, ready for new data
        $this->db->empty_table("dxcc_entities");
        $this->db->empty_table("dxcc_exceptions");
        $this->db->empty_table("dxcc_prefixes");
        $this->update_status();

        // Parse the three sections of the file and update the tables
        $this->db->trans_start();
        $this->dxcc_entities();
        $this->dxcc_exceptions();
        $this->dxcc_prefixes();
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
        echo $result;

    }

    public function download_lotw_users() {
        $this->lotw_users();
    }

    public function lotw_users() {

        $this->load->model('Update_model');
        $result = $this->Update_model->lotw_users();
        echo $result;

    }

    /*
     * Used for autoupdating the DOK file which is used in the QSO entry dialog for autocompletion.
     */
    public function update_dok() {

        $this->load->model('Update_model');
        $result = $this->Update_model->dok();
        echo $result;

    }

    /*
     * Used for autoupdating the SOTA file which is used in the QSO entry dialog for autocompletion.
     */
    public function update_sota() {

        $this->load->model('Update_model');
        $result = $this->Update_model->sota();
        echo $result;

    }

    /*
     * Pulls the WWFF directory for autocompletion in QSO dialogs
     */
    public function update_wwff() {

        $this->load->model('Update_model');
        $result = $this->Update_model->wwff();
        echo $result;

    }

    public function update_pota() {

        $this->load->model('Update_model');
        $result = $this->Update_model->pota();
        echo $result;

    }

	public function update_tle() {
		$mtime = microtime();
        $mtime = explode(" ",$mtime);
        $mtime = $mtime[1] + $mtime[0];
        $starttime = $mtime;

		$url = 'https://www.amsat.org/tle/dailytle.txt';
		$curl = curl_init($url);

		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);

		$response = curl_exec($curl);

		$count = 0;

		if ($response === false) {
			echo 'Error: ' . curl_error($curl);
		} else {
			$this->db->empty_table("tle");
			// Split the response into an array of lines
			$lines = explode("\n", $response);

			$satname = '';
			$tleline1 = '';
			$tleline2 = '';
			// Process each line
			for ($i = 0; $i < count($lines); $i += 3) {
				$count++;
				// Check if there are at least three lines remaining
				if (isset($lines[$i], $lines[$i + 1], $lines[$i + 2])) {
					// Get the three lines
					$satname = $lines[$i];
					$tleline1 = $lines[$i + 1];
					$tleline2 = $lines[$i + 2];
					$sql = "INSERT INTO tle (satelliteid, tle) select id, ? from satellite where name = ? or exportname = ?";
					$this->db->query($sql,array($tleline1."\n".$tleline2,$satname,$satname));
				}
			}
		}

		curl_close($curl);

        $mtime = microtime();
        $mtime = explode(" ",$mtime);
        $mtime = $mtime[1] + $mtime[0];
        $endtime = $mtime;
        $totaltime = ($endtime - $starttime);
        echo "This page was created in ".$totaltime." seconds <br />";
        echo "Records inserted: " . $count . " <br/>";
        $datetime = new DateTime("now", new DateTimeZone('UTC'));
        $datetime = $datetime->format('Ymd h:i');
        $this->optionslib->update('tle_update', $datetime , 'no');
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
