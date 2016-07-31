<?php

if(realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME'])) {
	header('Location:../index.php');
	exit();
}

class PluginGroups extends PluginBaseTable
{
	public $_tablename = 'groups';
	public $_dependencies = array();

	function on_load() 
	{
		$this->register_tab('draw', 'groups', null, 'Groups', 'admin');
	}

	function draw($template, $tab_id, $tab_path)
	{
		global $DB;

		if (isset($_POST['group'])) {
			$tab_path = 'save';
		}
		
		$current_page = max((isset($_GET['page']) ? intval($_GET['page']) : 1), 1); 
		$max_results = max((isset($_GET['max']) ? intval($_GET['max']) : intval($_SESSION['cdr_rpp'])), 1);
		$row_start = ($current_page - 1) * $max_results;
		
		switch ($tab_path) {
			case 'add':
				$new = true;

				$last_post = array(
					'group'     => '',
					'fullname'  => '',
				);

				require($template->load('add_edit.tpl'));
				break;

			case 'edit':
				$new = false;

				$res = $DB->exec_query(
					'
						SELECT *
						FROM groups
						WHERE `group` = ?
						LIMIT 1
					',
					array($_GET['id'])
				);

				$last_post = array(
					'group'    => odbc_result($res, 'group'),
					'fullname' => odbc_result($res, 'fullname'),
				);

				require($template->load('add_edit.tpl'));
				break;


			case 'delete':
				if ($_GET['id'] == 'admin') {
					print_message("You can't delete admin", true);
					break;

				} else {
					$res = $DB->exec_query(
						'
							DELETE FROM groups
							WHERE `group` = ?
							LIMIT 1
						',
						array($_GET['id'])
					);
				}

				$this->redirect("index.php#Groups.groups");
				break;

			case 'save':
				$passwd = '';
	
				if (!empty( $_POST['passwd'])) {
					$passwd = hash('sha256', $_POST['passwd']);
				}

				$new = ($_POST['op'] == 'add');
				$last_post = $_POST;

				if ($_POST['op'] == 'add') {
					$res = $DB->exec_query(
						'
							SELECT `group`
							FROM groups
							WHERE `group` = ?
							LIMIT 1
						',
						array($_POST['group'])
					);
	
					$rows = odbc_num_rows($res);
					odbc_free_result($res);

					if ($rows > 0) {
						print_message('Duplicated group', true);
						require($template->load('add_edit.tpl'));
						break;

					} else {
						$res = $DB->exec_query(
							'
								INSERT INTO groups(
									`group`,
									fullname
								) VALUES (?, ?)
							',
							array(
								$_POST['group'],
								$_POST['fullname'],
							)
						);

						odbc_free_result($res);
					}

				} else {
					$res = $DB->exec_query(
						'
							UPDATE groups
							SET fullname = ?
							WHERE `group` = ?
							LIMIT 1
						',
						array(
							$_POST['fullname'],
							$_POST['group'],
						)
					);

					odbc_free_result($res);
           			}

				$this->redirect("index.php#Groups.groups");
				break;

			default:
				$search = '%' . $_GET['s'] . '%';
				
				if ($search == '%%') {
					$search = '%';
				}

			        $r_total = $DB->exec_query(
					'
						SELECT COUNT(*)
						FROM groups
						WHERE
							(`group` LIKE ?) OR
							(fullname LIKE ?)
					',
					array($search, $search)
				);

				$total = odbc_result($r_total, 1);
		
				/* Set pager variables for the template */
				$total_pages = max(1, ceil($total / $max_results));

			        $results = $DB->exec_query(
					'
						SELECT *
						FROM groups
						WHERE 
							(`group` LIKE ?) OR
							(fullname LIKE ?)
					',
					array($search, $search)
				);
		
				require($template->load('template.tpl'));
				break;
		}
	}
}

