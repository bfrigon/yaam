<form id="frm_originate" method="post">
    <dialog>
        <title>Originate call</title>

        <if type="perm" value="originate_from_other_ext">
            <field name="ext" type="text" caption="From (Ext.)" placeholder="(e.g. 100)" value="[[$ext]]" />
        </if>
        <field name="number" type="text" caption="Dial" value="[[$number]]" />

        <section title="Caller ID override">
            <field name="caller_num" type="text" caption="Number" placeholder="Default" value="[[$caller_num]]" />
            <field name="caller_name" type="text" caption="Name" placeholder="Default" value="[[$caller_name]]" />
        </section>

        <toolbar class="center">
            <item name="call" type="submit" icon="call">Call</item>
            <item id="frm_originate_cancel" name="cancel" type="button" action="cancel" icon="cancel">Cancel</item>
        </toolbar>
    </dialog>
</form>


