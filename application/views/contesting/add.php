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
		<div class="card-body">
			<p class="card-text">
				<?= __("Using the contest list, you can control which Contests are shown when logging QSOs in a contest."); ?>
			</p>
			<p class="card-text">
				<?= __("Active contests will be shown in the Contest Name drop-down, while inactive contests will be hidden and cannot be selected."); ?>
			</p>
			<div class="table-responsive">
				<table style="width:100%" class="contesttable table table-sm table-striped">
					<thead>
					<tr>
						<th scope="col"><?= __("Name"); ?></th>
						<th scope="col"><?= __("ADIF Name"); ?></th>
						<th scope="col"><?= __("Active"); ?></th>
						<th scope="col"></th>
						<th scope="col"></th>
						<th scope="col"></th>
					</tr>
					</thead>
					<tbody>
					<?php foreach ($contests as $row) { ?>
						<tr>
							<td><?php echo $row['name'];?></td>
							<td><?php echo $row['adifname'];?></td>
							<script>
								var lang_admin_contest_menu_n_active = '<?= __("Not Active"); ?>';
								var lang_admin_contest_menu_activate = '<?= __("Activate"); ?>';
								var lang_admin_contest_menu_active = '<?= __("Active"); ?>';
								var lang_admin_contest_menu_deactivate = '<?= __("Deactivate"); ?>';
							</script>
							<td class='contest_<?php echo $row['id'] ?>'><?php if ($row['active'] == 1) { echo __("Active");} else { echo __("Not Active");};?></td>
							<td style="text-align: center">
								<?php if ($row['active'] == 1) {
									echo "<button onclick='javascript:deactivateContest(". $row['id'] . ")' class='btn_" . $row['id'] . " btn btn-secondary btn-sm'>" . __("Deactivate") . "</button>";
								} else {
									echo "<button onclick='javascript:activateContest(". $row['id'] . ")' class='btn_" . $row['id'] . " btn btn-secondary btn-sm'>" . __("Activate") . "</button>";
								};?>
							</td>
							<td>
								<script>
									var lang_admin_danger = '<?= __("DANGER!"); ?>';
									var lang_admin_contest_deletion_warning = '<?= __("Warning! Are you sure you want to delete the following contest: "); ?>';
									var lang_admin_contest_active_all_warning = '<?= __("Warning! Are you sure you want to activate all contests?"); ?>';
									var lang_admin_contest_deactive_all_warning = '<?= __("Warning! Are you sure you want to deactivate all contests?"); ?>';
								</script>
								<a href="<?php echo site_url('contesting/edit')."/".$row['id']; ?>" class="btn btn-outline-primary btn-sm"><i class="fas fa-edit"></i> <?= __("Edit"); ?></a>
							</td>
							<td>
								<a href="javascript:deleteContest('<?php echo $row['id']; ?>', '<?php echo $row['name']; ?>');" class="btn btn-danger btn-sm" ><i class="fas fa-trash-alt"></i> <?= __("Delete"); ?></a>
							</td>
						</tr>

					<?php } ?>
					</tbody>
					<table>
			</div>
			<br/>
			<p>
				<script>
					var lang_admin_contest_add_contest = '<?= __("Add a Contest"); ?>';
					var lang_admin_close = '<?= __("Close"); ?>'
				</script>
				<button onclick="createContestDialog();" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> <?= __("Add a Contest"); ?></button>
				<button onclick="activateAllContests();" class="btn btn-primary btn-sm"><?= __("Activate All"); ?></button>
				<button onclick="deactivateAllContests();" class="btn btn-primary btn-sm"><?= __("Deactivate All"); ?></button>
			</p>
		</div>
	</div>
