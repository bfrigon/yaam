<toolbar id="toolbar_channel_status">
    <!-- Navigation buttons -->
    <item type="button" action="first-page" icon="first" title="Goto first page" />
    <item type="button" action="prev-page" icon="prev" title="Goto previous page" />
    <item type="page-list" prefix="Page " range="5" />
    <item type="button" action="next-page" icon="next" title="Goto next page" />
    <item type="button" action="last-page" icon="last" title="Goto last page" />

    <item type="separator"></item>
    <item type="button" action="refresh" icon="reload" title="Refresh channel status">Refresh</item>
</toolbar>

<grid class="expand" data-type="dict" data-source="channels" min-rows=15>
    <header>
        <column id="col_cstatus_channel">Channel</column>
        <column id="col_cstatus_from">From</column>
        <column id="col_cstatus_name">Name</column>
        <column id="col_cstatus_to">To</column>
        <column id="col_cstatus_duration">Duration</column>
        <column id="col_cstatus_state">State</column>
    </header>

    <row>
        <column>[[channel]]</column>
        <column>[[calleridnum | format_phone]]</column>
        <column>[[calleridname | lower | ucwords]]</column>
        <column>[[dnid | format_phone]]</column>
        <column>[[seconds | format_time_seconds]]</column>
        <column>[[channelstatedesc || ucfirst]]</column>
    </row>

    <if-empty>** No active channels **</if-empty>
</grid>

<p class="v_spacing">
    <var name="num_results" format="%d active channels(s). " if-empty="No active channels." /><br />
    <var name="current_page, total_pages" format="Page %d of %d" />
</p>
