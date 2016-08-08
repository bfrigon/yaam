<?php
//******************************************************************************
// class.OdbcException.php - ODBC Exception
//
// Project   : Asterisk Y.A.A.M (Yet another asterisk manager)
// Author    : Benoit Frigon <benoit@frigon.info>
//
// Copyright (c) Benoit Frigon
// www.bfrigon.com
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
