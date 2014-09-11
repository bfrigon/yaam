<?php

if(realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME'])) {
	header('Location:../index.php');
	exit();
}

class PluginStatusPanel extends Plugin
{
	function on_load() 
	{
	
		if (!isset($_SESSION['statuspanel_widgets']))
			$_SESSION['statuspanel_widgets'] = 'sysinfo;services;versions;VBRAKE;log';
	
		if (!isset($_SESSION['statuspanel_disk_info']))
			$_SESSION['statuspanel_disk_info'] = '/;/var/log';
	
		$this->register_tab('draw', 'status', NULL, 'Status', 'user', 1);
	}

	function ajax_update()
	{
		global $DB;

		header("Content-Type: text/plain");

		/* Load all widgets classes */
		$WIDGETS = array();
		foreach (glob("plugins/StatusPanel/widgets/*.php") as $filename)
			include $filename;

		echo '<div>';

		foreach (explode(';', $_SESSION['statuspanel_widgets']) as $name) {
			if (!isset($WIDGETS[$name]))
				continue;
	
			$widget = $WIDGETS[$name];
	
			if (method_exists($widget, "needs_update") && !$widget->needs_update())
				continue;

			echo '<div id="', $name, '">';		
			$widget->print_widget();
			echo '</div>';		
		}

		echo '</div>';	
	}

	
	function draw($template, $tab_path, $action, $uri_query)
	{
		echo '<div style="height: 0px;">&nbsp;</div>';  /* weird IE7 bug */
		
		echo '<div class="widgets_left">';
	
		/* Load all widgets classes */
		$WIDGETS = array();
		foreach (glob("plugins/StatusPanel/widgets/*.php") as $filename)
			include $filename;

		foreach (explode(';', $_SESSION['statuspanel_widgets']) as $name)
		{
			if ($name == "VBRAKE") {
				echo '</div><div class="widgets_right">';

			} else {
				if (!isset($WIDGETS[$name]))
					continue;

				$style = '';

				if (isset($WIDGETS[$name]->min_width))
					$style .= 'min-width: ' . $WIDGETS[$name]->min_width . '; ';

				if (isset($WIDGETS[$name]->min_height))
					$style .= 'min-height: ' . $WIDGETS[$name]->min_height . '; ';

				/* Print widget content */
				echo '<div class="box widget" id="', $name, '" style="', $style, '">';

				$widget = $WIDGETS[$name]->print_widget();

				echo '</div>';
			}
		}

		echo '</div><div class="clear"></div>';
		
		$this->include_js_script('update.js');
	}
	
}



function print_progressbar($caption, $value, $percentage=-1, $critical=false)
{
	echo '<div class="progress', ($critical ? ' critical' : ''), '">';
	
	if ($percentage > -1)
		echo '<span class="bar" style="width: ', $percentage, '%;"></span>';	

	echo '<span class="name">', $caption, '</span>';
	echo '<span class="value">', $value;

	if ($percentage > -1)
		sprintf(" (%.1f %%)", $percentage);
		
	echo '</span></div>';		
}


?>
