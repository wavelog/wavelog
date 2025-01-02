<?php

class Radio extends CI_Controller {

	public function index() {
		// Check Auth
		$this->load->model('user_model');

		// Check if users logged in

		if ($this->user_model->validate_session() == 0) {
			// user is not logged in
			redirect('user/login');
		}
		session_write_close();
		// load the view
		$data['page_title'] = __("Hardware Interfaces");

		$footerData = [];
		$footerData['scripts'] = [
			'assets/js/sections/radio.js?' . filemtime(realpath(__DIR__ . "/../../assets/js/sections/radio.js")),
		];

		$this->load->view('interface_assets/header', $data);
		$this->load->view('radio/index');
		$this->load->view('interface_assets/footer', $footerData);
	}

	function status() {

		$this->load->model('user_model');
		if(!$this->user_model->authorize(3)) { $this->session->set_flashdata('error', __("You're not allowed to do that!")); redirect('dashboard'); }

		session_write_close();

		$this->load->model('cat');
		$query = $this->cat->status();

		if ($query->num_rows() > 0) {
			echo "<thead><tr>";
			echo "<th>" . __("Radio") . "</th>";
			if ($this->session->userdata('clubstation') == 1 && clubaccess_check(9)) {
				echo "<th>" . __("Operator") . "</th>";
			}
			echo "<th>" . __("Frequency") . "</th>";
			echo "<th>" . __("Mode") . "</th>";
			echo "<th>" . __("Timestamp") . "</th>";
			echo "<th> </th>";
			echo "<th>" . __("Options") . "</th>";
			echo "<th>" . __("Settings") . "</th>";
			echo "<th></th>";
			echo "</tr></thead><tbody>";
			foreach ($query->result() as $row) {
				echo "<tr>";
				echo "<td>" . $row->radio . "</td>";

				$this->load->model('user_model');
				if ($this->session->userdata('clubstation') == 1 && clubaccess_check(9)) {
					$operator = $this->user_model->get_by_id($row->operator)->row();
					if ($operator) {
						echo "<td>" . $operator->user_callsign . "</td>";
					} else {
						echo "<td>" . __("UNKNOWN") . "</td>";
					}
				}

				if (empty($row->frequency) || $row->frequency == "0") {
					echo "<td>- / -</td>";
				} elseif (empty($row->frequency_rx) || $row->frequency_rx == "0") {
					echo "<td>" . $this->frequency->qrg_conversion($row->frequency) . "</td>";
				} else {
					echo "<td>" . $this->frequency->qrg_conversion($row->frequency_rx) . " / " . $this->frequency->qrg_conversion($row->frequency) . "</td>";
				}

				if (empty($row->mode) || $row->mode == "non") {
					echo "<td>N/A</td>";
				} elseif (empty($row->mode_rx) || $row->mode_rx == "non") {
					echo "<td>" . $row->mode . "</td>";
				} else {
					echo "<td>" . $row->mode_rx . " / " . $row->mode . "</td>";
				}

				// Get Date format
				if ($this->session->userdata('user_date_format')) {
					// If Logged in and session exists
					$custom_date_format = $this->session->userdata('user_date_format');
				} else {
					// Get Default date format from /config/wavelog.php
					$custom_date_format = $this->config->item('qso_date_format');
				}

				$phpdate = strtotime($row->timestamp);
				echo "<td>" . date($custom_date_format . ' H:i:s', $phpdate) . "</td>";

				$last_updated = $this->cat->last_updated()->row()->id;

				if ($last_updated == $row->id) {
					echo '<td><i>' . __("last updated") . '</i></td>';
				} else {
					echo '<td></td>';
				}

				if ($this->session->userdata('clubstation') != 1) {
					$defaul_user_radio = $this->user_options_model->get_options('cat', array('option_name' => 'default_radio'))->row()->option_value ?? NULL;
					if (!$defaul_user_radio) {
						echo '<td><button id="default_radio_btn_' . $row->id . '" class="btn btn-sm btn-outline-primary ld-ext-right" onclick="set_default_radio(' . $row->id . ')">' . __("Set as default radio") . '<div class="ld ld-ring ld-spin"></div></button</td>';
					} else {
						if ($defaul_user_radio !== $row->id) {
							echo '<td><button id="default_radio_btn_' . $row->id . '" class="btn btn-sm btn-outline-primary ld-ext-right" onclick="set_default_radio(' . $row->id . ')">' . __("Set as default radio") . '<div class="ld ld-ring ld-spin"></div></button</td>';
						} else {
							echo '<td><button id="default_radio_btn_' . $row->id . '" class="btn btn-sm btn-primary ld-ext-right" onclick="release_default_radio(' . $row->id . ')">' . __("Default (click to release)") . '<div class="ld ld-ring ld-spin"></div></button</td>';
						}
					}
				}
				echo "<td><button id='edit_cat_settings_".$row->id."' \" class=\"editCatSettings btn btn-sm btn-primary\"> " . __("Edit") . "</button></td>";
				echo "<td><a href=\"" . site_url('radio/delete') . "/" . $row->id . "\" class=\"btn btn-sm btn-danger\"> <i class=\"fas fa-trash-alt\"></i> " . __("Delete") . "</a></td>";
				echo "</tr>";
			}
			echo "</tbody>";
		} else {
			echo "<thead><tr>";
			echo "<td colspan=\"6\"><div class=\"alert alert-info text-center\">" . __("No CAT interfaced radios found.") . "</div></td>";
			echo "</tr></thead>";
		}
	}

