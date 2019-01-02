<toolbar id="toolbar_peers_status">
    <!-- Navigation buttons -->
    <item type="button" action="first-page" icon="first" title="Goto first page" />
    <item type="button" action="prev-page" icon="prev" title="Goto previous page" />
    <item type="page-list" prefix="Page " range="5" />
    <item type="button" action="next-page" icon="next" title="Goto next page" />
    <item type="button" action="last-page" icon="last" title="Goto last page" />

    <item type="separator"></item>
    <item type="button" action="refresh" icon="reload" title="Refresh peer status">Refresh</item>
</toolbar>

<grid class="expand" data-type="array" data-source="peers" min-rows=15>
    <header>
        <column id="col_pstatus_name">Name</column>
        <column id="col_pstatus_protocol">Protocol</column>
        <column id="col_pstatus_host">Host</column>
        <column id="col_pstatus_port">Port</column>
        <column id="col_pstatus_status">Status</column>
    </header>

    <row class="[[ row.cssclass ]]">
        <column>[[ row.objectname ]]</column>
        <column>[[ row.channeltype | upper ]]</column>
        <column>[[ row.ipaddress ]]</column>
        <column>[[ row.ipport ]]</column>
        <column>[[ row.status ]]</column>
    </row>

    <if-empty>** No monitored peers **</if-empty>
</grid>

<p>
    <var name="num_results" format="%d monitored peer(s). " if-empty="No monitored peers." /><br />
    <var name="current_page, total_pages" format="Page %d of %d" if-empty="" />
</p>
