<?php

defined('BASEPATH') or exit('No direct script access allowed');

use Wavelog\Label\PDF_Label;

class Labeldesigner_model extends CI_Model {

    function __construct() {
        // Resolves Wavelog\Label\* (PDF_Label, tfpdf) to src/Label/
        $this->load->helper('psr4_autoloader');
        // Shared QSO-field resolution (resolve_field, FONT_MAP, hex_to_rgb, ...)
        $this->load->model('Qslpostcard_model');
    }

    public function list_templates() {
	$sql = "SELECT t.id, t.name, t.label_type_id, l.label_name
            FROM label_templates t
            LEFT OUTER JOIN label_types l ON (l.id = t.label_type_id AND l.user_id = t.user_id)
            WHERE t.user_id = ? ORDER BY t.updated_at DESC";
	return $this->db->query($sql, [$this->session->userdata('user_id')])->result_array();
    }

    public function get_template($id) {
	$sql = "SELECT * FROM label_templates WHERE id = ? AND user_id = ?";
	return $this->db->query($sql, [$id, $this->session->userdata('user_id')])->row_array();
    }

    public function save_template($id, $name, $label_type_id, $layout_json) {
        $uid = $this->session->userdata('user_id');

        $row = [
            'name'          => $name,
            'label_type_id' => $label_type_id,
            'layout_json'   => $layout_json,
            'user_id'       => $uid,
        ];

        if ($id > 0) {
            // Don't report success for a template that doesn't belong to the user.
            // Check ownership explicitly (a no-op update reports 0 affected rows, so
            // affected_rows() can't distinguish "not owned" from "unchanged").
            $owns = $this->db->query(
                "SELECT 1 FROM label_templates WHERE id = ? AND user_id = ?",
                [$id, $uid]
            )->row_array();
            if (!$owns) {
                return false;
            }
            $row['updated_at'] = date('Y-m-d H:i:s');
            $this->db->where('id', $id)->where('user_id', $uid)->update('label_templates', $row);
            return $id;
        } else {
            $this->db->insert('label_templates', $row);
            return (int)$this->db->insert_id();
        }
    }

    // Duplicate a saved template into a new row owned by the same user,
    // appending " (copy)" to the name. Labels have no per-template files
    // (unlike postcard backgrounds), so a plain row copy is enough.
    public function copy_template($id) {
        $uid = $this->session->userdata('user_id');

        $src = $this->db->query(
            "SELECT * FROM label_templates WHERE id = ? AND user_id = ?",
            [$id, $uid]
        )->row_array();

        if (!$src) {
            return false;
        }

        $copyName = mb_substr(rtrim((string)$src['name']) . ' (' . __('Copy') . ')', 0, 100);

        $row = [
            'name'          => $copyName,
            'label_type_id' => $src['label_type_id'],
            'layout_json'   => $src['layout_json'],
            'user_id'       => $uid,
        ];

        $this->db->insert('label_templates', $row);
        return (int)$this->db->insert_id();
    }

    function delete_template($id) {
        $uid = $this->session->userdata('user_id');
        $this->db->query("DELETE FROM label_templates WHERE id = ? AND user_id = ?", [$id, $uid]);
        return true;
    }

    // Fetch a label type together with its paper type geometry (aliases
    // paper_*). fetchLabels() doesn't select paper geometry, so this dedicated
    // join is the single source for the sheet renderer. Paper columns are NULL
    // when the label type has no paper assigned.
    public function get_label_with_paper($label_id, $user_id = null) {
        $uid = $user_id ?? $this->session->userdata('user_id');
        $sql = "SELECT l.id AS label_id, l.label_name, l.metric, l.marginleft, l.margintop,
                       l.nx, l.ny, l.spacex, l.spacey, l.width, l.height, l.font_size, l.qsos,
                       p.paper_id, p.metric AS paper_metric, p.width AS paper_width,
                       p.height AS paper_height, p.orientation
                FROM label_types l
                LEFT OUTER JOIN paper_types p ON (p.paper_id = l.paper_type_id AND p.user_id = l.user_id)
                WHERE l.id = ? AND l.user_id = ?";
        return $this->db->query($sql, [(int)$label_id, $uid])->row();
    }

