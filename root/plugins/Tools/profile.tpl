<div class="box form">
<h1>Profile</h1>
<form id="frm_profile" action="" method="post">
	<input type="hidden" name="path" value="Tools.tools.profile" />

	<label for="user">Username :</label>
	<input name="user" type="text" value="[[$_SESSION['user']]]"/><br />
	
	<label for="fullname">Full name :</label>
	<input name="fullname" type="text" value="{{$_SESSION['fullname']}}"/><br />
	
	<label for="ui_theme">UI Theme :</label>
	<input name="ui_theme" type="text" value="{{$_SESSION['ui_theme']}}"/><br />
	
	<div class="clear"><br /></div>
	<h2>Call settings</h2>
	
	<label for="user_chan">Channel name :<span class="small">"ex. SIP/John</span></label>
	<input name="user_chan" type="text" value="{{$_SESSION['user_chan']}}"/><br />
	
	<label for="vbox">Voicemail box :<span class="small">"ex. default/100</span></label>
	<input name="vbox" type="text" value="{{$_SESSION['vbox']}}"/><br />

	<div class="clear"><br /></div>
	<h2>Change password</h2>
	
	<label for="old_pwd">Old password :</label>
	<input name="old_pwd" type="password" value=""/><br />	

	<label for="new_pwd">New password :</label>
	<input name="new_pwd" type="password" value=""/><br />	

	<label for="new_pwdver">Again :</label>
	<input name="new_pwdver" type="password" value=""/><br />	

	<div class="toolbar center v_spacing">
		<ul>
			<li><button type="submit"><img src="images/blank.png" class="icon16-save" />Save</button></li>
		</ul>
	</div>
	
	<div class="clear"><br /></div>
</form>
</div>
