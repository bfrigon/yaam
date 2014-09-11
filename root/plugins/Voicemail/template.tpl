{{****************************************************************************}}
{{* Voicemail plugin template                                                *}}
{{* Author   : Benoit Frigon                                                 *}}
{{* Last mod : 7 oct 2012                                                    *}}
{{****************************************************************************}}
<form action="" method="get">
	<input type="hidden" name="path" value="{{$tab_path}}" />
	<input type="hidden" name="folder" value="{{$current_folder}}" />

	<div class="box toolbar">
		<ul class="toolbar">
			<li class="text">Folder :</li>
			<li class="dropdown" style="width: 160px;"><a tabindex="1" href="#">{{$current_folder_caption}}</a>
				<img class="close-dropdown" src="images/blank.png" alt="" />
				<ul>
					{{foreach|$folders}}
						<li><a tabindex="1" href="?path={{$tab_path}}&folder={{column|0}}">{{column|1}}</a></li>
					{{else}}
						<li class="disabled"><a href="#">*No folders*</a></li>
					{{/foreach}}
				</ul>
			</li>
			<li class="separator"></li>
			<li class="dropdown"><a href="#"><img src="images/blank.png" class="icon16-unchecked" /></a>
				<img class="close-dropdown" src="images/blank.png" alt="" />
				<ul>
					<li><a tabindex="1" href="?path={{$tab_path}}&action=select_all&folder={{$current_folder}}&page={{$page}}">All</a></li>
					<li><a tabindex="1" href="?path={{$tab_path}}&folder={{$current_folder}}&page={{$page}}">None</a></li>
				</ul>
			</li>
			<li id="vm-msg-btn-delete"><button type="submit" name="action" value="delete" title="Delete selected item(s)"><!--delete--><img src="images/blank.png" class="icon16-delete" />Delete</button></li>
			<li id="vm-msg-btn-refresh"><a class="refresh" tabindex="1" href="?path={{$tab_path}}&folder={{$current_folder}}&page={{$page}}"><img src="images/blank.png" class="icon16-reload" />Refresh</a></li>
			<li id="vm-msg-btn-move" class="dropdown"><a tabindex="1" href="#"><img src="images/blank.png" class="icon16-move" />Move to</a>
				<img class="close-dropdown" src="images/blank.png" alt="" />
				<ul>
					{{foreach|$folders}}
						<li><button type="submit" name="action" value="move+{{column|0}}"><!--move+{{column|0}}-->{{column|1}}</button></li>
					{{else}}
						<li class="disabled"><a tabindex="1" href="#">*No folders*</a></li>
					{{/foreach}}
				</ul>
			</li>
		
			<li class="separator"></li>
			<li class="button {{is|"$page==1"||disabled}}" title="First page"><a tabindex="1" href="?path={{$tab_path}}&folder={{$current_folder}}&page=1"><img src="images/blank.png" class="icon16-first" /></a></li>
			<li class="button {{is|"$page==1"||disabled}}" title="Previous page"><a tabindex="1" href="?path={{$tab_path}}&folder={{$current_folder}}&page={{$prev_page}}"><img src="images/blank.png" class="icon16-prev" /></a></li>
			<li class="dropdown" style=""><a tabindex="1" title="Page {{$page}} of {{$last_page}}" href="#">Page {{$page}}</a>
				<img class="close-dropdown" src="images/blank.png" alt="" />
				<ul>
				{{foreach|$pages}}
					<li><a tabindex="1" href="?path={{$tab_path}}&folder={{$current_folder}}&page={{row}}">Page {{row}}</a></li>
				{{/foreach}}
				</ul>
			</li>
			<li class="button {{is|"$page==$last_page"||disabled}}" title="Next page"><a tabindex="1" href="?path={{$tab_path}}&folder={{$current_folder}}&page={{$next_page}}"><img src="images/blank.png" class="icon16-next" /></a></li>
			<li class="button {{is|"$page==$last_page"||disabled}}" title="Last page"><a tabindex="1" href="?path={{$tab_path}}&folder={{$current_folder}}&page={{$last_page}}"><img src="images/blank.png" class="icon16-last" /></a></li>		
		</ul>
		<div class="clear"></div>
	</div>

	<table id="vm-msg-list" class="grid">
		<caption>{{$current_voicemail}} - {{$current_folder_caption}} ({{$num_messages}})</caption>
		<thead><tr>
			<th class="column-icon"><span class="checkbox"></span></th>
			<th style="width: 180px">Received on</th>
			<th style="width: 160px">From</th>
			<th style="width: 220px">Name</th>
			<th style="width: 80px">Duration</th>
			<th style="width: 100px">Size</th>
			<th class="column-actions">&nbsp;</th>
		</tr></thead>
		{{foreach|$messages}}
			<tr class="{{altern|msg_row|alt}} highlight" initial-data='{{row|json}}'>
				<td class="column-icon"><input type="checkbox" name="id[]" value="{{column|0}}" {{is|$action=="select_all"|checked}} /></td>
				<td class="clickable"><a class="vm-msg-open" tabindex="1" href="{{column|6}}">{{column|1}}</a></td>
				<td class="clickable"><a class="vm-msg-open" tabindex="1" href="{{column|6}}">{{column|3}}</a></td>
				<td class="clickable"><a class="vm-msg-open" tabindex="1" href="{{column|6}}">{{column|2}}</a></td>
				<td class="clickable"><a class="vm-msg-open" tabindex="1" href="{{column|6}}">{{column|4}}</a></td>
				<td class="clickable"><a class="vm-msg-open" tabindex="1" href="{{column|6}}">{{column|5}}</a></td>
				<td class="column-actions">
					<a class="vm-msg-open" tabindex="1" href="{{column|6}}"><img src="images/blank.png" class="icon16-vol-up" /></a>
					<a tabindex="1" href="?path={{$tab_path}}&folder={{$current_folder}}&action=delete&id={{column|0}}"><img src="images/blank.png" class="icon16-delete" /></a>
				</td>
			</tr>
		{{remaining|$msg_per_page}}
			<tr class="{{altern|msg_row|alt}}">
				<td class="column-icon">&nbsp;</td>
				<td>&nbsp;</td>
				<td>&nbsp;</td>
				<td>&nbsp;</td>
				<td>&nbsp;</td>
				<td>&nbsp;</td>
				<td class="column-actions">&nbsp;</td>
			</tr>
		{{/foreach}}
	</table>        
