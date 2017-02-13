<form id="frm_addedit_ct" method="post" keep-referrer>
    <dialog>
        <if type="is" name="action" value="add"><h1>Add call treatment rule</h1></if>
        <if type="is" name="action" value="edit"><h1>Edit call treatment rule</h1></if>

        <field name="description" type="text" caption="Description" value="[[$ct_data@description]]" />
        <field name="ct_action" type="select" caption="Action" value="[[$ct_data@action]]" data-type="dict" data-source="action_list" />

        <h2>Match rules</h2>
        <if type="perm" value="ct_rules_all_users">
            <field name="extension" type="text" caption="Extension" value="[[$ct_data@extension]]" />
        </if>

        <field name="caller_num" type="text" caption="Number" placeholder="Number (regex)" value="[[$ct_data@caller_num]]" />
        <field name="caller_name" type="text" caption="Name" placeholder="Name (regex)" value="[[$ct_data@caller_name]]" />

        <toolbar class="center v_spacing">
            <item type="submit" name="submit" icon="save">Save</item>
            <item type="button" action="cancel" icon="cancel">Cancel</item>
        </toolbar>
    </dialog>
</form>
