<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Backup extends CI_Controller {
	function __construct()
	{
		parent::__construct();
	}
	
	/* User Facing Links to Backup URLs */
	public function index()
	{
		$this->load->model('user_model');
		if(!$this->user_model->authorize(99)) { $this->session->set_flashdata('error', __("You're not allowed to do that!")); redirect('dashboard'); }

		$data['page_title'] = __("Backup");

		$this->load->view('interface_assets/header', $data);
		$this->load->view('backup/main');
		$this->load->view('interface_assets/footer');
	}

	/* Gets all QSOs and Dumps them to logbook.adi */
	public function adif($key = null){ 
		if ($key == null) {
			$this->load->model('user_model');
			if(!$this->user_model->authorize(99)) { $this->session->set_flashdata('error', __("You're not allowed to do that!")); redirect('dashboard'); }
		}

		$clean_key = $this->security->xss_clean($key);

		$this->load->helper('file');
		$this->load->library('AdifHelper');
		// Set memory limit to unlimited to allow heavy usage
		ini_set('memory_limit', '-1');
		
		$this->load->model('adif_data');
		$filename = 'backup/logbook'. date('_Y_m_d_H_i_s') .'.adi';

		header('Content-Type: text/plain; charset=utf-8');
		header('Content-Disposition: attachment; filename="'.$filename.'"');

		// Output ADIF header // No chance to use exportall-view any longer, because of chunking logic
		echo "Wavelog ADIF export\n";
		echo "<ADIF_VER:5>3.1.6\n";
		echo "<PROGRAMID:".strlen($this->config->item('app_name')).">".$this->config->item('app_name')."\r\n";
		echo "<PROGRAMVERSION:".strlen($this->optionslib->get_option('version')).">".$this->optionslib->get_option('version')."\r\n";
		echo "<EOH>\n\n";

		// Stream QSOs in 5K chunks
		$offset = 0;
		$chunk_size = 5000;

		do {
			$qsos = $this->adif_data->export_all_chunked($clean_key, null, null, false, null, $offset, $chunk_size);

			if ($qsos->num_rows() > 0) {
				foreach ($qsos->result() as $qso) {
					echo $this->adifhelper->getAdifLine($qso);
				}

				// Free memory
				$qsos->free_result();
			}

			$offset += $chunk_size;
		} while ($qsos->num_rows() > 0);

		exit;
	}

	/* Export the notes to XML */
	public function notes($key = null) {
		if ($key == null) {
			$this->load->model('user_model');
			if(!$this->user_model->authorize(99)) { $this->session->set_flashdata('error', __("You're not allowed to do that!")); redirect('dashboard'); }
		}

		$clean_key = $this->security->xss_clean($key);

		$this->load->helper('file');
		$this->load->model('note');

		$data['list_note'] = $this->note->list_all($clean_key);

		$data['filename'] = 'backup/notes'. date('_Y_m_d_H_i_s') .'.xml';

		if ( ! write_file($data['filename'], $this->load->view('backup/notes', $data, true)))
		{
			$data['status'] = false;
		}
		else
		{
			$data['status'] = true;
		}

		$data['page_title'] = __("Notes - Backup");

		$this->load->view('interface_assets/header', $data);
		$this->load->view('backup/notes_view');
		$this->load->view('interface_assets/footer');

	}
}

/* End of file Backup.php */
