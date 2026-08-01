<?php

class Club_model extends CI_Model {

    /**
     * Permission levels a club member can hold, level => label.
     *
     * @return array
     */
    function permission_levels() {
        return [
            9 => __("Club Officer"),
            6 => __("Club Member ADIF"),
            3 => __("Club Member"),
        ];
    }

    /**
     * Whether this clubstation's memberships are managed by an identity
     * provider. 
     *
     * @param int $club_id
     *
     * @return boolean
     */
    function is_sso_managed($club_id) {

        if (!($this->config->item('auth_header_enable') ?? false)) {
            return false;
        }

        $this->config->load('sso', true, true);
        $directs = $this->config->item('auth_header_clubstation_direct', 'sso') ?: [];

        return key_exists($club_id, $directs);
    }

    /**
     * Authorization for Club Features
     * 
     * @param int $level
     * @param int $club_id
     * @param int $user_id (optional)
     * 
     * @return boolean
     */
    function club_authorize($level, $club_id, $user_id = NULL) {

        if ($level == 0 || !is_numeric($level)) {
            log_message('error', 'Club Authorization Level not set!');
            return false;
        }

        if ($club_id == 0 || !is_numeric($club_id)) {
            $this->session->set_flashdata('error', __("Invalid Club ID!"));
            redirect('dashboard');
        }

        // admin is always allowed
        if ($user_id != NULL) {
            if ($this->user_model->get_by_id($user_id)->row()->user_type == 99) {
                return true;
            }
        }

        if ($user_id == NULL || !is_numeric($user_id)) {
            $user_id = $this->session->userdata('user_id');
        } else {
            $user_id = xss_clean($user_id);
        }

        // Now we can check the database for permissions
        $binding = [];
        $sql = 'SELECT * FROM `club_permissions` WHERE user_id = ? AND club_id = ? AND p_level >= ?';
        $binding[] = $user_id;
        $binding[] = $club_id;
        $binding[] = $level;

        $query = $this->db->query($sql, $binding);

        if ($query->num_rows() > 0) {
            return true;
        } else {
            $this->session->set_flashdata('error', __("You're not allowed to do that!"));
            redirect('dashboard');
        }

        return false;
    }

    /**
     * Get Permissionlevel for User in Club in a real model-way without UI
     * 
     * @param int $club_id
     * @param int $user_id
     * 
     * @return int
     */
    function get_permission_noui($club_id, $user_id) {

        if ($club_id == 0 || !is_numeric($club_id)) {
		return 0;
        }

        if ($user_id == 0 || !is_numeric($user_id)) {
		return 0;
        }

        $binding = [];
        $sql = 'SELECT p_level FROM `club_permissions` WHERE user_id = ? AND club_id = ?';
        $binding[] = $user_id;
        $binding[] = $club_id;

        $query = $this->db->query($sql, $binding);

        if ($query->num_rows() > 0) {
            return $query->row()->p_level;
        } else {
            return 0;
        }
    }

    /**
     * Get Permissionlevel for User in Club
     * 
     * @param int $club_id
     * @param int $user_id
     * 
     * @return int
     */
    function get_permission($club_id, $user_id) {

        if ($club_id == 0 || !is_numeric($club_id)) {
            $this->session->set_flashdata('error', __("Invalid Club ID!"));
            redirect('dashboard');
        }

        if ($user_id == 0 || !is_numeric($user_id)) {
            $this->session->set_flashdata('error', __("Invalid User ID!"));
            redirect('dashboard');
        }

        $binding = [];
        $sql = 'SELECT p_level FROM `club_permissions` WHERE user_id = ? AND club_id = ?';
        $binding[] = $user_id;
        $binding[] = $club_id;

        $query = $this->db->query($sql, $binding);

        if ($query->num_rows() > 0) {
            return $query->row()->p_level;
        } else {
            return 0;
        }
    }

    /**
     * Get Club Members
     * 
     * @param int $club_id
     * 
     * @return array
     */
    function get_club_members($club_id) {

        $sql = 'SELECT  users.user_id, users.user_type, users.user_callsign, users.user_name, users.user_firstname, users.user_lastname, users.user_locator, users.user_email, club_permissions.p_level, users.user_language
                FROM club_permissions 
                JOIN users ON club_permissions.user_id = users.user_id 
                WHERE club_permissions.club_id = ?;';

        $members = $this->db->query($sql, [$club_id])->result();

        return $members;
    }

