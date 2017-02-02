<?php
//******************************************************************************
// class_odbc_database.php - ODBC Database connection
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

if(realpath(__FILE__) == realpath($_SERVER["SCRIPT_FILENAME"])) {
    header("Location:../index.php");
    exit();
}

class OdbcDatabase
{
    private $_dsn;
    private $_user;
    private $_pwd;

    public $conn;
    public $lastResults = null;


    /*--------------------------------------------------------------------------
     * OdbcDatabase() : Initialize a new instance of OdbcDatabase.
     *
     * Arguments
     * ---------
     *  - dsn  : Dadabase name.
     *  - user : Database username.
     *  - pwd  : Password
     *
     * Returns : Nothing
     */
    function OdbcDatabase($dsn, $user, $pwd)
    {
        if (!($this->conn = @odbc_pconnect($dsn, $user, $pwd)))
            throw new OdbcException($_conn, "Can't connect to the database");

        /* If a persistent connection is found, check if the link is still valid (avoid error 08S01) */
        if (!(@odbc_exec($this->conn, "SELECT 1;"))) {
            odbc_close($this->conn);

            if (!($this->conn = @odbc_pconnect($dsn, $user, $pwd)))
                throw new OdbcException($this->conn);
        }

    }


    /*--------------------------------------------------------------------------
     * exec_query() : Prepare a statement and execute the query.
     *
     * Arguments
     * ---------
     *  - query  : Query string.
     *  - params : An array containing the data to replace the placeholder in the
     *             query string.
     *
     * Returns : ODBC result identifier.
     */
    function exec_query($query, $params = array())
    {
        if (!($this->lastResults = @odbc_prepare($this->conn, $query)))
            throw new OdbcException($this->conn);

        if (!@odbc_execute($this->lastResults, $params))
            throw new OdbcException($this->conn);

        return $this->lastResults;
    }


    /*--------------------------------------------------------------------------
     * set_autocommit() : Enable or disable auto-commit.
     *
     * Arguments
     * ---------
     *  - auto : Auto commit ON or OFF.
     *
     * Returns : Nothing
     */
    function set_autocommit($auto)
    {
        odbc_autocommit($this->conn, $auto);
    }


    /*--------------------------------------------------------------------------
     * commit() : Commit current transaction.
     *
     * Arguments
     * ---------
     *  None
     *
     * Returns : Nothing
     */
    function commit()
    {
        odbc_commit($this->conn);
    }


    /*--------------------------------------------------------------------------
     * rollback() : Discard current transaction.
     *
     * Arguments
     * ---------
     *  None
     *
     * Returns : Nothing
     */
    function rollback()
    {
        odbc_rollback($this->conn);
    }


    /*--------------------------------------------------------------------------
     * exec_query_simple() : Same as exec_query(), but return the data of a
     *                       single column in the first row.
     *
     * Arguments
     * ---------
     *  - query  : Query string.
     *  - column : Column to read.
     *  - params : An array containing the data to replace the placeholder in the
     *             query string.
     *
     * Returns : Data contained in the column.
     */
    function exec_query_simple($query, $column, $params = array())
    {
        $this->lastResults = $this->exec_query($query, $params);

        if (!(@odbc_fetch_row($this->lastResults)))
            return null;

        return odbc_result($this->lastResults, $column);

    }


    /*--------------------------------------------------------------------------
     * create_query() : Creates a QueryBuilder instance for this database
     *                  connection.
     *
     * Arguments
     * ---------
     *  - table  : Database table to use.
     *
     * Returns : QueryBuilder object
     */
    function create_query($table)
    {
        return new QueryBuilder($this, $table);
    }
}
