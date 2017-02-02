<if type="isset" name="ajax_url">
    <iframe class="box dialog message" src="[[$ajax_url]]"></iframe>
</if>

<div class="box dialog">
    <h1>Originate call</h1>
    <form id="frm_originate" method="post">

        <if type="perm" value="originate_from_other_ext">
            <field name="ext" type="text" caption="From (Ext.)" placeholder="(e.g. 100)" value="[[$ext]]" />
        </if>
        <field name="number" type="text" caption="Dial" value="[[$number]]" />

        <h2>Caller ID override</h2>
        <field name="caller_num" type="text" caption="Number" placeholder="Default" value="[[$caller_num]]" />
        <field name="caller_name" type="text" caption="Name" placeholder="Default" value="[[$caller_name]]" />

        <toolbar class="center v_spacing">
            <item type="submit" icon="call">Call</item>
            <item type="button" action="cancel" icon="cancel">Cancel</item>
        </toolbar>
    </form>
</div>


