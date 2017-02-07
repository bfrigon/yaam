<?php
//******************************************************************************
// login.php - Login page
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
require("include/common.php");

$error_msg = "";
try {
    /* Destroy current session if logout is requested */
    if (isset($_REQUEST["logout"])) {
        session_start();
        session_destroy();
    }

    /* Start a new session or resume the current one */
    session_start();

    /* load config, connect to database */
    init_session();

    /* Check if admin user exists, if not redirect to setup page */
    if ($DB->exec_query_simple("SELECT user FROM users where user=\"admin\"", "user") != "admin") {
        header("Location: setup.php");
        exit();
    }

    $f_user = (isset($_REQUEST["user"]) ? strtolower($_POST["user"]) : "");
    $f_pass = (isset($_REQUEST["pass"]) ? $_POST["pass"] : "");

    if (!empty($f_user)) {
        $result = $DB->exec_query(
            "
            SELECT * from users
            WHERE user = ?
            LIMIT 1
            ", array($f_user)
        );

        if (!(@odbc_fetch_row($result)))
            throw new Exception("User '$f_user' don't exist.");

        if (hash("sha256", $f_pass, false) != odbc_result($result, "pwhash"))
            throw new Exception("Authentication failed"); // ah ah ah, you didn't say the magic word...

        $_SESSION["user"] = $f_user;
        $_SESSION["logged"] = true;
        $_SESSION["pwhash"] = odbc_result($result, "pwhash");
        $_SESSION["fullname"] = odbc_result($result, "fullname");
        $_SESSION["vbox_context"] = odbc_result($result, "vbox_context");
        $_SESSION["vbox_user"] = odbc_result($result, "vbox_user");
        $_SESSION["dial_string"] = odbc_result($result, "dial_string");
        $_SESSION["did"] = odbc_result($result, "did");
        $_SESSION["extension"] = odbc_result($result, "extension");

        $pgroups = strtolower(odbc_result($result, "pgroups"));
        $_SESSION["pgroups"] = array_map("trim", explode(",", $pgroups));

        /* Read user configuration data from user_config table */
        load_user_config();

        header("Location: index.php");
        exit();
    }

} catch (Exception $e) {
    $error_msg = $e->getmessage();
}

/* Display page template */
$template = new TemplateEngine(null);
require($template->load("login.tpl", true, true));
