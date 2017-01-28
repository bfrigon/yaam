<form id="ct_frm">
    <toolbar id="ct_toolbar">

        <item type="label">Find :</item>
        <item type="text" name="s" width="150px" title="Filter call treatments by description"></item>

        <item type="submit" icon="search" title="Search call treatment"></item>
        <item type="button" action="clear" icon="clear" title="Clear search query"></item>

        <if type="perm" value="ct_write_rules">
            <item type="separator" />
            <item type="button" action="add" icon="add" title="Add a new call treatment rule">New</item>
            <item type="submit" action="delete" icon="delete" title="Delete selected call treatment rule(s)">Delete</item>
        </if>

        <!-- Navigation buttons -->
        <item type="separator" />
        <item type="button" action="first-page" icon="first" title="Goto first page" />
        <item type="button" action="prev-page" icon="prev" title="Goto previous page" />
        <item type="page-list" prefix="Page " range="5" />
        <item type="button" action="next-page" icon="next" title="Goto next page" />
        <item type="button" action="last-page" icon="last" title="Goto last page" />
    </toolbar>


    <datagrid id="ct_grid" data-type="odbc" data-source="results" min-rows="15">
        <header>
            <column style="width: 16px"><input type="checkbox" id="select-all" /></column>
            <column if="perm" if-value="ct_rules_all_users" style="width: 80px">Ext.</column>
            <column style="width: 220px">Description</column>
            <column style="width: 160px">Action</column>
            <column style="width: 120px">Caller number</column>
            <column style="width: 160px">Caller name</column>

            <column style="width: 50px" type="actions"></column>
        </header>

        <row>
            <column><input type="checkbox" name="id[]" value="[[id]]" /></column>
            <column if="perm" if-value="ct_rules_all_users">[[extension]]</column>
            <column>[[description]]</column>

            <column>
                <callback name="get_action_desc" params="[[action]]" return="action_desc" />
                [[$action_desc]]
            </column>

            <column>[[caller_num]]</column>
            <column>[[caller_name]]</column>

            <column type="actions">
                <if type="perm" value="ct_write_rules">
                    <icon action="edit" icon="edit" title="Edit rule" params="id=[[id]]" />
                    <icon action="delete" icon="delete" title="Delete rule" params="id=[[id]]" />
                </if>
            </column>
        </row>
    </datagrid>
</form>

<p class="v_spacing">
    <var name="num_results" format="%d rule(s) found. "><if-empty>No rules found!</if-empty></var><br />
    <var name="current_page, total_pages" format="Page %d of %d" />
</p>
