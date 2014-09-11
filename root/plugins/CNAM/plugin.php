<?php

if(realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME'])) {
	header('Location:../index.php');
	exit();
}

class PluginCNAM extends Plugin
{

	public $_dependencies = array('Tools');


	function on_load() 
	{
		$this->register_tab($this->draw, 'tools', 'CNAM directory', 'admin');
	}
	
	/************************************************************************************************
	*
	* CNAM directory tab
	*
	*************************************************************************************************/
	function draw($template, $tab_path, $action, $uri_query)
	{
		global $DB;
		$s_query = (isset($_GET['s']) ? $_GET['s'] : '');
		$s_wildcard = '%' . $s_query . '%';
		
		
		switch ($this->REQUEST_ACTION) {
		
			case 'add':
			case 'edit':
				try {
					$f_num = isset($_REQUEST['num']) ? $_REQUEST['num'] : '';
					$f_fullname = isset($_REQUEST['fullname']) ? $_REQUEST['fullname'] : '';
					$f_cidname = isset($_REQUEST['cidname']) ? $_REQUEST['cidname'] : '';
					$id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
				
					if (isset($_REQUEST['num'])) {
						if (strlen($f_num) == 0) {
							$h_num = true;
							throw new Exception('The phone number is missing');
						}

						if (strlen($f_cidname) == 0) {
							$h_cidname = true;
							throw new Exception('The CID name display is missing');
						}

						if (strlen($f_cidname) > 15) {
							$h_cidname = true;
							throw new Exception('The CID name must not contain more than 15 characters.');
						}

						if ($action == 'edit') {
							$result = $DB->exec_query('UPDATE cnam SET number=?,cidname=?,fullname=? WHERE id=?', 
								array($f_num, $f_cidname, $f_fullname, $id));
						} else {
							$results = $DB->exec_query('INSERT INTO cnam (number,cidname,fullname) VALUES (?,?,?)',
								array($f_num, $f_cidname, $f_fullname));
						}

						$this->redirect($referer);
						break;
						
					} else if ($action == 'edit') {
						
						if (!isset($_REQUEST['id']))
							throw new Exception('ID parameter is missing');
						
						$result = $DB->exec_query('SELECT * FROM cnam WHERE id=?',
												   array($id));
						
						if (!odbc_fetch_row($result))
							throw new Exception('Record ID does not exists in the CNAM database.');
						
						
						$f_num = odbc_result($result, 'number');
						$f_fullname = odbc_result($result, 'fullname');
						$f_cidname = odbc_result($result, 'cidname');
					}
					
				} catch (Exception $e) {
					print_message($e->getmessage(), true);
				}		
			
				require($template->load('cnam_add.tpl'));
							
				break;
		
			case 'delete':
				try {

					$id_list = (isset($_REQUEST['id']) ? $_REQUEST['id'] : array());
	
					if (count($id_list) < 1)
						throw new Exception('No items to delete.');		
				
					if (!isset($_REQUEST['confirm'])) {
					
						$params = $_REQUEST;
						$params['confirm'] = 1;
						$params['referer'] = $referer;

						$dialog_href_delete = '?' . http_build_query($params);
						
						require($template->load('dialog_delete.tpl', true));
						break;
					}
	
					$id_list = array_map('intval', $id_list);
					$id_list = join(',', $id_list);

					$result = $DB->exec_query("DELETE FROM cnam WHERE id in ($id_list)", array());

					$this->redirect($referer);
					break;
					
				} catch (Exception $e) {
					
					print_message($e->getmessage(), true);				
				}
				
				/* fall through */
		
			default:
				

				/* Get number of rows */
				if (!empty($s_query)) {
					$r_total = $DB->exec_query('SELECT count(*) FROM cnam 
												WHERE (number like ? OR fullname like ? or cidname like ?)',
											   array($s_wildcard, $s_wildcard, $s_wildcard));
				} else {
					$r_total = $DB->exec_query('SELECT count(*) FROM cnam', 
												array());
				}
		
				$num_results = odbc_result($r_total, 1);
				$max_results = 15;
		
				$current_page = max((isset($_GET['page']) ? intval($_GET['page']) : 1), 1); 
				$total_pages = max(ceil($num_results / $max_results), 1);
				
				$row_start = ($current_page - 1) * $max_results;

				/* Fetch the results */
				if (!empty($s_query)) {
					$results = $DB->exec_query("SELECT * FROM cnam 
												WHERE (number like ? OR fullname like ? or cidname like ?)
												LIMIT $row_start,$max_results", 
											   array($s_wildcard, $s_wildcard, $s_wildcard));
				} else {
					$results = $DB->exec_query("SELECT * FROM cnam 
												LIMIT $row_start,$max_results", 
												array());
				}

				require($template->load('cnam.tpl'));
				$this->include_js_script('cnam.js');

				break;
		}
	}
}

?>
