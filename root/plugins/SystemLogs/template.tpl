<!--
--------------------------------------------------------------------------------
 System logs plugin template
 
 Author   : Benoit Frigon
 Last mod : 15 sept 2013
--------------------------------------------------------------------------------
-->

<toolbar id="filters">
	<item type="label">Log file :</item>
	<item type="list" icon="move" data-type="dict" data-source="log_list">
		<caption>[[$log_basename]]</caption>
		<row><item type="button" href="?path=SystemLogs.logs.[[$tab_id]]&file=[[value]]">[[value]]</item></row>
	</item>
	<item type="separator" />
	
	<item style="width: 250px" type="button" icon="reload" action="refresh" >Refresh</item>
</toolbar>

<div class="box scroll" id="log_container">
	<div id="log_content">
		{{dumpgzfile|$log_filename}}
	</div>
</div>

