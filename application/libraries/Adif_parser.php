<?php
/*
   Copyright 2011 Jason Harris KJ4IWX

   Licensed under the Apache License, Version 2.0 (the "License");
   you may not use this file except in compliance with the License.
   You may obtain a copy of the License at

       http://www.apache.org/licenses/LICENSE-2.0

   Unless required by applicable law or agreed to in writing, software
   distributed under the License is distributed on an "AS IS" BASIS,
   WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
   See the License for the specific language governing permissions and
   limitations under the License.
*/

class ADIF_Parser
{

	var $data; //the adif data
    var $datasplit; // one entry is one QSO in the array
    var $currentarray = 0; // current place in the array
	var $i = 0; //the iterator
	var $headers = array();

	public function initialize() //this function locates the <EOH>
	{

		$pos = mb_stripos(mb_strtoupper($this->data, "UTF-8"), "<EOH>", 0, "UTF-8");

		$in_tag = false;
		$tag = "";
		$value_length = "";
		$value = "";

		if($pos == false) //did we find the end of headers?
		{
			// Just skip if we did not find (optional) headers
			$pos = 0;
			goto noheaders;
		};

		while($this->i < $pos)
		{
			//skip comments
			if(mb_substr($this->data, $this->i, 1, "UTF-8") == "#")
			{
				while($this->i < $pos)
				{
					if(mb_substr($this->data, $this->i, 1, "UTF-8") == "\n")
					{
						break;
					}

					$this->i++;
				}
			}else{
				//find the beginning of a tag
				if(mb_substr($this->data, $this->i, 1, "UTF-8") == "<")
				{
					$this->i++;
					//record the key
					while($this->i < $pos && mb_substr($this->data, $this->i, 1, "UTF-8") != ':')
					{
						$tag = $tag.mb_substr($this->data, $this->i, 1, "UTF-8");
						$this->i++;
					}

					$this->i++; //iterate past the :

					//find out how long the value is

					while($this->i < $pos && mb_substr($this->data, $this->i, 1, "UTF-8") != '>')
					{
						$value_length = $value_length.mb_substr($this->data, $this->i, 1, "UTF-8");
						$this->i++;
					}

					$this->i++; //iterate past the >

					$len = (int)$value_length;
					//copy the value into the buffer

					if ($this->i + $len > $pos) {
						$len = $len - ($this->i + $len - $pos);
					}
					$value = mb_substr($this->data, $this->i, $len, "UTF-8");
					$this->i = $this->i + $len;

					$this->headers[mb_strtolower(trim($tag), "UTF-8")] = $value; //convert it to lowercase and trim it in case of \r
					//clear all of our variables
					$tag = "";
					$value_length = "";
					$value = "";

				}
			}

			$this->i++;

		};


		$this->i = $pos+5; //iterate past the <eoh>

		// Skip to here in case we did not find headers
		noheaders:
		if($this->i >= mb_strlen($this->data, "UTF-8")) //is this the end of the file?
		{
			echo "Error: ADIF File Does Not Contain Any QSOs";
			return 0;
		};

        $this->datasplit = preg_split("/<eor>/i", mb_substr($this->data, $this->i, NULL, "UTF-8"));
		$this->currentarray = 0;
		return 1;
	}

	public function feed($input_data) //allows the parser to be fed a string
	{

		if (strpos($input_data, "<EOH>") !== false) {
			$arr=explode("<EOH>",$input_data);
			$newstring = $arr[1];
			$this->data = $newstring;
		} else {
			$this->data = $input_data;
		}

        $this->datasplit = preg_split("/<eor>/i", mb_substr($this->data, $this->i, NULL, "UTF-8"));
	}

	public function load_from_file($fname) //allows the user to accept a filename as input
	{
		$this->data = $string = mb_convert_encoding(file_get_contents($fname), "UTF-8");
	}

