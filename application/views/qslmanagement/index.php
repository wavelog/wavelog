<div class="container qsl_management">
	<?php if($this->session->flashdata('message')) { ?>
		<!-- Display Message -->
		<div class="alert-message error">
		  <p><?php echo $this->session->flashdata('message'); ?></p>
		</div>
	<?php } ?>

	<div class="row">
	    <div class="col-sm-8">

	    	<div class="card">
	    		<div class="card-header"><h3><?= __("Incoming QSL Cards"); ?></h3></div>
	    		<div class="card-body">

	    			<form>
						<input type="text" class="form-control" placeholder="Callsign">
					</form>

  					<table class="table">
  						<thead>
  							<tr>
  								<th><?= __("Date/Time"); ?></th>
  								<th><?= __("Band"); ?></th>
  								<th><?= __("Report"); ?></th>
  								<th><?= __("Option"); ?></th>
  							</tr>
  						</thead>

  						<tbody>
  							<tr>
  								<td>1</td>
  								<td>2</td>
  								<td>3</td>
  								<td>4</td>
  							</tr>
  						</tbody>	
  					</table>
	    		</div>
	    	</div>

	    </div>

	    <div class="col-sm-4">
	    	<div class="card">
	    		<div class="card-header"><h3><?= __("Outgoing QSL Cards"); ?></h3></div>
	    		<div class="card-body">

	    			<form>
						<input type="text" class="form-control" placeholder="Callsign">
					</form>
	    		</div>
	    	</div>
	    </div>
	</div>

</div>