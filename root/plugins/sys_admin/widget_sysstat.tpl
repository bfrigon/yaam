<dialog type="widget">
    <title>System</title>

    <field type="progress" caption="Server time">[[server_time]]</field>
    <field type="progress" caption="Uptime">[[system_uptime]]</field>
    <field type="progress" caption="Avg. Load">[[system_load]]</field>

    <section title="Memory">
        <field type="progress" caption="Applications">[[$meminfo.mem_used | format_byte]]</field>
        <field type="progress" caption="Cache/Buffers">[[$meminfo.total_cb | format_byte]]</field>
        <field type="progress" caption="Free (- C/B)" class="[[$meminfo.free_critical || if:critical:normal]]" >[[$meminfo.free | format_byte]]</field>
        <field type="progress" caption="Total (+ C/B)" class="[[$meminfo.free_critical || if:critical:normal]]" value="$meminfo.perc_used" max="100">[[$meminfo.used | format_byte]] / [[$meminfo.total | format_byte]]</field>
        <field type="progress" caption="Swap" class="[[$meminfo.swap_critical | if:critical:normal]]" value="$meminfo.perc_swap_used" max="100">[[$meminfo.swap_used | format_byte]] / [[$meminfo.swap_total | format_byte]]</field>
    </section>

    <section title="Disks">
        <foreach data-type="array" data-source="disk_info">
            <field type="progress" class="[[ row.class ]]" value="row.perc" max="100" caption="[[ key ]]">[[ row.used | format_byte ]] / [[ row.total | format_byte ]]</field>
        </foreach>
    </section>

    <section title="Network">
        <foreach data-type="array" data-source="network_info">
            <field type="progress" caption="[[key]]">Down: [[ row.down | format_byte ]] / Up: [[ row.up | format_byte ]]</field>
        </foreach>
    </section>
</dialog>