    // --- Sheet render (tFPDF via PDF_Label, UTF-8 via embedded TrueType fonts) ---
    // $label: combined row from get_label_with_paper(). $qsos: array rows.
    // Renders one label cell per QSO group chunk on the sheet grid defined by
    // the label type (+ its paper type), honouring the start-at position.
    public function render_label_pdf_from_layout($layout, $label, $qsos, $startat = 1) {
        if (!defined('FPDF_FONTPATH')) {
            define('FPDF_FONTPATH', FCPATH . 'src/Label/font/');
        }

        // Paper size in mm (PDF_Label's doc unit), exactly like Labels::prepareLabel()
        if (($label->paper_metric ?? '') == 'in') {
            $paper_width  = $label->paper_width * 25.4;
            $paper_height = $label->paper_height * 25.4;
        } else {
            $paper_width  = $label->paper_width;
            $paper_height = $label->paper_height;
        }
        $orientation = $label->orientation ?? 'P';

        // font-size only feeds PDF_Label's own MultiCell line height (Add_Label),
        // which the designer never uses — clamp into _Get_Height_Chars' valid
        // range so construction can't error before we set per-element fonts.
        $font_size = max(6, min(15, (int)($label->font_size ?? 8)));

        $pdf = new PDF_Label(array(
            'paper-size' => 'custom',
            'metric'     => $label->metric,
            'marginLeft' => $label->marginleft,
            'marginTop'  => $label->margintop,
            'NX'         => $label->nx,
            'NY'         => $label->ny,
            'SpaceX'     => $label->spacex,
            'SpaceY'     => $label->spacey,
            'width'      => $label->width,
            'height'     => $label->height,
            'font-size'  => $font_size,
            'pgX'        => $paper_width,
            'pgY'        => $paper_height,
        ));

        $pdf->AddPage($orientation);

        // Register each DejaVu family once, regular + bold (uni=true embeds a subset).
        $pdf->AddFont('DejaVuSans', '', 'DejaVuSans.ttf', true);
        $pdf->AddFont('DejaVuSans', 'B', 'DejaVuSans-Bold.ttf', true);
        $pdf->AddFont('DejaVuSerif', '', 'DejaVuSerif.ttf', true);
        $pdf->AddFont('DejaVuSerif', 'B', 'DejaVuSerif-Bold.ttf', true);
        $pdf->AddFont('DejaVuSansMono', '', 'DejaVuSansMono.ttf', true);
        $pdf->AddFont('DejaVuSansMono', 'B', 'DejaVuSansMono-Bold.ttf', true);

        // Label cell width in mm — used to clamp wrapped text so it can't bleed
        // into the neighbouring cell on the sheet.
        $cell_w_mm = (($label->metric ?? 'mm') == 'in') ? $label->width * 25.4 : (float)$label->width;

        $cal = $layout['calibration'] ?? ['offset_x_in' => 0, 'offset_y_in' => 0];
        $ox = (float)($cal['offset_x_in'] ?? 0);
        $oy = (float)($cal['offset_y_in'] ?? 0);

        // Template options (see layout.options). qsos_per_label chunks a QSO
        // group across labels; "repeats per QSO" elements print once per QSO at
        // the row pitch, the rest print once per label.
        $opts     = $layout['options'] ?? [];
        $perLabel = max(1, (int)($opts['qsos_per_label'] ?? 1));
        $pitch    = (float)($opts['row_pitch_in'] ?? 0.3);

        // Skip already-used cells on a partially consumed sheet (same semantics
        // as the classic flow's startat handling).
        for ($i = 1; $i < (int)$startat; $i++) {
            $pdf->Next_Label($orientation);
        }

        // Group like Labels::makeMultiQsoLabel(): a group breaks when call,
        // satellite, sat mode or (with a sat mode) the RX band changes.
        $groups = [];
        $current_callsign = null;
        $current_sat = '';
        $current_sat_mode = '';
        $current_sat_bandrx = '';
        foreach ($qsos as $qso) {
            $sat_mode = $this->Qslpostcard_model->pretty_sat_mode($qso['COL_SAT_MODE'] ?? '');
            if (($sat_mode !== $current_sat_mode)
                || (($qso['COL_SAT_NAME'] ?? '') !== $current_sat)
                || (($qso['COL_CALL'] ?? '') !== $current_callsign)
                || ((($qso['COL_BAND_RX'] ?? '') !== $current_sat_bandrx) && ($sat_mode !== ''))
            ) {
                $current_callsign   = $qso['COL_CALL'] ?? '';
                $current_sat        = $qso['COL_SAT_NAME'] ?? '';
                $current_sat_mode   = $sat_mode;
                $current_sat_bandrx = $qso['COL_BAND_RX'] ?? '';
                $groups[] = [];
            }
            $groups[array_key_last($groups)][] = $qso;
        }

        foreach ($groups as $groupQsos) {
            foreach (array_chunk($groupQsos, $perLabel) as $chunk) {
                $cell = $pdf->Next_Label($orientation);

                foreach (($layout['elements'] ?? []) as $el) {
                    $type   = $el['type'] ?? 'field';
                    $field  = $el['field'] ?? '';
                    $repeat = !empty($el['repeat_per_qso']) && $type !== 'text';
                    $targets = $repeat ? $chunk : [$chunk[0]];

                    $font      = Qslpostcard_model::FONT_MAP[$el['font'] ?? 'Helvetica'] ?? 'DejaVuSans';
                    $pt        = (float)($el['font_pt'] ?? 8);
                    $bold      = !empty($el['bold']) ? 'B' : '';
                    $wrap_w_in = isset($el['wrap_w_in']) ? (float)$el['wrap_w_in'] : 0;
                    [$cr, $cg, $cb] = $this->Qslpostcard_model->hex_to_rgb($el['color'] ?? '#000000');

                    foreach ($targets as $rowIdx => $qso) {
                        if ($type === 'text') {
                            $val = $el['text'] ?? '';
                        } else {
                            $val = $this->Qslpostcard_model->resolve_field($field, $qso, null, $el);
                        }

                        if ($val === '') {
                            continue;
                        }

                        $x_in = (float)($el['x_in'] ?? 0) + $ox;
                        $y_in = (float)($el['y_in'] ?? 0) + $oy + ($repeat ? $pitch * $rowIdx : 0);

                        $x_mm = $cell['x'] + $x_in * 25.4;
                        $y_mm = $cell['y'] + $y_in * 25.4;

                        $pdf->SetFont($font, $bold, $pt);
                        $pdf->SetTextColor($cr, $cg, $cb);

                        if ($wrap_w_in > 0) {
                            // Line height derived from the point size (the postcard's
                            // fixed 4.5mm would overflow a small label cell).
                            $lh = max(2.2, $pt * 0.3528 * 1.15);
                            $w  = min($wrap_w_in * 25.4, max(2, $cell_w_mm - 2));
                            $pdf->SetXY($x_mm, $y_mm);
                            $pdf->MultiCell($w, $lh, $val, 0, 'L');
                        } else {
                            $pdf->Text($x_mm, $y_mm + ($pt * 0.30), $val);
                        }
                    }
                }
            }
        }

        $tmp = sys_get_temp_dir() . '/qsl_labels_' . uniqid() . '.pdf';
        $pdf->Output('F', $tmp);

        if (!file_exists($tmp)) {
            throw new Exception('FPDF completed but temp PDF was not created');
        }

        return $tmp;
    }
}
