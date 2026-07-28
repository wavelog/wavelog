<?php

defined('BASEPATH') OR exit('No direct script access allowed');

if (!function_exists('cronauth_token')) {
	function cronauth_token() {
		$CI = &get_instance();
		$key = $CI->config->item('encryption_key');
		if ($key === null || $key === '' || $key === 'flossie1234555541') {
			log_message('error', 'Cron auth token cannot be generated because the encryption key is not set or is the default value. Please set a proper encryption key in your config.php file.');
			return '';
		}
		return hash_hmac('sha256', 'wavelog-cron-v1', $key);
	}
}

if (!function_exists('cronauth_mark_active')) {
	function cronauth_mark_active() {
		$CI = &get_instance();
		$CI->load->driver('cache', [
			'adapter' => $CI->config->item('cache_adapter') ?? 'file',
			'backup' => $CI->config->item('cache_backup') ?? 'file',
			'key_prefix' => $CI->config->item('cache_key_prefix') ?? ''
		]);
		$CI->cache->save('cron_master_active', time(), 200);
	}
}

if (!function_exists('cronauth_allowed')) {
	function cronauth_allowed($min_user_type = 99) {
		$CI = &get_instance();

		$user_type = $CI->session->userdata('user_type');
		if ($user_type !== null && (int)$user_type >= $min_user_type) {
			return true;
		}

		$CI->load->driver('cache', [
			'adapter' => $CI->config->item('cache_adapter') ?? 'file',
			'backup' => $CI->config->item('cache_backup') ?? 'file',
			'key_prefix' => $CI->config->item('cache_key_prefix') ?? ''
		]);

		if ($CI->cache->get('cron_master_active') === false) {
			return true;
		}

		$expected = cronauth_token();
		if ($expected === '') {
			return false;
		}
		$supplied = $CI->input->get_request_header('X-Wavelog-Auth');
		if ($supplied !== null && $supplied !== '' && hash_equals($expected, (string)$supplied)) {
			return true;
		}
		return false;
	}
}
