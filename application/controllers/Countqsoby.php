<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Countqsoby extends CI_Controller {

    function __construct() {
        parent::__construct();

        if(!$this->user_model->authorize(2)) { $this->session->set_flashdata('error', __("You're not allowed to do that!")); redirect('dashboard'); }
    }

    public function index() {
        $data['page_title'] = __("Count QSOs by...");

        // Dropdown data comes from catalog tables only, so the first load
        // fires no queries against the big QSO log table.
        $this->load->model('bands');
        $data['user_bands'] = $this->bands->get_user_bands();

        $data['sats_available'] = $this->db->select('name, displayname')->order_by('name', 'ASC')->get('satellite')->result();
        $data['orbits'] = $this->db->select('orbit')->distinct()->where('orbit IS NOT NULL', null, false)->order_by('orbit', 'ASC')->get('satellite')->result();

        $this->load->model('modes');
        $data['modes'] = $this->modes->active();

        $data['user_default_band'] = $this->session->userdata('user_default_band');
        $data['user_default_confirmation'] = $this->session->userdata('user_default_confirmation');
        $data['adif_propmodes'] = $this->config->item('adif_propmodes');
        $data['user_map_custom'] = $this->optionslib->get_map_custom();

        $this->load->view('interface_assets/header', $data);
        $this->load->view('countqsoby/index');
        $this->load->view('interface_assets/footer');
    }

    public function get_counts() {
        $this->load->model('countqsoby_model');

        $data = $this->countqsoby_model->get_counts($this->input->post());

        header('Content-Type: application/json');
        echo json_encode($data);
    }

    public function details() {
        $this->load->model('countqsoby_model');

        $type = $this->input->post('type', true);
        $group = $this->input->post('group', true);
        $band = $this->input->post('band', true);
        $sat = $this->input->post('sat', true);
        $orbit = $this->input->post('orbit', true);
        $propagation = $this->input->post('propagation', true);
        $mode = $this->input->post('mode', true);
        $dateFrom = $this->input->post('dateFrom', true);
        $dateTo = $this->input->post('dateTo', true);

        $postdata = array(
            'confirmed' => $this->input->post('confirmed', true),
            'qsl' => $this->input->post('qsl', true),
            'lotw' => $this->input->post('lotw', true),
            'eqsl' => $this->input->post('eqsl', true),
            'qrz' => $this->input->post('qrz', true),
            'clublog' => $this->input->post('clublog', true),
        );

        $data['results'] = $this->countqsoby_model->qso_details($type, $group, $band, $sat, $propagation, $mode, $orbit, $dateFrom, $dateTo, $postdata);
        $data['adif_propmodes'] = $this->config->item('adif_propmodes');

        $data['page_title'] = "Log View - " . $group;

        $type_labels = array(
            'dxcc' => __("DXCC"),
            'grid' => __("Gridsquare"),
            'itu' => __("ITU Zone"),
            'cq' => __("CQ Zone"),
            'pota' => __("POTA Reference"),
            'sota' => __("SOTA Reference"),
            'iota' => __("IOTA Reference"),
            'wwff' => __("WWFF Reference"),
        );
        $type_label = $type_labels[$type] ?? htmlspecialchars((string) $type);

        $group_label = (string) $group;
        if ($type == 'dxcc') {
            $this->load->model('logbook_model');
            $entity = $this->logbook_model->get_entity($group);
            if (!empty($entity['name'])) {
                $group_label = ucwords(strtolower($entity['name']), '- (/');
            }
        }

        $data['filter'] = $type_label . " " . htmlspecialchars($group_label) . " " . __("and") . " ";
        $data['filter'] .= ($band == 'All' ? lcfirst(__("Every band (w/o SAT)")) : __("band") . " " . htmlspecialchars((string) $band));
        if ($band == 'SAT') {
            if ($sat != 'All' && $sat != null) {
                $data['filter'] .= __(" and satellite ") . htmlspecialchars((string) $sat);
            }
            if ($orbit != 'All' && $orbit != null) {
                $data['filter'] .= __(" and orbit type ") . htmlspecialchars((string) $orbit);
            }
        }
        if ($propagation != '' && $propagation != null && $propagation != 'All') {
            $data['filter'] .= __(" and propagation ") . htmlspecialchars((string) $propagation);
        }
        if ($mode != null && strtolower($mode) != 'all') {
            $data['filter'] .= __(" and mode ") . htmlspecialchars(strtoupper((string) $mode));
        }
        if (($postdata['confirmed'] ?? '') === 'true') {
            $qsltype = array();
            if (($postdata['qsl'] ?? '') === 'true') { $qsltype[] = "QSL"; }
            if (($postdata['lotw'] ?? '') === 'true') { $qsltype[] = "LoTW"; }
            if (($postdata['eqsl'] ?? '') === 'true') { $qsltype[] = "eQSL"; }
            if (($postdata['qrz'] ?? '') === 'true') { $qsltype[] = "QRZ.com"; }
            if (!empty($qsltype)) {
                $data['filter'] .= __(" and ") . implode('/', $qsltype);
            }
        }

        $data['ispopup'] = true;
        $this->load->view('awards/details', $data);
    }
}
