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


class QueryBuilder
{

    private $_table = "";
    private $_orderby = "";
    private $_limit = 0;
    private $_offset = 0;
    private $_where = "";
    private $_where_data = array();
    private $_where_nestcount = 0;


    /*--------------------------------------------------------------------------
     * QueryBuilder() : Initialize a new instance of QueryBuilder.
     *
     * Arguments
     * ---------
     *  - database  : ODBCDatabase object.
     *  - table     : Database table to use to run the query on.
     *
     * Returns : None
     */
    public function QueryBuilder($database, $table)
    {
        $this->_database = $database;
        $this->_table = $table;

        $this->clear_all();

    }


    /*--------------------------------------------------------------------------
     * run_query_select() : Prepare and execute a SELECT query.
     *
     * Arguments
     * ---------
     *  - columns : Single column name or an array of columns name to read.
     *
     * Returns : ODBC result identifier.
     */
    public function run_query_select($columns)
    {
        $query = "SELECT ";
        $data = array();

        /* Adds the list of columns to the query */
        if (is_array($columns)) {
            $query .= implode($columns, ", ");

        } else if (is_string($columns)) {
            $query .= $columns;

        } else {
            throw new Exception(__FUNCTION__ . "() : Wrong argument type. 'columns' expects an array or string");
        }


        $query .= " FROM {$this->_table} ";

        /* Adds the 'where' clause to the query. */
        if (!empty($this->_where)) {
            $query .= $this->get_where_clause();

            $data = array_merge($data, $this->_where_data);
        }

        /* Adds the 'order by' clause to the query */
        if (!empty($this->_orderby)) {
            $query .= " ORDER BY {$this->_orderby} ";
        }

        /* Adds the limit and offset clauses to the query */
        if ($this->_limit > 0)
            $query .= " LIMIT {$this->_offset}, {$this->_limit} ";

        /* Prepares and execute the query */
        return $this->_database->exec_query($query, $data);
    }


    /*--------------------------------------------------------------------------
     * run_query_select_simple() : Prepare and execute a SELECT query, then return
     *                             the data contained in the specified column.
     *
     * Arguments
     * ---------
     *  - columns : Single column name.
     *
     * Returns : ODBC result identifier.
     */
    public function run_query_select_simple($column)
    {
        $results = $this->run_query_select($column);

        if (!(@odbc_fetch_row($results)))
            return null;

        $value = odbc_result($results, $column);

        odbc_free_result($results);

        return $value;
    }


    /*--------------------------------------------------------------------------
     * run_query_delete() : Prepare and execute a DELETE query.
     *
     * Arguments
     * ---------
     *  None
     *
     * Returns : Number of rows affected
     */
    public function run_query_delete()
    {
        $query = "DELETE FROM {$this->_table}";
        $data = array();

        /* Adds the 'where' clause to the query */
        if (!empty($this->_where)) {
            $query .= $this->get_where_clause();

            $data = array_merge($data, $this->_where_data);
        }

        /* Adds limit clause to the query */
        if ($this->_limit > 0)
            $query .= " LIMIT {$this->_limit}";

        /* Prepare and execute the query */
        $result = $this->_database->exec_query($query, $data);

        $num_rows = odbc_num_rows($result);
        odbc_free_result($result);

        return $num_rows;
    }


    /*--------------------------------------------------------------------------
     * run_query_insert() : Prepare and execute an UPDATE query.
     *
     * Arguments
     * ---------
     *  - data : An array containing the column name and value to set.
     *
     * Returns : Number of rows affected
     */
    public function run_query_insert($data)
    {
        $query = "INSERT INTO {$this->_table} (";


        $separator = "";
        foreach ($data as $key => $value) {
            $query .= "$separator$key";
            $separator = ", ";
        }

        $query .= ") VALUES (" . implode(", ", array_fill(0, count($data), "?")) . ")";

        /* Prepare and execute the query */
        $result = $this->_database->exec_query($query, $data);

        $num_rows = odbc_num_rows($result);
        odbc_free_result($result);

        return $num_rows;
    }


    /*--------------------------------------------------------------------------
     * run_query_update() : Prepare and execute an UPDATE query.
     *
     * Arguments
     * ---------
     *  - data : An array containing the column name and value to set.
     *
     * Returns : TRUE if successful.
     */
    public function run_query_update($data)
    {

        $query = "UPDATE {$this->_table} SET ";


        $separator = "";
        foreach($data as $key => $value) {
           $query .= "$separator$key = ?";
           $separator = ", ";
        }

        $data = array_values($data);


        /* Adds the 'where' clause to the query. */
        if (!empty($this->_where)) {
            $query .= $this->get_where_clause();

            $data = array_merge($data, $this->_where_data);
        }

        /* Prepare and execute the query */
        $result = $this->_database->exec_query($query, $data);

        $num_rows = odbc_num_rows($result);
        odbc_free_result($result);

        return $num_rows;
    }


