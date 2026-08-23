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

    // --- Import / export -----------------------------------------------
    // The exchanged file is a versioned envelope: template name, a geometry
    // snapshot of the label type (plus its paper type, if any) and the layout
    // JSON. Row ids are user/install-specific, so only geometry travels.

    public function export_envelope($id) {
	$tpl = $this->get_template($id);
	if (!$tpl) {
	    return null;
	}

	$label = $this->get_label_with_paper($tpl['label_type_id']);

	$label_type = null;
	$paper_type = null;
	if ($label) {
	    $label_type = [
		'label_name' => (string)$label->label_name,
		'metric'     => (string)$label->metric,
		'marginleft' => (float)$label->marginleft,
		'margintop'  => (float)$label->margintop,
		'nx'         => (float)$label->nx,
		'ny'         => (float)$label->ny,
		'spacex'     => (float)$label->spacex,
		'spacey'     => (float)$label->spacey,
		'width'      => (float)$label->width,
		'height'     => (float)$label->height,
		'font_size'  => (int)$label->font_size,
		'qsos'       => (int)$label->qsos,
	    ];

	    if (!empty($label->paper_id)) {
		$paper = $this->db->query(
		    "SELECT paper_name, metric, width, height, orientation
		     FROM paper_types WHERE paper_id = ? AND user_id = ?",
		    [(int)$label->paper_id, $this->session->userdata('user_id')]
		)->row_array();
		if ($paper) {
		    $paper_type = [
			'paper_name'  => (string)$paper['paper_name'],
			'metric'      => (string)$paper['metric'],
			'width'       => (float)$paper['width'],
			'height'      => (float)$paper['height'],
			'orientation' => (string)$paper['orientation'],
		    ];
		}
	    }
	}

	return [
	    'wavelog_label_template' => 1,
	    'name'       => (string)$tpl['name'],
	    'label_type' => $label_type,
	    'paper_type' => $paper_type,
	    'layout'     => json_decode($tpl['layout_json'], true),
	];
    }

    // $name arrives raw from the client; cleaned here so all sanitizing for
    // the import path lives in one place.
    public function import_template(array $label_type, $paper_type, array $layout, $name) {
	$uid = $this->session->userdata('user_id');

	$name = $this->_clean_name($name, 100);
	if ($name === '') {
	    $name = __('Imported');
	}

	$paper_id = is_array($paper_type)
	    ? $this->_find_or_create_paper_type($paper_type, $uid)
	    : 0;

	$lt = $this->_find_or_create_label_type($label_type, $paper_id, $uid);

	$tpl_id = $this->save_template(0, $name, $lt['id'], json_encode($layout, JSON_UNESCAPED_SLASHES));

	return [
	    'template_id' => $tpl_id,
	    'name'        => $name,
	    'label_type'  => $lt['meta'],
	];
    }

    // paper_types identity is (user_id, paper_name) — there's a unique index.
    // An import matching an existing name reuses that row, else a new one is
    // created with the geometry from the file.
    private function _find_or_create_paper_type(array $pt, $uid) {
	$name = $this->_clean_name($pt['paper_name'] ?? '', 191);
	if ($name === '') {
	    $name = __('Imported paper');
	}
	$metric      = self::_metric_or_default($pt['metric'] ?? '');
	$width       = $this->_clamp_num($pt['width'] ?? 210);
	$height      = $this->_clamp_num($pt['height'] ?? 297);
	$orientation = in_array($pt['orientation'] ?? '', ['P', 'L'], true) ? $pt['orientation'] : 'P';

	$row = $this->db->query(
	    "SELECT paper_id FROM paper_types WHERE user_id = ? AND paper_name = ?",
	    [$uid, $name]
	)->row_array();
	if ($row) {
	    return (int)$row['paper_id'];
	}

	$this->db->insert('paper_types', [
	    'user_id'     => $uid,
	    'paper_name'  => $name,
	    'metric'      => $metric,
	    'width'       => $width,
	    'height'      => $height,
	    'orientation' => $orientation,
	    'last_modified' => date('Y-m-d H:i:s'),
	]);
	return (int)$this->db->insert_id();
    }

    // label_types has no unique key on name; its identity for import purposes
    // is the geometry (incl. paper). A template whose geometry matches an
    // existing type reuses that row — the name is cosmetic and stays as-is.
    // On creation, a colliding name gets a " (n)" suffix like copy_template.
    private function _find_or_create_label_type(array $lt, $paper_id, $uid) {
	$metric = self::_metric_or_default($lt['metric'] ?? '');
	$geo = [
	    'marginleft' => $this->_clamp_num($lt['marginleft'] ?? 0),
	    'margintop'  => $this->_clamp_num($lt['margintop'] ?? 0),
	    'nx'         => $this->_clamp_num($lt['nx'] ?? 3, 1, 100),
	    'ny'         => $this->_clamp_num($lt['ny'] ?? 8, 1, 100),
	    'spacex'     => $this->_clamp_num($lt['spacex'] ?? 0),
	    'spacey'     => $this->_clamp_num($lt['spacey'] ?? 0),
	    'width'      => $this->_clamp_num($lt['width'] ?? 66.675),
	    'height'     => $this->_clamp_num($lt['height'] ?? 25.4),
	];
	$font_size = (int)$this->_clamp_num($lt['font_size'] ?? 8, 1, 100);
	$qsos      = (int)$this->_clamp_num($lt['qsos'] ?? 1, 1, 100);

	$row = $this->db->query(
	    "SELECT id, label_name FROM label_types
	     WHERE user_id = ? AND metric = ? AND marginleft = ? AND margintop = ?
	       AND nx = ? AND ny = ? AND spacex = ? AND spacey = ?
	       AND width = ? AND height = ? AND paper_type_id = ?",
	    [$uid, $metric, $geo['marginleft'], $geo['margintop'], $geo['nx'], $geo['ny'],
	     $geo['spacex'], $geo['spacey'], $geo['width'], $geo['height'], $paper_id]
	)->row_array();

	$lt_name = null;
	if ($row) {
	    $id       = (int)$row['id'];
	    $lt_name  = (string)$row['label_name'];
	} else {
	    $base = $this->_clean_name($lt['label_name'] ?? '', 250);
	    if ($base === '') {
		$base = __('Imported label type');
	    }
	    $lt_name = $base;
	    $i = 0;
	    while ($this->db->query(
		"SELECT 1 FROM label_types WHERE user_id = ? AND label_name = ?",
		[$uid, $lt_name]
	    )->row_array()) {
		$i++;
		$lt_name = mb_substr($base, 0, 250 - strlen(" ($i)")) . " ($i)";
	    }

	    $this->db->insert('label_types', [
		'user_id'     => $uid,
		'label_name'  => $lt_name,
		'metric'      => $metric,
		'marginleft'  => $geo['marginleft'],
		'margintop'   => $geo['margintop'],
		'nx'          => $geo['nx'],
		'ny'          => $geo['ny'],
		'spacex'      => $geo['spacex'],
		'spacey'      => $geo['spacey'],
		'width'       => $geo['width'],
		'height'      => $geo['height'],
		'font_size'   => $font_size,
		'qsos'        => $qsos,
		'paper_type_id' => $paper_id,
		'last_modified' => date('Y-m-d H:i:s'),
	    ]);
	    $id = (int)$this->db->insert_id();
	}

	// Meta for the frontend dropdown/canvas (same shape as LABEL_TYPES in
	// the designer view).
	$w_in = ($metric == 'in') ? $geo['width'] : $geo['width'] / 25.4;
	$h_in = ($metric == 'in') ? $geo['height'] : $geo['height'] / 25.4;
	$meta = [
	    'id'        => $id,
	    'w_in'      => round($w_in, 4),
	    'h_in'      => round($h_in, 4),
	    'nx'        => (int)$geo['nx'],
	    'ny'        => (int)$geo['ny'],
	    'name'      => $lt_name,
	    'has_paper' => $paper_id > 0,
	];

	return ['id' => $id, 'meta' => $meta];
    }

    private static function _metric_or_default($m) {
	return in_array($m, ['mm', 'in'], true) ? $m : 'mm';
    }

    // decimal(6,3) columns — clamp into range and round to column precision
    // (a value like 66.6751 would be stored as 66.675 and never match the
    // find-or-create geometry SELECT again).
    private function _clamp_num($v, $min = 0, $max = 999.999) {
	return round(max($min, min($max, (float)$v)), 3);
    }

    private function _clean_name($s, $max) {
	$s = preg_replace('/[\x00-\x1F\x7F]/u', '', strip_tags((string)$s));
	return mb_substr(trim($s), 0, $max);
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

        // Label cell size in mm — used to clamp wrapped text and drawn lines so
        // they can't bleed into the neighbouring cell on the sheet.
        $cell_w_mm = (($label->metric ?? 'mm') == 'in') ? $label->width * 25.4 : (float)$label->width;
        $cell_h_mm = (($label->metric ?? 'mm') == 'in') ? $label->height * 25.4 : (float)$label->height;

        $cal = $layout['calibration'] ?? ['offset_x_in' => 0, 'offset_y_in' => 0];
        $ox = (float)($cal['offset_x_in'] ?? 0);
        $oy = (float)($cal['offset_y_in'] ?? 0);

        // Template options (see layout.options). qsos_per_label chunks a QSO
        // group across labels; "repeats per QSO" elements print once per QSO at
        // the row pitch, the rest print once per label.
        $opts     = $layout['options'] ?? [];
        $perLabel = max(1, (int)($opts['qsos_per_label'] ?? 1));
        $pitch    = (float)($opts['row_pitch_in'] ?? 0.3);

        // Classic ruled look: automatic separator lines between QSO rows. The
        // rows' origin is derived from the repeating elements themselves (their
        // topmost y), so the rules land between the rows the user placed — no
        // line elements needed. Manual line elements can still be added on top.
        $rowSeparators = !empty($opts['row_separators']);
        $sepThickMm    = max(0.1, min(4, (float)($opts['sep_thick_pt'] ?? 0.4))) * 0.3528;
        $firstRowY     = null;
        if ($rowSeparators) {
            foreach (($layout['elements'] ?? []) as $el) {
                if (!empty($el['repeat_per_qso']) && ($el['type'] ?? 'field') !== 'line') {
                    $y = (float)($el['y_in'] ?? 0);
                    $firstRowY = ($firstRowY === null) ? $y : min($firstRowY, $y);
                }
            }
        }

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

                // Automatic separators between QSO rows (drawn first, so text
                // prints on top). One rule per row boundary, nudged 0.5mm up
                // from the next row's top edge so ascenders aren't struck.
                if ($rowSeparators && $firstRowY !== null && count($chunk) > 1) {
                    $pdf->SetDrawColor(0, 0, 0);
                    $pdf->SetLineWidth($sepThickMm);
                    $w = max(2, $cell_w_mm - 2);
                    for ($i = 1; $i < count($chunk); $i++) {
                        $y_mm = $cell['y'] + ($firstRowY + $oy + $pitch * $i) * 25.4 - 0.5;
                        $pdf->Line($cell['x'] + 1, $y_mm, $cell['x'] + 1 + $w, $y_mm);
                    }
                }
                foreach (($layout['elements'] ?? []) as $el) {
                    $type   = $el['type'] ?? 'field';
                    $field  = $el['field'] ?? '';
                    $repeat = !empty($el['repeat_per_qso']) && $type !== 'text';
                    $targets = $repeat ? $chunk : [$chunk[0]];

                    // Table grid: outer border + column/row rules. Column
                    // widths come from the (relative) col_w list; rows are
                    // evenly divided over the table height. Fields are placed
                    // into the cells separately (drawn on top).
                    if ($type === 'table') {
                        [$tr2, $tg2, $tb2] = $this->Qslpostcard_model->hex_to_rgb($el['color'] ?? '#000000');
                        $pdf->SetDrawColor($tr2, $tg2, $tb2);
                        $pdf->SetLineWidth(max(0.1, min(4, (float)($el['thick_pt'] ?? 0.4))) * 0.3528);

                        $x_mm = $cell['x'] + ((float)($el['x_in'] ?? 0) + $ox) * 25.4;
                        $y_mm = $cell['y'] + ((float)($el['y_in'] ?? 0) + $oy) * 25.4;
                        $w_mm = min((float)($el['w_in'] ?? 1) * 25.4, max(2, $cell_w_mm - 2));
                        $h_mm = min((float)($el['h_in'] ?? 0.5) * 25.4, max(2, $cell_h_mm - 2));

                        $pdf->Rect($x_mm, $y_mm, $w_mm, $h_mm);

                        $cols = max(1, min(12, (int)($el['cols'] ?? 3)));
                        $rows = max(1, min(20, (int)($el['rows'] ?? 3)));

                        // Column boundaries as cumulative fractions of the width
                        $colw = [];
                        if (is_array($el['col_w'] ?? null)) {
                            foreach ($el['col_w'] as $cw) {
                                $colw[] = max(0, (float)$cw);
                            }
                        }
                        if (count($colw) < $cols || array_sum($colw) <= 0) {
                            $colw = array_fill(0, $cols, 1);
                        }
                        $tot = array_sum($colw);
                        $frac = 0;
                        for ($i = 0; $i < $cols - 1; $i++) {
                            $frac += $colw[$i] / $tot;
                            $bx = $x_mm + $w_mm * $frac;
                            $pdf->Line($bx, $y_mm, $bx, $y_mm + $h_mm);
                        }
                        for ($i = 1; $i < $rows; $i++) {
                            $by = $y_mm + $h_mm * $i / $rows;
                            $pdf->Line($x_mm, $by, $x_mm + $w_mm, $by);
                        }
                        continue;
                    }

                    // Ruled lines (grid separators between QSO details). Like
                    // "repeats per QSO" fields, an h-line with repeat enabled
                    // draws once per row at the row pitch.
                    if ($type === 'line') {
                        [$lr, $lg, $lb] = $this->Qslpostcard_model->hex_to_rgb($el['color'] ?? '#000000');
                        $pdf->SetDrawColor($lr, $lg, $lb);
                        $pdf->SetLineWidth(max(0.1, min(4, (float)($el['thick_pt'] ?? 0.5))) * 0.3528);
                        $len_mm = max(0.5, (float)($el['len_in'] ?? 1)) * 25.4;
                        $isV = (($el['orient'] ?? 'h') === 'v');
                        foreach ($targets as $rowIdx => $_q) {
                            $x_mm = $cell['x'] + ((float)($el['x_in'] ?? 0) + $ox) * 25.4;
                            $y_mm = $cell['y'] + ((float)($el['y_in'] ?? 0) + $oy + ($repeat ? $pitch * $rowIdx : 0)) * 25.4;
                            if ($isV) {
                                $h = min($len_mm, max(2, $cell_h_mm - 2));
                                $pdf->Line($x_mm, $y_mm, $x_mm, $y_mm + $h);
                            } else {
                                $w = min($len_mm, max(2, $cell_w_mm - 2));
                                $pdf->Line($x_mm, $y_mm, $x_mm + $w, $y_mm);
                            }
                        }
                        continue;
                    }

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
