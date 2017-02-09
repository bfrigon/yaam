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

$DEBUGINFO_TEMPLATE_ENGINE = "";
$DEBUG_TIME_START = microtime(true);
$DEBUG_EXEC_TIME = 0;

try
{

    session_start();

    if (!isset($_SESSION['logged'])) {
        header("Location:login.php");
        exit();
    }

    /* Load config, connect to database */
    init_session();

    /* Load all plugins */
    $PLUGINS = new PluginManager();
    $PLUGINS->load();
    $PLUGINS->sort_tabs();


} catch (Exception $e) {

    $error_msg = $e->getmessage();
}

$DEBUG_EXEC_TIME = (microtime(true) - $DEBUG_TIME_START) * 1000;

/* Display the page template */
$template = new TemplateEngine(null);
require($template->load("index.tpl", true, true));




/*--------------------------------------------------------------------------
 * show_tab_content() : Template callback function. Outputs the content of a tab.
 *
 * Arguments
 * ---------
 *  - path : Path to the tab content to display : [plugin_name].[parent_tab].[sub_tab]
 *
 * Returns : None
 */
function show_tab_content($path)
{
    global $DEBUG_TIME_START, $DEBUG_EXEC_TIME;

    try {
        global $PLUGINS;
        $PLUGINS->show_tab_content($path);

        $error_msg = "";

    } catch (Exception $e) {
        $error_msg = $e->getmessage();
    }

    $DEBUG_EXEC_TIME = (microtime(true) - $DEBUG_TIME_START) * 1000;

    return $error_msg;
}


/*--------------------------------------------------------------------------
 * show_tabs() : Template callback function. Display the tabs list.
 *
 * Arguments
 * ---------
 *  None
 *
 * Returns : Array containing variables for the template
 *  - page_class    : Class name for the page content div
 *  - selected_path : Path to the selected tab
 *  - selected_tab  : Name of the selected tab
 */
function show_tabs()
{
    global $PLUGINS;

    $tabs = $PLUGINS->get_tabs();
    $selected_tab_haschilds = false;

    $selected_path = $_GET["path"];
    if (empty($selected_path)) {

        $first_tab = reset($tabs);
        $selected_path = "{$first_tab['plugin']}.{$first_tab['id']}";
    }

    list($selected_plugin, $selected_tab, $selected_page) = explode(".", $selected_path . ".." );



    echo "<ul class=\"top-nav\" id=\"tabs\">";

    foreach ($tabs as $parent_id => $parent_tab) {
        $parent_path = $parent_tab["plugin"] . '.' . $parent_id;

        $link_class = ($selected_tab == $parent_id) ? "selected" : "";

        echo "<li id=\"tab_$parent_id\" class=\"$link_class\"><a href=\"?path=$parent_path\">";
        echo $parent_tab["caption"], "</a>";

        if (isset($parent_tab["childs"])) {

            if ($selected_tab == $parent_id) {
                $selected_tab_haschilds = true;

                if (empty($selected_page)) {
                    $selected_page = array_shift(array_keys($parent_tab["childs"]));
                    $selected_path = sprintf("%s.%s.%s", $selected_plugin, $selected_tab, $selected_page);
                }
            }

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

    $page_class = ($selected_tab_haschilds ? "has-childs" : "");
    return array($page_class, $selected_path, $selected_tab);
}

