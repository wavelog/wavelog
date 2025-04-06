<?php

// This model handles all file updates (cronjobs)

class Update_model extends CI_Model {
    function clublog_scp() {
        // set the last run in cron table for the correct cron id
        $this->load->model('cron_model');
        $this->load->library('Paths');

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

        $csvfile = 'https://www.sotadata.org.uk/summitslist.csv';

        $sotafile = './updates/sota.txt';

        $csvhandle = fopen($csvfile, "r");
        if ($csvhandle === FALSE) {
            return  "Something went wrong with fetching the SOTA file";
        }

        $data = fgetcsv($csvhandle, 1000, ","); // Skip line we are not interested in
        $data = fgetcsv($csvhandle, 1000, ","); // Skip line we are not interested in
        $data = fgetcsv($csvhandle, 1000, ",");
        $sotafilehandle = fopen($sotafile, 'w');

        if ($sotafilehandle === FALSE) {
            return "FAILED: Could not write to sota.txt file";
        }

        $nCount = 0;
        do {
            if ($data[0]) {
                fwrite($sotafilehandle, $data[0] . PHP_EOL);
                $nCount++;
            }
        } while ($data = fgetcsv($csvhandle, 1000, ","));

        fclose($csvhandle);
        fclose($sotafilehandle);

        if ($nCount > 0) {
            return "DONE: " . number_format($nCount) . " SOTA's saved";
        } else {
            return "FAILED: Empty file";
        }
    }

    function wwff() {
        // set the last run in cron table for the correct cron id
        $this->load->model('cron_model');
        $this->cron_model->set_last_run($this->router->class . '_' . $this->router->method);

        $csvfile = 'https://wwff.co/wwff-data/wwff_directory.csv';

        $wwfffile = './updates/wwff.txt';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $csvfile);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Wavelog Updater');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $csv = curl_exec($ch);
        curl_close($ch);
        if ($csv === FALSE) {
            return "Something went wrong with fetching the WWFF file";
        }

        $wwfffilehandle = fopen($wwfffile, 'w');
        if ($wwfffilehandle === FALSE) {
            return "FAILED: Could not write to wwff.txt file";
        }

        $data = str_getcsv($csv, "\n");
        $nCount = 0;
        foreach ($data as $idx => $row) {
            if ($idx == 0) continue; // Skip line we are not interested in
            $row = str_getcsv($row, ',');
            if ($row[0]) {
                fwrite($wwfffilehandle, $row[0] . PHP_EOL);
                $nCount++;
            }
        }

        fclose($wwfffilehandle);

