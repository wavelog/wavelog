<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Labeldesigner extends CI_Controller {

    public function __construct() {
        parent::__construct();
		$this->load->helper(array('form', 'url', 'psr4_autoloader'));

        // Same guard as the Labels controller: label templates reference
        // user-scoped label_types, so club access (9) applies here too.
		if (!$this->user_model->authorize(2) || !clubaccess_check(9)) {
			$this->session->set_flashdata('error', __("You're not allowed to do that!"));
			redirect('dashboard');
		}

        $this->load->model('Labeldesigner_model');
    }

    public function index() {
        $data['page_title'] = __("Label Designer");
        $data['templates']  = $this->Labeldesigner_model->list_templates();

        $this->load->model('labels_model');
        $data['labels'] = $this->labels_model->fetchLabels($this->session->userdata('user_id'));

		$footerData = [];
		$footerData['scripts'] = [
			'assets/js/sections/labels_designer.js',
		];

        $this->load->view('interface_assets/header', $data);
        $this->load->view('labeldesigner/designer');
        $this->load->view('interface_assets/footer', $footerData);
    }

    // AJAX: GET template JSON
    public function get_template($id) {
        $tpl = $this->Labeldesigner_model->get_template((int)$id);
        if (!$tpl) show_404();

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'id'            => (int)$tpl['id'],
                'name'          => $tpl['name'],
                'label_type_id' => (int)$tpl['label_type_id'],
                'layout'        => json_decode($tpl['layout_json'], true),
            ]));
    }

    // AJAX: POST template JSON
    public function save_template() {
        $raw = $this->input->raw_input_stream;

        // Cap the raw body; a template layout is small JSON, anything larger is abuse
        if (strlen($raw) > 256 * 1024) {
            return $this->_json_error('Payload too large', 413);
        }

        $payload = json_decode($raw, true);

        if (!is_array($payload) || empty($payload['name']) || empty($payload['layout']) || empty($payload['label_type_id'])) {
            return $this->_json_error('Invalid payload');
        }

        // Reject absurdly large layouts (element count) before we store them
        if (isset($payload['layout']['elements']) && count($payload['layout']['elements']) > 200) {
            return $this->_json_error('Too many layout elements');
        }

        $id = isset($payload['id']) ? (int)$payload['id'] : 0;

        // Trim + cap to the name column width (VARCHAR(100)) to avoid overflow/bloat
        $name = mb_substr(trim((string)$payload['name']), 0, 100);
        if ($name === '') {
            return $this->_json_error('Invalid payload');
        }

        // The template's geometry comes from a label type; make sure it exists
        // and belongs to this user.
        $label_type_id = (int)$payload['label_type_id'];
        $ownsLabelType = $this->db->query(
            "SELECT 1 FROM label_types WHERE id = ? AND user_id = ?",
            [$label_type_id, $this->session->userdata('user_id')]
        )->row_array();
        if (!$ownsLabelType) {
            return $this->_json_error('Invalid label type');
        }

        $savedId = $this->Labeldesigner_model->save_template(
            $id,
            $name,
            $label_type_id,
            json_encode($payload['layout'], JSON_UNESCAPED_SLASHES)
        );

        if ($savedId === false) {
            return $this->_json_error('Template not found', 404);
        }

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(['ok' => true, 'id' => $savedId]));
    }

    // Generate a sample PDF: renders the layout with the most recent QSOs —
    // roughly one sheet's worth — so the design can be checked on paper.
    public function pdf($template_id) {
        try {
            $tpl = $this->Labeldesigner_model->get_template((int)$template_id);
            if (!$tpl) {
                show_error(__("Template not found"));
                return;
            }

            $layout = json_decode($tpl['layout_json'], true);
            if (!is_array($layout)) {
                show_error(__("Template JSON is invalid"));
                return;
            }

            if (empty($layout['elements'])) {
                show_error(__("Template has no elements"));
                return;
            }

            $label = $this->Labeldesigner_model->get_label_with_paper($tpl['label_type_id']);
            if (!$label) {
                show_error(__("Label type not found"));
                return;
            }

            if (($label->paper_id ?? '') == '') {
                show_error(__('You need to assign a paperType to the label before printing'));
                return;
            }

            // Enough sample QSOs to fill about one sheet for this layout
            $opts    = $layout['options'] ?? [];
            $perLabel = max(1, (int)($opts['qsos_per_label'] ?? 1));
            $sampleN = min(60, max(10, $perLabel * (int)$label->nx * (int)$label->ny));

            $this->load->model('Qslpostcard_model');
            $qsos = $this->Qslpostcard_model->get_sample_qsos($sampleN);

            if (empty($qsos)) {
                show_error(__("No QSOs returned by get_sample_qsos()"));
                return;
            }

            $pdfPath = $this->Labeldesigner_model->render_label_pdf_from_layout($layout, $label, $qsos);

            if (!$pdfPath || !file_exists($pdfPath)) {
                show_error(__("PDF file was not created"));
                return;
            }

            $download = (bool)$this->input->get('download');
            $this->stream_pdf($pdfPath, $tpl, $download);
        } catch (Throwable $e) {
            log_message('error', 'LABELDESIGNER pdf() failed: ' . $e->getMessage());
            log_message('error', $e->getTraceAsString());
            show_error(__("Label PDF failed: ") . $e->getMessage());
        }
    }

    // Download a template as a versioned JSON file (geometry snapshot + layout)
    public function export_template($id) {
        $envelope = $this->Labeldesigner_model->export_envelope((int)$id);
        if (!$envelope) {
            show_404();
            return;
        }

        // A template whose label type was deleted carries no geometry; the
        // import side needs one, so refuse rather than ship a half envelope.
        if (!is_array($envelope['label_type'] ?? null)) {
            show_error(__("The label type of this template no longer exists. Please select another one before exporting."));
            return;
        }

        $json = json_encode($envelope, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        session_write_close();

        $name = preg_replace('/[^A-Za-z0-9_-]/', '_', $envelope['name'] ?? '');
        if ($name === '') {
            $name = 'tpl_' . (int)$id;
        }

        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="label_template_' . $name . '_' . date('Ymd-Hi') . '.json"');
        if (!ini_get('zlib.output_compression')) {
            header('Content-Length: ' . strlen($json));
        }
        echo $json;
        exit;
    }

    // AJAX: POST a template JSON file (same caps as save_template)
    public function import_template() {
        $raw = $this->input->raw_input_stream;

        if (strlen($raw) > 256 * 1024) {
            return $this->_json_error('Payload too large', 413);
        }

        $payload = json_decode($raw, true);

        if (!is_array($payload)
            || ($payload['wavelog_label_template'] ?? 0) !== 1
            || !is_array($payload['label_type'] ?? null)
            || !is_array($payload['layout'] ?? null)
        ) {
            return $this->_json_error('Invalid payload');
        }

        $els = $payload['layout']['elements'] ?? null;
        if ($els !== null && (!is_array($els) || count($els) > 200)) {
            return $this->_json_error('Too many layout elements');
        }

        try {
            $out = $this->Labeldesigner_model->import_template(
                $payload['label_type'],
                empty($payload['paper_type']) ? null : $payload['paper_type'],
                $payload['layout'],
                is_string($payload['name'] ?? null) ? $payload['name'] : ''
            );
        } catch (Throwable $e) {
            log_message('error', 'LABELDESIGNER import failed: ' . $e->getMessage());
            return $this->_json_error('Import failed', 500);
        }

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'ok'         => true,
                'id'         => $out['template_id'],
                'name'       => $out['name'],
                'label_type' => $out['label_type'],
            ]));
    }

    private function _json_error($msg, $code = 400) {
        $this->output
            ->set_status_header($code)
            ->set_content_type('application/json')
            ->set_output(json_encode(['ok' => false, 'error' => $msg]));
    }

    function delete_template() {
	$raw = $this->input->raw_input_stream;
	$payload = json_decode($raw, true);

	if (!is_array($payload) || empty($payload['id'])) {
		return $this->_json_error('Invalid payload');
	}

	$id = (int)$payload['id'];

	$success = $this->Labeldesigner_model->delete_template($id);
	if (!$success) {
		return $this->_json_error('Failed to delete template', 500);
	}

	$this->output
		->set_content_type('application/json')
		->set_output(json_encode(['ok' => true]));
    }

    function copy_template() {
	$raw = $this->input->raw_input_stream;
	$payload = json_decode($raw, true);

	if (!is_array($payload) || empty($payload['id'])) {
		return $this->_json_error('Invalid payload');
	}

	$id = (int)$payload['id'];

	$newId = $this->Labeldesigner_model->copy_template($id);
	if (!$newId) {
		return $this->_json_error('Template not found', 404);
	}

	// Echo the generated name back so the frontend can label the new dropdown
	// option without an extra round-trip.
	$tpl = $this->Labeldesigner_model->get_template($newId);

	$this->output
		->set_content_type('application/json')
		->set_output(json_encode([
			'ok'   => true,
			'id'   => $newId,
			'name' => $tpl['name'] ?? '',
		]));
    }

    private function stream_pdf(string $pdfPath, array $tpl, bool $download = false): void
    {
	session_write_close();

	$name = preg_replace('/[^A-Za-z0-9_-]/', '_', $tpl['name'] ?? '');
	if ($name === '') {
		$name = 'tpl_' . ($tpl['id'] ?? 'x');
	}
	$filename = 'qsl_labels_' . $name . '_' . date('Ymd-Hi') . '.pdf';
	$disp = $download ? 'attachment' : 'inline';

	header('Content-Type: application/pdf');
	header('Content-Disposition: ' . $disp . '; filename="' . $filename . '"');
	if (!ini_get('zlib.output_compression')) {
		header('Content-Length: ' . filesize($pdfPath));
	}
	readfile($pdfPath);
	if (!@unlink($pdfPath)) {
		log_message('error', 'LABELDESIGNER: temp PDF unlink failed: ' . $pdfPath);
	}
	exit;
    }
}
