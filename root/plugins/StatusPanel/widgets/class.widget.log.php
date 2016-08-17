<?php
/***************************************************************************
 * Y.A.A.M (Yet Another Asterisk Manager)
 *
 * Copyright (c) 2011 - 2012 Benoit Frigon <bfrigon@gmail.com>
 * All Rights Reserved.
 *
 * This software is released under the terms of the GNU Lesser General Public License v2.1
 *  A copy of which is available from http://www.gnu.org/copyleft/lesser.html
 *
 */

class WidgetLogs
{
    public $min_width = "480px";
    public $min_height = "300px";


    function needs_update()
    {
        if (!isset($_SESSION['tmp_widget_log_lastmod']))
            return true;

        return ($_SESSION['tmp_widget_log_lastmod'] != filemtime('/var/log/asterisk/messages'));
    }

    function print_widget()
    {
        echo '<h1>Messages</h1>';
        echo '<p style="margin-bottom: 10px"><a href="?path=SystemLogs.logs">View all logs</a></p>';


        $log = explode("\n", `tail -n 6 /var/log/asterisk/messages`);
        foreach ($log as $line) {
            if (strpos($line, "ERROR") !== false)
                echo '<span class="log-error">';
            else if (strpos($line, "WARNING") !== false)
                echo '<span class="log-warning">';
            else
                echo '<span>';

            echo $line, '</span><br />';
        }

        echo '<div class="brake"></div>';


        $_SESSION['tmp_widget_log_lastmod'] = filemtime('/var/log/asterisk/messages');
    }
}

$WIDGETS['log'] = new WidgetLogs();
?>
