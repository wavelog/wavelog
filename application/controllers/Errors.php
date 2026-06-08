<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
	Controller for custom error pages.

	Wired up via $route['404_override'] in config/routes.php, so it runs as a
	normal controller. That means the gettext hook has already run (__() is
	available) and autoloaded libraries like optionslib are ready.
*/

class Errors extends CI_Controller {

	function __construct() {
		parent::__construct();
	}

	// 404 page - rendered for any URI that doesn't match a route.
	public function show_404() {

		$data['theme'] = $this->optionslib->get_theme();
		$data['logo'] = $this->paths->cache_buster('/assets/logo/'.$this->optionslib->get_logo('header_logo').'.png');
		$data['heading'] = __("Page Not Found");
		$data['message1'] = __("QRZ? ... no reply.");
		$data['message2'] = __("Nobody is transmitting on this frequency.");

		// Keep the HTTP status correct (404_override otherwise yields 200).
		$this->output->set_status_header(404);

		$this->load->view('errors/html/error_404', $data);
	}
}
