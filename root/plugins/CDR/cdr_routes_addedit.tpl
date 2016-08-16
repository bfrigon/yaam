<div class="box form">
    <h1>Add call route</h1>
    <form id="frm_add_crte" method="post">

        <field name="name" type="textbox" caption="Route name" value="[[$rte_data@name]]" />
        <field name="type" type="textbox" caption="Route type" value="[[$rte_data@type]]" />

        <h2>Billing</h2>
        <field name="cost" type="textbox" caption="Cost" value="[[$rte_data@cost]]" />
        <field name="min" type="textbox" caption="Minimum" value="[[$rte_data@min]]" />
        <field name="increment" type="textbox" caption="Increment" value="[[$rte_data@increment]]" />

        <h2>CDR field match rules</h2>
        <field name="priority" type="textbox" caption="Match priority" value="[[$rte_data@priority]]" />
        <field name="channel" type="textbox" caption="From channel" value="[[$rte_data@channel]]" />
        <field name="src" type="textbox" caption="From number" value="[[$rte_data@src]]" />
        <field name="dcontext" type="textbox" caption="Dest. context" value="[[$rte_data@dcontext]]" />
        <field name="dstchannel" type="textbox" caption="Dest. channel" value="[[$rte_data@dstchannel]]" />
        <field name="dst" type="textbox" caption="Dest. number" value="[[$rte_data@dst]]" />

        <toolbar class="center v_spacing">
            <item type="submit" icon="save">Save</item>
            <item type="button" action="cancel" icon="cancel">Cancel</item>
        </toolbar>
    </form>
</div>
