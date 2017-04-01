<dialog type="widget">
    <title>Services status</title>

    <foreach data-type="array" data-source="services">
        <field class="center [[ row.class ]]" type="progress" caption="[[ key ]]">[[ row.state ]]</field>
    </foreach>
</dialog>
