<form id="frm_cdr_search" method="get">
    <toolbar id="cdr_filters">
        <item type="label">Date :</item>
        <group id="cdr_date_filter">
            <item type="date" id="cdr_d_from" name="d_from" width="90px" placeholder="[[format_date]]" title="Filter calls by date"/>
            <item type="label">To</item>
            <item type="date" id="cdr_d_to" name="d_to" width="90px" placeholder="[[format_date]]" title="Filter calls by date" />
        </group>
        <item type="separator"></item>

        <item type="label">Find :</item>
        <item type="text" name="s" width="150px" title="Filter calls by number or name"></item>

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
        <column style="width: 16px" type="icon"></column>
        <column style="width: 120px">Date</column>
        <column style="width: 120px">From</column>
        <column style="width: 160px">Name</column>
        <column style="width: 120px">Destination</column>
        <column style="width: 40px">Duration</column>
        <column style="width: 40px">Billed</column>
        <column style="width: 40px">Cost</column>
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

<p class="v_spacing">
    <var name="num_results" format="%d results(s) found. " if-empty="No results found" /><br />
    <var name="current_page, total_pages" format="Page %d of %d" />
</p>

<script type="text/javascript">
//<![CDATA[
    $(document).ready(function() {

        $('#cdr_date_filter').dateRangePicker({
            format: '<?php echo $format_date ?>',
            autoClose: true,
            separator : ' to ',
            showShortcuts: true,
            shortcuts : null,
            customShortcuts: [
                {
                    name: 'Today',
                    dates: function() {
                        start = moment().toDate();
                        end = moment().toDate();
                        return [start, end];
                   }
                },{
                    name: 'Yesterday',
                    dates: function() {
                        start = moment().subtract(1, 'days').toDate();
                        end = moment().subtract(1, 'days').toDate();
                        return [start, end];
                    }
                },{
                    name: 'Previous week',
                    dates: function() {
                        start = moment().subtract(1, 'weeks').startOf('week').toDate();
                        end = moment().subtract(1, 'weeks').endOf('week').toDate();
                        return [start, end];
                    }
                },{
                    name: 'Previous month',
                    dates: function() {
                        start = moment().subtract(1, 'months').startOf('month').toDate();
                        end = moment().subtract(1, 'months').endOf('month').toDate();
                        return [start, end];
                    }
                },{
                    name: 'Year-to-date',
                    dates: function() {
                        start = moment().startOf('year').toDate();
                        end = moment().toDate()
                        return [start, end]
                    }
                }
            ],

            getValue: function() {
                if ($('#cdr_d_from').val() && $('#cdr_d_to').val() )
                    return $('#cdr_d_from').val() + ' to ' + $('#cdr_d_to').val();
                else
                    return '';
            },

            setValue: function(s_date, s_from, s_to) {
                $('#cdr_d_from').val(s_from);
                $('#cdr_d_to').val(s_to);
            },

            customOpenAnimation: function(cb) {
                $(this).fadeIn(300, cb);
            },

            customCloseAnimation: function(cb) {
                $(this).fadeOut(300, cb);
            }
        })
        .bind('datepicker-open', function() {

            var elem = $('#cdr_d_from');
            $('.date-picker-wrapper').css({
                position: 'absolute',
                top: '' + (elem.offset().bottom) + 'px',
                left: '' + (elem.offset().left) + 'px',
            });
        });
    });
//]]>
</script>
