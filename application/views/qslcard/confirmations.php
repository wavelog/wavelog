<div class="container">

	<br>

	<h2><?php echo $page_title; ?></h2>

	<?php
	if ($this->session->userdata('user_date_format')) {
		// If Logged in and session exists
		$custom_date_format = $this->session->userdata('user_date_format');
	} else {
		// Get Default date format from /config/wavelog.php
		$custom_date_format = $this->config->item('qso_date_format');
	}
	?>

<div class="d-flex mt-2">
	<form class="form">
		<div class="row mb-2">
			<label class="col-md-1 w-auto" for="confirmationtype"><?= __("Confirmation type") ?></label>
			<div class="col-sm-4 w-auto">
				<select class="form-select form-select-sm w-auto" id="confirmationtype">
					<option value="All"><?= __("All") ?></option>
					<option value="lotw">LoTW</option>
					<option value="qsl">QSL card</option>
					<option value="eqsl">eQSL</option>
					<option value="qrz">QRZ.com</option>
					<option value="clublog">Clublog</option>
				</select>
			</div>
			<button id="confirmations" type="button" name="plot" class="w-auto btn btn-sm btn-primary me-1 mb-1 ld-ext-right ld-ext-right-plot" onclick="getConfirmations(this.form,'false')"><?= __("Show"); ?><div class="ld ld-ring ld-spin"></div></button>
		</div>
	</form>
</div>
<div id="searchresult"></div>

</div>
