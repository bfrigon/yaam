<toolbar id="filters">
    <item type="label">Log file :</item>
    <item type="list" icon="move" data-type="dict" data-source="log_list">
        <caption>[[$log_basename]]</caption>
        <row>
            <item type="button" params="file=[[value]]">[[value]]</item>
        </row>
    </item>
    <item type="separator" />

    <item type="button" icon="reload" action="refresh">Refresh</item>
</toolbar>

<div class="box scroll" id="log_container">
    <div id="log_content">
        [[$log_filename | dumpfile]]
    </div>
</div>

