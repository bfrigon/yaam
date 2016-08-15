<?php
//******************************************************************************
// Users/plugin.php - User management plugin
//
// Project : Asterisk Y.A.A.M (Yet another asterisk manager)
// Author  : Benoit Frigon <benoit@frigon.info>
//
// Contributors
// ------------
//  Rafael G. Dantas <rafagd@gmail.com>
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


    function on_load()
    {
        if (!isset($_SESSION["cdr_columns"]))
            $_SESSION["cdr_columns"] = "type;calldate;cid_num;cid_name;dst;duration;billsec;cost;disposition";

        if (!isset($_SESSION["rpp"]))
            $_SESSION["rpp"] = "25";

        $this->register_tab("on_show_cdr", "cdr", null, "Call log", "user");
        $this->register_tab("on_show_routes", "cdr_routes", "tools", "Call routes", "admin");
    }


    function on_show_cdr($template, $tab_path, $action)
    {
        global $DB;

        $filters = array();


        /* Set search filters */
        if (isset($_GET["s"]) || isset($_GET["d_from"]) || isset($_GET["d_to"])) {

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
                    array($_GET["d_from"], get_config_dateformat(DATE_FORMAT_MYSQL)),
                    "AND"
                );
            }

            if (!empty($_GET["d_to"])) {
                $filters[] = array(
                    "CAST(calldate as DATE) <= STR_TO_DATE(?, ?)",
                    array($_GET["d_to"], get_config_dateformat(DATE_FORMAT_MYSQL)),
                    "AND"
                );
            }
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
        $results = $DB->exec_select_query("cdr", "*", $filters, $max_results, $row_start);

        require($template->load("template.tpl"));

        $this->include_js_script("filters.js");
    }

    function regex_clid($clid)
    {
        preg_match('|(?:"(.*)")?\s*<?([\d\*#]*)>?|', $clid, $matches);
        list(, $clid_name, $clid_number) = $matches;

        $clid_name = ucfirst(strtolower($clid_name));

        return array($clid_name, $clid_number);
    }


    function on_show_routes($template, $tab_path, $action)
    {
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


                break;
        }
    }


    function action_add_route($template, $tab_path)
    {


    }


    function action_edit_route($template, $tab_path)
    {


    }


    function action_delete_route($template, $tab_path)
    {


    }
}
