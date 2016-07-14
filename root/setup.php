<?php

/*
function init()
{
	global $DB_CONN;

	try {
		session_start();
		
		load_global_config();
		
		init_database();
		
		
		if ($res = @odbc_exec($DB_CONN, 'SELECT user FROM users where user="admin";')) {
			if (@odbc_fetch_row($res) && odbc_result($res, 'user') == 'admin') {
				return false;
			}
		}
		
		
		$query = 'CREATE TABLE IF NOT EXISTS users (
				  user varchar(40) NOT NULL,
				  pwhash char(64) NOT NULL,
				  pgroups varchar(128) NOT NULL DEFAULT "user",
				  config_theme varchar(40) NOT NULL DEFAULT "default",
				  PRIMARY KEY (user))';

		if (!@odbc_exec($DB_CONN, $query))
			throw new OdbcException($DB_CONN);

		
		$query = 'INSERT INTO users (user, pwhash, pgroups) VALUES (?,?,?);';
		$res = odbc_prepare($DB_CONN, $query);
		$values = array(
			'admin', 
			hash('sha256', 'admin', false),
			'admin');
		
		if (!@odbc_execute($res, $values))
			throw new OdbcException($DB_CONN);
				  
		return true;
		
	} catch (Exception $e) {
		$error = $e->getmessage();
		include('include/fatal_error.php');
		
		die();
	}
}
*/

?>
