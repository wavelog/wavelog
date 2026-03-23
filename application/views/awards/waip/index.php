<div class="container">
    <!-- Award Info Box -->
    <br>
    <div class="position-relative">
        <div id="awardInfoButton">
            <script>
            var lang_awards_info_button = "<?= __("Award Info"); ?>";
            var lang_award_info_ln1 = "<?= __("WAIP Award"); ?>";
            var lang_award_info_ln2 = "<?= __("The WAIP (Worked All Italian Provinces) Award is issued for contacts with stations operating from Italian provinces. Italy has over 100 provinces, making this a challenging and prestigious award."); ?>";
            var lang_award_info_ln3 = "<?= __("Award categories: PHONE (SSB/AM/FM/SSTV), CW, DIGI (RTTY/PSK/FT8/etc.), and individual bands. All provinces must be confirmed via QSL card. Requirements: COL_STATE (2-letter province code), COL_DXCC=248 (Italy) or 225 (Sardinia), QSL card confirmation only. No satellite contacts allowed."); ?>";
			var lang_award_info_ln4 = "<?= __("Province codes are 2-letter abbreviations (e.g., RM=Roma, MI=Milano, NA=Napoli). See Italian provincial award references for complete list."); ?>";
			var lang_award_info_ln5 = "<?= sprintf(__("For more information about the WAIP award, visit: %s"), "<a href='https://www.ari.it/diplomi-hf/diplomiari/waip/1724-waip-worked-all-italian-provinces.html' target='_blank'>" . __("WAIP Award Information") . "</a>"); ?>";
            </script>
            <h2><?php echo $page_title; ?></h2>
            <button type="button" class="btn btn-sm btn-primary me-1" id="displayAwardInfo"><?= __("Award Info"); ?></button>
        </div>

    </div>
    <!-- End of Award Info Box -->

    <div class="alert alert-info mb-3">
        <i class="fas fa-info-circle"></i>
        <?= __("This award tracks worked and confirmed status for Italian provinces. Only QSL card confirmations are accepted. Green = Confirmed, Orange = Worked but not confirmed."); ?>
    </div>

