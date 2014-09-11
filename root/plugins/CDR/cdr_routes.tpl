<!--
--------------------------------------------------------------------------------
 Call Routes plugin template
 
 Author   : Benoit Frigon
 Last mod : 9 sept 2013
--------------------------------------------------------------------------------
-->
<form id="crte_frm" action="" method="get">
	<toolbar id="routes">
		<item type="list" icon="unchecked">
			<item type="button" action="select-all">All</item>
			<item type="button" action="select-none">None</item>
		</item>
	
		<item type="button" action="add" icon="add" title="Add a new user">New</item>
		<item type="button" action="delete" icon="delete" title="Delete selected user(s)">Delete</item>
	
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
			<column style="width: 290px">Name</column>
			<column style="width: 120px">Type</column>
			<column style="width: 50px">Priority</column>
			<column style="width: 100px">Cost</column>
			<column style="width: 80px" type="actions"></column>
		</header>
	
		<row>
			<column type="checkbox"></column>
			<column>[[name]]</column>
			<column>[[type]]</column>
			<column>[[priority]]</column>
			<column>[[cost|format_money]]/min.</column>
			<column type="actions">
				<a tabindex="1" href="?path=CDR.tools.cdr_routes&action=edit&id={{column|id}}"><img alt="Edit" class="icon16 icon16-edit" src="images/blank.png" /></a>
				<a tabindex="1" href="?path=CDR.tools.cdr_routes&action=delete&id[]={{column|id}}"><img alt="Delete" class="icon16 icon16-delete" src="images/blank.png" /></a>
			</column>
		</row>
	</datagrid>
</form>

<p class="v_spacing">
	<var name="num_results" format="%d route(s) defined. "><if-empty>No routes defined!</if-empty></var><br />
	<var name="current_page, total_pages" format="Page %d of %d" />
</p>
