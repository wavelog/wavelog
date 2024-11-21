<?php
$cq = 0;
$dok = 0;
$dxcc = 0;
$helvetia = 0;
$iota = 0;
$jcc = 0;
$pota = 0;
$rac = 0;
$sig = 0;
$sota = 0;
$uscounties = 0;
$vucc = 0;
$waja = 0;
$was = 0;
$wwff = 0;
?>
<div class="container">

	<br>
	<?php if($this->session->flashdata('message')) { ?>
		<!-- Display Message -->
		<div class="alert-message error">
			<p><?php echo $this->session->flashdata('message'); ?></p>
		</div>
	<?php } ?>

	<h2><?= __("Bands"); ?></h2>

	<div class="card">
		<div class="card-header">
			<?= __("Bands"); ?>
		</div>
		<div class="card-body">
			<p class="card-text">
				<?= __("Using the band list you can control which bands are shown when creating a new QSO."); ?><br>
				<?= __("Active bands will be shown in the QSO 'Band' drop-down, while inactive bands will be hidden and cannot be selected."); ?>
			</p>
			<div class="table-responsive">

				<table style="width:100%" class="bandtable table table-sm table-striped">
					<thead>
						<tr>
							<th></th>
							<th><?= __("Band"); ?></th>
							<th><?= __("CQ"); ?></th>
							<th><?= __("DOK"); ?></th>
							<th><?= __("DXCC"); ?></th>
							<th><?= __("H26"); ?></th>
							<th><?= __("IOTA"); ?></th>
							<th><?= __("JCC"); ?></th>
							<th><?= __("POTA"); ?></th>
							<th><?= __("RAC"); ?></th>
							<th><?= __("Sig"); ?></th>
							<th><?= __("SOTA"); ?></th>
							<th><?= __("USA County"); ?></th>
							<th><?= __("VUCC"); ?></th>
							<th><?= __("WAJA"); ?></th>
							<th><?= __("WAS"); ?></th>
							<th><?= __("WWFF"); ?></th>
							<th><?= __("Bandgroup"); ?></th>
							<th><?= __("SSB QRG"); ?></th>
							<th><?= __("DATA QRG"); ?></th>
							<th><?= __("CW QRG"); ?></th>
							<th><?= __("QRG Unit"); ?></th>
							<?php if($this->session->userdata('user_type') == '99') { ?>
								<th></th>
								<th></th>
							<?php } ?>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($bands as $band) { ?>
							<tr>
								<td style="text-align: center; vertical-align: middle;" class='band_<?php echo $band->id ?>'><input type="checkbox" <?php if ($band->active == 1) {echo 'checked';}?>></td>
								<td style="text-align: center; vertical-align: middle;" ><?php echo $band->band;?></td>
								<td style="text-align: center; vertical-align: middle;" class='cq_<?php echo $band->id ?>'><input type="checkbox" <?php if ($band->cq == 1) {echo 'checked'; $cq++;}?>></td>
								<td style="text-align: center; vertical-align: middle;" class='dok_<?php echo $band->id ?>'><input type="checkbox" <?php if ($band->dok == 1) {echo 'checked'; $dok++;}?>></td>
								<td style="text-align: center; vertical-align: middle;" class='dxcc_<?php echo $band->id ?>'><input type="checkbox" <?php if ($band->dxcc == 1) {echo 'checked'; $dxcc++;}?>></td>
								<td style="text-align: center; vertical-align: middle;" class='helvetia_<?php echo $band->id ?>'><input type="checkbox" <?php if ($band->helvetia == 1) {echo 'checked'; $helvetia++;}?>></td>
								<td style="text-align: center; vertical-align: middle;" class='iota_<?php echo $band->id ?>'><input type="checkbox" <?php if ($band->iota == 1) {echo 'checked'; $iota++;}?>></td>
								<td style="text-align: center; vertical-align: middle;" class='jcc_<?php echo $band->id ?>'><input type="checkbox" <?php if ($band->jcc == 1) {echo 'checked'; $jcc++;}?>></td>
								<td style="text-align: center; vertical-align: middle;" class='pota_<?php echo $band->id ?>'><input type="checkbox" <?php if ($band->pota == 1) {echo 'checked'; $pota++;}?>></td>
								<td style="text-align: center; vertical-align: middle;" class='rac_<?php echo $band->id ?>'><input type="checkbox" <?php if ($band->rac == 1) {echo 'checked'; $rac++;}?>></td>
								<td style="text-align: center; vertical-align: middle;" class='sig_<?php echo $band->id ?>'><input type="checkbox" <?php if ($band->sig == 1) {echo 'checked'; $sig++;}?>></td>
								<td style="text-align: center; vertical-align: middle;" class='sota_<?php echo $band->id ?>'><input type="checkbox" <?php if ($band->sota == 1) {echo 'checked'; $sota++;}?>></td>
								<td style="text-align: center; vertical-align: middle;" class='uscounties_<?php echo $band->id ?>'><input type="checkbox" <?php if ($band->uscounties == 1) {echo 'checked'; $uscounties++;}?>></td>
								<td style="text-align: center; vertical-align: middle;" class='vucc_<?php echo $band->id ?>'><input type="checkbox" <?php if ($band->vucc == 1) {echo 'checked'; $vucc++;}?>></td>
								<td style="text-align: center; vertical-align: middle;" class='waja_<?php echo $band->id ?>'><input type="checkbox" <?php if ($band->waja == 1) {echo 'checked'; $waja++;}?>></td>
								<td style="text-align: center; vertical-align: middle;" class='was_<?php echo $band->id ?>'><input type="checkbox" <?php if ($band->was == 1) {echo 'checked'; $was++;}?>></td>
								<td style="text-align: center; vertical-align: middle;" class='wwff_<?php echo $band->id ?>'><input type="checkbox" <?php if ($band->wwff == 1) {echo 'checked'; $wwff++;}?>></td>
								<td style="text-align: center; vertical-align: middle;" ><?php echo $band->bandgroup;?></td>
								<td style="text-align: center; vertical-align: middle;" ><?php echo $band->ssb;?></td>
								<td style="text-align: center; vertical-align: middle;" ><?php echo $band->data;?></td>
								<td style="text-align: center; vertical-align: middle;" ><?php echo $band->cw;?></td>
								<td style="text-align: center; vertical-align: middle;" class='band_<?php echo $band->bandid ?>'>
									<select style="min-width: 80px;" class="form-select unitselect" name="unit_<?php echo $band->id; ?>">
										<option value="Hz" <?php if ($this->frequency->qrg_unit($band->band) == 'Hz') { echo 'selected';} ?>><?= __("Hz"); ?></option>
										<option value="kHz" <?php if ($this->frequency->qrg_unit($band->band) == 'kHz') { echo 'selected';} ?>><?= __("kHz"); ?></option>
										<option value="MHz" <?php if ($this->frequency->qrg_unit($band->band) == 'MHz') { echo 'selected';} ?>><?= __("MHz"); ?></option>
										<option value="GHz" <?php if ($this->frequency->qrg_unit($band->band) == 'GHz') { echo 'selected';} ?>><?= __("GHz"); ?></option>
									</select>
								</td>
								<?php if($this->session->userdata('user_type') == '99') { ?>
									<td style="text-align: center; vertical-align: middle;" >
										<a href="javascript:editBandDialog('<?php echo $band->bandid ?>');" class="btn btn-outline-primary btn-sm" title="Edit"><i class="fas fa-edit"></i></a>
									</td>
									<td style="text-align: center; vertical-align: middle;" >
										<a href="javascript:deleteBand('<?php echo $band->id . '\',\'' . $band->band ?>');" class="btn btn-danger btn-sm" title="Delete"><i class="fas fa-trash-alt"></i></a>
									</td>
								<?php } ?>
							</tr>

						<?php } ?>
					</tbody>
					<tfoot>
						<th><?= __("All"); ?></th>
						<th></th>
						<th class="master_cq"><input type="checkbox" <?php if ($cq > 0) echo 'checked';?>></th>
						<th class="master_dok"><input type="checkbox" <?php if ($dok > 0) echo 'checked';?>></th>
						<th class="master_dxcc"><input type="checkbox" <?php if ($dxcc > 0) echo 'checked';?>></th>
						<th class="master_helvetia"><input type="checkbox" <?php if ($helvetia > 0) echo 'checked';?>></th>
						<th class="master_iota"><input type="checkbox" <?php if ($iota > 0) echo 'checked';?>></th>
						<th class="master_jcc"><input type="checkbox" <?php if ($jcc > 0) echo 'checked';?>></th>
						<th class="master_pota"><input type="checkbox" <?php if ($pota > 0) echo 'checked';?>></th>
						<th class="master_rac"><input type="checkbox" <?php if ($rac > 0) echo 'checked';?>></th>
						<th class="master_sig"><input type="checkbox" <?php if ($sig > 0) echo 'checked';?>></th>
						<th class="master_sota"><input type="checkbox" <?php if ($sota > 0) echo 'checked';?>></th>
						<th class="master_uscounties"><input type="checkbox" <?php if ($uscounties > 0) echo 'checked';?>></th>
						<th class="master_vucc"><input type="checkbox" <?php if ($vucc > 0) echo 'checked';?>></th>
						<th class="master_waja"><input type="checkbox" <?php if ($waja > 0) echo 'checked';?>></th>
						<th class="master_was"><input type="checkbox" <?php if ($was > 0) echo 'checked';?>></th>
						<th class="master_wwff"><input type="checkbox" <?php if ($wwff > 0) echo 'checked';?>></th>
						<th></th>
						<th></th>
						<th></th>
						<th></th>
						<th></th>
						<?php if($this->session->userdata('user_type') == '99') { ?>
							<th></th>
							<th></th>
						<?php } ?>
					</tfoot>
					<table>
			</div>
  			<br/>
			<p>
				<?php if($this->session->userdata('user_type') == '99') { ?>
					<script>
						var lang_options_bands_edit = '<?= __("Edit Band"); ?>';
						var lang_options_bands_create = '<?= __("Create a band"); ?>';
						var lang_admin_close = '<?= __("Close"); ?>';
						var lang_options_bands_delete_warning = '<?= __("Warning! Are you sure you want to delete the following band: "); ?>';
						var lang_options_bands_activateall_warning = '<?= __("Warning! Are you sure you want to activate all bands?"); ?>';
						var lang_options_bands_deactivateall_warning = '<?= __("Warning! Are you sure you want to deactivate all bands?"); ?>';
					</script>
					<button onclick="createBandDialog();" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> <?= __("Create a band"); ?></button>
					<button onclick="activateAllBands();" class="btn btn-primary btn-sm"><?= __("Activate All"); ?></button>
					<button onclick="deactivateAllBands();" class="btn btn-primary btn-sm"><?= __("Deactivate All"); ?></button>
				<?php } ?>
			</p>
		</div>
	</div>
</div>
