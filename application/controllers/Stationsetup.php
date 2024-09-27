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
		if(!$this->user_model->authorize(2)) { $this->session->set_flashdata('error', __("You're not allowed to do that!")); redirect('dashboard'); }
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
		$data['page_title'] = __("Station Setup");
		$this->load->view('interface_assets/header', $data);
		$this->load->view('stationsetup/stationsetup');
		$this->load->view('interface_assets/footer', $footerData);
	}

	public function DeleteStation_json() {
		$id2del=xss_clean($this->input->post('id2del',true));
		if ($id2del ?? '' != '') {
			$this->load->model('stations');
			if ($this->stations->check_station_is_accessible($id2del)) {
				$this->stations->delete($id2del);
				$data['success']=1;
			} else {
				$data['success']=0;
				$data['flashdata'] = __("Not allowed");
			}
		} else {
			$data['success']=0;
			$data['flashdata']='Error';
		}
		echo json_encode($data);
	}

	public function EmptyStation_json() {
		$id2empty=xss_clean($this->input->post('id2Empty',true));
		if ($id2empty ?? '' != '') {
			$this->load->model('stations');
			if ($this->stations->check_station_is_accessible($id2empty)) {
				$this->stations->deletelog($id2empty);
				$data['success']=1;
			} else {
				$data['success']=0;
				$data['flashdata'] = __("Not allowed");
			}
		} else {
			$data['success']=0;
			$data['flashdata'] = __("Error");
		}
		echo json_encode($data);
	}

	public function setActiveStation_json() {
		$id2act=xss_clean($this->input->post('id2setActive',true));
		if ($id2act ?? '' != '') {
			$this->load->model('stations');
			$current=$this->stations->find_active();
			$this->stations->set_active($current, $id2act);
			$data['success']=1;
		} else {
			$data['success']=0;
			$data['flashdata'] = __("Error");
		}
		echo json_encode($data);
	}

	// get active station for quickswitcher
	public function getActiveStation() {
		$active_loc = $this->stations->find_active();
		echo json_encode($active_loc);
	}

	public function setFavorite_json() {
		$id2fav = xss_clean($this->input->post('id2Favorite', true));
		if ($id2fav ?? '' != '') {
			$this->load->model('stations');
			$this->stations->edit_favourite($id2fav);
			$data['success'] = 1;
		} else {
			$data['success'] = 0;
			$data['flashdata'] = __("Error");
		}
		echo json_encode($data);
	}

	public function setActiveLogbook_json() {
		$id2act=xss_clean($this->input->post('id2setActive',true));
		if ($id2act ?? '' != '') {
			$this->load->model('logbooks_model');
			$this->logbooks_model->set_logbook_active($id2act);
			$data['success']=1;
		} else {
			$data['success']=0;
			$data['flashdata'] = __("Error");
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
			$data['flashdata'] = __("Error");
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
				$data['flashdata'] = __("Error");
			}
			echo json_encode($data);
		}
	}

	public function newLogbook() {
		$data['page_title'] = __("Create Station Logbook");
		$this->load->view('stationsetup/create', $data);
	}

	public function editContainerName() {
		$this->load->model('stationsetup_model');
		$data['container'] = $this->stationsetup_model->getContainer(xss_clean($this->input->post('id', true)))->row();
		$data['page_title'] = __("Edit container name");
		$this->load->view('stationsetup/edit', $data);
	}

	public function saveContainerName() {
		$this->load->model('stationsetup_model');
		$this->stationsetup_model->saveContainer();
	}

	public function editLinkedLocations() {
		$this->load->model('logbooks_model');
		$data['station_locations_list'] = $this->stations->all_of_user();
		$station_logbook_details_query = $this->logbooks_model->logbook(xss_clean($this->input->post('id', true)));
		$data['station_logbook_details'] = $station_logbook_details_query->row();
		$data['station_locations_linked'] = $this->logbooks_model->list_logbooks_linked($this->input->post('id', true));
		$data['page_title'] = __("Edit linked locations");
		$this->load->view('stationsetup/linkedlocations', $data);
	}

	public function editVisitorLink() {
		$this->load->model('logbooks_model');
		$station_logbook_details_query = $this->logbooks_model->logbook(xss_clean($this->input->post('id', true)));
		$data['station_logbook_details'] = $station_logbook_details_query->row();
		$data['station_locations_list'] = $this->stations->all_of_user();
		$data['page_title'] = __("Edit visitor site");
		$this->load->view('stationsetup/visitor', $data);
	}

	public function saveVisitorLink() {
		$name = xss_clean($this->input->post('name', true));
		$id = xss_clean($this->input->post('id', true));

		$this->load->model('stationsetup_model');
		$result = $this->stationsetup_model->is_public_slug_available($name);
		if (!($result)) {
			$current_lb4slug=$this->stationsetup_model->public_slug_exists_logbook_id($name);
			if ($current_lb4slug == $id) {
				$result=true;	// Set to true if we want to update the SAME slug for the SAME Logbook
			}
		}

		if($result == true) {
			$this->stationsetup_model->saveVisitorLink($id, $name);
			$data['success'] = 1;
		} else {
			$data['success'] = 0;
			$data['flashdata'] = __("Error. Link is already in use!");
		}

		echo json_encode($data);
	}

	public function newLocation() {
		$this->load->model('stations');
		$this->load->model('dxcc');
		$data['dxcc_list'] = $this->dxcc->list();

		$this->load->model('logbook_model');
		$data['iota_list'] = $this->logbook_model->fetchIota();

		$data['page_title'] = __("Create Station Location");
		$this->load->view('station_profile/create', $data);
	}

	public function fetchLogbooks() {
		$this->load->model('logbooks_model');
		$hres=[];
		$result = $this->logbooks_model->show_all()->result();
		foreach ($result as $entry) {
			$single=(Object)[];
			$single->logbook_id = $entry->logbook_id;
			$single->logbook_name = $this->lbname2html($entry->logbook_id, $entry->logbook_name);
			$single->logbook_state = $this->lbstate2html($entry->logbook_id);
			$single->logbook_edit = $this->lbedit2html($entry->logbook_id);
			$single->logbook_delete = $this->lbdel2html($entry->logbook_id, $entry->logbook_name);
			$single->logbook_link = $this->lblnk2html($entry->public_slug, $entry->logbook_name, $entry->logbook_id);
			$single->logbook_publicsearch = $this->lbpublicsearch2html($entry->public_search, $entry->logbook_id);
			array_push($hres,$single);
		}
		echo json_encode($hres);
	}

	private function lbname2html($id, $name) {
		return $name . ' <i id="' . $id . '" class="editContainerName fas fa-edit" role="button"></i>';
	}

	private function lbpublicsearch2html($publicsearch, $id) {
		$htmret = ($publicsearch=='1' ? '<span class="badge text-bg-success">Enabled</span>' : '<span class="badge bg-dark">' . __("Disabled") . '</span>');
		$htmret .= '<div class="form-check" style="margin-top: -1.5em"><input id="'.$id.'" class="form-check-input publicSearchCheckbox" type="checkbox"'. ($publicsearch=='1' ? 'checked' : '') . '/></div>';
		return $htmret;

	}

	private function lbstate2html($id) {
		if($this->session->userdata('active_station_logbook') != $id) {
			$htmret='<button id="'.$id.'" class="setActiveLogbook btn btn-outline-primary btn-sm">'.__("Set as Active Logbook").'</button>';
		} else {
			$htmret="<span class='badge text-bg-success'>" . __("Active Logbook") . "</span>";
		}
		return $htmret;
	}

	private function lbdel2html($id, $logbook_name) {
		if($this->session->userdata('active_station_logbook') != $id) {
			$htmret='<button id="'.$id.'" class="deleteLogbook btn btn-outline-danger btn-sm" cnftext="'.__("Are you sure you want to delete the following station logbook? You must re-link any locations linked here to another logbook.: ").$logbook_name.'"><i class="fas fa-trash-alt"></i></button>';
		} else {
			$htmret='';
		}
		return $htmret;
	}

	private function lblnk2html($public_slug, $logbook_name, $id) {
		$htmret = '<button class="btn btn-outline-primary btn-sm editVisitorLink" id="' . $id . '"><i class="fas fa-edit"></i></button> ';
		if($public_slug != '') {
			$htmret .= '<a target="_blank" href="'.site_url('visitor')."/".$public_slug.'" class="btn btn-outline-primary btn-sm"><i class="fas fa-globe" title="'.__("View Public Page for Logbook: ") . $logbook_name.'"></i></a>';
			$htmret .= ' <button id="' . $id . '" class="deletePublicSlug btn btn-outline-danger btn-sm" cnftext="' . __("Are you sure you want to delete the public slug?") . '"><i class="fas fa-trash-alt"></i></button>';
			$htmret .= ' <button id="' . $id . '" class="editExportmapOptions btn btn-outline-primary btn-sm"><i class="fas fa-globe-europe"></i></button>';
		}
		return $htmret;
	}

	private function lbedit2html($id) {
		return '<button class="btn btn-outline-primary btn-sm editLinkedLocations" id="' . $id . '"><i class="fas fa-edit"></i></button>';
	}

	public function fetchLocations() {
		$this->load->model('stations');
		$this->load->model('Logbook_model');

		$result = $this->stations->all_with_count()->result();
		$current_active = $this->stations->find_active();
		$data['is_there_qsos_with_no_station_id'] = $this->Logbook_model->check_for_station_id();

		$quickswitch_enabled = ($this->user_options_model->get_options('header_menu', array('option_name'=>'locations_quickswitch'))->row()->option_value ?? 'false');

		$hres=[];
		foreach ($result as $entry) {
			$single=(Object)[];
			$single->station_id = $this->stationid2html($entry->station_id);
			$single->station_name = $entry->station_profile_name;
			$single->station_callsign = $entry->station_callsign;
			$single->station_country = $this->stationcountry2html($entry->station_country, $entry->dxcc_end);
			$single->station_gridsquare = $entry->station_gridsquare;
			$single->station_badge = $this->stationbadge2html($entry->station_active, $entry->qso_total, $current_active, $entry->station_profile_name,$entry->station_id);
			$single->station_edit = $this->stationedit2html($entry->station_id);
			$single->station_emptylog = $this->stationemptylog2html($entry->station_id);
			$single->station_copylog = $this->stationcopy2html($entry->station_id);
			$single->station_delete = $this->stationdelete2html($entry->station_id, $entry->station_profile_name, $entry->station_active);
			$single->station_favorite = $this->stationfavorite2html($entry->station_id, $quickswitch_enabled);
			$single->station_linked = $this->stationlinked2html($entry->linked);
			array_push($hres,$single);
		}
		echo json_encode($hres);
	}

	private function stationlinked2html($linked) {
		if ($linked == 1) {
			return '<i class="fa fa-check text-success" aria-hidden="true"></i>';
		}
		return '<i class="fa fa-times text-danger" aria-hidden="true"></i>';
	}

	private function stationfavorite2html($id, $quickswitch_enabled) {
		if ($quickswitch_enabled == 'false') {
			return '';
		}

		$locationFavorite = ($this->user_options_model->get_options('station_location', array('option_name'=>'is_favorite', 'option_key'=>$id))->row()->option_value ?? 'false');
		if ($locationFavorite == 'true') {
			$favStarClasses = 'class="setFavorite fas fa-star btn btn-sm" style="color: #ffc82b;"';
		} else {
			$favStarClasses = 'class="setFavorite far fa-star btn btn-sm" style="color: #a58118;"';
		}
		return '<button id ="' . $id .'" title="mark/unmark as favorite" ' . $favStarClasses . ' </a>';
	}

	private function stationid2html($station_id) {
		return '<span class="badge bg-info">'.$station_id.'</span>';
	}

	private function stationbadge2html($station_active, $qso_total, $current_active, $station_profile_name, $id) {
		$returntext = '';
		if($station_active != 1) {
			$returntext .= '<button id="'.$id.'" class="setActiveStation btn btn-outline-secondary btn-sm" cnftext="'. __("Are you sure you want to make the following station the active station: ") . $station_profile_name .'">' . __("Set Active") . '</button><br/>';
		} else {
			$returntext .= '<span class="badge bg-success text-bg-success">' . __("Active Station") . '</span><br/>';
		}

		$returntext .='<span class="badge bg-dark">' . $qso_total .' '. __("QSO") . '</span>';
		return $returntext;
	}

	private function stationedit2html($id) {
		return '<a href="' . site_url('station/edit')."/" . $id . '" title="' . __("Edit") . '" class="btn btn-outline-primary btn-sm"><i class="fas fa-edit"></i></a>';
	}

	private function stationemptylog2html($id) {
		return '<button id="'. $id . '" class="EmptyStation btn btn-danger btn-sm" title="' . __("Empty Log") . '" cnftext="' . __("Are you sure you want to delete all QSOs within this station profile?") . '"><i class="fas fa-trash-alt"></i></button>';
	}

	private function stationcopy2html($id) {
		return '<a href="' . site_url('station/copy') . "/" . $id . '" title="' . __("Copy") . '" class="btn btn-outline-primary btn-sm"><i class="fas fa-copy"></i></a>';
	}

	private function stationdelete2html($id, $station_profile_name, $station_active) {
		if($station_active != 1) {
			return '<button id="'.$id . '" class="DeleteStation btn btn-danger btn-sm" title="' . __("Delete") . '" cnftext="' . sprintf(__("Are you sure you want delete station profile '%s'? This will delete all QSOs within this station profile."), $station_profile_name) . '"><i class="fas fa-trash-alt"></i></button>';
		}

		return '';
	}

	private function stationcountry2html($station_country, $dxcc_end) {
		$returntext = $station_country == '' ? _pgettext("DXCC Select - No DXCC", "- NONE - (e.g. /MM, /AM)") : $station_country;
		if ($dxcc_end != NULL) {
			$returntext .= ' <span class="badge bg-danger">'.__("Deleted DXCC").'</span>';
		}

		return $returntext;
	}

	public function remove_publicslug() {
		$id = xss_clean($this->input->post('id',true));
		if ($id ?? '' != '') {
				$this->load->model('stationsetup_model');
				$this->stationsetup_model->remove_public_slug($id);
				$data['success']=1;
		} else {
			$data['success']=0;
			$data['flashdata'] = __("Not allowed");
		}
		echo json_encode($data);
	}

	public function togglePublicSearch() {
		$id = xss_clean($this->input->post('id',true));
		$publicSearch = xss_clean($this->input->post('checked',true));
		if ($id ?? '' != '') {
				$this->load->model('stationsetup_model');
				$this->stationsetup_model->togglePublicSearch($id, $publicSearch);
				$data['success']=1;
		} else {
			$data['success']=0;
			$data['flashdata'] = __("Not allowed");
		}
		echo json_encode($data);
	}

	public function unLinkLocations() {
		$containerid = xss_clean($this->input->post('containerid',true));
		$locationid = xss_clean($this->input->post('locationid',true));
		$this->load->model('stationsetup_model');
		$this->stationsetup_model->unLinkLocations($containerid, $locationid);
		$data['success']=1;
		echo json_encode($data);
	}

	public function linkLocations() {
		$containerid = xss_clean($this->input->post('containerid',true));
		$locationid = xss_clean($this->input->post('locationid',true));

		$this->load->model('stationsetup_model');

		if(!$this->stationsetup_model->relationship_exists($containerid, $locationid)) {
			// If no link exists, create
			$this->stationsetup_model->create_logbook_location_link($containerid, $locationid);
			$data['success']=1;
			$data['locationdata'] = $this->stationsetup_model->locationInfo($locationid)->result();
		} else {
			$data['success']=0;
			$data['flashdata'] = __("Error");
		}
		echo json_encode($data);
	}

	public function editExportmapOptions() {
		$this->load->model('stationsetup_model');

		$this->load->model('bands');

		$data['bands'] = $this->bands->get_user_bands();

		$container = $this->stationsetup_model->getContainer(xss_clean($this->input->post('id', true)))->row();
		$slug = $container->public_slug;
		$data['logbookid'] = xss_clean($this->input->post('id', true));
		$data['slug'] = $slug;

		$exportmapoptions['gridsquare_layer'] = $this->user_options_model->get_options('ExportMapOptions',array('option_name'=>'gridsquare_layer','option_key'=>$slug))->row();
		$exportmapoptions['path_lines'] = $this->user_options_model->get_options('ExportMapOptions',array('option_name'=>'path_lines','option_key'=>$slug))->row();
		$exportmapoptions['cqzone_layer'] = $this->user_options_model->get_options('ExportMapOptions',array('option_name'=>'cqzone_layer','option_key'=>$slug))->row();
		$exportmapoptions['qsocount'] = $this->user_options_model->get_options('ExportMapOptions',array('option_name'=>'qsocount','option_key'=>$slug))->row();
		$exportmapoptions['nightshadow_layer'] = $this->user_options_model->get_options('ExportMapOptions',array('option_name'=>'nightshadow_layer','option_key'=>$slug))->row();
		$exportmapoptions['band'] = $this->user_options_model->get_options('ExportMapOptions',array('option_name'=>'band','option_key'=>$slug))->row();

		$data['exportmapoptions'] = $exportmapoptions;

		$data['page_title'] = __("Edit Export Map options");
		$this->load->view('stationsetup/exportmapoptions', $data);
	}

	public function saveExportmapOptions() {
		$this->load->model('stationsetup_model');
		$container = $this->stationsetup_model->getContainer(xss_clean($this->input->post('id', true)))->row();
		$slug = $container->public_slug;

		$this->load->model('user_options_model');

		$this->user_options_model->set_option('ExportMapOptions', 'gridsquare_layer',  array($slug => xss_clean($this->input->post('gridsquare_layer'))));
		$this->user_options_model->set_option('ExportMapOptions', 'path_lines',  array($slug => xss_clean($this->input->post('path_lines'))));
		$this->user_options_model->set_option('ExportMapOptions', 'cqzone_layer',  array($slug => xss_clean($this->input->post('cqzone_layer'))));
		$this->user_options_model->set_option('ExportMapOptions', 'nightshadow_layer',  array($slug => xss_clean($this->input->post('nightshadow_layer'))));
		$this->user_options_model->set_option('ExportMapOptions', 'qsocount',  array($slug => xss_clean($this->input->post('qsocount'))));
		$this->user_options_model->set_option('ExportMapOptions', 'band',  array($slug => xss_clean($this->input->post('band'))));
	}
}
