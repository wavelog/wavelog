<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

use Wavelog\Dxcc\Dxcc;

require_once APPPATH . '../src/Dxcc/Dxcc.php';

class Dxcluster extends CI_Controller {

	function __construct()
	{
		parent::__construct();
		$this->load->is_loaded('user_model') ?: $this->load->model('user_model');
		if(!$this->user_model->authorize(2)) { $this->session->set_flashdata('error', __("You're not allowed to do that!")); redirect('dashboard'); }
		$this->load->is_loaded('dxcluster_model') ?: $this->load->model('dxcluster_model');
	}


	public function spots($band, $age = '', $de = '', $mode = 'All') {
		// Sanitize inputs
		$band = $this->security->xss_clean($band);
		$mode = $this->security->xss_clean($mode);


		// Only load cache driver if caching is enabled
		if (($this->config->item('enable_dxcluster_file_cache_band') ?? false) || ($this->config->item('enable_dxcluster_file_cache_worked') ?? false)) {
			$this->load->driver('cache', [
				'adapter' => $this->config->item('cache_adapter') ?? 'file', 
				'backup' => $this->config->item('cache_backup') ?? 'file',
				'key_prefix' => $this->config->item('cache_key_prefix') ?? ''
			]);
		}

		if ($age == '') {
			$age = $this->optionslib->get_option('dxcluster_maxage') ?? 60;
		} else {
			$age = (int)$age;
		}

		if ($de == '') {
			$de = $this->optionslib->get_option('dxcluster_decont') ?? 'EU';
		} else {
			$de = $this->security->xss_clean($de);
		}
		$calls_found = $this->dxcluster_model->dxc_spotlist($band, $age, $de, $mode);

		header('Content-Type: application/json');
		http_response_code(200);
		if ($calls_found && !empty($calls_found)) {
			echo json_encode($calls_found, JSON_PRETTY_PRINT);
		} else {
			echo json_encode([], JSON_PRETTY_PRINT);  // "error: not found" would be misleading here. No spots are not an error. Therefore we return an empty array
		}
	}

	public function qrg_lookup($qrg) {
		$call_found = $this->dxcluster_model->dxc_qrg_lookup($this->security->xss_clean($qrg));
		header('Content-Type: application/json');
		http_response_code(200);
		if ($call_found) {
			echo json_encode($call_found, JSON_PRETTY_PRINT);
		} else {
			echo json_encode([], JSON_PRETTY_PRINT); // "error: not found" would be misleading here. No call is not an error, the call is just not in the spotlist. Therefore we return an empty array
		}
	}

	// TODO: Is this used anywhere? If not, remove it!
	public function call($call) {
		$date = date('Y-m-d', time());
		$dxccobj = new Dxcc();

		$dxcc = $dxccobj->dxcc_lookup($call, $date);

		header('Content-Type: application/json');
		http_response_code(200);
		if ($dxcc) {
			echo json_encode($dxcc, JSON_PRETTY_PRINT);
		} else {
			echo json_encode(['error' => 'not found'], JSON_PRETTY_PRINT);
		}
	}
}
