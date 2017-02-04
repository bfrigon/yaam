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

class PluginSystemLogs extends Plugin
{
    public $dependencies = array("tools");


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
        $manager->register_tab($this, null, "logs", NULL, "System logs", PERM_LOGS_VIEW,2);
        $manager->register_tab($this, "on_show_syslog", "ast", "logs", "Asterisk server", PERM_LOGS_VIEW);
        $manager->register_tab($this, "on_show_syslog", "sys", "logs", "System (syslog)", PERM_LOGS_VIEW);
        $manager->register_tab($this, "on_show_syslog", "kern", "logs", "Kernel", PERM_LOGS_VIEW);
        $manager->register_tab($this, "on_show_syslog", "dmesg", "logs", "Boot log", PERM_LOGS_VIEW);
        $manager->register_tab($this, "on_show_syslog", "auth", "logs", "Authentication", PERM_LOGS_VIEW);

        $manager->register_tab($this, "on_show_command", "command", "tools", "Run commands", PERM_EXEC_COMMANDS);





        $manager->declare_permissions($this, array(
            PERM_LOGS_VIEW,
            PERM_EXEC_COMMANDS,
        ));
    }


    /*--------------------------------------------------------------------------
     * on_show_syslog() : Called when the tab content is requested.
     *
     * Arguments :
     * ---------
     *  - template : Instance of the template engine.
     *  - tab_path : Path to the current tab.
     *  - action   : Requested action.
     *
     * Return : None
     */
    function on_show_syslog($template, $tab_path, $action)
    {
        if (!(check_permission(PERM_LOGS_VIEW)))
            throw new Exception("You do not have the permissions to view the logs!");

        $log_basename = preg_replace("_.*/_", "", isset($_REQUEST["file"]) ? $_REQUEST["file"] : "");

        $path = explode(".", $tab_path);

        switch ($path[2]) {
            case "sys":
                $log_filename = "/var/log/syslog";
                break;

            case "kern":
                $log_filename = "/var/log/kern.log";
                break;

            case "dmesg":
                $log_filename = "/var/log/dmesg";
                break;

            case "auth":
                $log_filename = "/var/log/auth.log";
                break;

            default:
                $tab_id = "system_logs.logs.ast";
                $log_filename = "/var/log/asterisk/messages";
                break;
        }

        $log_list = array();
        foreach (glob($log_filename . "*") as $path)
            $log_list[] = basename($path);

        if (empty($log_basename))
            $log_basename = basename($log_filename);

        $log_filename .= str_replace(basename($log_filename), "", $log_basename);

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

        if (isset($_POST["command"])) {

            /* Send the command to the AMI */
            $result = $MANAGER->send("command", array(
                "command" => $_POST["command"]
            ))[0];

            $cmd_result = "";
            if (isset($result["data"])) {
                $cmd_result = $result["data"];
            }
        }


        require($template->load("command.tpl"));
    }
}
