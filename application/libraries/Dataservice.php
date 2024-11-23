<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed'); 

class Dataservice {

    /**
     * Communicates with the Wavelog Dataservice
     */

    /**
     * Codeigniter Instance
     */
    protected $CI;

    /**
     * Wavelog ID
     */
    protected $wavelog_id;

    /**
     * URL of the Dataservice
     */
    protected $url;

    /**
     * API Endpoint
     */
    protected $api;

    /**
     * Insecure Curl Request
     */
    protected $insecure;

    /**
     * Dataservice is enabled
     */
    protected $dataservice_enabled;



    function __construct($api) {
        $this->CI =& get_instance();
        $this->wavelog_id = ((($this->CI->session->userdata('wavelog_id') ?? '') == '') ? $this->CI->optionslib->get_wlid() : $this->CI->session->userdata('wavelog_id'));
        $this->url = rtrim($this->CI->optionslib->get_option('dataservice_url') ?? 'https://data.wavelog.org', '/') . '/';
        $this->api = $api[0];
        $this->insecure = $this->CI->optionslib->get_option('dataservice_insecure') ?? false;
        $this->dataservice_enabled = $this->CI->optionslib->get_option('dataservice_enabled') ?? '';
        if ($this->dataservice_enabled == '') {
            $this->dataservice_enabled = true;
            $this->update_settings();
        }
    }

    function request($data = []) {
        if (!$this->dataservice_enabled) {
            return false;
        }
        
        $data['wl_id'] = $this->wavelog_id;
        
        $response = $this->make_request($data);
        
        if ($response === false) {
            return false;
        }
        return json_decode($response, true);
    }
    
    private function make_request($data = []) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->url . $this->api);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if ($this->insecure) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Wavelog');
    
        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            $error_msg = curl_error($ch);
            curl_close($ch);
            log_message('error', 'Dataservice make_request cURL error: ' . $error_msg);
            return false;
        }
    
        curl_close($ch);
    
        return $response;
    }

    private function update_settings() 
    {
        $this->CI->optionslib->update('dataservice_enabled', $this->dataservice_enabled, 'yes');
        $this->CI->optionslib->update('dataservice_insecure', $this->insecure, 'yes');
        $this->CI->optionslib->update('dataservice_url', $this->url, 'yes');
    }
}