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

    if (isset($_POST["js_detect"]))
        $_SESSION["js"] = true;

    /* Load configuration */
    $CONFIG = load_global_config();

    /* Connect to the database */
    $DB = new ODBCDatabase($CONFIG["db_dsn"], $CONFIG["db_user"], $CONFIG["db_pass"]);

    /* Check if admin user exists, if not redirect to setup page */
    if ($DB->exec_query_simple("SELECT user FROM users where user=\"admin\"", "user") != "admin") {
        header("Location: setup.php");
        exit();
    }

    $f_user = (isset($_REQUEST["user"]) ? $_REQUEST["user"] : "");
    $f_pass = (isset($_REQUEST["pass"]) ? $_REQUEST["pass"] : "");

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
        $_SESSION["pgroups"] = array_map("trim", explode(",", odbc_result($result, "pgroups")));
        $_SESSION["vbox_context"] = odbc_result($result, "vbox_context");
        $_SESSION["vbox_user"] = odbc_result($result, "vbox_user");
        $_SESSION["user_chan"] = odbc_result($result, "user_chan");



        /* Read user configuration data from user_config table */
        load_user_config();


        header("Location: index.php");
        exit();
    }

} catch (Exception $e) {
    $error_msg = $e->getmessage();
}

/*------------------------------------------------------------------------------------------------*/
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
<head>
    <title>Asterisk Manager</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">

    <link rel="stylesheet" type="text/css" href="themes/default/theme.css" />
    <script type="text/javascript" src="include/js/jquery-env.min.js"></script>


    <script type="text/javascript">
        $(document).ready(function () {
            $("#login").append($("<input>")
                .attr("type", "hidden")
                .attr("name", "js_detect")
                .attr("value", "true"));
        });
    </script>

</head>

<body>
    <div style="margin: 20px auto; text-align: center">
        <img src="images/ast_logo.png" alt="Asterisk"/>
        <p>Y.A.A.M (v<?=YAAM_VERSION?>)</p>
    </div>

    <?php if (!empty($error_msg)) print_message($error_msg, true); ?>

    <div class="box dialog">
        <form id="login" method="post">

        <label for="user">Username</label>
        <input type="text" name="user" value="<?php echo $f_user ?>">
        <div class="clear"><br /></div>

        <label for="pass">Password</label>
        <input type="password" name="pass">
        <div class="clear"><br /></div>

        <div class="toolbar center v_spacing">
            <ul>
                <li><button type="submit">Login</button></li>
            </ul>
        </div>
        <div class="clear"><br /></div>

        </form>
    </div>
</body>
</html>
