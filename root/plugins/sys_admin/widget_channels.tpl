<dialog type="widget">
    <title>Channels</title>
    <grid class="expand" data-type="dict" data-source="channels" min-rows=5>
        <header>
            <column id="col_cstatus_channel">Channel</column>
            <column id="col_cstatus_from">From</column>
            <column id="col_cstatus_to">To</column>
            <column id="col_cstatus_duration">Duration</column>
        </header>

        <row>
            <column>[[channel]]</column>
            <column>[[calleridnum | format_phone]] ([[calleridname | lower | ucwords]]</column>
            <column>[[dnid | format_phone]]</column>
            <column>[[seconds | format_time_seconds]]</column>
        </row>
    </grid>
</div>
