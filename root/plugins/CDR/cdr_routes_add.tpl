<div class="box form">
<h1>[[if:$action!='edit':'New':'Edit']] CDR route entry</h1>
<form id="cnam_frm_add" action="" method="post">

	<input name="id" type="hidden" value="[[$id]]" />


	<label for="name">Route name :</label>
	<input name="name" type="text" value="[[$f_name]]"/><br />
	
	<label for="type">Route type :</label>
	<input name="type" type="text" value="[[$f_type]]"/><br />

	<div class="clear"><br /></div>
	<h2>Billing</h2>
	
	<label for="type">Cost :<span class="small">$ / minute</span></label>
	<input style="width: 80px;" name="cost" type="text" value="[[$f_cost]]"/><br />

	<label for="minimum">Minimum :<span class="small">(Seconds)</span></label>
	<input style="width: 80px;" name="minimum" type="text" value="[[$f_minimum]]"/><br />
 
	<label for="inc">Increment :<span class="small">(Seconds)</span></label>
	<input style="width: 80px;" name="inc" type="text" value="[[$f_inc]]"/><br />
	
	<div class="clear"><br /></div>
	<h2>Field match rules</h2>

	<label for="priority">Priority :</label>
	<input style="width: 60px;"name="priority" type="text" value="[[$f_priority]]"/><br />

	<label for="srcchannel">From channel :</label>
	<input name="srcchannel" type="text" value="[[$f_srcchannel]]"/><br />

	<label for="src">From number :</label>
	<input name="src" type="text" value="[[$f_src]]"/><br />

	<label for="dcontext">Dest. context :</label>
	<input name="dcontext" type="text" value="[[$f_dcontext]]"/><br />
	
	<label for="dst">Dest. number :</label>
	<input name="dst" type="text" value="[[$f_dst]]"/><br />

	<label for="dstchannel">Dest. channel :</label>
	<input name="dstchannel" type="text" value="[[$f_dstchannel]]"/><br />

	<div class="toolbar center v_spacing">
		<ul>
			<li><button id="cnam_add_btnsave" type="submit"><img src="images/blank.png" class="icon16-save" />Save</button></li>
			<li><a class="cancel" id="cnam_add_btncancel" href="[[$referer]]"><img src="images/blank.png" class="icon16-cancel" />Cancel</a></li>
		</ul>
	</div>
	<div class="clear"><br /></div></form>
</div>
