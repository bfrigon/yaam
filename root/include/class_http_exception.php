<?php
//******************************************************************************
//
// Project : Asterisk Y.A.A.M (Yet another asterisk manager)
// Author  : Benoit Frigon <www.bfrigon.com>
//
// Contributors
// ============
//
//
//
// -----------------------------------------------------------------------------
//
// Copyright (c) 2017 Benoit Frigon
//
// Permission is hereby granted, free of charge, to any person obtaining a copy
// of this software and associated documentation files (the "Software"), to deal
// in the Software without restriction, including without limitation the rights
// to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
// copies of the Software, and to permit persons to whom the Software is
// furnished to do so, subject to the following conditions:
//
// The above copyright notice and this permission notice shall be included in
// all copies or substantial portions of the Software.
//
// THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
// IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
// FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
// AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
// LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
// OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
// THE SOFTWARE.
//
//******************************************************************************

if(realpath(__FILE__) == realpath($_SERVER["SCRIPT_FILENAME"])) {
    header("Location:../index.php");
    exit();
}


class HttpException extends Exception
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
