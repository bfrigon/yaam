<div class="box form">
    <h1>Edit user profile</h1>
    <form id="frm_edit_user"  method="post">

        <field name="user" type="view" caption="Username" value="[[$user_id]]" />
        <field name="fullname" type="text" caption="Full name" value="[[$user_data@fullname]]" />
        <field name="pgroups" type="text" caption="Permissions" value="[[$user_data@pgroups]]" />

        <h2>Call settings</h2>
        <field name="extension" type="text" caption="Extension" value="[[$user_data@extension]]">
            <help>Extension number of the user in the dialplan (e.g. 100)</help>
        </field>

        <field name="user_chan" type="text" caption="Channel" tip="e.g. SIP/user100" value="[[$user_data@user_chan]]">
            <help>Device associated with the user's phone. (e.g. SIP/user-100)</help>
        </field>


        <h2>Voicemail</h2>
        <field name="vbox_context" type="text" caption="Voicemail context" value="[[$user_data@vbox_context]]">
            <help>Voicemail box context the user belongs to. (e.g. local)</help>
        </field>

        <field name="vbox_user" type="text" caption="Voicemail user" value="[[$user_data@vbox_user]]">
            <help>Voicemail box extension of the user. (e.g. 100).</help>
        </field>

        <h2>Change password</h2>
        <field name="password" type="text" caption="Password" value="" />

        <toolbar class="center v_spacing">
            <item type="submit" name="submit" icon="save">Save</item>
            <item type="button" action="cancel" icon="cancel">Cancel</item>
        </toolbar>
    </form>
</div>
