<div class="container lotw">

	<h2><?= __("Logbook of the World"); ?></h2>

	<!-- Card Starts -->
	<div class="card">
		<div class="card-header">
			<?= __("Upload Logbook of the World .p12 Certificate"); ?>
		</div>

		<div class="card-body">
			<?php if($error != " ") { ?>
				<div class="alert alert-danger" role="alert">
			  	<?php echo $error; ?>
				</div>
			<?php } ?>

			<div class="alert alert-info" role="alert">
				<h5><?= __("Export .p12 File Instructions"); ?></h5>

				<ul>
					<li><b><?= __("Use at least version 2.7 of TQSL"); ?></b></li>
					<li><?= __("Open TQSL and go to the Callsign Certificates Tab"); ?></li>
					<li><?= __("Right click on desired Callsign"); ?></li>
					<li><?= __("Click 'Save Callsign Certificate File'"); ?></li>
					<li><b><?= __("Do not add a password"); ?></b></li>
					<li><?= __("Upload File below"); ?></li>
				</ul>
			</div>

			<?php echo form_open_multipart('lotw/do_cert_upload');?>
				<div class="mb-3">
					<label for="exampleFormControlFile1"><?= __("Upload Logbook of the World .p12 Certificate"); ?></label>
					<input type="file" name="userfile" class="form-control" id="exampleFormControlFile1">
				 </div>

				<button type="submit" value="upload" class="btn btn-primary"><?= __("Upload File"); ?></button>
			</form>

		</div>
	</div>
	<!-- Card Ends -->

</div>
