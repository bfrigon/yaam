<toolbar id="filters">
    <item type="label">Log file :</item>
    <item type="list" width="250px" icon="move" data-type="array" data-source="log_list">
        <caption>[[$log_basename]]</caption>
        <row>
            <item type="button" params="file=[[value]]">[[value]]</item>
        </row>
    </item>
    <item type="separator" />

    <item type="button" icon="reload" action="refresh">Refresh</item>
</toolbar>

<div class="box scroll" id="log_container">
    <div id="log_content">
        [[$log_filename | dumpfile]]
    </div>
</div>

<script type="text/javascript">
//<![CDATA[

    $(document).ready(function () {
        $("#log_content").contents().filter(function(){return this.nodeType !== 1;}).each(function() {

            if ($(this).text().match(/error|fatal|panic/i)) {
                $(this).wrap("<span class=\"log-error\" />");

            } else if ($(this).text().match(/warning/i)) {
                $(this).wrap("<span class=\"log-warning\" />");
            }
        });
    });

//]]>
</script>
