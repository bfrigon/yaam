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
                $this->action_add($template, $tab_path);
                break;

            case "edit":
                $this->action_edit($template, $tab_path);
                break;

            case "delete":
                $this->action_delete($template, $tab_path);
                break;

            default:

                /* Workaround : Search query submited with POST causes the tab content
                   to not update since the url don't change. Including the search in the
                   tab url force the page to update. */
                if (isset($_POST["s"])) {
                    $params = array("s" => $_POST["s"]);
                    $url = $this->build_tab_url($params, false, true);

                    $this->redirect($url);
                    return;
                }


                $filter = array();

                /* Set search filters */
                if (isset($_GET["s"])) {
                    $search = $_GET["s"];

                    $filter[] = array(
                        array("user,fullname,pgroups,user_chan,vbox", "LIKE ?"),
                        "%$search%"
                    );
                }

                /* Get the number of users matching the filters. */
                $results = $DB->exec_select_query("users", "COUNT(*) as row_count", $filter);
                $num_results = odbc_result($results, "row_count");

                odbc_free_result($results);

                /* Set pager variables for the template. */
                $max_results = max((isset($_GET["max"]) ? intval($_GET["max"]) : intval($_SESSION["rpp"])), 1);
                $total_pages = max(1, ceil($num_results / $max_results));
                $current_page = max((isset($_GET["page"]) ? intval($_GET["page"]) : 1), 1);
                $row_start = ($current_page - 1) * $max_results;

                /* Select the users matching the filters. */
                $results = $DB->exec_select_query("users", "*", $filter, $max_results, $row_start);

                require($template->load("users.tpl"));
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
    function action_add($template)
    {
        global $DB;

        try {

            $user_data = array(
                "user"      => isset($_POST["user"])      ? $_POST["user"] : "",
                "fullname"  => isset($_POST["fullname"])  ? $_POST["fullname"] : "",
                "extension" => isset($_POST["extension"]) ? $_POST["extension"] : "",
                "user_chan" => isset($_POST["user_chan"]) ? $_POST["user_chan"] : "",
                "pwhash"    => isset($_POST["password"])  ? hash(sha256, $_POST["password"]) : "",
                "pgroups"   => isset($_POST["pgroups"])   ? $_POST["pgroups"] : "",
                "ui_theme"  => isset($_POST["ui_theme"])  ? $_POST["ui_theme"] : "",
                "vbox"      => isset($_POST["vbox"])      ? $_POST["vbox"] : "",
            );

            /* If data has been submited, validate it and update the database. */
            if (isset($_POST["submit"])) {

                /* Validate fields */
                if (empty($user_data["user"]))
                    throw new Exception("Username is required");

                if (empty($_POST["password"]))
                    throw new Exception('The password cannot be blank');

                if (strlen($_POST["password"]) < 6)
                    throw new Exception("The password must have at least 6 characters");

                /* If all fields are valid, Insert the new user profile in the database. */
                $DB->exec_insert_query("users", $user_data);

                /* Redirect to the previous location. */
                $this->redirect($this->get_tab_referrer());
                return;
            }

        } catch (Exception $e) {

            print_message($e->getmessage(), true);
        }

        require($template->load("add_user.tpl"));
    }


    /*--------------------------------------------------------------------------
     * action_edit() : Modify an existing user
     *
     * Arguments :
     * ---------
     *  - template : Instance of the template engine.
     *
     * Return : None
     */
    function action_edit($template)
    {
        global $DB;

        try {

            $user_id = $_GET["id"];
            $filters = array("user" => $user_id);

            /* If data has been submited, validate it and update the database. */
            if (isset($_POST["submit"])) {

                $user_data = array(
                    "fullname"  => $_POST["fullname"],
                    "extension" => $_POST["extension"],
                    "user_chan" => $_POST["user_chan"],
                    "pgroups"   => $_POST["pgroups"],
                    "ui_theme"  => $_POST["ui_theme"],
                    "vbox"      => $_POST["vbox"],
                );

                /* Validate fields */
                if (!empty($_POST["password"])) {

                    if (strlen($_POST["password"]) < 6)
                        throw new Exception("The password must have at least 6 characters");

                    $user_data["pwhash"] = hash(sha256, $_POST["password"]);
                }

                /* If all fields are valid, update the user profile in the database. */
                $DB->exec_update_query("users", $user_data, $filters, 1);

                /* Redirect to the previous location */
                $this->redirect($this->get_tab_referrer());
                return;

            /* If not, read the user profile from the database */
            } else {
                $res = $DB->exec_select_query("users", "*", $filters, 1);

                $user_data = odbc_fetch_array($res);
           }

        } catch (Exception $e) {

            print_message($e->getmessage(), true);
        }

        require($template->load("edit_user.tpl"));
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

        $user_id = $_GET["id"];
        $filters = array("user" => $user_id);

        if (isset($_GET["confirm"])) {

            $DB->exec_delete_query("users", $filters);

            /* Redirect to the previous location */
            $this->redirect($this->get_tab_referrer());
            return;

        } else {

            require($template->load("dialog_delete.tpl", true));
        }
    }
}
