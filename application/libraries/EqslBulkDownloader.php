<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * EqslBulkDownloader - Parallel eQSL Image Download Library
 *
 * Uses curl_multi to download multiple eQSL card images in parallel
 * while respecting eQSL's rate limits through batch processing.
 */
class EqslBulkDownloader {

	const CONCURRENCY = 10;           // Number of parallel downloads
	const BATCH_DELAY = 2;          // Delay between batches (seconds)
	const MAX_BATCH_SIZE = 150;       // Safety limit per request
	const TIMEOUT = 30;              // Request timeout (seconds)

	private $ci;                    // CodeIgniter instance
	private $imagePath;             // Path to save images
	private $errors = array();      // Error tracking
	private $successCount = 0;      // Success counter

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->ci =& get_instance();
		$this->ci->load->library('electronicqsl');
		$this->ci->load->model('Eqsl_images');
		$this->ci->load->model('user_model');
		$this->ci->load->model('logbook_model');

		// Get image path
		$this->imagePath = $this->ci->Eqsl_images->get_imagePath('p');

		log_message('info', 'EqslBulkDownloader initialized with concurrency=' . self::CONCURRENCY);
	}

	/**
	 * Download multiple QSO images in parallel batches
	 * @param array $qsos Array of QSO records from eqsl_not_yet_downloaded()
	 * @return array Results with 'success_count', 'error_count', 'errors'
	 */
	public function downloadBatch($qsos) {
		if (empty($qsos)) {
			return array(
				'success_count' => 0,
				'error_count' => 0,
				'errors' => array()
			);
		}

		log_message('info', 'Starting parallel download of ' . count($qsos) . ' images');

		// Reset counters
		$this->errors = array();
		$this->successCount = 0;

		// Process in chunks based on concurrency
		$chunks = array_chunk($qsos, self::CONCURRENCY);
		$totalChunks = count($chunks);

		foreach ($chunks as $chunkIndex => $qsoChunk) {
			log_message('debug', 'Processing batch ' . ($chunkIndex + 1) . ' of ' . $totalChunks);

			// Download this batch in parallel
			$this->downloadBatchInternal($qsoChunk);

			// Add delay between batches (except for last batch)
			if ($chunkIndex < $totalChunks - 1) {
				log_message('debug', 'Sleeping for ' . self::BATCH_DELAY . ' seconds');
				sleep(self::BATCH_DELAY);
			}
		}

		return array(
			'success_count' => $this->successCount,
			'error_count' => count($this->errors),
			'errors' => $this->errors
		);
	}

	/**
	 * Download a batch of QSOs in parallel using curl_multi
	 * @param array $qsos Array of QSO records
	 */
	private function downloadBatchInternal($qsos) {
		$userId = $this->ci->session->userdata('user_id');
		$query = $this->ci->user_model->get_by_id($userId);
		$userRow = $query->row();
		$password = $userRow->user_eqsl_password;

		$this->ci->load->model('stations');

		// Prepare URLs for all QSOs
		$downloads = array();
		foreach ($qsos as $qso) {
			// Get station callsign from station_profile
			$station = $this->ci->stations->profile($qso['station_id']);
			if ($station && $station->num_rows() > 0) {
				$qso['COL_STATION_CALLSIGN'] = $station->row()->station_callsign;
			} else {
				log_message('error', 'Station not found for station_id: ' . $qso['station_id']);
				// Add error and skip this QSO
				$this->errors[] = array(
					'date' => $qso['COL_TIME_ON'],
					'call' => $qso['COL_CALL'],
					'mode' => $qso['COL_MODE'],
					'submode' => isset($qso['COL_SUBMODE']) ? $qso['COL_SUBMODE'] : '',
					'status' => 'Station profile not found',
					'qsoid' => $qso['COL_PRIMARY_KEY']
				);
				continue;
			}

			$url = $this->buildImageUrl($qso, $password);
			$downloads[] = array(
				'url' => $url,
				'qso' => $qso
			);
		}

		// Execute parallel downloads
		$results = $this->executeParallelDownloads($downloads);

		// Process results
		foreach ($results as $result) {
			if ($result['success']) {
				$this->successCount++;
			} else {
				$this->errors[] = array(
					'date' => $result['qso']['COL_TIME_ON'],
					'call' => $result['qso']['COL_CALL'],
					'mode' => $result['qso']['COL_MODE'],
					'submode' => isset($result['qso']['COL_SUBMODE']) ? $result['qso']['COL_SUBMODE'] : '',
					'status' => $result['error'],
					'qsoid' => $result['qso']['COL_PRIMARY_KEY']
				);

				// Break on rate limit
				if ($result['error'] === 'Rate Limited') {
					log_message('warning', 'eQSL rate limit detected, stopping batch');
					break;
				}
			}
		}
	}

	private function executeParallelDownloads($downloads) {
		$results = array();
		$mh = curl_multi_init();
		$curlMap = array(); // Map curl handles to download info

		// Step 1: Fetch all HTML pages in parallel to get image URLs
		foreach ($downloads as $index => $download) {
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $download['url']);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_TIMEOUT, self::TIMEOUT);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($ch, CURLOPT_USERAGENT, 'Wavelog-eQSL/1.0');

			curl_multi_add_handle($mh, $ch);
			$curlMap[(int)$ch] = array(
				'index' => $index,
				'qso' => $download['qso'],
				'handle' => $ch
			);
		}

		// Execute all HTML page handles simultaneously
		$active = null;
		do {
			$status = curl_multi_exec($mh, $active);
			if ($active) {
				curl_multi_select($mh); // Wait for activity
			}
		} while ($active && $status == CURLM_OK);

		// Collect HTML responses and extract image URLs
		$imageDownloads = array();
		$tempResults = array(); // Store intermediate results

		foreach ($curlMap as $curlInfo) {
			$ch = $curlInfo['handle'];
			$qso = $curlInfo['qso'];
			$qsoId = $qso['COL_PRIMARY_KEY'];

			$content = curl_multi_getcontent($ch);
			$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

			$tempResults[$qsoId] = array(
				'qso' => $qso,
				'success' => false,
				'error' => ''
			);

			if ($content !== false && $httpCode == 200) {
				// Parse HTML to find image URL
				$imageUrl = $this->parseImageResponse($content, $qsoId);

				if ($imageUrl['success']) {
					// Queue for parallel image download
					$imageDownloads[] = array(
						'url' => $imageUrl['url'],
						'qso' => $qso
					);
				} else {
					// Parsing failed
					$tempResults[$qsoId]['error'] = $imageUrl['error'];
				}
			} else {
				// HTTP error
				$tempResults[$qsoId]['error'] = 'HTTP ' . $httpCode;
			}

			curl_multi_remove_handle($mh, $ch);
			curl_close($ch);
		}

		curl_multi_close($mh);

		// Step 2: Download all actual images in parallel
		$downloadStatus = $this->downloadImagesInParallel($imageDownloads);

		// Build results array from download status and temp results
		foreach ($downloads as $download) {
			$qso = $download['qso'];
			$qsoId = $qso['COL_PRIMARY_KEY'];

			if (isset($downloadStatus[$qsoId])) {
				// Image was downloaded
				$results[] = array(
					'qso' => $qso,
					'success' => $downloadStatus[$qsoId]['success'],
					'error' => $downloadStatus[$qsoId]['error']
				);
			} elseif (isset($tempResults[$qsoId])) {
				// HTML parsing failed or HTTP error
				$results[] = $tempResults[$qsoId];
			} else {
				// Shouldn't happen
				$results[] = array(
					'qso' => $qso,
					'success' => false,
					'error' => 'Unknown error'
				);
			}
		}

		return $results;
	}

	private function downloadImagesInParallel($imageDownloads) {
		$mh = curl_multi_init();
		$curlMap = array();
		$status = array();

		// Initialize all curl handles for image downloads
		foreach ($imageDownloads as $index => $download) {
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $download['url']);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_TIMEOUT, self::TIMEOUT);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($ch, CURLOPT_USERAGENT, 'Wavelog-eQSL/1.0');

			curl_multi_add_handle($mh, $ch);
			$curlMap[$index] = array(
				'handle' => $ch,
				'qso' => $download['qso']
			);
		}

		// Execute all image handles simultaneously
		$active = null;
		do {
			$status_curl = curl_multi_exec($mh, $active);
			if ($active) {
				curl_multi_select($mh);
			}
		} while ($active && $status_curl == CURLM_OK);

		// Save images and build status array
		foreach ($curlMap as $item) {
			$ch = $item['handle'];
			$qso = $item['qso'];
			$qsoId = $qso['COL_PRIMARY_KEY'];

			$content = curl_multi_getcontent($ch);
			$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

			$status[$qsoId] = array(
				'success' => false,
				'error' => ''
			);

			if ($content !== false && $httpCode == 200) {
				// Check if already downloaded
				if ($this->ci->Eqsl_images->get_image($qsoId) == "No Image") {
					// Save image
					$filename = uniqid() . '.jpg';
					$imagePath = $this->imagePath . '/' . $filename;

					if (file_put_contents($imagePath, $content) !== false) {
						$this->ci->Eqsl_images->save_image($qsoId, $filename);
						$status[$qsoId]['success'] = true;
						log_message('debug', 'Successfully downloaded image for QSO ' . $qsoId);
					} else {
						log_message('error', 'Failed to save image for QSO ' . $qsoId);
						$status[$qsoId]['error'] = 'Failed to save image';
					}
				} else {
					// Already exists
					$status[$qsoId]['success'] = true;
					log_message('info', 'Image already exists for QSO ' . $qsoId);
				}
			} else {
				log_message('error', 'Failed to download image for QSO ' . $qsoId . ' HTTP ' . $httpCode);
				$status[$qsoId]['error'] = 'HTTP ' . $httpCode;
			}

			curl_multi_remove_handle($mh, $ch);
			curl_close($ch);
		}

		curl_multi_close($mh);

		return $status;
	}

	private function buildImageUrl($qso, $password) {
		$qso_timestamp = strtotime($qso['COL_TIME_ON']);
		$callsign = $qso['COL_CALL'];
		$band = $qso['COL_BAND'];
		$mode = $qso['COL_MODE'];
		$year = date('Y', $qso_timestamp);
		$month = date('m', $qso_timestamp);
		$day = date('d', $qso_timestamp);
		$hour = date('H', $qso_timestamp);
		$minute = date('i', $qso_timestamp);
		$username = $qso['COL_STATION_CALLSIGN'];

		return $this->ci->electronicqsl->card_image(
			$username,
			urlencode($password),
			$callsign,
			$band,
			$mode,
			$year,
			$month,
			$day,
			$hour,
			$minute
		);
	}

	private function parseImageResponse($html, $qsoId) {
		$result = array('success' => false, 'url' => '', 'error' => '');

		// Check for error in HTML
		if (strpos($html, 'Error') !== false) {
			$result['error'] = rtrim(preg_replace('/^\s*Error: /', '', $html));
			return $result;
		}

		// Parse HTML to find image
		$dom = new domDocument;
		libxml_use_internal_errors(true);
		$dom->loadHTML($html);
		libxml_clear_errors();
		$dom->preserveWhiteSpace = false;
		$images = $dom->getElementsByTagName('img');

		if (!isset($images) || $images->length == 0) {
			$h3 = $dom->getElementsByTagName('h3');
			if (isset($h3) && $h3->item(0) !== null) {
				$result['error'] = $h3->item(0)->nodeValue;
			} else {
				$result['error'] = 'Rate Limited';
			}
			return $result;
		}

		// Get first image URL
		$imageSrc = "https://www.eqsl.cc" . $images->item(0)->getAttribute('src');
		$result['success'] = true;
		$result['url'] = $imageSrc;

		return $result;
	}

	private function downloadAndSaveImage($url, $qsoId) {
		// Check if already downloaded
		if ($this->ci->Eqsl_images->get_image($qsoId) != "No Image") {
			return true; // Already exists
		}

		// Download image
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, self::TIMEOUT);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_USERAGENT, 'Wavelog-eQSL/1.0');
		$content = curl_exec($ch);
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		if ($content === false || $httpCode != 200) {
			log_message('error', 'Failed to download image from: ' . $url);
			return false;
		}

		// Save image
		$filename = uniqid() . '.jpg';
		$imagePath = $this->imagePath . '/' . $filename;

		if (file_put_contents($imagePath, $content) !== false) {
			$this->ci->Eqsl_images->save_image($qsoId, $filename);
			return true;
		} else {
			log_message('error', 'Failed to save image to: ' . $imagePath);
			return false;
		}
	}
}
