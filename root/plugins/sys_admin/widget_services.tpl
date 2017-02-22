<dialog type="widget">
    <title>Services</title>

    <foreach data-type="dict" data-source="services">
        <field class="center" type="progress" critical="value@stopped" caption="[[key]]">[[value@state]]</field>
    </foreach>
</dialog>
