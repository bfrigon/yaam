<form id="frm_addedit_route" method="post" keep-referrer>
    <dialog>
        <if type="is" name="action" value="add"><title>Add call route</title></if>
        <if type="is" name="action" value="edit"><title>Edit call route</title></if>

        <field name="name" type="text" caption="Route name" value="[[$rte_data@name]]" />

        <section title="Billing">
            <field name="cost" type="text" caption="Cost (per minute)" value="[[$rte_data@cost]]" />
            <field name="min" type="text" caption="Minimum duration" value="[[$rte_data@min]]" />
            <field name="increment" type="text" caption="Increment" value="[[$rte_data@increment]]" />
        </section>

        <toolbar class="center">
            <item type="submit" name="submit" icon="save">Save</item>
            <item type="button" action="cancel" icon="cancel">Cancel</item>
        </toolbar>
    </dialog>
</form>
