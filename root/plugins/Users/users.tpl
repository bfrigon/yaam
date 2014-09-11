<!--
--------------------------------------------------------------------------------
 Users management plugin template
 
 Author   : Benoit Frigon
 Last mod : 9 sept 2013
--------------------------------------------------------------------------------
-->
<toolbar>
	<item type="label">Find :</item>
	<item type="textbox" name="s"  width="170px" title="Search user"></item>
	
	<item type="submit" action="search" icon="search" title="Search user"></item>
	<item type="button" action="clear" icon="clear" title="Clear search query"></item>

	<item type="separator"></item>

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



<datagrid id="users" data-type="odbc" data-source="results">
	<header>
		<column style="width: 16px" type="checkbox"></column>
		<column style="width: 100px">User name</column>
		<column style="width: 160px">Name</column>
		<column style="width: 80px">Extension</column>
		<column style="width: 110px">Context</column>
		<column style="width: 110px">Voicemail</column>
		<column style="width: 130px">Permissions</column>
		<column style="width: 80px" type="actions"></column>
	</header>
	
	<row>
		<column type="checkbox"></column>
		<column>[[id]]</column>
		<column><a href="?path=ReverseLookup.tools.rlookup&number=[[number]]">[[number|format_phone]]</a></column>
		<column>[[action|lower|ucfirst]]</column>
		<column>[[extension]]</column>
		<column>[[description]]</column>
		<column></column>
		<column type="actions">test</column>
	</row>
	
	<if-empty>No results found</if-empty>
</datagrid>

<div id="log_content">
	<include src="[[$file]]" format="gz" />
</div>


<p class="v_spacing">
	<var name="num_results" format="%d user(s) found."><if-empty>No user found.</if-empty></var>
	<var name="current_page, total_pages,test" format="Page %d of %d"></var>
</p>
