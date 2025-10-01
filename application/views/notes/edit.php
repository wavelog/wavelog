<!-- Notes edit view: form for editing an existing note -->
<div class="container notes">
<?php foreach ($note->result() as $row) { ?>
  <div class="card">
    <div class="card-header">
      <h2 class="card-title"><?= __("Notes"); ?></h2>
      <ul class="nav nav-tabs card-header-tabs">
        <li class="nav-item">
          <a class="nav-link" href="<?= site_url('notes'); ?>">
            <i class="fa fa-sticky-note"></i> <?= __("Your Notes"); ?>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="<?= site_url('notes/add'); ?>">
            <i class="fa fa-plus-square"></i> <?= __("Create Note"); ?>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link active" href="<?= site_url('notes/add'); ?>">
            <i class="fa fa-pencil-square-o"></i> <?= __("Edit Note"); ?>
          </a>
        </li>
      </ul>
    </div>
    <div class="card-body">
      <!-- Show validation errors if any -->
      <?php if (!empty(validation_errors())): ?>
      <div class="alert alert-danger alert-dismissible fade show" role="alert" style="margin-top: 1rem;">
      	<span class="badge text-bg-info"><?= __("Warning"); ?></span>
        <?php echo (validation_errors('<span>', '</span>')); ?>
		<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
      <?php endif; ?>
      <!-- Note edit form -->
      <form method="post" action="<?php echo site_url('notes/edit'); ?>/<?php echo $id; ?>" name="notes_add" id="notes_add">
        <div class="mb-3">
          <label for="inputTitle"><?= __("Title"); ?></label>
          <input type="text" name="title" class="form-control" value="<?php echo isset($suggested_title) && $suggested_title ? $suggested_title : set_value('title', $row->title); ?>" id="inputTitle">
        </div>
        <div class="mb-3">
          <label for="catSelect">
            <?= __("Category"); ?>
            <?php if ($row->cat == 'Contacts'): ?>
              <span class="ms-1" data-bs-toggle="tooltip" title="<?= __("Contacts is a special note category used in various places of Wavelog to store information about QSO partners. This notes are private and are not shared with other users nor exported to external services.") ?>">
                <i class="fa fa-question-circle text-info"></i>
              </span>
            <?php endif; ?>
          </label>
          <select name="category" class="form-select" id="catSelect">
              <?php foreach (Note::get_possible_categories() as $category_key => $category_label): ?>
                <option value="<?= htmlspecialchars($category_key) ?>"<?= (set_value('category', $row->cat) == $category_key ? ' selected="selected"' : '') ?>><?= $category_label ?></option>
              <?php endforeach; ?>
          </select>
        </div>
        <div class="mb-3">
          <label for="inputTitle"><?= __("Note Contents"); ?></label>
          <textarea name="content" style="display:none" id="notes"><?php echo set_value('content', $row->note); ?></textarea>
        </div>
        <input type="hidden" name="id" value="<?php echo $id; ?>" />
        <div class="row">
          <div class="col text-end">
            <button type="submit" value="Submit" class="btn btn-primary">
              <i class="fa fa-pencil-square-o btn-sm"></i> <?= __("Save Note"); ?>
            </button>
          </div>
        </div>
      </form>
    </div>
  </div>
<?php } ?>
</div>

