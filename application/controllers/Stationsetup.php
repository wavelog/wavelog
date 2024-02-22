<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
	Handles Displaying of information for station tools.
*/

class Stationsetup extends CI_Controller {

	function __construct()
	{
		parent::__construct();
		$this->load->helper(array('form', 'url'));

		$this->load->model('user_model');
		if(!$this->user_model->authorize(2)) { $this->session->set_flashdata('notice', 'You\'re not allowed to do that!'); redirect('dashboard'); }
	}

	public function index() {
		$this->load->model('stations');
		$this->load->model('Logbook_model');
		$this->load->model('logbooks_model');

		$data['my_logbooks'] = $this->logbooks_model->show_all();

		$data['stations'] = $this->stations->all_with_count();
		$data['current_active'] = $this->stations->find_active();
		$data['is_there_qsos_with_no_station_id'] = $this->Logbook_model->check_for_station_id();

		$footerData = [];
		$footerData['scripts'] = [
			'assets/js/sections/stationsetup.js?' . filemtime(realpath(__DIR__ . "/../../assets/js/sections/stationsetup.js")),
		];

		// Render Page
		$data['page_title'] = "Station Setup";
		$this->load->view('interface_assets/header', $data);
		$this->load->view('stationsetup/stationsetup');
		$this->load->view('interface_assets/footer', $footerData);
	}

	public function setActiveLogbook_json() {
		$id2act=xss_clean($this->input->post('id2setActive',true));
		if ($id2act ?? '' != '') {
			$this->load->model('logbooks_model');
			$this->logbooks_model->set_logbook_active($id2act);
			$data['success']=1;
		} else {
			$data['success']=0;
			$data['flashdata']='Error';
		}
		echo json_encode($data);
	}

	public function deleteLogbook_json() {
		$id2del=xss_clean($this->input->post('id2delete',true));
		if ($id2del ?? '' != '') {
			$this->load->model('logbooks_model');
			$this->logbooks_model->delete($id2del);
			$data['success']=1;
		} else {
			$data['success']=0;
			$data['flashdata']='Error';
		}
		echo json_encode($data);
	}

	public function newLogbook_json() {
		$this->load->library('form_validation');

		$this->form_validation->set_rules('stationLogbook_Name', 'Station Logbook Name', 'required');

		if ($this->form_validation->run() == FALSE) {
			$data['flashdata']=validation_errors();
			$data['success']=0;
			echo json_encode($data);
		} else {
			$this->load->model('logbooks_model');
			$newId=$this->logbooks_model->add(xss_clean($this->input->post('stationLogbook_Name', true)));
			if ($newId > 0) {
				$data['success']=1;
			} else {
				$data['success']=0;
				$data['flashdata']='Error';
			}
			echo json_encode($data);
		}
	}

	public function newLogbook() {
		$data['page_title'] = "Create Station Logbook";
		$this->load->view('stationsetup/create', $data);
	}

	public function newLocation() {
		$this->load->model('stations');
		$this->load->model('dxcc');
		$data['dxcc_list'] = $this->dxcc->list();

		$this->load->model('logbook_model');
		$data['iota_list'] = $this->logbook_model->fetchIota();

		$data['page_title'] = lang('station_location_create_header');
		$this->load->view('station_profile/create', $data);
	}

	public function fetchLogbooks() {
		$this->load->model('logbooks_model');
		$hres=[];
		$result = $this->logbooks_model->show_all()->result();
		foreach ($result as $entry) {
			$single=(Object)[];
			$single->logbook_id=$entry->logbook_id;
			$single->logbook_name=$entry->logbook_name;
			$single->logbook_state=$this->lbstate2html($entry->logbook_id);
			$single->logbook_edit=$this->lbedit2html($entry->logbook_id,$entry->logbook_name);
			$single->logbook_delete=$this->lbdel2html($entry->logbook_id,$entry->logbook_name);
			$single->logbook_link=$this->lblnk2html($entry->public_slug,$entry->logbook_name);
			$single->logbook_publicsearch=($entry->public_search=='1') ? 'Enabled' : 'Disabled';
			array_push($hres,$single);
		}
		echo json_encode($hres);
	}

	private function lbstate2html($id) {
		if($this->session->userdata('active_station_logbook') != $id) {
			$htmret='<button id="'.$id.'" class="setActiveLogbook btn btn-outline-primary btn-sm">'.lang('station_logbooks_set_active').'</button>';
		} else {
			$htmret="<span class='badge badge-success'>" . lang('station_logbooks_active_logbook') . "</span>";
		}
		return $htmret;
	}

	private function lbdel2html($id, $logbook_name) {
                if($this->session->userdata('active_station_logbook') != $id) {
                	$htmret='<button id="'.$id.'" class="deleteLogbook btn btn-danger btn-sm" cnftext="'.lang('station_logbooks_confirm_delete').$logbook_name.'"><i class="fas fa-trash-alt"></i></button>';
		} else {
			$htmret='';
		}
		return $htmret;
	}

