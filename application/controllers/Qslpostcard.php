<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Qslpostcard extends CI_Controller {

    /**
     * Name of Path type for userdata location
     */
    private const PATH_TYPE = 'qslpostcard_images';

    public function __construct() {
        parent::__construct();

		if (!$this->user_model->authorize(2)) {
			$this->session->set_flashdata('error', __("You're not allowed to do that!"));
			redirect('dashboard');
		}

        $this->load->model('Qslpostcard_model');
    }

	public function index() {

        // a properly set userdata config is a hard depedency for this feature
        // we don't want to create new legacy paths so we just show that this feature
        // is unavailable uness the config is updated.
        $userdata_dir = $this->config->item('userdata');
        if (!isset($userdata_dir)) {
            echo __("QSL Postcard Designer is unavailable because the 'userdata' config option is not set. Your config is outdated.") . "<br>";
            echo __("Please compare your current config.php with the latest config.sample.php and update accordingly.");
            return;
        }

        $data['page_title'] = __("QSL Postcard Designer");
        $data['templates']  = $this->Qslpostcard_model->list_templates();

		$footerData = [];
		$footerData['scripts'] = [
			'assets/js/sections/qslpostcard.js',
		];

        $this->load->view('interface_assets/header', $data);
        $this->load->view('qslpostcard/designer');
        $this->load->view('interface_assets/footer', $footerData);
    }

    public function upload_preview() {

        $config['upload_path']   = $this->paths->getUserdataPath(self::PATH_TYPE, 'p');
        $config['allowed_types'] = 'jpg|jpeg|png|JPG|JPEG|PNG';
        $config['encrypt_name']  = true;
        $config['max_size']      = Qslpostcard_model::MAX_BG_IMAGE_BYTES / 1024; // KB; same cap as render-side

        $this->load->library('upload_guard');

        // Align the filename's extension with the file's real content (JPEGs named .png etc.)
        $this->upload_guard->normalize_image_ext('preview_image');

        if (!$this->upload_guard->has_free_space($config['upload_path'], $_FILES['preview_image']['size'])) {
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'ok' => false,
                    'error' => __("Not enough free disk space to store the QSL Background.")
                ]));
            return;
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

        if (!$this->upload_guard->is_real_image($data['full_path'])) {
            @unlink($data['full_path']);
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'ok' => false,
                    'error' => __("The uploaded file is not a valid image.")
                ]));
            return;
        }

        $rel_path = $this->paths->getUserdataPath(self::PATH_TYPE) . '/' . $data['file_name'];
        $url = base_url($rel_path);

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'ok' => true,
                'url' => $url,
                'path' => $rel_path
            ]));
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

        // Cap the raw body; a template layout is small JSON, anything larger is abuse
        if (strlen($raw) > 256 * 1024) {
            return $this->_json_error('Payload too large', 413);
        }

        $payload = json_decode($raw, true);

        if (!is_array($payload) || empty($payload['name']) || empty($payload['layout'])) {
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

        // preview_image is untrusted client input. Keep only the basename, validate
        // it's an image filename, and rebuild the canonical userdata path ourselves so
        // a forged value can't point outside the user's image dir (traversal/XSS).
        $preview_image = isset($payload['preview_image']) ? $payload['preview_image'] : null;
        if ($preview_image !== null) {
            $file = basename((string)$preview_image);
            if (!preg_match('/^[A-Za-z0-9._-]+\.(jpe?g|png)$/i', $file)) {
                return $this->_json_error('Invalid preview image');
            }
            $dir = $this->paths->getUserdataPath(self::PATH_TYPE, 'u');
            if ($dir === false) {
                return $this->_json_error('Invalid preview image');
            }
            $preview_image = $dir . '/' . $file;
        }

        $savedId = $this->Qslpostcard_model->save_template(
            $id,
            $name,
            json_encode($payload['layout'], JSON_UNESCAPED_SLASHES),
            $preview_image
        );

        if ($savedId === false) {
            return $this->_json_error('Template not found', 404);
        }

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(['ok' => true, 'id' => $savedId]));
    }

    // Generate a PDF for a set of QSOs in queue (v1: demo uses last N QSOs)
    public function pdf($template_id) {
        try {
            $tpl = $this->Qslpostcard_model->get_template((int)$template_id);
            if (!$tpl) {
                show_error(__("Template not found"));
                return;
            }

            $layout = json_decode($tpl['layout_json'], true);
            if (!is_array($layout)) {
                show_error(__("Template JSON is invalid"));
                return;
            }

            // Honour the template's options for the demo PDF. qsos_per_card /
            // per_callsign are read from layout.options inside the renderer; here
            // we translate print_background / skip_address into the renderer's
            // $background / $noaddress overrides.
            $opts    = $layout['options'] ?? [];
            $sampleN = max(3, (int)($opts['qsos_per_card'] ?? 1) * 3);

            // v1 demo data
            $qsos = $this->Qslpostcard_model->get_sample_qsos($sampleN);

            if (empty($qsos)) {
                show_error(__("No QSOs returned by get_sample_qsos()"));
                return;
            }

            $background = !empty($opts['print_background']) ? $tpl['preview_image'] : null;
            $noaddress  = !empty($opts['skip_address']);

            $pdfPath = $this->Qslpostcard_model->render_pdf_from_layout($layout, $qsos, false, $background, $noaddress);

            if (!$pdfPath || !file_exists($pdfPath)) {
                show_error(__("PDF file was not created"));
                return;
            }

            $download = (bool)$this->input->get('download');
            $this->stream_pdf($pdfPath, '', $tpl, $download);
        } catch (Throwable $e) {
            log_message('error', 'QSLPOSTCARD pdf() failed: ' . $e->getMessage());
            log_message('error', $e->getTraceAsString());
            show_error(__("QSL Postcard PDF failed: ") . $e->getMessage());
        }
    }

    public function printqueue() {
        $data['page_title'] = __("Print QSL Postcards");
        $data['templates']  = $this->Qslpostcard_model->list_templates();

        $this->load->view('interface_assets/header', $data);
        $this->load->view('qslpostcard/printqueue');
        $this->load->view('interface_assets/footer');
    }

    public function pdfqueue($template_id) {
        try {
            $tpl = $this->Qslpostcard_model->get_template((int)$template_id);
            if (!$tpl) {
                show_error(__("Template not found"));
                return;
            }

            $layout = json_decode($tpl['layout_json'], true);
            if (!is_array($layout)) {
                show_error(__("Template JSON is invalid"));
                return;
            }

            // The GET params are QSO *filters* only (station/band/mode/call). The
            // print options themselves come from the template's layout.options, not
            // the form: per_callsign + qsos_per_card are applied in the renderer,
            // print_background / skip_address are passed through here.
            $filters = $this->input->get(NULL, true);

            $qsos = $this->Qslpostcard_model->get_qsl_queue_qsos($filters);

            if (empty($qsos)) {
                show_error(__("No QSOs found for postcard printing"));
                return;
            }

            $opts       = $layout['options'] ?? [];
            $background = !empty($opts['print_background']) ? $tpl['preview_image'] : null;
            $noaddress  = !empty($opts['skip_address']);

            $pdfPath = $this->Qslpostcard_model->render_pdf_from_layout($layout, $qsos, false, $background, $noaddress);

            $download = (bool)$this->input->get('download');
            $this->stream_pdf($pdfPath, 'queue', $tpl, $download);
        } catch (Throwable $e) {
            log_message('error', 'QSLPOSTCARD pdfqueue() failed: ' . $e->getMessage());
            log_message('error', $e->getTraceAsString());
            show_error(__("QSL Postcard Queue PDF failed: ") . $e->getMessage());
        }
    }

    public function printqueue_selected() {
        // Accept the ids as a JSON string (one POST var, immune to
        // max_input_vars) or as a classic selected_qsos[] array.
        $selected_ids = $this->input->post('selected_qsos');
        if (is_string($selected_ids)) {
            $selected_ids = xss_clean(json_decode($selected_ids, true));
        }

        if (!is_array($selected_ids) || empty($selected_ids)) {
            show_error(__("No QSOs were selected"));
            return;
        }

        $data['page_title'] = __("Print Selected QSL Postcards");
        $data['templates'] = $this->Qslpostcard_model->list_templates();
        $data['selected_ids'] = array_values(array_filter(array_map('intval', $selected_ids)));

        $this->load->view('interface_assets/header', $data);
        $this->load->view('qslpostcard/printqueue_selected');
        $this->load->view('interface_assets/footer');
    }

    public function pdfselected($template_id) {
        try {
            $tpl = $this->Qslpostcard_model->get_template((int)$template_id);
            if (!$tpl) {
                show_error(__("Template not found"));
                return;
            }

            $layout = json_decode($tpl['layout_json'], true);
            if (!is_array($layout)) {
                show_error(__("Template JSON is invalid"));
                return;
            }

            // Accept the ids as a JSON string (one POST var, immune to
            // max_input_vars) or as a classic selected_ids[] array.
            $selected_ids = $this->input->post('selected_ids');
            if (is_string($selected_ids)) {
                $selected_ids = xss_clean(json_decode($selected_ids, true));
            }
            if (!is_array($selected_ids) || empty($selected_ids)) {
                show_error(__("No selected QSO IDs were provided"));
                return;
            }

            $qsos = $this->Qslpostcard_model->get_qsos_by_ids($selected_ids);

            if (empty($qsos)) {
                show_error(__("No QSOs found for postcard printing"));
                return;
            }

            // Print options come from the template's layout.options, not the form:
            // per_callsign + qsos_per_card are applied in the renderer, print_background
            // / skip_address are passed through here.
            $opts       = $layout['options'] ?? [];
            $background = !empty($opts['print_background']) ? $tpl['preview_image'] : null;
            $noaddress  = !empty($opts['skip_address']);

            $pdfPath = $this->Qslpostcard_model->render_pdf_from_layout($layout, $qsos, false, $background, $noaddress);

            $download = (bool)$this->input->get('download');
            $this->stream_pdf($pdfPath, 'selected', $tpl, $download);
        } catch (Throwable $e) {
            log_message('error', 'QSLPOSTCARD pdfselected() failed: ' . $e->getMessage());
            log_message('error', $e->getTraceAsString());
            show_error(__("Selected QSL Postcard PDF failed: ") . $e->getMessage());
        }
    }

    public function create_oqrs_drafts($template_id) {
        if (strtoupper($this->input->method()) !== 'POST') {
            return $this->draft_response(405, 'POST required');
        }
        $token = (string)$this->session->userdata('postcard_draft_token');
        if ($token === '' || !hash_equals($token, (string)$this->input->post('draft_token'))) {
            return $this->draft_response(403, 'Invalid draft request token');
        }

        $created = 0;
        try {
            $tpl = $this->Qslpostcard_model->get_template((int)$template_id);
            $layout = $tpl ? json_decode($tpl['layout_json'], true) : null;
            if (!is_array($layout)) {
                throw new InvalidArgumentException('Postcard template not found');
            }

            $all = $this->input->post('print_all') === '1';
            if ($all) {
                $qsos = $this->Qslpostcard_model->get_qsl_queue_qsos();
            } else {
                $ids = json_decode((string)$this->input->post('selected_ids'), true);
                if (!is_array($ids) || count($ids) > 100 || !$ids) {
                    throw new InvalidArgumentException('Select 1 to 100 QSOs');
                }
                $qsos = $this->Qslpostcard_model->get_qsos_by_ids($ids);
                if (count($qsos) !== count(array_unique(array_map('intval', $ids)))) {
                    throw new InvalidArgumentException('Some selected QSOs are unavailable');
                }
            }

            if (!$qsos || count($qsos) > 100) {
                throw new InvalidArgumentException('Select 1 to 100 QSOs');
            }

            $byId = [];
            foreach ($qsos as $qso) {
                $byId[(int)$qso['COL_PRIMARY_KEY']] = $qso;
            }
            $requests = $this->Qslpostcard_model->get_oqrs_requests_for_qsos(array_keys($byId));
            if ($all) {
                // The print queue can also contain paper QSLs without OQRS requests.
                $byId = array_intersect_key(
                    $byId, array_fill_keys(array_column($requests, 'qsoid'), true)
                );
            }
            if (!$requests || count($requests) !== count($byId)) {
                throw new InvalidArgumentException('Every selected QSO must have exactly one OQRS request');
            }
            foreach ($requests as $request) {
                if (!isset($byId[(int)$request['qsoid']])
                    || !filter_var($request['email'], FILTER_VALIDATE_EMAIL)) {
                    throw new InvalidArgumentException('An OQRS request has no valid email address');
                }
            }

            $this->load->library('Postcard_draft_mailbox');
            $opts = $layout['options'] ?? [];
            $background = !empty($opts['print_background']) ? $tpl['preview_image'] : null;
            $noaddress = !empty($opts['skip_address']);
            foreach ($requests as $request) {
                $qso = $byId[(int)$request['qsoid']];
                $pdfPath = $this->Qslpostcard_model->render_pdf_from_layout(
                    $layout, [$qso], false, $background, $noaddress
                );
                if (!$pdfPath || !is_file($pdfPath)) {
                    throw new RuntimeException('QSL PDF could not be generated');
                }
                try {
                    $this->postcard_draft_mailbox->append(
                        $request['email'], $request['requestcallsign'], $pdfPath
                    );
                    $created++;
                } finally {
                    @unlink($pdfPath);
                }
            }

            return $this->draft_response(200, 'Drafts saved in Mailcow', $created);
        } catch (Throwable $e) {
            log_message('error', 'QSLPOSTCARD create_oqrs_drafts failed: ' . $e->getMessage());
            return $this->draft_response(
                $e instanceof InvalidArgumentException ? 400 : 500,
                $e->getMessage(), $created
            );
        }
    }

    private function draft_response(int $status, string $message, int $created = 0): void {
        $webmailUrl = (string)(getenv('WAVELOG_DRAFTS_WEBMAIL_URL')
            ?: $this->config->item('postcard_drafts_webmail_url'));
        if (!filter_var($webmailUrl, FILTER_VALIDATE_URL)
            || parse_url($webmailUrl, PHP_URL_SCHEME) !== 'https') {
            $webmailUrl = '';
        }
        $this->output->set_status_header($status)
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'message' => $message, 'created' => $created, 'webmail_url' => $webmailUrl
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

		$success = $this->Qslpostcard_model->delete_template($id);
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

		$newId = $this->Qslpostcard_model->copy_template($id);
		if (!$newId) {
			return $this->_json_error('Template not found', 404);
		}

		// Echo the generated name back so the frontend can label the new dropdown
		// option without an extra round-trip.
		$tpl = $this->Qslpostcard_model->get_template($newId);

		$this->output
			->set_content_type('application/json')
			->set_output(json_encode([
				'ok'   => true,
				'id'   => $newId,
				'name' => $tpl['name'] ?? '',
			]));
	}

	private function stream_pdf(string $pdfPath, string $variant, array $tpl, bool $download = false): void
	{
		session_write_close();

		$name = preg_replace('/[^A-Za-z0-9_-]/', '_', $tpl['name'] ?? '');
		if ($name === '') {
			$name = 'tpl_' . ($tpl['id'] ?? 'x');
		}
		$suffix = $variant !== '' ? '_' . $variant : '';
		$filename = 'qsl_postcards' . $suffix . '_' . $name . '_' . date('Ymd-Hi') . '.pdf';
		$disp = $download ? 'attachment' : 'inline';

		header('Content-Type: application/pdf');
		header('Content-Disposition: ' . $disp . '; filename="' . $filename . '"');
		if (!ini_get('zlib.output_compression')) {
			header('Content-Length: ' . filesize($pdfPath));
		}
		readfile($pdfPath);
		if (!@unlink($pdfPath)) {
			log_message('error', 'QSLPOSTCARD: temp PDF unlink failed: ' . $pdfPath);
		}
		exit;
	}
}
