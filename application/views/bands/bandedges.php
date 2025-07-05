<div class="container">

	<br>
	<?php if($this->session->flashdata('message')) { ?>
		<!-- Display Message -->
		<div class="alert-message error">
			<p><?php echo $this->session->flashdata('message'); ?></p>
		</div>
	<?php } ?>

	<h2><?= __("Bandedges"); ?></h2>

	<div class="card">
		<div class="card-body">
			<p class="card-text">
				<?= __("Using the bandedges list you can control the mode classification in the cluster."); ?><br>
			</p>
			<div class="table-responsive">

				<table style="width:100%" class="bandtable table table-sm table-striped">
					<thead>
						<tr>
							<th><?= __("Frequency from (Hz)"); ?></th>
							<th><?= __("Frequency to (Hz)"); ?></th>
							<th><?= __("Mode"); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($bands as $band) { ?>
							<tr>
								<td style="text-align: center; vertical-align: middle;" ><?php echo $band->frequencyfrom;?></td>
								<td style="text-align: center; vertical-align: middle;" ><?php echo $band->frequencyto;?></td>
								<td style="text-align: center; vertical-align: middle;" ><?php echo $band->mode;?></td>
							</tr>

						<?php } ?>
					</tbody>
					<table>
			</div>
  			<br/>
			<p>
			</p>
		</div>
	</div>
</div>
