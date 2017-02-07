<toolbar id="filters">
    <item type="label">Log file :</item>
    <item type="list" width="250px" icon="move" data-type="array" data-source="log_list">
        <caption>[[$log_file]]</caption>
        <row>
            <item type="button" params="file=[[value]]">[[value]]</item>
        </row>
    </item>
    <item type="separator" />

    <item type="button" icon="reload" action="refresh">Refresh</item>
</toolbar>

<div class="box scroll viewer">
    <div class="content">
        [[$log_filename | dumpfile]]
    </div>
</div>
