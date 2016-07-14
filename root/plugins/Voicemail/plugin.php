<?php
//******************************************************************************
// plugin.php - Voicemail plugin
// 
// Project   : Asterisk Y.A.A.M (Yet another asterisk manager)
// Version   : 0.1
// Author    : Benoit Frigon
// Last mod. : 7 mars 2013
// 
// Copyright (c) 2011 - 2013 Benoit Frigon <bfrigon@gmail.com>
// www.bfrigon.com
// All Rights Reserved.
//
// This software is released under the terms of the GNU Lesser General Public 
// License v2.1. 
// A copy of which is available from http://www.gnu.org/copyleft/lesser.html
// 
//******************************************************************************

/* Don't allow this script to be called directly */
if(realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME'])) {
	header('Location:../index.php');
	exit();
}

class PluginVoicemail extends Plugin
{
	private $vm_root = '/var/spool/asterisk/voicemail';
	private $vm_user_path = '';

	/*--------------------------------------------------------------------------
	 * on_load : Called after the plugin has been loaded
	 *
	 * Arguments : None
	 * Return    : None
	 */
	function on_load() 
	{
		global $CONFIG;

		/* Create tabs for this plugin */
		$this->register_tab('draw', 'vm', null, 'Voicemail', 'user');

		if (isset($CONFIG['voicemail_root']))
			$this->vm_root = $CONFIG['voicemail_root'];
			
		$this->vm_user_path = $this->vm_root . '/' . $_SESSION['vbox'];
	}

	/*--------------------------------------------------------------------------
	 * draw : Display content for tab 'Voicemail'
	 *
	 * Arguments : None
	 * Return    : None
	 */
	function draw($template, $tab_id, $tab_path)
	{
		$msg_per_page = 15;
		$page = max((isset($_GET['page']) ? intval($_GET['page']) : 1), 1); 	
		
		$current_folder = preg_replace("_.*/_", "", (isset($_REQUEST['folder'])) ? $_REQUEST['folder'] : 'INBOX');
		
		$referer = $this->REQUEST_REFERER;
		$action = $this->REQUEST_ACTION;

		try {
		
			if ($action == 'delete') {
				$id_list = (isset($_REQUEST['id']) ? $_REQUEST['id'] : array());
				if (!is_array($id_list))
					$id_list = array($id_list);
	

				if (count($id_list) < 1)
					throw new Exception('No messages selected.');		

				if (!isset($_REQUEST['confirm'])) {

					$params = $_REQUEST;
					$params['confirm'] = 1;
					$params['referer'] = $referer;

					$dialog_href_delete = '?' . http_build_query($params);

					require($template->load('dialog_delete.tpl', true));
					return;
				}

				foreach($id_list as $id) {
					$msg_files = $this->vm_user_path . '/' . $current_folder . '/msg' . $id . '.*';
	
					foreach(glob($msg_files) as $filename) {
						unlink($filename);
					}
				}
				
				$this->redirect($referer);
				break;
			}
			
			if (strstr($action, 'move')) {
				$id_list = (isset($_REQUEST['id']) ? $_REQUEST['id'] : array());
				if (!is_array($id_list))
					$id_list = array($id_list);
		
				if (count($id_list) < 1)
					throw new Exception('No messages to move.');
				
				
				$src_dir = $this->vm_user_path . '/' . $current_folder;
				$dest_dir = $this->vm_user_path . '/' . preg_replace("_.*/_", "", substr($action, 5));

				
				/* Find a free msg id in the destination directory */
				$dest_free_msgid = 0;
				$dest_msg_list = glob($dest_dir . '/msg*.txt');
				
				while (count($dest_msg_list)) {
					preg_match('/msg(\d{4}).txt/', array_pop($dest_msg_list), $matches);
					
					if (!isset($matches[1]))
						continue;
						
					$dest_free_msgid = intval($matches[1]) + 1;
					break;
				}
				
				/* Move the files */
				foreach($id_list as $id) {
					$src_msg_id = sprintf('%04d', $id);
					$dest_msg_id = sprintf('%04d', $dest_free_msgid++);
				
					foreach (glob($src_dir . '/msg' . $id . '.*') as $src_file) {
						$dest_file = $dest_dir . '/' . str_replace($src_msg_id, $dest_msg_id, basename($src_file));
						
						rename($src_file, $dest_file);						
					}
				}
				
				$this->redirect($referer);
				break;
				
			}
			
		} catch (Exception $e) {

			print_message($e->getmessage(), true);	
		}
		
		
		try {
			$folders = array();
			$current_folder_caption = '';			
			
			$msg_root_dir = $this->vm_user_path . '/*';

			
			foreach(glob($msg_root_dir, GLOB_ONLYDIR) as $dir) {
				$name = basename($dir);
				$caption = $name;
				switch ($name) {
					case 'INBOX':
						$caption = 'Inbox';
						break;

					case 'tmp':
						$caption = 'Trash';
						break;
				}
	                        
				if ($current_folder == $name)
					$current_folder_caption = $caption;
					
				$folders[] = array($name, $caption);
			}

			$msg_dir = $this->vm_user_path . '/' . $current_folder;
			$current_voicemail = $_SESSION['vbox'];
			$msg_list = glob($msg_dir . '/*.txt');
		

			$last_page = max(ceil(count($msg_list) / $msg_per_page), 1);
			$prev_page = ($page > 1) ? $page - 1 : 1;
			$next_page = min($page + 1, $last_page);
			$pages = range(max($page - 5, 1),min($page + 5, $last_page));

			if ($page > $last_page)
				$page = $last_page;
	
			$num_messages = count($msg_list);	
			$row_start = ($page - 1) * $msg_per_page;
			$row_end = min($num_messages, $row_start + $msg_per_page);	
	
			$messages = array();
	
	
			for ($id=$row_start; $id<($row_end); $id++) {
				preg_match('/msg(\d{4}).txt/', $msg_list[$id], $matches);
		
				if (count($matches) < 2)
					continue;
		
				$info = parse_ini_file($msg_list[$id], false, INI_SCANNER_RAW);
				$msg_id = $matches[1];
				$msg_date = date('Y-m-d H:i:s', intval($info['origtime']));
				$msg_duration = format_time_seconds(intval($info['duration']));
				$msg_size = format_byte(@filesize($msg_dir . '/msg' . $msg_id . '.wav'));
				$msg_url = 'ajax.php?function=Voicemail/download&output=wav&folder=' . $current_folder . '&msgid=' . $msg_id;
	
		
				preg_match('/\s*\"?([^<\"]*)\"?\s*<?(\d*)>?/', $info['callerid'], $matches);
		
				if (empty($matches[2])) {
					$msg_caller_num = format_phone_number($matches[1]);
					$msg_caller_name = "Unknown";
				} else {
					$msg_caller_num = format_phone_number($matches[2]);
					$msg_caller_name = ucwords(strtolower($matches[1]));
				}				

				$messages[] = array(
					$msg_id, 
					$msg_date, 
					$msg_caller_name, 
					$msg_caller_num,
					$msg_duration,
					$msg_size,
					$msg_url,
				);			
			}

		} catch (Exception $e) {

			print_message($e->getmessage(), true);	
		}

		require($template->load('template.tpl'));
		$this->include_js_script('ui.js');
	}
	
	
	
	
	