</form>

<div id="vm-popup" class="box dialog popup">
	<h1>Message - <span id="vm-popup-date"></span></h1>

	<p>From : <span id="vm-popup-from-name">Unknown</span> - <span id="vm-popup-from-number">?</span></p>
	<div class="toolbar">
		<ul>
			<li id='vm-popup-btn-skip-start' title="Skip to beginning"><a href="javascript:vm_do_skip_start()"><img src="images/blank.png" class="icon16-first" /></a></li>
			<li id='vm-popup-btn-rew' title="Jump back 10 seconds"><a href="javascript:vm_do_rew()"><img src="images/blank.png" class="icon16-rew" /></a></li>
			<li id='vm-popup-btn-play' title="Play/Pause"><a href="javascript:vm_do_play()"><img src="images/blank.png" class="icon16-play" /></a></li>
			<li id='vm-popup-btn-stop' title="Stop"><a href="javascript:vm_do_stop()"><img src="images/blank.png" class="icon16-stop" /></a></li>
			<li id='vm-popup-btn-ffwd' title="Jump forward 10 seconds"><a href="javascript:vm_do_ffwd()"><img src="images/blank.png" class="icon16-ffwd" /></a></li>
			<li class="separator"></li>
			<li id='vm-popup-btn-vol-down' title="Volume down"><a href="javascript:vm_do_set_volume('down')"><img src="images/blank.png" class="icon16-vol-down" /></a></li>
			<li id='vm-popup-btn-vol-up' title="Volume up"><a href="javascript:vm_do_set_volume('up')"><img src="images/blank.png" class="icon16-vol-up" /></a></li>
			<li class="break"></li>
			<li class="text"><span id="vm-popup-player-cursor">00:00</span> / <span id="vm-popup-player-total">00:00</span></li>
		</ul>
	</div>
	<div class="clear"></div>
	<div id="vm-player"></div>
</div>

