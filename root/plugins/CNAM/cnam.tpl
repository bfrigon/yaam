<!--
--------------------------------------------------------------------------------
 CNAM directory plugin template
 
 Author   : Benoit Frigon
 Last mod : 9 sept 2013
--------------------------------------------------------------------------------
-->

<form action="" method="get">
	<toolbar id="filters">
		<item type="label">Find :</item>
		<item type="textbox" name="s" width="170px" title="Search user"></item>
	
		<item type="submit" action="search" icon="search" title="Search CNAM entries"></item>
		<item type="button" action="clear" icon="clear" title="Clear search query"></item>

		<item type="separator"></item>

		<item type="list" icon="unchecked">
			<item type="button" action="select-all">All</item>
			<item type="button" action="select-none">None</item>
		</item>
	
		<item type="button" action="add" icon="add" title="Add a new CNAM entry">New</item>
		<item type="button" action="delete" icon="delete" title="Delete selected CNAM entrie(s)">Delete</item>
	
		<item type="separator"></item>
	
		<!-- Navigation buttons -->
		<item type="button" action="first-page" icon="first" title="Goto first page" />
		<item type="button" action="prev-page" icon="prev" title="Goto previous page" />
		<item type="page-list" prefix="Page " range="5" />
		<item type="button" action="next-page" icon="next" title="Goto next page" />
		<item type="button" action="last-page" icon="last" title="Goto last page" />
	</toolbar>


	<datagrid id="users" data-type="odbc" data-source="results" min-rows="15">
		<header>
			<column style="width: 16px" type="checkbox"></column>
			<column style="width: 170px">Number</column>
			<column style="width: 200px">Caller ID (CNAM)</column>
			<column style="width: 230px">Full name</column>
			<column style="width: 80px" type="actions"></column>
		</header>
	
		<row>
			<column type="checkbox"></column>
			<column>[[number|format_phone]]</column>
			<column>[[cidname]]</column>
			<column>[[fullname]]</column>
			<column type="actions">
				<icon icon="edit" action="edit" params="id[]=[[id]]" />
				<icon icon="delete" action="delete" params="id[]=[[id]]" />
			</column>
		</row>
	</datagrid>
</form>

<p class="v_spacing">
	<var name="num_results" format="%d result(s) found. "><if-empty>No results found!</if-empty></var><br />
	<var name="current_page, total_pages" format="Page %d of %d" />
</p>
