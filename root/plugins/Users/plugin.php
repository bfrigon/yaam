<?php

if(realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME'])) {
	header('Location:../index.php');
	exit();
}

class PluginUsers extends PluginBaseTable
{

	public $_tablename = 'users';

	

	public $_dependencies = array();


	function on_load() 
	{
		$this->create_tab(null, 'users', 'Users', 'admin');
	}


	function draw($template, $tab_id, $tab_path)
	{
		global $DB;
	
		$test = '4504492406';
		$current_page = 4;
		$total_pages = 10;

		
		$folders = array('tmp' => 'Trash', 'inbox' => 'Inbox', 'old' => 'Old');
		$current_folder = 'Trash';
		
		$num_results = 45;
		$results = $DB->exec_query('SELECT * FROM call_treatment', array());
		
		require($template->load('users.tpl'));
	
	}


}
