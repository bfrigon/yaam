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
			$s_uptime[]	= $min . " minutes";
		else if($min == 1)
			$s_uptime[] = "1 minute";
		
		return implode(", ", $s_uptime);
	}
}

$WIDGETS['sysinfo'] = new WidgetSysinfo();
?>
