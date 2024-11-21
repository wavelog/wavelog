<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed'); 


// Connection Library for trx-control
// https://github.com/hb9ssb/trx-control

class Trxd {

    /**
     * CodeIgniter instance
     */
    private $CI;

    /**
     * trx-control server IP
     */
    private $server_ip;

    /**
     * trx-control server port
     */
    private $server_port;

    /**
     * connection type
     */
    private $connection_type;

    /**
     * Server Timeout
     */
    private $trxd_timeout;

    /**
     * WS Path
     */
    private $trxd_ws_path;

    /**
     * Maximum message length
     */
    private $max_msg_length = 1048576; // 1MB

    function __construct() {
        $this->CI =& get_instance();

        $this->server_ip = $this->CI->config->item('trxd_server_ip');
        $this->server_port = $this->CI->config->item('trxd_server_port');
        $this->trxd_ws_path = $this->CI->config->item('trxd_ws_path');
        $this->connection_type = $this->CI->config->item('trxd_connection_type');
        $this->trxd_timeout = $this->CI->config->item('trxd_timeout');

    }
    
    public function request($command, $to, $parameters = array()) {

        $request = array(
            'request' => $command,
            'to' => $to
        );

        if (!empty($parameters)) {
            foreach ($parameters as $key => $value) {
                $request[$key] = $value;
            }
        }

        $raw_request = json_encode($request) . "\n";

        if ($this->connection_type == 'plain') {
            $result = $this->request_plain($raw_request);
        } elseif ($this->connection_type == 'ws') {
            $result = $this->request_ws($raw_request, false);
        } elseif ($this->connection_type == 'wss') {
            $result = $this->request_ws($raw_request, true);
        }

        if ($result === false) {
            return false;
        } else {
            return $result;
        }

    }

    private function request_plain($raw_request) {

        $socket = stream_socket_client(
            "tcp://{$this->server_ip}:{$this->server_port}",
            $errno,
            $errstr,
            $this->trxd_timeout,
            STREAM_CLIENT_CONNECT
        );
    
        if (!$socket) {
            log_message('error', "trxd: connection failed: $errstr ($errno)");
            return false;
        }
    
        stream_set_timeout($socket, $this->trxd_timeout);
    
        fwrite($socket, $raw_request);
    
        $response = fread($socket, $this->max_msg_length);
        if ($response === false) {
            log_message('error', 'trxd: could not read response');
            fclose($socket);
            return false;
        }
    
        fclose($socket);
    
        return $response;
    }

    private function request_ws($raw_request, $ssl) {

        $secKey = base64_encode(openssl_random_pseudo_bytes(16));
        $base_url = base_url();
        $header = "GET $this->trxd_ws_path HTTP/1.1\r\n" .
                  "Host: $this->server_ip\r\n" .
                  "Upgrade: websocket\r\n" .
                  "Connection: Upgrade\r\n" .
                  "Sec-WebSocket-Key: $secKey\r\n" .
                  "Sec-WebSocket-Version: 13\r\n" .
                  "Origin: $base_url\r\n\r\n";
    
        $protocol = $ssl ? "ssl://" : "tcp://";
    
        $contextOptions = $ssl ? [
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
                'allow_self_signed' => true
            ]
        ] : [];
    
        $context = stream_context_create($contextOptions);
    
        $socket = stream_socket_client(
            "{$protocol}{$this->server_ip}:{$this->server_port}",
            $errno,
            $errstr,
            $this->trxd_timeout,
            STREAM_CLIENT_CONNECT,
            $context
        );
    
        if (!$socket) {
            log_message('error', "trxd: connection failed: $errstr ($errno)");
            return false;
        }
    
        stream_set_timeout($socket, $this->trxd_timeout);
    
        fwrite($socket, $header);
        
        $responseHeader = fread($socket, 1500);
        if (strpos($responseHeader, ' 101 ') === false) {
            log_message('error', 'trxd: handshake failed: ' . $responseHeader);
            fclose($socket);
            return false;
        } else {
            log_message('debug', 'trxd: handshake successful!');
        }
    
        $frame = chr(0x81);
        $length = strlen($raw_request);
    
        if ($length <= 125) {
            $frame .= chr($length | 0x80);
        } elseif ($length <= 65535) {
            $frame .= chr(126 | 0x80) . pack("n", $length);
        } else {
            $frame .= chr(127 | 0x80) . pack("J", $length);
        }
    
        $mask = openssl_random_pseudo_bytes(4);
        $frame .= $mask;
        for ($i = 0; $i < $length; $i++) {
            $frame .= $raw_request[$i] ^ $mask[$i % 4];
        }
    
        fwrite($socket, $frame);
    
        $response = fread($socket, $this->max_msg_length);
        if ($response === false) {
            log_message('error', 'trxd: could not read response');
            fclose($socket);
            return false;
        }
    
        $response = substr($response, 2);
    
        fclose($socket);
    
        return $response;
    }
}