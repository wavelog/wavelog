<?php

class Debug_model extends CI_Model
{

    private $userdata_dir;
    private $flag_file;

    private $src_eqsl;
    private $eqsl_dir;

    private $src_qsl;
    private $qsl_dir;

    public function __construct()
    {
        $this->userdata_dir = $this->config->item('userdata');
        $this->flag_file = '.migrated'; // we use this flag file to determine if the migration already run through

        $this->src_eqsl = 'images/eqsl_card_images';
        $this->eqsl_dir = 'eqsl_card';  // make sure this is the same as in Paths.php function getUserdataPath()

        $this->src_qsl = 'assets/qslcard';
        $this->qsl_dir = 'qsl_card';  // make sure this is the same as in Paths.php function getUserdataPath()
    }

    function migrate_userdata()
    {

        $this->load->model('Logbook_model');

        $allowed_file_extensions = ['jpg', 'jpeg', 'gif', 'png'];

        // *****   EQSL   ***** //

        // Let's scan the whole folder and get necessary data for each file
        foreach (scandir($this->src_eqsl) as $file) {
            // Ignore files if they are not jpg, png or gif
            $file_extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if (!in_array($file_extension, $allowed_file_extensions)) continue;

            if (!is_readable($this->src_eqsl . '/' . $file)) continue;
            if ($file != '.' && $file != '..') {

                // we need the qso_id from the database to get the necessary user_id
                $qso_id = $this->get_qsoid_from_eqsl_filename($file) ?? '';

                // check if the qso_id is empty, if yes we create a folder 'not assigned' instead of 'user_id'
                if (!empty($qso_id)) {
                    // get the user_id for this qso_id
                    $get_user_id = $this->Logbook_model->get_user_id_from_qso($qso_id);

                    // it can happen that the user_id is empty even there is a qso_id (deleted qso or deleted user)
                    if(!empty($get_user_id)) {
                        $user_id = $get_user_id;
                    } else {
                        $user_id = 'not_assigned';
                    }
                } else {
                    $user_id = 'not_assigned';
                }

                // make sure the target path exists
                $target = $this->userdata_dir . '/' . $user_id . '/' . $this->eqsl_dir;
                if (!file_exists(realpath(APPPATH . '../') . '/' . $target)) {
                    mkdir(realpath(APPPATH . '../') . '/' . $target, 0755, true);
                }

                // then copy the file
                if (!copy($this->src_eqsl . '/' . $file, $target . '/' . $file)) {
                    return false; // Failed to copy file
                }
            }
        }

        // *****   QSL Cards   ***** //

        // Let's scan the whole folder and get necessary data for each file
        foreach (scandir($this->src_qsl) as $file) {
            // Ignore files if they are not jpg, png or gif
            $file_extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if (!in_array($file_extension, $allowed_file_extensions)) continue;

            if (!is_readable($this->src_qsl . '/' . $file)) continue;
            if ($file != '.' && $file != '..') {

                // we need the qso_id from the database to get the necessary user_id
                $qso_id = $this->get_qsoid_from_qsl_filename($file) ?? '';

                // check if the qso_id is empty, if yes we create a folder 'not assigned' instead of 'user_id'
                if (!empty($qso_id)) {
                    // get the user_id for this qso_id
                    $get_user_id = $this->Logbook_model->get_user_id_from_qso($qso_id);

                    // it can happen that the user_id is empty even there is a qso_id (deleted qso or deleted user)
                    if(!empty($get_user_id)) {
                        $user_id = $get_user_id;
                    } else {
                        $user_id = 'not_assigned';
                    }
                } else {
                    $user_id = 'not_assigned';
                }

                // make sure the target path exists
                $target = $this->userdata_dir . '/' . $user_id . '/' . $this->qsl_dir;
                if (!file_exists(realpath(APPPATH . '../') . '/' . $target)) {
                    mkdir(realpath(APPPATH . '../') . '/' . $target, 0755, true);
                }

                // then copy the file
                if (!copy($this->src_qsl . '/' . $file, $target . '/' . $file)) {
                    return false; // Failed to copy file
                }
            }
        }

        // here we create the 'migrated' flag
        if (!file_exists(realpath(APPPATH . '../') . '/' . $this->userdata_dir . '/' . $this->flag_file)) {
            touch(realpath(APPPATH . '../') . '/' . $this->userdata_dir . '/' . $this->flag_file);
        }

        return true;
    }

    function check_migrated_flag()
    {
        if (!file_exists(realpath(APPPATH . '../') . '/' . $this->userdata_dir . '/' . $this->flag_file)) {
            return false;
        } else {
            return true;
        }
    }

