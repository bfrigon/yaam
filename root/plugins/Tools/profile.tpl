<div class="box form">
<h1>Profile</h1>
<form id="frm_profile" action="" method="post">
	<input type="hidden" name="path" value="Tools.tools.profile" />

	<label for="user">Username :</label>
	<input name="user" type="text" value="[[$_SESSION|#user]]" readonly="readonly"/>
	<br />
	
	<label for="fullname">Full name :</label>
	<input name="fullname" type="text" value="[[$_SESSION|#fullname]]"/>
	<br />
	
	<div class="clear"><br /></div>
	<h2>Change password</h2>
	
	<label for="old_pwd">Old password :</label>
	<input name="old_pwd" type="password" value=""/>
	<br />

	<label for="new_pwd">New password :</label>
	<input name="new_pwd" type="password" value=""/>
	<br />	

	<label for="new_pwdver">Again :</label>
	<input name="new_pwdver" type="password" value=""/>
	<br />	

	<div class="toolbar center v_spacing">
		<ul>
			<li><button type="submit"><img src="images/blank.png" class="icon16-save" />Save</button></li>
		</ul>
	</div>
	
	<div class="clear"><br /></div>
</form>
</div>
