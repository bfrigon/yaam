<?php

if(realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME'])) {
	header('Location:../index.php');
	exit();
}

class PluginBaseTable extends Plugin
{

	public $_tablename = '';
	public $_template_view = '';
	public $_template_add = '';
	public $_template_delete = '';
	

	
	function on_load()
	{
		
					
	}
	
	function exec($tplengine, $tab_id, $tab_path, $action, $referer)
	{
		global $DB;
		$s_query = (isset($_GET['s']) ? $_GET['s'] : '');
		$s_wildcard = '%' . $s_query . '%';
		
		
				
	}










}