	//the following function does the processing of the array into its key and value pairs
	public function record_to_array($record)
{
	$return = array();
	$length = mb_strlen($record, "UTF-8");

	for ($a = 0; $a < $length; $a++) {
		if (mb_substr($record, $a, 1, "UTF-8") == '<') {
			$tag_name = "";
			$value = "";
			$len_str = "";
			$len = 0;
			$a++; //go past the <

			while ($a < $length && mb_substr($record, $a, 1, "UTF-8") != ':') { //get the tag
				$tag_name .= mb_substr($record, $a, 1, "UTF-8");
				$a++;
			}

			// If we reached the end unexpectedly, exit
			if ($a >= $length) break;

			$a++; //iterate past the colon

			// Get length string
			while ($a < $length && mb_substr($record, $a, 1, "UTF-8") != '>' && mb_substr($record, $a, 1, "UTF-8") != ':') {
				$len_str .= mb_substr($record, $a, 1, "UTF-8");
				$a++;
			}

			// If malformed tag or no length string, skip this tag
			if (!ctype_digit($len_str)) {
				continue;
			}

			$len = (int)$len_str;

			// Skip extra colon if present
			if ($a < $length && mb_substr($record, $a, 1, "UTF-8") == ':') {
				while ($a < $length && mb_substr($record, $a, 1, "UTF-8") != '>') {
					$a++;
				}
			}

			$a++; // past >

			// Hybrid logic: Try character-based first (as before), fall back to byte-based, since there are ADIFs in the wild, which count bytes instead of chars
			$value = "";
			$consumed_chars = 0;

			if (($a + $len) <= $length) {
				// Character-based extraction (ADIF spec compliant)
				$value = mb_substr($record, $a, $len, "UTF-8");
				$consumed_chars = $len;

				// Validate: check if next position starts with '<' (new tag) or end
				$next_pos = $a + $consumed_chars;
				if ($next_pos < $length) {
					$next_char = mb_substr($record, $next_pos, 1, "UTF-8");
					// If not '<' and not whitespace followed by '<', might be byte-length
					if ($next_char !== '<' && !ctype_space($next_char)) {
						// Character-based didn't land on tag boundary, try byte-based
						$byte_length = strlen($record);
						// Get byte offset for character position $a
						$char_before = mb_substr($record, 0, $a, "UTF-8");
						$byte_offset = strlen($char_before);

						if ($byte_offset + $len <= $byte_length) {
							// Extract by bytes
							$byte_value = substr($record, $byte_offset, $len);
							$value = $byte_value;
							// Calculate how many characters we actually consumed
							$consumed_chars = mb_strlen($value, "UTF-8");
						}
					}
				}
			} else { // length of string different than announced? try byte-based
				$byte_length = strlen($record);
				$char_before = mb_substr($record, 0, $a, "UTF-8");
				$byte_offset = strlen($char_before);

				if ($byte_offset + $len <= $byte_length) {
					$value = substr($record, $byte_offset, $len);
					$consumed_chars = mb_strlen($value, "UTF-8");
				} else {
					// Totally broken? skip the field
					break;
				}
			}

			$a += $consumed_chars - 1; // adjust for loop increment
			$return[mb_strtolower($tag_name, "UTF-8")] = rtrim($value);
		}

		// skip comments
		if (mb_substr($record, $a, 1, "UTF-8") == "#") {
			while ($a < $length) {
				if (mb_substr($record, $a, 1, "UTF-8") == "\n") {
					break;
				}
				$a++;
			}
		}
	}

	return $return;
}

	//finds the next record in the file
	public function get_record()
	{
	    // Are we at the end of the array containing the QSOs?
        if($this->currentarray >= count($this->datasplit)) {
            return array(); //return nothing
        } else {
			return $this->record_to_array($this->datasplit[$this->currentarray++]); //process and return output
        }
 	}

	public function get_header($key)
	{
		if(array_key_exists(mb_strtolower($key, "UTF-8"), $this->headers))
		{
			return $this->headers[mb_strtolower($key, "UTF-8")];
		}else{
			return NULL;
		}
	}

}
?>
