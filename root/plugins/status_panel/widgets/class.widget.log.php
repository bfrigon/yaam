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
