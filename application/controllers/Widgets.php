<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*

	Widgets are designed to be addons to use around the internet.

*/

class Widgets extends CI_Controller {

	public function index()
	{
		// Show a help page
	}


	// Can be used to embed last few QSOs
	public function qsos($logbook_slug = null) {

		if($logbook_slug == null) {
			show_error(__("Unknown Public Page, please make sure the public slug is correct."));
		}

		// determine theme
		$this->load->model('themes_model');
		$theme = $this->input->get('theme', TRUE);
		if ($theme != null) {
			if (($this->themes_model->get_theme_mode($theme) ?? '') != '') {
				$data['theme'] = $theme;
			} else {
				$data['theme'] = $this->config->item('option_theme');
			}
		} else {
			$data['theme'] = "default";
		}

		// determine text size
		$text_size = $this->input->get('text_size', true) ?? 1;
		$data['text_size_class'] = $this->prepare_text_size_css_class($text_size);

		// number of QSOs shown
		$qso_count_param = $this->input->get('qso_count', TRUE);
		if ($qso_count_param === null || !is_numeric($qso_count_param)) {
			$qso_count = QSO_WIDGET_DEFAULT_QSO_LIMIT;
		} else {
			$qso_count = min($qso_count_param, QSO_WIDGET_MAX_QSO_LIMIT);
		}

		// date format
		$data['date_format'] = $this->config->item('qso_date_format'); // date format from /config/wavelog.php
		
		$this->load->model('logbook_model');
		$this->load->model('logbooks_model');
		$this->load->model('stationsetup_model');
		if($this->logbooks_model->public_slug_exists($logbook_slug)) {

			$logbook_id = $this->logbooks_model->public_slug_exists_logbook_id($logbook_slug);
			if($logbook_id != false)
			{
				// Get associated station locations for mysql queries
				$logbooks_locations_array = $this->logbooks_model->list_logbook_relationships($logbook_id);

				if (!$logbooks_locations_array) {
					show_404(__("Empty Logbook"));
				}
			} else {
				log_message('error', $logbook_slug.' has no associated station locations');
				show_404(__("Unknown Public Page."));
			}

			// Get widget settings
			$user_id = $this->stationsetup_model->public_slug_exists_userid($logbook_slug);
			$widget_options = $this->get_qso_widget_options($user_id);

			$data['show_time'] = $widget_options->display_qso_time;			
			$data['last_qsos_list'] = $this->logbook_model->get_last_qsos($qso_count, $logbooks_locations_array);

			$this->load->view('widgets/qsos', $data);
		}
	}

	public function oqrs($user_callsign = 'CALL MISSING') {
		$this->load->model('oqrs_model');
		$stations = $this->oqrs_model->get_oqrs_stations();

		if ($stations->result() === NULL) {
			show_404(__("No stations found that are using Wavelog OQRS."));
			return;
		}

		$slug = $this->input->get('slug', TRUE);
		if ($slug != null) {
			$data['logo_url'] = base_url() . 'index.php/visitor/' . $slug;
		} else {
			$data['logo_url'] = 'https://github.com/wavelog/wavelog';
		}

		$this->load->model('themes_model');
		$theme = $this->input->get('theme', TRUE);
		if ($theme != null) {
			if (($this->themes_model->get_theme_mode($theme) ?? '') != '') {
				$data['theme'] = $theme;
			} else {
				$data['theme'] = $this->config->item('option_theme');
			}
		} else {
			$data['theme'] = $this->config->item('option_theme');
		}

		$data['user_callsign'] = strtoupper($this->security->xss_clean($user_callsign));
		$this->load->view('widgets/oqrs', $data);
	}

