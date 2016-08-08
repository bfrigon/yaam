<form id="frm_users" method="post">
    <toolbar>
        <item type="label">Find :</item>
        <item type="textbox" name="s"  width="170px"title="Search user"></item>

        <item type="submit" icon="search" title="Search user"></item>
        <item type="button" action="clear" icon="clear" title="Clear search query"></item>

        <item type="separator" />

        <item type="list" icon="unchecked">
            <item type="button" action="select-all">All</item>
            <item type="button" action="select-none">None</item>
        </item>

        <item type="button" action="add" icon="add" title="Add a new user">New</item>
        <item type="button" action="delete" icon="delete" title="Delete selected user(s)">Delete</item>

        <item type="separator"></item>

        <!-- Navigation buttons -->
        <item type="button" action="first-page" icon="first" title="Goto first page" />
        <item type="button" action="prev-page" icon="prev" title="Goto previous page" />
        <item type="page-list" prefix="Page " range="5" />
        <item type="button" action="next-page" icon="next" title="Goto next page" />
        <item type="button" action="last-page" icon="last" title="Goto last page" />
    </toolbar>

    <datagrid id="users" data-type="odbc" data-source="results">
        <header>
            <column style="width: 16px" type="checkbox"></column>
            <column style="width: 100px">Username</column>
            <column style="width: 160px">Full name</column>
            <column style="width: 80px">Extension</column>
            <column style="width: 110px">Device</column>
            <column style="width: 110px">Voicemail box</column>
            <column style="width: 130px">Permissions</column>
            <column style="width: 80px" type="actions"></column>
        </header>

        <row>
            <column><input type="checkbox" name="user_[[user]]"/></column>
            <column>[[user]]</column>
            <column>[[fullname]]</column>
            <column>[[extension]]</column>
            <column>[[user_chan]]</column>
            <column>[[vbox]]</column>
            <column>[[pgroups]]</column>
            <column type="actions">
                <icon action="edit" icon="edit" title="Edit user profile" params="id=[[user]]" />
                <icon action="delete" icon="delete" title="Delete user" params="id=[[user]]" />
            </column>
        </row>

        <if-empty>No results found</if-empty>
    </datagrid>
</form>

<p class="v_spacing">
    <var name="num_results" format="%d user(s) found."><if-empty>No user found.</if-empty></var>
    <var name="current_page, total_pages" format="Page %d of %d"></var>
</p>
