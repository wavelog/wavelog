<?php

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 *  Create URL "slug" for each user that serves as an alias for a user_id.
 *  The purpose is to have user identifier for use in public URLs (i.e. for widget purposes)
 *  that is bound to one concrete user, as the widgets might contain privacy-sensitive data.
 *  This slug is to "mask" the user_id in lieu of avoiding brute-force iteration over
 *  user_ids in URL that might be available in particular Wavelog instance.
 */
class Migration_user_callsign_public_slug extends CI_Migration {

    public function up()
    {
        // Step 1: Add user slug column
        if (!$this->db->field_exists('slug', 'users')) {
            $fields = array(
                'slug varchar(50) DEFAULT NULL'
            );
            $this->dbforge->add_column('users', $fields);
        }

        // Step 2: Create public user-slug for each user
        if (!$this->load->is_loaded('encryption')) {
            $this->load->library('encryption');
        }

        $fetch_result = $this->db->get($this->config->item('auth_table'));
        if ($fetch_result && $fetch_result->num_rows() > 0) {
            foreach ($fetch_result->result_array() as $user_row) {
                if ($user_row["slug"] === null) {
                    $user_name = $user_row["user_name"];
                    $user_slug_base = md5($this->encryption->encrypt($user_name));
                    $url_slug = substr($user_slug_base, 0, USER_SLUG_LENGTH);

                    // update the slug only in case the slug does not exist yet
                    $user_id = $user_row["user_id"];
                    $this->db->where('user_id', $user_id);
                    $this->db->update('users', array('slug' => $url_slug));
                }
            }
        }

        // Step 3: Make user slug unique
        $this->db->query("ALTER TABLE `users` modify `slug` varchar(50) NOT NULL");
        $this->db->query("ALTER TABLE `users` ADD CONSTRAINT `user_slug_unique` UNIQUE (`slug`)");
    }

    public function down()
    {
        if ($this->db->field_exists('slug', 'users')) {
            $this->dbforge->drop_column('users', 'slug');
        }
    }
}
