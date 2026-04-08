<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

class Timeplotter_model extends CI_Model
{

    function getTimes($postdata) {
		$logbooks_locations_array = $this->logbooks_model->list_logbook_relationships($this->session->userdata('active_station_logbook'));

        if ($logbooks_locations_array[0] === -1) {
            header('Content-Type: application/json');
            $data['error'] = 'No QSOs found to plot!';
            echo json_encode($data);
            return;
        }

        $this->db->select('time(col_time_on) time, col_call as callsign, col_band as band, col_mode as mode');

        if ($postdata['band'] != 'All') {
            if ($postdata['band'] == 'SAT') {
                $this->db->where('col_prop_mode', $postdata['band']);
            }
            else {
                $this->db->where('col_band', $postdata['band']);
            }
        }

        if ($postdata['dxcc'] != 'All') {
            $this->db->where('col_dxcc', $postdata['dxcc']);
        }

        if ($postdata['cqzone'] != 'All') {
            $this->db->where('col_cqz', $postdata['cqzone']);
        }

        if (!empty($postdata['mode']) && $postdata['mode'] != 'All') {
            $this->db->where('col_mode', $postdata['mode']);
        }

        $this->db->where_in('station_id', $logbooks_locations_array);
        $this->db->order_by('col_band', 'ASC');
        $datearray = $this->db->get($this->config->item('table_name'));
        $this->plot($datearray->result_array());
    }