    function get_qsoid_from_eqsl_filename($filename)
    {

        $sql = "SELECT qso_id FROM eQSL_images WHERE image_file = ?";

        $result = $this->db->query($sql, $filename);

        $row = $result->row();
        return $row->qso_id;
    }

    function get_qsoid_from_qsl_filename($filename)
    {

        $sql = "SELECT qsoid FROM qsl_images WHERE filename = ?";

        $result = $this->db->query($sql, $filename);

        $row = $result->row();
        return $row->qsoid;
    }

	// Returns the number of qso's total on this instance
	function count_all_qso() {
		$sql = 'SELECT COUNT(*) AS total FROM '. $this->config->item('table_name').' WHERE station_id IS NOT NULL;';
		$query = $this->db->query($sql);
		return $query->row()->total;
	}

    function count_users() {
        $sql = 'SELECT COUNT(*) AS total FROM users;';
        $query = $this->db->query($sql);
        return $query->row()->total;
    }

	function getMigrationVersion() {
        $this->db->select_max('version');
        $query = $this->db->get('migrations');
        $migration_version = $query->row();

        if ($query->num_rows() == 1) {
            $migration_version = $query->row()->version;
            return $migration_version;
        } else {
            return null;
        }
    }

	public function calls_without_station_id() {
		$query=$this->db->query("select distinct COL_STATION_CALLSIGN from ".$this->config->item('table_name')." where station_id is null or station_id = ''");
		$result = $query->result_array();
		return $result;
    }

    function get_cache_info() {

        $response = [];

        $cache_path = $this->config->item('cache_path') ?? NULL;
        if (!$cache_path && $cache_path !== '') {
            $cache_path = ''; // default path is application/cache
            $response['config']['cache_path'] = 'application/cache';
        } else {
            $response['config']['cache_path'] = $cache_path;
        }

        $cache_adapter = $this->config->item('cache_adapter') ?? NULL;
        if (!$cache_adapter) {
            $cache_adapter = 'file'; // default adapter is file
            $response['config']['cache_adapter'] = 'file';
        } else {
            $response['config']['cache_adapter'] = $cache_adapter;
        }

        $cache_backup = $this->config->item('cache_backup') ?? NULL;
        if (!$cache_backup) {
            $cache_backup = 'file'; // default backup is file
            $response['config']['cache_backup'] = 'file';
        } else {
            $response['config']['cache_backup'] = $cache_backup;
        }

        $cache_key_prefix = $this->config->item('cache_key_prefix') ?? NULL;
        if (!$cache_key_prefix) {
            $cache_key_prefix = ''; // default key prefix is empty
            $response['config']['cache_key_prefix'] = '';
        } else {
            $response['config']['cache_key_prefix'] = $cache_key_prefix;
        }

        // Load cache driver
		$this->load->driver('cache', [
			'adapter' => $cache_adapter, 
			'backup' => $cache_backup,
			'key_prefix' => $cache_key_prefix
		]);

        $active_adapter = method_exists($this->cache, 'get_loaded_driver') ? $this->cache->get_loaded_driver() : $cache_adapter;
        $response['active']['adapter'] = $active_adapter;
        $response['active']['using_backup'] = ($active_adapter !== $cache_adapter);

        // Get cache details
        $cache_size = $this->get_cache_size();
        $cache_keys_count = $this->get_cache_keys_count();
        
        $response['details']['size'] = $this->format_bytes($cache_size);
        $response['details']['size_bytes'] = $cache_size;
        $response['details']['keys_count'] = $cache_keys_count;

        $available_adapters = ['file', 'redis', 'memcached', 'apcu'];
        foreach ($available_adapters as $adapter) {
            // For redis, memcached and apcu we should check for the extension first to avoid unnecessary errors
            if (in_array($adapter, ['redis', 'memcached', 'apcu'])) {
                $is_supported = extension_loaded($adapter) ?? false;
                $response['adapters'][$adapter] = $is_supported;
                continue;
            }
            $response['adapters'][$adapter] = $this->cache->is_supported($adapter);
        }

        return $response;
    }

    public function clear_cache() {
        $cache_adapter = $this->config->item('cache_adapter') ?? 'file';
        $cache_backup = $this->config->item('cache_backup') ?? 'file';
        $cache_key_prefix = $this->config->item('cache_key_prefix') ?? '';

		$this->load->driver('cache', [
			'adapter' => $cache_adapter,
			'backup' => $cache_backup,
			'key_prefix' => $cache_key_prefix
		]);

        if (method_exists($this->cache, 'clean')) {
            return $this->cache->clean();
        }

        return false;
    }

