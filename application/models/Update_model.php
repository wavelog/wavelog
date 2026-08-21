<?php

// This model handles all file updates (cronjobs)

class Update_model extends CI_Model {
    function clublog_scp() {
        // set the last run in cron table for the correct cron id
        $this->load->model('cron_model');

        $this->cron_model->set_last_run($this->router->class . '_' . $this->router->method);

        $result = '';
		$result .= $this->fetch_clublog_scp();
		$result .= $this->fetch_supercheckpartial_master();

        return $result;
    }

	function fetch_clublog_scp() {
		$strFile = $this->paths->make_update_path("clublog_scp.txt");

        $url = "https://cdn.clublog.org/clublog.scp.gz";
        set_time_limit(300);

        $gz = gzopen($url, 'r');
        if ($gz) {
            $data = "";
            while (!gzeof($gz)) {
                $data .= gzgetc($gz);
            }
            gzclose($gz);
            if (file_put_contents($strFile, $data) !== FALSE) {
                $nCount = count(file($strFile));
                if ($nCount > 0) {
                    return "DONE: " . number_format($nCount) . " callsigns loaded";
                } else {
                    return "FAILED: Empty file";
                }
            } else {
                return "FAILED: Could not write to Club Log SCP file";
            }
        } else {
            return "FAILED: Could not connect to Club Log";
        }
	}

	function fetch_supercheckpartial_master() {
		$contents = file_get_contents('https://www.supercheckpartial.com/MASTER.SCP', true);

        if ($contents === FALSE) {
            return  "Something went wrong with fetching the MASTER.SCP file.";
        } else {
            $file = './updates/MASTER.SCP';

            if (file_put_contents($file, $contents) !== FALSE) {     // Save our content to the file.
                $nCount = count(file($file));
                if ($nCount > 0) {
                    return  "DONE: " . number_format($nCount) . " callsigns loaded";
                } else {
                    return "FAILED: Empty file";
                }
            } else {
                return "FAILED: Could not write to Supercheckpartial MASTER.SCP file";
            }
        }
	}

    function dok() {
        // set the last run in cron table for the correct cron id
        $this->load->model('cron_model');
        $this->cron_model->set_last_run($this->router->class . '_' . $this->router->method);

        $contents = file_get_contents('https://www.df2et.de/cqrlog/dok_and_sdok.txt', true);

        if ($contents === FALSE) {
            return  "Something went wrong with fetching the DOK file.";
        } else {
            $file = './updates/dok.txt';

            if (file_put_contents($file, $contents) !== FALSE) {     // Save our content to the file.
                $nCount = count(file($file));
                if ($nCount > 0) {
                    return  "DONE: " . number_format($nCount) . " DOKs and SDOKs saved";
                } else {
                    return "FAILED: Empty file";
                }
            } else {
                return "FAILED: Could not write to dok.txt file";
            }
        }
    }

	function sota() {
        // set the last run in cron table for the correct cron id
        $this->load->model('cron_model');
        $this->cron_model->set_last_run($this->router->class . '_' . $this->router->method);

        $csvfile = 'https://storage.sota.org.uk/summitslist.csv';

		$ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $csvfile);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Wavelog Updater');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        $csv = curl_exec($ch);
        if ($csv === FALSE) {
            return "Something went wrong with fetching the SOTA file";
        }

        $stream = fopen('php://temp', 'r+');
        if ($stream === FALSE) {
            return "FAILED: Could not open temp stream";
        }
        fwrite($stream, $csv);
        rewind($stream);

        $nCount = 0;
        $batch = [];
        $first = true;
		$second = true;

        $this->db->trans_start();
        $this->db->empty_table('sota_directory');

        while (($cols = fgetcsv($stream, 0, ",", '"', '\\')) !== FALSE) {
            if ($first) {
                $first = false;
                continue;
            }

			if ($second) {
                $second = false;
                continue;
            }

            $batch[] = [
                'reference'      => isset($cols[0]) ? trim($cols[0]) : null,
                'name'           => isset($cols[3]) ? trim($cols[3]) : null,
                'altitude'       => isset($cols[4]) ? trim($cols[4]) : null,
                'lat'            => $this->_wwff_coord($cols[9] ?? null),
                'lon'            => $this->_wwff_coord($cols[8] ?? null),
                'valid_from'     => $this->_dir_date($cols[12] ?? null, '!d/m/Y'),
                'valid_till'     => $this->_dir_date($cols[13] ?? null, '!d/m/Y'),
                'last_activated' => $this->_dir_date($cols[15] ?? null, '!d/m/Y'),
                'last_activator' => isset($cols[16]) ? trim($cols[16]) : null,
            ];
            $nCount++;

            if (count($batch) >= 1000) {
                $this->_sota_upsert_batch($batch);
                $batch = [];
            }
        }

        fclose($stream);