	/**
	 * On-Air widget handler
	 *
	 * @param string $user_slug
	 * @return void
	 */
	public function on_air($user_slug = "") {
		// determine theme
		$this->load->model('themes_model');
		$theme = $this->input->get('theme', TRUE);
		if ($theme != null) {
			if (($this->themes_model->get_theme_mode($theme) ?? '') != '') {
				$data['theme'] = $theme;
			} else {
				$data['theme'] = $this->config->item('option_theme');
			}
		} else {
			$data['theme'] = $this->config->item('option_theme');
		}

		// determine text size
		$text_size = $this->input->get('text_size', true) ?? 1;

		if (empty($user_slug)) {
			$data['text_size_class'] = $this->prepare_text_size_css_class($text_size);
			$data['error'] = __("User slug not specified");
			$this->load->view('widgets/on_air', $data);
			return;
		}

		try {
			$user = $this->get_user_by_slug($user_slug);
		} catch (\Exception $e) {
			$data['text_size_class'] = $this->prepare_text_size_css_class($text_size);
			$data['error'] = __("User slug not specified");
			$data['error'] = $e->getMessage();
			$this->load->view('widgets/on_air', $data);
			return;
		}

		$user_id = $user->user_id;
		$widget_options = $this->get_on_air_widget_options($user_id);

		if ($widget_options->is_enabled === false) {
			$data['text_size_class'] = $this->prepare_text_size_css_class($text_size);
			$data['error'] = __("User has on-air widget disabled");
			$this->load->view('widgets/on_air', $data);
			return;
		}

		$this->load->model('cat');
		$query = $this->cat->status_for_user_id($user_id);


		if ($query->num_rows() > 0) {
			$radio_timeout_seconds = $this->get_radio_timeout_seconds();
			$cat_timeout_interval_minutes = floor($radio_timeout_seconds / 60);
			$radios_online = [];
			$last_seen_days_ago = 999;

			foreach ($query->result() as $radio_data) {
				// There can be multiple radios online, we need to take into account all of them
				$radio_updated_ago_minutes = $this->calculate_radio_updated_ago_minutes($radio_data->timestamp);

				if ($radio_updated_ago_minutes > $cat_timeout_interval_minutes) {
					// Radio was updated too long ago - calculate user's "last seen X days ago" value
					$mins_per_day = 1440;
					$radio_last_seen_days_ago = (int)floor($radio_updated_ago_minutes / $mins_per_day);
					$last_seen_days_ago = min($last_seen_days_ago, $radio_last_seen_days_ago);
				} else {
					// Radio is available - add it to the array of available radios to be presented in UI
					$radio_obj = new \stdClass;
					$radio_obj->updated_at = $radio_data->timestamp;
					$radio_obj->frequency_string = $this->prepare_frequency_string_for_widget($radio_data);
					$radios_online[] = $radio_obj;
				}
			}

			if (count($radios_online) > 1 && $widget_options->display_only_most_recent_radio) {
				// in case only most recent radio should be displayed, use only most recently updated radio as a result
				usort($radios_online, function($radio_a, $radio_b) {
					if ($radio_a->updated_at == $radio_b->updated_at) return 0;
  					return ($radio_a->updated_at > $radio_b->updated_at) ? -1 : 1;
				});

				$radios_online = [$radios_online[0]];
			}

			// last seen text
			$last_seen_text = $widget_options->display_last_seen ? $this->prepare_last_seen_text($last_seen_days_ago) : null;

			$data['text_size_class'] = $this->prepare_text_size_css_class($text_size);

			// prepare rest of the data for UI
			$data['user_callsign'] = strtoupper($user->user_callsign);
			$data['is_on_air'] = count($radios_online) > 0;
			$data['radios_online'] = $radios_online;
			$data['last_seen_text'] = $last_seen_text;

			$this->load->view('widgets/on_air', $data);

		} else {
			$data['text_size_class'] = $this->prepare_text_size_css_class($text_size);
			$data['user_callsign'] = strtoupper($user->user_callsign);
			$data['error'] = __("No CAT interfaced radios found. You need to have at least one radio interface configured.");
			$this->load->view('widgets/on_air', $data);
			return;
		}
	}

	/**
	 * Fetch and prepare user options for QSO widget
	 *
	 * @return stdClass
	 */
	private function get_qso_widget_options($user_id) {
		$raw_widget_options = $this->user_options_model->get_options('widget', null, $user_id)->result_array();

		// default values
		$options = new \stdClass();
		$options->display_qso_time = false;

		if ($raw_widget_options === null) {
			return $options;
		}

		foreach ($raw_widget_options as $opt_data) {
			if ($opt_data["option_name"] !== 'qso') {
				continue;
			}

			$key = $opt_data["option_key"];
			$value = $opt_data["option_value"];

			if ($key === "display_qso_time") {
				$options->display_qso_time = $value === "true";
			}
		}

		return $options;
	}

	/**
	 * Fetch and prepare user options for on air widget
	 *
	 * @return stdClass
	 */
	private function get_on_air_widget_options($user_id) {
		$raw_widget_options = $this->user_options_model->get_options('widget', null, $user_id)->result_array();

		// default values
		$options = new \stdClass();
		$options->is_enabled = false;
		$options->display_last_seen = false;
		$options->display_only_most_recent_radio = true;

		if ($raw_widget_options === null) {
			return $options;
		}

		foreach ($raw_widget_options as $opt_data) {
			if ($opt_data["option_name"] !== 'on_air') {
				continue;
			}

			$key = $opt_data["option_key"];
			$value = $opt_data["option_value"];

			if ($key === "enabled") {
				$options->is_enabled = $value === "true";
			}
			if ($key === "display_last_seen") {
				$options->display_last_seen = $value === "true";
			}
			if ($key === "display_only_most_recent_radio") {
				$options->display_only_most_recent_radio = $value === "true";
			}
		}

		return $options;
	}

