<toolbar id="toolbar_syslog">
    <item type="label">Log file :</item>
    <item type="list" id="dropdown_syslog_list" icon="move" data-type="array" data-source="log_list">
        <caption>[[$log_file]]</caption>
        <row>
            <item type="button" params="file=[[value]]">[[value]]</item>
        </row>
    </item>
    <item type="separator" />

    <item type="button" icon="reload" action="refresh">Refresh</item>
</toolbar>

<div class="box scroll viewer" id="viewer_syslog">
    <div class="content">
        [[$log_filename | dumpfile]]
    </div>
</div>
