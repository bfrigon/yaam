<div class="box form">
<h1>[[if:$new:"Add\x20new":"Edit"]] group</h1>
<form id="frm_profile" action="" method="post">
	<input type="hidden" name="path" value="Groups.groups" />
	<input type="hidden" name="op" value="[[if:$new:'add':'edit']]"/>

	<label for="user">Group name :</label>
	<input name="group" type="text" value="[[$last_post|#group]]" onkeypress="[[if:$new:'':'event.preventDefault()']]"/>
	<br />
	
	<label for="fullname">Full name :</label>
	<input name="fullname" type="text" value="[[$last_post|#fullname]]"/>
	<br />
	
	<div class="toolbar center v_spacing">
		<ul>
			<li><button type="submit"><img src="images/blank.png" class="icon16-save" />Save</button></li>
		</ul>
	</div>
	
	<div class="clear"><br /></div>
</form>
</div>
