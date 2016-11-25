<?php
//******************************************************************************
// Plugins/CDR/plugin.php - Call log plugin
//
// Project : Asterisk Y.A.A.M (Yet another asterisk manager)
// Author  : Benoit Frigon <benoit@frigon.info>
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


class PluginCDR extends Plugin
{
    public $_dependencies = array("Tools");


    /*--------------------------------------------------------------------------
     * on_load() : Called after the plugin has been initialized.
     *
     * Arguments :
     * ---------
     *  None
     *
     * Return : None
     */
    function on_load()
    {
        if (!isset($_SESSION["cdr_columns"]))
            $_SESSION["cdr_columns"] = "type;calldate;cid_num;cid_name;dst;duration;billsec;cost;disposition";

        if (!isset($_SESSION["rpp"]))
            $_SESSION["rpp"] = "25";

        $this->register_tab("on_show_cdr", "cdr", null, "Call log", "user");
        $this->register_tab("on_show_routes", "cdr_routes", "tools", "Call routes", "admin");
    }


    /*--------------------------------------------------------------------------
     * on_show_cdr() : Called when the 'cdr' tab content is requested.
     *
     * Arguments :
     * ---------
     *  - template : Instance of the template engine.
     *  - tab_path : Path to the current tab.
     *  - action   : Requested action.
     *
     * Return : None
     */
    function on_show_cdr($template, $tab_path, $action)
    {
        global $DB;

        $query = $DB->create_query("cdr");

        $query->orderby_desc("calldate");

        /* Set search filters */
        if (!empty($_GET["s"])) {

            $search = "%{$_GET["s"]}%";

            $query->group_where_begin();

            $query->or_where(
                array("dst","src","clid"),
                "like",
                $search
            );

            $query->group_where_end();
        }

        if (!empty($_GET["d_from"])) {

            $query->and_where(
                "CAST(calldate AS DATE) >= STR_TO_DATE(?,?)",
                array($_GET["d_from"], get_user_dateformat(DATE_FORMAT_MYSQL))
            );
        }

        if (!empty($_GET["d_to"])) {
            $query->and_where(
                "CAST(calldate as DATE) <= STR_TO_DATE(?,?)",
                array($_GET["d_to"], get_user_dateformat(DATE_FORMAT_MYSQL))
            );
        }

        /* Get the number of cdr entries matching the filters. */
        $columns = array(
            "COUNT(*) as row_count",
            "sum(duration) as total_duration",
            "sum(billsec) as total_billsec",
            "sum(cost) as total_cost"
        );

        $results = $query->run_query_select($columns);

        $num_results = odbc_result($results, "row_count");
        $total_duration = odbc_result($results, "total_duration");
        $total_billsec = odbc_result($results, "total_billsec");
        $total_cost = odbc_result($results, "total_cost");

        odbc_free_result($results);

        /* Set pager variables for the template. */
        $max_results = max((isset($_GET["max"]) ? intval($_GET["max"]) : intval($_SESSION["rpp"])), 1);
        $total_pages = max(1, ceil($num_results / $max_results));
        $current_page = max((isset($_GET["page"]) ? intval($_GET["page"]) : 1), 1);
        $row_start = ($current_page - 1) * $max_results;

        $query->limit($max_results);
        $query->offset($row_start);

        /* Select the users matching the filters. */
        $results = $query->run_query_select("*");

        /* Load the template */
        require($template->load("cdr.tpl"));

        odbc_free_result($results);
    }


    /*--------------------------------------------------------------------------
     * regex_clid() : Split the components of a given caller ID.
     *                name <number>
     *
     * Arguments :
     * ---------
     *  - clid : Caller id to split
     *
     * Return : An array containing the name and number.
     */
    function regex_clid($clid)
    {
        preg_match('|(?:"(.*)")?\s*<?([\d\*#]*)>?|', $clid, $matches);
        list(, $clid_name, $clid_number) = $matches;

        $clid_name = ucwords(strtolower($clid_name));

        return array($clid_name, $clid_number);
    }


