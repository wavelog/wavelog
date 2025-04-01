<?php

require_once './src/Label/vendor/autoload.php';
use Wavelog\Label\PDF_Label;
use Wavelog\Label\tfpdf;
use Wavelog\Label\font\unifont\ttfonts;

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Labels extends CI_Controller {
	/*
	|--------------------------------------------------------------------------
	| Controller: Labels
	|--------------------------------------------------------------------------
	|
	| This Controller handles all things Labels, creating, editing and printing
	|
	|
	 */

	function __construct() {
		parent::__construct();
		$this->load->helper(array('form', 'url', 'psr4_autoloader'));

		$this->load->model('user_model');
		if(!$this->user_model->authorize(2) || !clubaccess_check(9)) { $this->session->set_flashdata('error', __("You're not allowed to do that!")); redirect('dashboard'); }
	}


	/*
	|--------------------------------------------------------------------------
	| Function: index
	|--------------------------------------------------------------------------
	|
	| Nothing fancy just shows the main display of how many labels are waiting
	| to be printed per station profile.
	|
	 */
	public function index() {
		$data['page_title'] = __("QSL Card Labels");

		$this->load->model('labels_model');

		$data['labels'] = $this->labels_model->fetchLabels($this->session->userdata('user_id'));

		$data['papertypes'] = $this->labels_model->fetchPapertypes($this->session->userdata('user_id'));

		$data['qsos'] = $this->labels_model->fetchQsos($this->session->userdata('user_id'));

		$footerData = [];
		$footerData['scripts'] = [
			'assets/js/sections/labels.js',
		];

		$this->load->view('interface_assets/header', $data);
		$this->load->view('labels/index');
		$this->load->view('interface_assets/footer', $footerData);

	}

	/*
	|--------------------------------------------------------------------------
	| Function: create
	|--------------------------------------------------------------------------
	|
	| Shows the form used to create a label type.
	|
	 */
	public function create() {

		$data['page_title'] = __("Create Label Type");

		$this->load->library('form_validation');
		$this->load->model('labels_model');

		$data['papertypes'] = $this->labels_model->fetchPapertypes($this->session->userdata('user_id'));

		$this->form_validation->set_rules('label_name', __('Label Name'), 'required');
		$this->form_validation->set_rules('paper_type_id', __('Paper Type'), 'required');
		$this->form_validation->set_rules('measurementType', __('Measurement'), 'required');
		$this->form_validation->set_rules('marginTop', __('Top Margin'), 'required');
		$this->form_validation->set_rules('marginLeft', __('Left Margin'), 'required');
		$this->form_validation->set_rules('NX', __('QSLs Horizontally'), 'required');
		$this->form_validation->set_rules('NY', __('QSLs Vertically'), 'required');
		$this->form_validation->set_rules('SpaceX', __('Horizontal Space'), 'required');
		$this->form_validation->set_rules('SpaceY', __('Vertical Space'), 'required');
		$this->form_validation->set_rules('width', __('Label width'), 'required');
		$this->form_validation->set_rules('height', __('Label height'), 'required');
		$this->form_validation->set_rules('font_size', __('Size of Font'), 'required');
		$this->form_validation->set_rules('label_qsos', __('Number of QSOs on label'), 'required');

		if ($this->form_validation->run() == FALSE) {
			$this->load->view('interface_assets/header', $data);
			$this->load->view('labels/create');
			$this->load->view('interface_assets/footer');
		} else {
			$this->load->model('labels_model');
			$this->labels_model->addLabel();

			redirect('labels');
		}

	}

		/*
	|--------------------------------------------------------------------------
	| Function: createpaper
	|--------------------------------------------------------------------------
	|
	| Shows the form used to create a paper type.
	|
	 */
	public function createpaper() {

		$data['page_title'] = __("Create Paper Type");

		$this->load->library('form_validation');

		$this->form_validation->set_rules('paper_name', __('Paper Name'), 'required');
		$this->form_validation->set_rules('width', __('Paper Width'), 'required');
		$this->form_validation->set_rules('height', __('Paper Height'), 'required');

		if ($this->form_validation->run() == FALSE) {
			$this->load->view('interface_assets/header', $data);
			$this->load->view('labels/createpaper');
			$this->load->view('interface_assets/footer');
		} else {
			$this->load->model('labels_model');
			try {
				$this->labels_model->addPaper();
			} catch (\Throwable $th) {
				$this->session->set_flashdata('error', __('Your paper could not be saved. Remember that it can\'t have the same name as existing paper types.'));
				redirect('labels/createpaper');
			}
			redirect('labels');
		}

	}


	public function printids() {
		$ids = xss_clean(json_decode($this->input->post('id')));
		$offset = xss_clean($this->input->post('startat'));
		$grid = $this->input->post('grid') === "true" ? 1 : 0;
		$via = $this->input->post('via') === "true" ? 1 : 0;
		$qslmsg = $this->input->post('qslmsg') === "true" ? 1 : 0;
		$tnxmsg = $this->input->post('tnxmsg') === "true" ? 1 : 0;
		$reference = $this->input->post('reference') == "true" ? 1 : 0;
		$this->load->model('labels_model');
		$result = $this->labels_model->export_printrequestedids($ids);

		$this->prepareLabel($result, true, $offset, $grid, $via, $reference, $qslmsg, $tnxmsg);
	}

	public function print($station_id) {
		$clean_id = xss_clean($station_id);
		$offset = xss_clean($this->input->post('startat'));
		$grid = xss_clean($this->input->post('grid') ?? 0);
		$via = xss_clean($this->input->post('via') ?? 0);
		$qslmsg = xss_clean($this->input->post('qslmsg') ?? 0);
		$tnxmsg = xss_clean($this->input->post('tnxmsg') ?? 0);
		$reference = xss_clean($this->input->post('reference') ?? 0);
		$this->load->model('stations');
		if ($this->stations->check_station_is_accessible($station_id)) {
			$this->load->model('labels_model');
			$result = $this->labels_model->export_printrequested($clean_id);

			$this->prepareLabel($result, false, $offset, $grid, $via, $reference, $qslmsg, $tnxmsg);
		} else {
			redirect('labels');
		}
	}

	function prepareLabel($qsos, $jscall = false, $offset = 1, $grid = false, $via = false, $reference = false, $qslmsg = false, $tnxmsg = true) {
		$this->load->model('labels_model');
		$label = $this->labels_model->getDefaultLabel();


		try {
			if ($label) {
				$label->font='DejaVuSans'; // Fix font to DejaVuSans
				$ptype=$this->labels_model->getPaperType($label->paper_type_id);	// fetch papersize out of paper-table
				if (($ptype->paper_id ?? '') != '') {
					if ($ptype->metric == 'in')	{		// convert papersize to mm if given in inch
						$paper_width=$ptype->width*25.4;
						$paper_height=$ptype->height*25.4;
					} else {
						$paper_width=$ptype->width;
						$paper_height=$ptype->height;
					}
					$pdf = new PDF_Label(array(
						'paper-size'	=> 'custom', 				// $label->paper_type,	// The only Type left is "custom" because A4 and so on are also defined at paper_types
						'metric'		=> $label->metric,
						'marginLeft'	=> $label->marginleft,
						'marginTop'		=> $label->margintop,
						'NX'			=> $label->nx,
						'NY'			=> $label->ny,
						'SpaceX'		=> $label->spacex,
						'SpaceY'		=> $label->spacey,
						'width'			=> $label->width,
						'height'		=> $label->height,
						'font-size'		=> $label->font_size,
						'pgX'		=> $paper_width,
						'pgY'		=> $paper_height
					));
				} else {
					if ($jscall) {
						header('Content-Type: application/json');
						echo json_encode(array('message' => __('You need to assign a paperType to the label before printing')));
						return;
					} else {
						$this->session->set_flashdata('error', __('You need to assign a paperType to the label before printing'));
						redirect('labels');
					}
				}
			} else {
				if ($jscall) {
					header('Content-Type: application/json');
					echo json_encode(array('message' => __('You need to create a label and set it to be used for print.')));
					return;
				} else {
					$this->session->set_flashdata('error', __('You need to create a label and set it to be used for print.'));
					redirect('labels');
				}
			}
		} catch (\Throwable $th) {
			if ($jscall) {
				header('Content-Type: application/json');
				echo json_encode(array('message' => __('Something went wrong! The label could not be generated. Check label size and font size.')));
				return;
			} else {
				$this->session->set_flashdata('error', __('Something went wrong! The label could not be generated. Check label size and font size.'));
				redirect('labels');
			}
		}
		define('FPDF_FONTPATH', './src/Label/font/');

		$pdf->AddPage($ptype->orientation);

		if ($label->font == 'DejaVuSans') {	// leave this here, for future Use
			$pdf->AddFont($label->font,'','DejaVuSansMono.ttf',true);
			$pdf->SetFont($label->font,'');
		} else {
			$pdf->AddFont($label->font);
			$pdf->SetFont($label->font);
		}

		if ($qsos->num_rows() > 0) {
			if ($label->qsos == 1) {
				$this->makeMultiQsoLabel($qsos->result(), $pdf, 1, $offset, $ptype->orientation, $grid, $via, $reference, $qslmsg, $tnxmsg);
			} else {
				$this->makeMultiQsoLabel($qsos->result(), $pdf, $label->qsos, $offset, $ptype->orientation, $grid, $via, $reference, $qslmsg, $tnxmsg);
			}
		} else {
			$this->session->set_flashdata('message', __('0 QSOs found for print!'));
			redirect('labels');
		}
		$pdf->Output();
	}

	function makeMultiQsoLabel($qsos, $pdf, $numberofqsos, $offset, $orientation, $grid, $via, $reference, $qslmsg, $tnxmsg) {
		$text = '';
		$current_callsign = '';
		$current_sat = '';
		$current_sat_mode = '';
		$current_sat_bandrx = '';
		$qso_data = [];
		if ($offset !== 1) {
			for ($i = 1; $i < $offset; $i++) {
				$pdf->Add_Label('',$orientation);
			}
		}
		foreach($qsos as $qso) {
			if (($this->pretty_sat_mode($qso->COL_SAT_MODE) !== $current_sat_mode) || ($qso->COL_SAT_NAME  !== $current_sat) || ($qso->COL_CALL !== $current_callsign) || // Call, SAT or SAT-Mode differs?
			( ($qso->COL_BAND_RX !== $current_sat_bandrx) && ($this->pretty_sat_mode($qso->COL_SAT_MODE) !== '')) ) {
			   // ((($qso->COL_SAT_NAME ?? '' !== $current_sat) || ($qso->COL_CALL !== $current_callsign)) && ($qso->COL_SAT_NAME ?? '' !== '') && ($col->COL_BAND_RX ?? '' !== $current_sat_bandrx))) {
				if (!empty($qso_data)) {
					$this->finalizeData($pdf, $current_callsign, $qso_data, $numberofqsos, $orientation, $grid, $via, $reference, $qslmsg, $tnxmsg);
					$qso_data = [];
				}
				$current_callsign = $qso->COL_CALL;
				$current_sat = $qso->COL_SAT_NAME;
				$current_sat_mode = $this->pretty_sat_mode($qso->COL_SAT_MODE);
				$current_sat_bandrx = $qso->COL_BAND_RX ?? '';
			}

			$qso_data[] = [
				'time' => $qso->COL_TIME_ON,
				'band' => $qso->COL_BAND,
				'mode' => (($qso->COL_SUBMODE ?? '') == '') ? $qso->COL_MODE : $qso->COL_SUBMODE,
				'rst' => $qso->COL_RST_SENT,
				'mygrid' => $qso->station_gridsquare,
				'via' => $qso->COL_QSL_VIA,
				'sat' => $qso->COL_SAT_NAME,
				'sat_mode' => $this->pretty_sat_mode($qso->COL_SAT_MODE ?? ''),
				'sat_band_rx' => ($qso->COL_BAND_RX ?? ''),
				'qsl_recvd' => $qso->COL_QSL_RCVD,
				'mycall' => $qso->COL_STATION_CALLSIGN,
				'sig' => $qso->station_sig ?? '',
				'sig_info' => $qso->station_sig_info ?? '',
				'sota' => $qso->station_sota ?? '',
				'iota' => $qso->station_iota ?? '',
				'pota' => $qso->station_pota ?? '',
				'wwff' => $qso->station_wwff ?? '',
				'qslmsg' => $qso->COL_QSLMSG  ?? '',

			];
		}
		if (!empty($qso_data)) {
			$this->finalizeData($pdf, $current_callsign, $qso_data, $numberofqsos, $orientation, $grid, $via, $reference, $qslmsg, $tnxmsg);
		}
	}
	// New begin
	function pretty_sat_mode($sat_mode) {
		return(strlen($sat_mode ?? '') == 2 ? (strtoupper($sat_mode[0]).'/'.strtoupper($sat_mode[1])) : strtoupper($sat_mode ?? ''));
	}

	function finalizeData($pdf, $current_callsign, &$preliminaryData, $qso_per_label,$orientation, $grid, $via, $reference, $qslmsg, $tnxmsg) {

		$tableData = [];
		$count_qso = 0;
		$qso=[];
		foreach ($preliminaryData as $key => $row) {
			$qso=$row;
			$time = strtotime($qso['time']);
			$myFormatForView = date("d.m.y H:i", $time);
			$rowData = [
				'DD.MM.YY  UTC' => $myFormatForView,
				'Band' => $row['band'],
				'Mode' => $row['mode'],
				'RST' => $row['rst'],
			];
			$tableData[] = $rowData;
			$count_qso++;


			if($count_qso == $qso_per_label){
				$this->generateLabel($pdf, $current_callsign, $tableData,$count_qso,$qso,$orientation, $grid, $via, $reference, $qslmsg, $tnxmsg);
				$tableData = []; // reset the data
				$count_qso = 0;  // reset the counter
			}
			unset($preliminaryData[$key]);
		}
		// generate label for remaining QSOs
		if($count_qso > 0){
			$this->generateLabel($pdf, $current_callsign, $tableData,$count_qso,$qso,$orientation, $grid, $via, $reference, $qslmsg, $tnxmsg);
			$preliminaryData = []; // reset the data
		}
	}

	function generateLabel($pdf, $current_callsign, $tableData,$numofqsos,$qso,$orientation,$grid=true, $via=false, $reference = false, $qslmsg = false, $tnxmsg = true){
		$builder = new \AsciiTable\Builder();
		$builder->addRows($tableData);
			$toradio = "To Radio: ";
			$toradio .= $current_callsign;
			if (($via) && ($qso['via'] ?? '' != '')) {
				$toradio.=' via '.substr($qso['via'],0,8);
			}
			$builder->setTitle($toradio);

			$additionalText = "Confirming QSO".($numofqsos>1 ? 's' : '');
			$builder->setAdditionalText($additionalText);

			$text = $builder->renderTable();
		if($qso['sat'] != "") {
			if (($qso['sat_mode'] == '') && ($qso['sat_band_rx'] !== '')) {
				$text .= "\n".'Satellite: '.$qso['sat'].' Band RX: '.$qso['sat_band_rx'];
			} elseif (($qso['sat_mode'] == '') && ($qso['sat_band_rx'] == '')) {
				$text .= "\n".'Satellite: '.$qso['sat'];
			} else {
				$text .= "\n".'Satellite: '.$qso['sat'].' Mode: '.$qso['sat_mode'];
			}
		}
		$text.="\n";
		if ($grid) { $text .= "My call: ".$qso['mycall']." Grid: ".$qso['mygrid']."\n"; }
		if ($reference) {
			$ref_text = "";
			$ref_avail = false;
			if (!empty($qso['sig']) && !empty($qso['sig_info'])) { $ref_text .= $qso['sig'].":".$qso['sig_info']." "; $ref_avail = true;}
			if (!empty($qso['sota'])) { $ref_text .= "SOTA:".$qso['sota']." "; $ref_avail = true;}
			if (!empty($qso['iota'])) { $ref_text .= "IOTA:".$qso['iota']." "; $ref_avail = true;}
			if (!empty($qso['pota'])) { $ref_text .= "POTA:".$qso['pota']." "; $ref_avail = true;}
			if (!empty($qso['wwff'])) { $ref_text .= "WWFF:".$qso['wwff']; $ref_avail = true;}
			if ($ref_avail == true) {$text .= $ref_text."\n";}
		}
		if ($qslmsg) {
		    if (!empty($qso['qslmsg'])) { $text .= $qso['qslmsg']."\n";}
		}
		if ($tnxmsg) {
		    $text .= "Thanks for the QSO".($numofqsos>1 ? 's' : '');
		    $text .= " | ".($qso['qsl_recvd'] == 'Y' ? 'TNX' : 'PSE')." QSL";
		}

		$pdf->Add_Label($text,$orientation);    }


	// New End

	public function edit($id) {
		$this->load->model('labels_model');

		$cleanid = $this->security->xss_clean($id);

		$data['label'] = $this->labels_model->getLabel($cleanid,$this->session->userdata('user_id'));

		$data['papertypes'] = $this->labels_model->fetchPapertypes($this->session->userdata('user_id'));

		$data['page_title'] = __("Edit Label");

		$this->load->view('interface_assets/header', $data);
		$this->load->view('labels/edit');
		$this->load->view('interface_assets/footer');
	}

	public function updateLabel($id) {
		$this->load->model('labels_model');
		$this->load->library('form_validation');

		$this->form_validation->set_rules('label_name', __('Label Name'), 'required');
		$this->form_validation->set_rules('paper_type_id', __('Paper Type'), 'required');
		$this->form_validation->set_rules('measurementType', __('Measurement'), 'required');
		$this->form_validation->set_rules('marginTop', __('Top Margin'), 'required');
		$this->form_validation->set_rules('marginLeft', __('Left Margin'), 'required');
		$this->form_validation->set_rules('NX', __('QSLs Horizontally'), 'required');
		$this->form_validation->set_rules('NY', __('QSLs Vertically'), 'required');
		$this->form_validation->set_rules('SpaceX', __('Horizontal Space'), 'required');
		$this->form_validation->set_rules('SpaceY', __('Vertical Space'), 'required');
		$this->form_validation->set_rules('width', __('Label width'), 'required');
		$this->form_validation->set_rules('height', __('Label height'), 'required');
		$this->form_validation->set_rules('font_size', __('Size of Font'), 'required');
		$this->form_validation->set_rules('label_qsos', __('Number of QSOs on label'), 'required');

		if ($this->form_validation->run() == FALSE) {
			$this->edit($id);
		} else {
			$this->labels_model->updateLabel($id);
			$this->session->set_flashdata('message', __('Label was saved.'));
			redirect('labels');
		}
	}

	public function delete($id) {
		$this->load->model('labels_model');
		$this->labels_model->deleteLabel($id);
		$this->session->set_flashdata('warning', __('Label was deleted.'));
		redirect('labels');
	}

	public function saveDefaultLabel() {
		$id = $this->input->post('id');
		$this->load->model('labels_model');
		$this->labels_model->saveDefaultLabel($id);
	}

	public function startAtLabel() {
		$data['stationid'] = xss_clean($this->input->post('stationid'));
		$this->load->view('labels/startatform', $data);
	}

	public function editPaper($id) {
		$this->load->model('labels_model');

		$cleanid = $this->security->xss_clean($id);

		$data['paper'] = $this->labels_model->getPaper($cleanid);

		$data['page_title'] = __("Edit Paper");

		$this->load->view('interface_assets/header', $data);
		$this->load->view('labels/editpaper');
		$this->load->view('interface_assets/footer');
	}

	public function updatePaper($id) {
		$this->load->model('labels_model');
		$this->load->library('form_validation');

		$this->form_validation->set_rules('paper_name', __('Paper Name'), 'required');
		$this->form_validation->set_rules('width', __('Paper Width'), 'required');
		$this->form_validation->set_rules('height', __('Paper Height'), 'required');

		if ($this->form_validation->run() == FALSE) {
			$this->editPaper($id);
		} else {
			try {
				$this->labels_model->updatePaper($id);
			} catch (\Throwable $th) {
				$this->session->set_flashdata('error', __('Your paper could not be saved. Remember that it can\'t have the same name as existing paper types.'));
				$cleanid = $this->security->xss_clean($id);
				redirect('labels/editpaper/'.$cleanid);
			}
			$this->session->set_flashdata('message', __('Paper was saved.'));
			redirect('labels');
		}
	}

	function label_cnt_with_paper($paper_id) {
		$this->load->model('labels_model');
		return $this->labels_model->label_cnt_with_paper($paper_id);
	}

	public function deletePaper($id) {
		$this->load->model('labels_model');
		$this->labels_model->deletePaper($id);
		$this->session->set_flashdata('warning', __('Paper was deleted.'));
		redirect('labels');
	}
}
