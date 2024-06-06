<div class="container">
    <br>
    <?php if($this->session->flashdata('message')) { ?>
        <!-- Display Message -->
        <div class="alert-message error">
            <p><?php echo $this->session->flashdata('message'); ?></p>
        </div>
    <?php } ?>

    <div class="card">
        <div class="card-header">
            <?= __("QSOs marked")?>
        </div>
        <div class="card-body">
            <h3 class="card-title"><?= __("Yay, its done!")?></h3>
            <p class="card-text"><?= __("The QSOs are marked as exported to LoTW.")?></p>
        </div>
    </div>


</div>

