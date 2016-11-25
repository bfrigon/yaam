<div class="box form">
    <if type="is" name="action" value="add"><h1>Add call route</h1></if>
    <if type="is" name="action" value="edit"><h1>Edit call route</h1></if>

    <form id="frm_add_crte" method="post">

        <field name="name" type="text" caption="Route name" value="[[$rte_data@name]]" />
        <field name="type" type="text" caption="Route type" value="[[$rte_data@type]]" />

        <h2>Billing</h2>
        <field name="cost" type="text" caption="Cost" value="[[$rte_data@cost]]" />
        <field name="min" type="text" caption="Minimum" value="[[$rte_data@min]]" />
        <field name="increment" type="text" caption="Increment" value="[[$rte_data@increment]]" />

        <h2>CDR field match rules</h2>
        <field name="priority" type="text" caption="Match priority" value="[[$rte_data@priority]]" />
        <field name="channel" type="text" caption="From channel" value="[[$rte_data@channel]]" />
        <field name="src" type="text" caption="From number" value="[[$rte_data@src]]" />
        <field name="dcontext" type="text" caption="Dest. context" value="[[$rte_data@dcontext]]" />
        <field name="dstchannel" type="text" caption="Dest. channel" value="[[$rte_data@dstchannel]]" />
        <field name="dst" type="text" caption="Dest. number" value="[[$rte_data@dst]]" />

        <toolbar class="center v_spacing">
            <item type="submit" name="submit" icon="save">Save</item>
            <item type="button" action="cancel" icon="cancel">Cancel</item>
        </toolbar>
    </form>
</div>
