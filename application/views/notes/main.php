<!-- Notes main view: lists notes, categories, and search UI -->
<div class="container notes">
	<div class="card">
		<div class="card-header">
			<h2 class="card-title"><?= __("Notes"); ?></h2>
			<ul class="nav nav-tabs card-header-tabs">
				<li class="nav-item">
					<a class="nav-link active" href="<?= site_url('notes'); ?>">
						<i class="fa fa-sticky-note"></i> <?= __("Your Notes"); ?>
					</a>
				</li>
				<li class="nav-item">
					<a class="nav-link" href="<?= site_url('notes/add'); ?>">
						<i class="fa fa-plus-square"></i> <?= __("Create Note"); ?>
					</a>
				</li>
			</ul>
		</div>
		<div class="card-body">
			<div class="row pt-2 align-items-center flex-wrap">
				<div class="col-md-7 col-12 mb-2 mb-md-0">
					<!-- Category filter buttons -->
					<div id="categoryButtons" class="btn-group me-2 flex-wrap" role="group" aria-label="Category Filter">
						<button type="button" class="btn btn-sm btn-outline-secondary btn-light category-btn active" data-category="__all__">
							<?= __("All Categories"); ?> <span class="badge bg-secondary"><?= $all_notes_count ?></span>
						</button>
						<?php 
						// Decode HTML entities for proper display
						$decoded_categories = array();
						foreach ($categories as $key => $value) {
							$decoded_categories[$key] = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
						}
						?>
						<?php foreach ($decoded_categories as $category_key => $category_label): ?>
							<button type="button" class="btn btn-sm btn-outline-secondary btn-light category-btn" data-category="<?= htmlspecialchars($category_key) ?>">
								<?= htmlspecialchars($category_label); ?>
								<?php if ($category_key === 'Contacts'): ?>
									<span class="ms-1" data-bs-toggle="tooltip" title="<?= __("Contacts is a special note category used in various places of Wavelog to store information about QSO partners. These notes are private and are not shared with other users nor exported to external services.") ?>">
										<i class="fa fa-question-circle"></i>
									</span>
								<?php endif; ?>
								<span class="badge bg-secondary"><?= $category_counts[$category_key] ?? 0 ?></span>
							</button>
						<?php endforeach; ?>
						<a href="<?php echo site_url('notes/add'); ?>" class="btn btn-sm btn-success">
							<i class="fas fa-plus"></i> <?= __("Create Note"); ?>
						</a>
					</div>
				</div>
				<div class="col-md-5 col-12">
					<!-- Search box and reset button -->
					<div class="input-group">
						<input type="text" id="notesSearchBox" class="form-control form-control-sm" maxlength="50" placeholder="<?= __("Search notes (min. 3 chars)") ?>">
						<button class="btn btn-outline-secondary btn-sm btn-light" id="notesSearchReset" type="button" title="<?= __("Reset search") ?>">
							<i class="fa fa-times"></i>
						</button>
					</div>
				</div>
			</div>
			<div class="pt-3" id="notesTableContainer">
				<!-- Notes table -->
				<script>
					// Pass data from PHP to JavaScript
					window.browserTimezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
					window.categoryTranslations = <?= json_encode($decoded_categories, JSON_UNESCAPED_UNICODE) ?>;
				</script>
				<table id="notesTable" style="width:100%" class="table-sm table table-hover table-striped table-bordered table-condensed text-center">
					<thead>
						<tr>
							<th style="width:15%" class="text-center"><?= __("Category"); ?></th>
							<th style="width:45%" class="text-start"><?= __("Title"); ?></th>
							<th style="width:15%" class="text-center"><?= __("Creation"); ?></th>
							<th style="width:15%" class="text-center"><?= __("Last Modification"); ?></th>
							<th style="width:10%" class="text-center"><?= __("Actions"); ?></th>
						</tr>
					</thead>
					<tbody>
						<!-- Notes rows will be loaded and rendered by JavaScript -->
					</tbody>
				</table>
			</div>
		</div>
	</div>
</div>
