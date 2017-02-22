<dialog>
    <h1>Message</h1>

    <field name="mailbox" type="view" caption="Mailbox" value="[[$mailbox]]" />
    <field name="date" type="view" caption="Date" value="[[$msgdate | format_unix_time]]" />
    <field name="from_name" type="view" caption="From" value="[[$caller_name | lower | ucwords]]" />
    <field name="from_num" type="view" caption="Number" value="[[$caller_number]]" />
    <field name="duration" type="view" caption="Duration" value="[[$duration | format_seconds]]" />
    <field name="size" type="view" caption="Size" value="[[$msg_size | format_byte]]" />

    <toolbar>
        <item type="view" id="vm_time"><span id="vm_time_cursor">--:--</span> / <span id="vm_time_total">--:--</span></item>
        <item type="separator" />
        <item type="button" id="btn_vm_play" href="#" icon="play" />
        <item type="button" id="btn_vm_stop" href="#" icon="stop" />
        <item type="button" id="btn_vm_rewind" href="#" icon="prev" />
        <item type="button" id="btn_vm_ffwd" href="#" icon="next" />
        <item type="separator" />
        <item type="button" id="btn_vm_vol_down" href="#" icon="vol-down" />
        <item type="button" id="btn_vm_vol_up" href="#" icon="vol-up" />
    </toolbar>

    <toolbar class="center">
        <item type="button" action="cancel" icon="left">Back</item>
        <item type="separator" />
        <item type="button" id="btn_vm_download" href="[[$msg_url]]" title="Download message" icon="download" >Download</item>
        <item type="button" action="delete" title="Delete message" icon="delete" params="id=[[id]]" keep-referrer>  Delete</item>
    </toolbar>
</dialog>

<div id="vm_player"></div>
