<?php

use Wavelog\Dxcc\Dxcc;

class Dxcluster_model extends CI_Model {
	public function dxc_spotlist($band = '20m', $maxage = 60, $de = '') {
		$this->load->helper(array('psr4_autoloader'));

		if($this->session->userdata('user_date_format')) {
			$custom_date_format = $this->session->userdata('user_date_format');
		} else {
			$custom_date_format = $this->config->item('qso_date_format');
		}

		$dxcache_url = ($this->optionslib->get_option('dxcache_url') == '' ? 'https://dxc.jo30.de/dxcache' : $this->optionslib->get_option('dxcache_url'));

		if ($band == "All") {
			$dxcache_url = $dxcache_url . '/spots/';
		} else {
			$dxcache_url = $dxcache_url . '/spots/'.$band;
		}
		// $this->load->model('logbooks_model');  lives in the autoloader
		$this->load->model('logbook_model');
		$logbooks_locations_array = $this->logbooks_model->list_logbook_relationships($this->session->userdata('active_station_logbook'));

		$this->load->driver('cache', array('adapter' => 'file', 'backup' => 'file'));
		if (!$jsonraw = $this->cache->get('dxcache'.$band)) {
			// CURL Functions
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $dxcache_url);
			curl_setopt($ch, CURLOPT_USERAGENT, 'Wavelog '.$this->optionslib->get_option('version').' DXLookup');
			curl_setopt($ch, CURLOPT_TIMEOUT, 10);
			curl_setopt($ch, CURLOPT_HEADER, false);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			$jsonraw = curl_exec($ch);
			curl_close($ch);
			$this->cache->save('dxcache'.$band, $jsonraw, 59);	// Cache DXClusterCache Instancewide for 59seconds
		}
		$json = json_decode($jsonraw);
		$date = date('Ymd', time());

		$dxccObj = new DXCC($date);

