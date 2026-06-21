<div class="container px-3 px-lg-4 mt-3 mb-3">

	<h2><?php echo $page_title; ?></h2>

	<div class="card">
	  <div class="card-header">
	    <?= __("View QSO Confirmations"); ?> - <?= __("A maximum of 1000 rows are shown in the table. This is for performance reasons."); ?>
	  </div>
	  <div class="card-body">

<div class="d-flex mt-2">
	<form class="form confirmationform">
		<div class="row mb-2">
			<label class="col-md-1 w-auto" for="confirmationtype"><?= __("Confirmation type") ?></label>
			<div class="col-sm-4 w-auto">
				<select class="form-select form-select-sm w-auto" id="confirmationtype" multiple="multiple">
					<option value="lotw" <?php if (isset($confirmation_type) && $confirmation_type === 'lotw') { echo "selected"; } elseif (isset($user_default_confirmation) && strpos($user_default_confirmation, 'L') !== false) { echo "selected"; } ?>>LoTW</option>
					<option value="qsl" <?php if (isset($confirmation_type) && $confirmation_type === 'qsl') { echo "selected"; } elseif (isset($user_default_confirmation) && strpos($user_default_confirmation, 'Q') !== false) { echo "selected"; } ?>>QSL card</option>
					<option value="eqsl" <?php if (isset($confirmation_type) && $confirmation_type === 'eqsl') { echo "selected"; } elseif (isset($user_default_confirmation) && strpos($user_default_confirmation, 'E') !== false) { echo "selected"; } ?>>eQSL</option>
					<option value="qrz" <?php if (isset($confirmation_type) && $confirmation_type === 'qrz') { echo "selected"; } elseif (isset($user_default_confirmation) && strpos($user_default_confirmation, 'Z') !== false) { echo "selected"; } ?>>QRZ.com</option>
					<option value="clublog" <?php if (isset($confirmation_type) && $confirmation_type === 'clublog') { echo "selected"; } elseif (isset($user_default_confirmation) && strpos($user_default_confirmation, 'C') !== false) { echo "selected"; } ?>>Clublog</option>
				</select>
			</div>
			<button id="confirmationbutton" type="button" name="plot" class="w-auto btn btn-sm btn-primary me-1 mb-1 ld-ext-right ld-ext-right-plot" onclick="getConfirmations(this.form,'false')"><?= __("Show"); ?><div class="ld ld-ring ld-spin"></div></button>
		</div>
	</form>
</div>
<div id="searchresult"></div>


	  </div>
	</div>
</div>
