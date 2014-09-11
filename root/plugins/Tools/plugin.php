<?php

if(realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME'])) {
	header('Location:../index.php');
	exit();
}

class PluginTools extends Plugin
{
	function on_load() 
	{
	
		$this->register_tab(null, 'tools', null, 'Tools', 'user', 200);
		$this->register_tab('do_profile', 'profile', 'tools', 'Edit profile', 'user');
		$this->register_tab('do_originate', 'originate', 'tools', 'Originate Call', 'user');
	}

	
		
	/************************************************************************************************
	*
	* Originate call tab
	*
	*************************************************************************************************/
	function do_originate($template, $tab_path, $action, $uri)
	{
		global $CONFIG;
	
		try {

			$f_chan = isset($_POST['chan']) ? $_POST['chan'] : $_SESSION['user_chan'];
			$f_exten = isset($_POST['exten']) ? $_POST['exten'] : "";
			$f_context = isset($_POST['context']) ? $_POST['context'] : "";
			$f_cid_num = isset($_POST['cid_num']) ? $_POST['cid_num'] : "";
			$f_cid_name = isset($_POST['cid_name']) ? $_POST['cid_name'] : "";
			$h_chan = false;
			$h_exten = false;

			if (!function_exists("ami_connect"))
				throw new Exception("Extension php-ami is not installed");

			if (count($_POST) > 0) {

				if (empty($f_chan)) {
					$h_chan = true;
					throw new Exception('The destination channel is missing.');
				}
	
				if (empty($f_exten)) {
					$h_exten = true;
					throw new Exception('The destination extension is missing.');
				}
	

				if (empty($context))
					$context = "default";

				$cid = NULL;
				$priority = "1";
				$exten = $f_exten;
				$context = $f_context;
	
				if (!empty($f_cid_num) || !empty($f_cid_name))
					$cid = "$f_cid_name <$f_cid_num>";

				if (strstr($exten, ",") != 0)
					list($exten, $priority) = explode(",", $f_exten, 2);

				if (empty($context))
					$context = "default";
					
				
		
				if (($conn = @ami_connect($CONFIG['ami_host'], $CONFIG['ami_user'], $CONFIG['ami_pass'], $CONFIG['ami_port'])) == NULL)
					throw new Exception("Cannot connect to the Asterisk manager interface.");

				if (@ami_originate($conn, $f_chan, $context, $exten, $priority, true, $cid)) {

					$date = date(DATE_RFC2822);
					print_message("Originate call succeded.<br />\"$f_chan\" -> \"$context/$exten/$priority\" <br />at $date");

				} else {
					throw new Exception("Originate call failed!<br />" . ami_lasterror());
				}
			}
		} catch (Exception $e) {
		
			print_message($e->getmessage(), true);
		}
		
		require($template->load('originate.tpl'));
	}
	
	
	
	/************************************************************************************************
	*
	* User profile tab
	*
	*************************************************************************************************/
	function do_profile($template, $tab_path, $action, $uri)
	{
		global $DB;

		$highlight_fields = null;
		$profile_updated = false;

		try {
			if (!empty($_POST['new_pwd']) || !empty($_POST['old_pwd'])) {

				$result = $DB->exec_query('SELECT pwhash from users WHERE user=?', 
						array($_SESSION['user']));

				if (!(@odbc_fetch_row($result)))
					throw new Exception('user ' . $_SESSION['user'] . ' don\'t exist');
		
				if (hash('sha256', $_POST['old_pwd'], false) != $_SESSION['pwhash']) {
					$highlight_fields = array('old_pwd');
					throw new Exception('The old password is invalid.');		
				}
		
				if (strlen($_POST['new_pwd']) < 6) {
					$highlight_fields = array('new_pwd');
					throw new Exception('The password must contain at least 6 characters.');
				}

				if (hash('sha256', $_POST['new_pwd']) != hash('sha256', $_POST['new_pwdver'])) {
					$highlight_fields = array('new_pwd', 'new_pwdver');
					throw new Exception('The new password don\'t match with the verification field.');
				}
		
				$_SESSION['pwhash'] = hash('sha256', $_POST['new_pwd']);
				$profile_updated = true;
			}
	
			/* check if full name has changed */
			if (!empty($_POST['fullname']) && $_POST['fullname'] != $_SESSION['fullname']) {
	
				$_SESSION['fullname'] = $_POST['fullname'];
		
				echo '<script>$("#userinfo_fullname").html("', $_SESSION['fullname'], '");</script>';
		
				$profile_updated = true;	
			}
	
			/* Check if UI theme has changed */
			if (!empty($_POST['theme']) && $_POST['theme'] != $_SESSION['ui_theme']) {

				/* Remove slashes from filename */
				$_POST['theme'] = preg_replace("_.*(/|\\\\)_", "", $_POST['theme']);

				/* Check if theme exist */		
				if (!file_exists(DOCUMENT_ROOT . '/themes/' . $_POST['theme'] . '/theme.css')) {
					$highlight_fields = array('theme');
					throw new Exception('The style "' . $_POST['theme'] . '" does not exist');
				}

				$_SESSION['ui_theme'] = $_POST['theme'];
		
				echo '<script>';
				echo '$("#css_theme").attr("href", "css/', $_SESSION['ui_theme'], '/theme.css");';
				echo '$("#css_theme_ie7").attr("href", "css/', $_SESSION['ui_theme'], '/theme_ie7.css");';
				echo '$("#css_theme_ie8").attr("href", "css/', $_SESSION['ui_theme'], '/theme_ie8.css");';
				echo '</script>';
	
				$profile_updated = true;
			}
			
			
			/* Only admin can change these settings */
			if ($_SESSION['user'] == 'admin') {
				if (!empty($_POST['vbox']) && $_POST['vbox'] != $_SESSION['vbox']) {
					$_SESSION['vbox'] = $_POST['vbox'];
					$profile_updated = true;
				}
	
				if (!empty($_POST['user_chan']) && $_POST['user_chan'] != $_SESSION['user_chan']) {
					$_SESSION['user_chan'] = $_POST['user_chan'];
					$profile_updated = true;
				}
			}

			/* Save the changes, if any, to the database */
			if ($profile_updated) {
				$result = $DB->exec_query('UPDATE users SET fullname=?, pwhash=?, ui_theme=?, vbox=?, user_chan=? WHERE user=?',
						array(
							$_SESSION['fullname'], 
							$_SESSION['pwhash'], 
							$_SESSION['ui_theme'],
							$_SESSION['vbox'],
							$_SESSION['user_chan'],
							$_SESSION['user']
						)
					);
	
				if (odbc_num_rows($result) != 0)
					print_message('The profile has been updated', false);
				else
					print_message('The profile was not updated', true);

				odbc_free_result($result);
			}
	

		} catch (Exception $e) {
			print_message($e->getmessage(), true);
		}
	
	
		require($template->load('profile.tpl'));
	}
}

?>
