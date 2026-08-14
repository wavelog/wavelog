<div class="container">

	<br>
	<?php if ($this->session->flashdata('danger')) { ?>
		<!-- Display Message -->
		<div class="alert alert-danger">
			<p><?php echo $this->session->flashdata('danger'); ?></p>
		</div>
	<?php } ?>

	<?php if ($this->session->flashdata('success')) { ?>
		<!-- Display Message -->
		<div class="alert alert-success">
			<p><?php echo $this->session->flashdata('success'); ?></p>
		</div>
	<?php } ?>

	<h2><?php echo $page_title; ?></h2>

	<div class="card">
		<div class="card-header">
			<?= __("Themes list"); ?>
		</div>
		<div class="card-body">
			<div class="card-text border-bottom mb-2 mt-2">
				<div class="row">
					<div class="col">
						<p><?= __("In this menu you can add and edit themes to Wavelog. The process of adding a theme is not really straightforward and should only be done by an admin who is aware of their actions."); ?><br>
							<?= __("Also, it has to be said that this feature is still under development."); ?><br></p>
					</div>
				</div>

				<div class="row">
					<div class="col">
						<h5><?= __("Process of adding a new theme"); ?></h5>
					</div>
				</div>

				<div class="row">
					<div class="col-md-2">
						<h6><?= __("1. Step"); ?></h6>
					</div>
					<div class="col-md-10">
						<p><?= sprintf(__("Create a new folder in 'assets/css/' and upload a 'bootstrap.min.css' file which contains basic styling rules. We use the themes from %s"), '<a href="https://bootswatch.com/" target="_blank">https://bootswatch.com/</a>'); ?></p>
					</div>
				</div>

				<div class="row">
					<div class="col-md-2">
						<h6><?= __("2. Step"); ?></h6>
					</div>
					<div class="col-md-10">
						<p><?= __("Create a file in this folder called 'overrides.css'. Place your custom CSS code in there."); ?></p>
					</div>
				</div>

				<div class="row">
					<div class="col-md-2">
						<h6><?= __("3. Step"); ?></h6>
					</div>
					<div class="col-md-10">
						<p><?= __("For each new theme, you need two logo files. One for the login screen and one for the header."); ?><br>
							<?= __("Only PNG files are allowed, and they should have a pixel ratio of 1:1 (e.g., 1000px height and 1000px width)."); ?><br>
							<?= __("Place the two logo files in the folder 'assets/logo/'"); ?></p>
					</div>
				</div>

				<div class="row">
					<div class="col-md-2">
						<h6><?= __("4. Step"); ?></h6>
					</div>
					<div class="col-md-10">
						<p><?= sprintf(__("Click here on 'Add a Theme' and type in the necessary data. Type in the filenames for the logos %swithout%s the file extension '.png'"), '<u><b>', '</b></u>'); ?></p>
					</div>
				</div>
			</div>

			<div class="table-responsive">
				<table style="width:100%" class="contesttable table table-sm table-striped">
					<thead>
						<tr>
							<th scope="col"><?= __("Name"); ?></th>
							<th scope="col"><?= __("Foldername"); ?></th>
							<th scope="col"><?= __("Theme Mode"); ?></th>
							<th scope="col"><?= __("Header Logo"); ?></th>
							<th scope="col"><?= __("Main Logo"); ?></th>
							<th scope="col"></th>
							<th scope="col"></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($themes as $theme) { ?>
							<tr>
								<td><?php echo $theme->name; ?></td>
								<td><?php echo $theme->foldername; ?></td>
								<td><?php if ($theme->theme_mode != "") {
										echo $theme->theme_mode;
									} else {
										echo "<span class=\"text-danger\">Theme-Mode undefined! " . __("Please edit") . "</span>";
									} ?></td>
								<td><?php echo $theme->header_logo; ?></td>
								<td><?php echo $theme->main_logo; ?></td>
								<td>
									<a onclick="editThemeDialog('<?php echo $theme->id; ?>')" class="btn btn-outline-primary btn-sm"><i class="fas fa-edit"></i> <?= __("Edit"); ?></a>
								</td>
								<td class='theme_<?php echo $theme->id ?>'>
									<a href="javascript:deleteTheme('<?php echo $theme->id; ?>', '<?php echo $theme->name; ?>');" class="btn btn-danger btn-sm"><i class="fas fa-trash-alt"></i> <?= __("Delete"); ?></a>
								</td>
							</tr>

						<?php } ?>
					</tbody>
					<table>
			</div>
			<br />
			<p><button onclick="addThemeDialog();" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> <?= __("Add a Theme"); ?></button></p>
		</div>
	</div>