        if (!empty($batch)) {
            $this->_sota_upsert_batch($batch);
        }

        $this->db->trans_complete();
        if ($this->db->trans_status() === FALSE) {
            return "FAILED: import rolled back";
        }

        if ($nCount > 0) {
            return "DONE: " . number_format($nCount) . " SOTA's saved";
        } else {
            return "FAILED: Empty file";
        }
    }

    function wwff() {
        $this->load->model('cron_model');
        $this->cron_model->set_last_run($this->router->class . '_' . $this->router->method);

        $csvfile = 'https://wwff.co/wwff-data/wwff_directory.csv';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $csvfile);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Wavelog Updater');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        $csv = curl_exec($ch);
        if ($csv === FALSE) {
            return "Something went wrong with fetching the WWFF file";
        }

        $stream = fopen('php://temp', 'r+');
        if ($stream === FALSE) {
            return "FAILED: Could not open temp stream";
        }
        fwrite($stream, $csv);
        rewind($stream);

        $nCount = 0;
        $batch = [];
        $first = true;

        $this->db->trans_start();
        $this->db->empty_table('wwff_directory');

        while (($cols = fgetcsv($stream, 0, ",", '"', '\\')) !== FALSE) {
            if ($first) {
                $first = false;
                continue;
            }
            $ref = strtoupper(trim($cols[0] ?? ''));
            if ($ref === '') {
                continue;
            }

            $batch[] = [
                'reference'      => $ref,
                'name'           => isset($cols[2]) ? trim($cols[2]) : null,
                'lat'            => $this->_wwff_coord($cols[10] ?? null),
                'lon'            => $this->_wwff_coord($cols[11] ?? null),
                'valid_from'     => $this->_dir_date($cols[13] ?? null, '!Y-m-d'),
                'valid_till'     => $this->_dir_date($cols[14] ?? null, '!Y-m-d'),
                'last_activated' => $this->_dir_date($cols[25] ?? null, '!Y-m-d'),
            ];
            $nCount++;

            if (count($batch) >= 1000) {
                $this->_wwff_upsert_batch($batch);
                $batch = [];
            }
        }

        fclose($stream);

        if (!empty($batch)) {
            $this->_wwff_upsert_batch($batch);
        }

        $this->db->trans_complete();
        if ($this->db->trans_status() === FALSE) {
            return "FAILED: import rolled back";
        }

        if ($nCount > 0) {
            return "DONE: " . number_format($nCount) . " WWFF's saved";
        } else {
            return "FAILED: Empty file";
        }
    }

    private function _wwff_coord($val) {
        if ($val === null) {
            return null;
        }
        $val = trim($val);
        if ($val === '' || !is_numeric($val)) {
            return null;
        }
        $f = (float) $val;
        return $f == 0 ? null : $f;
    }

    // Normalises a directory date cell to a storage-ready DATE (Y-m-d) string.
    // Returns null for empty cells, unparseable values, or the zero-date
    // sentinel some sources use (e.g. WWFF's "0000-00-00"), which we treat as
    // "no constraint". $fmt is the createFromFormat mask (e.g. '!d/m/Y' for
    // SOTA, '!Y-m-d' for WWFF).
    private function _dir_date($val, $fmt) {
        if ($val === null) {
            return null;
        }
        $val = trim($val);
        if ($val === '' || $val === '0000-00-00') {
            return null;
        }
        $dt = DateTime::createFromFormat($fmt, $val);
        if ($dt === false) {
            return null;
        }
        return $dt->format('Y-m-d');
    }

	private function _pota_upsert_batch($batch) {
        $placeholders = [];
        $bindings = [];
        foreach ($batch as $b) {
            $placeholders[] = '(?, ?, ?, ?, ?, ?, ?, ?)';
            array_push($bindings, $b['reference'], $b['name'], $b['active'], $b['entityid'], $b['locationdesc'], $b['lat'], $b['lon'], $b['gridsquare']);
        }

        $sql = 'INSERT IGNORE INTO pota_directory (reference, name, active, entityid, locationdesc, lat, lon, gridsquare) VALUES '
            . implode(', ', $placeholders);

        $this->db->query($sql, $bindings);
    }

    private function _wwff_upsert_batch($batch) {
        $placeholders = [];
        $bindings = [];
        foreach ($batch as $b) {
            $placeholders[] = '(?, ?, ?, ?, ?, ?, ?)';
            array_push($bindings, $b['reference'], $b['name'], $b['lat'], $b['lon'], $b['valid_from'], $b['valid_till'], $b['last_activated']);
        }

        $sql = 'INSERT IGNORE INTO wwff_directory (reference, name, lat, lon, valid_from, valid_till, last_activated) VALUES '
            . implode(', ', $placeholders);

        $this->db->query($sql, $bindings);
    }

	private function _sota_upsert_batch($batch) {
		$placeholders = [];
		$bindings = [];
		foreach ($batch as $b) {
			$placeholders[] = '(?, ?, ?, ?, ?, ?, ?, ?, ?)';
			array_push($bindings, $b['reference'], $b['name'], $b['altitude'], $b['lat'], $b['lon'], $b['valid_from'], $b['valid_till'], $b['last_activated'], $b['last_activator']);
		}

		$sql = 'INSERT IGNORE INTO sota_directory (reference, name, altitude, lat, lon, valid_from, valid_till, last_activated, last_activator) VALUES '
			. implode(', ', $placeholders);

		$this->db->query($sql, $bindings);
	}

    function hamqsl(){
	    // This downloads and stores hamqsl propagation data XML file
	    $this->load->model('cron_model');
	    $this->cron_model->set_last_run($this->router->class . '_' . $this->router->method);

	    $url = 'https://www.hamqsl.com/solarxml.php';
	    $ch = curl_init();
	    curl_setopt($ch, CURLOPT_URL, $url);
	    curl_setopt($ch, CURLOPT_HEADER, false);
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	    curl_setopt($ch, CURLOPT_USERAGENT, 'Wavelog Updater');
	    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
	    $contents = curl_exec($ch);
	    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

	    if ($contents === FALSE || $http_code != 200) {
		    return "Something went wrong with fetching the solarxml.xml file from HAMqsl website.";
	    } else {
		    $file = './updates/solarxml.xml';

		    if (file_put_contents($file, $contents) !== FALSE) {     // Save our content to the file.
			    $nCount = count(file($file));
			    if ($nCount > 0) {
				    return  "DONE: solarxml.xml downloaded from HAMqsl website.";
			    } else {
				    return "FAILED: Empty file received from HAMqsl website.";
			    }
		    } else {
			    return "FAILED: Could not write solarxml.xml file from HAMqsl website.";
		    }
	    }
    }

	function pota() {
        // set the last run in cron table for the correct cron id
        $this->load->model('cron_model');
        $this->cron_model->set_last_run($this->router->class . '_' . $this->router->method);

        $csvfile = 'https://pota.app/all_parks_ext.csv';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $csvfile);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Wavelog Updater');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        $csv = curl_exec($ch);
        if ($csv === FALSE) {
            return "Something went wrong with fetching the POTA file";
        }

        $stream = fopen('php://temp', 'r+');
        if ($stream === FALSE) {
            return "FAILED: Could not open temp stream";
        }
        fwrite($stream, $csv);
        rewind($stream);

        $nCount = 0;
        $batch = [];
        $first = true;

        $this->db->trans_start();
        $this->db->empty_table('pota_directory');

        while (($cols = fgetcsv($stream, 0, ",", '"', '\\')) !== FALSE) {
            if ($first) {
                $first = false;
                continue;
            }

            $batch[] = [
                'reference'    => isset($cols[0]) ? trim($cols[0]) : null,
                'name'         => isset($cols[1]) ? trim($cols[1]) : null,
				'active'       => isset($cols[2]) ? trim($cols[2]) : null,
				'entityid'     => isset($cols[3]) ? trim($cols[3]) : null,
				'locationdesc' => isset($cols[4]) ? trim($cols[4]) : null,
				'lat'          => $this->_wwff_coord($cols[5] ?? null),
                'lon'          => $this->_wwff_coord($cols[6] ?? null),
                'gridsquare'   => isset($cols[7]) ? trim($cols[7]) : null,
            ];
            $nCount++;

            if (count($batch) >= 1000) {
                $this->_pota_upsert_batch($batch);
                $batch = [];
            }
        }

        fclose($stream);

        if (!empty($batch)) {
            $this->_pota_upsert_batch($batch);
        }

        $this->db->trans_complete();
        if ($this->db->trans_status() === FALSE) {
            return "FAILED: import rolled back";
        }

        if ($nCount > 0) {
            return "DONE: " . number_format($nCount) . " POTA's saved";
        } else {
            return "FAILED: Empty file";
        }
    }

    private $pota_boundary_sources = ['DE', 'AT', 'CH', 'CZ', 'DK', 'LU', 'LI'];

    function pota_boundaries() {
        $this->load->model('cron_model');
        $this->cron_model->set_last_run('update_pota_boundaries');

         set_time_limit(0);                       // 7 files, the big ones are slow

        // Peak is ~24 MB on the largest file (DE, 1436 parks), so 256M is ample.
        // Raise-only: never shrink a host that is already configured higher.
        $cur = trim((string) ini_get('memory_limit'));
        if ($cur !== '' && $cur !== '-1') {
            $bytes = (int) $cur;
            switch (strtolower(substr($cur, -1))) {
                case 'g': $bytes *= 1024 * 1024 * 1024; break;
                case 'm': $bytes *= 1024 * 1024; break;
                case 'k': $bytes *= 1024; break;
            }
            if ($bytes < 256 * 1024 * 1024) { ini_set('memory_limit', '256M'); }
        }

        $total = 0;
        $errors = [];
        $per_source = [];

        // CI keeps every executed statement in $this->db->queries with the binds
        // already substituted, so the whole ~150 MB of geometry would be retained
        // for the rest of the request. Nothing reads it (the profiler is never
        // enabled), so switch it off for the import and restore it afterwards --
        // the controller still redirects to /debug in this same request.
        $prev_save_queries = $this->db->save_queries;
        $this->db->save_queries = FALSE;
        $txn_open = false;
        try {
            foreach ($this->pota_boundary_sources as $cc) {
                $url = 'https://pota-map.info/geojson/' . $cc . '.geojson';
                $tmp = tempnam(sys_get_temp_dir(), 'pota_geo_');
                if ($tmp === false) {
                    $errors[] = $cc . ': tempnam failed';
                    continue;
                }
                if (!$this->_download_to_file($url, $tmp)) {
                    $errors[] = $cc . ': download failed';
                    @unlink($tmp);
                    continue;
                }

                $this->db->trans_begin();
                $txn_open = true;
                $this->db->query('DELETE FROM pota_boundaries WHERE source = ?', [$cc]);

                $count = 0;
                $last_ref = '';
                $batch = [];
                $batch_bytes = 0;
                // Batch on accumulated bytes, not on row count: park geometry is
                // very uneven (DE averages 60 kB but DE-1211 alone is 1.3 MB), so
                // a fixed row count could overshoot max_allowed_packet.
                $flush = function () use (&$batch, &$batch_bytes) {
                    if ($batch) {
                        $this->db->insert_batch('pota_boundaries', $batch);
                        $batch = [];
                        $batch_bytes = 0;
                    }
                };

                foreach ($this->_stream_geojson_features($tmp) as $feature) {
                    if (!is_array($feature) || ($feature['type'] ?? '') !== 'Feature') { continue; }
                    $ref = $feature['properties']['id'] ?? null;
                    $geom = $feature['geometry'] ?? null;
                    if ($ref === null || $geom === null) { continue; }

                    $json = json_encode($geom);
                    $batch[] = ['reference' => $ref, 'geom' => $json, 'source' => $cc];
                    $batch_bytes += strlen($json);
                    $last_ref = $ref;
                    $count++;

                    // 4 MB keeps every emitted statement well under the 16 MB
                    // default max_allowed_packet even when outsized parks cluster.
                    if ($batch_bytes >= 4 * 1024 * 1024) { $flush(); }
                }
                $flush();   // remainder

                if ($count === 0) {
                    $this->db->trans_rollback();
                    $txn_open = false;
                    $errors[] = $cc . ': 0 features parsed (format drift?) — existing data kept';
                } elseif ($this->db->trans_status() === FALSE) {
                    // last_query() is empty while save_queries is off, so name the
                    // reference we got to instead -- more useful than the raw SQL.
                    $this->db->trans_rollback();
                    $txn_open = false;
                    $errors[] = $cc . ': db error at/near ' . $last_ref . ' — rolled back';
                } else {
                    $this->db->trans_commit();
                    $txn_open = false;
                    $per_source[] = $cc . '=' . number_format($count);
                    $total += $count;
                }
                @unlink($tmp);
            }
        } catch (Throwable $e) {
            if ($txn_open) { $this->db->trans_rollback(); }
            throw $e;
        } finally {
            $this->db->save_queries = $prev_save_queries;
        }

        if ($total > 0) {
            $msg = 'DONE: ' . number_format($total) . ' park boundaries saved'
                . ' (' . implode(', ', $per_source) . ')';
            if ($errors) { $msg .= ' | errors: ' . implode('; ', $errors); }
            return $msg;
        }
        return 'FAILED: no boundaries imported (' . implode('; ', $errors) . ')';
    }

    private function _download_to_file($url, $path) {
        $fp = fopen($path, 'wb');
        if ($fp === false) { return false; }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Wavelog Updater');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
        curl_setopt($ch, CURLOPT_TIMEOUT, 600);
        curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        fclose($fp);
        return $code == 200;
    }

    private function _stream_geojson_features($filepath) {
        // Extract each Feature object from a GeoJSON FeatureCollection.
        //
        // The file is read whole (the largest source, DE, is ~95 MB) and the
        // features are pulled out with a recursive PCRE pattern that matches one
        // balanced JSON object at a time, string-aware. We deliberately do NOT
        // walk the text byte-by-byte in PHP: on some builds (notably the Windows
        // XAMPP PHP) single-character string indexing runs at only ~180K chars/s,
        // which made a 95 MB file take 10+ minutes. PCRE does the same scan in C
        // in well under a second (~0.13 s for DE). Each feature is decoded and
        // yielded on its own, so only one feature is held in memory at a time.
        $json = file_get_contents($filepath);
        if ($json === false) { return; }

        // Start matching inside the "features":[ array -- matching from the very
        // start of the document would swallow the whole FeatureCollection as a
        // single object.
        $p = strpos($json, '"features"');
        if ($p === false) { return; }
        $bracket = strpos($json, '[', $p);
        if ($bracket === false) { return; }

        // Raise PCRE's safety ceilings for the large subject; harmless for the
        // rest of the request, so not restored.
        ini_set('pcre.backtrack_limit', '100000000');
        ini_set('pcre.recursion_limit', '100000000');

        // Alternation order matters: a string literal first (so braces that
        // appear inside strings don't break the depth count), then ordinary
        // non-brace characters (numbers, [], commas, whitespace, ...), then a
        // nested object via (?R). Possessive quantifiers keep it linear.
        $pattern = '/\{(?:[^{}"\\\\]++|"(?:\\\\.|[^"\\\\])*"|(?R))*\}/';

        $off = $bracket + 1;
        $len = strlen($json);
        while ($off < $len) {
            if (!preg_match($pattern, $json, $m, PREG_OFFSET_CAPTURE, $off)) {
                break;
            }
            $feat = json_decode($m[0][0], true);
            if (is_array($feat)) { yield $feat; }
            $off = $m[0][1] + strlen($m[0][0]);
        }
    }

    function lotw_users() {
        // set the last run in cron table for the correct cron id
        $this->load->model('cron_model');
        $this->cron_model->set_last_run($this->router->class . '_' . $this->router->method);

        $mtime = microtime();
        $mtime = explode(" ", $mtime);
        $mtime = $mtime[1] + $mtime[0];
        $starttime = $mtime;

        $url = 'https://lotw.arrl.org/lotw-user-activity.csv';

        $f = fopen('php://temp', 'w+');
        if ($f === FALSE) {
           return "Something went wrong creating the temporary LoTW users file";
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Wavelog LoTW Updater');
        curl_setopt($ch, CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_FILE, $f);
        curl_exec($ch);
        if (curl_getinfo($ch, CURLINFO_HTTP_CODE) != 200) {
           return "Something went wrong with fetching the LoTW users file";
        }
        rewind($f);
        if (count(fgetcsv($f, 1000, ",", '"', '\\')) == 1) {
           fclose($f);
           return "File format of LoTW users file does not match expected format. Update skipped!";
        }

        rewind($f);
 		$this->db->query("TRUNCATE TABLE lotw_users");
        $i = 0;
        $data = fgetcsv($f, 1000, ",", '"', '\\');
        do {
            if ($data[0]) {
                $lotwdata[$i]['callsign'] = $data[0];
                $lotwdata[$i]['lastupload'] = $data[1] . ' ' . $data[2];
                if (($i % 2000) == 0) {
                    $this->db->insert_batch('lotw_users', $lotwdata);
                    unset($lotwdata);
                }
                $i++;
            }
        } while ($data = fgetcsv($f, 1000, ",", '"', '\\'));
        fclose($f);

		if (isset($lotwdata) && count($lotwdata) > 0) {
        	$this->db->insert_batch('lotw_users', $lotwdata);
		}

        $mtime = microtime();
        $mtime = explode(" ", $mtime);
        $mtime = $mtime[1] + $mtime[0];
        $endtime = $mtime;
        $totaltime = ($endtime - $starttime);
        return "Records inserted: " . $i . " in " . $totaltime . " seconds";
    }

    function wavelog_latest_release() {
        $latest_tag = null;
        $url = "https://api.github.com/repos/wavelog/wavelog/releases";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Wavelog Updater');
        curl_setopt($ch, CURLOPT_URL,$url);
        $result=curl_exec($ch);
        $json = json_decode($result, true);
        $latest_tag = $json[0]['tag_name'] ?? 'Unknown';
        return $latest_tag;
    }

    function update_check($silent = false) {
        if (!$this->config->item('disable_version_check') ?? false) {
            $running_version = $this->optionslib->get_option('version');
            $latest_release = $this->wavelog_latest_release();
            $this->optionslib->update('latest_release', $latest_release);
            if (version_compare($latest_release, $running_version, '>')) {
                if (!$silent) {
                   print __("Newer release available:")." ".$latest_release;
                }
            } else {
                if (!$silent) {
                    print __("You are running the latest version.");
                }
            }
        }
    }

	function tle() {
		// set the last run in cron table for the correct cron id
		$this->load->model('cron_model');
		$this->cron_model->set_last_run($this->router->class . '_' . $this->router->method);
		$mtime = microtime();
		$mtime = explode(" ",$mtime);
		$mtime = $mtime[1] + $mtime[0];
		$starttime = $mtime;

		$this->update_norad_ids();

		$url = 'https://www.amsat.org/tle/dailytle.txt';
		$curl = curl_init($url);

		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);

		$response = curl_exec($curl);

		if (strlen($response) >= 140) {

			// Clear all TLE so that reentered birds disappear from planner and path prediction
			$sql = "UPDATE `tle` LEFT JOIN `satellite` ON `tle`.`satelliteid` = `satellite`.`id` SET `tle` = NULL WHERE `satellite`.`name` != '' AND `satellite`.`name` IS NOT NULL;";
			$this->db->query($sql);

			$amsat_count = 0;

			if ($response === false) {
				return 'Error: ' . curl_error($curl);
			} else {
				// Split the response into an array of lines
				$lines = explode("\n", $response);

				$satname = '';
				$tleline1 = '';
				$tleline2 = '';
				// Process each line
				for ($i = 0; $i < count($lines); $i += 3) {
					// Check if there are at least three lines remaining
					if (isset($lines[$i], $lines[$i + 1], $lines[$i + 2])) {
						// Get the three lines
						$satname = substr($lines[$i+1], 2, 5);
						$tleline1 = $lines[$i + 1];
						$tleline2 = $lines[$i + 2];
						$sql = "
						INSERT INTO tle (satelliteid, tle)
						SELECT id, ?
						FROM satellite
						WHERE norad_id = ?
						ON DUPLICATE KEY UPDATE
						tle = VALUES(tle), updated = now()
					";
					$this->db->query($sql, array($tleline1 . "\n" . $tleline2, $satname));
					if ($this->db->affected_rows() > 0) {
						$amsat_count++;
					}
					}
				}
			}

			// Gap-fill NULL rows from CelesTrak OMM (covers 6+ digit NORAD IDs AMSAT can't encode).
			$omm_count = $this->update_tle_from_celestrak_omm();


			$mtime = microtime();
			$mtime = explode(" ",$mtime);
			$mtime = $mtime[1] + $mtime[0];
			$endtime = $mtime;
			$totaltime = ($endtime - $starttime);
			return "This page was created in ".$totaltime." seconds <br />AMSAT TLE: ".$amsat_count." updated, OMM: ".$omm_count." updated";

		} else {
			return "Error: Received file was empty";
		}
	}

	/**
	 * Gap-fill from CelesTrak OMM JSON. Only fills NULL rows so AMSAT stays primary.
	 * @return int records applied
	 */
	private function update_tle_from_celestrak_omm() {
		$url = 'https://celestrak.org/NORAD/elements/gp.php?GROUP=amateur&FORMAT=JSON';
		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($curl, CURLOPT_USERAGENT, 'Wavelog TLE Updater');
		curl_setopt($curl, CURLOPT_TIMEOUT, 30);
		$response = curl_exec($curl);
		$err  = curl_error($curl);
		$http = curl_getinfo($curl, CURLINFO_HTTP_CODE);

		if ($response === false || $http !== 200) {
			log_message('error', 'CelesTrak OMM fetch failed (HTTP ' . $http . '): ' . $err);
			return 0;
		}

		$omm = json_decode($response, true);
		if (!is_array($omm)) {
			return 0;
		}

		$count = 0;
		// Only fill still-NULL rows; AMSAT stays authoritative.
		$sql = "INSERT INTO tle (satelliteid, tle)
			SELECT s.id, ?
			FROM satellite s
			WHERE s.norad_id = ?
			ON DUPLICATE KEY UPDATE
			tle = IF(tle IS NULL, VALUES(tle), tle),
			updated = IF(tle IS NULL, now(), updated)";
		foreach ($omm as $obj) {
			if (!isset($obj['NORAD_CAT_ID'])) { continue; }
			$cat = (int) $obj['NORAD_CAT_ID'];
			if ($cat <= 0) { continue; }
			$this->db->query($sql, array(json_encode($obj), $cat));
			// Counts only newly filled rows.
			if ($this->db->affected_rows() > 0) {
				$count++;
			}
		}
		return $count;
	}

	 function lotw_sats() {
		$url = 'https://lotw.arrl.org/lotw/config.tq6';
		$curl = curl_init($url);

		curl_setopt($curl, CURLOPT_FAILONERROR, true);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($curl, CURLOPT_TIMEOUT, 10);

		$response = curl_exec($curl);
		if (curl_errno($curl)) {
			log_message('error', __('cURL error:').' '.curl_error($curl).' ('.curl_errno($curl).')');
			return;
		}
		$xmlstring = gzdecode($response);
		if ($xmlstring === false) {
			return;
		}
		$xml = simplexml_load_string($xmlstring);
		if ($xml === false) {
			return;
		}

		$existingSats = array();
		$this->db->select('name, displayname, lotw');
		$query = $this->db->get('satellite');
		foreach($query->result() as $row) {
			$existingSats[$row->name] = array($row->lotw, $row->displayname);
		}

		$result = array();

		foreach ($xml->tqslconfig->satellites->satellite as $sat) {
			$name = ($sat->attributes()->{'name'} ?? '')->__toString();
			$startDate = $sat->attributes()->{'startDate'};
			$endDate = $sat->attributes()->{'endDate'};
			$displayname = ($sat ?? '')->__toString();
			$status = '';

			if (array_key_exists("$name", $existingSats)) {
				if ($existingSats["$name"][0] == 'N') {
					$this->db->set('lotw', 'Y');
					$this->db->where('name', $name);
					$this->db->update('satellite');
					if ($this->db->affected_rows() > 0) {
						$status = __('SAT already existing. LoTW status updated.');
						$updateresult = $this->reset_lotw_qsl_fields($name, $existingSats["$name"][1]);
						if ($updateresult > 0) {
							$status .= ' '.sprintf(_ngettext('LoTW status for %d QSO updated', 'LoTW status for %d QSOs updated', intval($updateresult)), intval($updateresult));
						}
					} else {
						$status = __('SAT already existing. Updating LoTW status failed.');
					}
				} else {
					$status = __('SAT already existing. Ignored.');
				}
				if ($existingSats["$name"][1] == '') {
					$this->db->set('displayname', $displayname);
					$this->db->where('name', $name);
					$this->db->update('satellite');
					if ($this->db->affected_rows() > 0) {
						$status = __('SAT already existing. Display name updated.');
					} else {
						$status = __('SAT already existing. Updating display name failed.');
					}
				}
			} else {
				$data = array(
					'name' => $name,
					'displayname' => $displayname,
					'lotw' => 'Y',
				);
				if ($this->db->insert('satellite', $data)) {
					$status = __('New SAT. Inserted.');
					if (array_key_exists($name, $existingSats)) {
						$updateresult = $this->reset_lotw_qsl_fields($data['name'], $existingSats["$name"][1]);
						if ($updateresult > 0) {
							$status .= ' '.sprintf(_ngettext('LoTW status for %d QSO updated', 'LoTW status for %d QSOs updated', intval($updateresult)), intval($updateresult));
						}
					}
				} else {
					$status = __('New SAT. Insert failed.');
				}
			}
			array_push($result, array('name' => $name, 'displayname' => $displayname, 'startDate' => $startDate, 'endDate' => $endDate, 'status' => $status));
		}
		return $result;
	}

	function reset_lotw_qsl_fields($satname = null, $displayname = null) {
		if (isset($satname) && $satname != '' && isset($displayname) && $displayname != '') {
			$sql = "UPDATE ".$this->config->item('table_name')." SET COL_LOTW_QSL_SENT = 'N', COL_LOTW_QSL_RCVD = 'N', COL_LOTW_QSLSDATE = NULL, COL_LOTW_QSLRDATE = NULL, COL_SAT_NAME = ? WHERE COL_SAT_NAME = ? AND COL_PROP_MODE = 'SAT' AND COL_LOTW_QSL_SENT = 'I' AND COL_LOTW_QSL_RCVD = 'I';";
			$this->db->query($sql, array($satname, $displayname));
			return $this->db->affected_rows();
		} else {
			return 0;
		}
	}

	function update_norad_ids() {
		$csvfile = 'https://www.df2et.de/cqrlog/lotw_norad.csv';
		$csvhandle = fopen($csvfile, "r");
		while (false !== ($data = fgetcsv($csvhandle, 1000, ",", '"', '\\'))) {
			$this->db->set('norad_id', $data[1]);
			$this->db->where('name', $data[0]);
			$this->db->update('satellite');
		}
		return;
	}


	function update_hams_of_note() {
		if (($this->optionslib->get_option('hon_url') ?? '') == '') {
			$file = 'https://api.ham2k.net/data/ham2k/hams-of-note.txt';
		} else {
			$file = $this->optionslib->get_option('hon_url');
		}
		$result = array();
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $file);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_TIMEOUT, 10);
		curl_setopt($ch, CURLOPT_USERAGENT, 'Wavelog Updater');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$response = curl_exec($ch);
		$http_result = curl_getinfo($ch);
		if ($http_result['http_code'] == "200") {
			$lines = explode("\n", $response);
			if (count($lines) > 0) {	// Check if there was data, otherwise skip parsing / truncating the table and preserve whats there
				$this->db->query("TRUNCATE TABLE hams_of_note");
				$i = 0;
				foreach($lines as $data) {
					$line = trim($data);
					if ($line != "" && $line[0] != '#') {
						$index = strpos($line, ' ');
						$call = $this->security->xss_clean(substr($line, 0, $index));
						if (preg_match('/[^a-zA-Z0-9\/]/', $call)) {
							continue;
						}
						$name = $this->security->xss_clean(substr($line, strpos($line, ' ')));
						$truncated = false;
						if (mb_strlen($name, 'UTF-8') > 256) {
							$name = mb_substr($name, 0, 256, 'UTF-8');
							$truncated = true;
						}
						$linkname = $link = null;
						if (strpos($name, '[')) {
							$linkname = $this->security->xss_clean(substr($name, strpos($name, '[')+1, (strpos($name, ']') - strpos($name, '[')-1)));
							if (mb_strlen($linkname, 'UTF-8') > 256) {
								$linkname = mb_substr($linkname, 0, 256, 'UTF-8');
								$truncated = true;
							}
							$link= $this->security->xss_clean(substr($name, strpos($name, '(')+1, (strpos($name, ')') - strpos($name, '(')-1)));
							if (mb_strlen($link, 'UTF-8') > 256) {
								$link= mb_substr($link, 0, 256, 'UTF-8');
								$truncated = true;
							}
							$name = substr($name, 0, strpos($name, '['));
							if (mb_strlen($name, 'UTF-8') > 256) {
								$name = mb_substr($name, 0, 256, 'UTF-8');
								$truncated = true;
							}
						}
						if ($truncated == true) {
							log_message('error', 'Hams Of Note '.$call.': Data too long. Truncated at 256 characters.');
						}
						array_push($result, array('callsign' => $call, 'name' => $name, 'linkname' => $linkname, 'link' => $link));
						$hon[$i]['callsign'] = $call;
						$hon[$i]['description'] = trim($name);
						$hon[$i]['linkname'] = $linkname;
						$hon[$i]['link'] = $link;
						$i++;
						if (($i % 100) == 0) {
							$this->db->insert_batch('hams_of_note', $hon);
							unset($hon);
							$i=0;	// reset $i to see if there's something more at the end
						}
					}
				}
				if ($i>0 && isset($hon)) {	// Leftovers?
					$this->db->insert_batch('hams_of_note', $hon);
				}
			} else {
				$result=null;
			}
		} else {
			$result=null;
		}
		return $result;
	}

	function update_vucc_grids() {
		// set the last run in cron table for the correct cron id
		$this->load->model('cron_model');
		$this->cron_model->set_last_run('vucc_grid_file');
		$mtime = microtime();
		$mtime = explode(" ",$mtime);
		$mtime = $mtime[1] + $mtime[0];
		$starttime = $mtime;

		$url = 'https://raw.githubusercontent.com/wavelog/dxcc_data/refs/heads/master/vuccgrids.dat';
		$curl = curl_init($url);

		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);

		$response = curl_exec($curl);

		$xml = @simplexml_load_string($response);

		if ($xml === false) {
			log_message('error', 'vuccgrids.dat update from primary location failed.');

			// Try our own mirror in case upstream fails
			$url = 'https://sourceforges.net/p/trustedqsl/tqsl/ci/master/tree/apps/vuccgrids.dat?format=raw';
			$curl = curl_init($url);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
			$response = curl_exec($curl);
			$xml = @simplexml_load_string($response);
			if ($xml === false) {
				log_message('error', 'vuccgrids.dat update from backup location failed.');
				return "Failed to parse TQSL VUCC grid file XML.";
			}
		}

		// Truncate the table first
		$this->db->query("TRUNCATE TABLE vuccgrids;");

		// Loop through <vucc> elements
		$batchSize = 2000;
		$vuccdata = [];
		$total_inserted  = 0;
		foreach ($xml->vucc as $vucc) {
			$adif = (int)$vucc['entity']; // assuming "entity" attribute is ADIF
			$grid = strtoupper(trim((string)$vucc['grid']));

			if ($adif > 0 && $grid !== '') {
				$key = $adif . '-' . $grid;

				// Only add if not already in array
				if (!isset($vuccdata[$key])) {
					$vuccdata[$key] = [
						'adif' => $adif,
						'gridsquare' => $grid
					];
				}

                if (count($vuccdata) >= $batchSize) {
					$rows = $this->db->insert_batch('vuccgrids', array_values($vuccdata));
					if ($rows !== false) {
						$total_inserted += $rows;
					}
					$vuccdata = []; // clear after insert
				}
			}
		}

		// insert any remaining rows
		if (!empty($vuccdata)) {
			$rows = $this->db->insert_batch('vuccgrids', array_values($vuccdata));
			if ($rows !== false) {
				$total_inserted += $rows;
			}
		}

		$mtime = microtime();
		$mtime = explode(" ",$mtime);
		$mtime = $mtime[1] + $mtime[0];
		$endtime = $mtime;
		$totaltime = ($endtime - $starttime);

		if ($total_inserted > 0) {
            return "DONE: This page was created in ".$totaltime." seconds.<br />" . number_format($total_inserted ) . " Grids saved";
        } else {
            return "FAILED: Empty file";
        }
	}

}
