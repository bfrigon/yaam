<div class="box form">
    <h1>Add user</h1>
    <form id="frm_add_user" method="post">

        <field name="user" type="text" caption="Username" value="[[$user_data@user]]" />
        <field name="fullname" type="text" caption="Full name" value="[[$user_data@fullname]]" />
        <field name="pgroups" type="text" caption="Permissions" value="[[$user_data@pgroups]]" />

        <h2>Call settings</h2>
        <field name="extension" type="text" caption="Extension" tip="e.g. 100" value="[[$user_data@extension]]" />
        <field name="user_chan" type="text" caption="Channel" tip="e.g. SIP/user100" value="[[$user_data@user_chan]]" />
        <field name="vbox" type="text" caption="Voicemail box" value="[[$user_data@vbox]]" />

        <h2>Password</h2>
        <field name="password" type="text" caption="Password" value="[[$user_data@password]]" />

        <toolbar class="center v_spacing">
            <item type="submit" icon="save">Save</item>
            <item type="button" action="cancel" icon="cancel">Cancel</item>
        </toolbar>
    </form>
</div>
