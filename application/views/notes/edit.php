

<div class="container notes">
<?php foreach ($note->result() as $row) { ?>
<div class="card">
  <div class="card-header">
    <h2 class="card-title"><?= __("Edit Note"); ?></h2>
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

  	<?php if (!empty(validation_errors())): ?>
    <div class="alert alert-danger">
        <a class="btn-close" data-bs-dismiss="alert" title="close">x</a>
        <ul><?php echo (validation_errors('<li>', '</li>')); ?></ul>
    </div>
	<?php endif; ?>

	<form method="post" action="<?php echo site_url('notes/edit'); ?>/<?php echo $id; ?>" name="notes_add" id="notes_add">

	<div class="mb-3">
		<label for="inputTitle"><?= __("Title"); ?></label>
		<input type="text" name="title" class="form-control" value="<?php echo $row->title; ?>" id="inputTitle">
	</div>

	<div class="mb-3">
	   <label for="catSelect"><?= __("Category"); ?></label>
	   <select name="category" class="form-select" id="catSelect">
	    <option value="General" selected="selected"><?= __("General"); ?></option>
		<option value="Antennas"><?= __("Antennas"); ?></option>
		<option value="Satellites"><?= __("Satellites"); ?></option>
	   </select>
	</div>

	<div class="mb-3">
		<label for="inputTitle"><?= __("Note Contents"); ?></label>
		<textarea name="content" style="display:none" id="notes"><?php echo $row->note; ?></textarea>
	</div>

	<input type="hidden" name="id" value="<?php echo $id; ?>" />
	<button type="submit" value="Submit" class="btn btn-primary"><?= __("Save Note"); ?></button>
	</form>
  </div>

  <?php } ?>
</div>

</div>

