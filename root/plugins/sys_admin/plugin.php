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
define("PERM_LOGS_VIEW", "logs_view");
define("PERM_EXEC_COMMANDS", "exec_commands");
define("PERM_VIEW_SYSTEM_INFO", "view_system_info");
define("PERM_CHANNEL_VIEW_ALL", "channel_view_all");
define("PERM_CHANNEL_HANGUP_OTHER_USERS", "channel_hangup_other_users");
define("PERM_PEERS_STATUS", "peers_status");

require("sysinfo.php");

class PluginSystemAdmin extends Plugin
{

    /* List of plugins incompatible with this one */
    public $conflicts = array();

    /* Other plugins required */
    public $dependencies = array("tools");

    /* Files (css, javascript) to include in the html header */
    public $static_files = array(
        "css" => "layout.css",
        "js"  => "highlight.js",
    );



    private $_log_items = array();



    /*--------------------------------------------------------------------------
     * on_load() : Called after the plugin has been initialized.
     *
     * Arguments :
     * ---------
     *  None
     *
     * Return : None
     */
    function on_load(&$plugins)
    {
        $plugins->declare_permissions($this, array(
            PERM_LOGS_VIEW,
            PERM_EXEC_COMMANDS,
            PERM_CHANNEL_VIEW_ALL,
            PERM_CHANNEL_HANGUP_OTHER_USERS,
            PERM_PEERS_STATUS,
        ));


        /* Register diagnostic tab */
        $plugins->register_tab($this, null, "diag", null, "Diagnostic", "");
        $plugins->register_tab($this, "on_show_command", "command", "diag", "Run command", PERM_EXEC_COMMANDS);
        $plugins->register_tab($this, "on_show_channels", "channels", "diag", "Channel status", PERM_CHANNEL_VIEW_ALL);
        $plugins->register_tab($this, "on_show_peers", "peers", "diag", "Peer status", PERM_PEERS_STATUS);


        /* Register log viewer tab */
        $plugins->register_tab($this, "on_show_log", "logs", NULL, "System logs", PERM_LOGS_VIEW,2);
        $log_items = get_global_config_item("log_viewer", "groups", array());

        /* Create a sub tab for each items */
        foreach($log_items as $name => $files) {

            $tab_name = strtolower(preg_replace("/[^a-zA-Z0-9]+/", "", $name));
            $this->_log_items[$tab_name] = $files;

            $plugins->register_tab($this, "on_show_log", $tab_name, "logs", $name, PERM_LOGS_VIEW);
        }


        /* Register widgets */
        $plugins->register_widget($this, "sysstat", "show_widget_sysstat", PERM_VIEW_SYSTEM_INFO);
        $plugins->register_widget($this, "version", "show_widget_version", PERM_VIEW_SYSTEM_INFO, false);
        $plugins->register_widget($this, "services", "show_widget_services", PERM_VIEW_SYSTEM_INFO);
        $plugins->register_widget($this, "channels", "show_widget_channels", PERM_CHANNEL_VIEW_ALL);

    }


    /*--------------------------------------------------------------------------
     * on_show_log() : Called when the tab content is requested.
     *
     * Arguments :
     * ---------
     *  - template : Instance of the template engine.
     *  - tab_path : Path to the current tab.
     *  - action   : Requested action.
     *
     * Return : None
     */
    function on_show_log($template, $tab_path, $action)
    {
        if (!(check_permission(PERM_LOGS_VIEW)))
            throw new Exception("You do not have the permissions to view the logs!");


        if (count($this->_log_items) == 0)
            throw new Exception("No log file categories were configured in yaam.conf");

        $path = explode(".", $tab_path);

        $log_files = $this->_log_items[$path[2]];
        if (empty($log_files))
            throw new Exception("log file category does not exists");


        /* Get the files that matches the search filter */
        $log_list = array();
        foreach (glob($log_files) as $path)
            $log_list[] = basename($path);

        /* Check if the search filter found log files */
        if (count($log_list) == 0)
            throw new Exception("No files found in $log_files!");

        /* Do not allow directory traversal */
        $log_file = preg_replace("_.*/_", "", isset($_REQUEST["file"]) ? $_REQUEST["file"] : $log_list[0]);


        /* Do not allow to view other files that those specified by the search filter */
        if (!(in_array($log_file, $log_list)))
            throw new Exception("You cannot view files which does not match with the search filters!");


        $log_dir = dirname($log_files);
        $log_filename = "$log_dir/$log_file";

        require($template->load("systemlogs.tpl"));
    }


    /*--------------------------------------------------------------------------
     * on_show_command() : Called when the tab content is requested.
     *
     * Arguments :
     * ---------
     *  - template : Instance of the template engine.
     *  - tab_path : Path to the current tab.
     *  - action   : Requested action.
     *
     * Return : None
     */
    function on_show_command($template, $tab_path, $action)
    {
        global $_AMI;

        if (!(check_permission(PERM_EXEC_COMMANDS)))
            throw new Exception("You do not have the permissions to execute console commands!");

        $cmd_result = "";
        $command = isset($_POST["command"]) ? $_POST["command"] : "";

        if (isset($_POST["command"])) {

            /* Send the command to the AMI */
            $result = $_AMI->send("command", array(
                "command" => $_POST["command"]
            ))[0];

            if (isset($result["data"])) {
                $cmd_result = $result["data"];
            }
        }

        require($template->load("command.tpl"));
    }


