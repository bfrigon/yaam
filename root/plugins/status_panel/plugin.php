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
define("PERM_VIEW_STATUS_PANEL", "view_status_panel");


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
    function on_load(&$manager)
    {

        if (!isset($_SESSION["statuspanel_widgets"]))
            $_SESSION["statuspanel_widgets"] = "sysinfo;services;versions;VBRAKE;log";

        if (!isset($_SESSION["statuspanel_disk_info"]))
            $_SESSION["statuspanel_disk_info"] = "/;/var/log";

        $manager->register_tab($this, "on_show", "status", null, "Status", PERM_VIEW_STATUS_PANEL, 1);

        $manager->declare_permissions($this, array(
            PERM_VIEW_STATUS_PANEL
        ));
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
        foreach (glob("plugins/status_panel/widgets/*.php") as $filename)
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
        foreach (glob("plugins/status_panel/widgets/*.php") as $filename)
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
