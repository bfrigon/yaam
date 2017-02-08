<?php
//******************************************************************************
// Systemlogs/plugin.php - System logs plugin
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


define("PERM_LOGS_VIEW", "logs_view");
define("PERM_EXEC_COMMANDS", "exec_commands");
define("PERM_CHANNEL_STATUS_VIEW", "channel_status_view");


class PluginSystemAdmin extends Plugin
{
    public $dependencies = array("tools");

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
    function on_load(&$manager)
    {
        $manager->register_tab($this, "on_show_command", "command", "tools", "Run command", PERM_EXEC_COMMANDS);
        $manager->register_tab($this, "on_show_channels", "channels", "tools", "Channel status", PERM_CHANNEL_STATUS_VIEW);


        $manager->register_tab($this, "on_show_log", "logs", NULL, "System logs", PERM_LOGS_VIEW,2);

        $log_items = get_global_config_item("sys_admin", "log_files", array());


        /* Create a sub tab for each items */
        foreach($log_items as $name => $files) {

            $tab_name = strtolower(preg_replace("/[^a-zA-Z0-9]+/", "", $name));
            $this->_log_items[$tab_name] = $files;

            $manager->register_tab($this, "on_show_log", $tab_name, "logs", $name, PERM_LOGS_VIEW);
        }

        $manager->declare_permissions($this, array(
            PERM_LOGS_VIEW,
            PERM_EXEC_COMMANDS,
            PERM_CHANNEL_STATUS_VIEW,
        ));
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
            throw new Exception("No log file categories was configured in yaam.conf");

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
        require("{$this->dir}/js_highlight.php");
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
        global $MANAGER;

        if (!(check_permission(PERM_EXEC_COMMANDS)))
            throw new Exception("You do not have the permissions to execute commands!");

        $cmd_result = "";
        $command = isset($_POST["command"]) ? $_POST["command"] : "";

        if (isset($_POST["command"])) {

            /* Send the command to the AMI */
            $result = $MANAGER->send("command", array(
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
        global $MANAGER;

        $channels = $MANAGER->send("Status", array());
        $num_results = count($channels);

        /* Set pager variables for the template. */
        $max_results = max((isset($_GET["max"]) ? intval($_GET["max"]) : intval($_SESSION["rpp"])), 1);
        $total_pages = max(1, ceil($num_results / $max_results));
        $current_page = max((isset($_GET["page"]) ? intval($_GET["page"]) : 1), 1);
        $row_start = ($current_page - 1) * $max_results;


        $channels = array_slice($channels, $row_start, $max_results);


        require($template->load("channels.tpl"));
    }
}
