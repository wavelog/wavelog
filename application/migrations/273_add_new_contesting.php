<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Migration_add_new_contesting extends CI_Migration
{
    public function up() {
        // Instead modify existing tables, we create new ones for the new contesting system.
        $this->dbtry("DROP TABLE IF EXISTS `contest_session`;");
        $this->dbtry("CREATE TABLE `contest_session` (
            `id` int(20) unsigned NOT NULL AUTO_INCREMENT,
            `user_id` int(10) unsigned NOT NULL,
            `time_start` datetime NOT NULL,
            `time_end` datetime NOT NULL,
            `station_id` int(10) unsigned NOT NULL,
            `comment` varchar(255) DEFAULT NULL,
            `contest_adif_id` int unsigned NOT NULL,
            `creation_date` timestamp NOT NULL DEFAULT current_timestamp(),
            `last_modified` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");

        // We need a table which refeers qsos to contest sessions
        $this->dbtry("CREATE TABLE `contest_qsos` (
            `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            `contest_session_id` int(20) unsigned NOT NULL,
            `qso_id` bigint(20) unsigned NOT NULL,
            PRIMARY KEY (`id`),
            KEY `idx_contest_session_id` (`contest_session_id`),
            KEY `idx_qso_id` (`qso_id`),
            CONSTRAINT `fk_contest_qsos_qso` FOREIGN KEY (`qso_id`) REFERENCES `".$this->config->item('table_name')."` (`COL_PRIMARY_KEY`) ON DELETE CASCADE,
            CONSTRAINT `fk_contest_qsos_session` FOREIGN KEY (`contest_session_id`) REFERENCES `contest_session` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
    }

    public function down() {        
        // Drop the contest_qsos table
        $this->dbtry("DROP TABLE IF EXISTS `contest_qsos`;");

        // Drop the contest_session to revert to old contesting system
        $this->dbtry("DROP TABLE IF EXISTS `contest_session`;");
        $this->dbtry("CREATE TABLE `contest_session` (
            `id` int(20) unsigned NOT NULL AUTO_INCREMENT,
            `contestid` varchar(100) NOT NULL,
            `exchangesent` varchar(20) NOT NULL,
            `serialsent` varchar(20) NOT NULL,
            `qso` varchar(100) NOT NULL,
            `station_id` int(10) unsigned NOT NULL,
            `settings` text,
            `creation_date` timestamp NOT NULL DEFAULT current_timestamp(),
            `last_modified` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
    }

    function dbtry($what)
    {
        try {
            $this->db->query($what);
        } catch (Exception $e) {
            log_message("error", "Error setting up the new contesting db structure: " . $e . " // Executing: " . $this->db->last_query());
        }
    }
}
