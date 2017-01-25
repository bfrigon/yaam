<form id="frm_users">
    <toolbar>
        <item type="label">Find :</item>
        <item type="text" name="s"  width="170px"title="Search user"></item>

        <item type="submit" icon="search" title="Search user"></item>
        <item type="button" action="clear" icon="clear" title="Clear search query"></item>

        <item type="separator" />

        <item type="button" action="add" icon="useradd" title="Add a new user">New</item>
        <item type="submit" action="delete" icon="userdelete" title="Delete selected user(s)">Delete</item>

        <item type="separator"></item>

        <!-- Navigation buttons -->
        <item type="button" action="first-page" icon="first" title="Goto first page" />
        <item type="button" action="prev-page" icon="prev" title="Goto previous page" />
        <item type="page-list" prefix="Page " range="5" />
        <item type="button" action="next-page" icon="next" title="Goto next page" />
        <item type="button" action="last-page" icon="last" title="Goto last page" />
    </toolbar>

    <datagrid class="expand" id="users" data-type="odbc" data-source="results">
        <header>
            <column style="width: 16px"><input type="checkbox" id="select-all" /></column>
            <column style="width: 100px">Username</column>
            <column style="width: 160px">Full name</column>
            <column style="width: 80px">Extension</column>
            <column style="width: 110px">DID</column>
            <column style="width: 110px">Voicemail box</column>
            <column style="width: 130px">Permissions</column>
            <column style="width: 80px" type="actions"></column>
        </header>

        <row>
            <column><input type="checkbox" name="user[]" value="[[user]]" /></column>
            <column>[[user]]</column>
            <column>[[fullname]]</column>
            <column>[[extension]]</column>
            <column>[[did | format_phone]]</column>
            <column>[[vbox_context]] @ [[vbox_user]]</column>
            <column>[[pgroups]]</column>
            <column type="actions">
                <icon action="edit" icon="edit" title="Edit user profile" params="user=[[user]]" />
                <icon action="delete" icon="delete" title="Delete user" params="user=[[user]]" />
            </column>
        </row>

        <if-empty>No results found</if-empty>
    </datagrid>
</form>

<p class="v_spacing">
    <var name="num_results" format="%d user(s) found."><if-empty>No user found.</if-empty></var>
    <var name="current_page, total_pages" format="Page %d of %d"></var>
</p>
