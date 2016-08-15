<?php
//******************************************************************************
// index.php
//
// Project   : Asterisk Y.A.A.M (Yet another asterisk manager)
// Author    : Benoit Frigon <benoit@frigon.info>
//
// Copyright (c) Benoit Frigon
// www.bfrigon.com
//
// This software is released under the terms of the GNU Lesser General Public
// License v2.1.
// A copy of which is available from http://www.gnu.org/copyleft/lesser.html
//
//******************************************************************************

require("include/common.php");

try
{
    session_start();

    if (!isset($_SESSION['logged'])) {
        header("Location:login.php");
        exit();
    }

    /* Load configuration */
    $CONFIG = load_global_config();

    /* Connect to the database */
    $DB = new ODBCDatabase($CONFIG["db_dsn"], $CONFIG["db_user"], $CONFIG["db_pass"]);

    /* Load plugins */
    $PLUGINS = new PluginManager();
    $plugin_list = explode(";", $CONFIG["plugins"]);

    foreach($plugin_list as $name)
        $PLUGINS->load($name);

    $PLUGINS->sort_tabs();


} catch (Exception $e) {

    $err_message = $e->getmessage();

    $template = new TemplateEngine(null);
    require($template->load("fatal_error.tpl", true));

    die();
}


/*------------------------------------------------------------------------------------------------*/
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
<head>
    <title>Asterisk Manager</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">

    <link id="css_theme" rel="stylesheet" type="text/css" href="themes/<?=$_SESSION['ui_theme']?>/theme.css" />

    <script type="text/javascript" src="include/js/jquery-env.min.js"></script>
    <script type="text/javascript" src="include/js/index.js"></script>
    <script type="text/javascript" src="include/js/jquery-custom-ui-dialog.js"></script>
    <script src="http://maps.google.com/maps/api/js?sensor=false" type="text/javascript" ></script>
</head>

<body>
    <a name="top"></a>

    <div id="main">
        <div class="header">
            <ul class="top-nav" id="tabs">
                <?php

                    $selected_path = !empty($_GET["path"]) ? $_GET["path"] : "StatusPanel.status";
                    list($selected_plugin, $selected_tab, $selected_page) = explode(".", $selected_path . ".." );

                    $selected_tab_haschilds = false;

                    foreach ($PLUGINS->_tabs as $parent_id => $parent_tab) {
                        $parent_path = $parent_tab["plugin"] . '.' . $parent_id;

                        $link_class = ($selected_tab == $parent_id && !isset($_SESSION["js"])) ? "selected" : "";

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
                ?>
            </ul>
        </div>

        <div id="userinfo">
            Logged as: <?=$_SESSION['user']?>&nbsp;
            (<span id="userinfo_fullname"><?=$_SESSION['fullname']?></span>)&nbsp;-&nbsp;
            <a href="?path=Tools.tools.profile">Edit profile</a>&nbsp;|&nbsp;
            <a href="login.php?logout=true">Logout</a>
        </div>
        <div class="clear"></div>

        <?php
            if (!isset($_SESSION['js'])) {
                echo "<div class=\"page ", ($selected_tab_haschilds ? "has-childs" : "") , "\" id=\"tab_$selected_tab\">";

                try {
                    $PLUGINS->show_tab_content($selected_path);

                    $exec_time = sprintf("%0.4f s", (microtime(true) - $DEBUG_TIME_START));

                } catch (Exception $e) {

                    $error = $e->getmessage();
                    print_message($error, true);
                }

                echo "</div>";
            }

            unset($_SESSION["js"]);
        ?>

    </div>
    <div class="footer">
        Y.A.A.M (v<?=YAAM_VERSION?>)
        <p class="copyright">Execution time : <span id="exec_time"><?php echo isset($exec_time) ? $exec_time : ""; ?></span></p>
    </div>
</body>
</html>
