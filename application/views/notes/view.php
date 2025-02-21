<div class="container notes">

	<div class="card">
	<?php foreach ($note->result() as $row) { ?>
		<div class="card-header">
    		<h2 class="card-title"><?= __("Notes"); ?> - <?php echo $row->title; ?></h2>
    			<ul class="nav nav-tabs card-header-tabs">
	    <li class="nav-item">
	    	<a class="nav-link" href="<?php echo site_url('notes'); ?>"><?= __("Notes"); ?></a>
	    </li>
	    <li class="nav-item">
	    	<a class="nav-link" href="<?php echo site_url('notes/add'); ?>"><?= __("Create Note"); ?></a>
	    </li>
	</ul>
		</div>
	  <div class="card-body">
	  	<textarea name="content" style="display:none" id="notes_view"><?php echo $row->note; ?></textarea>

	    <a href="<?php echo site_url('notes/edit'); ?>/<?php echo $row->id; ?>" class="btn btn-primary"><i class="fas fa-edit"></i> <?= __("Edit Note"); ?></a>

	    <a href="<?php echo site_url('notes/delete'); ?>/<?php echo $row->id; ?>" class="btn btn-danger"><i class="fas fa-trash-alt"></i> <?= __("Delete Note"); ?></a>
	    <?php } ?>
	  </div
>	</div>

</div>