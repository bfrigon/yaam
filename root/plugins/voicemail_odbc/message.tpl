<div class="box dialog">
    <h1>Message</h1>
    <field name="mailbox" type="view" caption="Mailbox" value="[[$mailbox]]" />
    <field name="date" type="view" caption="Date" value="[[$msgdate | format_unix_time]]" />
    <field name="from_name" type="view" caption="From" value="[[$caller_name | lower | ucwords]]" />
    <field name="from_num" type="view" caption="Number" value="[[$caller_number]]" />
    <field name="duration" type="view" caption="Duration" value="[[$duration | format_seconds]]" />
    <field name="size" type="view" caption="Size" value="[[$msg_size | format_byte]]" />

    <toolbar>
        <item type="button" href="[[$msg_url]]" caption="Download" icon="download" >Download</item>
        <item type="separator" />

    </toolbar>

    <toolbar class="center v_spacing">
        <item type="button" action="cancel" icon="left">Back</item>
    </toolbar>
</div>
