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