	/**
	 * Get radio timout value. In case user set value in GET parameter, this value is used.
	 * Otherwise global radio timeout value is used.
	 *
	 * @return int
	 */
	private function get_radio_timeout_seconds() {
		$query_param_value = $this->input->get('radio_timeout_seconds', true);

		if (is_numeric($query_param_value) === false || empty($query_param_value)) {
			return intval($this->config->item('radio_timeout_seconds'));
		}

		$radio_timeout_seconds = intval($query_param_value);
		$min_value = 60;

		if ($radio_timeout_seconds < $min_value) {
			$radio_timeout_seconds = $min_value;
		}

		return $radio_timeout_seconds;
	}

	/**
	 * Fetch user ID by user slug
	 *
	 * @param string $slug
	 * @return object
	 */
	private function get_user_by_slug($slug) {
		$this->load->model('user_model');
		$user_result = $this->user_model->get_by_slug($slug);
		if ($user_result->num_rows() == 0) {
			throw new \Exception(__("User not found by slug"));
		}
		if ($user_result->num_rows() > 1) {
			throw new \Exception(__("Multiple users found by slug"));
		}
		$user_row = $user_result->result();
		$user = current($user_row);

		return $user;
	}

	/**
	 * Prepare Boostrap CSS font size class for text in the widget
	 *
	 * @param integer $selected_size Use values in range 1-6
	 * @return string Prepared Bootstrap font-size CSS class
	 */
	private function prepare_text_size_css_class($selected_size = 1) {
		if ($selected_size < 1 || $selected_size > 6) {
			$selected_size = 1;
		}

		$base_size_number = 7;
		$calculated_size_number = $base_size_number - $selected_size;

		return sprintf("fs-%s", $calculated_size_number);
	}

	/**
	 * Prepare text "last seen" text
	 *
	 * @param int $last_seen_days_ago
	 * @return void
	 */
	private function prepare_last_seen_text($last_seen_days_ago) {
		if ($last_seen_days_ago === 0) {
			return "Last seen less than a day ago";
		}
		if ($last_seen_days_ago === 1) {
			return "Last seen yesterday";
		}
		return sprintf("Last seen %d days ago", $last_seen_days_ago);
	}

	/**
	 * Calculate "radio last updated at" value
	 *
	 * @param string $radio_last_updated_timestamp How many minutes ago was radio CAT data updated
	 * @return int
	 */
	private function calculate_radio_updated_ago_minutes($radio_last_updated_timestamp) {
		$datetime1 = new DateTime("now", new DateTimeZone('UTC')); // Today's Date/Time
		$datetime2 = new DateTime($radio_last_updated_timestamp, new DateTimeZone('UTC'));
		$interval = $datetime1->diff($datetime2);

		$minutes_ago = $interval->days * 24 * 60;
		$minutes_ago += $interval->h * 60;
		$minutes_ago += $interval->i;

		return (int)$minutes_ago;
	}

	/**
	 * Prepare formatted frequency string based on given CAT data
	 *
	 * @param object $cat_data
	 * @return string
	 */
	private function prepare_frequency_string_for_widget($cat_data) {
		$r_option = 1;
		$source_unit = "Hz";
		$target_unit = "MHz";

		if (empty($cat_data->frequency) || $cat_data->frequency == "0") {
			return "- / -";
		} elseif (empty($cat_data->frequency_rx) || $cat_data->frequency_rx == "0") {
			$tx_frequency = $this->frequency->qrg_conversion(
				$cat_data->frequency, $r_option, $source_unit, $target_unit
			);
			$mode_string = empty($cat_data->mode) ? "" : $cat_data->mode;

			return trim(sprintf("%s %s", $tx_frequency, $mode_string));
		} else {
			$rx_frequency = $this->frequency->qrg_conversion(
				$cat_data->frequency_rx, $r_option, $source_unit, $target_unit
			);
			$tx_frequency = $this->frequency->qrg_conversion(
				$cat_data->frequency, $r_option, $source_unit, $target_unit
			);
			$mode_string = empty($cat_data->mode) ? "" : $cat_data->mode;

			return trim(sprintf("%s / %s %s", $rx_frequency, $tx_frequency, $mode_string));
		}
	}
}
