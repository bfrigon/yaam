<div class="box form">
    <h1>Edit profile - [[$_SESSION@user]]</h1>
    <form id="frm_edit_profile" method="post">

        <field name="user" type="view" caption="Username" value="[[$_SESSION@user]]" />
        <field name="fullname" type="text" caption="Full name" value="[[$_SESSION@fullname]]" />


        <h2>Options</h2>

        <field name="ui_theme" type="select" caption="Theme" value="[[$_SESSION@ui_theme]]" data-type="dict" data-source="list_themes" />
        <field name="date_format" type="select" caption="Date format" value="[[$_SESSION@date_format]]" data-type="dict" data-source="list_date_formats" />


        <h2>Change password</h2>

        <field name="old_pwd" type="password" caption="Old password" value="" />
        <field name="pwd" type="password" caption="New password" value="" />
        <field name="pwd_check" type="password" caption="Check" value="" />

        <toolbar class="center v_spacing">
            <item type="submit" icon="save">Save</item>
        </toolbar>
    </form>
</div>
