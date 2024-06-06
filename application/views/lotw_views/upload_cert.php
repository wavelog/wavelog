<div class="container lotw">

	<h2><?php echo __("Logbook of the World"); ?></h2>

	<!-- Card Starts -->
	<div class="card">
		<div class="card-header">
			<?php echo __("Upload Logbook of the World .p12 Certificate"); ?>
		</div>

		<div class="card-body">
			<?php if($error != " ") { ?>
				<div class="alert alert-danger" role="alert">
			  	<?php echo $error; ?>
				</div>
	    	<?php } ?>

	    	<div class="alert alert-info" role="alert">
		    	<h5><?php echo __("Export .p12 File Instructions"); ?></h5>

		    	<ul>
		    		<li><?php echo __("Open TQSL &amp; go to the Callsign Certificates Tab"); ?></li>
		    		<li><?php echo __("Right click on desired Callsign"); ?></li>
		    		<li><?php echo __("Click "Save Callsign Certificate File" and do not add a password"); ?></li>
		    		<li><?php echo __("Upload File below."); ?></li>
		    	</ul>
	    	</div>

			<?php echo form_open_multipart('lotw/do_cert_upload');?>
				<div class="mb-3">
				    <label for="exampleFormControlFile1"><?php echo __("Upload Logbook of the World .p12 Certificate"); ?></label>
				    <input type="file" name="userfile" class="form-control" id="exampleFormControlFile1">
				 </div>

				<button type="submit" value="upload" class="btn btn-primary"><?php echo __("Upload File"); ?></button>
			</form>

	    </div>
	</div>
	<!-- Card Ends -->

</div>
