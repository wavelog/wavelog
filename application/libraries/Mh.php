<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once ('./src/phpMQTT.php');

class Mh {
	private $ci;
	private $mqsettings;
	private $mqtt;

	public function __construct() {
		$this->ci = & get_instance();
		$this->mqsettings['server']=($this->ci->config->item('mqtt_server') ?? '');
		$this->mqsettings['port']=($this->ci->config->item('mqtt_port') ?? 1883);
		$this->mqsettings['prefix']=($this->ci->config->item('mqtt_prefix') ?? 'wavelog/');
	}

	public function connect() {
		$server = $this->mqsettings['server'];
		$port = $this->mqsettings['port'];
		$clientId = uniqid('wl_'); 

		if ($this->mqsettings['server'] != '') {
			try {
				$this->mqtt = @new Wavelog\phpMQTT($server, $port, $clientId);

				if (!@$this->mqtt->connect(true, NULL)) {
					throw new Exception('Failed to connect to MQTT broker');
				}
				register_shutdown_function([$this, 'disconnect']);
				return true;
			} catch (Exception $e) {
				log_message('error','Error while trying to connect to MQTT: '.$e->getMessage());
				$this->mqtt=false;
			}
		}
	}

	public function disconnect() {
		if ($this->mqtt) {
			log_message('error', 'disconnect from MQTT broker');
			$this->mqtt->close();
		}
	}

	public function wl_event($topic, $message) {
		if ($this->mqsettings['server'] != '') {
			if (!($this->mqtt)) {
				$this->connect();
			}
			$this->publish($this->mqsettings['prefix'].$topic, $message);
		}
	}

	private function publish($topic, $message) {
		if ($this->mqtt) {
			$this->mqtt->publish($topic, $message, 0);
		} else {
			log_message('error', 'Cannot publish message: MQTT connection not established');
		}
	}
}
