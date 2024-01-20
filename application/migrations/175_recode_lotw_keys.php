<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_recode_lotw_keys extends CI_Migration {

	public function up() {
		$this->db->select('lotw_cert_id, cert_key');
		$query = $this->db->get('lotw_certs');
		foreach ($query->result() as $row) {
			$pkeyid = openssl_pkey_get_private(trim($row->cert_key), 'cloudlog');
			if (!$pkeyid) {
				log_message('error', 'Extracting private key of LoTW cert '.$row->lotw_cert_id.' failed.');
			}
			$pkey = null;
			$worked = openssl_pkey_export($pkeyid, $pkey, 'wavelog');
			if ($worked) {
				$this->db->set('cert_key', $pkey);
				$this->db->where('lotw_cert_id', $row->lotw_cert_id);
				$this->db->update('lotw_certs');
			} else {
				log_message('error', 'Updating LoTW key id '.$row->lotw_cert_id.' failed.');
			}
		}
	}

	public function down() {
		$this->db->select('lotw_cert_id, cert_key');
		$query = $this->db->get('lotw_certs');
		foreach ($query->result() as $row) {
			$pkeyid = openssl_pkey_get_private(trim($row->cert_key), 'wavelog');
			if (!$pkeyid) {
				log_message('error', 'Extracting private key of LoTW cert '.$row->lotw_cert_id.' failed.');
			}
			$pkey = null;
			$worked = openssl_pkey_export($pkeyid, $pkey, 'cloudlog');
			if ($worked) {
				$this->db->set('cert_key', $pkey);
				$this->db->where('lotw_cert_id', $row->lotw_cert_id);
				$this->db->update('lotw_certs');
			} else {
				log_message('error', 'Updating LoTW key id '.$row->lotw_cert_id.' failed.');
			}
		}
	}
}