    /*--------------------------------------------------------------------------
     * clear_where() : Remove the 'WHERE' clause from the query.
     *
     * Arguments
     * ---------
     *  None
     *
     * Returns : None
     */
    public function clear_where()
    {
        $this->_where = "";
        $this->_where_data = array();
        $this->_where_nestcount = 0;
    }


    /*--------------------------------------------------------------------------
     * clear_orderby() : Remove the 'ORDER BY' clause from the query.
     *
     * Arguments
     * ---------
     *  None
     *
     * Returns : None
     */
    public function clear_orderby()
    {
        $this->_orderby = "";
    }


    /*--------------------------------------------------------------------------
     * clear_all() : Clear all clauses from the query. (WHERE, ORDER BY, LIMIT)
     *
     * Arguments
     * ---------
     *  None
     *
     * Returns : None
     */
    public function clear_all()
    {
        $this->clear_where();
        $this->clear_orderby();

        $this->_limit = 0;
        $this->_offset = 0;
    }


    /*--------------------------------------------------------------------------
     * or_where() : Adds a condition to the 'WHERE' clause using the 'OR' operator.
     *
     * Arguments
     * ---------
     *  - column   : Column name. If the third argument is NULL, it defines the
     *               full condition syntax (column=?). If an array is provided, the
     *               same condition will be repeated for each columns.
     *  - operator : Logical operator for the condition (=, <=, etc.). If the third
     *               argument is null, then this argument is used as the value.
     *  - value    : Variable that will substitute the placeholder ("?") in the query.
     *               An array can also be used if there is multiple placeholder to
     *               substitute.
     *
     * Returns : None
     */
    public function or_where($column, $operator, $value=null)
    {
        $this->where($column, $operator, $value, "OR");
    }



    /*--------------------------------------------------------------------------
     * and_where() : Adds a condition to the 'WHERE' clause using the 'AND' operator.
     *
     * Arguments
     * ---------
     *  - column   : Column name. If the third argument is NULL, it defines the
     *               full condition syntax (column=?). If an array is provided, the
     *               same condition will be repeated for each columns.
     *  - operator : Logical operator for the condition (=, <=, etc.). If the third
     *               argument is null, then this argument is used as the value.
     *  - value    : Variable that will substitute the placeholder ("?") in the query.
     *               An array can also be used if there is multiple placeholder to
     *               substitute.
     *
     * Returns : None
     */
    public function and_where($column, $operator, $value=null)
    {
        $this->where($column, $operator, $value, "AND");
    }


    /*--------------------------------------------------------------------------
     * where() : Adds a condition to the 'WHERE' clause.
     *
     * Arguments
     * ---------
     *  - column   : Column name. If the third argument is NULL, it defines the
     *               full condition syntax (column=?). If an array is provided, the
     *               same condition will be repeated for each columns.
     *  - operator : Logical operator for the condition (=, <=, etc.). If the third
     *               argument is null, then this argument is used as the value.
     *  - value    : Variable that will substitute the placeholder ("?") in the query.
     *               An array can also be used if there is multiple placeholder to
     *               substitute.
     *  - conj_op  : Conjuctive operator to use for multiple conditions
     *               (cond OR cond2)
     *
     * Returns : None
     */
    public function where($column, $operator, $value=null, $conj_op="AND")
    {
        if (is_null($value)) {
            $value = $operator;
            $operator = null;
        }

        if ((substr($this->_where, -1) != "(") && (!empty($this->_where)))
            $this->_where .= " $conj_op ";

        if (is_array($column)) {

            $this->_where .= implode($column, " $operator ? $conj_op ");
            $this->_where .= " $operator ?";

            $value = array_fill(0, count($column), $value);

        } else if (is_null($operator)) {

            $this->_where .= "$column";

        } else {

            $this->_where .= "$column $operator ?";
        }


        /* Adds the value to the data array that will be used to substitute
           The placeholders in the query */
        if (is_array($value)) {
            $this->_where_data = array_merge($this->_where_data, $value);

        } else {
            $this->_where_data[] = $value;
        }
    }


