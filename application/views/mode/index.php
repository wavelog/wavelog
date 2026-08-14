<script>
	var lang_create_mode = "<?= __("Create mode"); ?>";
	var lang_mode_deletion_confirm = "<?= __("Warning! Are you sure you want to delete the following mode?:"); ?>";
	var lang_active_all_confirm = "<?= __("Warning! Are you sure you want to activate all modes?"); ?>";
	var lang_deactive_all_confirm = "<?= __("Warning! Are you sure you want to deactivate all modes?"); ?>";
	var lang_deactivate_mode = "<?= __("Deactivate"); ?>";
	var lang_activate_mode = "<?= __("Activate"); ?>";
	var lang_mode_active = "<?= __("Active"); ?>";
	var lang_mode_not_active = "<?= __("Not active"); ?>";
</script>


<div class="container">

<br>
	<?php if($this->session->flashdata('message')) { ?>
		<!-- Display Message -->
		<div class="alert-message error">
		  <p><?php echo $this->session->flashdata('message'); ?></p>
		</div>
	<?php } ?>

<h2><?php echo $page_title; ?></h2>

<div class="card">
  <div class="card-header">
    <?= __("Modes"); ?>
  </div>
  <div class="card-body">
    <p class="card-text">
		<?= __("Using the modes list you can control which modes are shown when creating a new QSO."); ?>
	</p>
    <p class="card-text">
		<?= __("Active modes will be shown in the QSO 'Mode' drop-down, while inactive modes will be hidden and cannot be selected."); ?>
	</p>
    <div class="table-responsive">
		<table style="width:100%" class="modetable table table-striped">
			<thead>
				<tr>
					<th class="select-filter" scope="col"><?= __("Mode"); ?></th>
					<th class="select-filter" scope="col"><?= __("Sub-Mode"); ?></th>
					<th class="select-filter" scope="col">SSB / DATA / CW</th>
					<th class="select-filter" scope="col"><?= __("Status"); ?></th>
                    <th scope="col"></th>
					<th scope="col"></th>
					<th scope="col"></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($modes->result() as $row) { ?>
				<tr>
					<td style="text-align: center; vertical-align: middle;" ><?php echo $row->mode;?></td>
					<td style="text-align: center; vertical-align: middle;" ><?php echo $row->submode;?></td>
					<td style="text-align: center; vertical-align: middle;" ><?php echo $row->qrgmode;?></td>
                    <td style="text-align: center; vertical-align: middle;"  class='mode_<?php echo $row->id ?>'><?php if ($row->active == 1) { echo __("Active");} else { echo __("Not active");};?></td>
                    <td style="text-align: center; vertical-align: middle;"  style="text-align: center">
                        <?php if ($row->active == 1) {
                            echo "<button onclick='javascript:deactivateMode(". $row->id . ")' class='btn_" . $row->id . " btn btn-secondary btn-sm'>" . __("Deactivate") . "</button>";
                        } else {
                            echo "<button onclick='javascript:activateMode(". $row->id . ")' class='btn_" . $row->id . " btn btn-primary btn-sm'>" . __("Activate") . "</button>";
                        };?>
                    </td>
					<td style="text-align: center; vertical-align: middle;" >
						<a href="<?php echo site_url('mode/edit')."/".$row->id; ?>" class="btn btn-outline-primary btn-sm"><i class="fas fa-edit"></i> <?= __("Edit"); ?></a>
					</td>
					<td style="text-align: center; vertical-align: middle;" >
						<a href="javascript:deleteMode('<?php echo $row->id; ?>', '<?php echo $row->mode; ?>');" class="btn btn-danger btn-sm" ><i class="fas fa-trash-alt"></i> <?= __("Delete"); ?></a>
                    </td>
				</tr>

				<?php } ?>
			</tbody>
			<tfoot>
				<tr>
					<th></th>
					<th></th>
					<th></th>
					<th></th>
					<th><---</th>
					<th><?= __("Filters"); ?></th>
					<th></th>
				</tr>
			</tfoot>
		<table>
	</div>
  <br/>
  <p>
	  	<button onclick="createModeDialog();" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> <?= __("Create a Mode"); ?></button>
  		<button onclick="activateAllModes();" class="btn btn-primary btn-sm"><?= __("Activate All"); ?></button>
		<button onclick="deactivateAllModes();" class="btn btn-primary btn-sm"><?= __("Deactivate All"); ?> </button>
	</p>
</div>
</div>
