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


class WidgetSysinfo
{

    public $min_width = "300px";


    function print_widget()
    {
        echo '<h1>System</h1>';

        print_progressbar("Server time", date(DATE_RFC2822));
        print_progressbar("Uptime", $this->parse_uptime());


        $fc = file_get_contents('/proc/loadavg');
        $load = explode(' ', $fc);
        print_progressbar('Avg. Load', implode(' ', array_slice($load, 0, 3)));

        /* --------------------------------------------------------------------
         *  Memory usage
         */
        echo '<h2>Memory</h2>';

        if ($fp = @fopen('/proc/meminfo', 'r')) {
            while (($buffer = fgets($fp, 4096)) !== false) {
                list($name, $value) = explode(':', $buffer);

                switch ($name) {
                    case 'MemTotal':
                        $mem_total = intval($value) * 1024;
                        break;

                    case 'MemFree':
                        $mem_free = intval($value) * 1024;
                        break;

                    case 'Buffers':
                        $mem_buffers = intval($value) * 1024;
                        break;

                    case 'Cached':
                        $mem_cached = intval($value) * 1024;
                        break;

                    case 'SwapTotal':
                        $mem_swap_total = intval($value) * 1024;
                        break;

                    case 'SwapFree';
                        $mem_swap_free = intval($value) * 1024;
                        break;
                }
            }

            print_progressbar("Applications",
                format_byte($mem_total - $mem_free - $mem_cached - $mem_buffers));

            print_progressbar("Cache/Buffers",
                format_byte($mem_buffers + $mem_cached));


            $mem_free_nocb = $mem_free + $mem_buffers + $mem_cached;


            print_progressbar("Free (- C/B)",
                format_byte($mem_free_nocb),
                -1, $mem_free_nocb < 40000000);

            print_progressbar("Total (+ C/B)",
                format_byte($mem_total - $mem_free) . " / " . format_byte($mem_total),
                round(($mem_total - $mem_free) * 100 / $mem_total));

            print_progressbar("Swap",
                format_byte($mem_swap_total - $mem_swap_free) . " / " . format_byte($mem_swap_total),
                ($mem_swap_total > 0) ? round(($mem_swap_total - $mem_swap_free) * 100 / $mem_swap_total) : 0);
        } else {
            echo 'unable to open /proc/meminfo !';
        }


        /* --------------------------------------------------------------------
         *  Disk usage
         */
        if (isset($_SESSION['statuspanel_disk_info'])) {
            echo '<h2>Drives</h2>';

            $disks = explode(';', $_SESSION['statuspanel_disk_info']);

            foreach ($disks as $mntpoint) {

                $total = disk_total_space($mntpoint);
                $used = $total - disk_free_space($mntpoint);
                $perc = ($total > 0) ? round(($used * 100) / $total) : 0;

                print_progressbar($mntpoint,
                    format_byte($used) . " / " . format_byte($total),
                    $perc, $perc > 95);
            }
        }

        /* --------------------------------------------------------------------
         *  Network interfaces stats
         */
        if ($fc = @file_get_contents('/proc/net/dev')) {
            echo '<h2>Network</h2>';

            $buffer = preg_split("/\n/", $fc, -1, PREG_SPLIT_NO_EMPTY);

            foreach ($buffer as $line) {
                if (strpos($line, ":") !== FALSE) {
                    $stats = preg_split('/\s+|:/', $line, -1, PREG_SPLIT_NO_EMPTY);

                    if ($stats[0] == "lo")
                        continue;

                    print_progressbar($stats[0],
                        "down: " . format_byte($stats[1]) . " / up: " . format_byte($stats[9]));
                }
            }
        } else {
            echo 'unable to open /proc/net/dev !';
        }
    }


    function parse_uptime()
    {
        $fc = file_get_contents('/proc/uptime');
        list($uptime) = explode(' ', $fc);

        if ($uptime < 60)
            return "< 1 minute";

        $s_uptime = array();

        $day = floor($uptime / 86400);
        $uptime %= 86400;

        if ($day > 1)
            $s_uptime[]= $day . " days";
        else if ($day == 1)
            $s_uptime[] = "1 day";

        $hour = floor($uptime / 3600);
        $uptime %= 3600;

        if ($hour > 1)
            $s_uptime[] = $hour . " hours";
        else if($hour == 1)
            $s_uptime[] = " 1 hour";

        $min = floor($uptime / 60);
        $uptime %= 60;

        if ($min > 1)
            $s_uptime[] = $min . " minutes";
        else if($min == 1)
            $s_uptime[] = "1 minute";

        return implode(", ", $s_uptime);
    }
}

$WIDGETS['sysinfo'] = new WidgetSysinfo();
?>
