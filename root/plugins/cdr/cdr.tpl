<form id="frm_cdr" method="get">
    <toolbar id="toolbar_cdr">
        <item type="label">Date :</item>
        <item type="date" id="cdr_d_from" name="d_from" placeholder="[[format_date]]" title="Filter calls by date"/>
        <item type="label">To</item>
        <item type="date" id="cdr_d_to" name="d_to" placeholder="[[format_date]]" title="Filter calls by date" />

        <item type="label">Find :</item>
        <item id="txt_cdr_search" type="text" name="s" title="Filter calls by number or name"></item>

        <item type="submit" action="search" icon="search" title="Search"></item>
        <item type="button" action="clear" icon="clear" title="Clear filters"></item>

        <item type="separator"></item>
        <item type="button" action="refresh" icon="reload" title="Refresh CDR">Refresh</item>
        <item type="separator"></item>

        <!-- Navigation buttons -->
        <item type="button" action="first-page" icon="first" title="Goto first page" />
        <item type="button" action="prev-page" icon="prev" title="Goto previous page" />
        <item type="page-list" prefix="Page " range="5" />
        <item type="button" action="next-page" icon="next" title="Goto next page" />
        <item type="button" action="last-page" icon="last" title="Goto last page" />
    </toolbar>
</form>
<datagrid class="expand" data-type="odbc" data-source="results" min-rows="25">
    <header>
        <column id="col_cdr_icon" type="icon"></column>
        <column id="col_cdr_date">Date</column>
        <column id="col_cdr_from">From</column>
        <column id="col_cdr_name">Name</column>
        <column id="col_cdr_dest">Destination</column>
        <column id="col_cdr_duration">Duration</column>
        <column id="col_cdr_billed">Billed</column>
        <column id="col_cdr_cost">Cost</column>
    </header>

    <row>
        <column type="icon"><icon icon="[[call_type]]" /></column>
        <column>[[calldate]]</column>

        <column>
            <call name="regex_clid" params="[[clid]]" return="clid_name,clid_number" />
            [[$clid_number | format_phone]]

            <action-list type="icon" icon-size="12" class="float-right" name="phone_number_tools">
                <param name="number" value="$clid_number" />
            </action>
        </column>
        <column>
            <var name="clid_name" if-empty="Unknown" />
        </column>

        <column>
            [[dst | format_phone]]

            <action-list type="icon" icon-size="12" class="float-right" name="phone_number_tools">
                <param name="number" value="dst" />
            </action-list>
        </column>
        <column>[[duration | format_time_seconds]]</column>
        <column>[[billsec | format_time_seconds]]</column>
        <column>[[cost | format_money]]</column>
    </row>

    <footer>
        <column></column>
        <column></column>
        <column></column>
        <column></column>
        <column></column>
        <column>[[$total_duration | format_seconds]]</column>
        <column>[[$total_billsec | format_seconds]]</column>
        <column>[[$total_cost | format_money]]</column>
    </footer>

    <if-empty>** No calls **</if-empty>
</datagrid>

<p>
    <var name="num_results" format="%d results(s) found. " if-empty="No results found" /><br />
    <var name="current_page, total_pages" format="Page %d of %d" />
</p>
