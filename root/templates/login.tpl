<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
<head>
    <title>Asterisk Manager</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">

    <link rel="stylesheet" type="text/css" href="themes/default/theme.css?v=[[#YAAM_VERSION]]" />
</head>

<body>
    <div style="margin: 20px auto; text-align: center">
        <img src="images/ast_logo.png" alt="Asterisk"/>
        <p>Y.A.A.M (v[[#YAAM_VERSION]])</p>
    </div>

    <if not type="empty" name="error_msg">
        <dialog type="error">
            <message>[[error_msg]]</message>
        </dialog>
    </if>

    <form id="login" method="post">
        <dialog class="small">
            <field type="text" name="user" caption="Username" value="[[$f_user]]" />
            <field type="password" name="pass" caption="Password" value="" />

            <toolbar class="center">
                <item type="submit" name="login" caption="Login">Login</item>
            </toolbar>
        </dialog>
    </form>
</body>
</html>
