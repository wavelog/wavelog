<?php

class Radio extends CI_Controller {

	public function index() {
		// Check Auth

		// Check if users logged in

		if ($this->user_model->validate_session() == 0) {
			// user is not logged in
			redirect('user/login');
		}
		session_write_close();
		// load the view
		$data['page_title'] = __("Hardware Interfaces");

		$this->load->is_loaded('worker') ?: $this->load->library('worker');
		$data['worker_enabled'] = $this->worker->is_enabled(); // without this line worker.js is not loaded!
		$data['radios_user_worker'] = null;
		if ($this->worker->is_enabled()) {
			$topic = 'radios_user.' . $this->session->userdata('user_id');
			$this->worker->register_topic($topic);
			$data['radios_user_worker'] = ['topic' => $topic, 'token' => $this->worker->create_token($topic)];
		}

		$footerData = [];
		$footerData['scripts'] = [
			'assets/js/sections/radio.js',
		];

		$this->load->view('interface_assets/header', $data);
		$this->load->view('radio/index');
		$this->load->view('interface_assets/footer', $footerData);
	}

	function status() {

		if(!$this->user_model->authorize(3)) { $this->session->set_flashdata('error', __("You're not allowed to do that!")); redirect('dashboard'); }

		session_write_close();

		$this->load->model('cat');
		$query = $this->cat->status();

		// Get the default radio
		$default_user_radio = $this->user_options_model->get_options('cat', array('option_name' => $this->_get_optionname()), $this->_get_correct_uid())->row()->option_value ?? NULL;

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

			// WebSocket as first row
			echo "<tr>";
			echo "<td>" . __("WebSocket") . "</td>";
			echo "<td>-</td>"; // Frequency
			echo "<td>-</td>"; // Mode
			echo "<td>-</td>"; // Timestamp
			echo '<td></td>'; // Last updated
			if ($default_user_radio === 'ws') {
				echo '<td><button id="default_radio_btn_ws" class="btn btn-sm btn-primary ld-ext-right" onclick="release_default_radio(\'ws\')">' . __("Default (click to release)") . '<div class="ld ld-ring ld-spin"></div></button></td>';
			} else {
				echo '<td><button id="default_radio_btn_ws" class="btn btn-sm btn-outline-primary ld-ext-right" onclick="set_default_radio(\'ws\')">' . __("Set as default radio") . '<div class="ld ld-ring ld-spin"></div></button></td>';
			}
			echo '<td></td>'; // Settings (no edit for WebSocket)
			echo '<td></td>'; // Delete (no delete for WebSocket)
			echo "</tr>";

			foreach ($query->result() as $row) {
				echo '<tr data-radio-id="' . $row->id . '">';
				echo "<td>" . $row->radio . "</td>";

				if ($this->session->userdata('clubstation') == 1 && clubaccess_check(9)) {
					$operator = $this->user_model->get_by_id($row->operator)->row();
					if ($operator) {
						echo "<td>" . $operator->user_callsign . "</td>";
					} else {
						echo "<td>" . __("UNKNOWN") . "</td>";
					}
				}

				if (empty($row->frequency) || $row->frequency == "0") {
					$freq_html = "- / -";
				} elseif (empty($row->frequency_rx) || $row->frequency_rx == "0") {
					$freq_html = $this->frequency->qrg_conversion($row->frequency);
				} elseif ($this->frequency->frequencies_are_equal($row->frequency, $row->frequency_rx)) {
					$freq_html = $this->frequency->qrg_conversion($row->frequency);
				} else {
					$freq_html = $this->frequency->qrg_conversion($row->frequency_rx) . " / " . $this->frequency->qrg_conversion($row->frequency);
				}
				echo '<td class="radio-freq">' . $freq_html . '</td>';

				if (empty($row->mode) || $row->mode == "non") {
					$mode_html = "N/A";
				} elseif (empty($row->mode_rx) || $row->mode_rx == "non") {
					$mode_html = $row->mode;
				} else {
					$mode_html = $row->mode_rx . " / " . $row->mode;
				}
				echo '<td class="radio-mode">' . $mode_html . '</td>';

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
					echo '<td class="radio-lastupdated"><i>' . __("last updated") . '</i></td>';
				} else {
					echo '<td class="radio-lastupdated"></td>';
				}

				if ($default_user_radio == $row->id) {
					echo '<td><button id="default_radio_btn_' . $row->id . '" class="btn btn-sm btn-primary ld-ext-right" onclick="release_default_radio(' . $row->id . ')">' . __("Default (click to release)") . '<div class="ld ld-ring ld-spin"></div></button</td>';
				} else {
					echo '<td><button id="default_radio_btn_' . $row->id . '" class="btn btn-sm btn-outline-primary ld-ext-right" onclick="set_default_radio(' . $row->id . ')">' . __("Set as default radio") . '<div class="ld ld-ring ld-spin"></div></button</td>';
				}
				echo "<td><button id='edit_cat_settings_".$row->id."' \" class=\"editCatSettings btn btn-sm btn-primary\"> " . __("Edit") . "</button></td>";
				echo "<td><a href=\"" . site_url('radio/delete') . "/" . $row->id . "\" class=\"btn btn-sm btn-danger\"> <i class=\"fas fa-trash-alt\"></i> " . __("Delete") . "</a></td>";
				echo "</tr>";
			}
			echo "</tbody>";
		} else {
			// No radios found - show WebSocket button
			if ($default_user_radio === 'ws') {
				$websocket_button = '<button id="default_radio_btn_ws" type="button" class="btn btn-sm btn-primary mt-2 ld-ext-right d-block mx-auto" onclick="release_default_radio(\'ws\')">' . __("WebSocket is currently default (click to release)") . '<div class="ld ld-ring ld-spin"></div></button>';
			} else {
				$websocket_button = '<button id="default_radio_btn_ws" type="button" class="btn btn-sm btn-primary mt-2 ld-ext-right d-block mx-auto" onclick="set_default_radio(\'ws\')">' . __("Set WebSocket as default radio") . '<div class="ld ld-ring ld-spin"></div></button>';
			}
			echo "<thead><tr>";
			echo "<td colspan=\"6\"><div class=\"alert alert-info text-center\">";
			echo __("No CAT interfaced radios found.");
			echo "<p>" . __("You can still set the WebSocket option as your default radio.") . "</p>";
			echo $websocket_button;
			echo "</div></td>";
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
					echo json_encode($this->cat->format_status($row), JSON_PRETTY_PRINT);
				}
			}
		}
	}

	function delete($id) {

		$clean_id = $this->security->xss_clean($id);

		// Check Auth
		if (!$this->user_model->authorize(3)) {
			$this->session->set_flashdata('error', __("You're not allowed to do that!"));
			redirect('dashboard');
		}

		$this->load->model('cat');

		$this->cat->delete($clean_id);

		if ($clean_id == $this->user_options_model->get_options('cat', array('option_name' => $this->_get_optionname()), $this->_get_correct_uid())->row()->option_value ?? '') {
			$this->release_default_radio();
		}

		$this->session->set_flashdata('message', __("Radio removed successfully"));

		session_write_close();
		redirect('radio');
	}

	function set_default_radio() {

		// get the radio_id from POST
		$clean_radio_id = $this->input->post('radio_id', TRUE);
		
		// Check Auth
		if (!$this->user_model->authorize(3)) {
			$this->session->set_flashdata('error', __("You're not allowed to do that!"));
			redirect('dashboard');
		}

		// we unset the current default radio
		$this->release_default_radio();

		// Set the user_option and session data
		$this->user_options_model->set_option('cat', $this->_get_optionname(), array('radio_id' => $clean_radio_id), $this->_get_correct_uid());
		$this->session->set_userdata('radio', $clean_radio_id);
	}

	function release_default_radio() {
		// Check Auth
		if (!$this->user_model->authorize(3)) {
			$this->session->set_flashdata('error', __("You're not allowed to do that!"));
			redirect('dashboard');
		}

		// Unset the user_option and session data
		$this->user_options_model->del_option('cat', $this->_get_optionname(), NULL, $this->_get_correct_uid());
		$this->session->unset_userdata('radio');
	}

	private function _get_correct_uid() {
		if ($this->_is_clubstation()) {
			return $this->session->userdata('source_uid');
		} else {
			return $this->session->userdata('user_id');
		}
	}

	private function _is_clubstation() {
		return $this->session->userdata('clubstation') == 1;
	}

	private function _get_optionname() {
		if ($this->_is_clubstation() && ($this->session->userdata('source_uid') ?? '') != '') {
			return 'default_clubradio_' . $this->session->userdata('user_id');
		} else {
			return 'default_radio';
		}
	}
}
