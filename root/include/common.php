<?php
//******************************************************************************
// common.php - Common functions
//
// Project   : Asterisk Y.A.A.M (Yet another asterisk manager)
// Author    : Benoit Frigon <benoit@frigon.info>
//
// Copyright (c) Benoit Frigon
// www.bfrigon.com
//
// This software is released under the terms of the GNU Lesser General Public
// License v2.1.
// A copy of which is available from http://www.gnu.org/copyleft/lesser.html
//
//******************************************************************************
$DEBUG_TIME_START = microtime(true);

define('DOCUMENT_ROOT', dirname(__DIR__));
define('SERVER_SCRIPT_DIR', dirname($_SERVER['SCRIPT_NAME']));

define('YAAM_VERSION', '0.2.1');


require(DOCUMENT_ROOT . '/include/class.OdbcException.php');
require(DOCUMENT_ROOT . '/include/class.HTTPException.php');
require(DOCUMENT_ROOT . '/include/class.OdbcDatabase.php');
require(DOCUMENT_ROOT . '/include/class.PluginManager.php');
require(DOCUMENT_ROOT . '/include/class.Plugin.php');
require(DOCUMENT_ROOT . '/include/class.TemplateEngine.php');
require(DOCUMENT_ROOT . '/include/Template.Functions.php');


/*--------------------------------------------------------------------------
 * load_global_config() : Load configuration from /etc/yaam.conf
 *
 * Arguments
 * ---------
 *  None
 *
 * Returns   : An array containing the site configuration
 */
function load_global_config()
{
    $default_cfg = array(
        'db_dsn' => '',
        'db_user' => '',
        'db_pass' => '',
        'ami_host' => 'localhost',
        'ami_port' => 5038,
        'ami_user' => '',
        'ami_pass' => '',
        'plugins' => ''
    );

    if (!file_exists('/etc/yaam.conf'))
        throw new Exception('Configuration file /etc/yaam.conf does not exist.');

    if (($config = parse_ini_file('/etc/yaam.conf')) === false)
        throw new Exception('Can\'t open config file! (/etc/yaam.conf). Make sure the permissions are set correctly.');

    return array_merge($default_cfg, $config);
}


/*--------------------------------------------------------------------------
 * check_permission() : Check if the current user has the required permissions.
 *
 * Arguments
 * ---------
 *  - req_perm : Required permission to check.
 *
 * Returns   : True if permission match, false otherwise.
 */
function check_permissions($req_perm)
{
    if (trim($req_perm) == "")
        return true;

    $permissions = explode(",", $_SESSION['pgroups']);

    foreach ($permissions as $perm) {

        $perm = strtolower(trim($perm));

        if ($perm == "admin")
            return true;

        if ($perm == strtolower(trim($req_perm)))
            return true;
    }

    return false;
}


/*--------------------------------------------------------------------------
 * get_url_contents() : Read the content of a remote url.
 *
 * Arguments :
 *  - $url     : Url to read
 *  - $agent   ; User agent string to send to the remote server
 *  - $timeout : Request timeout (default 5 sec.)
 *
 * Returns   : The contents of the url.
 */
function get_url_contents($url, $agent=NULL, $timeout=5)
{
    $curl = curl_init();

    if ($agent == NULL)
        $agent = "Mozilla/5.0 (iPhone; U; CPU iPhone OS 3_0 like Mac OS X; en-us) AppleWebKit/528.18 (KHTML, like Gecko) Version/4.0 Mobile/7A341 Safari/528.16";

    curl_setopt($curl, CURLOPT_USERAGENT, $agent);
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);

    return curl_exec($curl);
}


/*--------------------------------------------------------------------------
 * print_message() : Print a message box.
 *
 * Arguments
 * ---------
 *  - message : Message to print
 *  - error   : True if the message is an error
 *
 * Returns   : Nothing
 */
function print_message($message, $error = false)
{

    $message = preg_replace("/\n/", "<br />", $message);

    if ($error)
        echo '<div class="box dialog error">';
    else
        echo '<div class="box dialog info">';

    echo $message;
    echo '</div>';
}
