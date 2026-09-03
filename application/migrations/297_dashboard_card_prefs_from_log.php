<?php

defined('BASEPATH') OR exit('No direct script access allowed');

/*
	Set the dashboard card preferences (QSL/eQSL/LoTW/QRZ/Club Log) from the
	user's log. These cards used to be hidden on the dashboard as long as the
	corresponding counters were zero. That criteria is evaluated once here and
	stored as a user option instead, so the cards are purely option-driven.
	Counters are checked against all QSOs of the user (any station location),
	because the option is a per-user preference.
*/

class Migration_dashboard_card_prefs_from_log extends CI_Migration {

	public function up()
	{
		$log_table = $this->config->item('table_name');

		// Conditions mirror logbook_model->dashboard_stats_batch()
		$cards = [
			'show_qslcards'	=> "(t.COL_QSL_SENT = 'Y' OR t.COL_QSL_RCVD = 'Y' OR t.COL_QSL_SENT IN ('Q', 'R'))",
			'show_lotw'	=> "(t.COL_LOTW_QSL_SENT = 'Y' OR t.COL_LOTW_QSL_RCVD = 'Y')",
			'show_eqsl'	=> "(t.COL_EQSL_QSL_SENT = 'Y' OR t.COL_EQSL_QSL_RCVD = 'Y')",
			'show_qrz'	=> "(t.COL_QRZCOM_QSO_UPLOAD_STATUS = 'Y' OR t.COL_QRZCOM_QSO_DOWNLOAD_STATUS = 'Y')",
			'show_clublog'	=> "(t.COL_CLUBLOG_QSO_UPLOAD_STATUS = 'Y' OR t.COL_CLUBLOG_QSO_DOWNLOAD_STATUS = 'Y')",
		];

		foreach ($cards as $option_name => $criteria) {
			$this->db->query("
				INSERT INTO user_options (user_id, option_type, option_name, option_key, option_value)
				SELECT u.user_id, 'dashboard', '" . $option_name . "', 'boolean',
					CASE WHEN EXISTS (
						SELECT 1 FROM " . $log_table . " t
						JOIN station_profile sp ON sp.station_id = t.station_id
						WHERE sp.user_id = u.user_id AND " . $criteria . "
					) THEN '1' ELSE '0' END
				FROM users u
				ON DUPLICATE KEY UPDATE option_value = VALUES(option_value)
			");
		}
	}

	public function down()
	{
		// Options are kept on rollback; the dashboard cards fall back to
		// their default (shown) if the rows are removed.
	}

}
