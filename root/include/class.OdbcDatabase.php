<?php
//******************************************************************************
// class.OdbcDatabase.php - ODBC Database connection
// 
// Project   : Asterisk Y.A.A.M (Yet another asterisk manager)
// Version   : 0.1
// Author    : Benoit Frigon
// Last mod. : 3 mar. 2013
// 
// Copyright (c) 2011 - 2012 Benoit Frigon <bfrigon@gmail.com>
// www.bfrigon.com
// All Rights Reserved.
//
// This software is released under the terms of the GNU Lesser General Public 
// License v2.1. 
// A copy of which is available from http://www.gnu.org/copyleft/lesser.html
// 
//******************************************************************************

class OdbcDatabase
{
	public $_dsn;
	public $_user;
	public $_pwd;
	public $_conn;

	function OdbcDatabase($dsn, $user, $pwd)
	{
		if (!($this->_conn = @odbc_pconnect($dsn, $user, $pwd))) 
			throw new OdbcException($conn, "Can't connect to the database");

		/* If a persistent connection is found, check if the link is still valid (avoid error 08S01) */
		if (!(@odbc_exec($this->_conn, "SELECT 1;"))) {
			odbc_close($this->_conn);
		
			if (!($this->_conn = @odbc_pconnect($dsn, $user, $pwd))) 
				throw new OdbcException($conn);
		}		
	}
	
	function exec_query($query, $params = array())
	{
		if (!($res = @odbc_prepare($this->_conn, $query)))
			throw new OdbcException($this->_conn);

		if (!@odbc_execute($res, $params))
			throw new OdbcException($this->_conn);
			
		return $res;
	}

	function exec_query_simple($query, $column, $params = array())
	{	
		$res = $this->exec_query($query, $params);
			
		if (!(@odbc_fetch_row($res)))
			return NULL;

		return odbc_result($res, $column);
		
	}
	
}

?>
