<?php
//******************************************************************************
// class.OdbcDatabase.php - ODBC Database connection
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
class OdbcDatabase
{
    public $_dsn;
    public $_user;
    public $_pwd;
    public $_conn;


    /*--------------------------------------------------------------------------
     * OdbcDatabase() : Initialize a new instance of OdbcDatabase.
     *
     * Arguments
     * ---------
     *  - dsn  : Dadabase name.
     *  - user : Database username.
     *  - pwd  : Password
     *
     * Returns : Formated value
     */
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
        if (!($res = @odbc_prepare($this->_conn, $query)))
            throw new OdbcException($this->_conn);

        if (!@odbc_execute($res, $params))
            throw new OdbcException($this->_conn);

        return $res;
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
        $res = $this->exec_query($query, $params);

        if (!(@odbc_fetch_row($res)))
            return NULL;

        return odbc_result($res, $column);

    }


    /*--------------------------------------------------------------------------
     * exec_select_query() : Prepare and execute a SELECT query.
     *
     * Arguments
     * ---------
     *  - table   : Table in the database to read from..
     *  - columns : Single column name or an array of columns name to read.
     *  - filters : Insert WHERE conditional to the query.
     *  - limit   : Maximum number of rows to return.
     *  - offset  : First row to return.
     *
     * Returns :
     */
    function exec_select_query($table, $columns="*", $filters=null, $limit=null, $offset=0)
    {

        $query = "SELECT $columns FROM $table";
        $data = array();

        /* Include WHERE statement to the query based on filter */
        if (is_array($filters)) {

            $where = "";
            $query .= " WHERE";

            foreach($filters as $filter_columns => $filter) {

                /* Split the conditional and argument */
                if (is_array($filter)) {
                    list($cond, $arg) = $filter;
                    $cond = strtoupper($cond);

                /* If conditional is not specified, defaults to "OR" */
                } else {
                    $cond = "OR";
                    $arg = $filter;
                }

                $filter_columns = explode(",", $filter_columns);

                if (!empty($where))
                    $where .= " $cond ";

                if ($arg[0] == "%")
                    $where .= " ((" . implode(" LIKE ?) $cond (", $filter_columns) . " LIKE ?))";
                else
                    $where .= " ((" . implode(" = ?) $cond (", $filter_columns) . " = ?))";

                /* Add the argument to the data array for odbc_prepare */
                $data = array_merge($data, array_fill(0, count($filter_columns), $arg));
           }

           $query .= $where;
        }

        if (!is_null($limit))
            $query .= " LIMIT $offset, $limit";

        if (!($res = @odbc_prepare($this->_conn, $query)))
            throw new OdbcException($this->_conn);

        if (!@odbc_execute($res, $data))
            throw new OdbcException($this->_conn);

        return $res;
    }
}
