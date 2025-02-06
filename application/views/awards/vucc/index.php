<div class="container">
  <!-- Award Info Box -->
  <br>
  <div id="awardInfoButton">
    <script>
      var lang_awards_info_button = "<?= __("Award Info"); ?>";
      var lang_award_info_ln1 = "<?= __("VUCC - VHF/UHF Century Club Award"); ?>";
      var lang_award_info_ln2 = "<?= __("The VHF/UHF Century Club Award is given for a minimum number of worked and confirmed gridsquares on a desired band."); ?>";
      var lang_award_info_ln3 = "<?= sprintf(__("Official information and the rules can be found in this document: %s."), "<a href='https://www.arrl.org/vucc' target='_blank'>https://www.arrl.org/vucc</a>"); ?>";
      var lang_award_info_ln4 = "<?= __("Only VHF/UHF bands are relevant."); ?>";
    </script>
    <h2><?php echo $page_title; ?></h2>
    <button type="button" class="btn btn-sm btn-primary me-1" id="displayAwardInfo"><?= __("Award Info"); ?></button>
  </div>
  <!-- End of Award Info Box -->
<?php if (!empty($vucc_array)) { ?>

        <table class="table table-sm table-hover table-striped table-condensed text-center">
            <thead>
            <tr>
                <td><?= __("Band"); ?></td>
                <td><?= __("Grids Worked"); ?></td>
                <td><?= __("Grids Confirmed"); ?></td>
            </tr>
            </thead>
            <tbody>
                <?php foreach($vucc_array as $band => $vucc) {
				echo '<tr>';
				echo '<td><a href=\'vucc_band?Band="'. $band . '"\'>'. $band .'</td>';
				echo '<td><a href=\'vucc_band?Band="'. $band . '"&Type="worked"\'>'. $vucc['worked'] .'</td>';
				echo '<td><a href=\'vucc_band?Band="'. $band . '"&Type="confirmed"\'>'. $vucc['confirmed'] .'</td>';
				echo '</tr>';
                }
                ?>
            </tbody>
        </table>

        <?php } else {
            echo '<div class="alert alert-danger" role="alert">' . __("Nothing found!") . '</div>';
        } ?>
</div>