	public function saveCatUrl() {
		$url = $this->input->post('caturl', true);
		$id = $this->input->post('id', true);
		$this->load->model('cat');
		$this->cat->updateCatUrl($id,$url);
	}

	public function editCatUrl() {
		$this->load->model('cat');
		$data['container'] = $this->cat->radio_status($this->input->post('id', true))->row();
		$data['page_title'] = __("Edit CAT Settings");
		$this->load->view('radio/edit', $data);
	}

	function json($id) {

		$clean_id = $this->security->xss_clean($id);

		$this->load->model('user_model');

		// Check if users logged in

		if ($this->user_model->validate_session() == 0) {
			// user is not logged in
			// Return Json data
			header('Content-Type: application/json');
			echo json_encode(array(
				"error" => "not_logged_in"
			), JSON_PRETTY_PRINT);
		} else {
			session_write_close();

			header('Content-Type: application/json');

			$this->load->model('cat');

			$query = $this->cat->radio_status($clean_id);

			if ($query->num_rows() > 0) {
				foreach ($query->result() as $row) {

					$frequency = $row->frequency;

					$frequency_rx = $row->frequency_rx;

					$power = $row->power;

					$prop_mode = $row->prop_mode;

					$cat_url = $row->cat_url;;

					// Check Mode
					if (isset($row->mode) && ($row->mode != null)) {
						$mode = strtoupper($row->mode);
						if ($mode == "FMN") {
							$mode = "FM";
						}
					} else {
						$mode = null;
					}

					if ($row->prop_mode == "SAT") {
						// Get Satellite Name
						if ($row->sat_name == "AO-07") {
							$sat_name = "AO-7";
						} elseif ($row->sat_name == "LILACSAT") {
							$sat_name = "CAS-3H";
						} else {
							$sat_name =  strtoupper($row->sat_name);
						}

						// Get Satellite Mode
						$sat_mode_uplink = $this->get_mode_designator($row->frequency);
						$sat_mode_downlink = $this->get_mode_designator($row->frequency_rx);

						if (empty($sat_mode_uplink)) {
							$sat_mode = "";
						} elseif ($sat_mode_uplink !== $sat_mode_downlink) {
							$sat_mode = $sat_mode_uplink . "/" . $sat_mode_downlink;
						} else {
							$sat_mode = $sat_mode_uplink;
						}
					} else {
						$sat_name = "";
						$sat_mode = "";
					}

					// Calculate how old the data is in minutes
					$datetime1 = new DateTime("now", new DateTimeZone('UTC')); // Today's Date/Time
					$datetime2 = new DateTime($row->timestamp, new DateTimeZone('UTC'));
					$interval = $datetime1->diff($datetime2);

					$minutes = $interval->days * 24 * 60;
					$minutes += $interval->h * 60;
					$minutes += $interval->i;

					$updated_at = $minutes;

					// Return Json data
					$a_ret['frequency'] = $frequency;
					$a_ret['frequency_formatted'] = $this->frequency->qrg_conversion($frequency);
					if (isset($frequency_rx) && ($frequency_rx != null)) {
						$a_ret['frequency_rx'] = $frequency_rx;
						$a_ret['frequency_rx_formatted'] = $this->frequency->qrg_conversion($frequency_rx);
					}
					if (isset($mode) && ($mode != null)) {
						$a_ret['mode'] = $mode;
					}
					if (isset($sat_mode) && ($sat_mode != null)) {
						$a_ret['satmode'] = $sat_mode;
					}
					if (isset($sat_name) && ($sat_name != null)) {
						$a_ret['satname'] = $sat_name;
					}
					if (isset($power) && ($power != null)) {
						$a_ret['power'] = $power;
					}
					if (isset($prop_mode) && ($prop_mode != null)) {
						$a_ret['prop_mode'] = $prop_mode;
					}
					if (isset($cat_url) && ($cat_url != null)) {
						$a_ret['cat_url'] = $cat_url;
					}
					$a_ret['updated_minutes_ago'] = $updated_at;
					echo json_encode($a_ret, JSON_PRETTY_PRINT);
				}
			}
		}
	}

