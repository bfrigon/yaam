<!-- Toolbar -->
<toolbar name="filters">
	<item type="label">Folder :</item>
	
	<item type="list" data-type="dict" data-source="folders" width="200px">
		<caption>[[$current_folder]]</caption>
		<row><item type="button" params="folder=[[key]]">[[value]]</item></row>
		<if-empty><item type="button" disabled>** No folders **</item></if-empty>
	</item>
	
	<item type="separator"></item>

	<item type="list" icon="unchecked">
		<item type="button" action="select-all">All</item>
		<item type="button" action="select-none">None</item>
	</item>	
	
	<item type="button" action="delete" icon="delete" title="Delete selected user(s)">Delete</item>
	<item type="button" action="refresh" icon="reload" title="Refresh message list">Refresh</item>	
	<item type="list" icon="move" data-type="dict" data-source="folders">
		<caption>Move to</caption>
		<row><item type="submit" action="move+[[key]]">[[value]]</item></row>
		<if-empty><item type="button" disabled>** No folders **</item></if-empty>
	</item>
	
	<item type="separator"></item>
	
	<!-- Navigation buttons -->
	<item type="button" action="first-page" icon="first" title="Goto first page" />
	<item type="button" action="prev-page" icon="prev" title="Goto previous page" />
	<item type="page-list" prefix="Page " range="5" />
	<item type="button" action="next-page" icon="next" title="Goto next page" />
	<item type="button" action="last-page" icon="last" title="Goto last page" />
</toolbar>
