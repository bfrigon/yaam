<form id="phonebook_frm">
    <toolbar id="phonebook_toolbar">

        <item type="label">Find :</item>
        <item type="text" name="s" width="150px" title="Filter phone book records by description"></item>

        <item type="submit" icon="search" title="Search phone book records"></item>
        <item type="button" action="clear" icon="clear" title="Clear search query"></item>

        <if type="perm" value="phonebook_write">
            <item type="separator" />
            <item type="button" action="add" icon="add" title="Add a new phone number">New</item>
            <item type="submit" action="delete" icon="delete" title="Delete selected phone number(s)">Delete</item>
        </if>

        <!-- Navigation buttons -->
        <item type="separator" />
        <item type="button" action="first-page" icon="first" title="Goto first page" />
        <item type="button" action="prev-page" icon="prev" title="Goto previous page" />
        <item type="page-list" prefix="Page " range="5" />
        <item type="button" action="next-page" icon="next" title="Goto next page" />
        <item type="button" action="last-page" icon="last" title="Goto last page" />
    </toolbar>


    <datagrid class="expand" id="ct_grid" data-type="odbc" data-source="results" min-rows="15">
        <header>
            <column style="width: 16px"><input type="checkbox" id="select-all" /></column>
            <column if="perm" if-value="phonebook_all_users" style="width: 80px">Owner (ext.)</column>
            <column style="width: 140px">Name</column>
            <column style="width: 120px">Number</column>
            <column style="width: 240px">Notes</column>
            <column style="width: 100px" type="actions"></column>
        </header>

        <row>
            <column><input type="checkbox" name="id[]" value="[[id]]" /></column>
            <column if="perm" if-value="phonebook_all_users">[[extension]]</column>
            <column>[[name]]</column>
            <column>[[number]]</column>
            <column>[[notes]]</column>
            <column type="actions">
                <if type="perm" value="phonebook_write">
                    <icon action="edit" icon="edit" title="Edit phone number" params="id=[[id]]" />
                    <icon action="delete" icon="delete" title="Delete phone number" params="id=[[id]]" />
                </if>
                <action-list type="icon" name="phone_number_tools">
                    <param name="number" value="number" />
                </action-list>
            </column>
        </row>
    </datagrid>
</form>

<p class="v_spacing">
    <var name="num_results" format="%d phone number(s) found. "><if-empty>No phone numbers found!</if-empty></var><br />
    <var name="current_page, total_pages" format="Page %d of %d" />
</p>
