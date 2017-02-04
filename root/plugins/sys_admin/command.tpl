<form method="post">
    <toolbar id="toolbar_command">
        <item type="label">Command :</item>
        <item type="text" name="command" width="250px" value="[[$command]]" />
        <item type="submit" name="exec" icon="reload" action="refresh">Execute</item>
    </toolbar>
</form>

<div class="box scroll viewer code">
    <div class="content">[[$cmd_result]]</div>
</div>
