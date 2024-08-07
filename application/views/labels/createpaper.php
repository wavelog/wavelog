<div id="qsl_card_labels_container" class="container">

<br>
	<?php if($this->session->flashdata('message')) { ?>
		<!-- Display Message -->
		<div class="alert alert-success" role="alert">
			<?php echo $this->session->flashdata('message'); ?>
		</div>
	<?php } ?>

	<?php if($this->session->flashdata('error')) { ?>
		<!-- Display Message -->
		<div class="alert alert-danger" role="alert">
			<?php echo $this->session->flashdata('error'); ?>
		</div>
	<?php } ?>

	<?php if($this->session->flashdata('warning')) { ?>
		<!-- Display Message -->
		<div class="alert alert-warning" role="alert">
			<?php echo $this->session->flashdata('warning'); ?>
		</div>
	<?php } ?>

<?php echo validation_errors(); ?>

<form method="post" action="<?php echo site_url('labels/createpaper'); ?>" name="create_paper_type">

	<div class="card">
		<h2 class="card-header"><?php echo $page_title; ?></h2>

		<div class="card-body">

			<!-- Label Name Input -->
	    	<div class="mb-3 row">
			    <label class= "col-sm-2 col-form-label" for="PaperName"><?= __("Paper Type Name"); ?></label>
				<div class="col-sm-4">
					<input name="paper_name" type="text" class="form-control" id="PaperName" aria-describedby="paper_nameHelp">
					<small id="paper_nameHelp" class="form-text text-muted"><?= __("Paper name used for display purposes, so pick something meaningful."); ?></small>
	</div>
					<label class="col-sm-2 col-form-label" for="measurementType"><?= __("Measurement used"); ?></label>
					<div class="col-sm-4">
						<select name="measurementType" class="form-select" id="measurementType">
							<option selected value="mm"><?= __("Millimeters"); ?></option>
							<option value="in"><?= __("Inches"); ?></option>
						</select>
					</div>
  			</div>

			<div class="mb-3 row">
    			<label class="col-sm-2 col-form-label" for="width"><?= __("Width of paper"); ?></label>
			    <div class="col-sm-4">
				    <input name="width" type="text" class="form-control" pattern="[0-9]+([\.,][0-9]+)?" required step="0.01" id="width" aria-describedby="widthHelp">
			    	<small id="widthHelp" class="form-text text-muted"><?= __("Total width of paper."); ?></small>
			    </div>

    			<label class="col-sm-2 col-form-label" for="height"><?= __("Height of paper"); ?></label>
			    <div class="col-sm-4">
				    <input name="height" type="text" pattern="[0-9]+([\.,][0-9]+)?" step="0.01" required class="form-control" id="height" aria-describedby="heightHelp">
			    	<small id="heightHelp" class="form-text text-muted"><?= __("Total height of paper"); ?></small>
			    </div>
			</div>

			<div class="mb-3 row">
    			<label class="col-sm-2 col-form-label" for="orientation"><?= __("Orientation of paper"); ?></label>
			    <div class="col-sm-4">
				    <select name="orientation" class="form-select" id="orientation">
					<option value="L"><?= _pgettext("Orientation", "Landscape"); ?></option>
					<option value="P"><?= _pgettext("Orientation", "Portrait"); ?></option>
				    </select>
			    	<small id="heightHelp" class="form-text text-muted"><?= __("Orientation of paper"); ?></small>
			    </div>
  			</div>

  			<button type="submit" class="btn btn-primary"><i class="fas fa-plus-square"></i> <?= __("Save Paper Type"); ?></button>
		</div>
	</div>

</form>

</div>
<br>
