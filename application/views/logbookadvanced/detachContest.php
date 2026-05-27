<form id="detachForm">
    <?php foreach ($qsoIds as $id): ?>
        <input type="hidden" name="qsoIds[]" value="<?php echo htmlspecialchars($id); ?>">
    <?php endforeach; ?>

    <?php echo sprintf(_ngettext("Detach %s QSO from contest?", "Detach %s QSOs from contests?", count($qsoIds)), count($qsoIds)); ?>

</form>
