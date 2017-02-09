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
define("PERM_VOICEMAIL", "voicemail");
define("PERM_VOICEMAIL_ALL_USERS", "voicemail_all_users");


class PluginVoicemailOdbc extends Plugin
{

    public $dependencies = array();
    public $conflicts = array("voicemail");


    /*--------------------------------------------------------------------------
     * on_load() : Called after the plugin has been initialized.
     *
     * Arguments :
     * ---------
     *  - manager ; Plugin manager instance
     *
     * Return : None
     */
    function on_load(&$manager)
    {
        $manager->register_tab($this, "on_show", "vm", null, "Voicemail", PERM_VOICEMAIL);

        $manager->declare_permissions($this, array(
            PERM_VOICEMAIL,
            PERM_VOICEMAIL_ALL_USERS,
        ));

        if (!isset($_SESSION["rpp"]))
            $_SESSION["rpp"] = '25';

        $this->_table = get_global_config_item("voicemail", "table", "voicemessages");
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

        switch ($action) {

            case "view":
                $this->action_view_message($template);
                break;

            case "delete":
                $this->action_delete_message($template);
                break;

            default:
                $this->action_list_messages($template);
                break;
        }
    }


    /*--------------------------------------------------------------------------
     * action_list_messages() : View mailbox messages.
     *
     * Arguments :
     * ---------
     *  - template : Instance of the template engine.
     *
     * Return : None
     */
    function action_list_messages($template)
    {
        global $DB;

        if (!(check_permission(PERM_VOICEMAIL)))
            throw new Exception("You do not have the required permissions to access voicemail!");

        $folders = array(
            "inbox" => "Inbox",
            "old" => "Old",
            "work" => "Work",
            "family" => "Family",
            "friends" => "Friends"
        );


        /* If current user has 'admin' or 'vm-admin' access, allow access other users mailbox */
        if (check_permission(PERM_VOICEMAIL_ALL_USERS)) {

            $vbox_user = isset($_GET["user"]) ? $_GET["user"] : $_SESSION["vbox_user"];
            $vbox_context = isset($_GET["context"]) ? $_GET["context"] : $_SESSION["vbox_context"];

        } else {

            $vbox_user = $_SESSION["vbox_user"];
            $vbox_context = $_SESSION["vbox_context"];
        }

        $current_folder = isset($_GET["folder"]) ? $_GET["folder"] : "inbox";
        $current_folder_name = $folders[$current_folder];

        $query = $DB->create_query($this->_table);

        $columns = array(
            "id",
            "msgnum",
            "dir",
            "callerid",
            "origtime",
            "duration",
            "length(recording) as size"
        );

        $query->orderby_desc("origtime");


        $query->where("mailboxcontext", "=", $vbox_context);
        $query->where("mailboxuser", "=", $vbox_user);
        $query->where("dir", "LIKE", "%/$current_folder");

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

        $results = $query->run_query_select($columns);

        require($template->load("vm.tpl"));
    }


    /*--------------------------------------------------------------------------
     * action_delete_message() : Delete a mailbox message
     *
     * Arguments :
     * ---------
     *  - template : Instance of the template engine.
     *
     * Return : None
     */
    function action_delete_message($template)
    {
        global $DB;

        try {

            if (!(check_permission(PERM_VOICEMAIL)))
                throw new Exception("You do not have the required permissions to access voicemail!");

            $query = $DB->create_query($this->_table);

            if (!isset($_REQUEST["id"]))
                throw new Exception("You did not select any message(s) to delete");

            $id = $_REQUEST["id"];
            if (is_array($id)) {
                $query->where_in("id", $id);
            } else {
                $query->where("id", "=", $id);
            }


            /* Restrict to user's mailbox if the user does not have permission to access all mailboxes */
            if (!(check_permission(PERM_VOICEMAIL_ALL_USERS))) {
                $query->where("mailboxcontext", "=", $_SESSION["vbox_context"]);
                $query->where("mailboxuser", "=", $_SESSION["vbox_user"]);
            }

            if (isset($_GET["confirm"])) {

                /* Delete the message(s) */
                $query->run_query_delete();

                /* Redirect to the previous location */
                $this->redirect($this->get_tab_referrer());

            } else {

                /* Get the number of messages to delete */
                $msg_count = $query->run_query_select_simple("count(*)");

                if (intval($msg_count) == 0)
                    throw new Exception("You did not select any message(s) to delete");

                $results = $query->run_query_select("origtime,callerid");

                /* Display the delete message dialog */
                require($template->load("msg_delete.tpl"));

                odbc_free_result($results);
            }

        } catch (Exception $e) {
            $message = $e->getmessage();
            $url_ok = $this->get_tab_referrer();

            require($template->load("dialog_error.tpl", true));
        }

    }


