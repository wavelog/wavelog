<?php

class Club_model extends CI_Model {

    /**
     * Authorization for Club Features
     * 
     * Permission Levels:
     * 9 - Club Officer
     * 3 - Club Member (normal Operator)
     * 
     * These permission levels are independent of the codeigniter permission levels and managed in the club_permissions table!
     * // TODO: Add a Wiki Link to the Permission Levels and Explainations about the Club Features
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
        $this->load->model('user_model');
        if ($this->user_model->authorize(99)) {
            return true;
        }

        if ($user_id == NULL || !is_numeric($user_id)) {
            $user_id = $this->session->userdata('user_id');
        } else {
            $user_id = xss_clean($user_id);
        }

        // Now we can check the database for permissions
        $binding = [];
        $sql = 'SELECT * FROM `club_permissions` WHERE user_id = ? AND club_id = ? AND level >= ?';
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
     * Get Club Members
     * 
     * @param int $club_id
     * 
     * @return array
     */
    function get_club_members($club_id) {

        $sql = 'SELECT  users.user_id, users.user_callsign, users.user_name, users.user_firstname, users.user_lastname, users.user_email, club_permissions.p_level 
                FROM club_permissions 
                JOIN users ON club_permissions.user_id = users.user_id 
                WHERE club_permissions.club_id = ?;';

        $members = $this->db->query($sql, [$club_id])->result();

        return $members;
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
    function add_member($club_id, $user_id, $p_level) {

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
}
