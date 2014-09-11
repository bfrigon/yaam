<?php

if(realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME'])) {
	header('Location:../index.php');
	exit();
}

class PluginCDR extends Plugin
{
	public $_dependencies = array('Tools');

	function on_load() 
	{
		if (!isset($_SESSION['cdr_columns']))
			$_SESSION['cdr_columns'] = 'type;calldate;cid_num;cid_name;dst;duration;billsec;cost;disposition';

		if (!isset($_SESSION['cdr_rpp']))
			$_SESSION['cdr_rpp'] = '25';
		
		$this->register_tab('tab_cdr', 'cdr', null, 'Call log', 'user');
		$this->register_tab('tab_cdr_routes', 'cdr_routes', 'tools', 'Call routes', 'admin');
	}
	
	
	function tab_cdr($template, $tab_path, $action, $uri)
	{
		global $DB;
		
		$current_page = max((isset($_GET['page']) ? intval($_GET['page']) : 1), 1); 
		$max_results = max((isset($_GET['max']) ? intval($_GET['max']) : intval($_SESSION['cdr_rpp'])), 1);
		$row_start = ($current_page - 1) * $max_results;
		
		if (isset($_GET['s'])) {
			$search = '%' . $_GET['s'] . '%';
		
			$r_total = $DB->exec_query("SELECT COUNT(*),SUM(cost),SUM(duration),SUM(billsec)
										FROM cdr WHERE dst!='s' AND (dst like ? OR src like ? OR clid like ?)", 
									   array($search, $search, $search));
									   
			$results = $DB->exec_query("SELECT * FROM cdr 
										WHERE dst !='s' AND (dst like ? OR src like ? OR clid like ?)
										ORDER BY calldate DESC LIMIT $row_start,$max_results", 
									   array($search, $search, $search));

		
		} else {
			$r_total = $DB->exec_query("SELECT COUNT(*),SUM(cost),SUM(duration),SUM(billsec) FROM cdr WHERE dst!='s'", 
									   array());
									   
			$results = $DB->exec_query("SELECT * FROM cdr WHERE dst !='s' ORDER BY calldate DESC LIMIT $row_start,$max_results", 
									   array());
		}
		
		$total_calls = odbc_result($r_total, 1);
		$total_duration = odbc_result($r_total, 3);
		$total_billsec = odbc_result($r_total, 4);
		$total_cost = odbc_result($r_total, 2);
		
		/* Set pager variables for the template */
		$total_pages = max(ceil($total_calls / $max_results), 1);

		/* Apply the template */	
		require($template->load('template.tpl'));
		
		
		$this->include_js_script('filters.js');
	}
	
	
	function regex_clid($clid)
	{
		preg_match('|(?:"(.*)")?\s*<?([\d\*#]*)>?|', $clid, $matches);
		list(, $clid_name, $clid_number) = $matches;
		
		$clid_name = ucfirst(strtolower($clid_name));
		
		return array($clid_name, $clid_number);
	}
	
	
	function tab_cdr_routes($template, $tab_path, $action, $uri)
	{
		global $DB;
		
		switch ($action) {
		
			case 'add':
			case 'edit':
				try {
					$id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;			
					$f_name = isset($_REQUEST['name']) ? $_REQUEST['name'] : '';
					$f_type = isset($_REQUEST['type']) ? $_REQUEST['type'] : '';
					$f_cost = isset($_REQUEST['cost']) ? floatval($_REQUEST['cost']) : 0;
					$f_minimum = isset($_REQUEST['minimum']) ? intval($_REQUEST['minimum']) : 0;
					$f_inc = isset($_REQUEST['inc']) ? intval($_REQUEST['inc']) : 0;
					$f_priority = isset($_REQUEST['priority']) ? intval($_REQUEST['priority']) : 1;
					$f_srcchannel = isset($_REQUEST['srcchannel']) ? $_REQUEST['srcchannel'] : '';
					$f_src = isset($_REQUEST['src']) ? $_REQUEST['src'] : '';
					$f_dcontext = isset($_REQUEST['dcontext']) ? $_REQUEST['dcontext'] : '';
					$f_dst = isset($_REQUEST['dst']) ? $_REQUEST['dst'] : '';
					$f_dstchannel = isset($_REQUEST['dstchannel']) ? $_REQUEST['dstchannel'] : '';
			
				
					if (isset($_REQUEST['name'])) {

						if (strlen($f_name) == 0) {
							$h_name = true;
							throw new Exception('The route name is missing');
						}
						
						if (strlen($f_type) == 0) {
							$h_type = true;
							throw new Exception('The route type is missing');
						}
						
						
						$fields = 	array($f_name, $f_type, $f_cost, $f_minimum, $f_inc, $f_priority, 
									$f_srcchannel, $f_src, $f_dcontext, $f_dst, $f_dstchannel);
						
						if ($action == 'edit') {
							$fields[] = $id;
						
							$result = $DB->exec_query('UPDATE cdr_routes SET name=?,type=?,cost=?,min=?,increment=?,priority=?,
													   channel=?,src=?,dcontext=?,dst=?,dstchannel=? WHERE id=?', 
													   $fields);
						} else {
							$results = $DB->exec_query('INSERT INTO cdr_routes
														(name,type,cost,min,increment,priority,channel,src,dcontext,dst,dstchannel) 
														VALUES (?,?,?,?,?,?,?,?,?,?,?)',
														$fields);
						}
						
						$this->redirect($referer);
						break;
				
				
					} else if ($action == 'edit') {
						if (!isset($_REQUEST['id']))
							throw new Exception('ID parameter is missing');
					
						$result = $DB->exec_query('SELECT * FROM cdr_routes WHERE id=?',
												   array($id));
					
						if (!odbc_fetch_row($result))
							throw new Exception('Record ID does not exists in the CDR routes database.');
					
						$f_name = odbc_result($result, 'name');
						$f_type = odbc_result($result, 'type');
						$f_cost = odbc_result($result, 'cost');				
						$f_minimum = odbc_result($result, 'min');				
						$f_inc = odbc_result($result, 'increment');
						$f_priority = odbc_result($result, 'priority');
						$f_srcchannel = odbc_result($result, 'channel');
						$f_src = odbc_result($result, 'src');
						$f_dcontext = odbc_result($result, 'dcontext');
						$f_dst = odbc_result($result, 'dst');
						$f_dstchannel = odbc_result($result, 'dstchannel');
				
					}
				} catch (Exception $e) {
					print_message($e->getmessage(), true);
				}		
			
				require($template->load('cdr_routes_add.tpl'));
			
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

					$result = $DB->exec_query("DELETE FROM cdr_routes WHERE id in ($id_list)", array());

					$this->redirect($referer);
					break;
					
				} catch (Exception $e) {
					
					print_message($e->getmessage(), true);				
				}
				
			default:
				$r_total = $DB->exec_query('select COUNT(*) from cdr_routes');
				$num_results = odbc_result($r_total, 1);

				$max_results = 15;
				$current_page = max((isset($_GET['page']) ? intval($_GET['page']) : 1), 1);
				$total_pages = max(ceil($num_results / $max_results), 1); 
				
				
				$row_start = ($current_page - 1) * $max_results;
				$results = $DB->exec_query("SELECT * FROM cdr_routes ORDER BY priority DESC LIMIT $row_start,$max_results", array());

				$template->currency_format = '%.4i $';

				require($template->load('cdr_routes.tpl'));
				$this->include_js_script('cdr_routes.js');
		
				break;
		}		
	}
}

?>
