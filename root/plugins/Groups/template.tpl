<!--
--------------------------------------------------------------------------------
 Groups management plugin template
 
 Author   : Benoit Frigon
 Last mod : 9 sept 2013
--------------------------------------------------------------------------------
-->
<toolbar>
	<item type="label">Find :</item>
	<item type="textbox" name="s"  width="170px" title="Search group"></item>
	
	<item type="submit" action="search" icon="search" title="Search group"></item>
	<item type="button" action="clear" icon="clear" title="Clear search query"></item>

	<item type="separator"></item>

	<item type="list" icon="unchecked">
		<item type="button" action="select-all">All</item>
		<item type="button" action="select-none">None</item>
	</item>
	
	<item type="button" action="add" icon="add" title="Add a new group">New</item>
	<item type="button" action="delete" icon="delete" title="Delete selected group(s)">Delete</item>
	
	<item type="separator"></item>
	
	<!-- Navigation buttons -->
	<item type="button" action="first-page" icon="first" title="Goto first page" />
	<item type="button" action="prev-page" icon="prev" title="Goto previous page" />
	<item type="page-list" prefix="Page " range="5" />
	<item type="button" action="next-page" icon="next" title="Goto next page" />
	<item type="button" action="last-page" icon="last" title="Goto last page" />
</toolbar>

<datagrid id="groups" data-type="odbc" data-source="results">
	<header>
		<column style="width: 16px" type="checkbox"></column>
		<column style="width: 100px">Group name</column>
		<column style="width: 160px">Name</column>
		<column style="width: 80px" type="actions"></column>
	</header>
	
	<row>
		<column><input type="checkbox" name="group_[[group]]"/></column>
		<column>[[group]]</column>
		<column>[[fullname]]</column>
		<column type="actions">
			<a tabindex="1" href="?path=Groups.groups&action=edit&id=[[group]]"><img alt="Edit" class="icon16 icon16-edit" src="images/blank.png" /></a>
			<a tabindex="1" href="?path=Groups.groups&action=delete&id=[[group]]"><img alt="Delete" class="icon16 icon16-delete" src="images/blank.png" /></a>
		</column>
	</row>
	
	<if-empty>No results found</if-empty>
</datagrid>

<div id="log_content">
	<include src="[[$file]]" format="gz" />
</div>


<p class="v_spacing">
	<var name="current_page, total_pages,test" format="Page %d of %d"></var>
</p>
