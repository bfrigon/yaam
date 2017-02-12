<if type="permission" value=500>
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
        <item id="txt_vm_search" type="list" icon="folder" data-type="dict" data-source="folders">
            <caption>[[$current_folder_name]]</caption>
            <row>
                <item type="button" params="folder=[[key]]" keep-uri>[[value]]</item>
            </row>
        </item>

        <item type="separator" />
        <item type="submit" action="delete" icon="delete" title="Delete selected message(s)">Delete</item>
        <item type="button" action="refresh" icon="reload" force-update>Refresh</item>

        <!-- Navigation buttons -->
        <item type="separator" />
        <item type="button" action="first-page" icon="first" title="Goto first page" />
        <item type="button" action="prev-page" icon="prev" title="Goto previous page" />
        <item type="page-list" prefix="Page " range="5" />
        <item type="button" action="next-page" icon="next" title="Goto next page" />
        <item type="button" action="last-page" icon="last" title="Goto last page" />
    </toolbar>


    <datagrid class="expand" id="grid_vm" data-type="odbc" data-source="results" min-rows="15">
        <caption>[[$vbox_user]]@[[$vbox_context]] - [[$current_folder_name]]([[$num_results]])</caption>
        <header>
            <column id="col_vm_check" type="select"></column>
            <column id="col_vm_date">Date</column>
            <column id="col_vm_from">From</column>
            <column id="col_vm_name">Name</column>
            <column id="col_vm_duration">Duration</column>
            <column id="col_vm_size">Size</column>
            <column id="col_vm_actions" type="actions"></column>
        </header>

        <row>
            <column type="select" name="id" value="[[id]]"></column>
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
        <if-empty>** No messages **</if-empty>
    </datagrid>
</form>

<p class="v_spacing">
    <var name="num_results" format="%d message(s)">
        <if-empty>No messages.</if-empty>
    </var>
    <var name="current_page, total_pages" format="Page %d of %d"></var>
</p>
