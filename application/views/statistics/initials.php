<div class="container">
    <h1><?php echo $page_title; ?></h1>

	<?php if ($worked_bands) { ?>
		<form>
			<!-- Select Basic -->
					<div class="mb-3 d-flex align-items-center row">
						<label class="w-auto control-label" for="band"><?= __("Band") ?></label>
						<div class="w-auto">
							<select id="band" name="band" class="form-select form-select-sm">
								<?php foreach($worked_bands as $band) {
									echo '<option value="' . $band . '">' . $band . '</option>'."\n";
								} ?>
							</select>
						</div>

						<label class="w-auto control-label" for="mode"><?= __("Mode") ?></label>
						<div class="w-auto">
							<select id="mode" name="mode" class="form-select form-select-sm">
								<option value="All" <?php if ($this->input->post('mode') == "All" || $this->input->method() !== 'post') echo ' selected'; ?> ><?= __("All") ?></option>
								<?php
								foreach($modes as $mode){
										echo '<option value="' . $mode . '">' . $mode . '</option>'."\n";
								}
								?>
							</select>
						</div>
					<label class="w-auto control-label" for="button1id"></label>
					<div class="w-auto">
						<button onclick="showinitials();" type="button" name="button1id" class="btn btn-sm btn-primary"><?= __("Show") ?></button>
					</div>
				</div>
		</form>
		<div class="resulttable"></div>
	<?php } else {
		echo __("No EME QSOs were found.");
	} ?>

</div>