	private function lblnk2html($public_slug, $logbook_name) {
		if($public_slug != '') {
			$htmret='<a target="_blank" href="'.site_url('visitor')."/".$public_slug.'" class="btn btn-outline-primary btn-sm"><i class="fas fa-globe" title="'.lang('station_logbooks_view_public') . $logbook_name.'"></i></a>';
		} else {
			$htmret='';
		}
		return $htmret;
	}

	private function lbps2html($id, $logbook_name) {
		return '<a href="'.site_url('logbooks/edit')."/".$id.'" class="btn btn-outline-primary btn-sm"><i class="fas fa-edit" title="'.lang('station_logbooks_edit_logbook').': '.$logbook_name.'"></i></a>';
	}

	private function lbedit2html($id, $logbook_name) {
		return '<a href="'.site_url('logbooks/edit')."/".$id.'" class="btn btn-outline-primary btn-sm"><i class="fas fa-edit" title="'.lang('station_logbooks_edit_logbook').': '.$logbook_name.'"></i></a>';
	}

	public function fetchLocations() {
		$this->load->model('stations');
		$this->load->model('Logbook_model');

		$result = $this->stations->all_with_count()->result();
		$current_active = $this->stations->find_active();
		$data['is_there_qsos_with_no_station_id'] = $this->Logbook_model->check_for_station_id();

		$hres=[];
		foreach ($result as $entry) {
			// var_dump($entry);
			$single=(Object)[];
			// $single->logbook_id = $entry->logbook_id;
			$single->station_name = $entry->station_profile_name;
			$single->station_callsign = $entry->station_callsign;
			$single->station_country = $this->stationcountry2html($entry->station_country, $entry->dxcc_end);
			$single->station_gridsquare = $entry->station_gridsquare;
			$single->station_badge = $this->stationbadge2html($entry->station_id, $entry->station_active, $entry->station_profile_name, $entry->qso_total, $current_active, $entry->station_profile_name);
			$single->station_edit = $this->stationedit2html($entry->station_id);
			$single->station_emptylog = $this->stationemptylog2html($entry->station_id);
			$single->station_copylog = $this->stationcopy2html($entry->station_id);
			$single->station_delete = $this->stationdelete2html($entry->station_id, $entry->station_profile_name, $entry->station_active);
			array_push($hres,$single);
		}
		echo json_encode($hres);
	}

	private function stationbadge2html($id, $station_active, $qso_total, $current_active, $station_profile_name) {
		$returntext = '';
		if($station_active != 1) {
			$returntext .= '<a href="' . site_url('station/set_active/') . $current_active. '/'. $id .
			'" class="btn btn-outline-secondary btn-sm" onclick="return confirm(\''. lang('station_location_confirm_active') . $station_profile_name .'\')' . lang('station_location_set_active') . '</a>';
		} else {
			$returntext .= '<span class="badge badge-success">' . lang('station_location_active') . '</span>';
		}

		$returntext .= '<span class="badge badge-info">ID: ' .$id .'</span>';
		$returntext .='<span class="badge badge-light">' . $qso_total . lang('gen_hamradio_qso') . '</span>';
		return $returntext;
	}

	private function stationedit2html($id) {
		return '<a href="' . site_url('station/edit')."/" . $id . '" title=' . lang('admin_edit') . ' class="btn btn-outline-primary btn-sm"><i class="fas fa-edit"></i></a>';
	}

	private function stationemptylog2html($id) {
		return '<a href="' . site_url('station/deletelog') . "/" . $id . '" class="btn btn-danger btn-sm" title=' . lang('station_location_emptylog') . ' onclick="return confirm(\'' . lang('station_location_confirm_del_qso') . '\')"><i class="fas fa-trash-alt"></i></a></td>';
	}

	private function stationcopy2html($id) {
		return '<a href="' . site_url('station/copy') . "/" . $id . '" title=' . lang('admin_copy') . ' class="btn btn-outline-primary btn-sm"><i class="fas fa-copy"></i></a>';
	}

	private function stationdelete2html($id, $station_profile_name, $station_active) {
		if($station_active != 1) {
			return '<a href="' . site_url('station/delete'). "/" .$id . '" class="btn btn-danger btn-sm" title=' . lang('admin_delete') . ' onclick="return confirm(\'' . lang('station_location_confirm_del_stationlocation') . $station_profile_name . lang('station_location_confirm_del_stationlocation_qso') . '\');"><i class="fas fa-trash-alt"></i></a>';
		}

		return '<a href="'.site_url('station/delete').'/'.$id.'" class="btn btn-danger btn-sm" title=' . lang('admin_delete') . ' onclick="return confirm(' . lang('station_location_confirm_del_stationlocation') .
			$station_profile_name . lang('station_location_confirm_del_stationlocation_qso') .'"><i class="fas fa-trash-alt"></i></a>';
	}

	private function stationcountry2html($station_country, $dxcc_end) {
		$returntext = $station_country == '' ? '- NONE -' : $station_country;
		if ($dxcc_end != NULL) {
			$returntext .= ' <span class="badge badge-danger">'.lang('gen_hamradio_deleted_dxcc').'</span>';
		}

		return $returntext;
	}

}
