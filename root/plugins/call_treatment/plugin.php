<?php
//******************************************************************************
// Plugins/call_treatment/plugin.php - Call treatment plugin
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


class PluginCallTreatment extends Plugin
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
        $this->register_tab("on_show_ct", "ct", "tools", "Call treatment", "user");
    }


    /*--------------------------------------------------------------------------
     * on_show_ct() : Called when the 'call treatment' tab content is requested.
     *
     * Arguments :
     * ---------
     *  - template : Instance of the template engine.
     *  - tab_path : Path to the current tab.
     *  - action   : Requested action.
     *
     * Return : None
     */
    function on_show_ct($template, $tab_path, $action)
    {
        global $DB;

        switch ($action) {
            case "add":
                $this->action_addedit_ct($template, "add");
                break;

            case "edit":
                $this->action_addedit_ct($template, "edit");
                break;

            case "delete":
                $this->action_delete_ct($template);
                break;

            default:

                $query = $DB->create_query("call_treatment");

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
                require($template->load("ct.tpl"));

                break;
        }
    }


    /*--------------------------------------------------------------------------
     * action_addedit_ct() : Create or update a new call treatment rule.
     *
     * Arguments :
     * ---------
     *  - template : Instance of the template engine.
     *  - action : "add" or "edit" call treatment rule.
     *
     * Return : None
     */
    function action_addedit_ct($template, $action)
    {
        global $DB, $CONFIG;

        try {
            $action_list = $CONFIG["call_treatment"]["actions"];

            $query = $DB->create_query("call_treatment");

            $query->where("id", "=", $_GET["id"]);
            $query->limit(1);

            $ct_data = array(
                "extension"     => isset($_POST["extension"])   ? $_POST["extension"] : "",
                "action"        => isset($_POST["ct_action"])   ? $_POST["ct_action"] : "",
                "caller_num"    => isset($_POST["caller_num"])  ? $_POST["caller_num"] : "",
                "caller_name"   => isset($_POST["caller_name"]) ? $_POST["caller_name"] : "",
                "description"   => isset($_POST["description"]) ? $_POST["description"] : ""
            );

            /* If data has been submited, validate it and update the database. */
            if (isset($_POST["submit"])) {

                /* Validate fields */


                /* If all fields are valid, Insert the new call route in the database. */
                if ($action == "add")
                    $query->run_query_insert($ct_data);
                else
                    $query->run_query_update($ct_data);

                /* Redirect to the previous location. */
                $this->redirect($this->get_tab_referrer());
                return;

            } elseif ($action == "edit") {

                $res = $query->run_query_select("*");
                $ct_data = odbc_fetch_array($res);
            }

        } catch (Exception $e) {

            print_message($e->getmessage(), true);
        }

        require($template->load("ct_addedit.tpl"));
    }


    /*--------------------------------------------------------------------------
     * action_delete_ct() : Delete an existing call treatment rule.
     *
     * Arguments :
     * ---------
     *  - template : Instance of the template engine.
     *
     * Return : None
     */
    function action_delete_ct($template)
    {
        global $DB;
        $query = $DB->create_query("call_treatment");

        $id = $_GET["id"];

        if (!isset($id)) {
            $message = "You did not select any call treatment rule(s) to delete.";
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

            $results = $query->run_query_select("extension,caller_num,caller_name,description");

            require($template->load("ct_delete.tpl"));

            odbc_free_result($results);
        }
    }
}
