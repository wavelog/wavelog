<div class="container">
        <!-- Award Info Box -->
        <br>
        <div id="awardInfoButton">
            <script>
            var lang_awards_info_button = "<?php echo __("Award Info"); ?>";
            var lang_award_info_ln1 = "<?php echo __("SIG Information"); ?>";
            var lang_award_info_ln2 = "<?php echo __("The SIG or Signature Category provides the possibility to use any kind of 'Award Signature' for awards that are not implemented in Wavelog."); ?>";
            var lang_award_info_ln3 = "<?php echo __("The reason for this is that the common ADIF format provides only a few dedicated fields for certain awards. SIG still makes it possible to use and evaluate all other types of signature markers."); ?>";
            var lang_award_info_ln4 = "<?php echo __("In the QSO processing, you will find two fields: 'SIG' contains the actual marker, which is also visible in the award evaluation, and 'SIG INFO,' which contains a description of the signature. Both fields are freely customizable."); ?>";
            </script>
            <h2><?php echo $page_title; ?></h2>
            <button type="button" class="btn btn-sm btn-primary me-1" id="displayAwardInfo"><?php echo __("Award Info"); ?></button>
        </div>
        <!-- End of Award Info Box -->

<?php if ($sig_types) { ?>
    <table style="width:100%" class="table-sm table table-bordered table-hover table-striped table-condensed text-center">

	<tr>
		<td>Award Type</td>
        <td># QSOs</td>
        <td># Refs</td>
    </tr>

    <?php
    foreach ($sig_types as $row) {
	?>

    <tr>
		<td>
			<?php echo $row->col_sig; ?>
		</td>
        <td>
            <a href='sig_details?type="<?php echo $row->col_sig; ?>"'><?php echo $row->qsos; ?></a>
        </td>
        <td>
            <a href='sig_details?type="<?php echo $row->col_sig; ?>"'><?php echo $row->refs; ?></a>
        </td>
	</tr>
    <?php } ?>
	</table>
<?php }
else {
    echo '<div class="alert alert-danger" role="alert">Nothing found!</div>';
}
?>
</div>