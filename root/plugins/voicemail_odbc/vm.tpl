<if type="permission" value="vm-admin">
    <form method="get" keep-uri="folder,test">
        <toolbar>
            <item type="label">Mailbox user :</item>
            <item type="text" name="user" value="[[$vbox_user]]" />

            <item type="label">context :</item>
            <item type="text" name="context" value="[[$vbox_context]]" />
            <item type="separator" />

            <item type="submit" icon="search" />
            <item type="button" icon="clear" action="clear" />

        </toolbar>
    </form>
</if>
<form id="frm_vm" method="post" keep-uri="folder">
    <toolbar>
        <item type="label">Folder :</item>
        <item type="list" width="120px" icon="folder" data-type="dict" data-source="folders">
            <caption>[[$current_folder_name]]</caption>
            <row>
                <item type="button" params="folder=[[key]]" keep-uri>[[value]]</item>
            </row>
        </item>

        <item type="separator" />

        <item type="list" icon="unchecked">
            <item type="button" action="select-all">All</item>
            <item type="button" action="select-none">None</item>
        </item>

        <item type="submit" action="delete" icon="delete" title="Delete selected message(s)">Delete</item>
        <item type="button" action="refresh" icon="reload" force-update>Refresh</item>

        <item type="separator"></item>

        <!-- Navigation buttons -->
        <item type="button" action="first-page" icon="first" title="Goto first page" />
        <item type="button" action="prev-page" icon="prev" title="Goto previous page" />
        <item type="page-list" prefix="Page " range="5" />
        <item type="button" action="next-page" icon="next" title="Goto next page" />
        <item type="button" action="last-page" icon="last" title="Goto last page" />
    </toolbar>


    <datagrid class="expand" id="grid_vm" data-type="odbc" data-source="results">
        <caption>[[$vbox_user]]@[[$vbox_context]] - [[$current_folder_name]]([[$num_results]])</caption>
        <header>
            <column style="width: 16px" type="checkbox"></column>
            <column style="width: 140px">Date</column>
            <column style="width: 90px">From</column>
            <column style="width: 140px">Name</column>
            <column style="width: 60px">Duration</column>
            <column style="width: 60px">Size</column>
            <column style="width: 80px" type="actions"></column>
        </header>

        <row>
            <column><input type="checkbox" name="id[]" value="[[id]]" /></column>
            <column>[[origtime | format_unix_time]]</column>

            <column>
                <call name="regex_clid" params="[[callerid]]" return="clid_name,clid_number" />
                [[$clid_number | format_phone]]
            </column>
            <column>
                <var name="clid_name" if-empty="Unknown" />
            </column>
            <column>[[duration | format_time_seconds]]</column>
            <column>[[size | format_byte]]</column>
            <column type="actions">
                <icon action="delete" icon="delete" title="Delete message" params="id=[[id]]" />
                <icon action="view" icon="play" title="Listen" params="id=[[id]]" />
            </column>
        </row>
    </datagrid>
</form>

<p class="v_spacing">
    <var name="num_results" format="%d message(s)">
        <if-empty>No messages.</if-empty>
    </var>
    <var name="current_page, total_pages" format="Page %d of %d"></var>
</p>
