<div class="container">

<br>
	<?php if($this->session->flashdata('message')) { ?>
		<!-- Display Message -->
		<div class="alert-message error">
		  <p><?php echo $this->session->flashdata('message'); ?></p>
		</div>
	<?php } ?>

<h2>Satellites</h2>

<div class="card">
  <div class="card-body">
  <button onclick="createSatelliteDialog();" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Add a satellite</button>
    <div class="table-responsive">

    <table style="width:100%" class="sattable table table-sm table-striped">
			<thead>
				<tr>
					<th>Name</th>
					<th>Export name</th>
                    <th>Orbit</th>
					<th>Mode</th>
					<th>Edit</th>
					<th>Delete</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($satellites as $sat) { ?>
				<tr>
					<td style="text-align: center; vertical-align: middle;" class="satellite_<?php echo $sat->id ?>"><?php echo $sat->satname ?></td>
					<td style="text-align: center; vertical-align: middle;"><?php echo $sat->exportname ?></td>
					<td style="text-align: center; vertical-align: middle;"><span class="badge bg-success"><?php echo $sat->orbit ?></span></td>
					<td style="text-align: center; vertical-align: middle;"><?php echo $sat->modename ?></td>
					<td style="text-align: center; vertical-align: middle;"><button onclick="editSatelliteDialog()" class="btn btn-sm btn-success"><i class="fas fa-edit"></i></i></button></td>
					<td style="text-align: center; vertical-align: middle;"><button onclick="deleteSatellite('<?php echo $sat->id . '\',\'' . $sat->satname ?>')" class="btn btn-sm btn-danger"><i class="fas fa-trash-alt"></i></button></td>
				</tr>

				<?php } ?>
			</tbody>
		<table>

	</div>
  <br/>
</div>
</div>
