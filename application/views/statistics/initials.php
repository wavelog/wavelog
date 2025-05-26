<div class="container">
    <h1><?php echo $page_title; ?></h1>

	<?php if ($worked_bands) { ?>
		<form>
			<!-- Select Basic -->
					<div class="mb-3 row">
						<label class="col-md-1 control-label" for="band"><?= __("Band") ?></label>
						<div class="col-md-3">
							<select id="band" name="band" class="form-select">
								<?php foreach($worked_bands as $band) {
									echo '<option value="' . $band . '"';
									if ($this->input->post('band') == $band) echo ' selected';
									echo '>' . $band . '</option>'."\n";
								} ?>
							</select>
						</div>

						<label class="col-md-1 control-label" for="mode"><?= __("Mode") ?></label>
						<div class="col-md-3">
							<select id="mode" name="mode" class="form-select">
								<option value="All" <?php if ($this->input->post('mode') == "All" || $this->input->method() !== 'post') echo ' selected'; ?> ><?= __("All") ?></option>
								<?php
								foreach($modes->result() as $mode){
									if ($mode->submode == null) {
										echo '<option value="' . $mode->mode . '"';
										if ($this->input->post('mode') == $mode->mode) echo ' selected';
										echo '>' . $mode->mode . '</option>'."\n";
									} else {
										echo '<option value="' . $mode->submode . '"';
										if ($this->input->post('mode') == $mode->submode) echo ' selected';
										echo '>' . $mode->submode . '</option>'."\n";
									}
								}
								?>
							</select>
						</div>
					</div>
					<div class="mb-3 row">
					<label class="col-md-1 control-label" for="button1id"></label>
					<div class="col-md-10">
						<button onclick="showinitials();" type="button" name="button1id" class="btn btn-primary"><?= __("Show") ?></button>
					</div>
				</div>
		</form>
		<div class="resulttable"></div>
	<?php } else {
		echo __("No EME QSO(s) was found.");
	} ?>

</div>