    function get_cache_size($adapter = NULL) {
        $cache_adapter = $adapter ?? ($this->config->item('cache_adapter') ?? 'file');

        switch ($cache_adapter) {
            case 'file':
                $cache_path = $this->config->item('cache_path') ?: 'application/cache';
                $cache_path = realpath(APPPATH . '../') . '/' . $cache_path;
                
                if (!is_dir($cache_path)) {
                    return 0;
                }

                $size = 0;
                $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($cache_path, RecursiveDirectoryIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
                
                foreach ($files as $file) {
                    if ($file->isFile() && !in_array($file->getFilename(), ['index.html', '.htaccess'])) {
                        $size += $file->getSize();
                    }
                }
                
                return $size;

            case 'redis':
                if ($this->cache->is_supported('redis')) {
                    $redis_info = $this->cache->cache_info('redis');
                    // Note: This returns total Redis server memory usage, not just cache keys with prefix
                    // used_memory_dataset excludes overhead and is more accurate for data size
                    if (isset($redis_info['used_memory_dataset'])) {
                        return (int)$redis_info['used_memory_dataset'];
                    }
                    return isset($redis_info['used_memory']) ? (int)$redis_info['used_memory'] : 0;
                }
                return 0;

            case 'memcached':
                if ($this->cache->is_supported('memcached')) {
                    $memcached_info = $this->cache->cache_info('memcached');
                    
                    // Memcached returns array of servers, each with stats
                    if (is_array($memcached_info)) {
                        $total_bytes = 0;
                        foreach ($memcached_info as $server_stats) {
                            if (is_array($server_stats)) {
                                // bytes is the current bytes used in the cache
                                if (isset($server_stats['bytes'])) {
                                    $total_bytes += (int) $server_stats['bytes'];
                                }
                            }
                        }
                        return $total_bytes;
                    }
                    
                    // Fallback for single server format
                    if (isset($memcached_info['bytes'])) {
                        return (int) $memcached_info['bytes'];
                    }
                    
                    return 0;
                }
                return 0;

            case 'apcu':
                if ($this->cache->is_supported('apcu')) {
                    $apcu_info = apcu_cache_info();
                    return isset($apcu_info['mem_size']) ? (int)$apcu_info['mem_size'] : 0;
                }
                return 0;

            default:
                return 0;
        }
    }

    function get_cache_keys_count($adapter = NULL) {
        $cache_adapter = $adapter ?? ($this->config->item('cache_adapter') ?? 'file');

        switch ($cache_adapter) {
            case 'file':
                $cache_path = $this->config->item('cache_path') ?: 'application/cache';
                $cache_path = realpath(APPPATH . '../') . '/' . $cache_path;
                
                if (!is_dir($cache_path)) {
                    return 0;
                }

                $count = 0;
                $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($cache_path, RecursiveDirectoryIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
                
                foreach ($files as $file) {
                    if ($file->isFile() && !in_array($file->getFilename(), ['index.html', '.htaccess'])) {
                        $count++;
                    }
                }
                
                return $count;

            case 'redis':
                if ($this->cache->is_supported('redis')) {
                    $redis_info = $this->cache->cache_info('redis');
                    $total_keys = 0;
                    // Parse keyspace info (db0, db1, etc.)
                    foreach ($redis_info as $key => $value) {
                        if (preg_match('/^db(\d+)$/', $key) && is_string($value)) {
                            // Parse "keys=4,expires=4,avg_ttl=43131246" format
                            if (preg_match('/keys=(\d+)/', $value, $matches)) {
                                $total_keys += (int)$matches[1];
                            }
                        }
                    }
                    return $total_keys;
                }
                return 0;

            case 'memcached':
                if ($this->cache->is_supported('memcached')) {
                    $memcached_info = $this->cache->cache_info('memcached');
                    if (isset($memcached_info['curr_items'])) {
                        return (int) $memcached_info['curr_items'];
                    }

                    if (is_array($memcached_info)) {
                        $total_items = 0;
                        foreach ($memcached_info as $server_stats) {
                            if (is_array($server_stats) && isset($server_stats['curr_items'])) {
                                $total_items += (int) $server_stats['curr_items'];
                            }
                        }
                        return $total_items;
                    }
                    return 0;
                }
                return 0;

            case 'apcu':
                if ($this->cache->is_supported('apcu')) {
                    $apcu_info = apcu_cache_info();
                    return isset($apcu_info['num_entries']) ? (int)$apcu_info['num_entries'] : 0;
                }
                return 0;

            default:
                return 0;
        }
        
    }

    function format_bytes($bytes, $precision = 2) {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
