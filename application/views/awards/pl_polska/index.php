<script>
	var tileUrl="<?php echo $this->optionslib->get_option('option_map_tile_server');?>";
    var lang_polish_voivodeship = "<?= __("Polish Voivodeships"); ?>";
    var lang_hover_over_voivodeship = "<?= __("Hover over a voivodeship"); ?>";
</script>

<style>
    #polska-map {
        height: calc(100vh - 500px) !important;
        max-height: 900px !important;
        position: relative;
    }
    .map-spinner-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.4);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 1000;
    }
</style>

<div class="container">
    <!-- Award Info Box -->
    <br>
    <div class="position-relative">
        <div id="awardInfoButton">
            <script>
            var lang_awards_info_button = "<?= __("Award Info"); ?>";
            var lang_award_info_ln1 = "<?= __('"Polska" Award'); ?>";
            var lang_award_info_ln2 = "<?= __("The Polska Award is issued by the Polish Amateur Radio Union (PZK) for contacts with stations operating from all 16 Polish voivodeships (provinces). Valid from January 1, 1999."); ?>";
            var lang_award_info_ln3 = "<?= __("Award categories: MIXED (all modes/bands), PHONE (SSB/AM/FM/SSTV), CW, DIGI (RTTY/PSK/FSK), and individual bands (160M-2M). Classes: Basic (1 QSO/voiv), Bronze (3), Silver (7), Gold (12). All 16 voivodeships required."); ?>";
            var lang_award_info_ln4 = "<?= sprintf(__("Official rules and information: %s"), "<a href='https://awards.pzk.org.pl/polish-awards/polska.html' target='_blank'>" . __("PZK Polska Award Rules") . "</a>"); ?>";
            var lang_award_info_ln5 = "<?= __("Requirements: COL_STATE (voivodeship code), COL_DXCC=269 (Poland), QSO date >= 1999-01-01. No cross-band/cross-mode/repeater contacts."); ?>";
            </script>
            <h2><?php echo $page_title; ?></h2>
            <button type="button" class="btn btn-sm btn-primary me-1" id="displayAwardInfo"><?= __("Award Info"); ?></button>
        </div>

    </div>
    <!-- End of Award Info Box -->

    <form class="form" action="<?php echo site_url('awards/pl_polska'); ?>" method="post" enctype="multipart/form-data">
        <fieldset>

        <div class="mb-3 row">
            <label class="col-md-2 control-label"><?= __("Station Location"); ?></label>
            <div class="col-md-10">
                <?php
                $active_station = null;
                foreach ($station_profile->result() as $station) {
                    if ($station->station_id == $active_station_id) {
                        $active_station = $station;
                        break;
                    }
                }
                if ($active_station) {
                    echo '<span class="badge text-bg-info">' . htmlspecialchars($active_station->station_profile_name) . '</span>';
                }
                ?>
            </div>
        </div>

        <div class="mb-3 row">
            <div class="col-md-2">
                <?= __("Confirmation methods"); ?>
                <i class="fas fa-info-circle" data-bs-toggle="tooltip" data-bs-placement="right" title="<?= __("According to official award rules, paper QSL cards or LoTW confirmations are accepted for award applications. Other digital confirmations are shown here for tracking purposes only."); ?>"></i>
            </div>
            <div class="col-md-10">
                <div class="form-check-inline">
                    <input class="form-check-input" type="checkbox" name="qsl" value="1" id="qsl" <?php if ($this->input->post('qsl') || $this->input->method() !== 'post') echo ' checked="checked"'; ?> >
                    <label class="form-check-label" for="qsl"><?= __("QSL"); ?></label>
                </div>
                <div class="form-check-inline">
                    <input class="form-check-input" type="checkbox" name="lotw" value="1" id="lotw" <?php if ($this->input->post('lotw') || $this->input->method() !== 'post') echo ' checked="checked"'; ?> >
                    <label class="form-check-label" for="lotw"><?= __("LoTW"); ?></label>
                </div>
                <div class="form-check-inline">
                    <input class="form-check-input" type="checkbox" name="eqsl" value="1" id="eqsl" <?php if ($this->input->post('eqsl')) echo ' checked="checked"'; ?> >
                    <label class="form-check-label" for="eqsl"><?= __("eQSL"); ?></label>
                </div>
                <div class="form-check-inline">
                    <input class="form-check-input" type="checkbox" name="qrz" value="1" id="qrz" <?php if ($this->input->post('qrz')) echo ' checked="checked"'; ?> >
                    <label class="form-check-label" for="qrz"><?= __("QRZ.com"); ?></label>
                </div>
                <div class="form-check-inline">
                    <input class="form-check-input" type="checkbox" name="clublog" value="1" id="clublog" <?php if ($this->input->post('clublog')) echo ' checked="checked"'; ?> >
                    <label class="form-check-label" for="clublog"><?= __("Clublog"); ?></label>
                </div>
            </div>
        </div>

        <div class="mb-3 row">
            <label class="col-md-2 control-label" for="button1id"></label>
            <div class="col-md-10">
                <button id="button2id" type="reset" name="button2id" class="btn btn-sm btn-warning"><?= __("Reset"); ?></button>
                <button id="button1id" type="submit" name="button1id" class="btn btn-sm btn-primary"><?= __("Show"); ?></button>
            </div>
        </div>
    </fieldset>