		// Create JSON object
		if (strlen($jsonraw)>20) {
			$spotsout=[];
			foreach($json as $singlespot){
				$spotband = $this->frequency->GetBand($singlespot->frequency*1000);
				$singlespot->band=$spotband;
				if (($band != 'All') && ($band != $spotband)) { continue; }
				$datetimecurrent = new DateTime("now", new DateTimeZone('UTC')); // Today's Date/Time
				$datetimespot = new DateTime($singlespot->when, new DateTimeZone('UTC'));
				$spotage = $datetimecurrent->diff($datetimespot);
				$minutes = $spotage->days * 24 * 60;
				$minutes += $spotage->h * 60;
				$minutes += $spotage->i;
				$singlespot->age=$minutes;
				$singlespot->when_pretty=date($custom_date_format . " H:i", strtotime($singlespot->when));

				if ($minutes<=$maxage) {
					if (!(property_exists($singlespot,'dxcc_spotted'))) {	// Check if we already have dxcc of spotted
						$dxcc=$dxccObj->dxcc_lookup($singlespot->spotted,date('Ymd', time()));
						$singlespot->dxcc_spotted->dxcc_id=$dxcc['adif'];
						$singlespot->dxcc_spotted->cont=$dxcc['cont'];
						$singlespot->dxcc_spotted->flag='';
						$singlespot->dxcc_spotted->entity=$dxcc['entity'];
					}
					if (!(property_exists($singlespot,'dxcc_spotter'))) {	// Check if we already have dxcc of spotter
						$dxcc=$dxccObj->dxcc_lookup($singlespot->spotter,date('Ymd', time()));
						$singlespot->dxcc_spotter->dxcc_id=$dxcc['adif'];
						$singlespot->dxcc_spotter->cont=$dxcc['cont'];
						$singlespot->dxcc_spotter->flag='';
						$singlespot->dxcc_spotter->entity=$dxcc['entity'];
					}
					if ( ($de != '') && ($de != 'Any') && (property_exists($singlespot->dxcc_spotter,'cont')) ){	// If we have a "de continent" and a filter-wish filter on that
						if (strtolower($de) == strtolower($singlespot->dxcc_spotter->cont ?? '')) {
							$singlespot->worked_dxcc = ($this->logbook_model->check_if_dxcc_worked_in_logbook($singlespot->dxcc_spotted->dxcc_id, $logbooks_locations_array, $singlespot->band) >= 1);
							$singlespot->cnfmd_dxcc = ($this->logbook_model->check_if_dxcc_cnfmd_in_logbook($singlespot->dxcc_spotted->dxcc_id, $logbooks_locations_array, $singlespot->band) >= 1);
							$singlespot->worked_call = ($this->logbook_model->check_if_callsign_worked_in_logbook($singlespot->spotted, $logbooks_locations_array, $singlespot->band) >= 1);
							$singlespot->cnfmd_call = ($this->logbook_model->check_if_callsign_cnfmd_in_logbook($singlespot->spotted, $logbooks_locations_array, $singlespot->band) >= 1);
							array_push($spotsout,$singlespot);
						}
					} else {	// No de continent? No Filter --> Just push
						$singlespot->worked_dxcc = ($this->logbook_model->check_if_dxcc_worked_in_logbook($singlespot->dxcc_spotted->dxcc_id, $logbooks_locations_array, $singlespot->band) >= 1);
						$singlespot->worked_call = ($this->logbook_model->check_if_callsign_worked_in_logbook($singlespot->spotted, $logbooks_locations_array, $singlespot->band) >= 1);
						$singlespot->cnfmd_dxcc = ($this->logbook_model->check_if_dxcc_cnfmd_in_logbook($singlespot->dxcc_spotted->dxcc_id, $logbooks_locations_array, $singlespot->band) >= 1);
						$singlespot->cnfmd_call = ($this->logbook_model->check_if_callsign_cnfmd_in_logbook($singlespot->spotted, $logbooks_locations_array, $singlespot->band) >= 1);
						array_push($spotsout,$singlespot);
					}
				}
			}
			return ($spotsout);
		} else {
			return '';
		}

	}

    public function dxc_qrg_lookup($qrg, $maxage = 120) {
		$this->load->helper(array('psr4_autoloader'));
	    if (is_numeric($qrg)) {

			$dxcache_url = ($this->optionslib->get_option('dxcache_url') == '' ? 'https://dxc.jo30.de/dxcache' : $this->optionslib->get_option('dxcache_url'));

		    $dxcache_url = $dxcache_url .'/spot/'.$qrg;

		    // CURL Functions
		    $ch = curl_init();
		    curl_setopt($ch, CURLOPT_URL, $dxcache_url);
		    curl_setopt($ch, CURLOPT_USERAGENT, 'Wavelog '.$this->optionslib->get_option('version').' DXLookup by QRG');
		    curl_setopt($ch, CURLOPT_HEADER, false);
		    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		    $jsonraw = curl_exec($ch);
		    curl_close($ch);
		    $json = json_decode($jsonraw);

			$date = date('Ymd', time());

			$dxccObj = new DXCC($date);

		    // Create JSON object
			if (strlen($jsonraw)>20) {
			    $datetimecurrent = new DateTime("now", new DateTimeZone('UTC')); // Today's Date/Time
			    $datetimespot = new DateTime($json->when, new DateTimeZone('UTC'));
			    $spotage = $datetimecurrent->diff($datetimespot);
			    $minutes = $spotage->days * 24 * 60;
			    $minutes += $spotage->h * 60;
			    $minutes += $spotage->i;
			    $json->age=$minutes;
			    if ($minutes<=$maxage) {
				    $dxcc=$dxccObj->dxcc_lookup($json->spotter,date('Ymd', time()));
				    $json->dxcc_spotter=$dxcc;
				    return ($json);
			    } else {
				    return '';
			    }
		    } else {
			    return '';
		    }
	    }
    }
}
