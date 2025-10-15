<!-- Notes view: displays a single note -->
<div class="container notes">
  <div class="card">
    <?php foreach ($note->result() as $row) { ?>
      <div class="card-header">
        <h2 class="card-title"><?= __("Notes"); ?></h2>
        <ul class="nav nav-tabs card-header-tabs">
          <li class="nav-item">
            <a class="nav-link" href="<?= site_url('notes'); ?>">
              <i class="fa fa-sticky-note-o"></i> <?= __("Your Notes"); ?>
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="<?= site_url('notes/add'); ?>">
              <i class="fa fa-plus-square"></i> <?= __("Create Note"); ?>
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link active" href="<?= site_url('notes/add'); ?>">
              <i class="fa fa-sticky-note"></i> <?= __("View Note"); ?>
            </a>
          </li>
        </ul>
      </div>
      <div class="card-body">
        <div class="row mb-3">
          <div class="col-md-8">
            <div class="mb-2">
              <span style="font-size:1em;">
                <?= __("Category"); ?>: <?= __($row->cat); ?>
                <?php if ($row->cat == 'Contacts'): ?>
                  <span class="ms-1" data-bs-toggle="tooltip" title="<?= __("Contacts is a special note category used in various places of Wavelog to store information about QSO partners. These notes are private and are not shared with other users nor exported to external services.") ?>">
                    <i class="fa fa-question-circle text-info"></i>
                  </span>
                <?php endif; ?>
              </span>
            </div>
            <h4 class="fw-bold mb-0"><?php echo htmlspecialchars($row->title); ?></h4>
          </div>
        </div>
        <!-- Note contents -->
        <div class="mb-4">
          <textarea name="content" style="display:none" id="notes_view"><?php echo $row->note; ?></textarea>
        </div>
        <div class="row">
          <div class="col text-end">
            <a href="<?php echo site_url('notes/edit'); ?>/<?php echo $row->id; ?>" class="btn btn-primary btn-sm"><i class="fas fa-pencil-square-o"></i> <?= __("Edit Note"); ?></a>
            <a href="<?php echo site_url('notes/delete'); ?>/<?php echo $row->id; ?>" class="btn btn-danger btn-sm"><i class="fas fa-trash-alt"></i> <?= __("Delete Note"); ?></a>

          </div>
        </div>
      </div>
    <?php } ?>
  </div>
</div>
