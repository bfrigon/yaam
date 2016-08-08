<div class="box form">
    <h1>Edit user profile</h1>
    <form id="frm_edit_user" action="" method="post">

        <field name="user" type="view" caption="Username" value="[[$user_data@user]]" />
        <field name="fullname" type="textbox" caption="Full name" value="[[$user_data@fullname]]" />
        <field name="pgroups" type="textbox" caption="Permissions" value="[[$user_data@pgroups]]" />
        <field name="ui_theme" type="textbox" caption="UI theme" value="[[$user_data@ui_theme]]" />

        <h2>Call settings</h2>
        <field name="extension" type="textbox" caption="Extension" value="[[$user_data@extension]]">
            <help>Extension number of the user in the dialplan (e.g. 100)</help>
        </field>

        <field name="user_chan" type="textbox" caption="Channel" tip="e.g. SIP/user100" value="[[$user_data@user_chan]]">
            <help>Device associated with the user's phone. (e.g. SIP/user-100)</help>
        </field>

        <field name="vbox" type="textbox" caption="Voicemail box" value="[[$user_data@vbox]]">
            <help>Path to the user's voicemail box in <strong>/var/spool/asterisk/voicemail</strong></help>
        </field>

        <h2>Change password</h2>
        <field name="password" type="textbox" caption="Password" value="" />

        <toolbar class="center v_spacing">
            <item type="submit" icon="save">Save</item>
            <item type="button" action="cancel" icon="cancel">Cancel</item>
        </toolbar>
    </form>
</div>
