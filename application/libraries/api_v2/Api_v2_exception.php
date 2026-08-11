<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * API v2 - Resource exception
 *
 * Resource handlers throw this to signal an error condition. The central
 * dispatcher (Api_v2 controller) catches it and turns it into a structured
 * error response via the Api_v2_response library. This keeps error handling
 * out of every handler method and guarantees a consistent error envelope.
 */
class Api_v2_exception extends Exception {

	/** @var string Machine-readable error code (e.g. "not_found"). */
	protected $error_code;

	/** @var int HTTP status code to send (4xx/5xx). */
	protected $http_status;

	/** @var mixed Optional structured details (e.g. validation errors). */
	protected $details;

	/**
	 * @param string $error_code  Machine-readable code (e.g. "forbidden").
	 * @param string $message     Human-readable message.
	 * @param int    $http_status HTTP status code.
	 * @param mixed  $details     Optional structured details.
	 */
	public function __construct($error_code, $message, $http_status, $details = null) {
		parent::__construct($message);
		$this->error_code  = $error_code;
		$this->http_status = $http_status;
		$this->details     = $details;
	}

	public function get_error_code() {
		return $this->error_code;
	}

	public function get_http_status() {
		return $this->http_status;
	}

	public function get_details() {
		return $this->details;
	}
}
