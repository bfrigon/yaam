<!--
--------------------------------------------------------------------------------
 Reverse lookup plugin template
 
 Author   : Benoit Frigon
 Last mod : 9 sept 2013
--------------------------------------------------------------------------------
-->
<form id="form_map" action="" method="get">
	
	<toolbar id="toolbar_map">
		<item type="label">Number :</item>
		<item id="txt_number" type="textbox" name="number" style="width: 200px" />

		<item id="btn_lookup" type="submit" action="search" icon="search" title="Search CNAM entries"></item>
		<item id="btn_clear" type="button" action="clear" icon="clear" title="Clear search query"></item>
		
		<item type="label"><span style="display: none;" id="no_result">No results found.</span></item>
	</toolbar>
</form>

<div class="map <?php if ($static) echo 'static' ?>">
	<div class="box results">
		<div id="map_result_info" style="display: <?php echo (strlen($number) > 0) ? 'block' : 'none' ?>">
			<h1 id="result_number">[[$number|format_phone]]</h1>
			<h2 id="result_name">[[$result_name]]</h2>
			<span id="result_address">[[$result_address]]</span>
			<label>Carrier :</label><span id="result_carrier">[[$result_carrier]]</span>
			<label>Line type :</label><span id="result_line_type">[[$result_line_type]]</span>
		</div>
	</div>
	<div id="map_viewport"><img id="static" src="[[$static_map_url]]" alt="map"/></div>
</div>

<div class="popup dialog box" id="dialog_map_lookup">
	<h1>Reverse lookup</h1>
	Searching <span id="lookup_number"></span> ...
	<div class="break"></div>
	<div class="toolbar center">
		<ul>
			<li><a id="btn_cancel" href="javascript:map_cancel_lookup()"><img src="images/blank.png" class="icon16-cancel">Cancel</a></li>
		</ul>
	</div>
</div>
