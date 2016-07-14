<?php

if(realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME'])) {
	header('Location:../index.php');
	exit();
}

class PluginCallTreatment extends Plugin
{

	public $_dependencies = array('Tools');


	function on_load() 
	{
		$this->register_tab($this->draw, 'ct', 'tools', 'Call Treatment', 'admin');
	}
	
	/************************************************************************************************
	*
	* call treatment directory tab
	*
	*************************************************************************************************/
	function draw($template, $tab_id, $tab_path)
	{
		global $DB;
		$s_query = (isset($_GET['s']) ? $_GET['s'] : '');
		$s_wildcard = '%' . $s_query . '%';
		
		$referer = $this->REQUEST_REFERER;
		$action = $this->REQUEST_ACTION;
		
		switch ($this->REQUEST_ACTION) {
		
			case 'add':
			case 'edit':
				
				try {
					$f_num = isset($_REQUEST['num']) ? $_REQUEST['num'] : '';
					$f_ctaction = isset($_REQUEST['ctaction']) ? $_REQUEST['ctaction'] : '';
					$f_extension = isset($_REQUEST['extension']) ? $_REQUEST['extension'] : '';
					$f_description = isset($_REQUEST['description']) ? $_REQUEST['description'] : '';
					$id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
				
					if (isset($_REQUEST['num'])) {
						if (strlen($f_num) == 0) {
							$h_num = true;
							throw new Exception('The phone number is missing');
						}

						if (strlen($f_ctaction) == 0) {
							$h_ctaction = true;
							throw new Exception('The call treatment action is missing.');
						}

						if ($action == 'edit') {
							$result = $DB->exec_query('UPDATE call_treatment SET number=?,action=?,extension=?,description=? WHERE id=?', 
								array($f_num, $f_ctaction, $f_extension, $f_description, $id));
						} else {
							$results = $DB->exec_query('INSERT INTO call_treatment (number,action,extension,description) VALUES (?,?,?,?)',
								array($f_num, $f_ctaction, $f_extension, $f_description));
						}

						$this->redirect($referer);
						break;
						
					} else if ($action == 'edit') {
						
						if (!isset($_REQUEST['id']))
							throw new Exception('ID parameter is missing');
						
						$result = $DB->exec_query('SELECT * FROM call_treatment WHERE id=?',
												   array($id));
						
						if (!odbc_fetch_row($result))
							throw new Exception('Record ID does not exists in the call treatment database.');
						
						
						$f_num = odbc_result($result, 'number');
						$f_ctaction = odbc_result($result, 'action');
						$f_extension = odbc_result($result, 'extension');
						$f_description = odbc_result($result, 'description');
					}
					
				} catch (Exception $e) {
					print_message($e->getmessage(), true);
				}		
			
				require($template->load('ct_add.tpl'));
							
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

					$result = $DB->exec_query("DELETE FROM call_treatment WHERE id in ($id_list)", array());

					$this->redirect($referer);
					break;
					
				} catch (Exception $e) {
					
					print_message($e->getmessage(), true);				
				}
				
				/* fall through */				
				
				
			default:
				
				

				/* Get number of rows */
				if (!empty($s_query)) {
					$r_total = $DB->exec_query('SELECT count(*) FROM call_treatment 
												WHERE (number like ? OR action like ? or extension like ? or description like ?)',
											   array($s_wildcard, $s_wildcard, $s_wildcard, $s_wildcard));
				} else {
					$r_total = $DB->exec_query('SELECT count(*) FROM call_treatment', 
												array());
				}
				
				$num_results = odbc_result($r_total, 1);
				
				$max_results = 15;
				$current_page = max((isset($_GET['page']) ? intval($_GET['page']) : 1), 1); 
				$total_pages = max(ceil($num_results / $max_results), 1);
				
				
				$row_start = ($current_page - 1) * $max_results;

				/* Fetch the results */
				if (!empty($s_query)) {
					$results = $DB->exec_query("SELECT * FROM call_treatment 
												WHERE (number like ? OR action like ? or extension like ? or description like ?)
												LIMIT $row_start,$max_results", 
											   array($s_wildcard, $s_wildcard, $s_wildcard, $s_wildcard));
				} else {
					$results = $DB->exec_query("SELECT * FROM call_treatment 
												LIMIT $row_start,$max_results", 
												array());
				}						
			
				$uri_query = $_GET;
				unset($uri_query['path'], $uri_query['page'], $uri_query['action']);
		
				if ($max_results == 25)
					unset($uri_query['max']);
		
				$uri_query = http_build_query($uri_query);

	
				require($template->load('ct.tpl'));			
		}		
	}
}

?>
