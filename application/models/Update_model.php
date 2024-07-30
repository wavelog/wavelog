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
}
