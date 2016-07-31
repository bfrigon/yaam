<?php

if(realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME'])) {
	header('Location:../index.php');
	exit();
}

class PluginSystemLogs extends Plugin
{
	function on_load() 
	{
		
		$this->register_tab(null, 'logs', NULL, 'System logs', 'admin');
		$this->register_tab('print_logs', 'ast', 'logs', 'Asterisk server', 'admin');
		$this->register_tab('print_logs', 'sys', 'logs', 'System (syslog)', 'admin');
		$this->register_tab('print_logs', 'kern', 'logs', 'Kernel', 'admin');
		$this->register_tab('print_logs', 'dmesg', 'logs', 'Boot log', 'admin');
		$this->register_tab('print_logs', 'auth', 'logs', 'Authentication', 'admin');
	}
	
	function print_logs($template, $tab_path, $action, $uri_query)
	{
		$log_basename = preg_replace("_.*/_", "", isset($_REQUEST['file']) ? $_REQUEST['file'] : '');

		switch ($tab_path) {
			case 'SystemLogs.logs.sys':
				$log_filename = '/var/log/syslog';
				break;
		
			case 'SystemLogs.logs.kern':
				$log_filename = '/var/log/kern.log';
				break;
		
			case 'SystemLogs.logs.dmesg':
				$log_filename = '/var/log/dmesg';
				break;
		
			case 'SystemLogs.logs.auth':
				$log_filename = '/var/log/auth.log';
				break;

			default:
				$tab_id = 'ast';
				$log_filename = '/var/log/asterisk/messages';
				break;
		}
	
		$log_list = array();
		foreach (glob($log_filename . '*') as $path)
			$log_list[] = basename($path);
			
		if (empty($log_basename))
			$log_basename = basename($log_filename);

		$log_filename .= str_replace(basename($log_filename), '', $log_basename);
			
		require($template->load('template.tpl'));
		$this->include_js_script('ui.js');
	}
}
?>
