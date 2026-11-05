/*
 * Email options page
 *
 * Saving the settings and sending the test mail both go through AJAX, so the page
 * does not reload and the result can be shown as a toast.
 */

$(document).ready(function () {

	$('#emailSettingsForm').on('submit', function (event) {
		event.preventDefault();

		postWithSpinner($('#emailSettingsSave'), base_url + 'index.php/options/email_save', $(this).serialize(), function (response) {
			if (response.success) {
				showToast(lang_general_word_success, response.message, 'bg-success text-white', 5000);
			} else {
				showToast(lang_general_word_error, response.message, 'bg-danger text-white', 5000);
			}
		});
	});

	$('#sendTestMail').on('click', function () {
		var $detail = $('#testmailDetail');

		$detail.addClass('d-none').empty();

		postWithSpinner($(this), base_url + 'index.php/options/sendTestMail', {}, function (response) {
			if (response.success) {
				showToast(lang_general_word_success, response.message, 'bg-success text-white', 5000);
			} else {
				showToast(lang_general_word_error, response.message, 'bg-danger text-white', 8000);

				if (response.detail) {
					// The mailer debug output can be several lines of SMTP dialogue. It goes
					// below the button instead of into the toast, where it stays readable and
					// can be copied. Inserted as text, it is raw output from the mail server.
					$detail.text(response.detail).removeClass('d-none');
				}
			}
		});
	});
});

/*
 * Posts to the given url and keeps the button disabled with a spinner in it until
 * the request is done. Prevents a second click while a mail is on its way, which
 * can take as long as the configured SMTP timeout.
 */
function postWithSpinner($button, url, data, done) {
	var label = $button.html();
	var text = $button.text();

	$button.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>' + text + '...');

	$.ajax({
		url: url,
		type: 'post',
		dataType: 'json',
		data: data,
		success: done,
		error: function () {
			showToast(lang_general_word_error, lang_general_word_query_failed_unkown, 'bg-danger text-white', 5000);
		},
		complete: function () {
			$button.prop('disabled', false).html(label);
		}
	});
}
