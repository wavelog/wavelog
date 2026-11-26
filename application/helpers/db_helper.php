<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Database helpers for reading large results without loading them into
 * memory as a whole.
 */

if (!function_exists('db_unbuffered_rows')) {
	/**
	 * Runs a prepared SELECT and yields its rows one at a time, read unbuffered
	 * from the server, so large results never sit in memory as a whole. The
	 * database connection must not be used for other queries until the last
	 * row was read or the generator goes out of scope.
	 *
	 * @param string $sql    SELECT statement with ? placeholders
	 * @param array  $params Values for the placeholders, in order
	 * @return \Generator stdClass per row with the column names as properties
	 */
	function db_unbuffered_rows($sql, $params) {
		$CI =& get_instance();
		$stmt = $CI->db->conn_id->prepare($sql);
		$stmt->execute($params);

		$row = array();
		$refs = array();
		foreach ($stmt->result_metadata()->fetch_fields() as $field) {
			$row[$field->name] = null;
			$refs[] = &$row[$field->name];
		}
		call_user_func_array(array($stmt, 'bind_result'), $refs);

		try {
			while ($stmt->fetch()) {
				$object = new stdClass;
				foreach ($row as $key => $value) {
					$object->$key = $value;
				}
				yield $object;
			}
		} finally {
			$stmt->close();
		}
	}
}
