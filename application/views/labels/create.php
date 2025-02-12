<div id="qsl_card_labels_container" class="container">

<br>
	<?php if($this->session->flashdata('message')) { ?>
		<!-- Display Message -->
		<div class="alert-message error">
		  <p><?php echo $this->session->flashdata('message'); ?></p>
		</div>
	<?php } ?>

<?php echo validation_errors('<div class="alert alert-danger" role="alert">','</div>'); ?>

<form method="post" action="<?php echo site_url('labels/create'); ?>" name="create_label_type">

	<div class="card">
		<h2 class="card-header"><?php echo $page_title; ?></h2>

		<div class="card-body">

			<!-- Label Name Input -->
	    	<div class="mb-3">
			    <label for="LabelName"><?= __("Label Name"); ?></label>
			    <input name="label_name" type="text" class="form-control" id="LabelName" aria-describedby="label_nameHelp" placeholder="Code 925041 6x3 Generic Label Sheet">
			    <small id="label_nameHelp" class="form-text text-muted"><?= __("Label name used for display purposes, so pick something meaningful, perhaps the label style."); ?></small>
			</div>

			<div class="mb-3 row">
    			<label class="col-sm-2 col-form-label" for="paperType_id"><?= __("Paper Type"); ?></label>
			    <div class="col-sm-4">
				    <select name="paper_type_id" class="form-select" id="paperType_id">
						<?php
							foreach($papertypes as $paper){
								echo '<option value=' . $paper->paper_id . '>' . ucwords(strtolower(($paper->paper_name))) . '</option>';
							}
						?>
					</select>
			    </div>

    			<label class="col-sm-2 col-form-label" for="measurementType"><?= __("Measurement used"); ?></label>
			    <div class="col-sm-4">
				    <select name="measurementType" class="form-select" id="measurementType">
						<option value="mm"><?= __("Millimeters"); ?></option>
						<option value="in"><?= __("Inches"); ?></option>
					</select>
			    </div>
  			</div>

			<div class="mb-3 row">
    			<label class="col-sm-2 col-form-label" for="marginTop"><?= __("Margin Top"); ?></label>
			    <div class="col-sm-4">
				    <input name="marginTop" type="text" class="form-control" pattern="[0-9]+([\.,][0-9]+)?" step="0.01" required id="marginTop" aria-describedby="marginTopHelp">
			    	<small id="marginTopHelp" class="form-text text-muted"><?= __("Top margin of labels"); ?></small>
			    </div>

    			<label class="col-sm-2 col-form-label" for="marginLeft"><?= __("Margin Left"); ?></label>
			    <div class="col-sm-4">
				    <input name="marginLeft" type="text" class="form-control" id="marginLeft" pattern="[0-9]+([\.,][0-9]+)?" step="0.01" required aria-describedby="marginLeftHelp">
			    	<small id="marginLeftHelp" class="form-text text-muted"><?= __("Left margin of labels."); ?></small>
			    </div>
  			</div>

  			<div class="mb-3 row">
    			<label class="col-sm-2 col-form-label" for="NX"><?= __("Labels horizontally"); ?></label>
			    <div class="col-sm-4">
				    <input name="NX" type="number" min="1" max="40" step="1" required class="form-control" id="NX" aria-describedby="NXHelp">
			    	<small id="NXHelp" class="form-text text-muted"><?= __("Number of labels horizontally across the page."); ?></small>
			    </div>

    			<label class="col-sm-2 col-form-label" for="NY"><?= __("Labels vertically"); ?></label>
			    <div class="col-sm-4">
				    <input name="NY" type="number" min="1" max="40" step="1" required class="form-control" id="NY" aria-describedby="NYHelp">
			    	<small id="NYHelp" class="form-text text-muted"><?= __("Number of labels vertically across the page."); ?></small>
			    </div>
  			</div>

  			<div class="mb-3 row">
    			<label class="col-sm-2 col-form-label" for="SpaceX"><?= __("Horizontal space"); ?></label>
			    <div class="col-sm-4">
				    <input name="SpaceX" type="text" pattern="[0-9]+([\.,][0-9]+)?" step="0.01" required class="form-control" id="SpaceX">
					<small id="NYHelp" class="form-text text-muted"><?= __("Horizontal space between 2 labels."); ?></small>
			    </div>

    			<label class="col-sm-2 col-form-label" for="SpaceY"><?= __("Vertical space"); ?></label>
			    <div class="col-sm-4">
				    <input name="SpaceY" type="text" pattern="[0-9]+([\.,][0-9]+)?" step="0.01" required class="form-control" id="SpaceY">
					<small id="NYHelp" class="form-text text-muted"><?= __("Vertical space between 2 labels."); ?></small>
			    </div>
  			</div>

			<div class="mb-3 row">
    			<label class="col-sm-2 col-form-label" for="width"><?= __("Width of label"); ?></label>
			    <div class="col-sm-4">
				    <input name="width" type="text" class="form-control" pattern="[0-9]+([\.,][0-9]+)?" step="0.01" required id="width" aria-describedby="widthHelp">
			    	<small id="widthHelp" class="form-text text-muted"><?= __("Total width of one label."); ?></small>
			    </div>

    			<label class="col-sm-2 col-form-label" for="height"><?= __("Height of label"); ?></label>
			    <div class="col-sm-4">
				    <input name="height" type="text" class="form-control" pattern="[0-9]+([\.,][0-9]+)?" step="0.01" required id="height" aria-describedby="heightHelp">
			    	<small id="heightHelp" class="form-text text-muted"><?= __("Total height of one label"); ?></small>
			    </div>
  			</div>

  			<div class="mb-3 row">
    			<label class="col-sm-2 col-form-label" for="font_size"><?= __("Font Size"); ?></label>
			    <div class="col-sm-4">
				    <input name="font_size" type="number" min="1" max="40" step="1" required class="form-control" id="font_size" value="8" aria-describedby="font_sizeHelp">
			    	<small id="font_sizeHelp" class="form-text text-muted"><?= __("Font size used on the label don't go too big."); ?></small>
			    </div>

    			<label class="col-sm-2 col-form-label" for="font_size"><?= __("QSOs on label"); ?></label>
			    <div class="col-sm-4">
				    <input name="label_qsos" type="number" min="1" max="40" step="1" required class="form-control" id="font_size" value="5" aria-describedby="font_sizeHelp">
			    </div>
  			</div>

  			<button type="submit" class="btn btn-primary"><i class="fas fa-plus-square"></i> <?= __("Save Label Type"); ?></button>
		</div>
	</div>

</form>

</div>
<br>