    /*--------------------------------------------------------------------------
     * on_show_channels() : Called when the 'channel status' tab content is requested.
     *
     * Arguments :
     * ---------
     *  - template : Instance of the template engine.
     *  - tab_path : Path to the current tab.
     *  - action   : Requested action.
     *
     * Return : None
     */
    public function on_show_channels($template, $tab_path, $action)
    {
        global $_AMI;

        switch ($action) {
            case "hangup":
                $this->action_hangup_channel($template);
                break;

            default:
                try {

                    if (!(check_permission(PERM_CHANNEL_VIEW_ALL)))
                        throw new Exception("You do not have the required permissions to view channel status");

                    $channels = $_AMI->send("Status", array());
                    if ($channels === false)
                        throw new Exception("Error while sending command to the Asterisk manager.\n {$_AMI->last_error}");

                    $num_results = count($channels);

                    /* Set pager variables for the template. */
                    $max_results = max((isset($_GET["max"]) ? intval($_GET["max"]) : intval($_SESSION["rpp"])), 1);
                    $total_pages = max(1, ceil($num_results / $max_results));
                    $current_page = max((isset($_GET["page"]) ? intval($_GET["page"]) : 1), 1);
                    $row_start = ($current_page - 1) * $max_results;


                    $channels = array_slice($channels, $row_start, $max_results);
                } catch (Exception $e) {
                    $this->show_messagebox(MESSAGEBOX_ERROR, $e->getmessage(), false);
                }

                require($template->load("channels.tpl"));
                break;
        }
    }


    /*--------------------------------------------------------------------------
     * action_hangup_channel() : Send a channel hangup request.
     *
     * Arguments :
     * ---------
     *  - template : Instance of the template engine.
     *
     * Return : None
     */
    private function action_hangup_channel($template)
    {
        global $_AMI;

        try {

            if (!(check_permission(PERM_CHANNEL_VIEW_ALL)))
                throw new Exception("You do not have the required permissions to view channel status");

            if (!(check_permission(PERM_CHANNEL_HANGUP_OTHER_USERS)))
                throw new Exception("You do not have the required permissions to hangup channels!");

            $channel = $_GET["channel"];

            if (!(isset($_GET["confirm"]))) {
                require($template->load("hangup_confirm.tpl"));
                return;
            }

            $_AMI->send("Hangup", array(
                "channel" => $channel,
            ));

            $this->redirect($this->get_tab_referrer());

        } catch (Exception $e) {

            $this->show_messagebox(MESSAGEBOX_ERROR, $e->getmessage(), true);
        }

    }

    /*--------------------------------------------------------------------------
     * on_show_channels() : Called when the 'channel status' tab content is requested.
     *
     * Arguments :
     * ---------
     *  - template : Instance of the template engine.
     *  - tab_path : Path to the current tab.
     *  - action   : Requested action.
     *
     * Return : None
     */
    public function on_show_peers($template, $tab_path, $action)
    {
        global $_AMI;

        $protocols = array("IAX", "SIP");
        $peers = array();

        foreach ($protocols as $protocol) {

            $list = $_AMI->send("${protocol}peers", array());

            if (!(is_array($list)))
                continue;


            foreach ($list as $peer) {
                $status = strtolower($peer["status"]);

                if ($status == "unmonitored")
                    continue;

                $peer["cssclass"] = (substr($status, 0, 2) != "ok" ? "error" : "normal");
                $peers[] = $peer;

            }
        }

        $num_results = count($peers);

        require($template->load("peers.tpl"));
    }

    /*--------------------------------------------------------------------------
     * show_widget_channels() : Called when the content of the 'channels status'
     * widget is requested.
     *
     * Arguments :
     * ---------
     *  - template : Instance of the template engine.
     *
     * Return : None
     */
    public function show_widget_channels($template)
    {
        global $_AMI;

        $channels = $_AMI->send("Status", array());
        $num_results = count($channels);

        $channels = array_slice($channels, 0, 10);

        require($template->load("widget_channels.tpl"));
    }


    /*--------------------------------------------------------------------------
     * show_widget_sysstat() : Called when the content of the 'system status'
     * widget is requested.
     *
     * Arguments :
     * ---------
     *  - template : Instance of the template engine.
     *
     * Return : None
     */
    public function show_widget_sysstat($template)
    {
        $server_time = date(DATE_RFC2822);
        $system_uptime = get_system_uptime();
        $system_load = get_system_load();
        $meminfo = get_system_meminfo();
        $disk_info = get_system_diskinfo();
        $network_info = get_system_netinfo();

        require($template->load("widget_sysstat.tpl"));
    }


    /*--------------------------------------------------------------------------
     * show_widget_version() : Called when the content of the 'versions'
     * widget is requested.
     *
     * Arguments :
     * ---------
     *  - template : Instance of the template engine.
     *
     * Return : None
     */
    public function show_widget_version($template)
    {
        global $_AMI;

        $os_ver = sprintf("%s %s",
            php_uname("s"),
            php_uname("r")
        );

        $response = $_AMI->send("CoreSettings", array());
        $asterisk_ver = @$response[0]["asteriskversion"];

        require($template->load("widget_version.tpl"));

    }


    /*--------------------------------------------------------------------------
     * show_widget_services() : Called when the content of the 'services status'
     * widget is requested.
     *
     * Arguments :
     * ---------
     *  - template : Instance of the template engine.
     *
     * Return : None
     */
    public function show_widget_services($template)
    {
        $services = get_service_status();

        require($template->load("widget_services.tpl"));

    }
}
