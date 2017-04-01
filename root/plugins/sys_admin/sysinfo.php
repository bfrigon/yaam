<?php


function get_system_uptime()
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


function get_system_load()
{
    $fc = file_get_contents('/proc/loadavg');
    $load = explode(' ', $fc);

    return implode(' ', array_slice($load, 0, 3));
}

function get_system_meminfo()
{
    if (!($fp = @fopen('/proc/meminfo', 'r')))
        return array();

    $threshold = intval(get_global_config_item("widget_sysinfo", "threshold_memory", 90));

    $mem_total = 0;
    $mem_free = 0;
    $mem_buffers = 0;
    $mem_cached = 0;
    $mem_swap_total = 0;
    $mem_swap_free = 0;


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

    $mem_used = $mem_total - ($mem_free + $mem_cached + $mem_buffers);
    $mem_swap_used = $mem_swap_total - $mem_swap_free;
    $perc_swap_used = (($mem_swap_total > 0) ? $mem_swap_used * 100 / $mem_swap_total : 0);
    $perc_used = (($mem_total > 0) ? $mem_used * 100 / $mem_total : 0);



    return array(
        "mem_used" => $mem_used,
        "total_cb" => $mem_cached + $mem_buffers,
        "free" => $mem_free + $mem_cached + $mem_buffers,
        "used" => $mem_used,
        "total" => $mem_total,
        "perc_used" => $perc_used,
        "swap_used" => $mem_swap_used,
        "swap_total" => $mem_swap_total,
        "perc_swap_used" => $perc_swap_used,

        "swap_critical" => ($perc_swap_used > $threshold),
        "free_critical" => ($perc_used > $threshold),
    );
}

function get_system_diskinfo()
{
    $disk_info = array();

    $mntpoints = get_global_config_item("widget_sys_info", "disks", array("Root" => "/"));
    $threshold = intval(get_global_config_item("widget_sysinfo", "threshold_diskspace", 90));

    foreach ($mntpoints as $name => $mntpoint) {

            $total = disk_total_space($mntpoint);
            $used = $total - disk_free_space($mntpoint);
            $perc = ($total > 0) ? round(($used * 100) / $total) : 0;

            $disk_info[$name] = array(
                "used" => $used,
                "total" => $total,
                "perc" => $perc,
                "class" => ($perc < $threshold ? "normal" : "critical")
            );
    }

    return $disk_info;
}

function get_system_netinfo()
{
    $net_info = array();

    $interfaces = explode(",", get_global_config_item("widget_sys_info", "network_interfaces", "eth0"));

    if ($fc = @file_get_contents('/proc/net/dev')) {

        $buffer = preg_split("/\n/", $fc, -1, PREG_SPLIT_NO_EMPTY);

        foreach ($buffer as $line) {
            if (strpos($line, ":") !== FALSE) {
                $stats = preg_split('/\s+|:/', $line, -1, PREG_SPLIT_NO_EMPTY);

                $name = $stats[0];

                /* Ignore if interface is not present in the list */
                if (!(in_array($name, $interfaces)))
                    continue;

                $net_info[$stats[0]] = array(
                    "down" => $stats[1],
                    "up" => $stats[9],
                );
            }
        }
    } else {
        echo 'unable to open /proc/net/dev !';
    }

    return $net_info;
}




function get_service_status()
{
    $service_info = array();


    $services = get_global_config_item("widget_service_monitor", "services", array());

    foreach ($services as $name => $service) {
        $pid = 0;

        if (isset($_SESSION["tmp_sysinfo_srv_$service"]))
            $pid = $_SESSION["tmp_sysinfo_srv_$service"];

        if ($pid == 0)
            $pid = find_service_pid($service);

        if ($pid == 0 || (!file_exists("/proc/$pid/status"))) {

            $_SESSION["tmp_sysinfo_srv_$service"] = 0;
            $stopped = true;

        } else {
            $stopped = false;
        }

        $service_info[$name] = array(
            "state" => ($stopped) ? "Stopped" : "Running",
            "class" => ($stopped) ? "critical" : "normal",
        );
    }

    return $service_info;
}

function find_service_pid($name)
{
    $pid = intval(shell_exec("pgrep " . escapeshellcmd($name)));

    $_SESSION['tmp_sysinfo_srv_' . $name] = $pid;
    return $pid;
}

