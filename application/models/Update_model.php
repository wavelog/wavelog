<?php

// This model handles all file updates (cronjobs)

class Update_model extends CI_Model {
    function clublog_scp() {
        // set the last run in cron table for the correct cron id
        $this->load->model('cron_model');
        $this->load->library('Paths');

        $this->cron_model->set_last_run($this->router->class . '_' . $this->router->method);

		$this->fetch_clublog_scp();
		$this->fetch_supercheckpartial_master();
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
                    echo "DONE: " . number_format($nCount) . " callsigns loaded";
                } else {
                    echo "FAILED: Empty file";
                }
            } else {
                echo "FAILED: Could not write to Club Log SCP file";
            }
        } else {
            echo "FAILED: Could not connect to Club Log";
        }
	}

	function fetch_supercheckpartial_master() {
		$contents = file_get_contents('https://www.supercheckpartial.com/MASTER.SCP', true);

        if ($contents === FALSE) {
            echo  "Something went wrong with fetching the MASTER.SCP file.";
        } else {
            $file = './updates/MASTER.SCP';

            if (file_put_contents($file, $contents) !== FALSE) {     // Save our content to the file.
                $nCount = count(file($file));
                if ($nCount > 0) {
                    echo  "DONE: " . number_format($nCount) . " callsigns loaded";
                } else {
                    echo "FAILED: Empty file";
                }
            } else {
                echo "FAILED: Could not write to Supercheckpartial MASTER.SCP file";
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
        return "Records inserted: " . $i . " in " . $totaltime . " seconds <br />";
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

		$url = 'https://www.amsat.org/tle/dailytle.txt';
		$curl = curl_init($url);

		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);

		$response = curl_exec($curl);

		$count = 0;

		if ($response === false) {
			echo 'Error: ' . curl_error($curl);
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
					$satname = $lines[$i];
					$tleline1 = $lines[$i + 1];
					$tleline2 = $lines[$i + 2];
					$sql = "
					INSERT INTO tle (satelliteid, tle)
					SELECT id, ?
					FROM satellite
					WHERE name = ? OR displayname = ?
					ON DUPLICATE KEY UPDATE
					tle = VALUES(tle), updated = now()
				";
				$this->db->query($sql, array($tleline1 . "\n" . $tleline2, $satname, $satname));
				}
			}
		}

		curl_close($curl);

		$mtime = microtime();
		$mtime = explode(" ",$mtime);
		$mtime = $mtime[1] + $mtime[0];
		$endtime = $mtime;
		$totaltime = ($endtime - $starttime);
		echo "This page was created in ".$totaltime." seconds <br />";
		echo "Records inserted: " . $count . " <br/>";
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
				} else {
					$status = __('New SAT. Insert failed.');
				}
			}
			array_push($result, array('name' => $name, 'displayname' => $displayname, 'startDate' => $startDate, 'endDate' => $endDate, 'status' => $status));
		}
		return $result;
	 }

}
