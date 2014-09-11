<?php
//******************************************************************************
// class.OdbcException.php - ODBC Exception
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

class OdbcException extends Exception
{
	protected $state = "";

	public function __construct($conn, $message = "", Exception $previous = NULL)
	{
		if (!empty($message))
			$message .= "\n";
			
		$this->state = odbc_error();
		
		$message .= preg_replace("/\[.*\]/", "", odbc_errormsg());
	
		parent::__construct($message, 0, $previous);
	}
	
	public function getState()
	{
		return $this->state;
	}

}

?>