    /**
     * Get available Clubstations per User
     * 
     * @param int $user_id
     * 
     * @return array
     */
    function get_clubstations($user_id) {

        $sql = 'SELECT users.user_id, users.user_callsign, club_permissions.p_level
                FROM club_permissions 
                JOIN users ON club_permissions.club_id = users.user_id
                WHERE club_permissions.user_id = ?;';

        $clubs = $this->db->query($sql, [$user_id])->result();

        return $clubs;
    }

    /**
     * 
     * Add Club Member
     * 
     * @param int $club_id
     * @param int $user_id
     * @param int $p_level
     * 
     * @return boolean
     */
    function alter_member($club_id, $user_id, $p_level) {

        if ($club_id == 0 || !is_numeric($club_id)) {
            $this->session->set_flashdata('error', __("Invalid Club ID!"));
            redirect('dashboard');
        }

        if ($user_id == 0 || !is_numeric($user_id)) {
            $this->session->set_flashdata('error', __("Invalid User ID!"));
            redirect('dashboard');
        }

        if ($p_level == 0 || !is_numeric($p_level)) {
            $this->session->set_flashdata('error', __("Invalid Permission Level!"));
            redirect('dashboard');
        }

        $binding = [];
        $sql = "INSERT INTO club_permissions (club_id, user_id, p_level)
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE p_level = VALUES(p_level)";
        $binding[] = $club_id;
        $binding[] = $user_id;
        $binding[] = $p_level;

        if ($this->db->query($sql, $binding)) {
            return true;
        } else {
            $this->session->set_flashdata('error', __("Error adding Club Member!"));
            redirect('club/permissions/' . $club_id);
        }
    }

    /**
     * 
     * Delete Club Member
     * 
     * @param int $club_id
     * @param int $user_id
     * 
     * @return boolean
     */
    function delete_member($club_id, $user_id) {

        if ($club_id == 0 || !is_numeric($club_id)) {
            $this->session->set_flashdata('error', __("Invalid Club ID!"));
            redirect('dashboard');
        }

        if ($user_id == 0 || !is_numeric($user_id)) {
            $this->session->set_flashdata('error', __("Invalid User ID!"));
            redirect('dashboard');
        }

        try {
            $this->db->query('DELETE FROM club_permissions WHERE club_id = ? AND user_id = ?', [$club_id, $user_id]);
            $this->db->query('DELETE FROM api WHERE user_id = ? AND created_by = ?', [$club_id, $user_id]);
            // The v2 tokens in `api_token` deliberately survive: the dispatcher
            // re-checks the membership on every request and answers 403
            // club_access_revoked, so re-adding the member restores access
            // without them having to create a new token.
            $this->db->query('DELETE FROM cat WHERE user_id = ? AND operator = ?', [$club_id, $user_id]);
            return true;
        } catch (Exception $e) {
            log_message('error', 'Error deleting Club Member: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Notify a club member about their new or changed permission level.
     *
     * @param int    $user_id
     * @param int    $club_id
     * @param string $message 'new_member' or 'modified_member'
     *
     * @return boolean
     */
    function notify_member($user_id, $club_id, $message) {

        $this->load->library('email');

        switch ($message) {
            case 'new_member':
                $view = 'email/club/new_member';
                break;
            case 'modified_member':
                $view = 'email/club/modified_member';
                break;
            default:
                log_message('error', "Club Notification; Can't notify user - Invalid message type.");
                return false;
        }

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
        if (!$this->email->initialize($config)) {
            log_message('error', "Club Notification; Can't notify user - Email can't be initialized.");
            return false;
        }

        $user = $this->user_model->get_by_id($user_id)->row();
        $club = $this->user_model->get_by_id($club_id)->row();
        $permission = $this->get_permission_noui($club_id, $user_id);
        $permission_level = $this->permission_levels()[$permission] ?? __("Unknown");

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
