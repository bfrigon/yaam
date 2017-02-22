<form id="frm_add_user" method="post" keep-referrer>
    <dialog>
        <if type="is" name="action" value="add"><title>Add user</title></if>
        <if type="is" name="action" value="edit"><title>Edit user</title></if>
        <if type="is" name="action" value="view"><title>View user</title></if>

        <if type="is" name="action" value="add"><field name="user" type="text" caption="Username" value="[[$user_data@user]]" /></if>
        <if type="is" name="action" value="edit"><field name="user" type="view" caption="Username" value="[[$user_data@user]]" /></if>

        <field name="fullname" type="text" caption="Full name" value="[[$user_data@fullname]]" />

        <field name="cid_name" type="text" caption="Caller ID name" value="[[$user_data@cid_name]]">
            <help>This is the caller ID name which is sent for outbound calls (Max. 15 characters)</help>
        </field>

        <if type="perm" value="user_set_permission">
            <field name="pgroups" type="listbox" caption="Permissions" data-type="array" data-source="perm_list" value="$user_data@pgroups" />
        </if>

        <section title="Account settings">
            <field name="extension" type="text" caption="Extension" placeholder="(e.g. 100)" value="[[$user_data@extension]]">
                <help>Extension number of the user in the dialplan (e.g. 100)</help>
            </field>

            <field name="dial_string" type="text" caption="Dial string" placeholder="(e.g. SIP/phone100)" value="[[$user_data@dial_string]]">
                <help>Dial string used to reach the user's phone. (e.g. SIP/phone100)</help>
            </field>

            <field name="did" type="text" caption="DID number" placeholder="(e.g. 4501231234)" value="[[$user_data@did]]">
                <help>The external phone number associated with this extension (e.g. 4501231234)</help>
            </field>
        </section>

        <section title="Voicemail">
            <field name="vbox_context" type="text" caption="Context" placeholder="(e.g. local)" value="[[$user_data@vbox_context]]">
                <help>Voicemail box context the user belongs to. (e.g. local)</help>
            </field>

            <field name="vbox_user" type="text" caption="Mailbox" placeholder="(e.g. 100)" value="[[$user_data@vbox_user]]">
                <help>Voicemail box id of the user. (e.g. 100).</help>
            </field>
        </section>

        <section title="Change password">
            <field name="password" type="text" caption="New password" value="[[$user_data@password]]" />
        </section>

        <toolbar class="center">
            <if type="perm" value="user_write">
                <item type="submit" name="submit" icon="save">Save</item>
                <item type="button" action="cancel" icon="cancel">Cancel</item>
            </if>
            <if type="perm" not value="user_write">
                <item type="button" action="cancel" icon="ok">Ok</item>
            </if>

        </toolbar>
    </dialog>
</form>
