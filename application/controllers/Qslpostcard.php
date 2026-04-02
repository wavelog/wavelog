<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Qslpostcard extends CI_Controller {

    public function upload_preview() {

        $config['upload_path']   = FCPATH . 'uploads/qsl_postcards/';
        $config['allowed_types'] = 'jpg|jpeg|png|webp';
        $config['max_size']      = 4096;
        $config['encrypt_name']  = true;

        if (!is_dir($config['upload_path'])) {
            mkdir($config['upload_path'], 0755, true);
        }

        $this->load->library('upload', $config);

        if (!$this->upload->do_upload('preview_image')) {
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'ok' => false,
                    'error' => strip_tags($this->upload->display_errors('', ''))
                ]));
            return;
        }

        $data = $this->upload->data();

        $url = base_url('uploads/qsl_postcards/' . $data['file_name']);

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'ok' => true,
                'url' => $url,
                'path' => 'uploads/qsl_postcards/' . $data['file_name']
            ]));
    }

    public function __construct() {
        parent::__construct();

        // Typical Wavelog pattern (adjust if your auth model differs)
        // $this->load->model('user_model');
        // $this->user_model->authorize(2);

        $this->load->model('Qslpostcard_model');
        $this->load->helper(array('url', 'security'));
    }

    public function index() {
        $data['page_title'] = 'QSL Postcard Designer';
        $data['templates']  = $this->Qslpostcard_model->list_templates();

        $this->load->view('interface_assets/header', $data);
        $this->load->view('qslpostcard/designer', $data);
        $this->load->view('interface_assets/footer');
    }

    // AJAX: GET template JSON
    public function get_template($id) {
        $tpl = $this->Qslpostcard_model->get_template((int)$id);
        if (!$tpl) show_404();

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'id' => (int)$tpl['id'],
                'name' => $tpl['name'],
                'preview_image' => $tpl['preview_image'],
                'layout' => json_decode($tpl['layout_json'], true),
            ]));
    }

    // AJAX: POST template JSON
    public function save_template() {
        $raw = $this->input->raw_input_stream;
        $payload = json_decode($raw, true);

        if (!is_array($payload) || empty($payload['name']) || empty($payload['layout'])) {
            return $this->_json_error('Invalid payload');
        }

        $id = isset($payload['id']) ? (int)$payload['id'] : 0;

        $preview_image = isset($payload['preview_image']) ? $payload['preview_image'] : null;

        $savedId = $this->Qslpostcard_model->save_template(
            $id,
            $payload['name'],
            json_encode($payload['layout'], JSON_UNESCAPED_SLASHES),
            $preview_image
        );
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(['ok' => true, 'id' => $savedId]));
    }

    // Generate a PDF for a set of QSOs in queue (v1: demo uses last N QSOs)
    public function pdf($template_id) {
        try {
            $tpl = $this->Qslpostcard_model->get_template((int)$template_id);
            if (!$tpl) {
                show_error('Template not found');
                return;
            }

            $layout = json_decode($tpl['layout_json'], true);
            if (!is_array($layout)) {
                show_error('Template JSON is invalid');
                return;
            }

            // v1 demo data
            $qsos = $this->Qslpostcard_model->get_sample_qsos(25);

            if (empty($qsos)) {
                show_error('No QSOs returned by get_sample_qsos()');
                return;
            }

            $pdfPath = $this->Qslpostcard_model->render_pdf_from_layout($layout, $qsos);

            if (!$pdfPath || !file_exists($pdfPath)) {
                show_error('PDF file was not created');
                return;
            }

            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="qsl_postcards_' . $template_id . '.pdf"');
            header('Content-Length: ' . filesize($pdfPath));
            readfile($pdfPath);
            @unlink($pdfPath);
            exit;
        } catch (Throwable $e) {
            log_message('error', 'QSLPOSTCARD pdf() failed: ' . $e->getMessage());
            log_message('error', $e->getTraceAsString());
            show_error('QSL Postcard PDF failed: ' . $e->getMessage());
        }
    }
    public function printqueue() {
        $data['page_title'] = 'Print QSL Postcards';
        $data['templates']  = $this->Qslpostcard_model->list_templates();

        // preserve incoming filter params from qsl queue
        $data['filters'] = $this->input->get(NULL, true);

        $this->load->view('interface_assets/header', $data);
        $this->load->view('qslpostcard/printqueue', $data);
        $this->load->view('interface_assets/footer');
    }

    public function pdfqueue($template_id) {
        try {
            $tpl = $this->Qslpostcard_model->get_template((int)$template_id);
            if (!$tpl) {
                show_error('Template not found');
                return;
            }

            $layout = json_decode($tpl['layout_json'], true);
            if (!is_array($layout)) {
                show_error('Template JSON is invalid');
                return;
            }

            $filters = $this->input->get(NULL, true);

            $qsos = $this->Qslpostcard_model->get_qsl_queue_qsos($filters);

            $dedupe = $this->input->get('dedupe_by_call', true);

            if ((string)$dedupe === '1') {
                $qsos = $this->Qslpostcard_model->dedupe_qsos_by_call($qsos);
            }
            if (empty($qsos)) {
                show_error('No QSOs found for postcard printing');
                return;
            }

            $pdfPath = $this->Qslpostcard_model->render_pdf_from_layout($layout, $qsos);

            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="qsl_postcards_queue_' . $template_id . '.pdf"');
            header('Content-Length: ' . filesize($pdfPath));
            readfile($pdfPath);
            @unlink($pdfPath);
            exit;
        } catch (Throwable $e) {
            log_message('error', 'QSLPOSTCARD pdfqueue() failed: ' . $e->getMessage());
            log_message('error', $e->getTraceAsString());
            show_error('QSL Postcard Queue PDF failed: ' . $e->getMessage());
        }
    }

    public function printqueue_selected() {
        $selected_ids = $this->input->post('selected_qsos');

        if (!is_array($selected_ids) || empty($selected_ids)) {
            show_error('No QSOs were selected');
            return;
        }

        $data['page_title'] = 'Print Selected QSL Postcards';
        $data['templates'] = $this->Qslpostcard_model->list_templates();
        $data['selected_ids'] = array_values(array_filter(array_map('intval', $selected_ids)));

        $this->load->view('interface_assets/header', $data);
        $this->load->view('qslpostcard/printqueue_selected', $data);
        $this->load->view('interface_assets/footer');
    }

    public function pdfselected($template_id) {
        try {
            $tpl = $this->Qslpostcard_model->get_template((int)$template_id);
            if (!$tpl) {
                show_error('Template not found');
                return;
            }

            $layout = json_decode($tpl['layout_json'], true);
            if (!is_array($layout)) {
                show_error('Template JSON is invalid');
                return;
            }

            $selected_ids = $this->input->post('selected_ids');
            if (!is_array($selected_ids) || empty($selected_ids)) {
                show_error('No selected QSO IDs were provided');
                return;
            }

            $qsos = $this->Qslpostcard_model->get_qsos_by_ids($selected_ids);

            $dedupe = $this->input->post('dedupe_by_call', true);
            if ((string)$dedupe === '1') {
                $qsos = $this->Qslpostcard_model->dedupe_qsos_by_call($qsos);
            }

            if (empty($qsos)) {
                show_error('No QSOs found for postcard printing');
                return;
            }

            $pdfPath = $this->Qslpostcard_model->render_pdf_from_layout($layout, $qsos);

            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="qsl_postcards_selected_' . $template_id . '.pdf"');
            header('Content-Length: ' . filesize($pdfPath));
            readfile($pdfPath);
            @unlink($pdfPath);
            exit;
        } catch (Throwable $e) {
            log_message('error', 'QSLPOSTCARD pdfselected() failed: ' . $e->getMessage());
            log_message('error', $e->getTraceAsString());
            show_error('Selected QSL Postcard PDF failed: ' . $e->getMessage());
        }
    }

    private function _json_error($msg, $code = 400) {
        $this->output
            ->set_status_header($code)
            ->set_content_type('application/json')
            ->set_output(json_encode(['ok' => false, 'error' => $msg]));
    }
}