    /*
    * Function generates the array, checks for array entries, and adds them before returning data ready for plot
    */
    function plot($log) {

        $start = "00:00";
        $end = "23:59";

        $tStart = strtotime($start);
        $tEnd = strtotime($end);
        $tNow = $tStart;
        $i = 0;

        // Initialize time slots
        while($tNow <= $tEnd){
            $label = date("H:i",$tNow).'z - ';
            $tNow = strtotime('+30 minutes',$tNow);
            $label .= date("H:i",$tNow).'z';
            $dataarray[$i]['time'] =  $label;
            $dataarray[$i]['count'] = '0';
            $dataarray[$i]['calls'] = '';
            $dataarray[$i]['callcount'] = '0';
            $i++;
        }

        // Organize data by band
        $byBand = array();
        $bandCounts = array();
        $modeCounts = array();
        $bandsWithCalls = array();

        foreach ($log as $line) {
            $time = $line['time'];
            $band = !empty($line['band']) ? $line['band'] : 'Unknown';
            $mode = !empty($line['mode']) ? strtoupper($line['mode']) : 'Unknown';
            $callsign = $line['callsign'];

            // Overall aggregation
            $dt = new DateTime("1970-01-01 $time", new DateTimeZone('UTC'));
            $arrayplacement = floor((int)$dt->getTimestamp() / 1800);
            $dataarray[$arrayplacement]['count']++;

            $callCount = $dataarray[$arrayplacement]['callcount'];
            if ($callCount < 5) {
                if ($callCount > 0) {
                    $dataarray[$arrayplacement]['calls'] .= ', ';
                }
                $dataarray[$arrayplacement]['calls'] .= $callsign;
                $dataarray[$arrayplacement]['callcount']++;
            }

            // Per-band aggregation
            if (!isset($byBand[$band])) {
                $byBand[$band] = array();
                for ($j = 0; $j < 48; $j++) {
                    $byBand[$band][$j] = 0;
                }
                $bandCounts[$band] = 0;
                $bandsWithCalls[$band] = array();
            }
            $byBand[$band][$arrayplacement]++;
            $bandCounts[$band]++;

            // Store callsigns per band (max 5 per band)
            if (count($bandsWithCalls[$band]) < 5) {
                if (!in_array($callsign, $bandsWithCalls[$band])) {
                    $bandsWithCalls[$band][] = $callsign;
                }
            }

            // Mode aggregation
            if (!isset($modeCounts[$mode])) {
                $modeCounts[$mode] = 0;
            }
            $modeCounts[$mode]++;
        }

        // Calculate hourly data per band (for heatmap)
        $hourlyByBand = array();
        foreach ($byBand as $band => $slots) {
            $hourlyByBand[$band] = array_fill(0, 24, 0);
            for ($h = 0; $h < 24; $h++) {
                // Aggregate two 30-minute slots into one hour
                $hourlyByBand[$band][$h] = $slots[$h * 2] + $slots[$h * 2 + 1];
            }
        }

        // Calculate best band, mode, and time window
        $bestBand = array('value' => null, 'total' => 0);
        foreach ($bandCounts as $band => $count) {
            if ($count > $bestBand['total']) {
                $bestBand = array('value' => $band, 'total' => $count);
            }
        }

        $bestMode = array('value' => null, 'total' => 0);
        foreach ($modeCounts as $mode => $count) {
            if ($count > $bestMode['total']) {
                $bestMode = array('value' => $mode, 'total' => $count);
            }
        }

        $bestWindow = null;
        $bestWindowCount = 0;
        foreach ($dataarray as $slot) {
            if ($slot['count'] > $bestWindowCount) {
                $bestWindowCount = $slot['count'];
                $bestWindow = $slot['time'];
            }
        }

        if (count($log) != 0) {
            header('Content-Type: application/json');
            $data['qsocount'] = count($log);
            $data['ok'] = 'OK';
            $data['qsodata'] = $dataarray;
            $data['by_band'] = $byBand;
            $data['hourly_by_band'] = $hourlyByBand;
            $data['bands'] = array_keys($byBand);

            usort(
                $data['bands'],
                function($b, $a) {
                    sscanf($a, '%f%s', $ac, $ar);
                    sscanf($b, '%f%s', $bc, $br);
                    return ($ar == $br) ? $ac <=> $bc : $ar <=> $br;
                }
            );

            // Rebuild hourly_by_band array in sorted band order
            $sortedHourlyByBand = array();
            foreach ($data['bands'] as $band) {
                $sortedHourlyByBand[$band] = $hourlyByBand[$band];
            }
            $data['hourly_by_band'] = $sortedHourlyByBand;

            $data['band_counts'] = $bandCounts;
            $data['mode_counts'] = $modeCounts;
            $data['best_band'] = $bestBand;
            $data['best_mode'] = $bestMode;
            $data['best_window'] = array('label' => $bestWindow, 'total' => $bestWindowCount);
            echo json_encode($data);
        }
        else {
            header('Content-Type: application/json');
            $data['error'] = 'No QSOs found to plot!';
            echo json_encode($data);
        }

    }

	/*
	 * Get's the worked modes from the log
	 */
	function get_worked_modes() {

		$this->load->model('logbooks_model');
		$logbooks_locations_array = $this->logbooks_model->list_logbook_relationships($this->session->userdata('active_station_logbook'));

		if ($logbooks_locations_array[0] === -1) {
			return null;
		}

		$location_list = "'".implode("','",$logbooks_locations_array)."'";

		// get all worked modes from database
		$data = $this->db->query(
			"SELECT distinct LOWER(`COL_MODE`) as `COL_MODE` FROM `" . $this->config->item('table_name') . "` WHERE station_id in (" . $location_list . ") order by COL_MODE ASC"
		);
		$results = array();
		foreach ($data->result() as $row) {
			array_push($results, $row->COL_MODE);
		}

		$data = $this->db->query(
			"SELECT distinct LOWER(`COL_SUBMODE`) as `COL_SUBMODE` FROM `" . $this->config->item('table_name') . "` WHERE station_id in (" . $location_list . ") and coalesce(COL_SUBMODE, '') <> '' order by COL_SUBMODE ASC"
		);
		foreach ($data->result() as $row) {
			if (!in_array($row, $results)) {
				array_push($results, $row->COL_SUBMODE);
			}
		}
		asort($results);

		return $results;
	}
}
