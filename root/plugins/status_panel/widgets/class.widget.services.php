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
