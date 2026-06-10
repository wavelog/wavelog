<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * API v2 - Response & Error helper
 *
 * Centralises the JSON envelope and HTTP status handling for the REST API v2
 * so individual resource handlers never call json_encode/http_response_code
 * directly. This keeps the wire format consistent across every endpoint.
 *
 * Envelope conventions (slim envelope, the HTTP status carries the semantics):
 *   success:  { "data": <object|array>, "meta"?: { ... } }
 *   error:    { "error": { "code": "<machine_code>", "message": "<human>", "details"?: ... } }
 *   no body:  204 No Content (e.g. after DELETE)
 */
class Api_v2_response {

	protected $CI;

	public function __construct() {
		$this->CI =& get_instance();
	}

	/**
	 * Emit a success response wrapping $data in a "data" envelope.
	 *
	 * @param mixed $data    The resource object or array to return.
	 * @param int   $status  HTTP status code (200, 201, ...).
	 * @param array $meta    Optional top-level meta block (e.g. pagination).
	 * @param array $headers Optional extra response headers, name => value.
	 */
	public function respond($data, $status = 200, $meta = null, $headers = []) {
		$payload = ['data' => $data];
		if ($meta !== null) {
			$payload['meta'] = $meta;
		}
		$this->emit($payload, $status, $headers);
	}

	/**
	 * Emit an error response in the { "error": { ... } } envelope.
	 *
	 * @param string $code    Machine-readable error code (e.g. "not_found").
	 * @param string $message Human-readable message.
	 * @param int    $status  HTTP status code (4xx/5xx).
	 * @param mixed  $details Optional structured details (e.g. field errors).
	 * @param array  $headers Optional extra response headers.
	 */
	public function error($code, $message, $status, $details = null, $headers = []) {
		$error = ['code' => $code, 'message' => $message];
		if ($details !== null) {
			$error['details'] = $details;
		}
		$this->emit(['error' => $error], $status, $headers);
	}

	/**
	 * Emit a 204 No Content response with an empty body.
	 *
	 * @param array $headers Optional extra response headers.
	 */
	public function no_content($headers = []) {
		$this->CI->output->set_status_header(204);
		foreach ($headers as $name => $value) {
			$this->CI->output->set_header($name . ': ' . $value);
		}
	}

	/**
	 * Serialise a payload as JSON with the given status and headers.
	 */
	protected function emit($payload, $status, $headers) {
		$this->CI->output
			->set_status_header($status)
			->set_content_type('application/json', 'utf-8');

		foreach ($headers as $name => $value) {
			$this->CI->output->set_header($name . ': ' . $value);
		}

		$this->CI->output->set_output(json_encode($payload));
	}
}
