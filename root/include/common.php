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

define("DOCUMENT_ROOT", dirname(__DIR__));
define("SERVER_SCRIPT_DIR", dirname($_SERVER["SCRIPT_NAME"]));
define("YAAM_CONFIG_FILE", "/etc/asterisk/yaam.conf");

define("FORCE_RECOMPILE_TEMPLATE", true);
define("TEMPLATE_ENGINE_DEBUG", true);

define("YAAM_VERSION", "0.2.305");

define("DATE_FORMAT_MYSQL", 0);
define("DATE_FORMAT_DATEPICKER", 1);
define("DATE_FORMAT_DATETIME", 2);

define("PERMISSION_LVL_ADMIN", 1000);
define("PERMISSION_LVL_MANAGER", 500);
define("PERMISSION_LVL_USER", 1);
define("PERMISSION_LVL_DISABLED", 0);


require(DOCUMENT_ROOT . "/include/class_odbc_exception.php");
require(DOCUMENT_ROOT . "/include/class_http_exception.php");
require(DOCUMENT_ROOT . "/include/class_odbc_database.php");
require(DOCUMENT_ROOT . "/include/class_query_builder.php");
require(DOCUMENT_ROOT . "/include/class_plugin_manager.php");
require(DOCUMENT_ROOT . "/include/class_plugin.php");
require(DOCUMENT_ROOT . "/include/class_template_engine.php");
require(DOCUMENT_ROOT . "/include/template_functions.php");


/*--------------------------------------------------------------------------
 * load_global_config() : Load configuration from /etc/yaam.conf
 *
 * Arguments
 * ---------
 *  None
 *
 * Returns   : Nothing
 */
function load_global_config()
{
    global $CONFIG;

    $default_cfg = array("general" => array(
        "db_dsn" => "",
        "db_user" => "",
        "db_pass" => "",
        "ami_host" => "localhost",
        "ami_port" => 5038,
        "ami_user" => "",
        "ami_pass" => "",
        "plugins" => ""
    ));

    if (!file_exists(YAAM_CONFIG_FILE))
        throw new Exception("Configuration file " . YAAM_CONFIG_FILE . " does not exist.");

    if (($config = parse_ini_file(YAAM_CONFIG_FILE, true)) === false)
        throw new Exception(
            "Can't open config file! (" . YAAM_CONFIG_FILE . "). \r\n
            Make sure the permissions are set correctly."
        );

    $CONFIG = array_merge($default_cfg, $config);
}


/*--------------------------------------------------------------------------
 * get_global_config_item() : Return the value of a setting in the loaded
 *                            config file yaam.conf
 *
 * Arguments
 * ---------
 *  - section : Section to read the setting from.
 *  - item    : Setting name.
 *  - default : Default value.
 *
 * Returns   : The setting value or default value if not found.
 */
function get_global_config_item($section, $item, $default='')
{
    global $CONFIG;

    if (!(isset($CONFIG[$section][$item])))
        return $default;

    return $CONFIG[$section][$item];
}


/*--------------------------------------------------------------------------
 * load_user_config() : Load user configuration from database and populate
 *                      the _SESSION variable.
 *
 * Arguments
 * ---------
 *  None
 *
 * Returns   : Nothing
 */
function load_user_config()
{
    global $DB;
    $query = $DB->create_query("user_config");

    $query->where("user", "=", $_SESSION["user"]);

    $result = $query->run_query_select("*");

    while (odbc_fetch_row($result)) {
        $key = strtolower(odbc_result($result, "keyname"));
        $value = odbc_result($result, "value");

        switch ($key) {

            /* Skip these config item as they are defined in the user table */
            case "pwhash":
            case "plevel":
            case "user":
            case "dial_string":
            case "did":
            case "fullname":
            case "vbox_context":
            case "vbox_user":
            case "extension":
                continue;

            /* These key are used internaly */
            case "logged":
                continue;

            default:
                $_SESSION[$key] = $value;
                break;
        }
    }

    /* Use default theme if specified theme does not exists. */
    if (!file_exists(dirname(__FILE__) . "/themes/" . $_SESSION["ui_theme"]))
        $_SESSION["ui_theme"] = "default";

    odbc_free_result($result);
}


