<form id="routes_frm">
    <toolbar id="toolbar_routes">

        <item type="label">Find :</item>
        <item id="txt_routes_search" type="text" name="s" title="Filter routes by name"></item>

        <item type="submit" icon="search" title="Search"></item>
        <item type="button" action="clear" icon="clear" title="Clear filters"></item>

        <if type="perm" value="cdr_write_routes">
            <item type="separator" />
            <item type="button" action="add" icon="add" title="Add a call route">New</item>
            <item type="submit" action="delete" icon="delete" title="Delete selected call route(s)">Delete</item>
        </if>

        <!-- Navigation buttons -->
        <item type="separator" />
        <item type="button" action="first-page" icon="first" title="Goto first page" />
        <item type="button" action="prev-page" icon="prev" title="Goto previous page" />
        <item type="page-list" prefix="Page " range="5" />
        <item type="button" action="next-page" icon="next" title="Goto next page" />
        <item type="button" action="last-page" icon="last" title="Goto last page" />
    </toolbar>


    <datagrid class="expand" id="routes_grid" data-type="odbc" data-source="results" min-rows="15">
        <header>
            <column id="col_routes_check"><input class="select-all" type="checkbox" /></column>
            <column id="col_routes_name">Route name</column>
            <column id="col_routes_cost">Cost (per min.)</column>
            <column id="col_routes_min">Min. duration (sec) </column>
            <column id="col_routes_inc">Increment (sec)</column>
            <column id="col_routes_actions" type="actions"></column>
        </header>

        <row>
            <column><input type="checkbox" name="id[]" value="[[id]]" /></column>
            <column>[[name]]</column>
            <column>[[cost|format_money:%.4i]] $</column>
            <column>[[min]]</column>
            <column>[[increment]]</column>
            <column type="actions">
                <if type="perm" value="cdr_write_routes">
                    <icon action="edit" icon="edit" title="Edit call route" params="id=[[id]]" />
                    <icon action="delete" icon="delete" title="Delete call route" params="id=[[id]]" />
                </if>
            </column>
        </row>
    </datagrid>
</form>

<p class="v_spacing">
    <var name="num_results" format="%d route(s) found. "><if-empty>No routes found!</if-empty></var><br />
    <var name="current_page, total_pages" format="Page %d of %d" />
</p>
