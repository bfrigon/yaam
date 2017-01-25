<form id="cnam_frm">
    <toolbar id="cnam_toolbar">

        <item type="label">Find :</item>
        <item type="text" name="s" width="150px" title="Filter CNAM records by description"></item>

        <item type="submit" icon="search" title="Search CNAM records"></item>
        <item type="button" action="clear" icon="clear" title="Clear search query"></item>

        <item type="separator" />

        <item type="button" action="add" icon="add" title="Add a new CNAM record">New</item>
        <item type="submit" action="delete" icon="delete" title="Delete selected CNAM record(s)">Delete</item>

        <item type="separator" />

        <!-- Navigation buttons -->
        <item type="button" action="first-page" icon="first" title="Goto first page" />
        <item type="button" action="prev-page" icon="prev" title="Goto previous page" />
        <item type="page-list" prefix="Page " range="5" />
        <item type="button" action="next-page" icon="next" title="Goto next page" />
        <item type="button" action="last-page" icon="last" title="Goto last page" />
    </toolbar>


    <datagrid class="expand" id="ct_grid" data-type="odbc" data-source="results" min-rows="15">
        <header>
            <column style="width: 16px"><input type="checkbox" id="select-all" /></column>
            <column style="width: 220px">Description</column>
            <column style="width: 120px">Number</column>
            <column style="width: 160px">Caller ID</column>
            <column style="width: 60px" type="actions"></column>
        </header>

        <row>
            <column><input type="checkbox" name="id[]" value="[[id]]" /></column>
            <column>[[description]]</column>
            <column>[[number]]</column>
            <column>[[callerid]]</column>
            <column type="actions">
                <icon action="edit" icon="edit" title="Edit CNAM record" params="id=[[id]]" />
                <icon action="delete" icon="delete" title="Delete CNAM record" params="id=[[id]]" />
            </column>
        </row>
    </datagrid>
</form>

<p class="v_spacing">
    <var name="num_results" format="%d CNAM record(s) found. "><if-empty>No CNAM records found!</if-empty></var><br />
    <var name="current_page, total_pages" format="Page %d of %d" />
</p>
