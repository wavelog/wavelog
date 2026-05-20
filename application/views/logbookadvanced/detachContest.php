<form id="detachForm">
    <?php foreach ($qsoIds as $id): ?>
        <input type="hidden" name="qsoIds[]" value="<?php echo htmlspecialchars($id); ?>">
    <?php endforeach; ?>

    <?php echo sprintf(__("Detach %s QSOs from contests?"), count($qsoIds)); ?>

</form>
