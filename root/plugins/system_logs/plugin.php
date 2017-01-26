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


class PluginSystemLogs extends Plugin
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
    function on_load()
    {

        $this->register_tab(null, "logs", NULL, "System logs", PERMISSION_LVL_ADMIN,2);
        $this->register_tab("on_show", "ast", "logs", "Asterisk server", PERMISSION_LVL_ADMIN);
        $this->register_tab("on_show", "sys", "logs", "System (syslog)", PERMISSION_LVL_ADMIN);
        $this->register_tab("on_show", "kern", "logs", "Kernel", PERMISSION_LVL_ADMIN);
        $this->register_tab("on_show", "dmesg", "logs", "Boot log", PERMISSION_LVL_ADMIN);
        $this->register_tab("on_show", "auth", "logs", "Authentication", PERMISSION_LVL_ADMIN);
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
        $log_basename = preg_replace("_.*/_", "", isset($_REQUEST["file"]) ? $_REQUEST["file"] : "");

        switch ($tab_path) {
            case "system_logs.logs.sys":
                $log_filename = "/var/log/syslog";
                break;

            case "system_logs.logs.kern":
                $log_filename = "/var/log/kern.log";
                break;

            case "system_logs.logs.dmesg":
                $log_filename = "/var/log/dmesg";
                break;

            case "system_logs.logs.auth":
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
    }
}
