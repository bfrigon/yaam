<div class="box form">
    <if type="is" name="action" value="add"><h1>Add call treatment rule</h1></if>
    <if type="is" name="action" value="edit"><h1>Edit call treatment rule</h1></if>

    <form id="frm_addedit_ct" method="post">

        <field name="description" type="text" caption="Description" value="[[$ct_data@description]]" />
        <field name="ct_action" type="select" caption="Action" value="[[$ct_data@action]]" data-type="dict" data-source="action_list" />

        <h2>Match rules</h2>
        <field name="extension" type="text" caption="Extension" value="[[$ct_data@extension]]" />
        <field name="caller_num" type="text" caption="Caller ID number" value="[[$ct_data@caller_num]]" />
        <field name="caller_name" type="text" caption="Caller ID name" value="[[$ct_data@caller_name]]" />

        <toolbar class="center v_spacing">
            <item type="submit" name="submit" icon="save">Save</item>
            <item type="button" action="cancel" icon="cancel">Cancel</item>
        </toolbar>
    </form>
</div>
