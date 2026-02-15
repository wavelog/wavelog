<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Dxcluster_spotter {

    /**
     * Send a spot to a Telnet DX Cluster.
     *
     * @param string $host
     * @param int $port
     * @param string $spotter_call
     * @param string $dxcall
     * @param float $freq_khz
     * @param string $comment
     * @param int $timeout
     * @param string $password
     * @param string &$err
     * @return bool
     */
    public function send_spot($host, $port, $spotter_call, $dxcall, $freq_khz, $comment, $timeout, $password, &$err) {
        $err = '';
        $host = trim($host);
        $spotter_call = trim($spotter_call);
        $dxcall = trim($dxcall);
        $comment = trim($comment);

        $addr = $host;
        // Prefer IPv4 if hostname resolves to IPv6 but server can't use it
        if (strpos($host, ':') !== false && $host[0] !== '[') {
            $addr = '['.$host.']';
        }

        $errno = 0; $errstr = '';
        $fp = @fsockopen($addr, $port, $errno, $errstr, $timeout);
        if (!$fp) {
            $err = "Connect failed: {$errstr} ({$errno})";
            return false;
        }

        stream_set_timeout($fp, $timeout);
        stream_set_blocking($fp, true);

        // Read initial banner/prompt (non-fatal if empty)
        $banner = $this->read_until_prompt($fp, $timeout);

        // Some clusters show "login:" prompt; send callsign
        $this->write_line($fp, $spotter_call);

        // If password is required and server asks for it, send it.
        if ($password !== '') {
            $resp = $this->read_until_prompt($fp, $timeout);
            if (stripos($resp, 'password') !== false) {
                $this->write_line($fp, $password);
            } else {
                // keep resp for later
            }
        }

        // Build spot command (DXSpider supports "DX <call> <freq> <comment>")
        $freq_str = number_format($freq_khz, 1, '.', '');
        $line = "DX {$dxcall} {$freq_str}";
        if ($comment !== '') {
            $line .= " {$comment}";
        }
        $this->write_line($fp, $line);

        // Read response briefly to catch common rejections
        $resp2 = $this->read_for_seconds($fp, 2);

        fclose($fp);

        // Heuristics: if response contains "not allowed" or "register", treat as fail
        $lower = strtolower($resp2);
        if (strpos($lower, 'not allowed') !== false || strpos($lower, 'register') !== false || strpos($lower, 'won\'t be able to upload') !== false) {
            $err = trim($resp2) !== '' ? trim($resp2) : 'Cluster rejected spot.';
            return false;
        }

        return true;
    }

    private function write_line($fp, $line) {
        @fwrite($fp, rtrim($line, "\r\n") . "\n");
    }

    private function read_until_prompt($fp, $timeout) {
        $out = '';
        $start = time();
        while (time() - $start < $timeout) {
            $chunk = @fgets($fp, 4096);
            if ($chunk === false) {
                break;
            }
            $out .= $chunk;
            // stop when we see a typical prompt
            if (preg_match('/(login:|call:|password:|>\s*$)/i', $chunk)) {
                break;
            }
        }
        return $out;
    }

    private function read_for_seconds($fp, $seconds) {
        $out = '';
        $end = microtime(true) + $seconds;
        while (microtime(true) < $end) {
            $meta = stream_get_meta_data($fp);
            $chunk = @fgets($fp, 4096);
            if ($chunk !== false) {
                $out .= $chunk;
            } else {
                // avoid busy loop
                usleep(100000);
            }
        }
        return $out;
    }
}
