<script>
     var lang_edge_invalid_number = "<?= __("Please enter valid numbers for frequency"); ?>";
     var lang_edge_from_gt_to = "<?= __("The 'From' frequency must be less than the 'To' frequency."); ?>";
     var lang_edge_overlap = "<?= __("The Frequency overlaps with an existing entry."); ?>";
     var lang_edge_remove = "<?= __("Are you sure you want to delete this band edge?"); ?>";
</script>
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

				<table id="bandtable" style="width:100%"class="bandtable table table-sm table-striped">
					<thead>
						<tr>
							<th><?= __("Frequency from (Hz)"); ?></th>
							<th><?= __("Frequency to (Hz)"); ?></th>
							<th><?= __("Mode"); ?></th>
							<th><?= __("Edit"); ?></th>
							<th><?= __("Delete"); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($bands as $band) { ?>
							<tr class="bandedge_<?php echo $band->id ?>">
								<td id="frequencyfrom_<?php echo $band->id ?>" class="row_data" style="text-align: center; vertical-align: middle;" ><?php echo $band->frequencyfrom;?></td>
								<td id="frequencyto_<?php echo $band->id ?>" class="row_data" style="text-align: center; vertical-align: middle;" ><?php echo $band->frequencyto;?></td>
								<?php switch ($band->mode) {
									case 'cw':
										$mode = 'CW';
										break;
									case 'phone':
										$mode = 'Phone';
										break;
									case 'digi':
										$mode = 'Digi';
										break;
									default:
										$mode = $band->mode;
										break;
								} ?>
								<td class="text-center" id="mode_<?php echo $band->id ?>" class="row_data" style="text-align: center; vertical-align: middle;" ><?php echo $mode; ?></td>
								<td id="editButton" style="text-align: center; vertical-align: middle;"><button onclick="editBandEdge(<?php echo $band->id ?>)" id="<?php echo $band->id ?>" class="btn btn-sm btn-success"><i class="fas fa-edit"></i></button></td>
								<td id="deleteButton" style="text-align: center; vertical-align: middle;"><button id="<?php echo $band->id; ?>" class="deleteBandEdge btn btn-sm btn-danger" onclick="deleteBandEdge(<?php echo $band->id ?>)"><i class="fas fa-trash-alt"></i></button></td>
							</tr>

						<?php } ?>
					</tbody>
					<table>
					<br />

					<button onclick="addBandEdgeRow();" class="addnewrowbutton btn btn-primary btn-sm"><i class="fas fa-plus"></i> <?= __("Add a bandedge"); ?></button>
			</div>
  			<br/>
			<p>
			</p>
		</div>
	</div>
</div>
