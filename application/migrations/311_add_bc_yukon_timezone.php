<?php

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Migration: add permanent GMT-07:00 timezone entry for British Columbia / Yukon
 *
 * Adds a static (GMT-07:00) entry to the `timezones` table so users in regions
 * that abandoned DST (British Columbia, Yukon) can select a correctly named,
 * year-round -07:00 offset. See GitHub issue #4077.
 *
 * Usage: called by the CI_Migration runner during "php index.php migrate/latest".
 *
 * @package    Wavelog migrations
 * @subpackage Timezones
 * @category   Migrations
 * @author     Wavelog
 * @link       https://github.com/wavelog/wavelog/issues/4077
 */
class Migration_add_bc_yukon_timezone extends CI_Migration {

    /**
     * Insert the new timezone row if it is not already present.
     *
     * @return void
     */
    public function up()
    {
        if ($this->db->table_exists('timezones')) {
            $this->db->where('name', '(GMT-07:00)-Pacific Time (Canada); British Columbia; Yukon');
            $query = $this->db->get('timezones');

            if ($query->num_rows() == 0) {
                $this->db->insert('timezones', array(
                    'offset' => -7.0,
                    'name' => '(GMT-07:00)-Pacific Time (Canada); British Columbia; Yukon'
                ));
            }
        }
    }

    /**
     * Remove the timezone row added by up().
     *
     * @return void
     */
    public function down()
    {
        if ($this->db->table_exists('timezones')) {
            $this->db->where('name', '(GMT-07:00)-Pacific Time (Canada); British Columbia; Yukon');
            $this->db->delete('timezones');
        }
    }

}
