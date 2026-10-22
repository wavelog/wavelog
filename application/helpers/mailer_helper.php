<?php

defined('BASEPATH') OR exit('No direct script access allowed');

if (!function_exists('mailer_send')) {
	/**
	 * Send one of the templates in application/views/email/
	 *
	 * @param	string		$view			View path below application/views/, e.g. 'email/testmail'
	 * @param	string		$to				Recipient address
	 * @param	array		$data			Variables handed to the template
	 * @param	string|null	$language		Language folder of the recipient, NULL keeps the current one
	 * @param	string|null	$reply_to_email	Optional Reply-To address
	 * @param	string		$reply_to_name	Display name for the Reply-To address
	 *
	 * @return	array	['success' => bool, 'error' => string] - 'error' is empty on success
	 */
	function mailer_send($view, $to, $data = array(), $language = NULL, $reply_to_email = NULL, $reply_to_name = '') {

		$CI =& get_instance();
		$CI->load->library('email');

		$is_smtp = ($CI->optionslib->get_option('emailProtocol') === 'smtp');

		$config = array(
			'protocol' => $is_smtp ? 'smtp' : 'mail',
			'smtp_timeout' => (int) ($CI->optionslib->get_option('smtpTimeout') ?: 30),
			'crlf' => "\r\n",
			'newline' => "\r\n"
		);

		if ($is_smtp) {
			if ($CI->optionslib->get_option('smtpHost') == '') {
				log_message('error', 'Mailer: SMTP is selected but no SMTP host is configured.');
				return array('success' => FALSE, 'error' => __("SMTP is selected but no SMTP host is configured."));
			}

			$config['smtp_crypto'] = $CI->optionslib->get_option('smtpEncryption');
			$config['smtp_host'] = $CI->optionslib->get_option('smtpHost');
			$config['smtp_port'] = $CI->optionslib->get_option('smtpPort');
			$config['smtp_user'] = $CI->optionslib->get_option('smtpUsername');
			$config['smtp_pass'] = $CI->optionslib->get_option('smtpPassword');
		}

		$CI->email->initialize($config);

		$message = $CI->email->load($view, $data, $language);

		if (!is_array($message) || !isset($message['subject'], $message['body'])) {
			log_message('error', 'Mailer: template "'.$view.'" did not return a valid message.');
			return array('success' => FALSE, 'error' => __("The email template could not be rendered."));
		}

		$CI->email->from($CI->optionslib->get_option('emailAddress'), $CI->optionslib->get_option('emailSenderName'));
		$CI->email->to($to);

		if ($reply_to_email !== NULL && $reply_to_email !== '') {
			$CI->email->reply_to($reply_to_email, $reply_to_name);
		}

		$CI->email->subject($message['subject']);
		$CI->email->message($message['body']);

		if ($CI->email->send()) {
			return array('success' => TRUE, 'error' => '');
		}

		$error = trim(str_replace('<br />', "\n", $CI->email->print_debugger(array())));

		log_message('error', 'Mailer: sending "'.$view.'" failed: '.$error);

		return array('success' => FALSE, 'error' => $error);
	}
}
