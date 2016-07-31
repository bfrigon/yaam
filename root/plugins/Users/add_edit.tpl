<div class="box form">
<h1>[[if:$new:"Add\x20new":"Edit"]] user</h1>
<form id="frm_profile" action="" method="post">
	<input type="hidden" name="path" value="Users.users" />
	<input type="hidden" name="op" value="[[if:$new:'add':'edit']]"/>

	<label for="user">Username :</label>
	<input name="user" type="text" value="[[$last_post|#user]]" onkeypress="[[if:$new:'':'event.preventDefault()']]"/>
	<br />
	
	<label for="fullname">Full name :</label>
	<input name="fullname" type="text" value="[[$last_post|#fullname]]"/>
	<br />
	
	<label for="ui_theme">UI Theme :</label>
	<input name="ui_theme" type="text" value="[[$last_post|#ui_theme]]"/>
	<br />
	
	<label for="pgroups">Group :</label>
	<input name="pgroups" type="text" value="[[$last_post|#pgroups]]"/>
	<br />
	
	<div class="clear"><br /></div>
	<h2>Call settings</h2>
	
	<label for="user_chan">Channel name :<span class="small">"ex. SIP/John</span></label>
	<input name="user_chan" type="text" value="[[$last_post|#user_chan]]"/>
	<br />
	
	<label for="vbox">Voicemail box :<span class="small">"ex. default/100</span></label>
	<input name="vbox" type="text" value="[[$last_post|#vbox]]"/>
	<br />

	<div class="clear"><br /></div>
	<h2>Extension data</h2>

	<label for="exten">Extension :</label>
	<input name="exten" type="text" value="[[$last_post|#exten]]"/>
	<br />	
	
	<label for="passwd">Password :</label>
	<input name="passwd" type="password" value=""/>
	<br />	

	<div class="toolbar center v_spacing">
		<ul>
			<li><button type="submit"><img src="images/blank.png" class="icon16-save" />Save</button></li>
		</ul>
	</div>
	
	<div class="clear"><br /></div>
</form>
</div>
