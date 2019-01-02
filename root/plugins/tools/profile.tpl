<form id="frm_edit_profile" method="post">
    <dialog>
        <title>Edit profile - [[$_SESSION.user]]</title>

        <field name="user" type="view" caption="Username" value="[[$_SESSION.user]]" />
        <field name="fullname" type="text" caption="Full name" value="[[$_SESSION.fullname]]" />

        <section title="Options">
            <field name="ui_theme" type="select" caption="Theme" value="[[$_SESSION.ui_theme]]" data-type="array" data-source="list_themes" />
            <field name="date_format" type="select" caption="Date format" value="[[$_SESSION.date_format]]" data-type="array" data-source="list_date_formats" />
        </section>

        <section title="Change password">
            <field name="old_pwd" type="password" caption="Old password" value="" />
            <field name="pwd" type="password" caption="New password" value="" />
            <field name="pwd_check" type="password" caption="Check" value="" />

            <toolbar class="center">
                <item type="submit" icon="save">Save</item>
            </toolbar>
        </section>
    </dialog>
</form>
