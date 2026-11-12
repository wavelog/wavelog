<?php

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Migration: add the "Blue Monster" and "Darkly Ultrawide" themes
 *
 * Registers new dark themes in the `themes` table:
 *  - Blue Monster (Dark)             -> assets/css/bluemonster/normal/
 *  - Blue Monster wide (Dark)        -> assets/css/bluemonster/wide/
 *  - Blue Monster Ultrawide (Dark)   -> assets/css/bluemonster/ultrawide/
 *  - Darkly Ultrawide (Dark)         -> assets/css/darkly_ultrawide/
 *
 * All reuse the Darkly logo assets, so no new files are needed besides the
 * CSS folders that ship with this change.
 *
 * Usage: called by the CI_Migration runner during "php index.php migrate/latest".
 *
 * @package    Wavelog migrations
 * @subpackage Themes
 * @category   Migrations
 * @author     Wavelog
 * @link       https://github.com/wavelog/wavelog
 */
class Migration_add_new_themes extends CI_Migration {

    public function up() {
        $themes = array(
            array(
                'name' => 'Blue Monster (Dark)',
                'foldername' => 'bluemonster/normal',
                'theme_mode' => 'dark',
                'header_logo' => 'wavelog_icon_only_darkly',
                'main_logo' => 'wavelog_logo_darkly',
            ),
            array(
                'name' => 'Blue Monster wide (Dark)',
                'foldername' => 'bluemonster/wide',
                'theme_mode' => 'dark',
                'header_logo' => 'wavelog_icon_only_darkly_wide',
                'main_logo' => 'wavelog_logo_darkly_wide',
            ),
            array(
                'name' => 'Blue Monster Ultrawide (Dark)',
                'foldername' => 'bluemonster/ultrawide',
                'theme_mode' => 'dark',
                'header_logo' => 'wavelog_icon_only_darkly_wide',
                'main_logo' => 'wavelog_logo_darkly_wide',
            ),
            array(
                'name' => 'Darkly Ultrawide (Dark)',
                'foldername' => 'darkly_ultrawide',
                'theme_mode' => 'dark',
                'header_logo' => 'wavelog_icon_only_darkly_wide',
                'main_logo' => 'wavelog_logo_darkly_wide',
            ),
        );

        foreach ($themes as $theme) {
            if ($this->entrycheck($theme['foldername']) == 0) {
                $sql = "INSERT INTO `themes` (`name`, `foldername`, `theme_mode`, `header_logo`, `main_logo`) VALUES (?, ?, ?, ?, ?)";
                $this->dbtry($sql, array($theme['name'], $theme['foldername'], $theme['theme_mode'], $theme['header_logo'], $theme['main_logo']));
            }
        }

        $this->invalidate_theme_cache();
    }

    public function down() {
        $folders = array('bluemonster', 'bluemonster/normal', 'bluemonster/wide', 'bluemonster/ultrawide', 'darkly_ultrawide');
        $placeholders = implode(',', array_fill(0, count($folders), '?'));
        $this->dbtry("DELETE FROM `themes` WHERE `foldername` IN ($placeholders)", $folders);
        $this->dbtry("UPDATE `users` SET `user_stylesheet` = 'default' WHERE `user_stylesheet` IN ($placeholders)", $folders);

        $this->invalidate_theme_cache();
    }

    /**
     * Run a query with bound parameters and log failures instead of
     * aborting the migration run.
     *
     * @param  string $sql      SQL statement with ? placeholders
     * @param  array  $bindings values bound to the placeholders
     * @return void
     */
    function dbtry($sql, $bindings = array())
    {
        try {
            $this->db->query($sql, $bindings);
        } catch (Exception $e) {
            log_message("error", "Something gone wrong while altering the themes table: ".$e." // Executing: ".$this->db->last_query());
        }
    }

    /**
     * Count existing theme rows for a foldername so up() stays idempotent.
     *
     * @param  string $foldername theme folder to look for
     * @return int    number of matching rows
     */
    function entrycheck($foldername)
    {
        $query = $this->db->query("SELECT * FROM `themes` WHERE `foldername` = ?", array($foldername));
        return $query->num_rows();
    }

    /**
     * Drop the cached theme list so the new themes show up immediately
     * (User_model caches getThemes() under 'user_themes').
     *
     * @return void
     */
    function invalidate_theme_cache()
    {
        $this->load->is_loaded('cache') ?: $this->load->driver('cache', [
            'adapter' => $this->config->item('cache_adapter') ?? 'file',
            'backup' => $this->config->item('cache_backup') ?? 'file',
            'key_prefix' => $this->config->item('cache_key_prefix') ?? ''
        ]);
        $this->cache->delete('user_themes');
    }

}
