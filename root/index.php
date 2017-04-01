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

require("include/common.php");

$DEBUG_INFO_FOOTER = "";
$DEBUG_TIME_START = microtime(true);

try
{
    $PLUGINS = new PluginManager();
    $selected_path = "";

    session_start();

    if (!isset($_SESSION['logged'])) {
        header("Location:login.php");
        exit();
    }

    /* Load config, connect to database */
    init_session();

    /* Load all plugins */
    $PLUGINS->load();
    $PLUGINS->sort_tabs();

    $tabs = $PLUGINS->get_tabs();

    if (!(empty($_GET["path"]))) {
        $selected_path = $_GET["path"];
    } else {

        $first_tab = reset($tabs);
        $selected_path = "{$first_tab['plugin']}.{$first_tab['id']}";
    }

    list($selected_plugin, $selected_tab, $selected_page) = explode(".", $selected_path . ".." );


    $page_class = "";
    if (isset($tabs[$selected_tab])) {

        $parent_tab = $tabs[$selected_tab];

        if (!(empty($parent_tab["childs"]))) {
            $page_class = "has-childs";

            if (empty($selected_page)) {
                $selected_page = array_keys($parent_tab["childs"])[0];
                $selected_path = sprintf("%s.%s.%s", $selected_plugin, $selected_tab, $selected_page);
            }
        }
    }



} catch (Exception $e) {

    $error_msg = $e->getmessage();
}

/* Display the page template */
$template = new TemplateEngine(null);
require($template->load("index.tpl", true, true));



/*--------------------------------------------------------------------------
 * autoinclude_plugin_files() : Template callback function. Include stylesheets
 *                              and javscript files for the selected plugin.
 *
 * Arguments
 * ---------
 *  - path : Path to the tab content to display : [plugin_name].[parent_tab].[sub_tab]
 *
 * Returns : None
 */
function autoinclude_plugin_files($path)
{
    try {
        global $PLUGINS;

        $path = preg_replace("_.*(/|\\\\)_", "", $path);
        $path_item = explode(".", $path, 3);

        $plugin_name = $path_item[0];

        $plugin = $PLUGINS->get_plugin($plugin_name);
        if (is_null($plugin))
            return;

        if (!(isset($plugin->static_files)))
            return;

        $plugin_dir = $plugin->dir;
        foreach ($plugin->static_files as $type => $file) {

            $href = "plugins/$plugin_name/$file?v=" . YAAM_VERSION;

            switch (strtolower($type)) {

                case "css":
                case "stylesheet":
                    print "<link id=\"css_{$plugin_name}_theme\" rel=\"stylesheet\" type=\"text/css\" href=\"$href\" />";
                    break;

                case "js":
                case "javascript":
                case "script":
                    print "<script type=\"text/javascript\" src=\"$href\"></script>";
                    break;
            }
        }

    } catch (Exception $e) {

    }
}


/*--------------------------------------------------------------------------
 * show_tab_content() : Template callback function. Outputs the content of a tab.
 *
 * Arguments
 * ---------
 *  - path : Path to the tab content to display : [plugin_name].[parent_tab].[sub_tab]
 *
 * Returns : Error message if an error occured, empty string otherwise.
 */
function show_tab_content($path)
{
    global $DEBUG_TIME_START, $DEBUG_INFO_FOOTER;

    try {
        global $PLUGINS;
        $PLUGINS->show_tab_content($path);

        $error_msg = "";

    } catch (Exception $e) {
        $error_msg = $e->getmessage();
    }

    if (get_global_config_item("general", "debug_show_exectime", false)) {

        $exec_time = (microtime(true) - $DEBUG_TIME_START) * 1000;

        $DEBUG_INFO_FOOTER .= sprintf("<p class=\"copyright\">Execution time : %0.1d ms</p>", $exec_time);
    }

    return $error_msg;
}


/*--------------------------------------------------------------------------
 * show_tabs() : Template callback function. Display the tabs list.
 *
 * Arguments
 * ---------
 *  -
 *  - selected_path : Path to the selected tab : [plugin_name].[parent_tab].[sub_tab]
 *
 * Returns : None
 */
function show_tabs($selected_path)
{
    global $PLUGINS;

    $tabs = $PLUGINS->get_tabs();

    $path_item = explode(".", $selected_path, 3);
    $selected_tab = (isset($path_item[1]) ? $path_item[1] : "");

    echo "<ul class=\"top-nav\" id=\"tabs\">";

    foreach ($tabs as $parent_id => $parent_tab) {
        $parent_path = $parent_tab["plugin"] . '.' . $parent_id;

        $link_class = ($selected_tab == $parent_id) ? "selected" : "";

        echo "<li id=\"tab_$parent_id\" class=\"$link_class\"><a href=\"?path=$parent_path\">";
        echo $parent_tab["caption"], "</a>";

        if (isset($parent_tab["childs"])) {
            echo "<ul class=\"left-nav\">";

            foreach ($parent_tab["childs"] as $sub_id => $sub_tab) {
                $sub_path = $sub_tab["plugin"] . ".$parent_id.$sub_id";

                $link_class = ($selected_path == $sub_path ) ? "selected" : "";

                echo "<li id=\"page_$sub_id\" class=\"$link_class\"><a href=\"?path=$sub_path\">";
                echo $sub_tab["caption"], "</a></li>";
            }

            echo "</ul>";
        }

        echo "</li>";
    }

    echo "</ul>";
}

