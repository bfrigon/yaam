<?php
//******************************************************************************
// CDR/plugin.php - Call log plugin
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

        $filters = array();
        $orderby = array("!calldate");

        /* Set search filters */
        if (!empty($_GET["s"])) {
            $search = $_GET["s"];

            $filters[] = array(
                array("dst,src,clid", "LIKE ?"),
                "%$search%"
            );
        }

        if (!empty($_GET["d_from"])) {
            $filters[] = array(
                "CAST(calldate as DATE) >= STR_TO_DATE(?, ?)",
                array($_GET["d_from"], get_user_dateformat(DATE_FORMAT_MYSQL)),
                "AND"
            );
        }

        if (!empty($_GET["d_to"])) {
            $filters[] = array(
                "CAST(calldate as DATE) <= STR_TO_DATE(?, ?)",
                array($_GET["d_to"], get_user_dateformat(DATE_FORMAT_MYSQL)),
                "AND"
            );
        }

        /* Get the number of cdr entries matching the filters. */
        $results = $DB->exec_select_query("cdr", "COUNT(*) as row_count", $filters);
        $num_results = odbc_result($results, "row_count");

        odbc_free_result($results);

        /* Set pager variables for the template. */
        $max_results = max((isset($_GET["max"]) ? intval($_GET["max"]) : intval($_SESSION["rpp"])), 1);
        $total_pages = max(1, ceil($num_results / $max_results));
        $current_page = max((isset($_GET["page"]) ? intval($_GET["page"]) : 1), 1);
        $row_start = ($current_page - 1) * $max_results;

        /* Select the users matching the filters. */
        $results = $DB->exec_select_query("cdr", "*", $filters, $max_results, $row_start, $orderby);

        /* Load the template */
        require($template->load("cdr.tpl"));
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

        $clid_name = ucfirst(strtolower($clid_name));

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
                $this->action_add_route($template, $tab_path);
                break;

            case "edit":
                $this->action_edit_route($template, $tab_path);
                break;

            case "delete":
                $this->action_delete_route($template, $tab_path);
                break;

            default:

                $filters = array();

                /* Set search filters */
                if (!empty($_GET["s"])) {
                    $search = $_GET["s"];

                    $filters[] = array(
                        array("name", "LIKE ?"),
                        "%$search%"
                    );
                }

                $orderby = array("priority");

                /* Get the number of cdr entries matching the filters. */
                $results = $DB->exec_select_query("cdr_routes", "COUNT(*) as row_count", $filters);
                $num_results = odbc_result($results, "row_count");

                odbc_free_result($results);

                /* Set pager variables for the template. */
                $max_results = max((isset($_GET["max"]) ? intval($_GET["max"]) : intval($_SESSION["rpp"])), 1);
                $total_pages = max(1, ceil($num_results / $max_results));
                $current_page = max((isset($_GET["page"]) ? intval($_GET["page"]) : 1), 1);
                $row_start = ($current_page - 1) * $max_results;

                /* Select the users matching the filters. */
                $results = $DB->exec_select_query("cdr_routes", "*", $filters, $max_results, $row_start, $orderby);

                /* Load the template */
                require($template->load("cdr_routes.tpl"));

                break;
        }
    }


    /*--------------------------------------------------------------------------
     * action_add_route() : Create a new call route.
     *
     * Arguments :
     * ---------
     *  - template : Instance of the template engine.
     *
     * Return : None
     */
    function action_add_route($template)
    {
        global $DB;

        try {

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

                /* If all fields are valid, Insert the new user profile in the database. */
                $DB->exec_insert_query("cdr_routes", $rte_data);

                /* Redirect to the previous location. */
                $this->redirect($this->get_tab_referrer());
                return;
            }

        } catch (Exception $e) {

            print_message($e->getmessage(), true);
        }

        require($template->load("cdr_routes_addedit.tpl"));
    }


    /*--------------------------------------------------------------------------
     * action_edit_route() : Modify an existing call route.
     *
     * Arguments :
     * ---------
     *  - template : Instance of the template engine.
     *
     * Return : None
     */
    function action_edit_route($template)
    {
        global $DB;

        try {

            $route_id = $_GET["id"];
            $filters = array(
                array("id=?", $route_id),
            );

            /* If data has been submited, validate it and update the database. */
            if (isset($_POST["submit"])) {

                $rte_data = array(
                    "name"       => $_POST["name"],
                    "type"       => $_POST["type"],
                    "cost"       => $_POST["cost"],
                    "min"        => $_POST["min"],
                    "increment"  => $_POST["increment"],
                    "priority"   => $_POST["priority"],
                    "channel"    => $_POST["channel"],
                    "src"        => $_POST["src"],
                    "dcontext"   => $_POST["dcontext"],
                    "dst"        => $_POST["dst"],
                    "dstchannel" => $_POST["dstchannel"],
                );

                /* Validate fields */

                /* If all fields are valid, update the user profile in the database. */
                $DB->exec_update_query("cdr_routes", $rte_data, $filters, 1);

                /* Redirect to the previous location */
                $this->redirect($this->get_tab_referrer());
                return;

            /* If not, read the user profile from the database */
            } else {
                $res = $DB->exec_select_query("cdr_routes", "*", $filters, 1);

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

        $route_id = $_GET["id"];
        $filters = array(
            array("id=?", $route_id)
        );

        if (isset($_GET["confirm"])) {

            $DB->exec_delete_query("cdr_routes", $filters);

            /* Redirect to the previous location */
            $this->redirect($this->get_tab_referrer());
            return;

        } else {

            require($template->load("dialog_delete.tpl", true));
        }
    }
}
