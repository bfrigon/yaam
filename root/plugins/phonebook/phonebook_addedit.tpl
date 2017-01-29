<div class="box dialog">
    <if type="is" name="action" value="add"><h1>Add phone number</h1></if>
    <if type="is" name="action" value="edit"><h1>Edit phone number</h1></if>

    <form id="frm_addedit_phone" method="post">

        <if type="perm" value="phonebook_all_users">
            <field name="extension" type="text" caption="Owner (extension)" value="[[$pb_data@extension]]" />
        </if>

        <field name="number" type="text" caption="Number" value="[[$pb_data@number]]" />
        <field name="name" type="text" caption="Name" value="[[$pb_data@name]]" />
        <field name="notes" type="text" caption="Notes" value="[[$pb_data@notes]]" />

        <toolbar class="center v_spacing">
            <item type="submit" name="submit" icon="save">Save</item>
            <item type="button" action="cancel" icon="cancel">Cancel</item>
        </toolbar>
    </form>
</div>
