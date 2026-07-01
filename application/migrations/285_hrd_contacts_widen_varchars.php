<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Hrd_contacts_widen_varchars extends CI_Migration {

	public function up() {
		$table = $this->config->item('table_name');
		// Widen a set of mid-size VARCHAR columns in the (very wide) QSO table
		// from varchar(<=63) to varchar(64).
		//
		// Why: with utf8mb4 a varchar(N) reserves up to 4*N bytes inline on the
		// InnoDB clustered-index page. Columns whose maximum size is <= 255 bytes
		// (utf8mb4 varchar(<=63)) must keep that full width inline, while columns
		// whose maximum size exceeds 255 bytes (utf8mb4 varchar(>=64)) can be
		// stored off-page, leaving only a ~20-byte pointer inline. With ~170
		// columns this table exceeds InnoDB's ~8126-byte row-size limit, which
		// MySQL 8 rejects at table creation time because innodb_strict_mode is ON
		// by default (older MySQL only logged a warning). Growing these columns to
		// varchar(64) pushes them off-page and brings the row back under the limit
		// while keeping the columns indexable utf8mb4 VARCHARs and never truncating
		// existing data.
		if (!$this->db->table_exists($table)) {
			return;
		}

		// Idempotency guard: skip the (table-rebuilding) ALTER when the columns
		// have already been widened, e.g. on a fresh install where install.sql
		// already creates them as varchar(64).
		$check = $this->db->query("
			SELECT CHARACTER_MAXIMUM_LENGTH AS len
			FROM information_schema.COLUMNS
			WHERE TABLE_SCHEMA = DATABASE()
			  AND TABLE_NAME = '{$table}'
			  AND COLUMN_NAME = 'COL_RST_SENT'
		")->row();
		if ($check && (int) $check->len >= 64) {
			log_message('info', 'Mig 285 - HRD VARCHAR columns already widened, skipping.');
			return;
		}

		// Single ALTER so the table is rebuilt only once.
		$this->db->query("ALTER TABLE `{$table}`
			MODIFY `COL_CALL` varchar(64) DEFAULT NULL,
			MODIFY `COL_CNTY` varchar(64) DEFAULT NULL,
			MODIFY `COL_CONTACTED_OP` varchar(64) DEFAULT NULL,
			MODIFY `COL_CONTEST_ID` varchar(64) DEFAULT NULL,
			MODIFY `COL_EQ_CALL` varchar(64) DEFAULT NULL,
			MODIFY `COL_MS_SHOWER` varchar(64) DEFAULT NULL,
			MODIFY `COL_MY_CITY` varchar(64) DEFAULT NULL,
			MODIFY `COL_MY_CNTY` varchar(64) DEFAULT NULL,
			MODIFY `COL_MY_POSTAL_CODE` varchar(64) DEFAULT NULL,
			MODIFY `COL_MY_POTA_REF` varchar(64) DEFAULT NULL,
			MODIFY `COL_MY_SIG` varchar(64) DEFAULT NULL,
			MODIFY `COL_MY_SOTA_REF` varchar(64) DEFAULT NULL,
			MODIFY `COL_MY_STATE` varchar(64) DEFAULT NULL,
			MODIFY `COL_MY_VUCC_GRIDS` varchar(64) DEFAULT NULL,
			MODIFY `COL_MY_WWFF_REF` varchar(64) DEFAULT NULL,
			MODIFY `COL_OPERATOR` varchar(64) DEFAULT NULL,
			MODIFY `COL_OWNER_CALLSIGN` varchar(64) DEFAULT NULL,
			MODIFY `COL_PFX` varchar(64) DEFAULT NULL,
			MODIFY `COL_POTA_REF` varchar(64) DEFAULT NULL,
			MODIFY `COL_PRECEDENCE` varchar(64) DEFAULT NULL,
			MODIFY `COL_REGION` varchar(64) DEFAULT NULL,
			MODIFY `COL_RST_RCVD` varchar(64) DEFAULT NULL,
			MODIFY `COL_RST_SENT` varchar(64) DEFAULT NULL,
			MODIFY `COL_SAT_MODE` varchar(64) DEFAULT NULL,
			MODIFY `COL_SAT_NAME` varchar(64) DEFAULT NULL,
			MODIFY `COL_SIG` varchar(64) DEFAULT NULL,
			MODIFY `COL_SOTA_REF` varchar(64) DEFAULT NULL,
			MODIFY `COL_SRX_STRING` varchar(64) DEFAULT NULL,
			MODIFY `COL_STATE` varchar(64) DEFAULT NULL,
			MODIFY `COL_STATION_CALLSIGN` varchar(64) DEFAULT NULL,
			MODIFY `COL_STX_STRING` varchar(64) DEFAULT NULL,
			MODIFY `COL_SUBMODE` varchar(64) DEFAULT NULL,
			MODIFY `COL_WWFF_REF` varchar(64) DEFAULT NULL
		");
	}

	public function down() {
		// No-op: shrinking these columns back could re-introduce the row-size
		// error on strict MySQL 8 and risk truncating data, so the change is
		// intentionally not reverted.
	}
}
