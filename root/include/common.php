<?php
//******************************************************************************
//
// Project : Asterisk Y.A.A.M (Yet another asterisk manager)
// Author  : Benoit Frigon <www.bfrigon.com>
//
// Contributors
// ============
//
//
//
// -----------------------------------------------------------------------------
//
// Copyright (c) 2017 Benoit Frigon
//
// Permission is hereby granted, free of charge, to any person obtaining a copy
// of this software and associated documentation files (the "Software"), to deal
// in the Software without restriction, including without limitation the rights
// to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
// copies of the Software, and to permit persons to whom the Software is
// furnished to do so, subject to the following conditions:
//
// The above copyright notice and this permission notice shall be included in
// all copies or substantial portions of the Software.
//
// THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
// IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
// FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
// AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
// LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
// OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
// THE SOFTWARE.
//
//******************************************************************************

define("DOCUMENT_ROOT", dirname(__DIR__));
define("SERVER_SCRIPT_DIR", dirname($_SERVER["SCRIPT_NAME"]));
define("YAAM_CONFIG_FILE", "/etc/asterisk/yaam.conf");
define("YAAM_VERSION", "0.3.322");


/* --- Date format type --- */
define("DATE_FORMAT_MYSQL", 0);
define("DATE_FORMAT_DATEPICKER", 1);
define("DATE_FORMAT_DATETIME", 2);


/* --- Permissions --- */
define("PERM_NONE", "");


/* --- Asterisk channel states --- */
define("AST_CHANNEL_STATE_DOWN", 0);
define("AST_CHANNEL_STATE_DOWN_RESERVED", 1);
define("AST_CHANNEL_STATE_OFF_HOOK", 2);
define("AST_CHANNEL_STATE_DIGITS_DIALED", 3);
define("AST_CHANNEL_STATE_REMOTE_RINGING", 4);
define("AST_CHANNEL_STATE_RINGING", 5);
define("AST_CHANNEL_STATE_UP", 6);
define("AST_CHANNEL_STATE_BUSY", 7);



require(DOCUMENT_ROOT . "/include/class_odbc_exception.php");
require(DOCUMENT_ROOT . "/include/class_http_exception.php");
require(DOCUMENT_ROOT . "/include/class_odbc_database.php");
require(DOCUMENT_ROOT . "/include/class_query_builder.php");
require(DOCUMENT_ROOT . "/include/class_plugin_manager.php");
require(DOCUMENT_ROOT . "/include/class_plugin.php");
require(DOCUMENT_ROOT . "/include/class_template_engine.php");
require(DOCUMENT_ROOT . "/include/class_ajam.php");
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

    if (!file_exists(YAAM_CONFIG_FILE))
        throw new Exception("Configuration file " . YAAM_CONFIG_FILE . " does not exist.");

    if (($CONFIG = parse_ini_file(YAAM_CONFIG_FILE, true)) === false) {
        throw new Exception(
            "Can't open config file! (" . YAAM_CONFIG_FILE . "). \r\n
            Make sure the permissions are set correctly."
        );
    }
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
function get_global_config_item($section, $item, $default="")
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
            case "pgroups":
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
            case "pgroups":
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
 * check_permission() : Check if the user has the required permissions
 *
 * Arguments
 * ---------
 *  req_perm : Required permissions
 *
 * Returns : True if user is allowed
 */
function check_permission($req_perm)
{
    /* No permissions are required */
    if (empty($req_perm))
        return true;

    /* 'admin' user has access to everything */
    if ($_SESSION["user"] == "admin")
        return true;

    return in_array($req_perm, $_SESSION["pgroups"]);
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
    global $CONFIG, $DB, $MANAGER;

    /* Load configuration */
    load_global_config();

    /* Connect to the database */
    $dsn = get_global_config_item("odbc", "database");
    $user = get_global_config_item("odbc", "user");
    $secret = get_global_config_item("odbc", "secret");

    $DB = new ODBCDatabase($dsn, $user, $secret);



    $url = get_global_config_item("ajam", "url", "http://127.0.0.1:8088");
    $user = get_global_config_item("ajam", "user", "");
    $secret = get_global_config_item("ajam", "secret", "");

    $MANAGER = new AJAM($user, $secret, $url);
    $MANAGER->login();
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
    $format = (isset($_SESSION["date_format"])) ? $_SESSION["date_format"] : "";

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
                case DATE_FORMAT_DATETIME:   return "%m/%d/%Y %R";
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



function get_extension_info($extension)
{
    global $DB;

    $query = $DB->create_query("users");
    $query->where("extension", "=", $extension);
    $query->limit(1);

    $results = $query->run_query_select("*");

    if (!($row = @odbc_fetch_array($results)))
        throw new Exception("Extension '$extension' does not exists!");

    return $row;
}
