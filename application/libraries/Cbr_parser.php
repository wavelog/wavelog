<?php
class CBR_Parser
{
    public function parse_from_file($filename, $serial_number_present = false, $trx_number_present = false) : array
	{
		//load file, call parser
        return $this->parse(mb_convert_encoding(file_get_contents($filename), "UTF-8"), $serial_number_present, $trx_number_present);
	}

    public function parse(string $input, $serial_number_present = false, $trx_number_present = false) : array
    {
        //split the input into lines
        $lines = explode("\n", trim($input));

        //initialize the result array
        $qso_lines_raw = [];
        $header = [];

        //helper variable to determine common 59 element indices in QSO lines
        $common_59_indices = null;

        //flag to indicate processing mode
        $qso_mode = false;

        //helper variable to determine the maximum number of qso data fields
        $max_qso_fields = 0;

        //loop through each line
        foreach ($lines as $line) {

            //if we encounter "QSO" or "X-QSO" switch processing mode to QSO mode
            if (strpos($line, 'QSO:') === 0 or strpos($line, 'X-QSO:') === 0) {
                $qso_mode = true;
            }else {
                $qso_mode = false;
            }

            //if we encounter a QTC, skip that line
            if(strpos($line, 'QTC:') === 0){
                continue;
            }

            //if we encounter "END-OF-LOG", stop processing lines
            if (strpos($line, 'END-OF-LOG') === 0) {
                break;
            }

            //process and collect header lines if qso mode is not set
            if (!$qso_mode) {

                //split the line into an array using ': ' as the delimiter
                $parts = explode(': ', $line, 2);

                //collect header information
                $header[$parts[0]] = trim($parts[1]);

                //skip to next line
                continue;
            }

            //process and collect QSO lines if qso mode is set
            if ($qso_mode) {

                //split the line into the elements
                $qso_elements = preg_split('/\s+/', trim($line));

                //check occurances of signal rapport values
                $counts = array_count_values($qso_elements);
                $count59s = ($counts["59"] ?? 0) + ($counts["599"] ?? 0);

                //for those few contests who do not have signal exchange, synthesize one based on best efforts
                if($count59s < 2 and isset($header['CALLSIGN'])){
                    
                    //get own callsign from header
                    $own_callsign = $header['CALLSIGN'];

                    //get position of own callsign
                    $index = array_search($own_callsign, $qso_elements);
                    
                    //add synthesized sent signal rapport after own callsign
                    if ($index !== false) {
                       array_splice($qso_elements, $index + 1, 0, "59");
                    }

                    //search for the next amateur radio callsign after my own and add another 59. Abort after first find
                    for ($i = $index + 1; $i < count($qso_elements); $i++) {
                        $value = $qso_elements[$i];
                        if(preg_match('/(?=[A-Z0-9]*[A-Z])[A-Z0-9]{1,3}[0-9][A-Z0-9]{0,7}/', $value) === 1){
                            array_splice($qso_elements, $i + 1, 0, "59");
                            break;
                        }
                    }
                }

                //determine maximum qso field size
                $max_qso_fields = max($max_qso_fields ?? 0, count($qso_elements));

                //find all occurrences of "59" or "599"
                $indices_of_59 = [];
                foreach ($qso_elements as $index => $value) {
                    if ($value === "59" or $value === "599") {
                        $indices_of_59[] = $index;
                    }
                }

                //abort further processing if we find a line without a valid signal report (59 or 599)
                if(count($indices_of_59) < 1) {

                    //return error result
                    $result = [];
                    $result['error'] = __("Broken CBR file - no valid exchange or callsigns found");
                    return $result;
                }

                //find common indices position
                if ($common_59_indices === null) {
                    //initialize common indices on the first iteration
                    $common_59_indices = $indices_of_59;
                } else {
                    //intersect with current indices, preserving only common indices
                    $common_59_indices = array_intersect($common_59_indices, $indices_of_59);
                }

                //add qso elements to qso line array
                array_push($qso_lines_raw, $qso_elements);

                //skip to next line
                continue;
            }
        }

        //abort further processing if no qso lines were found, return header only
        if(count($qso_lines_raw) < 1) {

            //return error result
            $result = [];
            $result['error'] = __("Broken CBR file - no QSO data found.");
            return $result;
        }

        //abort if basic things (Callsign and Contest ID) are not included in the header
        $header_fields = array_keys($header);
        if(!in_array('CALLSIGN', $header_fields) or !in_array('CONTEST', $header_fields)){
            
            //return error result
            $result = [];
            $result['error'] = __("Broken CBR file - incomplete header found.");
            return $result;
        }

        //get positions of 59s inside QSO lines
        $sent_59_pos = min($common_59_indices);
        $rcvd_59_pos = max($common_59_indices);

        //abort if position of sent and received signal exchange is identical
        if($sent_59_pos == $rcvd_59_pos){
            
            //return error result
            $result = [];
            $result['error'] = __("Broken CBR file - no valid exchange or callsigns found");
            return $result;
        }

        //get codeigniter instance
        $CI = &get_instance();

        //load Frequency library
        if(!$CI->load->is_loaded('Frequency')) {
            $CI->load->library('Frequency');
        }

        //using 59 positions, remake qso_lines
        $qso_lines = [];

        //change all QSOs into associative arrays with meaningful keys
        foreach ($qso_lines_raw as $line) {

            $qso_line = [];

            //get well defined fields
            $qso_line["QSO_MARKER"] = $line[0];
            $qso_line["FREQ"] = $line[1];
            $qso_line["CBR_MODE"] = $line[2];
            $qso_line["DATE"] = $line[3];
            $qso_line["TIME"] = $line[4];
            $qso_line["OWN_CALLSIGN"] = $line[5];
            $qso_line["SENT_59"] = $line[$sent_59_pos];

            //set serial if requested
            if($serial_number_present) {
                $qso_line["SENT_SERIAL"] = $line[$sent_59_pos + 1];
            }

            //get all remaining sent exchanges
            $exchange_nr = 1;
            $startindex = ($sent_59_pos + ($serial_number_present ? 2 : 1));
            $endindex = ($rcvd_59_pos - 1);
            for ($i = $startindex; $i < $endindex; $i++) {
                $qso_line["SENT_EXCH_" . $exchange_nr] = $line[$i];
                $exchange_nr++;
            }

            //get rest of the well defined fields
            $qso_line["RCVD_CALLSIGN"] = $line[$rcvd_59_pos - 1];
            $qso_line["RCVD_59"] = $line[$rcvd_59_pos];

            //set serial if requested
            if($serial_number_present) {
                $qso_line["RCVD_SERIAL"] = $line[$rcvd_59_pos + 1];
            }

            //get all remaining received exchanges
            $exchange_nr = 1;
            $startindex = ($rcvd_59_pos + ($serial_number_present ? 2 : 1));
            $endindex = count($line) - ($trx_number_present ? 1 : 0);
            for ($i = $startindex; $i < $endindex; $i++) {
                $qso_line["RCVD_EXCH_" . $exchange_nr] = $line[$i];
                $exchange_nr++;
            }

            //end of data in CQR format
            //enhance QSO data with additional fields
            $band = "";

            //convert frequency to integer if possible
            if(is_numeric($qso_line["FREQ"])) {
                $frequency = (int)$qso_line["FREQ"];
            }else{
                $frequency = null;
            }

            //convert CBR values to band where no real frequency is given.
            //if frequency is given, consult the frequency library
            switch ($qso_line["FREQ"]) {
                case '50':
                    $band = '6m';
                    break;
                case '70':
                    $band = '4m';
                    break;
                case '144':
                    $band = '2m';
                    break;
                case '222':
                    $band = '1.25m';
                    break;
                case '432':
                    $band = '70cm';
                    break;
                case '902':
                    $band = '33cm';
                    break;
                case '1.2G':
                    $band = '23cm';
                    break;
                case '2.3G':
                    $band = '13cm';
                    break;
                case '3.4G':
                    $band = '9cm';
                    break;
                case '5.7G':
                    $band = '6cm';
                    break;
                case '10G':
                    $band = '3cm';
                    break;
                case '24G':
                    $band = '1.25cm';
                    break;
                case '47G':
                    $band = 'SAT';
                    break;
                case '75G':
                    $band = 'SAT';
                    break;
                case '122G':
                    $band = 'SAT';
                    break;
                case '134G':
                    $band = 'SAT';
                    break;
                case '241G':
                    $band = 'SAT';
                    break;
                case 'LIGHT':
                    $band = 'SAT';
                    break;
                default:
                    $band = $CI->frequency->GetBand($frequency * 1000);
                    break;
            }

            //set band data for QSO
            $qso_line["BAND"] = $band;

            //get Wavelog mode
            $mode = "";
            switch ($qso_line["CBR_MODE"]) {
                case 'CW':
                    $mode = 'CW';
                    break;
                case 'PH':
                    $mode = 'SSB';
                    break;
                case 'FM':
                    $mode = 'FM';
                    break;
                case 'RY':
                    $mode = 'RTTY';
                    break;
                case 'DG':
                    //indeterminate Digimode
                    $mode = '';
                    break;
                default:
                    //something is wrong with the CBR file
                    $mode = '';
                    break;
            }

            //set mode data for QSO
            $qso_line["MODE"] = $mode;

            //collect new associative array
            array_push($qso_lines, $qso_line);
        }

        //construct result, including positions of 59s for further processing down the line
        $result = [];
        $result["HEADER"] = $header;
        $result["QSOS"] = $qso_lines;
        $result["SENT_59_POS"] = $sent_59_pos;
        $result["RCVD_59_POS"] = $rcvd_59_pos;
        $result["SENT_EXCHANGE_COUNT"] = $rcvd_59_pos - $sent_59_pos - ($serial_number_present ? 3 : 2);
        $result["RCVD_EXCHANGE_COUNT"] = $max_qso_fields - 1 - $rcvd_59_pos - ($serial_number_present ? 1 : 0) - ($trx_number_present ? 1 : 0);

        //return result
        return $result;
    }
}
?>