	function get_mode_designator($frequency) {
		if ($frequency > 21000000 && $frequency < 22000000)
			return "H";
		if ($frequency > 28000000 && $frequency < 30000000)
			return "A";
		if ($frequency > 144000000 && $frequency < 147000000)
			return "V";
		if ($frequency > 432000000 && $frequency < 438000000)
			return "U";
		if ($frequency > 1240000000 && $frequency < 1300000000)
			return "L";
		if ($frequency > 2320000000 && $frequency < 2450000000)
			return "S";
		if ($frequency > 3400000000 && $frequency < 3475000000)
			return "S2";
		if ($frequency > 5650000000 && $frequency < 5850000000)
			return "C";
		if ($frequency > 10000000000 && $frequency < 10500000000)
			return "X";
		if ($frequency > 24000000000 && $frequency < 24250000000)
			return "K";
		if ($frequency > 47000000000 && $frequency < 47200000000)
			return "R";

		return "";
	}

	function delete($id) {

		$clean_id = $this->security->xss_clean($id);

		// Check Auth
		$this->load->model('user_model');
		if (!$this->user_model->authorize(3)) {
			$this->session->set_flashdata('error', __("You're not allowed to do that!"));
			redirect('dashboard');
		}

		$this->load->model('cat');

		$this->cat->delete($clean_id);

		if ($clean_id == $this->user_options_model->get_options('cat', array('option_name' => 'default_radio'))->row()->option_value ?? '') {
			$this->release_default_radio();
		}

		$this->session->set_flashdata('message', 'Radio Profile Deleted');

		session_write_close();
		redirect('radio');
	}

	function set_default_radio() {

		// get the radio_id from POST
		$clean_radio_id = $this->security->xss_clean($this->input->post('radio_id'));

		// Check Auth
		$this->load->model('user_model');
		if (!$this->user_model->authorize(3)) {
			$this->session->set_flashdata('error', __("You're not allowed to do that!"));
			redirect('dashboard');
		}

		// we unset the current default radio
		$this->release_default_radio();

		// Set the user_option and session data
		$this->user_options_model->set_option('cat', 'default_radio', array('radio_id' => $clean_radio_id));
		$this->session->set_userdata('radio', $clean_radio_id);
	}

	function release_default_radio() {
		// Check Auth
		$this->load->model('user_model');
		if (!$this->user_model->authorize(3)) {
			$this->session->set_flashdata('error', __("You're not allowed to do that!"));
			redirect('dashboard');
		}

		// Unset the user_option and session data
		$this->user_options_model->del_option('cat', 'default_radio');
		$this->session->unset_userdata('radio');
	}
}