/*--------------------------------------------------------------------------
 * save_user_config() : Save user configuration to database.
 *
 * Arguments
 * ---------
 *  None
 *
 * Returns   : Nothing
 */
function save_user_config()
{
    global $DB;

    $user = $_SESSION["user"];

    /* Turn off auto-commit */
    $DB->set_autocommit(false);

    foreach ($_SESSION as $key => $value) {

        $key = strtolower($key);

        switch ($key) {

            /* Skip these config item as they are defined in the user table */
            case "pwhash":
            case "plevel":
            case "user":
            case "dial_string":
            case "fullname":
            case "vbox_context":
            case "vbox_user":
            case "did":
            case "extension":
                continue;

            /* These key are used internaly */
            case "logged":
                continue;

            default:
                /* Skip if the session variable is a temporary one */
                if (strpos($key, "tmp_", 0) === 0)
                    continue;

                $query = "
                    INSERT INTO user_config
                    SET user=?, keyname=?, value=?
                    ON DUPLICATE KEY UPDATE value=?
                ";

                $params = array($user, $key, $value, $value);

                $DB->exec_query($query, $params);
        }
    }

    /* Commit transaction */
    $DB->commit();
    $DB->set_autocommit(true);
}


/*--------------------------------------------------------------------------
 * init_session() : Initialize session (load config, connect to database)
 *
 * Arguments
 * ---------
 *  None
 *
 * Returns   : Nothing
 */
function init_session()
{
    global $CONFIG, $DB;

    /* Load configuration */
    load_global_config();

    /* Connect to the database */
    $dsn = get_global_config_item("general", "db_dsn");
    $user = get_global_config_item("general", "db_user");
    $pwd = get_global_config_item("general", "db_pass");
    $DB = new ODBCDatabase($dsn, $user, $pwd);
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
        echo "<div class=\"box dialog error\">";
    else
        echo "<div class=\"box dialog info\">";

    echo $message;
    echo "</div>";
}


/*--------------------------------------------------------------------------
 * get_user_dateformat() : Get the date format from user settings.
 *
 * Arguments
 * ---------
 *  - type : Date format syntax.
 *
 * Returns   : The date format string.
 */
function get_user_dateformat($type=null)
{
    $format = $_SESSION["date_format"];

    switch ($format) {
        /* Day/Month/Year */
        case "DD/MM/YYYY":
            switch ($type) {
                case DATE_FORMAT_MYSQL:      return "%d/%m/%Y";
                case DATE_FORMAT_DATEPICKER: return "D/M/YYYY";
                case DATE_FORMAT_DATETIME:   return "%d/%m/%Y %R";
            }

        /* Year-Month-Day */
        case "YYYY-MM-DD":
            switch ($type) {
                case DATE_FORMAT_MYSQL:      return "%Y/%m/%d";
                case DATE_FORMAT_DATEPICKER: return "YYYY-M-D";
                case DATE_FORMAT_DATETIME:   return "%Y-%m-%d %R";
            }

        /* Month/Day/Year */
        default:
            switch ($type) {
                case DATE_FORMAT_MYSQL:      return "%m/%d/%Y";
                case DATE_FORMAT_DATEPICKER: return "M/D/YYYY";
                case DATA_FORMAT_DATETIME:   return "%m/%d/%Y %R";
            }
    }

    return $format;
}


/*--------------------------------------------------------------------------
 * get_dateformat_list() : Returns an array containing supported date formats.
 *
 * Arguments
 * ---------
 *  None
 *
 * Returns   : The date format list
 */
function get_dateformat_list()
{
    return array(
        "DD/MM/YYYY" => "D/M/Y",
        "MM/DD/YYYY" => "M/D/Y",
        "YYYY-MM-DD" => "Y-M-D",
    );
}
