<div class="box form">
<h1><?=$action!='edit' ? 'New' : 'Edit' ?> CNAM dictionary item</h1>
<form id="cnam_frm_add" action="" method="post">
	<input name="path" type="hidden" value="CNAM.tools.cnam" />
	<input name="id" type="hidden" value="{{$id}}" />
	<input name="action" type="hidden" value="{{$action}}" />
	<input type="hidden" name="referer" value="{{$referer}}" />

	<label for="num">Number :</label>
	<input class="{{is|"$h_num"|highlight}}" name="num" type="text" value="{{$f_num}}"/><br />
	
	<label for="cidname">CID name :<span class="small">15 chars. max</span></label>
	<input class="{{is|"$h_cidname"|highlight}}" name="cidname" type="text" value="{{$f_cidname}}"/><br />

	<label for="fullname">Full name :</label>
	<input name="fullname" type="text" value="{{$f_fullname}}"/><br />
	
	<div class="toolbar center v_spacing">
		<ul>
			<li><button id="cnam_add_btnsave" type="submit"><img src="images/blank.png" class="icon16-save" />Save</button></li>
			<li><a class="cancel" id="cnam_add_btncancel" href="{{$referer}}"><img src="images/blank.png" class="icon16-cancel" />Cancel</a></li>
		</ul>
	</div>
	<div class="clear"><br /></div>
</form>
</div>

