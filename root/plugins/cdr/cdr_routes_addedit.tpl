<div class="box dialog">
    <if type="is" name="action" value="add"><h1>Add call route</h1></if>
    <if type="is" name="action" value="edit"><h1>Edit call route</h1></if>

    <form id="frm_addedit_route" method="post">

        <field name="name" type="text" caption="Route name" value="[[$rte_data@name]]" />

        <h2>Billing</h2>
        <field name="cost" type="text" caption="Cost (per minute)" value="[[$rte_data@cost]]" />
        <field name="min" type="text" caption="Minimum duration" value="[[$rte_data@min]]" />
        <field name="increment" type="text" caption="Increment" value="[[$rte_data@increment]]" />

        <toolbar class="center v_spacing">
            <item type="submit" name="submit" icon="save">Save</item>
            <item type="button" action="cancel" icon="cancel">Cancel</item>
        </toolbar>
    </form>
</div>
