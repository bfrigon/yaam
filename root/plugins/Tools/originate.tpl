<div class="box form">
<h1>Originate call</h1>
<form id="frm_orig" action="" method="post">
	<label for="chan">Channel :</label>
	<input class="{{is|"$h_chan"|highlight}}" name="chan" type="text" value="{{$f_chan}}"/><br />
	
	<label for="exten">Extension :</label>
	<input class="{{is|"$h_exten"|highlight}}" name="exten" type="text" value="{{$f_exten}}"/><br />
	
	<label for="context">Context :<span class="small">"Default" if empty.</span></label>
	<input name="context" type="text" value="{{$f_context}}"/><br />

	<div class="clear"><br /></div>
	<h2>Outbound caller id</h2>
	
	<label for="cid_name">Name :</label>
	<input name="cid_name" type="text" value="{{$f_cid_name}}"/><br />
	
	<label for="cid_num">Number :</label>
	<input name="cid_num" type="text" value="{{$f_cid_num}}"/><br />

	<div class="toolbar center v_spacing">
		<ul>
			<li><button type="submit" id="originate_btnok"><img src="images/blank.png" class="icon16-call" />Originate</button></li>
			<li><a class="refresh" href="?path=Tools.tools.originate"><img src="images/blank.png" class="icon16-clear" />Clear</a></li>
		</ul>
	</div>

	<div class="clear"><br /></div>
</form>
</div>
