<?php
//******************************************************************************
// class.HTTPException.php - HTTP exception class
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
class HTTPException extends Exception
{
    protected $code = 0;

    public function __construct($status_code, $message = "", Exception $previous = NULL)
    {
        $this->code = $status_code;
        $message = sprintf('%d - %s', $status_code, $this->get_status_title());

        parent::__construct($message, 0, $previous);
    }


    public function print_error_page()
    {
        $code = $this->code;
        $title = $this->get_status_title();
        $error = sprintf('%d - %s', $code, $title);

        header("HTTP/1.1 $code $title");
        echo '<body><head><title>', $error, '</title></head>';
        echo '<body>';
        echo '<h1>', $error, '</h1>';
        echo '</body></html>';

    }

    public function get_code()
    {
        return $this->code;
    }


    public function get_status_title()
    {
        switch ($this->code) {
            case 404: return 'Not Found';
            case 403: return 'Forbidden';
            case 500: return 'Server error';

            default: return 'Unknown';
        }
    }
}
