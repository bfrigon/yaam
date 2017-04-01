<dialog type="widget">
    <title>Channels</title>
    <grid class="expand" data-type="array" data-source="channels" min-rows=5>
        <header>
            <column id="col_cstatus_channel">Channel</column>
            <column id="col_cstatus_from">From</column>
            <column id="col_cstatus_to">To</column>
            <column id="col_cstatus_duration">Duration</column>
        </header>

        <row>
            <column>[[ row.channel ]]</column>
            <column>[[ row.calleridnum | format_phone]] ([[ row.calleridname | lower | ucwords ]]</column>
            <column>[[ row.dnid | format_phone ]]</column>
            <column>[[ row.seconds | format_time_seconds ]]</column>
        </row>
        <if-empty class="center">No active channels</if-empty>
    </grid>
</div>
