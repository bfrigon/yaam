<div class="box form">
<h1><?=$action!='edit' ? 'New' : 'Edit' ?> Call treatment</h1>
<form id="ct_frm_add" action="" method="post">
	<input name="path" type="hidden" value="CallTreatment.tools.ct" />
	<input name="id" type="hidden" value="{{$id}}" />
	<input name="action" type="hidden" value="{{$action}}" />
	<input type="hidden" name="referer" value="{{$referer}}" />

	<label for="num">Number :</label>
	<input class="{{is|"$h_num"|highlight}}" name="num" type="text" value="{{$f_num}}"/><br />
	
	<label for="cidname">Action :</label>
	<input class="{{is|"$h_ctaction"|highlight}}" name="ctaction" type="text" value="{{$f_ctaction}}"/><br />

	<label for="extension">Extension :</label>
	<input name="extension" type="text" value="{{$f_extension}}"/><br />

	<label for="description">Description :</label>
	<input name="description" type="text" value="{{$f_description}}"/><br />


	<div class="toolbar center v_spacing">
		<ul>
			<li><button id="ct_add_btnsave" type="submit"><img src="images/blank.png" class="icon16-save" />Save</button></li>
			<li><a class="cancel" id="ct_add_btncancel" href="{{$referer}}"><img src="images/blank.png" class="icon16-cancel" />Cancel</a></li>
		</ul>
	</div>
	<div class="clear"><br /></div>
</form>
</div>

