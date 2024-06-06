

<div class="container notes">
<?php foreach ($note->result() as $row) { ?>
<div class="card">
  <div class="card-header">
    <h2 class="card-title"><?php echo __("Edit Note"); ?></h2>
	<ul class="nav nav-tabs card-header-tabs">
	    <li class="nav-item">
	    	<a class="nav-link" href="<?php echo site_url('notes'); ?>"><?php echo __("Notes"); ?></a>
	    </li>
	    <li class="nav-item">
	    	<a class="nav-link" href="<?php echo site_url('notes/add'); ?>"><?php echo __("Create Note"); ?></a>
	    </li>
	</ul>
  </div>

  <div class="card-body">

  	<?php if (!empty(validation_errors())): ?>
    <div class="alert alert-danger">
        <a class="btn-close" data-bs-dismiss="alert" title="close">x</a>
        <ul><?php echo (validation_errors('<li>', '</li>')); ?></ul>
    </div>
	<?php endif; ?>

	<form method="post" action="<?php echo site_url('notes/edit'); ?>/<?php echo $id; ?>" name="notes_add" id="notes_add">

	<div class="mb-3">
		<label for="inputTitle"><?php echo __("Title"); ?></label>
		<input type="text" name="title" class="form-control" value="<?php echo $row->title; ?>" id="inputTitle">
	</div>

	<div class="mb-3">
	   <label for="catSelect"><?php echo __("Category"); ?></label>
	   <select name="category" class="form-select" id="catSelect">
	    <option value="General" selected="selected"><?php echo __("General"); ?></option>
		<option value="Antennas"><?php echo __("Antennas"); ?></option>
		<option value="Satellites"><?php echo __("Satellites"); ?></option>
	   </select>
	</div>

	<div class="mb-3">
		<label for="inputTitle"><?php echo __("Note Contents"); ?></label>
		<div id="quillArea"><?php echo $row->note; ?></div>
		<textarea name="content" style="display:none" id="hiddenArea"></textarea>
	</div>

	<input type="hidden" name="id" value="<?php echo $id; ?>" />
	<button type="submit" value="Submit" class="btn btn-primary"><?php echo __("Save Note"); ?></button>
	</form>
  </div>

  <?php } ?>
</div>

</div>

