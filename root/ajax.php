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

require("include/common.php");

try {
    header("Cache-Control: no-cache, must-revalidate");

    $output = isset($_REQUEST["output"]) ? $_REQUEST["output"] : "";
    switch ($output) {
        case "wav":
            header("Content-Type: audio/x-wav");
            break;

        case "mp3":
            header("Content-Type: audio/mpeg");
            break;

        case "html":
            header("Content-Type: text/html");
            break;

        case "json":
            header("Content-Type: text/plain");
            break;

        default:
            header("Content-Type: text/html");
            throw new Exception("Invalid format");
            break;
    }

    session_start();

    if (!isset($_SESSION["logged"]))
        throw new HTTPException(403);

    if (!isset($_REQUEST["function"]))
        throw new Exception("function was not specified.");

    /* load config, connect to database */
    init_session();

    /* Call plugin AJAX function */
    $path = $_REQUEST["function"];
    $path = explode("/", $path);

    $plugin_name = $path[count($path) - 2];
    $ajax_function = "ajax_" . $path[count($path) - 1];

    /* Load the plugin containing the function */
    $manager = new PluginManager();
    $plugin = $manager->load($plugin_name);

    if (!method_exists($plugin, $ajax_function))
        throw new Exception("Cannot call ajax function in $plugin_name. No function named $ajax_function");

    $result = call_user_func(array($plugin, $ajax_function));

    if (is_array($result))
        echo json_encode($result);

// ----------------------------------------------
// HTTPException
// ----------------------------------------------
} catch (HTTPException $e) {

    switch ($output) {
        case "json":
            echo json_encode(array("_error" => $e->get_code()));
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
        case "json":
            echo json_encode(array("_error" => $error));
            break;

        case "html":
            print_message($error, true);
            break;

        default:
            echo $error;
            break;
    }
}
