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
        if (!($this->lastResults = @odbc_prepare($this->_conn, $query)))
            throw new OdbcException($this->_conn);

        if (!@odbc_execute($this->lastResults, $params))
            throw new OdbcException($this->_conn);

        return $this->lastResults;
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
            return NULL;

        return odbc_result($this->lastResults, $column);

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
     * Returns : ODBC result identifier.
     */
    function exec_select_query($table, $columns="*", $filters=null, $limit=null, $offset=0, $order=null)
    {

        $query = "SELECT $columns FROM $table";
        $data = array();

        /* Add filters to the query. */
        if (!is_null($filters))
            $this->query_add_filters($query, $data, $filters);

        /* Add order by clause */
        if (!is_null($order)) {

            if (is_string($order))
                $order = array($order);

            $orderby = "";
            foreach ($order as $order_column) {
                if (!empty($orderby))
                    $orderby .= ", ";

                if ($order_column[0] == "!")
                    $orderby .= substr($order_column, 1) . " DESC";
                else
                    $orderby .= $order_column;
            }

            $query .= " ORDER BY $orderby";
        }

        /* Add limit and offset clauses to the query */
        if (!is_null($limit))
            $query .= " LIMIT $offset, $limit";

        /* Prepare and execute the query */
        if (!($this->lastResults = @odbc_prepare($this->_conn, $query)))
            throw new OdbcException($this->_conn);

        if (!odbc_execute($this->lastResults, $data))
            throw new OdbcException($this->_conn, "ODBC query execute failed!");

        return $this->lastResults;
    }


    /*--------------------------------------------------------------------------
     * exec_update_query() : Prepare and execute an UPDATE query.
     *
     * Arguments
     * ---------
     *  - table   : Table in the database to update to.
     *  - data    : An array containing the column name and value to set.
     *  - filters : An array containing the arguments to add to
     *              the WHERE clause.
     *  - limit   : Maximum number of rows to return.
     *
     * Returns : TRUE if successful.
     */
    function exec_update_query($table, $data, $filters, $limit=null)
    {
        $query = "UPDATE $table SET ";


        $separator = "";
        foreach($data as $key => $value) {
           $query .= "$separator$key = ?";
           $separator = ", ";
        }

        /* Add filters to the query. */
        $this->query_add_filters($query, $data, $filters);

        /* Add limit option to the query */
        if (!is_null($limit))
            $query .= " LIMIT $limit";

        /* Prepare and execute the query */
        if (!($this->lastResults = @odbc_prepare($this->_conn, $query)))
            throw new OdbcException($this->_conn);

        if (!($success = @odbc_execute($this->lastResults, $data)))
            throw new OdbcException($this->_conn);

        return $success;
    }


    /*--------------------------------------------------------------------------
     * exec_delete_query() : Prepare and execute a DELETE query.
     *
     * Arguments
     * ---------
     *  - table   : Table in the database to delete from.
     *  - filters : An array containing the arguments to add to
     *              the WHERE clause.
     *  - limit   : Maximum number of rows to return.
     *
     * Returns : TRUE if successful.
     */
    function exec_delete_query($table, $filters, $limit=1)
    {
        $query = "DELETE FROM $table";
        $data = array();

        /* Add filters to the query */
        $this->query_add_filters($query, $data, $filters);

        /* Add limit option to the query */
        if (!is_null($limit))
            $query .= " LIMIT $limit";

         /* Prepare and execute the query */
        if (!($this->lastResults = @odbc_prepare($this->_conn, $query)))
            throw new OdbcException($this->_conn);

        if (!($success = @odbc_execute($this->lastResults, $data)))
            throw new OdbcException($this->_conn);

        return $success;
    }


    /*--------------------------------------------------------------------------
     * exec_insert_query() : Prepare and execute an UPDATE query.
     *
     * Arguments
     * ---------
     *  - table   : Table in the database to insert into.
     *  - data    : An array containing the column name and value to set.
     *
     * Returns : TRUE if successful.
     */
    function exec_insert_query($table, $data)
    {
        $query = "INSERT INTO $table (";


        $separator = "";
        foreach ($data as $key => $value) {
            $query .= "$separator$key";
            $separator = ", ";
        }

        $query .= ") VALUES (" . implode(", ", array_fill(0, count($data), "?")) . ")";

         /* Prepare and execute the query */
        if (!($this->lastResults = @odbc_prepare($this->_conn, $query)))
            throw new OdbcException($this->_conn);

        if (!($success = @odbc_execute($this->lastResults, $data)))
            throw new OdbcException($this->_conn);

        return $sucess;
    }


    /*--------------------------------------------------------------------------
     * query_add_filters() : Inserts a WHERE clause to the query.
     *
     * Arguments
     * ---------
     *  - query   : The query to add filters to.
     *  - data    :
     *  - filters : An array containing the conditions.
     *
     *              array(
     *                  array(array("column1,column2", "operator"), "value", ["logic"]),
     *                  array("column=?", "value", ["logic"])
     *              )
     *
     * Returns : Nothing
     */
    private function query_add_filters(&$query, &$data, $filters)
    {

        if (is_null($filters))
            return;

        if (!is_array($filters))
            throw new Exception(__FUNCTION__ . "() : Wrong argument type. 'filters' expects an array.");

        $where = "";
        foreach($filters as $filter) {

            if (!is_array($filter))
                throw new Exception(__FUNCTION__ . "() : Wrong argument type. 'filter' expects an array.");

            array_pad($filter, 3, "");
            list($clause, $arguments, $logic) = $filter;

            if (empty($logic))
                $logic = "OR";

            if (!empty($where))
                $where .= " $logic ";

            if (!is_array($arguments))
                $arguments = array($arguments);


            /* If clause is a string, it represents a single column */
            if (is_string($clause)) {
                $where .= "($clause)";
                $data = array_merge($data, $arguments);

            /* If clause is an array, replicate the clause for each columns.
               (col1=? or col2=? or ...) */
            } elseif (is_array($clause)) {

                array_pad($filter, 3, "");
                list($columns, $clause_op, $logic) = $clause;

                if (empty($logic))
                    $logic = "OR";


                $columns = explode(',', $columns);

                $where .= "((" . implode(" $clause_op) $logic (", $columns) . " $clause_op))";

                foreach($columns as $column)
                    $data = array_merge($data, $arguments);

            /* Invalid clause */
            } else {
                throw new Exception(__FUNCTION__ . "() : Wrong argument type. 'clause' expects an array or string");
            }
        }

        if (empty($where))
            return;

        $query .= " WHERE $where";
   }
}
