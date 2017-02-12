<form id="frm_users">
    <toolbar id="toolbar_users">
        <item type="label">Find :</item>
        <item id="txt_users_search" type="text" name="s" title="Search user"></item>

        <item type="submit" icon="search" title="Search user"></item>
        <item type="button" action="clear" icon="clear" title="Clear search query"></item>

        <if type="perm" value="user_write">
            <item type="separator" />
            <item type="button" action="add" icon="useradd" title="Add a new user">New</item>
            <item type="submit" action="delete" icon="userdelete" title="Delete selected user(s)">Delete</item>
        </if>

        <!-- Navigation buttons -->
        <item type="separator"></item>
        <item type="button" action="first-page" icon="first" title="Goto first page" />
        <item type="button" action="prev-page" icon="prev" title="Goto previous page" />
        <item type="page-list" prefix="Page " range="5" />
        <item type="button" action="next-page" icon="next" title="Goto next page" />
        <item type="button" action="last-page" icon="last" title="Goto last page" />
    </toolbar>

    <datagrid class="expand" id="users" data-type="odbc" data-source="results">
        <header>
            <column id="col_users_check"><input type="checkbox" id="select-all" /></column>
            <column id="col_users_username">Username</column>
            <column id="col_users_fullname">Full name</column>
            <column id="col_users_ext">Extension</column>
            <column id="col_users_did">DID</column>
            <column id="col_users_vbox">Voicemail box</column>
            <column id="col_users_actions" type="actions"></column>
        </header>

        <row>
            <column><input type="checkbox" name="user[]" value="[[user]]" /></column>
            <column>[[user]]</column>
            <column>[[fullname]]</column>
            <column>[[extension]]</column>
            <column>[[did | format_phone]]</column>
            <column>[[vbox_context]] @ [[vbox_user]]</column>
            <column type="actions">
                <if type="perm" value="user_write">
                    <icon action="edit" icon="edit" title="Edit user profile" params="user=[[user]]" />
                    <icon action="delete" icon="delete" title="Delete user" params="user=[[user]]" />
                </if>
                <if type="perm" not value="user_write">
                    <icon action="view" icon="folder" title="View user profile" params="user=[[user]]" />
                </if>
            </column>
        </row>

        <if-empty>No results found</if-empty>
    </datagrid>
</form>

<p class="v_spacing">
    <var name="num_results" format="%d user(s) found."><if-empty>No user found.</if-empty></var>
    <var name="current_page, total_pages" format="Page %d of %d"></var>
</p>
