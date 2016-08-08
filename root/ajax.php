<?php
//******************************************************************************
// ajax.php - Ajax requests handler
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
require('include/common.php');


$output = isset($_REQUEST['output']) ? $_REQUEST['output'] : 'json';

header('Cache-Control: no-cache, must-revalidate');

switch ($output) {
    case 'wav': header('Content-Type: audio/x-wav'); break;
    case 'mp3': header('Content-Type: audio/mpeg'); break;
    case 'html': header('Content-Type: text/html'); break;
    default: header('Content-Type: text/plain'); break;
}

try {

    session_start();


    if (!isset($_SESSION['logged']))
        throw new HTTPException(403);

    /* Load configuration */
    $CONFIG = load_global_config();

    /* Connect to the database */
    $DB = new ODBCDatabase($CONFIG['db_dsn'], $CONFIG['db_user'], $CONFIG['db_pass']);

    if (!isset($_REQUEST['path']) && !isset($_REQUEST['function']))
        throw new Exception('Path was not specified.');

    if (isset($_REQUEST['js']))
        $_SESSION['js'] = true;

    $manager = new PluginManager();

    /* Call plugin AJAX function */
    if (isset($_REQUEST['function'])) {
        $path = $_REQUEST['function'];
        $path = explode('/', $path);

        $plugin_name = $path[count($path) - 2];
        $ajax_function = 'ajax_' . $path[count($path) - 1];

        $plugin = $manager->load($plugin_name);

        if (!method_exists($plugin, $ajax_function))
            throw new Exception('Cannot call ajax function in ' . $plugin_name . '. No function named ' . $ajax_function);

        $result = call_user_func(array($plugin, $ajax_function));

        if (is_array($result))
            echo json_encode($result);

    /* Print plugin page content */
    } else {
        $path = isset($_REQUEST['path']) ? $_REQUEST['path'] : "";
        $manager->show_tab_content($path);

        printf('<script>$("#exec_time").html("%0.4f s");</script>', (microtime(true) - $DEBUG_TIME_START));

    }


//*****************************************************************************
//
// Exceptions
//
//*****************************************************************************

// ----------------------------------------------
// HTTPException
// ----------------------------------------------
} catch (HTTPException $e) {

    switch ($output) {
        case 'json':
            echo json_encode(array('_error' => $e->get_code()));
            break;

        default:
            $e->print_error_page();
            break;

    }


// ----------------------------------------------
// Exception
// ----------------------------------------------
} catch (Exception $e) {

    $error = $e->getmessage();

    switch ($output) {
        case 'json':
            echo json_encode(array('_error' => $error));
            break;

        case 'html':
            print_message($error, true);
            break;

        default:
            echo $error;
            break;
    }
}
