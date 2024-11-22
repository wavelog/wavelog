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
     * Insecure Curl Request
     */
    protected $insecure;



    function __construct($api) {
        $this->CI =& get_instance();
        $this->wavelog_id = ((($this->CI->session->userdata('wavelog_id') ?? '') == '') ? $this->CI->optionslib->get_wlid() : $this->CI->session->userdata('wavelog_id'));
        $this->url = rtrim($this->CI->config->item('dataservice_url') ?? 'https://data.wavelog.org', '/') . '/' . $api[0];
        $this->insecure = $this->CI->config->item('dataservice_insecure') == true ? true : false;
    }

    function request($data = []) {
        $data['wl_id'] = $this->wavelog_id;
        
        $response = $this->make_request($data);
        
        if ($response === false) {
            return false;
        }
        return json_decode($response, true);
    }
    
    private function make_request($data = []) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->url);
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
}