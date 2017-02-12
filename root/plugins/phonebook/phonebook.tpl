<form id="phonebook_frm">
    <toolbar id="phonebook_toolbar">

        <item type="label">Find :</item>
        <item id="txt_phonebook_search" type="text" name="s" title="Filter phone book records by description"></item>

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
            <column id="col_phonebook_sel" type="select"></column>
            <column id="col_phonebook_speed_dial">Speed dial</column>
            <column id="col_phonebook_owner" if="perm" if-value="phonebook_all_users">Owner (ext.)</column>
            <column id="col_phonebook_name">Name</column>
            <column id="col_phonebook_number">Number</column>
            <column id="col_phonebook_notes">Notes</column>
            <column id="col_phonebook_actions" type="actions"></column>
        </header>

        <row>
            <column type="select" name="id" value="[[id]]"></column>

            <column>
                <if not type="empty" name="speed_dial">
                    [[$speed_dial_prefix]][[speed_dial]]
                    <icon if="empty" if-name="extension" icon="globe" />
                </if>
            </column>

            <column if="perm" if-value="phonebook_all_users">
                <if type="empty" name="extension">Global</if>
                [[extension]]
            </column>

            <column>[[name]]</column>

            <column>[[number | format_phone]]</column>

            <column>[[notes]]</column>

            <column type="actions">
                <if type="function" name="is_editable" value="[[extension]]">
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
