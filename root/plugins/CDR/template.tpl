<!--
--------------------------------------------------------------------------------
 CDR plugin template
 
 Author   : Benoit Frigon
 Last mod : 9 sept 2013
--------------------------------------------------------------------------------
-->

<form action="" method="get">
	<toolbar id="filters">
		<item type="label">Date :</item>
		<item type="datebox" name="d" width="75px" title="Search date"/>
		<item type="separator"></item>
	
		<item type="label">Find :</item>
		<item type="textbox" name="s" width="170px" title="Search query"></item>
	
		<item type="submit" action="search" icon="search" title="Search record"></item>
		<item type="button" action="clear" icon="clear" title="Clear search query"></item>

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

	<datagrid data-type="odbc" data-source="results" min-rows="25">
		<header>
			<column style="width: 16px" type="icon"></column>
			<column style="width: 130px">Date</column>
			<column style="width: 110px">Number</column>
			<column style="width: 150px">Name</column>
			<column style="width: 110px">Destination</column>
			<column style="width: 50px">Duration</column>
			<column style="width: 50px">Billed</column>
			<column style="width: 50px">Cost</column>
			<column style="width: 80px">Disposition</column>
		</header>
		
		<row>
			<column type="icon"><icon icon="[[type]]" /></column>
			<column>[[calldate]]</column>
			
			<column>
				<call name="regex_clid" params="[[clid]]" return="clid_name,clid_number" />
				[[$clid_number | format_phone]]
			</column>
			<column>
				<var name="clid_name" if-empty="Unknown" />
			</column>
			
			<column>[[dst | format_phone]]</column>
			<column>[[duration | format_time_seconds]]</column>
			<column>[[billsec | format_time_seconds]]</column>
			<column>[[cost | format_money]]</column>
			<column>[[disposition || lower]]</column>
		</row>
		
		<footer>
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
</form>	

<p class="v_spacing">
	<var name="total_calls" format="%d results(s) found. " if-empty="No results found" /><br />
	<var name="current_page, total_pages" format="Page %d of %d" />
</p>
