<?php
//******************************************************************************
// Plugins/Voicemail-odbc/plugin.php - Voicemail(ODBC) plugin
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


class PluginVoicemailOdbc extends Plugin
{
    public $_dependencies = array();
    public $_conflicts = array("voicemail");


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
        $this->register_tab("on_show", "vm", null, "Voicemail", PERMISSION_LVL_USER);

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


    function action_list_messages($template)
    {
        global $DB;

        $folders = array(
            "inbox" => "Inbox",
            "old" => "Old",
            "work" => "Work",
            "family" => "Family",
            "friends" => "Friends"
        );


        /* If current user has 'admin' or 'vm-admin' access, allow access other users mailbox */
        if ($_SESSION["plevel"] >= PERMISSION_LVL_MANAGER) {

            $vbox_user = isset($_GET["user"]) ? $_GET["user"] : $_SESSION["vbox_user"];
            $vbox_context = isset($_GET["context"]) ? $_GET["context"] : $_SESSION["vbox_context"];

        } else {

            $vbox_user = $_SESSION["vbox_user"];
            $vbox_context = $_SESSION["vbox_context"];
        }

        $current_folder = isset($_GET["folder"]) ? $_GET["folder"] : "inbox";
        $current_folder_name = $folders[$current_folder];

        $query = $DB->create_query("voicemessages");

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


        $query->where("context", "=", $vbox_context);
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


    function action_delete_message($template)
    {
        global $DB;

        if (!isset($_GET["confirm"])) {

            require($template->load("dialog_delete.tpl", true));
            return;
        }

        try {


            $query = $DB->create_query("voicemessages");
            $id = $_REQUEST["id"];


            if (is_array($id)) {
                $query->where_in("id", $id);

            } else if (is_string($id)) {
                $query->where("id", "=", $id);

            } else {
                throw new Exception("Nothing to delete! No message ID or invalid parameter.");
            }

            $query->run_query_delete();

            /* Redirect to the previous location */
            $this->redirect($this->get_tab_referrer());
            return;

        } catch (Exception $e) {

            print_message($e->getmessage(), true);
        }
    }


    function action_view_message($template)
    {
        global $DB;

        try {

            $query = $DB->create_query("voicemessages");

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

            $mailbox_user = odbc_result($res, "mailboxuser");
            $mailbox_context = odbc_result($res, "mailboxcontext");
            $mailbox = "$mailbox_user@$mailbox_context";

            $msgdate = odbc_result($res, "origtime");
            $duration = odbc_result($res, "duration");
            $msg_size = odbc_result($res, "size");
            $callerid = odbc_result($res, "callerid");
            list($caller_name, $caller_number) = $this->regex_clid($callerid);

            $msg_url = "ajax.php?function=VoicemailOdbc/download&id=$id";
            } catch (Exception $e) {

            print_message($e->getmessage(), true);
        }

        require($template->load("message.tpl"));
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
        $query = $DB->create_query("voicemessages");

        $id = $_GET["id"];

        if (!isset($id))
            throw new HTTPException(404, "message ID is missing");

        /* Get recording info */
        $columns = array(
            "length(recording) as size",
            "msgnum",
            "mailboxcontext",
            "mailboxuser"
        );

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
