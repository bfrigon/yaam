<?php
//******************************************************************************
// Plugins/CNAM/plugin.php - CNAM records plugin
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


class PluginCNAM extends Plugin
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
        $this->register_tab("on_show_cnam", "cnam", "tools", "CNAM records", PERMISSION_LVL_MANAGER);
    }


    /*--------------------------------------------------------------------------
     * on_show_cnam() : Called when the 'CNAM records' tab content is requested.
     *
     * Arguments :
     * ---------
     *  - template : Instance of the template engine.
     *  - tab_path : Path to the current tab.
     *  - action   : Requested action.
     *
     * Return : None
     */
    function on_show_cnam($template, $tab_path, $action)
    {
        global $DB;

        switch ($action) {
            case "add":
                $this->action_addedit_cnam($template, "add");
                break;

            case "edit":
                $this->action_addedit_cnam($template, "edit");
                break;

            case "delete":
                $this->action_delete_cnam($template);
                break;

            default:

                $query = $DB->create_query("cnam");

                /* Set search filters */
                if (!empty($_GET["s"])) {
                    $search = $_GET["s"];

                    $query->where("description", "LIKE", "%$search%");
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
                require($template->load("cnam.tpl"));

                break;
        }
    }


    /*--------------------------------------------------------------------------
     * action_addedit_cnam() : Create or update a new CNAM record.
     *
     * Arguments :
     * ---------
     *  - template : Instance of the template engine.
     *  - action : "add" or "edit" CNAM record.
     *
     * Return : None
     */
    function action_addedit_cnam($template, $action)
    {
        global $DB;

        try {
            $query = $DB->create_query("cnam");

            $query->where("id", "=", $_GET["id"]);
            $query->limit(1);

            $cnam_data = array(
                "number"        => isset($_POST["number"])      ? $_POST["number"] : "",
                "callerid"      => isset($_POST["callerid"])    ? $_POST["callerid"] : "",
                "description"   => isset($_POST["description"]) ? $_POST["description"] : ""
            );

            /* If data has been submited, validate it and update the database. */
            if (isset($_POST["submit"])) {

                /* Validate fields */


                /* If all fields are valid, Insert the new call route in the database. */
                if ($action == "add")
                    $query->run_query_insert($cnam_data);
                else
                    $query->run_query_update($cnam_data);

                /* Redirect to the previous location. */
                $this->redirect($this->get_tab_referrer());
                return;

            } elseif ($action == "edit") {

                $res = $query->run_query_select("*");
                $cnam_data = odbc_fetch_array($res);
            }

        } catch (Exception $e) {

            print_message($e->getmessage(), true);
        }

        require($template->load("cnam_addedit.tpl"));
    }


    /*--------------------------------------------------------------------------
     * action_delete_cnam() : Delete an existing CNAM record.
     *
     * Arguments :
     * ---------
     *  - template : Instance of the template engine.
     *
     * Return : None
     */
    function action_delete_cnam($template)
    {
        global $DB;
        $query = $DB->create_query("cnam");

        $id = $_GET["id"];

        if (!isset($id)) {
            $message = "You did not select any CNAM record(s) to delete.";
            $url_ok = $this->get_tab_referrer();

            require($template->load("dialog_message.tpl", true));
            return;
        }

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

        } else {

            $results = $query->run_query_select("description,number");

            require($template->load("cnam_delete.tpl"));

            odbc_free_result($results);
        }
    }
}