    /*--------------------------------------------------------------------------
     * on_show_routes() : Called when the 'call routes' tab content is requested.
     *
     * Arguments :
     * ---------
     *  - template : Instance of the template engine.
     *  - tab_path : Path to the current tab.
     *  - action   : Requested action.
     *
     * Return : None
     */
    function on_show_routes($template, $tab_path, $action)
    {
        global $DB;

        switch ($action) {
            case "add":
                $this->action_addedit_route($template, "add");
                break;

            case "edit":
                $this->action_addedit_route($template, "edit");
                break;

            case "delete":
                $this->action_delete_route($template);
                break;

            default:

                $query = $DB->create_query("cdr_routes");

                /* Set search filters */
                if (!empty($_GET["s"])) {
                    $search = $_GET["s"];

                    $query->where("name", "LIKE", "%$search%");
                }

                $query->orderby_asc("priority");

                /* Get the number of cdr entries matching the filters. */
                $results = $query->run_query_select("COUNT(*) as row_count");
                $num_results = odbc_result($results, "row_count");

                odbc_free_result($results);

                /* Set pager variables for the template. */
                $max_results = max((isset($_GET["max"]) ? intval($_GET["max"]) : intval($_SESSION["rpp"])), 1);
                $total_pages = max(1, ceil($num_results / $max_results));
                $current_page = max((isset($_GET["page"]) ? intval($_GET["page"]) : 1), 1);
                $row_start = ($current_page - 1) * $max_results;

                $query->limit($max_results);
                $query->offset($row_start);

                /* Select the users matching the filters. */
                $results = $query->run_query_select("*");

                /* Load the template */
                require($template->load("cdr_routes.tpl"));

                break;
        }
    }


    /*--------------------------------------------------------------------------
     * action_addedit_route() : Create or update a new call route.
     *
     * Arguments :
     * ---------
     *  - template : Instance of the template engine.
     *  - action : "add" or "edit" call route
     *
     * Return : None
     */
    function action_addedit_route($template, $action)
    {
        global $DB;

        try {
            $query = $DB->create_query("cdr_routes");

            $query->where("id", "=", $_GET["id"]);
            $query->limit(1);

            $rte_data = array(
                "name"       => isset($_POST["name"])       ? $_POST["name"] : "",
                "type"       => isset($_POST["type"])       ? $_POST["type"] : "",
                "cost"       => isset($_POST["cost"])       ? $_POST["cost"] : "",
                "min"        => isset($_POST["min"])        ? $_POST["min"] : "",
                "increment"  => isset($_POST["increment"])  ? $_POST["increment"] : "",
                "priority"   => isset($_POST["priority"])   ? $_POST["priority"] : "",
                "channel"    => isset($_POST["channel"])    ? $_POST["channel"] : "",
                "src"        => isset($_POST["src"])        ? $_POST["src"] : "",
                "dcontext"   => isset($_POST["dcontext"])   ? $_POST["dcontext"] : "",
                "dst"        => isset($_POST["dst"])        ? $_POST["dst"] : "",
                "dstchannel" => isset($_POST["dstchannel"]) ? $_POST["dstchannel"] : "",
            );

            /* If data has been submited, validate it and update the database. */
            if (isset($_POST["submit"])) {

                /* Validate fields */


                /* If all fields are valid, Insert the new call route in the database. */
                if ($action == "add")
                    $query->run_query_insert($rte_data);
                else
                    $query->run_query_update($rte_data);

                /* Redirect to the previous location. */
                $this->redirect($this->get_tab_referrer());
                return;

            } elseif ($action == "edit") {

                $res = $query->run_query_select("*");
                $rte_data = odbc_fetch_array($res);
            }

        } catch (Exception $e) {

            print_message($e->getmessage(), true);
        }

        require($template->load("cdr_routes_addedit.tpl"));
    }


    /*--------------------------------------------------------------------------
     * action_delete_route() : Delete an existing call route.
     *
     * Arguments :
     * ---------
     *  - template : Instance of the template engine.
     *
     * Return : None
     */
    function action_delete_route($template)
    {
        global $DB;
        $query = $DB->create_query("cdr_routes");

        $query->where("id", "=", $_GET["id"]);

        if (isset($_GET["confirm"])) {

            $query->run_query_delete();

            /* Redirect to the previous location */
            $this->redirect($this->get_tab_referrer());
            return;

        } else {

            require($template->load("dialog_delete.tpl", true));
        }
    }
}
