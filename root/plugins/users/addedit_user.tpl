<div class="box form">
    <if type="is" name="action" value="add"><h1>Add user</h1></if>
    <if type="is" name="action" value="edit"><h1>Edit user</h1></if>

    <form id="frm_add_user" method="post">
        <if type="is" name="action" value="add"><field name="user" type="text" caption="Username" value="[[$user_data@user]]" /></if>
        <if type="is" name="action" value="edit"><field name="user" type="view" caption="Username" value="[[$user_data@user]]" /></if>

        <field name="fullname" type="text" caption="Full name" value="[[$user_data@fullname]]" />
        <field name="pgroups" type="text" caption="Permissions" value="[[$user_data@pgroups]]" />

        <h2>Account settings</h2>
        <field name="extension" type="text" caption="Extension" value="[[$user_data@extension]]">
            <help>Extension number of the user in the dialplan (e.g. 100)</help>
        </field>

        <field name="dial_string" type="text" caption="Dial string" tip="e.g. SIP/phone100" value="[[$user_data@dial_string]]">
            <help>Dial string used to reach the user's phone. (e.g. SIP/phone100)</help>
        </field>

        <field name="did" type="text" caption="DID number" tip="e.g. 4501231234" value="[[$user_data@did]]">
            <help>The external phone number associated with this extension (e.g. 4501231234)</help>
        </field>


        <h2>Voicemail</h2>
        <field name="vbox_context" type="text" caption="Voicemail context" value="[[$user_data@vbox_context]]">
            <help>Voicemail box context the user belongs to. (e.g. local)</help>
        </field>

        <field name="vbox_user" type="text" caption="Voicemail user" value="[[$user_data@vbox_user]]">
            <help>Voicemail box extension of the user. (e.g. 100).</help>
        </field>

        <h2>Password</h2>
        <field name="password" type="text" caption="Password" value="[[$user_data@password]]" />

        <toolbar class="center v_spacing">
            <item type="submit" name="submit" icon="save">Save</item>
            <item type="button" action="cancel" icon="cancel">Cancel</item>
        </toolbar>
    </form>
</div>
