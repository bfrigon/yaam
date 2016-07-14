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
		$this->register_tab('draw', 'users', null, 'Users', 'admin');
	}

	function draw($template, $tab_id, $tab_path)
	{
		global $DB;

		if (isset($_POST['user'])) {
			$tab_path = 'save';
		}
		
		$current_page = max((isset($_GET['page']) ? intval($_GET['page']) : 1), 1); 
		$max_results = max((isset($_GET['max']) ? intval($_GET['max']) : intval($_SESSION['cdr_rpp'])), 1);
		$row_start = ($current_page - 1) * $max_results;
		
		$res    = $DB->exec_query('SELECT * FROM groups');
		$groups = array();

		while (odbc_fetch_row($res)) {
		   	$groups[odbc_result($res, 'group')] = odbc_result($res, 'fullname');
		}
		
		odbc_free_result($res);

		switch ($tab_path) {
			case 'add':
				$new = true;

				$last_post = array(
					'user'      => '',
					'fullname'  => '',
					'exten'     => '',
					'passwd'    => '',
					'pgroups'   => '',
					'ui_theme'  => '',
					'user_chan' => '',
					'vbox'      => '',
				);

				require($template->load('add_edit.tpl'));
				break;

			case 'edit':
				$new = false;

				$res = $DB->exec_query(
					'
						SELECT *
						FROM users
						WHERE user = ?
						LIMIT 1
					',
					array($_GET['id'])
				);

				$last_post = array(
					'user'      => odbc_result($res, 'user'),
					'fullname'  => odbc_result($res, 'fullname'),
					'exten'     => odbc_result($res, 'extension'),
					'passwd'    => '',
					'pgroups'   => odbc_result($res, 'pgroups'),
					'ui_theme'  => odbc_result($res, 'ui_theme'),
					'user_chan' => odbc_result($res, 'user_chan'),
					'vbox'      => odbc_result($res, 'vbox'),
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
							DELETE FROM users
							WHERE user = ?
							LIMIT 1
						',
						array($_GET['id'])
					);
				}

				$this->redirect("index.php#Users.users");
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
							SELECT user
							FROM users
							WHERE user = ?
							LIMIT 1
						',
						array($_POST['user'])
					);
	
					$rows = odbc_num_rows($res);
					odbc_free_result($res);

					if ($rows > 0) {
						print_message('Duplicated user', true);
						require($template->load('add_edit.tpl'));
						break;

					} else {
						$res = $DB->exec_query(
							'
								INSERT INTO users(
									user,
									fullname, 
									extension,
									pwhash,
									pgroups,
									ui_theme,
									user_chan,
									vbox
								) VALUES (
									?, ?, ?,
									?, ?, ?,
									?, ?
								)
							',
							array(
								$_POST['user'],
								$_POST['fullname'],
								$_POST['exten'],
								$passwd,
								$_POST['pgroups'],
								$_POST['ui_theme'],
								$_POST['user_chan'],
								$_POST['vbox'],
							)
						);

						odbc_free_result($res);
					}

				} else {
					$res = $DB->exec_query(
						'
							UPDATE users
							SET
								fullname = ?,
								extension = ?,
								pwhash = IF(? = "", pwhash, ?),
								pgroups = ?,
								ui_theme = ?,
								user_chan = ?,
								vbox = ?
							WHERE user = ?
							LIMIT 1
						',
						array(
							$_POST['fullname'],
							$_POST['exten'],
							$passwd,
							$passwd,
							$_POST['pgroups'],
							$_POST['ui_theme'],
							$_POST['user_chan'],
							$_POST['vbox'],
							$_POST['user'],
						)
					);

					odbc_free_result($res);
           			}

				$this->redirect("index.php#Users.users");
				break;

			default:
				$search = '%' . $_GET['s'] . '%';

				if ($search == '%%') {
					$search = '%';
				}

			        $r_total = $DB->exec_query(
					'
						SELECT COUNT(*)
						FROM users
						WHERE 
							(user LIKE ?) OR
							(fullname LIKE ?) OR
							(pgroups LIKE ?) OR
							(user_chan LIKE ?) OR
							(vbox LIKE ?)
					',
					array(
						$search, $search, $search,
						$search, $search
					)
				);

				$total = odbc_result($r_total, 1);
		
				/* Set pager variables for the template */
				$total_pages = max(1, ceil($total / $max_results));

			        $results = $DB->exec_query(
					'
						SELECT *
						FROM users
						WHERE 
							(user LIKE ?) OR
							(fullname LIKE ?) OR
							(pgroups LIKE ?) OR
							(user_chan LIKE ?) OR
							(vbox LIKE ?)
					',
					array(
						$search, $search, $search,
						$search, $search
					)
				);
		
				require($template->load('users.tpl'));
				break;
		}
	}
}

