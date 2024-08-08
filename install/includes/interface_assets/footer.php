<footer>
	<script>
		// restore data from the localstorage if available
		$('#install_form input').each(function() {
			var inputId = $(this).attr('id');
			if (localStorage.getItem(inputId)) {
				$(this).val(localStorage.getItem(inputId));
			}
		});
		$('#install_form select').each(function() {
			var inputId = $(this).attr('id');
			if (localStorage.getItem(inputId)) {
				$(this).val(localStorage.getItem(inputId));
			}
		});

		// save data in the localstorage
		$('#install_form input').on('input', function() {
			var inputId = $(this).attr('id');
			var inputValue = $(this).val();
			localStorage.setItem(inputId, inputValue);
		});
		$('#install_form select').on('input', function() {
			var inputId = $(this).attr('id');
			var inputValue = $(this).val();
			localStorage.setItem(inputId, inputValue);
		});

		// delete all data in localStorage and reload page
		$('#resetInstaller').click(function() {
			localStorage.clear();
			location.reload();
		});
	</script>
	<script type="module" defer>
		import {
			polyfillCountryFlagEmojis
		} from "../assets/js/country-flag-emoji-polyfill.js";
		polyfillCountryFlagEmojis("Twemoji Country Flags", "<?php echo $websiteurl; ?>assets/fonts/TwemojiCountryFlags/TwemojiCountryFlags.woff2");
	</script>
	<script type="text/javascript" src="../assets/js/bootstrap-multiselect.js"></script>
	<?php
	/**
	 * Hidden field to be able to translate the language names
	 * Add english Language Name here if you add new languages to application/config/gettext.php
	 * This helps the po scanner to make them translatable
	 */
	?>
	<div style="display: none">
		<?= __("Bulgarian"); ?>
		<?= __("Chinese (Simplified)"); ?>
		<?= __("Czech"); ?>
		<?= __("Dutch"); ?>
		<?= __("English"); ?>
		<?= __("Finnish"); ?>
		<?= __("French"); ?>
		<?= __("German"); ?>
		<?= __("Greek"); ?>
		<?= __("Italian"); ?>
		<?= __("Portuguese"); ?>
		<?= __("Polish"); ?>
		<?= __("Russian"); ?>
		<?= __("Spanish"); ?>
		<?= __("Swedish"); ?>
		<?= __("Turkish"); ?>
	</div>
</footer>