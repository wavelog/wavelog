<div class="container" id="editCatUrl">

<br>
		<!-- Display Message -->
		<div id="flashdata" class="alert-message error">
		</div>

		<?php if($this->session->flashdata('notice')) { ?>
			<div id="message" >
			<?php echo $this->session->flashdata('notice'); ?>
			</div>
		<?php } ?>

		<?php $this->load->helper('form'); ?>
		<input type="hidden" id="catid" name="id" value="<?php echo $container->id; ?>">

		<div class="mb-3">
			<label for="CatUrlInput"><?= __("CAT URL"); ?></label>
			<input type="text" class="form-control" name="CatUrlInput" id="CatUrlInput" aria-describedby="CatUrlHelp" placeholder="http://127.0.0.1:54321" required value="<?php echo $container->cat_url; ?>">
			<small id="CatUrlHelp" class="form-text text-muted"><?= sprintf(__("Called URL when a spot at DXCluster is clicked. Notice: The trailing slash (/) and QRG is added automatically. Default is %s"), "http://127.0.0.1:54321"); ?></small>
		</div>


</div>
