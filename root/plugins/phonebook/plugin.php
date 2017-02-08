<?php
//******************************************************************************
// plugins/phonebook/plugin.php - Phonebook plugin
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


/* --- Plugin permissions --- */
define("PERM_PHONEBOOK_READ", "phonebook_read");
define("PERM_PHONEBOOK_WRITE", "phonebook_write");
define("PERM_PHONEBOOK_WRITE_GLOBAL", "phonebook_write_global");
define("PERM_PHONEBOOK_ALL_USERS", "phonebook_all_users");


class PluginPhonebook extends Plugin
{

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
        $manager->register_tab($this, "on_show_phonebook", "phonebook", null, "Phone book", PERM_PHONEBOOK_READ, 140);

        $manager->declare_permissions($this, array(
            PERM_PHONEBOOK_READ,
            PERM_PHONEBOOK_WRITE,
            PERM_PHONEBOOK_WRITE_GLOBAL,
            PERM_PHONEBOOK_ALL_USERS,
        ));
    }


    /*--------------------------------------------------------------------------
     * on_show_phonebook() : Called when the 'Phone book' tab content is requested.
     *
     * Arguments :
     * ---------
     *  - template : Instance of the template engine.
     *  - tab_path : Path to the current tab.
     *  - action   : Requested action.
     *
     * Return : None
     */
    function on_show_phonebook($template, $tab_path, $action)
    {
        global $DB;

        switch ($action) {
            case "add":
                $this->action_addedit_phone($template, "add");
                break;

            case "edit":
                $this->action_addedit_phone($template, "edit");
                break;

            case "delete":
                $this->action_delete_phone($template);
                break;

            default:

                if (!(check_permission(PERM_PHONEBOOK_READ)))
                    throw new Exception("You do not have the required permissions to view the phone book!");

                $query = $DB->create_query("phonebook");
                $query->orderby_asc("extension");

                /* Set search filters */
                if (!empty($_GET["s"])) {
                    $search = $_GET["s"];

                    $query->where("notes", "LIKE", "%$search%");
                    $query->or_where("name", "LIKE", "%$search%");
                }


                /* Restrict results to records owned by the user or global phonebook records */
                if (!(check_permission(PERM_PHONEBOOK_ALL_USERS))) {
                    $query->group_where_begin();

                    $query->where("extension", "=", $_SESSION["extension"]);
                    $query->or_where("extension", "=", "");

                    $query->group_where_close();
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

                /* Set template variables */
                $speed_dial_prefix = get_global_config_item("phonebook", "speed_dial_prefix", "");

                /* Load the template */
                require($template->load("phonebook.tpl"));

                break;
        }
    }


    /*--------------------------------------------------------------------------
     * action_addedit_phone() : Create or update a new phonebook record.
     *
     * Arguments :
     * ---------
     *  - template : Instance of the template engine.
     *  - action : "add" or "edit" CNAM record.
     *
     * Return : None
     */
    function action_addedit_phone($template, $action)
    {
        global $DB;

        if (!(check_permission(PERM_PHONEBOOK_WRITE)))
            throw new Exception("You do not have the required permissions to add/edit phone book records!");

        try {
            $query = $DB->create_query("phonebook");

            if (isset($_GET["id"])) {
                $query->where("id", "=", $_GET["id"]);
                $query->limit(1);
            }

            $pb_data = array();

            if ($action == "add") {

                $pb_data["extension"]  = (isset($_POST["extension"])  ? $_POST["extension"]  : $_SESSION["extension"]);
                $pb_data["number"]     = (isset($_POST["number"])     ? $_POST["number"]     : "");
                $pb_data["name"]       = (isset($_POST["name"])       ? $_POST["name"]       : "");
                $pb_data["notes"]      = (isset($_POST["notes"])      ? $_POST["notes"]      : "");
                $pb_data["speed_dial"] = (isset($_POST["speed_dial"]) ? $_POST["speed_dial"] : "");
            } else {

                $res = $query->run_query_select("*");

                $pb_data["extension"]  = (isset($_POST["extension"])  ? $_POST["extension"]  : odbc_result($res, "extension"));
                $pb_data["number"]     = (isset($_POST["number"])     ? $_POST["number"]     : odbc_result($res, "number"));
                $pb_data["name"]       = (isset($_POST["name"])       ? $_POST["name"]       : odbc_result($res, "name"));
                $pb_data["notes"]      = (isset($_POST["notes"])      ? $_POST["notes"]      : odbc_result($res, "notes"));
                $pb_data["speed_dial"] = (isset($_POST["speed_dial"]) ? $_POST["speed_dial"] : odbc_result($res, "speed_dial"));
            }

            /* If data has been submited, validate it and update the database. */
            if (isset($_POST["submit"])) {

                /* Validate fields */
                if ((!(check_permission(PERM_PHONEBOOK_WRITE_GLOBAL)))
                    && (empty($pb_data["extension"]))) {

                    throw new exception("You do not have the required permissions to add/edit global phonebook records!");
                }

                if ((!(check_permission(PERM_PHONEBOOK_ALL_USERS)))
                    && (intval($_SESSION["extension"]) != intval($pb_data["extension"]))
                    && (!empty($pb_data["extension"]))) {

                    throw new exception("You do not have the required permissions to add/edit phonebook records for other users!");
                }

                if (empty($pb_data["number"]))
                    throw new exception("Phone number is required!");


                /* If all fields are valid, Insert the new call route in the database. */
                if ($action == "add")
                    $query->run_query_insert($pb_data);
                else
                    $query->run_query_update($pb_data);

                /* Redirect to the previous location. */
                $this->redirect($this->get_tab_referrer());
                return;
            }

        } catch (Exception $e) {

            print_message($e->getmessage(), true);
        }

        /* Set template variables */
        $speed_dial_prefix = get_global_config_item("phonebook", "speed_dial_prefix", "");

        require($template->load("phonebook_addedit.tpl"));
    }


    /*--------------------------------------------------------------------------
     * action_delete_phone() : Delete an existing phone book record.
     *
     * Arguments :
     * ---------
     *  - template : Instance of the template engine.
     *
     * Return : None
     */
    function action_delete_phone($template)
    {
        global $DB;

        if (!(check_permission(PERM_PHONEBOOK_WRITE)))
            throw new Exception("You do not have the required permissions to delete phone book records!");

        $query = $DB->create_query("phonebook");

        $id = $_GET["id"];

        if (!isset($id)) {
            $message = "You did not select any phone book record(s) to delete.";
            $url_ok = $this->get_tab_referrer();

            require($template->load("dialog_message.tpl", true));
            return;
        }

        if (is_array($id)) {
            $query->where_in("id", $id);
        } else {
            $query->where("id", "=", $id);
        }

        if (!(check_permission(PERM_PHONEBOOK_WRITE_GLOBAL))) {
            $query->where("extension", "=", $_SESSION["extension"]);
        }

        if (isset($_GET["confirm"])) {
            $query->run_query_delete();

            /* Redirect to the previous location */
            $this->redirect($this->get_tab_referrer());
            return;

        } else {

            $results = $query->run_query_select("name,number");

            require($template->load("phonebook_delete.tpl"));

            odbc_free_result($results);
        }
    }


    function is_editable($extension)
    {
        if (check_permission(PERM_PHONEBOOK_WRITE_GLOBAL))
            return true;

        if (empty($extension) && (!(check_permission(PERM_PHONEBOOK_WRITE_GLOBAL))))
            return false;

        return check_permission(PERM_PHONEBOOK_WRITE);
    }
}
