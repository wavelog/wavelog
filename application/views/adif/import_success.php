<div class="container my-5">
    <?php if($this->session->flashdata('message')): ?>
        <!-- Display Flash Message -->
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= $this->session->flashdata('message'); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow-lg border-0 rounded-3">
        <div class="card-header bg-success text-white fw-bold">
            <?= __("ADIF Imported") ?>
        </div>
        <div class="card-body text-center">
            <!-- Success message -->
            <div class="mb-3">
                <div class="bg-success text-white rounded-circle d-inline-flex justify-content-center align-items-center"
                     style="width: 80px; height: 80px; font-size: 2.5rem;">
                    ✔
                </div>
            </div>
            <h3 class="card-title text-success"><?= __("Yay, it's imported!") ?></h3>
            <p class="card-text text-muted mb-2"><?= __("The ADIF File has been imported.") ?></p>
            <p class="card-text">
                <strong><?= __("Number of QSOs imported:") ?></strong> <?= $qsocount ?>
            </p>

            <!-- Dupe information -->
            <p class="card-text">
                <?php if(isset($skip_dupes)): ?>
                    <span class="badge bg-danger"><?= __("Dupes were inserted!") ?></span>
                <?php else: ?>
                    <span class="badge bg-success"><?= __("Dupes were skipped.") ?></span>
                <?php endif; ?>
            </p>

	<!-- Display imported information for contest data fixing if contest data was imported -->
    <?php if(count($imported_contests) > 0) {?>
       <div class="alert alert-dark" role="alert">
       <span class="badge text-bg-info"><?= __("Information"); ?></span> <i class="fas fa-list"></i> <b><?= __("Contest logs imported")?></b>
          <p>
          <p><?= __("You imported at least 1 QSO containing a contest ID.")?> <?= __("Sometimes, depending on your contest logging software, your exchanges will not be imported properly from that softwares ADIF. If you like to correct that, switch to the CBR Import Tab of the ADIF Import page.")?></p>
          <p><?= __("We found the following numbers of QSOs for the following contest IDs:")?></p>

    <!-- List imported contest data -->
    <ul>
    <?php foreach ($imported_contests as $contestid => $qsocount) { ?>
      <li><?php echo $contestid . ' (' . $qsocount . ' '. ($qsocount == 1 ? 'QSO' : 'QSOs')  .')'; ?></li>
    <?php } ?>
    </ul>
    <?php } ?>
       </div>

	<!-- Display errors for ADIF import -->
	<?php if($adif_errors): ?>
		<div class="mt-2 ms-2 me-2">
			<h3 class="text-danger"><?= __("Import details / possible problems") ?></h3>
			<br> <?= sprintf(__("Check %s for hints about errors in ADIF files."), "<a target=\"_blank\" href=\"https://github.com/wavelog/Wavelog/wiki/ADIF-file-can't-be-imported\">Wavelog Wiki</a>") ?>
			<p><?= __("You might have ADIF errors, the QSOs have still been added. Please check the following information:") ?></p>

			<div class="border rounded bg-light p-3" style="max-height: 250px; overflow-y: auto;">
				<pre class="mb-0"><?= $adif_errors ?></pre>
			</div>
		</div>
	<?php endif; ?>

        </div>
    </div>
</div>
