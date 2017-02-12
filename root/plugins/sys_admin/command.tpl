<form method="post">
    <toolbar id="toolbar_command">
        <item type="label">Command :</item>
        <item id="txt_command" type="text" name="command" value="[[$command]]" />
        <item type="submit" name="exec" icon="reload" action="refresh">Execute</item>
    </toolbar>
</form>

<div id="viewer_command_result" class="box scroll viewer code">
    <div class="content">[[$cmd_result]]</div>
</div>