    /*--------------------------------------------------------------------------
     * action_view_message() : View mailbox message details
     *
     * Arguments :
     * ---------
     *  - template : Instance of the template engine.
     *
     * Return : None
     */
    function action_view_message($template)
    {
        global $DB;

        try {
            if (!(check_permission(PERM_VOICEMAIL)))
                throw new Exception("You do not have the required permissions to access voicemail!");


            $query = $DB->create_query($this->_table);

            if (!(isset($_GET["id"])))
                throw new Exception("You did not select any message!");

            $id = $_GET["id"];
            $columns = array(
                "msgnum",
                "mailboxuser",
                "mailboxcontext",
                "dir",
                "callerid",
                "origtime",
                "duration",
                "length(recording) as size"
            );

            $query->where("id", "=", $id);


            $res = $query->run_query_select($columns);

            if (!(@odbc_fetch_row($res)))
                throw new Exception("Message not found");



            $mailbox_user = odbc_result($res, "mailboxuser");
            $mailbox_context = odbc_result($res, "mailboxcontext");
            $mailbox = "$mailbox_user@$mailbox_context";

            /* Check if the requested message ID belongs to the current logged user */
            if ((!(check_permission(PERM_VOICEMAIL_ALL_USERS))) &&
                (($_SESSION["vbox_context"] != $mailbox_context) || ($_SESSION["vbox_user"] != $mailbox_user))) {

                throw new Exception("You do not have the required permissions to access other user's voicemail!");
            }

            $msgdate = odbc_result($res, "origtime");
            $duration = odbc_result($res, "duration");
            $msg_size = odbc_result($res, "size");
            $callerid = odbc_result($res, "callerid");
            list($caller_name, $caller_number) = $this->regex_clid($callerid);

            $msg_url = "ajax.php?function={$this->name}/download&id=$id&output=binary";

            require($template->load("message.tpl"));

       } catch (Exception $e) {
            $message = $e->getmessage();
            $url_ok = $this->get_tab_referrer();

            require($template->load("dialog_error.tpl", true));
        }
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
     * ajax_download() : Outputs the recording data of a given message ID
     *
     *
     * Arguments :
     * ---------
     *  None
     *
     * Return : None
     */
    function ajax_download()
    {
        global $DB;

        if (!(check_permission(PERM_VOICEMAIL)))
            throw new Exception("You do not have the required permissions to access voicemail!");

        if (!isset($_GET["id"]))
            throw new HTTPException(404, "message ID is missing");

        /* Get recording info */
        $columns = array(
            "length(recording) as size",
            "msgnum",
            "mailboxcontext",
            "mailboxuser"
        );

        $query = $DB->create_query($this->_table);

        $id = $_GET["id"];
        $query->where("id", "=", $id);

        $res = $query->run_query_select($columns);

        if (odbc_fetch_row($res, 1) == false)
            throw new HTTPException(404, "message not found.");

        $file_size = intval(odbc_result($res, "size"));
        $file_name = sprintf("msg%04d.wav", intval(odbc_result($res, "msgnum")));
        $msg_vbox_context = odbc_result($res, "mailboxcontext");
        $msg_vbox_user = odbc_result($res, "mailboxuser");

        odbc_free_result($res);


        /* Check if the requested message ID belongs to the current logged user */
        if (($_SESSION["vbox_context"] != $msg_vbox_context) ||
            ($_SESSION["vbox_user"] != $msg_vbox_user)) {

            throw new HTTPException(403);
        }

        header("Content-Type: audio/x-wav");
        header("Content-Disposition: attachment; filename=$file_name");

        if (isset($_SERVER["HTTP_RANGE"])) {

            $range = explode("-", substr($_SERVER["HTTP_RANGE"], 6));
            $start = intval($range[0]);
            $end = intval($range[1]);
            $seg_length = $end + 1 - $start;

            if ($end == 0)
                $end = $file_size - 1;


            header("HTTP/1.1 206 Partial Content");

            header("Accept-Ranges: bytes");
            header("Content-Range: bytes $start-$end/$file_size");
            header("Content-Length: $seg_length");


            $columns = "SUBSTRING(recording, $start, $seg_length) as recording";


        } else {
            header("Content-Length: $file_size");

            $columns = "recording";
        }


        /* Retreive the recording from the database */
        $res = $query->run_query_select($columns);

        odbc_binmode ($res, ODBC_BINMODE_PASSTHRU);
        odbc_longreadlen ($res, 0);

        odbc_result($res, "recording");

        odbc_free_result($res);
    }
}