<?php
if ($waip_array) {
    $mode_categories = array('MIXED', 'PHONE', 'CW', 'DIGI');

    // Progress summary
    echo '<div class="card mb-3">';
    echo '<div class="card-header"><h5 class="mb-0"><i class="fas fa-trophy"></i> ' . __("Award Progress") . '</h5></div>';
    echo '<div class="card-body">';
    echo '<p class="mb-2">' . __("Progress toward working all Italian provinces:") . '</p>';

    $total_provinces = count($waip_array);

    echo '<div class="row">';
    echo '<div class="col-md-6">';
    echo '<h6>' . __("By Mode Category") . '</h6>';
    echo '<table class="table table-sm">';
    echo '<thead><tr><th>' . __("Category") . '</th><th>' . __("Confirmed") . '</th><th>' . __("Progress") . '</th></tr></thead>';
    echo '<tbody>';

    foreach ($mode_categories as $category) {
        $count = isset($waip_totals[$category]) ? $waip_totals[$category] : 0;
        $percentage = ($count / $total_provinces) * 100;
        $progress_class = $percentage == 100 ? 'success' : ($percentage >= 50 ? 'warning' : 'danger');

        echo '<tr>';
        echo '<td><strong>' . $category . '</strong></td>';
        echo '<td>' . $count . '/' . $total_provinces . ' (' . number_format($percentage, 1) . '%)</td>';
        echo '<td><div class="progress" style="height: 20px; position: relative;"><div class="progress-bar bg-' . $progress_class . '" role="progressbar" style="width: ' . number_format($percentage, 2) . '%;">' . number_format($percentage, 1) . '%' . '</div></div></td>';
        echo '</tr>';
    }

    echo '</tbody></table>';
    echo '</div>';

    if (isset($waip_totals_bands) && !empty($waip_totals_bands)) {
        echo '<div class="col-md-6">';
        echo '<h6>' . __("By Band") . '</h6>';
        echo '<table class="table table-sm">';
        echo '<thead><tr><th>' . __("Band") . '</th><th>' . __("Confirmed") . '</th><th>' . __("Progress") . '</th></tr></thead>';
        echo '<tbody>';

        foreach (array_keys($waip_totals_bands) as $band) {
            $count = isset($waip_totals_bands[$band]) ? $waip_totals_bands[$band] : 0;
            $percentage = ($count / $total_provinces) * 100;
            $progress_class = $percentage == 100 ? 'success' : ($percentage >= 50 ? 'warning' : 'danger');

            echo '<tr>';
            echo '<td><strong>' . $band . '</strong></td>';
            echo '<td>' . $count . '/' . $total_provinces . ' (' . number_format($percentage, 1) . '%)</td>';
            echo '<td><div class="progress" style="height: 20px; position: relative;"><div class="progress-bar bg-' . $progress_class . '" role="progressbar" style="width: ' . number_format($percentage, 2) . '%;">' . number_format($percentage, 1) . '%' . '</div></div></td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
        echo '</div>';
    }

    echo '</div>';
    echo '</div>';
    echo '</div>';
?>

<!-- Bootstrap Tabs Navigation -->
<ul class="nav nav-tabs" id="waipTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="mode-tab" data-bs-toggle="tab" data-bs-target="#mode-content" type="button" role="tab" aria-controls="mode-content" aria-selected="true">
            <?= __("By Mode Category") ?>
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="band-tab" data-bs-toggle="tab" data-bs-target="#band-content" type="button" role="tab" aria-controls="band-content" aria-selected="false">
            <?= __("By Band") ?>
        </button>
    </li>
</ul>

<!-- Tab Content -->
<div class="tab-content" id="waipTabsContent">
    <!-- Tab 1: By Mode Category -->
    <div class="tab-pane fade show active" id="mode-content" role="tabpanel" aria-labelledby="mode-tab">
        <div class="mt-3">
<?php
    echo '<table style="width:100%" id="waiptable" class="table table-sm table-bordered table-hover table-striped table-condensed text-center">
        <thead class="table-secondary">
        <tr>
            <th style="width: 30%"><strong>' . __("Province") . '</strong></th>
            <th style="width: 10%"><strong>' . __("Code") . '</strong></th>
            <th style="width: 15%"><strong>' . __("MIXED") . '</strong></th>
            <th style="width: 15%"><strong>' . __("PHONE") . '</strong></th>
            <th style="width: 15%"><strong>' . __("CW") . '</strong></th>
            <th style="width: 15%"><strong>' . __("DIGI") . '</strong></th>
        </tr>
        </thead>
        <tbody>';

    foreach ($waip_array as $province_code => $value) {
        $province_name = $this->waip->getProvinceName($province_code);

        echo '<tr>
            <td style="text-align: left">' . $province_name . '</td>
            <td>' . $province_code . '</td>';
        foreach ($mode_categories as $category) {
            $confirmed = isset($value[$category]) ? $value[$category] : 0;
            $worked = isset($waip_worked[$province_code][$category]) ? $waip_worked[$province_code][$category] : 0;

            if ($confirmed > 0) {
                // Confirmed - green
                echo '<td><div class="bg-success awardsBgSuccess"><a class="text-white" href=\'javascript:displayContacts("' . $province_code . '","All","All","All","' . $category . '","WAIP", "' . $qsl_string . '")\'>C</a></div></td>';
            } elseif ($worked > 0) {
                // Only worked - orange
                echo '<td><div class="bg-danger awardsBgWarning"><a class="text-white" href=\'javascript:displayContacts("' . $province_code . '","All","All","All","' . $category . '","WAIP", "")\'>W</a></div></td>';
            } else {
                echo '<td>-</td>';
            }
        }
        echo '</tr>';
    }

    echo '</tbody>
    <tfoot class="table-secondary">
        <tr>
            <td colspan="2" style="text-align: left"><strong>' . __("Total confirmed") . '</strong></td>';

    foreach ($mode_categories as $category) {
        $count = isset($waip_totals[$category]) ? $waip_totals[$category] : 0;
        echo '<td style="text-align: center"><strong>' . $count . '/' . $total_provinces . '</strong></td>';
    }

    echo '</tr>
    </tfoot>
    </table>';
?>
        </div>
    </div>

    <!-- Tab 2: By Band -->
    <div class="tab-pane fade" id="band-content" role="tabpanel" aria-labelledby="band-tab">
        <div class="mt-3">
<?php
    if (isset($waip_array_bands)) {
        echo '<table style="width:100%" id="waiptable_bands" class="table table-sm table-bordered table-hover table-striped table-condensed text-center">
            <thead class="table-secondary">
            <tr>
                <th style="text-align: left; width: 25%"><strong>' . __("Province") . '</strong></th>
                <th style="width: 7%"><strong>' . __("Code") . '</strong></th>';

        foreach(array_keys($waip_totals_bands) as $band) {
            echo '<th><strong>' . $band . '</strong></th>';
        }

        echo '</tr>
            </thead>
            <tbody>';

        // Sort provinces by code for consistent display
        if (is_array($waip_array_bands)) {
            ksort($waip_array_bands);
        }

        foreach ($waip_array_bands as $province_code => $value) {
            $province_name = $this->waip->getProvinceName($province_code);

            echo '<tr>
                <td style="text-align: left">' . $province_name . '</td>
                <td>' . $province_code . '</td>';
            foreach (array_keys($waip_totals_bands) as $band) {
                $confirmed = isset($value[$band]) ? $value[$band] : 0;
                $worked = isset($waip_worked_bands[$province_code][$band]) ? $waip_worked_bands[$province_code][$band] : 0;

                if ($confirmed > 0) {
                    // Confirmed - green
                    echo '<td><div class="bg-success awardsBgSuccess"><a class="text-white" href=\'javascript:displayContacts("' . $province_code . '","' . $band . '","All","All","All","WAIP", "' . $qsl_string . '")\'>C</a></div></td>';
                } elseif ($worked > 0) {
                    // Only worked - orange
                    echo '<td><div class="bg-danger awardsBgWarning"><a class="text-white" href=\'javascript:displayContacts("' . $province_code . '","' . $band . '","All","All","All","WAIP", "")\'>W</a></div></td>';
                } else {
                    echo '<td>-</td>';
                }
            }
            echo '</tr>';
        }

        echo '</tbody>
        <tfoot class="table-secondary">
            <tr>
                <td colspan="2" style="text-align: left"><strong>' . __("Total confirmed") . '</strong></td>';

        foreach (array_keys($waip_totals_bands) as $band) {
            $count = isset($waip_totals_bands[$band]) ? $waip_totals_bands[$band] : 0;
            echo '<td style="text-align: center"><strong>' . $count . '/' . $total_provinces . '</strong></td>';
        }

        echo '</tr>
        </tfoot>
        </table>';
    } else {
        echo '<div class="alert alert-warning" role="alert">No band data available. Debug: waip_array_bands=' . (isset($waip_array_bands) ? 'set' : 'not set') . ', worked_bands=' . (isset($worked_bands) ? 'set' : 'not set') . '</div>';
    }
?>
        </div>
    </div>

</div>
</div>

<?php
} else {
    echo '<div class="alert alert-danger" role="alert">' . __("No QSOs found matching the criteria for the WAIP award!") . '</div>';
}
?>
