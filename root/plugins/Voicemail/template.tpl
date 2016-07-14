<?php
/****************************************************************************
 * Voicemail plugin template                                                *
 * Author   : Benoit Frigon                                                 *
 * Last mod : 7 oct 2012                                                    *
 ***************************************************************************/
?>
<form action="" method="get">
	<input type="hidden" name="path" value="[[$tab_path]]" />
	<input type="hidden" name="folder" value="[[$current_folder]]" />
	<toolbar id="filters">
		<item type="label">Folder :</item>
		<item type="list" icon="move" data-type="dict" data-source="folders">
			<caption>[[$current_folder_caption]]</caption>
			<row><item type="button" href="?path=[[$tab_id]]&file=[[value|#0]]">[[value|#1]]</item></row>
		</item>
        	<!--li class="disabled"><a href="#">*No folders*</a></li-->

		<item type="separator"></item>

		<item type="delete"></item>
		<item type="refresh"></item>
		<item type="move-to"></item>

		<item type="separator"></item>

                <!-- Navigation buttons -->
                <item type="button" action="first-page" icon="first" title="Goto fist page"/>
                <item type="button" action="prev-page" icon="prev" title="Goto previous page"/>
                <item type="page-list" prefix="Page " range="5" />
                <item type="button" action="next-page" icon="next" title="Goto next page"/>
                <item type="button" action="last-page" icon="last" title="Goto last page"/>
        </toolbar>

	<datagrid data-type="odbc" data-source="results" min-row="25">
<caption>
	[[$current_voicemail]] - [[$current_folder_caption]] ([[$num_messages]])
</caption>
                <header>
                        <column style="width: 16px" type="checkbox"></column>
                        <column style="width: 180px">Received on</column>
                        <column style="width: 160px">From</column>
                        <column style="width: 220px">Name</column>
                        <column style="width: 80px">Duration</column>
                        <column style="width: 100px">Size</column>
                        <column>&nbsp;</column>
                </header>

                <row>
                        <column type="icon"><icon icon="[[type]]" /></column>
                        <column>[[calldate]]</column>
                        <column>[[src]]</column>
                        <column>[[name]]</column>
                        <column>[[duration]]</column>
                        <column>[[size]]</column>
			<column>&nbsp;</column>
                </row>

<!--tr
    class="{{altern|msg_row|alt}} highlight" 
    initial-data='{{row|json}}'
>
    <td class="column-icon">
        <input
            type="checkbox" 
            name="id[]"
            value="{{column|0}}"
            {{is|$action=="select_all"|checked}} />
    </td>
    <td class="clickable">
        <a class="vm-msg-open" tabindex="1" href="{{column|6}}">{{column|1}}</a>
    </td>
    <td class="clickable">
        <a class="vm-msg-open" tabindex="1" href="{{column|6}}">{{column|3}}</a>
    </td>
    <td class="clickable">
        <a class="vm-msg-open" tabindex="1" href="{{column|6}}">{{column|2}}</a>
    </td>
    <td class="clickable">
        <a class="vm-msg-open" tabindex="1" href="{{column|6}}">{{column|4}}</a>
    </td>
    <td class="clickable">
        <a class="vm-msg-open" tabindex="1" href="{{column|6}}">{{column|5}}</a>
    </td>
    <td class="column-actions">
        <a class="vm-msg-open" tabindex="1" href="{{column|6}}">
            <img src="images/blank.png" class="icon16-vol-up" />
        </a>
        <a
            tabindex="1"
            href="?
                path={{$tab_path}}&
                folder={{$current_folder}}&
                action=delete&
                id={{column|0}}">
            <img src="images/blank.png" class="icon16-delete" />
        </a>
    </td>
</tr-->
                <if-empty>** No calls **</if-empty>
	</datagrid>
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

