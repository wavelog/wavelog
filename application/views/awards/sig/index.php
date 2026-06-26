<div class="container px-3 px-lg-4 mt-3 mb-3">
    <!-- Award Info Box -->
    <div id="awardInfoButton">
        <script>
            let lang_awards_info_button = "<?= __("Award Info"); ?>";
            let lang_award_info_ln1 = "<?= __("SIG Information"); ?>";
            let lang_award_info_ln2 = "<?= __("The SIG or Special Interest Group Category provides the possibility to use any kind of 'Special Interest Group Award' for awards that are not implemented in Wavelog."); ?>";
            let lang_award_info_ln3 = "<?= __("The reason for this is that the common ADIF format provides only a few dedicated fields for certain awards. SIG still makes it possible to use and evaluate all other types of markers for special interest groups."); ?>";
            let lang_award_info_ln4 = "<?= __("In the QSO processing, you will find two fields: 'SIG' contains the abbreviation of the name of the special interest group which is also visible in the award evaluation, and 'SIG INFO,' which contains the actual reference(s). Both fields are freely customizable."); ?>";
        </script>
        <h2><?php echo $page_title; ?></h2>
        <button type="button" class="btn btn-sm btn-primary me-1" id="displayAwardInfo"><?= __("Award Info"); ?></button>
    </div>
    <!-- End of Award Info Box -->

    <div class="card">
		<div class="card-header">
			<?= __("View progress of SIG awards"); ?>
		</div>
        <div class="card-body">
<?php if ($sig_types) { ?>
            <table style="width:100%" class="table table-sm table-bordered table-hover table-striped table-condensed text-center">
                <thead>
                    <tr>
                        <th><?= __("Special Interest Group"); ?></th>
                        <th><?= __("Number of QSOs"); ?></th>
                        <th><?= __("Number of Refs"); ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($sig_types as $row) { ?>
                    <tr>
                        <td><?php echo $row->col_sig; ?></td>
                        <td><a href='sig_details?type="<?php echo $row->col_sig; ?>"'><?php echo $row->qsos; ?></a></td>
                        <td><a href='sig_details?type="<?php echo $row->col_sig; ?>"'><?php echo $row->refs; ?></a></td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
<?php }
else {
    echo '<div class="alert alert-danger" role="alert">' . __("Nothing found!") . '</div>';
}
?>
        </div>
    </div>
</div>
