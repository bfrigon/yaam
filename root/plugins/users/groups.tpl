<form id="frm_groups">
    <toolbar id="toolbar_groups">
        <item type="label">Find :</item>
        <item id="txt_groups_search" type="text" name="s" title="Search permissions group"></item>

        <item type="submit" icon="search" title="Search permissions group"></item>
        <item type="button" action="clear" icon="clear" title="Clear search query"></item>

        <if type="perm" value="group_write">
            <item type="separator" />
            <item type="button" action="add" icon="add" title="Add a new permissions group">New</item>
            <item type="submit" action="delete" icon="delete" title="Delete selected permissions group(s)">Delete</item>
        </if>

        <!-- Navigation buttons -->
        <item type="separator"></item>
        <item type="button" action="first-page" icon="first" title="Goto first page" />
        <item type="button" action="prev-page" icon="prev" title="Goto previous page" />
        <item type="page-list" prefix="Page " range="5" />
        <item type="button" action="next-page" icon="next" title="Goto next page" />
        <item type="button" action="last-page" icon="last" title="Goto last page" />
    </toolbar>

    <datagrid class="expand" id="groups" data-type="odbc" data-source="results">
        <header>
            <column id="col_group_check" type="select"></column>
            <column id="col_group_name">Group name</column>
            <column id="col_group_description">Description</column>
            <column id="col_group_actions" type="actions"></column>
        </header>

        <row>
            <column type="select" name="group" value="[[id]]"></column>
            <column>[[name]]</column>
            <column>[[description]]</column>
            <column type="actions">
                <if type="perm" value="group_write">
                    <icon action="edit" icon="edit" title="Edit permissions group" params="group=[[id]]" />
                    <icon action="delete" icon="delete" title="Delete user" params="group=[[id]]" />
                </if>
                <if type="perm" not value="group_write">
                    <icon action="view" icon="folder" title="View permissions group" params="group=[[id]]" />
                </if>
            </column>
        </row>

        <if-empty>No results found</if-empty>
    </datagrid>
</form>

<p>
    <var name="num_results" format="%d permissions group(s) found."><if-empty>No permissions group found.</if-empty></var>
    <var name="current_page, total_pages" format="Page %d of %d"></var>
</p>