</form>
<br />

<?php
if ($polska_array) {
    $mode_categories = array('MIXED', 'PHONE', 'CW', 'DIGI');

    // Award Categories Information
    echo '<div class="card mb-3">';
    echo '<div class="card-header"><h5 class="mb-0"><i class="fas fa-trophy"></i> ' . __('Award Categories') . '</h5></div>';
    echo '<div class="card-body">';
    echo '<p class="mb-2">' . __('Polska Award categories are based on the minimum number of confirmed QSOs with each of all 16 voivodeships:') . '</p>';
    echo '<div class="row">';
    echo '<div class="col-md-6">';
    echo '<ul class="mb-2">';
    echo '<li><i class="fas fa-certificate text-secondary"></i> <strong>' . __('Basic Class') . ':</strong> ' . __('1 QSO per voivodeship') . '</li>';
    echo '<li><i class="fas fa-medal" style="color: #cd7f32 !important;"></i> <strong>' . __('Bronze Class (3rd)') . ':</strong> ' . __('3 QSOs per voivodeship') . '</li>';
    echo '</ul>';
    echo '</div>';
    echo '<div class="col-md-6">';
    echo '<ul class="mb-2">';
    echo '<li><i class="fas fa-medal" style="color: #c0c0c0 !important;"></i> <strong>' . __('Silver Class (2nd)') . ':</strong> ' . __('7 QSOs per voivodeship') . '</li>';
    echo '<li><i class="fas fa-trophy text-warning"></i> <strong>' . __('Gold Class (1st)') . ':</strong> ' . __('12 QSOs per voivodeship') . '</li>';
    echo '</ul>';
    echo '</div>';
    echo '</div>';

    // Display entitlement if user has earned any confirmed classes
    if (isset($polska_classes)) {
        $eligible_classes = array();
        foreach ($mode_categories as $category) {
            if (isset($polska_classes[$category]) && $polska_classes[$category]) {
                $eligible_classes[] = $category . ' (' . ucfirst($polska_classes[$category]) . ')';
            }
        }

        $valid_bands = array('160M', '80M', '40M', '30M', '20M', '17M', '15M', '12M', '10M', '6M', '2M');
        if (isset($polska_classes_bands)) {
            foreach ($polska_classes_bands as $band => $class) {
                if (in_array(strtoupper($band), $valid_bands) && $class) {
                    $eligible_classes[] = strtoupper($band) . ' (' . ucfirst($class) . ')';
                }
            }
        }

        if (!empty($eligible_classes)) {
            echo '<div class="alert alert-success mb-0 py-2" role="alert" style="font-size: 0.9rem;">';
            echo '<strong><i class="fas fa-award"></i> ' . __('Congratulations!') . '</strong> ';
            echo __('You are entitled to the following award categories') . ': ';

            $formatted_classes = array();
            foreach ($eligible_classes as $class_text) {
                if (preg_match('/^(.*?)\s*\(([^)]+)\)$/', $class_text, $matches)) {
                    $category = $matches[1];
                    $level = strtolower($matches[2]);

                    $icon = '';
                    if ($level == 'gold') $icon = '<i class="fas fa-trophy text-warning"></i> ';
                    elseif ($level == 'silver') $icon = '<i class="fas fa-medal" style="color: #c0c0c0 !important;"></i> ';
                    elseif ($level == 'bronze') $icon = '<i class="fas fa-medal" style="color: #cd7f32 !important;"></i> ';
                    elseif ($level == 'basic') $icon = '<i class="fas fa-certificate text-secondary"></i> ';

                    $formatted_classes[] = '<strong>' . $icon . $category . ' (' . ucfirst($level) . ')</strong>';
                } else {
                    $formatted_classes[] = '<strong>' . $class_text . '</strong>';
                }
            }

            echo implode(', ', $formatted_classes);
            echo '</div>';
        }
    }

    echo '</div>';
    echo '</div>';
?>

<!-- Bootstrap Tabs Navigation -->
<ul class="nav nav-tabs" id="polskaTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="mode-tab" data-bs-toggle="tab" data-bs-target="#mode-content" type="button" role="tab" aria-controls="mode-content" aria-selected="true">
            <?= __("QSOs by Mode Category") ?>
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="band-tab" data-bs-toggle="tab" data-bs-target="#band-content" type="button" role="tab" aria-controls="band-content" aria-selected="false">
            <?= __("QSOs by Band") ?>
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="map-tab" data-bs-toggle="tab" data-bs-target="#map-content" type="button" role="tab" aria-controls="map-content" aria-selected="false">
            <?= __("Map") ?>
        </button>
    </li>
</ul>

<!-- Tab Content -->
<div class="tab-content" id="polskaTabsContent">
    <!-- Tab 1: QSOs by Mode Category -->
    <div class="tab-pane fade show active" id="mode-content" role="tabpanel" aria-labelledby="mode-tab">
        <div class="mt-3">
<?php
    echo '<table style="width:100%" id="polskatable" class="table table-sm table-bordered table-hover table-striped table-condensed text-center">
        <thead class="table-secondary">
        <tr>
            <th style="width: 20%"><strong>' . __('Voivodeship') . '</strong></th>
            <th style="width: 10%"><strong>' . __('Code') . '</strong></th>
            <th style="width: 17.5%"><strong>' . __('MIXED') . '</strong></th>
            <th style="width: 17.5%"><strong>' . __('PHONE') . '</strong></th>
            <th style="width: 17.5%"><strong>' . __('CW') . '</strong></th>
            <th style="width: 17.5%"><strong>' . __('DIGI') . '</strong></th>
        </tr>
        </thead>
        <tbody>';

    foreach ($polska_array as $voivodeship_code => $value) {
        $voivodeship_name = $this->award_pl_polska->getVoivodeshipName($voivodeship_code);

        echo '<tr>
            <td style="text-align: left">' . $voivodeship_name . '</td>
            <td>' . $voivodeship_code . '</td>';
        foreach ($mode_categories as $category) {
            $count = isset($value[$category]) ? $value[$category] : 0;
            if ($count > 0) {
                echo '<td><div class="bg-success text-white">' . $count . '</div></td>';
            } else {
                echo '<td>-</td>';
            }
        }
        echo '</tr>';
    }

    echo '</tbody>
    <tfoot class="table-secondary">
        <tr>
            <td colspan="2" style="text-align: left"><strong>' . __("Total voivodeships") . '</strong></td>';

    foreach ($mode_categories as $category) {
        $count = isset($polska_totals[$category]) ? $polska_totals[$category] : 0;
        echo '<td style="text-align: center">';

        // Add class icon if earned
        if (isset($polska_classes[$category]) && $polska_classes[$category]) {
            $class_name = $polska_classes[$category];
            $icon = '';
            if ($class_name == 'gold') $icon = '<i class="fas fa-trophy text-warning"></i>';
            elseif ($class_name == 'silver') $icon = '<i class="fas fa-medal" style="color: #c0c0c0 !important;"></i>';
            elseif ($class_name == 'bronze') $icon = '<i class="fas fa-medal" style="color: #cd7f32 !important;"></i>';
            elseif ($class_name == 'basic') $icon = '<i class="fas fa-certificate text-secondary"></i>';
            echo '<span title="' . __('Bravo! You are entitled to') . ' ' . $category . ' ' . ucfirst($class_name) . ' ' . __('category award!') . '">' . $icon . '</span> ';
        }

        echo '<strong>' . $count . '/16</strong></td>';
    }

    echo '</tr>
    </tfoot>
    </table>';
?>
        </div>
    </div>

    <!-- Tab 2: QSOs by Band -->
    <div class="tab-pane fade" id="band-content" role="tabpanel" aria-labelledby="band-tab">
        <div class="mt-3">
<?php
    if (isset($polska_array_bands) && isset($worked_bands) && $polska_array_bands) {
        echo '<table style="width:100%" id="polskatable_bands" class="table table-sm table-bordered table-hover table-striped table-condensed text-center">
            <thead class="table-secondary">
            <tr>
                <th style="text-align: left; width: 20%"><strong>' . __('Voivodeship') . '</strong></th>
                <th style="width: 7%"><strong>' . __('Code') . '</strong></th>';

        foreach($worked_bands as $band) {
            echo '<th><strong>' . $band . '</strong></th>';
        }

        echo '</tr>
            </thead>
            <tbody>';

        foreach ($polska_array_bands as $voivodeship_code => $value) {
            $voivodeship_name = $this->award_pl_polska->getVoivodeshipName($voivodeship_code);

            echo '<tr>
                <td style="text-align: left">' . $voivodeship_name . '</td>
                <td>' . $voivodeship_code . '</td>';
            foreach ($worked_bands as $band) {
                $count = isset($value[$band]) ? $value[$band] : 0;
                if ($count > 0) {
                    echo '<td><div class="bg-success text-white">' . $count . '</div></td>';
                } else {
                    echo '<td>-</td>';
                }
            }
            echo '</tr>';
        }

        echo '</tbody>
        <tfoot class="table-secondary">
            <tr>
                <td colspan="2" style="text-align: left"><strong>' . __("Total voivodeships") . '</strong></td>';

        foreach ($worked_bands as $band) {
            $count = isset($polska_totals_bands[$band]) ? $polska_totals_bands[$band] : 0;
            echo '<td style="text-align: center">';

            // Add class icon if earned
            if (isset($polska_classes_bands[$band]) && $polska_classes_bands[$band]) {
                $class_name = $polska_classes_bands[$band];
                $icon = '';
                if ($class_name == 'gold') $icon = '<i class="fas fa-trophy text-warning"></i>';
                elseif ($class_name == 'silver') $icon = '<i class="fas fa-medal" style="color: #c0c0c0 !important;"></i>';
                elseif ($class_name == 'bronze') $icon = '<i class="fas fa-medal" style="color: #cd7f32 !important;"></i>';
                elseif ($class_name == 'basic') $icon = '<i class="fas fa-certificate text-secondary"></i>';
                echo '<span title="' . __('Bravo! You are entitled to') . ' ' . strtoupper($band) . ' ' . ucfirst($class_name) . ' ' . __('category award!') . '">' . $icon . '</span> ';
            }

            echo '<strong>' . $count . '/16</strong></td>';
        }

        echo '</tr>
        </tfoot>
        </table>';
    }
?>
        </div>
    </div>

    <!-- Tab 3: Map -->
    <div class="tab-pane fade" id="map-content" role="tabpanel" aria-labelledby="map-tab">
        <div class="mt-3">
            <div class="mb-3">
                <label for="polska-category-select" class="form-label"><?= __("Award Category:") ?></label>
                <select id="polska-category-select" class="form-select" style="max-width: 300px;">
                    <optgroup label="<?= __("Mode Categories") ?>">
                        <option value="MIXED" selected><?= __("MIXED") ?></option>
                        <option value="PHONE"><?= __("PHONE") ?></option>
                        <option value="CW"><?= __("CW") ?></option>
                        <option value="DIGI"><?= __("DIGI") ?></option>
                    </optgroup>
                    <optgroup label="<?= __("Band Categories") ?>">
                        <option value="160M">160M</option>
                        <option value="80M">80M</option>
                        <option value="40M">40M</option>
                        <option value="30M">30M</option>
                        <option value="20M">20M</option>
                        <option value="17M">17M</option>
                        <option value="15M">15M</option>
                        <option value="12M">12M</option>
                        <option value="10M">10M</option>
                        <option value="6M">6M</option>
                        <option value="2M">2M</option>
                    </optgroup>
                </select>
            </div>
            <div id="polska-map" class="map-leaflet"></div>
        </div>
    </div>
</div>

<!-- Tips Section -->
<div class="text-muted small mt-3">
    <i class="fas fa-info-circle me-1"></i><?= __('Tip:') ?> <?= __('This award uses the State field from your logbook. Ensure this field is populated for all SP (Poland) contacts.') ?> <?= __('Use') ?> <strong><?= __('Logbook Advanced') ?> / <?= __('Actions') ?> / <?= __('Fix State') ?></strong> <?= __('to auto-populate states from Maidenhead locators.') ?>
</div>

<?php
} else {
    echo '<div class="alert alert-danger" role="alert">' . __("Nothing found!") . '</div>';
}
?>

    </div>
</div>
</div>
