<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
<head>
    <title>Asterisk Manager</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">

    <link id="css_theme" rel="stylesheet" type="text/css" href="themes/[[$_SESSION@ui_theme]]/theme.css?v=[[#YAAM_VERSION]]" />
    <script type="text/javascript" src="include/js/jquery-env.min.js?v=[[#YAAM_VERSION]]"></script>
    <script type="text/javascript" src="include/js/jquery-custom-ui-dialog.js?v=[[#YAAM_VERSION]]"></script>
</head>

<body>
    <a name="top"></a>

    <div id="main">
        <div class="header">
            <callback name="show_tabs" params="" return="page_class,selected_path,selected_tab" />
        </div>

        <div id="userinfo">
            Logged as: [[$_SESSION@user]]&nbsp;
            (<span id="userinfo_fullname">[[$_SESSION@fullname]]</span>)&nbsp;-&nbsp;
            <icon href="?path=tools.tools.profile" icon="edit" icon-size="12">Edit profile</icon> |
            <icon href="login.php?logout=true" icon="right" icon-size="12">Logout</icon>
        </div>
        <div class="clear"></div>

        <div class="page [[$page_class]]" id="tab_[[$selected_tab]]">

            <callback name="show_tab_content" params="[[$selected_path]]" return="error_msg" />

            <if not type="empty" name="error_msg">
                <div class="box dialog error">
                    [[error_msg]]
                </div>
            </if>

        </div>
    </div>
    <div class="footer">
        Y.A.A.M (v[[#YAAM_VERSION]])
        <p class="copyright">Execution time : <span id="exec_time"><var name="DEBUG_EXEC_TIME" format="%0.1f ms" /></span><p>
        <p class="copyright">[[$DEBUGINFO_TEMPLATE_ENGINE]]</p>
        </p>
    </div>
</body>
</html>
