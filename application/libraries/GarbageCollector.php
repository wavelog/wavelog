<?php

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Garbage collection library for file cache
 * 
 * Since we need a garbage collector for file caching, we implement a dynamic
 * probability check based on traffic patterns to avoid performance hits on
 * high-traffic sites while still ensuring regular cleanup on low-traffic sites.
 * 
 * Why we don't use $this->cache->clean()? Because this deletes everything. We only want to delete expired files.
 * 
 * 2026, Fabian Berg, HB9HIL
 */
class GarbageCollector {

    private $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
    }

	/**
	 * Run garbage collection for file cache with traffic-based probability and interval checks.
     * 
	 * @return	int	Number of deleted files, or 0 on failure
	 */
	public function run()
	{
		// The gc checkfile path has to exist on every possible environment. So we choose sys_get_temp_dir()
		// and hash it with FCPATH to avoid collisions between different Wavelog installations on the same server.
		$gc_file = sys_get_temp_dir() . '/ci_gc_last_run_' . md5(FCPATH) . '.txt';

		// The garbage collection should run around every 4 hours
		$gc_interval = 3600 * 4;
		
		// Traffic metric: Requests since last GC
		$data = file_exists($gc_file) ? (json_decode(file_get_contents($gc_file), true) ?: []) : [];
		$last_run = $data['time'] ?? 0;
		$request_count = ($data['count'] ?? 0) + 1; // This is also a request so +1
			
		// Dynamic probability based on traffic to reduce load on high-traffic installations
		// This logic is inverted to a normal human brain. Higher traffic = lower probability to go through the next check.
		if ($request_count < 100) {
			$probability = 100;  // Low-Traffic: check on every request (100% pass the probability check)
		} elseif ($request_count < 1000) {
			$probability = 50;   // Medium-Traffic: every 2nd request (50% pass the probability check)
		} else {
			$probability = 10;   // High-Traffic: every 10th request (only 10% pass the probability check)
		}
		
		// We do the probability check first. Let's play some lottery
		if (rand(1, 100) > $probability) {
			// Oh snag, we did not hit the probability but still need to update the request count
			// The +1 was already added above
			$this->_update_gc_file($gc_file, $last_run, $request_count);
			return 0;
		}
		
		// Oh dear, we hit the probability. Now check if enough time has passed since last run.
		if (time() - $last_run < $gc_interval) {
			// Nope, so just update the request count
			$this->_update_gc_file($gc_file, $last_run, $request_count);
			return 0;
		}
		
		// Alright, let's do some garbage collection!
		// We use a lock file to prevent multiple simultaneous GC runs
		// in case of high traffic. Only one process should do the GC at a time.
		$lock_file = $gc_file . '.lock';

		// Try to acquire the lock
		$fp = fopen($lock_file, 'c');

		if ($fp === FALSE) {
			return 0;
		}

		// If we cannot acquire the lock, another process is already doing GC
		// So we just return and do nothing so the other process can finish
		if ( ! flock($fp, LOCK_EX | LOCK_NB)) {
			fclose($fp);
			return 0;
		}

		log_message('info', 'Starting file cache garbage collection...');
		
		try {
			// Perform garbage collection itself (without loading the cache driver)
			$result = $this->_run_garbage_collector();

			// Update the GC file with the current time and reset request count to 0
			$this->_update_gc_file($gc_file, time(), 0);
		} finally {
			// Release the lock and close the file
			// This will happen even if an exception occurs during GC so we don't deadlock
			flock($fp, LOCK_UN);
			fclose($fp);
			@unlink($lock_file);
		}

		log_message('info', 'File cache garbage collection completed. Deleted ' . $result . ' expired files.');

		return $result;
	}

	/**
	 * Run file cache garbage collection without loading the cache driver.
	 *
	 * @return	int	Number of deleted files, or 0 on failure
	 */
	public function _run_garbage_collector()
	{
		$cache_path = $this->CI->config->item('cache_path') == '' ? APPPATH.'cache/' : $this->CI->config->item('cache_path');

        log_message('debug', 'GarbageCollector: Scanning cache path ' . $cache_path);

		if ( ! is_dir($cache_path))
		{
			log_message('error', 'GarbageCollector: Cache path is not a directory or does not exist: ' . $cache_path);
			return 0;
		}

		// We need to ignore some CI specific files
		$ignore_files = [
			'index.html',
			'.htaccess'
		];

		$deleted = 0;
		$current_time = time();

		if ($handle = opendir($cache_path))
		{
			while (($file = readdir($handle)) !== FALSE)
			{
				if ($file === '.' || $file === '..' || in_array($file, $ignore_files))
				{
					continue;
				}

				$filepath = $cache_path.$file;

				if (is_file($filepath))
				{
					$data = @unserialize(file_get_contents($filepath));

					if (is_array($data) && isset($data['time'], $data['ttl']))
					{
						// Check if TTL is set and file has expired
						if ($data['ttl'] > 0 && $current_time > $data['time'] + $data['ttl'])
						{
							if (unlink($filepath))
							{
								$deleted++;
							}
						}
					}
				}
			}
			closedir($handle);
		}

		return $deleted;
	}

    private function _update_gc_file($gc_file, $time, $count) {
        file_put_contents($gc_file, json_encode(['time' => $time, 'count' => $count]));
    }
}
