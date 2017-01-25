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

class WidgetServices
{
    //public $min_width = "300px";      /* Minimum width */

    function print_widget()
    {
        echo '<h1>Services</h1>';

        $state = $this->check_service("asterisk");
        print_progressbar("Asterisk", $state ? "Running" : "Stopped", -1, !$state);

        $state = $this->check_service("mysqld");
        print_progressbar("MySQL", $state ? "Running" : "Stopped", -1, !$state);

        $state = $this->check_service("sshd");
        print_progressbar("SSH", $state ? "Running" : "Stopped", -1, !$state);

        $state = $this->check_service("ntpd");
        print_progressbar("NTP", $state ? "Running" : "Stopped", -1, !$state);

    }


    function check_service($name)
    {
        $pid = 0;

        if (isset($_SESSION['tmp_sysinfo_srv_' . $name]))
            $pid = $_SESSION['tmp_sysinfo_srv_' . $name];

        if ($pid == 0)
            $pid = $this->find_service_pid($name);

        if ($pid == 0 || (!file_exists("/proc/$pid/status"))) {

            $_SESSION['tmp_sysinfo_srv_' . $name] = 0;
            return false;

        } else {
            return true;
        }
    }

    function find_service_pid($name)
    {
        $pid = intval(shell_exec("pgrep " . escapeshellcmd($name)));

        $_SESSION['tmp_sysinfo_srv_' . $name] = $pid;
        return $pid;
    }

}

$WIDGETS['services'] = new WidgetServices();
?>