    /*--------------------------------------------------------------------------
     * and_where_in() : Adds a condition with multiple values to the 'WHERE'
     *                  clause using the "AND" operator.
     *
     * Arguments
     * ---------
     *  - column : Column name. If an array is provided, the same condition
     *             will be repeated for each columns defined in the array.
     *  - values : Array containing the values to check.
     *
     * Returns : None
     */
    public function and_where_in($column, $values)
    {
        $this->where_in($column, $values, "AND");
    }


    /*--------------------------------------------------------------------------
     * or_where_in() : Adds a condition with multiple values to the 'WHERE'
     *                 clause using the "OR" operator
     *
     * Arguments
     * ---------
     *  - column : Column name. If an array is provided, the same condition
     *             will be repeated for each columns defined in the array.
     *  - values : Array containing the values to check.
     *
     * Returns : None
     */
    public function or_where_in($column, $values)
    {
        $this->where_in($column, $values, "OR");
    }


    /*--------------------------------------------------------------------------
     * where_in() : Adds a condition with multiple values to the 'WHERE'
     *              clause.
     *
     * Arguments
     * ---------
     *  - column  : Column name. If an array is provided, the same condition
     *              will be repeated for each columns defined in the array.
     *  - values  : Array containing the values to check.
     *  - conj_op : Conjuctive operator to use for multiple conditions
     *              (cond OR cond2)
     *
     * Returns : None
     */
    public function where_in($columns, $values, $conj_op="AND")
    {

        if (!is_array($columns))
            $columns = array($columns);


        foreach ($columns as $column) {
            if ((substr($this->_where, -1) != "(") && (!empty($this->_where)))
                $this->_where .= " $conj_op ";

            $this->_where .= "$column IN (" . implode(",", array_fill(0, count($values), "?")) . ")";
            $this->_where_data = array_merge($this->_where_data, $values);
        }
    }


    /*--------------------------------------------------------------------------
     * group_where_begin() : Begins a conditions group () in the "WHERE" clause
     *
     * Arguments
     * ---------
     *  None
     *
     * Returns : None
     */
    public function group_where_begin($group_op = "AND")
    {
        if (!(empty($this->_where)))
            $this->_where .= "$group_op (";
        else
            $this->_where .= "(";

        $this->_where_nestcount++;
    }


    /*--------------------------------------------------------------------------
     * group_where_close() : Ends a condition group () in the "WHERE" clause
     *
     * Arguments
     * ---------
     *  None
     *
     * Returns : None
     */
    public function group_where_close($close_all=false)
    {
        if ($this->_where_nestcount < 1)
            return;

        if ($close_all) {
            $this->_where .= str_repeat(")", $_where_nestcount);
            $this->_where_nestcount = 0;
        } else {
            $this->_where .= ")";
            $this->_where_nestcount--;
        }
    }


    /*--------------------------------------------------------------------------
     * orderby() : Adds a column to the "ORDER BY" clause.
     *
     * Arguments
     * ---------
     *  - column : Column to add.
     *  - order  : "ASCending" or "DESCending"
     *
     * Returns : None
     */
    public function orderby($column, $order = "ASC")
    {
        if (!empty($this->_orderby))
            $this->_orderby .= ", ";

        $this->_orderby .= "$column $order";
    }


    /*--------------------------------------------------------------------------
     * orderby_asc() : Adds a column to the "ORDER BY" clause using ascending order
     *
     * Arguments
     * ---------
     *  - column : Column to add.
     *
     * Returns : None
     */
    public function orderby_asc($column)
    {
        $this->orderby($column, "ASC");
    }


    /*--------------------------------------------------------------------------
     * orderby_desc() : Adds a column to the "ORDER BY" clause with descending order.
     *
     * Arguments
     * ---------
     *  - column : Column to add.
     *
     * Returns : None
     */
    public function orderby_desc($column)
    {
        $this->orderby($column, "DESC");
    }


    /*--------------------------------------------------------------------------
     * limit() : Adds a "LIMIT" clause to the query.
     *
     * Arguments
     * ---------
     *  - limit
     *
     * Returns : None
     */
    public function limit($limit)
    {
        $this->_limit = $limit;
    }


    /*--------------------------------------------------------------------------
     * offset() : Adds an offset for the "LIMIT" clause.
     *
     * Arguments
     * ---------
     *  - offset
     *
     * Returns : None
     */
    public function offset($offset)
    {
        $this->_offset = $offset;
    }


    public function get_where_clause()
    {
        $this->group_where_close(true);
        return " WHERE {$this->_where} ";
    }
}
