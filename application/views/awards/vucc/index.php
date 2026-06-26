<div class="container px-3 px-lg-4 mt-3 mb-3">
    <!-- Award Info Box -->
    <div id="awardInfoButton">
        <script>
            let lang_awards_info_button = "<?= __("Award Info"); ?>";
            let lang_award_info_ln1 = "<?= __("VUCC - VHF/UHF Century Club Award"); ?>";
            let lang_award_info_ln2 = "<?= __("The VHF/UHF Century Club Award is given for a minimum number of worked and confirmed gridsquares on a desired band."); ?>";
            let lang_award_info_ln3 = "<?= sprintf(__("Official information and the rules can be found in this document: %s."), "<a href='https://www.arrl.org/vucc' target='_blank'>https://www.arrl.org/vucc</a>"); ?>";
            let lang_award_info_ln4 = "<?= __("Only VHF/UHF bands are relevant."); ?>";
            let lang_award_info_ln5 = "<?= __("Fields taken for this Award: Gridsquare and vucc_grids (ADIF: GRIDSQUARE, VUCC_GRIDS)"); ?>";
        </script>
        <h2><?php echo $page_title; ?></h2>
        <button type="button" class="btn btn-sm btn-primary me-1" id="displayAwardInfo"><?= __("Award Info"); ?></button>
    </div>
    <!-- End of Award Info Box -->

    <div class="card">
        <div class="card-header">
            <?= __("VUCC Gridsquares by Band"); ?>
        </div>
        <div class="card-body">
        <?php if (!empty($vucc_array)) { ?>
            <table class="table table-sm table-bordered table-hover table-striped table-condensed text-center">
                <thead>
                    <tr>
                        <th><?= __("Band"); ?></th>
                        <th><?= __("Grids Worked"); ?></th>
                        <th><?= __("Grids Confirmed"); ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach($vucc_array as $band => $vucc) {
                    echo '<tr>';
                    echo '<td><a href=\'vucc_band?Band="'. $band . '"\'>'. $band .'</a></td>';
                    echo '<td><a href=\'vucc_band?Band="'. $band . '"&Type="worked"\'>'. $vucc['worked'] .'</a></td>';
                    echo '<td><a href=\'vucc_band?Band="'. $band . '"&Type="confirmed"\'>'. $vucc['confirmed'] .'</a></td>';
                    echo '</tr>';
                }
                ?>
                </tbody>
            </table>
        <?php } else {
            echo '<div class="alert alert-danger" role="alert">' . __("Nothing found!") . '</div>';
        } ?>
        </div>
    </div>
</div>
