<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Rate Limiting Library
 *
 * Implements sliding window rate limiting for API endpoints
 */
class Rate_limit {

    protected $CI;
    protected $cache;
    protected $rate_limits;

    public function __construct() {
        $this->CI =& get_instance();
        $this->CI->load->driver('cache', ['adapter' => 'file']);

        // Load rate limit config - if not set or empty, rate limiting is disabled
        $this->rate_limits = $this->CI->config->item('api_rate_limits');
    }

    /**
     * Check if rate limiting is enabled
     *
     * @return bool True if rate limiting is enabled
     */
    public function is_enabled() {
        return !empty($this->rate_limits) && is_array($this->rate_limits);
    }

    /**
     * Check and enforce rate limit for an endpoint
     *
     * @param string $endpoint The API endpoint name
     * @param string $identifier Unique identifier (API key, user ID, or IP)
     * @return array Array with 'allowed' (bool) and 'retry_after' (int|null)
     */
    public function check($endpoint, $identifier = null) {
        // If rate limiting is disabled, always allow
        if (!$this->is_enabled()) {
            return ['allowed' => true, 'retry_after' => null];
        }

        // Get the limit for this endpoint
        $limit = $this->get_limit($endpoint);

        // If no limit configured for this endpoint, allow
        if ($limit === null) {
            return ['allowed' => true, 'retry_after' => null];
        }

        // Generate identifier if not provided
        if ($identifier === null) {
            $identifier = $this->generate_identifier();
        }

        $max_requests = $limit['requests'];
        $window_seconds = $limit['window'];

        return $this->sliding_window_check($endpoint, $identifier, $max_requests, $window_seconds);
    }

    /**
     * Sliding window rate limit check
     *
     * @param string $endpoint The API endpoint name
     * @param string $identifier Unique identifier for the requester
     * @param int $max_requests Maximum requests allowed
     * @param int $window_seconds Time window in seconds
     * @return array Array with 'allowed' (bool) and 'retry_after' (int|null)
     */
    protected function sliding_window_check($endpoint, $identifier, $max_requests, $window_seconds) {
        $cache_key = 'rate_limit_' . md5($endpoint . '_' . $identifier);
        $now = time();

        // Get existing request timestamps from cache
        $request_timestamps = $this->CI->cache->get($cache_key);

        if ($request_timestamps === false) {
            $request_timestamps = [];
        }

        // Filter out timestamps that are outside the time window
        $window_start = $now - $window_seconds;
        $request_timestamps = array_filter($request_timestamps, function($timestamp) use ($window_start) {
            return $timestamp > $window_start;
        });

        // Check if limit exceeded
        if (count($request_timestamps) >= $max_requests) {
            // Sort timestamps to find the oldest one
            sort($request_timestamps);
            $oldest_request = $request_timestamps[0];
            $retry_after = ($oldest_request + $window_seconds) - $now;

            return [
                'allowed' => false,
                'retry_after' => max(1, $retry_after)
            ];
        }

        // Add current request timestamp
        $request_timestamps[] = $now;

        // Save back to cache with TTL equal to the window size
        $this->CI->cache->save($cache_key, $request_timestamps, $window_seconds);

        return [
            'allowed' => true,
            'retry_after' => null
        ];
    }

    /**
     * Get rate limit configuration for an endpoint
     *
     * @param string $endpoint The API endpoint name
     * @return array|null Array with 'requests' and 'window', or null if not configured
     */
    protected function get_limit($endpoint) {
        if (!is_array($this->rate_limits)) {
            return null;
        }

        // Check for endpoint-specific limit
        if (isset($this->rate_limits[$endpoint])) {
            return $this->rate_limits[$endpoint];
        }

        // Check for default limit
        if (isset($this->rate_limits['default'])) {
            return $this->rate_limits['default'];
        }

        return null;
    }

    /**
     * Generate identifier for rate limiting
     * Uses API key from request, session user ID, or IP address
     *
     * @return string Unique identifier
     */
    protected function generate_identifier() {
        $raw_input = json_decode(file_get_contents("php://input"), true);

        // Try API key first
        if (!empty($raw_input['key'])) {
            return 'api_key_' . $raw_input['key'];
        }

        // Try session user ID
        if (!empty($this->CI->session->userdata('user_id'))) {
            return 'user_' . $this->CI->session->userdata('user_id');
        }

        // Fallback to IP address
        return 'ip_' . $this->CI->input->ip_address();
    }

    /**
     * Send rate limit exceeded response
     *
     * @param int $retry_after Seconds until retry is allowed
     */
    public function send_limit_exceeded_response($retry_after) {
        http_response_code(429);
        header('Retry-After: ' . $retry_after);
        echo json_encode([
            'status' => 'failed',
            'reason' => 'Rate limit exceeded. Try again in ' . $retry_after . ' seconds.',
            'retry_after' => $retry_after
        ]);
        exit;
    }
}
