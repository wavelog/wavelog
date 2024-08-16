<?php

if ( ! defined('BASEPATH')) exit('No direct script access allowed');


/*
	This controller will contain features for contesting
*/

class Themes extends CI_Controller {

	function __construct()
	{
		parent::__construct();

		$this->load->model('user_model');
		if(!$this->user_model->authorize(2)) { $this->session->set_flashdata('error', __("You're not allowed to do that!")); redirect('dashboard'); }
	}

	public function index()
	{
		$this->load->model('Themes_model');

		$data['themes'] = $this->Themes_model->getThemes();

		// Render Page
		$data['page_title'] = __("Themes");
		$this->load->view('interface_assets/header', $data);
		$this->load->view('themes/index.php');
		$this->load->view('interface_assets/footer');
	}

	public function add()
	{
		$this->load->model('Themes_model');
		$this->load->library('form_validation');

		$this->form_validation->set_rules('name', 'Theme Name', 'required|callback_character_check');
		$this->form_validation->set_rules('foldername', 'Folder Name', 'required|callback_character_check');
		$this->form_validation->set_rules('theme_mode', 'Theme Mode', 'required|callback_character_check');
		$this->form_validation->set_rules('header_logo', 'Header Logo', 'required|callback_character_check');
		$this->form_validation->set_rules('main_logo', 'Main Logo', 'required|callback_character_check');

		if ($this->form_validation->run() == FALSE)
		{
			$data['page_title'] = __("Create Theme");
			$this->load->view('themes/add', $data);
		}
		else
		{
			$this->Themes_model->add();
		}
	}

	public function edit($id)
	{
		$this->load->library('form_validation');

		$this->load->model('Themes_model');

		$item_id_clean = $this->security->xss_clean($id);

		$data['theme'] = $this->Themes_model->theme($item_id_clean);

		$data['page_title'] = __("Edit Theme");

		$this->form_validation->set_rules('name', 'Theme Name', 'required|callback_character_check');
		$this->form_validation->set_rules('foldername', 'Folder Name', 'required|callback_character_check');
		$this->form_validation->set_rules('theme_mode', 'Theme Mode', 'required|callback_character_check');
		$this->form_validation->set_rules('header_logo', 'Header Logo', 'required|callback_character_check');
		$this->form_validation->set_rules('main_logo', 'Main Logo', 'required|callback_character_check');

		if ($this->form_validation->run() == FALSE)
		{
			$this->load->view('themes/edit', $data);
		}
		else
		{
			$this->Themes_model->edit($item_id_clean);

			$this->session->set_flashdata("success", "Theme updated");

			redirect('themes');
		}
	}

	public function delete() {
		$id = $this->input->post('id');
		$this->load->model('Themes_model');
		$this->Themes_model->delete($id);
	}

	function character_check() {
		$input_name = $this->input->post('name');
		$input_foldername = $this->input->post('foldername');
		$input_theme_mode = $this->input->post('theme_mode');
		$input_header_logo = $this->input->post('header_logo');
		$input_main_logo = $this->input->post('main_logo');

		if ($input_name !== null && preg_match('/^[^\/:\*\?"<>\|@.]*$/', $input_name)) {
			return true;
		} else {
			$this->session->set_flashdata('danger', 'Invalid value for ' . $input_name . '.');
			return false;
		}

		if ($input_foldername !== null && preg_match('/^[^\/:\*\?"<>\|@.]*$/', $input_foldername)) {
			return true;
		} else {
			$this->session->set_flashdata('danger', 'Invalid value for ' . $input_foldername . '.');
			return false;
		}

		if ($input_theme_mode !== null && preg_match('/^[^\/:\*\?"<>\|@.]*$/', $input_theme_mode)) {
			return true;
		} else {
			$this->session->set_flashdata('danger', 'Invalid value for ' . $input_theme_mode . '.');
			return false;
		}

		if ($input_header_logo !== null && preg_match('/^[^\/:\*\?"<>\|@.]*$/', $input_header_logo)) {
			return true;
		} else {
			$this->session->set_flashdata('danger', 'Invalid value for ' . $input_header_logo . '.');
			return false;
		}

		if ($input_main_logo !== null && preg_match('/^[^\/:\*\?"<>\|@.]*$/', $input_main_logo)) {
			return true;
		} else {
			$this->session->set_flashdata('danger', 'Invalid value for ' . $input_main_logo . '.');
			return false;
		}
	}
		
}
