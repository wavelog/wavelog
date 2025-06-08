<div class="container lotw">

	<h2><?= __("DCL Key Import"); ?></h2>

	<!-- Card Starts -->
	<div class="card">
		<div class="card-header">
			<?= __("DCL Key Management"); ?>
		</div>

		<div class="card-body">
			<div class="alert alert-info" role="alert">
				<h5><?= __("Import Key"); ?></h5>
				<?= __("You requested a key for DCL-Dataexchange. Please check carefully if it was you, who requested it, and confirm the Import below by pressing the Import-Button"); ?>
			</div>

			<div class="mb-3">
				<b><?= __("Received DCL-Key"); ?></b>:<pre><?php echo $token; ?></pre>
			</div>
				<button type="button" value="import" class="btn btn-primary"><?= __("Import"); ?></button>
		</div>
	</div>
</div>
