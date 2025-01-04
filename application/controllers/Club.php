<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Club extends CI_Controller
{
	/** 
	 * Permission Levels:
	 * 9 - Officer
	 * 3 - Member
	 * 
	 * These permission levels are independent of the codeigniter permission levels and managed in the club_permissions table!
	 * https://github.com/wavelog/wavelog/wiki/Clubstations
	*/

	/**
     * @var array $permissions
     */
    private $permissions;

	function __construct() {
		parent::__construct();

		$this->permissions = [
			9 => __("Club Officer"),
			3 => __("Club Member"),
		];
	}

    public function index()
    {
        // nothing to display
        redirect('dashboard');
    }

    public function permissions($club_id) {

		$this->load->model('user_model');
		$this->load->model('club_model');
		$this->load->library('form_validation');

		$cid = $this->security->xss_clean($club_id);
		$club = $this->user_model->get_by_id($cid)->row();

		if (!is_numeric($cid)) {
			$this->session->set_flashdata('error', __("Invalid User ID!"));
			redirect('user');
		}
		if(!$this->user_model->authorize(99) && !$this->club_model->club_authorize(9, $cid)) { 
			$this->session->set_flashdata('error', __("You're not allowed to do that!")); 
			redirect('dashboard'); 
		}
		if ($club->clubstation != 1) {
			$this->session->set_flashdata('error', __("This user is not a club station."));
			redirect('user');
		}

		$data['page_title'] = __("Club Permissions");
		$data['club'] = $club;
		$data['club_members'] = $this->club_model->get_club_members($cid);
		$data['permissions'] = $this->permissions;

		$footerData = [];
		$footerData['scripts'] = [
			'assets/js/sections/club_permissions.js?' . filemtime(realpath(__DIR__ . "/../../assets/js/sections/club_permissions.js")),
		];

		$this->load->view('interface_assets/header', $data);
		$this->load->view('club/permissions');
		$this->load->view('interface_assets/footer', $footerData);
	}

	public function get_users() {
		
		if(!clubaccess_check(9)) { 
			$this->session->set_flashdata('error', __("You're not allowed to do that!")); 
			redirect('dashboard'); 
		}
		if (!$this->load->is_loaded('user_model')) {
			$this->load->model('user_model');
		}

		$query = (string) $this->input->post('query', true) ?? '';
		if (empty($query)) {
			header('Content-Type: application/json');
			echo json_encode([]);
			return;
		}

		$users = $this->user_model->search_users($query);

		$result = [];

		if ($users != false) {
			foreach ($users->result() as $user) {
				$result[] = [
					'user_id' => $user->user_id,
					'user_name' => $user->user_name,
					'user_callsign' => $user->user_callsign,
					'user_firstname' => $user->user_firstname,
					'user_lastname' => $user->user_lastname
				];
			}
		}

		header('Content-Type: application/json');
    	echo json_encode($result);
	}

	public function alter_member() {
		
		$this->load->model('user_model');
		$this->load->model('club_model');

		$club_id = $this->input->post('club_id', true);
		$user_id = $this->input->post('user_id', true);
		$p_level = $this->input->post('permission', true);

		if (!is_numeric($club_id)) {
			$this->session->set_flashdata('error', __("Invalid Club ID!"));
			redirect('dashboard'); 
		}
		if(!$this->user_model->authorize(99) && !$this->club_model->club_authorize(9, $club_id)) { 
			$this->session->set_flashdata('error', __("You're not allowed to do that!")); 
			redirect('dashboard'); 
		}

		$this->club_model->alter_member($club_id, $user_id, $p_level);

		if ($this->input->post('notify_user', true) == 'on') {
			if (!$this->notification($user_id, $club_id, $this->input->post('notify_message', true))) {
				$this->session->set_flashdata('error', __("User could not be notified. Please check your email settings."));
			}
		}
		
		$this->session->set_flashdata('success', __("Club member permissions have been updated."));
		redirect('club/permissions/'.$club_id);
	}

	public function delete_member() {
		
		$this->load->model('user_model');
		$this->load->model('club_model');

		$club_id = $this->input->post('club_id', true);
		$user_id = $this->input->post('user_id', true);

		if (!is_numeric($club_id)) {
			$this->session->set_flashdata('error', __("Invalid Club ID!"));
			redirect('dashboard'); 
		}
		if(!$this->user_model->authorize(99) && !$this->club_model->club_authorize(9, $club_id)) { 
			$this->session->set_flashdata('error', __("You're not allowed to do that!")); 
			redirect('dashboard'); 
		}

		if ($this->club_model->delete_member($club_id, $user_id)) {
			$this->session->set_flashdata('success', __("User removed from club."));
		} else {
			$this->session->set_flashdata('error', __("User could not be removed from club."));
		}
		redirect('club/permissions/'.$club_id);
	}

	public function switch_modal() {
		
		$this->load->model('user_model');
		$this->load->model('club_model');
		$this->load->library('encryption');

		$cid = $this->input->post('club_id', true);
		$data['club_callsign'] = $this->input->post('club_callsign', true);
		$user_id = $this->session->userdata('user_id');

		if (!is_numeric($cid)) {
			$this->session->set_flashdata('error', __("Invalid Club ID!"));
			redirect('dashboard'); 
		}
		if(!$this->club_model->club_authorize(3, $cid)) { 
			$this->session->set_flashdata('error', __("You're not allowed to do that!")); 
			redirect('dashboard'); 
		}

		$data['impersonate_hash'] = $this->encryption->encrypt($user_id . '/' . $cid . '/' . time());

		$this->load->view('club/clubswitch_modal', $data);
	}

	private function notification($user_id, $club_id, $message) {

		$this->load->library('email');
		$this->load->model('club_model');

		switch ($message) {
			case 'new_member':
				$view = 'email/club/new_member';
				break;
			case 'modified_member':
				$view = 'email/club/modified_member';
				break;
			default:
				log_message('error', "Club Notification; Can't notify user - Invalid message type.");
				$this->session->set_flashdata('error', __("Invalid message type."));
				redirect('club/permissions/'.$club_id);
		}
		
		if($this->optionslib->get_option('emailProtocol') == "smtp") {
			$config = [
				'protocol' => $this->optionslib->get_option('emailProtocol'),
				'smtp_crypto' => $this->optionslib->get_option('smtpEncryption'),
				'smtp_host' => $this->optionslib->get_option('smtpHost'),
				'smtp_port' => $this->optionslib->get_option('smtpPort'),
				'smtp_user' => $this->optionslib->get_option('smtpUsername'),
				'smtp_pass' => $this->optionslib->get_option('smtpPassword'),
				'crlf' => "\r\n",
				'newline' => "\r\n"
			];
			$this->email->initialize($config);

		} else {
			log_message('error', "Club Notification; Can't notify user - Email settings not configured.");
			$this->session->set_flashdata('error', __("Email settings not configured."));
			redirect('club/permissions/'.$club_id);
		}

		$user = $this->user_model->get_by_id($user_id)->row();
		$club = $this->user_model->get_by_id($club_id)->row();
		$permission = $this->club_model->get_permission($club_id, $user_id);
		$permission_level = $this->permissions[$permission] ?? __("Unknown");

		$mail_data['user_callsign'] = $user->user_callsign;
		$mail_data['club_callsign'] = $club->user_callsign;
		$mail_data['permission_level'] = $permission_level;

		$message = $this->email->load($view, $mail_data, $user->user_language);

		$this->email->from($this->optionslib->get_option('emailAddress'), $this->optionslib->get_option('emailSenderName'));
		$this->email->to($user->user_email);
		$this->email->subject($message['subject']);
		$this->email->message($message['body']);

		return $this->email->send();
	}
	
}
