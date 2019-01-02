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
define("PERM_VIEW_DASHBOARD", "view_dashboard");


class PluginDashboard extends Plugin
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
    function on_load(&$plugins)
    {

        $plugins->register_tab($this, "on_show", "status", null, "Status", PERM_VIEW_DASHBOARD, 1);

        $plugins->declare_permissions($this, array(
            PERM_VIEW_DASHBOARD
        ));
    }


    /*--------------------------------------------------------------------------
     * ajax_update_widgets() : Prints the widgets that needs to be updated only.
     *
     * Arguments :
     * ---------
     *  None
     *
     * Return : None
     */
    function ajax_update_widgets()
    {
        $widgets = $this->manager->widgets;
        $sequence = $this->get_widget_sequence();


        foreach ($sequence as $name) {
            echo "<div class=\"widget\">";

            try {
                /* Ignore widgets that does not exists */
                if (!(isset($widgets[$name])))
                    continue;

                $widget = $widgets[$name];
                $plugin = $widget["plugin"];
                $callback = array($plugin, $widget["callback"]);

                /* Check if the widget display callback function is valid */
                if (!(is_callable($callback)))
                    throw new Exception("Invalid callback function given for widget ($name) : {$widget['callback']}");

                /* Get the template engine from the plugin to which the widget
                   belongs to */
                $plugin_template = $plugin->get_template_engine();

                call_user_func_array($callback, array($plugin_template));

            } catch (Exception $e) {

                $this->show_messagebox(MESSAGEBOX_ERROR, $e->getmessage(), false);
            }

            echo "</div>";
        }
    }


    /*--------------------------------------------------------------------------
     * ajax_save_widget_seq() : Save the widgets display sequence.
     *
     * Arguments :
     * ---------
     *  None
     *
     * Return : None
     */
    function ajax_save_widget_seq()
    {

    }


    /*--------------------------------------------------------------------------
     * get_widget_sequence() : Get the current user widget display sequence.
     *
     * Arguments :
     * ---------
     *  None
     *
     * Return : None
     */
    function get_widget_sequence()
    {
        $widgets = $this->manager->widgets;

        /* If widget sequence is not defined for the current user, build the default one */
        if (empty($_SESSION["widget_sequence"])) {

            $sequence = array();

            foreach ($widgets as $name => $widget) {
                if ($widget["visible"] == false)
                    continue;

                $sequence[] = $name;
            }

            array_splice($sequence, count($sequence) / 2, 0, "");

            $_SESSION["widget_sequence"] = implode(";", $sequence);


        } else {
            $sequence = explode(";", $_SESSION["widget_sequence"]);
        }

        return $sequence;
    }


    /*--------------------------------------------------------------------------
     * on_show() : Called when the tab content is requested.
     *
     * Arguments :
     * ---------
     *  None
     *
     * Return : None
     */
    function on_show()
    {
        $widgets = $this->manager->widgets;
        $sequence = $this->get_widget_sequence();

        $column_width_left = 40;
        $column_width_right = 100 - $column_width_left;
        $column_count = 0;



        echo "<div class=\"dashboard\"><div class=\"column\" style=\"width: $column_width_left%;\">";

        foreach ($sequence as $name) {

            try {

                /* Ignore if widget does not exists. */
                if (!(isset($widgets[$name])) && !(empty($name)))
                    continue;

                /* Begin a new column if name is empty (separator) */
                if (empty($name)) {

                    /* Maximum 2 columns */
                    if ($column_count > 0)
                        continue;

                    echo "</div><div class=\"column\" style=\"width: $column_width_right%;\">";

                    $column_count++;
                    continue;
                }

                $widget = $widgets[$name];
                $plugin = $widget["plugin"];
                $name = $widget["name"];
                $callback = array($plugin, $widget["callback"]);

                /* Check if the widget display callback function is valid */
                if (!(is_callable($callback)))
                    throw new Exception("Invalid callback function given for widget ($name) : {$widget['callback']}");

                /* Get the template engine from the plugin to which the widget
                   belongs to */
                $plugin_template = $plugin->get_template_engine();

                call_user_func_array($callback, array($plugin_template));

            } catch (Exception $e) {

                $this->show_messagebox(MESSAGEBOX_ERROR, $e->getmessage(), false);
            }
        }

        echo "</div></div>";
    }
}
