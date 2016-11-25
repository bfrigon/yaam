<?php
//******************************************************************************
// Users/plugin.php - User management plugin
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


class PluginStatusPanel extends Plugin
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

        if (!isset($_SESSION["statuspanel_widgets"]))
            $_SESSION["statuspanel_widgets"] = "sysinfo;services;versions;VBRAKE;log";

        if (!isset($_SESSION["statuspanel_disk_info"]))
            $_SESSION["statuspanel_disk_info"] = "/;/var/log";

        $this->register_tab("on_show", "status", null, "Status", "user", 1);
    }


    /*--------------------------------------------------------------------------
     * ajax_update() : Prints the widgets that needs to be updated only.
     *
     * Arguments :
     * ---------
     *  None
     *
     * Return : None
     */
    function ajax_update()
    {
        global $DB;

        header("Content-Type: text/plain");

        /* Load all widgets classes */
        $WIDGETS = array();
        foreach (glob("plugins/StatusPanel/widgets/*.php") as $filename)
            include $filename;

        echo "<div>";

        foreach (explode(";", $_SESSION["statuspanel_widgets"]) as $name) {
            if (!isset($WIDGETS[$name]))
                continue;

            $widget = $WIDGETS[$name];

            if (method_exists($widget, "needs_update") && !$widget->needs_update())
                continue;

            echo "<div id=\"$name\">";
            $widget->print_widget();
            echo "</div>";
        }

        echo "</div>";
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
        echo "<div class=\"widgets_left\">";

        /* Load all widgets classes */
        $WIDGETS = array();
        foreach (glob("plugins/StatusPanel/widgets/*.php") as $filename)
            include $filename;

        foreach (explode(";", $_SESSION["statuspanel_widgets"]) as $name)
        {
            if ($name == "VBRAKE") {
                echo "</div><div class=\"widgets_right\">";

            } else {
                if (!isset($WIDGETS[$name]))
                    continue;

                $style = "";

                if (isset($WIDGETS[$name]->min_width))
                    $style .= "min-width: " . $WIDGETS[$name]->min_width . "; ";

                if (isset($WIDGETS[$name]->min_height))
                    $style .= "min-height: " . $WIDGETS[$name]->min_height . "; ";

                /* Print widget content */
                echo "<div class=\"box widget\" id=\"$name\" style=\"$style\">";

                $widget = $WIDGETS[$name]->print_widget();

                echo "</div>";
            }
        }

        echo "</div><div class=\"clear\"></div>";

        require($template->load("statuspanel.tpl"));
   }
}