	function ajax_download()
	{
		$format = (isset($_GET['output'])) ? $_GET['output'] : 'wav';
		$msgid = (isset($_GET['msgid'])) ? $_GET['msgid'] : array('0000');
		
		if (!is_array($msgid))
			$msgid = array($msgid);

		$current_folder = isset($_REQUEST['folder']) ? $_REQUEST['folder'] : 'INBOX';
		$file_name = sprintf('msg%04d.%s', intval($msgid[0]), $format);
		$file_path = sprintf('%s/%s/%s', $this->vm_user_path, $current_folder, $file_name);
		
		if (!file_exists($file_path))
			throw new HTTPException(404);
	
		$file_size = filesize($file_path);

	
		if (isset($_SERVER['HTTP_RANGE'])) {

			$range = explode('-', substr($_SERVER['HTTP_RANGE'], 6));
			$start = intval($range[0]);
			$end = intval($range[1]);
		
			if ($end == 0)
				$end = $file_size - 1;

		
			header('HTTP/1.1 206 Partial Content');
		
			header('Accept-Ranges: bytes');
			header("Content-Range: bytes $start-$end/$file_size");
			header('Content-Length: ' . $end + 1 - $start);
		
		} else {
			$start = 0;
			$end = $file_size - 1;
		
			header('Content-Length: ' . $file_size);
		}
	
	
		header('Content-Disposition: attachment; filename=' . $file_name);
	
	
		if ($end == $file_size - 1 && $start == 0) {
			readfile($file_path);
		
		} else {
			$fh = fopen($file_path, 'r');
			fseek($fh, $start);
		
			$buffer = fread($fh, $end + 1 - $start);
			echo $buffer;
		
			fclose($fh);
		}
	
	
	}
}	
