<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/* 

	Provides outputted cfd files for use with Google Map services 
	All maps are stored within /cfd in the root directory

*/

class Cfdexport extends CI_Controller {

	public function index() {
		$this->load->model('user_model');
		$this->load->model('modes');
		$this->load->model('logbook_model');
		$this->load->model('bands');

		if(!$this->user_model->authorize(2) || !clubaccess_check(9)) { $this->session->set_flashdata('error', __("You're not allowed to do that!")); redirect('dashboard'); }

		$data['page_title'] = __("CFD Export");

		$this->load->view('interface_assets/header', $data);
		$this->load->view('cfd/index');
		$this->load->view('interface_assets/footer');
	}

	public function export() {
		$this->load->model('user_model');
		if(!$this->user_model->authorize(2)) { $this->session->set_flashdata('error', __("You're not allowed to do that!")); redirect('dashboard'); }
		$this->load->model('logbook_model');
		$this->load->model('dxcc');

		// Parameters
		$fromdate = xss_clean($this->input->post('from'));
		$todate = xss_clean($this->input->post('to'));

		// Get QSOs with Valid QRAs
		$qsos = $this->logbook_model->cfd_get_all_qsos($fromdate, $todate);
		$output=strtoupper($this->session->userdata('user_callsign'))."\n";
		$output.='--------------------------------------------------------------
Wavelog '.$this->optionslib->get_option('version').' - Overview Confirmed Entities
       (M=Mixed C=CW F=Fone R=Rest)
==============================================================

Entity                \          MHz:   ALL   1.8   3.5     7    10    14    18    21    24    28    50
-------------------------------------------------------------------------------------------------------'."\n";

		$dxcc_list=[];	// Prepare Array
		foreach ($qsos->result() as $row) {	// Loop through entities which are cnfmd
			$nominal=$this->frequency->defaultFrequencies[$row->band]['NOMINAL'];
			$dxcc_list[$row->prefix]['name']=$row->name;
			if ($row->cnfmd >=1) { $dxcc_list[$row->prefix][$nominal][$row->mode]=$row->mode; }
		}
		$dxccs=$this->dxcc->list_current('prefix');
		foreach ($dxccs->result() as $dxcc) {	// Loop through ALL active entities
			if ($dxcc->adif == 0) {
				continue;
			}
			$vals=$dxcc_list[$dxcc->prefix] ?? [];	// Set current Entity
			$output .= str_pad($dxcc->prefix,6," ")." ".str_pad(substr($dxcc->name,0,30),30,".")."  ";
			$allm=0;
			$allc=0;
			$allf=0;
			$allr=0;
			$bandachievements='';
			foreach ($this->frequency->defaultFrequencies as $band => $attribs) {	// Loop through Bands
				if (($attribs['NOMINAL'] <= 50) && ($attribs['NOMINAL'] != 5)) {	// Check Every Band for cnfm state
					if ($vals[$attribs['NOMINAL']] ?? '' != '') {  $bandachievements .= 'M'; $allm++; } else { $bandachievements .= '-'; }
					if ($vals[$attribs['NOMINAL']]['C'] ?? '' == 'C') { $bandachievements .= 'C'; $allc++; } else { $bandachievements .= '-'; }
					if ($vals[$attribs['NOMINAL']]['F'] ?? '' == 'F') { $bandachievements .= 'F'; $allf++; } else { $bandachievements .= '-'; }
					if ($vals[$attribs['NOMINAL']]['R'] ?? '' == 'R') { $bandachievements .= 'R'; $allr++; } else { $bandachievements .= '-'; }
					$bandachievements .= '  ';
				}
			}
			// Prepeare ALL Column
			if ($allm >0) { $output.='M'; } else {  $output.='-'; }
			if ($allc >0) { $output.='C'; } else {  $output.='-'; }
			if ($allf >0) { $output.='F'; } else {  $output.='-'; }
			if ($allr >0) { $output.='R'; } else {  $output.='-'; }
			$output .= '  '.$bandachievements."\n";
		}

		header("Content-Disposition: attachment; filename=\"".strtoupper($this->session->userdata('user_callsign')).".CFD\"");
		echo $output;

	}
}
