<?php if($radio_status->num_rows()) { ?>

<div class="card mb-3">
	<div class="card-header py-2">
		<h6 class="mb-0"><i class="fas fa-broadcast-tower"></i> <?= __("Radio Status"); ?></h6>
	</div>
	<div class="card-body p-0">

		<table class="table table-striped">
			<?php foreach ($radio_status->result_array() as $row) { ?>
			<tr>
				<td><?php echo $row['radio']; ?></td>
				<td>
					<?php if($row['prop_mode'] == 'SAT') { ?>
						<?php echo $row['sat_name']; ?>
					<?php } else { ?>
						<?php echo $this->frequency->qrg_conversion($row['frequency']); ?> (<?php echo $row['mode']; ?>)
					<?php } ?>
				</td>
			</tr>
			<?php } ?>
		</table>
	</div>
</div>

<?php } ?>
