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
define("PERM_CT_WRITE_RULES", "ct_write_rules");
define("PERM_CT_READ_RULES", "ct_read_rules");
define("PERM_CT_RULES_ALL_USERS", "ct_rules_all_users");


class PluginCallTreatment extends Plugin
{

     /* List of plugins incompatible with this one */
    public $conflicts = array();

    /* Other plugins required */
    public $dependencies = array("tools");

    /* Files (css, javascript) to include in the html header */
    public $static_files = array(
        "css" => "layout.css",
    );



    private $_ct_actions = array();  /* call treatment actions */



    /*--------------------------------------------------------------------------
     * on_load() : Called after the plugin has been initialized.
     *
     * Arguments :
     * ---------
     *  - manager : Plugin manager instance
     *
     * Return : None
     */
    function on_load(&$plugins)
    {
        $plugins->register_tab($this, "on_show_ct", "ct", "tools", "Call treatment", PERM_CT_READ_RULES);

        $plugins->register_action(
            $this,
            "phone_number_tools",
            "add",
            "tools.ct",
            "Add call treatment rule",
            "blacklist",
            "Create a call treatment rule for this number",
            PERM_CT_WRITE_RULES);

        $plugins->declare_permissions($this, array(
            PERM_CT_WRITE_RULES,
            PERM_CT_READ_RULES,
            PERM_CT_RULES_ALL_USERS,
        ));


        $this->_ct_actions = get_global_config_item("call_treatment", "actions");
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

                if (!(check_permission(PERM_CT_READ_RULES)))
                    throw new Exception("You do not have the required permissions to view call treatment rules!");

                $query = $DB->create_query("call_treatment");

                /* If user don't have permissions to view other users rules, restrict results to user extension */
                if (!(check_permission(PERM_CT_RULES_ALL_USERS))) {
                    $query->where("extension", "=", $_SESSION["extension"]);
                }

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
     * get_action_desc() : Get the call treatment action description from the name
     *
     * Arguments :
     * ---------
     *  - name : Name of the call treatment action
     *
     * Return : The action description.
     */
    function get_action_desc($name)
    {
        if (isset($this->_ct_actions[$name]))
            return $this->_ct_actions[$name];
        else
            return "Unknown";
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
        global $DB;

        if (!(check_permission(PERM_CT_WRITE_RULES)))
            throw new Exception("You do not have the required permissions to add/edit call treatment rules!");

        try {
            $query = $DB->create_query("call_treatment");

            if (isset($_GET["id"])) {
                $query->where("id", "=", $_GET["id"]);
                $query->limit(1);

            } else if ($action == "edit") {
                throw new Exception("You did not select any call treatment rules to edit!");
            }

            $ct_data = array(
                "extension"     => isset($_POST["extension"])   ? $_POST["extension"] : $_SESSION["extension"],
                "action"        => isset($_POST["ct_action"])   ? $_POST["ct_action"] : "",
                "caller_num"    => isset($_POST["caller_num"])  ? $_POST["caller_num"] : "",
                "caller_name"   => isset($_POST["caller_name"]) ? $_POST["caller_name"] : "",
                "description"   => isset($_POST["description"]) ? $_POST["description"] : ""
            );

            if (isset($_GET["number"]) && !(isset($_POST["caller_num"])))
                $ct_data["caller_num"] = $_GET["number"];

            /* If data has been submited, validate it and update the database. */
            if (isset($_POST["submit"])) {

                /* Validate fields */
                if (!(check_permission(PERM_CT_RULES_ALL_USERS)) &&
                    (intval($ct_data["extension"]) != intval($_SESSION["extension"]))) {

                    throw new Exception("You are not authorized to create rules for other extensions");
                }

                if (empty($ct_data["caller_num"]) && empty($ct_data["caller_name"]))
                    throw new Exception("Match rules are empty.");

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

            $this->show_messagebox(MESSAGEBOX_ERROR, $e->getmessage(), false);
        }

        $action_list = $this->_ct_actions;
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

        try {
            if (!(check_permission(PERM_CT_WRITE_RULES)))
                throw new Exception("You do not have the required permissions to delete call treatment rules!");

            $query = $DB->create_query("call_treatment");

            if (!isset($_GET["id"]))
                throw new Exception("You did not select any call treatment rule(s) to delete.");

            $id = $_GET["id"];
            if (is_array($id)) {
                $query->where_in("id", $id);
            } else {
                $query->where("id", "=", $id);
            }

            if (!(check_permission(PERM_CT_RULES_ALL_USERS))) {
                $query->where("extension", "=", intval($_SESSION["extension"]));
            }

            if (isset($_GET["confirm"])) {
                $query->run_query_delete();

                /* Redirect to the previous location */
                $this->redirect($this->get_tab_referrer());
                return;

            } else {

                $results = $query->run_query_select("description,caller_num,caller_name");

                require($template->load("ct_delete.tpl"));

                odbc_free_result($results);
            }
        } catch (Exception $e) {
            $this->show_messagebox(MESSAGEBOX_ERROR, $e->getmessage(), true);
        }
    }
}
