<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
	Controller for QSL Cards
*/

class Qsl extends CI_Controller {

    function __construct() {
        parent::__construct();
        $this->load->model('user_model');
        if(!$this->user_model->authorize(2)) { $this->session->set_flashdata('error', __("You're not allowed to do that!")); redirect('dashboard'); }
		if(($this->config->item('disable_qsl') ?? false)) { $this->session->set_flashdata('error', __("You're not allowed to do that!")); redirect('dashboard'); exit; }
    }

    // Default view when loading controller.
    public function index() {

        $this->load->model('qsl_model');
        $this->load->library('Genfunctions');
        $folder_name = $this->paths->getUserdataPath('qsl_card', 'p');
        $data['storage_used'] = $this->genfunctions->sizeFormat($this->genfunctions->folderSize($folder_name));

        // Render Page
        $data['page_title'] = __("QSL Cards");
        $data['qslarray'] = $this->qsl_model->getQsoWithQslList();

		$footerData = [];
		$footerData['scripts'] = [
			'assets/js/sections/qsl.js',
		];

        $this->load->view('interface_assets/header', $data);
        $this->load->view('qslcard/index');
        $this->load->view('interface_assets/footer', $footerData);
    }

    public function upload() {
        // Render Page
        $data['page_title'] = __("Upload QSL Cards");
        $this->load->view('interface_assets/header', $data);
        $this->load->view('qslcard/upload');
        $this->load->view('interface_assets/footer');
    }

    // Deletes QSL Card
    public function delete() {
        $this->load->model('user_model');
        if(!$this->user_model->authorize(2)) { $this->session->set_flashdata('error', __("You're not allowed to do that!")); redirect('dashboard'); }
        $id = $this->input->post('id');
        $this->load->model('Qsl_model');
        $this->Qsl_model->deleteQsl($id);
    }

    public function uploadqsl() {
        $this->load->model('user_model');
        if(!$this->user_model->authorize(2)) { $this->session->set_flashdata('error', __("You're not allowed to do that!")); redirect('dashboard'); }

        $qsoid = $this->input->post('qsoid');

        if (isset($_FILES['qslcardfront']) && $_FILES['qslcardfront']['name'] != "" && $_FILES['qslcardfront']['error'] == 0)
        {
            $result['front'] = $this->uploadQslCardFront($qsoid);
        } else {
            $result['front']['status'] = '';
        }

        if (isset($_FILES['qslcardback']) && $_FILES['qslcardback']['name'] != "" && $_FILES['qslcardback']['error'] == 0)
        {
            $result['back'] = $this->uploadQslCardBack($qsoid);
        } else {
            $result['back']['status'] = '';
        }

        header("Content-type: application/json");
        echo json_encode(['status' => $result]);
    }

    function uploadQslCardFront($qsoid) {
        $this->load->model('Qsl_model');
        $this->load->model('logbook_model');
        $this->load->library('upload_guard');

        if (!$this->logbook_model->check_qso_is_accessible($qsoid)) {
            return array('error' => __("You're not allowed to do that!"));
        }

        $config['upload_path']          = $this->paths->getUserdataPath('qsl_card', 'p');
        $config['allowed_types']        = 'jpg|gif|png|jpeg|JPG|PNG';
        $array = explode(".", $_FILES['qslcardfront']['name']);
        $ext = end($array);
        $config['file_name'] = $qsoid . '_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $ext;

        // Refuse the upload if it would leave the storage volume too full
        if (!$this->upload_guard->has_free_space($config['upload_path'], $_FILES['qslcardfront']['size'])) {
            return array('error' => __("Not enough free disk space to store the QSL card."));
        }

        $this->load->library('upload', $config);

        if ( ! $this->upload->do_upload('qslcardfront')) {
            // Upload of QSL card Failed
            $error = array('error' => $this->upload->display_errors());

            return $error;
        }
        else {
            //Upload of QSL card was successful
            $data = $this->upload->data();

            if (!$this->upload_guard->is_real_image($data['full_path'])) {
                unlink($data['full_path']);
                return array('error' => __("The uploaded file is not a valid image."));
            }

            // Now we need to insert info into database about file
            $filename = $data['file_name'];
            $insertid = $this->Qsl_model->saveQsl($qsoid, $filename);

            $result['status']  = 'Success';
            $result['insertid'] = $insertid;
            $result['filename'] = $filename;
            return $result;
        }
    }

    function uploadQslCardBack($qsoid) {
        $this->load->model('Qsl_model');
        $this->load->model('logbook_model');
        $this->load->library('upload_guard');

        if (!$this->logbook_model->check_qso_is_accessible($qsoid)) {
            return array('error' => __("You're not allowed to do that!"));
        }

        $config['upload_path']          = $this->paths->getUserdataPath('qsl_card', 'p');
        $config['allowed_types']        = 'jpg|gif|png|jpeg|JPG|PNG';
        $array = explode(".", $_FILES['qslcardback']['name']);
        $ext = end($array);
        $config['file_name'] = $qsoid . '_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $ext;

        if (!$this->upload_guard->has_free_space($config['upload_path'], $_FILES['qslcardback']['size'])) {
            return array('error' => __("Not enough free disk space to store the QSL card."));
        }

        $this->load->library('upload', $config);

        if ( ! $this->upload->do_upload('qslcardback')) {
            // Upload of QSL card Failed
            $error = array('error' => $this->upload->display_errors());

            return $error;
        }
        else {
            //Upload of QSL card was successful
            $data = $this->upload->data();

            if (!$this->upload_guard->is_real_image($data['full_path'])) {
                unlink($data['full_path']);
                return array('error' => __("The uploaded file is not a valid image."));
            }

            // Now we need to insert info into database about file
            $filename = $data['file_name'];
            $insertid = $this->Qsl_model->saveQsl($qsoid, $filename);

            $result['status']  = 'Success';
            $result['insertid'] = $insertid;
            $result['filename'] = $filename;
            return $result;
        }
    }

	function loadSearchForm() {
    	$data['filename'] = $this->input->post('filename');
		$this->load->view('qslcard/searchform', $data);
	}

	function searchQsos() {
		$this->load->model('Qsl_model');
		$callsign = $this->input->post('callsign');

		$data['results'] = $this->Qsl_model->searchQsos($callsign);
		$data['filename'] = $this->input->post('filename');
		$this->load->view('qslcard/searchresult', $data);
	}

	function addQsoToQsl() {
		$qsoid = $this->input->post('qsoid', TRUE);
		$filename = $this->input->post('filename', TRUE);

		$this->load->model('Qsl_model');
		$insertid = $this->Qsl_model->addQsotoQsl($qsoid, $filename);
		header("Content-type: application/json");
		$result['status']  = 'Success';
		$result['insertid'] = $insertid;
		$result['filename'] = $filename;
		echo json_encode($result);
	}

    function viewQsl() {
        $cleanid = $this->security->xss_clean($this->input->post('id'));
        $this->load->model('Qsl_model');
        $data['qslimages'] = $this->Qsl_model->getQslForQsoId($cleanid);
        $this->load->view('qslcard/qslcarousel', $data);
    }

}
