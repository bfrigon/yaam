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


/* --- Plugin permissions --- */
define("PERM_CDR_VIEW", "cdr_view");
define("PERM_CDR_VIEW_ALL_USERS", "cdr_view_all_users");
define("PERM_CDR_READ_ROUTES", "cdr_read_routes");
define("PERM_CDR_WRITE_ROUTES", "cdr_write_routes");


class PluginCdr extends Plugin
{

    /* List of plugins incompatible with this one */
    public $conflicts = array();

    /* Other plugins required */
    public $dependencies = array("tools");

    /* Files (css, javascript) to include in the html header */
    public $static_files = array(
        "css" => "layout.css",
        "js" => "datepicker.js"
    );



    /*--------------------------------------------------------------------------
     * on_load() : Called after the plugin has been initialized.
     *
     * Arguments :
     * ---------
     *  None
     *
     * Return : None
     */
    function on_load(&$manager)
    {
        if (!isset($_SESSION["rpp"]))
            $_SESSION["rpp"] = "25";

        $manager->register_tab($this, "on_show_cdr", "cdr", null, "Call log", PERM_CDR_VIEW);
        $manager->register_tab($this, "on_show_routes", "cdr_routes", "tools", "Call routes", PERM_CDR_READ_ROUTES);

        $manager->declare_permissions($this, array(
            PERM_CDR_VIEW,
            PERM_CDR_VIEW_ALL_USERS,
            PERM_CDR_READ_ROUTES,
            PERM_CDR_WRITE_ROUTES,
        ));
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

        if (!(check_permission(PERM_CDR_VIEW)))
            throw new Exception("You do not have the required permissions to view the CDR!");

        $query = $DB->create_query("cdr");

        $query->orderby_desc("calldate");

        /* if unanswered_calls is off, only show calls with disposition 'ANSWERED' */
        if (!get_global_config_item("cdr", "unanswered_calls", False))
            $query->where("disposition", "=", "answered");

        /* If user don't have permissions to view other user's cdr records, restrict the result to the user extension */
        if (!(check_permission(PERM_CDR_VIEW_ALL_USERS))) {

            $user_ext = $_SESSION["extension"];
            $user_did = $_SESSION["did"];

            $query->group_where_begin();
            $query->or_where("src", "=", $user_ext);
            $query->or_where("dst", "=", $user_ext);
            $query->or_where("src", "RLIKE", "$user_did\$");
            $query->or_where("dst", "RLIKE", "$user_did\$");
            $query->group_where_close();
        }

        /* Set search filters */
        if (!empty($_GET["s"])) {

            $search = "%{$_GET["s"]}%";

            $query->group_where_begin();

            $query->or_where(
                array("dst","src","clid"),
                "like",
                $search
            );

            $query->group_where_close();
        }

        /* Set date filters */
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

        $format_date = get_user_dateformat(DATE_FORMAT_DATEPICKER);

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

                if (!(check_permission(PERM_CDR_READ_ROUTES)))
                    throw new Exception("You do not have the required permissions to view call routes!");

                $query = $DB->create_query("cdr_routes");

                /* Set search filters */
                if (!empty($_GET["s"])) {
                    $search = $_GET["s"];

                    $query->where("name", "LIKE", "%$search%");
                }

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

        if (!(check_permission(PERM_CDR_WRITE_ROUTES)))
            throw new Exception("You do not have the required permissions to add/edit call routes!");


        try {
            $query = $DB->create_query("cdr_routes");

            if (isset($_GET["id"])) {
                $query->where("id", "=", $_GET["id"]);
                $query->limit(1);

            } else if ($action == "edit") {
                throw new Exception("You did not select any call routes to edit!");
            }

            $rte_data = array(
                "name"       => isset($_POST["name"])       ? $_POST["name"] : "",
                "cost"       => isset($_POST["cost"])       ? $_POST["cost"] : "",
                "min"        => isset($_POST["min"])        ? $_POST["min"] : "",
                "increment"  => isset($_POST["increment"])  ? $_POST["increment"] : "",
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

                odbc_free_result($res);
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

        try {

            if (!(check_permission(PERM_CDR_WRITE_ROUTES)))
                throw new Exception("You do not have the required permissions to delete call routes!");

            $query = $DB->create_query("cdr_routes");

            if (!isset($_GET["id"])) {
                $message = "You did not select any routes to delete.";
                $url_ok = $this->get_tab_referrer();

                require($template->load("dialog_message.tpl", true));
                return;
            }

            $id = $_GET["id"];
            if (is_array($id)) {
                $query->where_in("id", $id);
            } else {
                $query->where("id", "=", $id);
            }

            if (isset($_GET["confirm"])) {
                $query->run_query_delete();

                /* Redirect to the previous location */
                $this->redirect($this->get_tab_referrer());
                return;

            }

            $results = $query->run_query_select("name");

            require($template->load("cdr_routes_delete.tpl"));

            odbc_free_result($results);

        } catch (Exception $e) {

            print_message($e->getmessage(), true);

        }
    }
}
