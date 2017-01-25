<?php
//******************************************************************************
// Plugins/Users/plugin.php - User management plugin
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


class PluginUsers extends Plugin
{
    public $_dependencies = array();

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
        $this->register_tab("on_show", "users", null, "Users", "admin");

        if (!isset($_SESSION["rpp"]))
            $_SESSION["rpp"] = '25';

    }


    /*--------------------------------------------------------------------------
     * on_show() : Called when the tab content is requested.
     *
     * Arguments :
     * ---------
     *  - template : Instance of the template engine.
     *  - tab_path : Path to the current tab.
     *  - action   : Requested action.
     *
     * Return : None
     */
    function on_show($template, $tab_path, $action)
    {
        global $DB;

        switch ($action) {

            case "add":
            case "edit":
                $this->action_addedit($template, $action);
                break;

            case "delete":
                $this->action_delete($template);
                break;

            default:

                $query = $DB->create_query("users");

                /* Set search filters */
                if (isset($_GET["s"])) {
                    $search = $_GET["s"];

                    $query->group_where_begin();
                    $query->or_where(
                        array("user", "fullname", "pgroups", "did"),
                        "LIKE",
                        "%$search%"
                    );
                    $query->group_where_close();
                }

                /* Get the number of users matching the filters. */
                $results = $query->run_query_select("COUNT(*) as row_count");
                $num_results = odbc_result($results, "row_count");

                odbc_free_result($results);

                /* Set pager variables for the template. */
                $max_results = max((isset($_GET["max"]) ? intval($_GET["max"]) : intval($_SESSION["rpp"])), 1);
                $total_pages = max(1, ceil($num_results / $max_results));
                $current_page = max((isset($_GET["page"]) ? intval($_GET["page"]) : 1), 1);
                $row_start = ($current_page - 1) * $max_results;

                $query->limit = $max_results;
                $query->offset = $row_start;

                /* Select the users matching the filters. */
                $results = $query->run_query_select("*");

                require($template->load("users.tpl"));

                odbc_free_result($results);
                break;
        }
    }


    /*--------------------------------------------------------------------------
     * action_add() : Create a new user.
     *
     * Arguments :
     * ---------
     *  - template : Instance of the template engine.
     *
     * Return : None
     */
    function action_addedit($template, $action)
    {
        global $DB;

        $query = $DB->create_query("users");

        try {
            $query->where("user", "=", $_GET["user"]);
            $query->limit(1);

            $user_data = array(
                "fullname"     => isset($_POST["fullname"])     ? $_POST["fullname"] : "",
                "extension"    => isset($_POST["extension"])    ? $_POST["extension"] : "",
                "dial_string"  => isset($_POST["dial_string"])  ? $_POST["dial_string"] : "",
                "pgroups"      => isset($_POST["pgroups"])      ? $_POST["pgroups"] : "",
                "vbox_context" => isset($_POST["vbox_context"]) ? $_POST["vbox_context"] : "",
                "vbox_user"    => isset($_POST["vbox_user"])    ? $_POST["vbox_user"] : "",
                "did"          => isset($_POST["did"])          ? $_POST["did"] : "",
            );

            /* If data has been submited, validate it and update the database. */
            if (isset($_POST["submit"])) {

                /* Validate fields */
                if ($action == "add") {

                    if (empty($_POST["user"]))
                        throw new Exception("Username is required");

                    $user_data["user"] = $_POST["user"];
                }

                if (empty($user_data["extension"]))
                    throw new Exception("Extension is required");

                if (empty($user_data["dial_string"]))
                    throw new Exception("Dial string is required");

                /* Validate password */
                if (empty($_POST["password"]) && $action == "add") {
                    throw new Exception('The password cannot be blank');

                } else if (!(empty($_POST["password"]))) {

                    if (strlen($_POST["password"]) < 6)
                        throw new Exception("The password must have at least 6 characters");

                    $user_data["pwhash"] = hash(sha256, $_POST["password"]);
                }

                /* If all fields are valid, Insert/update the user profile in the database. */
                if ($action == "add")
                    $query->run_query_insert($user_data);
                else
                    $query->run_query_update($user_data);

                /* Redirect to the previous location. */
                $this->redirect($this->get_tab_referrer());
                return;

            } else if ($action == "edit") {

                $res = $query->run_query_select("*");
                $user_data = odbc_fetch_array($res);
            }

        } catch (Exception $e) {

            print_message($e->getmessage(), true);
        }

        require($template->load("addedit_user.tpl"));
    }


    /*--------------------------------------------------------------------------
     * action_delete() : Delete an existing user
     *
     * Arguments :
     * ---------
     *  - template : Instance of the template engine.
     *
     * Return : None
     */
    function action_delete($template)
    {
        global $DB;

        $query = $DB->create_query("users");

        $user = $_GET["user"];

        if (!isset($user)) {
            $message = "You did not select any users to delete.";
            $url_ok = $this->get_tab_referrer();

            require($template->load("dialog_message.tpl", true));
            return;
        }

        if (is_array($user)) {
            $query->where_in("user", $user);
        } else {
            $query->where("user", "=", $user);
        }

        if (isset($_GET["confirm"])) {
            $query->run_query_delete();

            /* Redirect to the previous location */
            $this->redirect($this->get_tab_referrer());
            return;

        } else {

            $results = $query->run_query_select("user,fullname");

            require($template->load("delete_user.tpl"));

            odbc_free_result($results);
        }
    }
}