        if ($nCount > 0) {
            return "DONE: " . number_format($nCount) . " WWFF's saved";
        } else {
            return "FAILED: Empty file";
        }
    }

    function pota() {
        // set the last run in cron table for the correct cron id
        $this->load->model('cron_model');
        $this->cron_model->set_last_run($this->router->class . '_' . $this->router->method);

        $csvfile = 'https://pota.app/all_parks.csv';

        $potafile = './updates/pota.txt';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $csvfile);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Wavelog Updater');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $csv = curl_exec($ch);
        curl_close($ch);
        if ($csv === FALSE) {
            return "Something went wrong with fetching the POTA file";
        }

        $potafilehandle = fopen($potafile, 'w');
        if ($potafilehandle === FALSE) {
            return "FAILED: Could not write to pota.txt file";
        }
        $data = str_getcsv($csv, "\n");
        $nCount = 0;
        foreach ($data as $idx => $row) {
            if ($idx == 0) continue; // Skip line we are not interested in
            $row = str_getcsv($row, ',');
            if ($row[0]) {
                fwrite($potafilehandle, $row[0] . PHP_EOL);
                $nCount++;
            }
        }

        fclose($potafilehandle);

        if ($nCount > 0) {
            return "DONE: " . number_format($nCount) . " POTA's saved";
        } else {
            return "FAILED: Empty file";
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

        $file = 'https://lotw.arrl.org/lotw-user-activity.csv';

        $handle = fopen($file, "r");
        if ($handle === FALSE) {
            return "Something went wrong with fetching the LoTW uses file";
        }
        $this->db->empty_table("lotw_users");
        $this->db->query("ALTER TABLE lotw_users AUTO_INCREMENT 1");
        $i = 0;
        $data = fgetcsv($handle, 1000, ",");
        do {
            if ($data[0]) {
                $lotwdata[$i]['callsign'] = $data[0];
                $lotwdata[$i]['lastupload'] = $data[1] . ' ' . $data[2];
                if (($i % 2000) == 0) {
                    $this->db->insert_batch('lotw_users', $lotwdata);
                    unset($lotwdata);
                    // echo 'Record ' . $i . '<br />';
                }
                $i++;
            }
        } while ($data = fgetcsv($handle, 1000, ","));
        fclose($handle);

        $this->db->insert_batch('lotw_users', $lotwdata);

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
        curl_close($ch);
        $json = json_decode($result, true);
        $latest_tag = $json[0]['tag_name'] ?? 'Unknown';
        return $latest_tag;
    }

    function set_latest_release($release) {
        $this->db->select('option_value');
        $this->db->where('option_name', 'latest_release');
        $query = $this->db->get('options');
        if ($query->num_rows() > 0) {
            $this->db->where('option_name', 'latest_release');
            $this->db->update('options', array('option_value' => $release));
        } else {
            $data = array(
                array('option_name' => "latest_release", 'option_value' => $release, 'autoload' => "yes"),
            );
            $this->db->insert_batch('options', $data);
        }
    }

    function update_check($silent = false) {
        if (!$this->config->item('disable_version_check') ?? false) {
            $running_version = $this->optionslib->get_option('version');
            $latest_release = $this->wavelog_latest_release();
            $this->set_latest_release($latest_release);
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

		$count = 0;

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
				$count++;
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
				}
			}
		}

		curl_close($curl);

		$mtime = microtime();
		$mtime = explode(" ",$mtime);
		$mtime = $mtime[1] + $mtime[0];
		$endtime = $mtime;
		$totaltime = ($endtime - $starttime);
		return "This page was created in ".$totaltime." seconds <br />Records inserted: " . $count;
	}

	 function lotw_sats() {
		$url = 'https://lotw.arrl.org/lotw/config.tq6';
		$curl = curl_init($url);

		curl_setopt($curl, CURLOPT_FAILONERROR, true);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($curl, CURLOPT_BINARYTRANSFER,true);
		curl_setopt($curl, CURLOPT_TIMEOUT, 10);

		$response = curl_exec($curl);
		if (curl_errno($curl)) {
			log_message('error', __('cURL error:').' '.curl_error($curl).' ('.curl_errno($curl).')');
			return;
		}
		curl_close($curl);
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
		while (false !== ($data = fgetcsv($csvhandle, 1000, ","))) {
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
		curl_close($ch);
		if ($http_result['http_code'] == "200") {
			$lines = explode("\n", $response);
			if (count($lines) > 0) {	// Check if there was data, otherwise skip parsing / truncating the table and preserve whats there
				$this->db->empty_table("hams_of_note");
				$this->db->query("ALTER TABLE hams_of_note AUTO_INCREMENT 1");
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
						$linkname = $link = null;
						if (strpos($name, '[')) {
							$linkname = $this->security->xss_clean(substr($name, strpos($name, '[')+1, (strpos($name, ']') - strpos($name, '[')-1)));
							$link= $this->security->xss_clean(substr($name, strpos($name, '(')+1, (strpos($name, ')') - strpos($name, '(')-1)));
							$name = substr($name, 0, strpos($name, '['));
						}
						array_push($result, array('callsign' => $call, 'name' => $name, 'linkname' => $linkname, 'link' => $link));
						$hon[$i]['callsign'] = $call;
						$hon[$i]['description'] = $name;
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
				if ($i>0) {	// Leftovers?
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

}
