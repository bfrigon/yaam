<div class="box form">
    <if type="is" name="action" value="add"><h1>Add CNAM record</h1></if>
    <if type="is" name="action" value="edit"><h1>Edit CNAM record</h1></if>

    <form id="frm_addedit_cnam" method="post">

        <field name="description" type="text" caption="Description" value="[[$cnam_data@description]]" />
        <field name="number" type="text" caption="Caller ID number" value="[[$cnam_data@number]]" />
        <field name="callerid" type="text" caption="Caller ID name" value="[[$cnam_data@callerid]]" />

        <toolbar class="center v_spacing">
            <item type="submit" name="submit" icon="save">Save</item>
            <item type="button" action="cancel" icon="cancel">Cancel</item>
        </toolbar>
    </form>
</div>
