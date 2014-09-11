//******************************************************************************
// ui.js - Voicemail plugin user interface
// 
// Project   : Asterisk Y.A.A.M (Yet another asterisk manager)
// Version   : 0.1
// Author    : Benoit Frigon
// Last mod. : 7 mar. 2013
// 
// Copyright (c) 2011 - 2013 Benoit Frigon <bfrigon@gmail.com>
// www.bfrigon.com
// All Rights Reserved.
//
// This software is released under the terms of the GNU Lesser General Public 
// License v2.1. 
// A copy of which is available from http://www.gnu.org/copyleft/lesser.html
// 
//******************************************************************************
var objPlayer = null;
var vm_player_state = 'stopped';
var vm_msg_data = null;
var vm_player_current_pos = 0;
var vm_player_volume = 0.6;



//*****************************************************************************
//
// Events
//
//*****************************************************************************

// ----------------------------------------------
// Event: Document ready
// ----------------------------------------------
$(document).ready(function() {

	vm_init_player();


	$('#vm-popup').dialog({
		modal: true,
		width: 350,
		parent: $('#vm-msg-list'),
		closebtn: true
	});
	
	
	$('#vm-popup').on('dialog.close', function(event) {
		vm_do_stop();
	});


	$('.vm-msg-open').on('click', function(event) {
		
		data = $.parseJSON($(this).closest('tr').attr('initial-data'));
		if (data == undefined || data.length == 0)
			return
		
		$('#vm-popup-date').html(data[1]);
		$('#vm-popup-from-name').html(data[2]);
		$('#vm-popup-from-number').html(data[3]);
		
		vm_msg_data = data;
		
		vm_set_vm_player_state('stopped');
		
		
		$('#vm-popup').dialog('show');
		
		
		event.preventDefault();
	});


});


/*--------------------------------------------------------------------------
 * vm_init_player() : Initialize JPlayer object
 *
 * Arguments : None
 *
 * Returns   : Nothing
 */
function vm_init_player()
{
	objPlayer = $('#vm-player').jPlayer({
		wmode: "window",
		swfPath: 'include/player.swf',
		supplied: 'wav',
		volume: vm_player_volume,
		
		play: function () {
			vm_set_vm_player_state('playing');
		},
		
		ended: function() {
			vm_set_vm_player_state('stopped');
			vm_player_current_pos = 0;
		},
		
		timeupdate: function(event) {
			vm_player_current_pos = event.jPlayer.status.currentTime;
		
			if (vm_player_state == 'playing') {
				$('#vm-popup-player-cursor').text($.jPlayer.convertTime(event.jPlayer.status.currentTime));
				$('#vm-popup-player-total').text($.jPlayer.convertTime(event.jPlayer.status.duration));
			}
		},

	});

}


/*--------------------------------------------------------------------------
 * vm_do_play() : Play button event
 *
 * Arguments : None
 *
 * Returns   : Nothing
 */
function vm_do_play()
{
	switch (vm_player_state) {
		case 'stopped':
			objPlayer.jPlayer('setMedia', {wav: vm_msg_data[6]});
			
			objPlayer.jPlayer('play');
			break;
	
		case 'paused':
			objPlayer.jPlayer('play');
			vm_set_vm_player_state('playing');
			break;
		
		
		case 'playing':
			objPlayer.jPlayer('pause');
			vm_set_vm_player_state('paused');
			break;
	}
}


/*--------------------------------------------------------------------------
 * vm_do_stop() : Stop button event
 *
 * Arguments : None
 *
 * Returns   : Nothing
 */
function vm_do_stop()
{
	objPlayer.jPlayer('stop');
	vm_set_vm_player_state('stopped');
}


/*--------------------------------------------------------------------------
 * vm_do_rew() : Rewind button event
 *
 * Arguments : None
 *
 * Returns   : Nothing
 */
function vm_do_rew()
{
	objPlayer.jPlayer('play', vm_player_current_pos - 10);
}


/*--------------------------------------------------------------------------
 * vm_do_ffwd() : Fast forward button event
 *
 * Arguments : None
 *
 * Returns   : Nothing
 */
function vm_do_ffwd()
{
	objPlayer.jPlayer('play', vm_player_current_pos + 10);
}


/*--------------------------------------------------------------------------
 * vm_do_skip_start() : Skip to message begining button event 
 *
 * Arguments : None
 *
 * Returns   : Nothing
 */
function vm_do_skip_start()
{
	objPlayer.jPlayer('play', 0);
}


/*--------------------------------------------------------------------------
 * vm_do_set_volume() : Set JPlayer volume
 *
 * Arguments : 
 *   - volume : Value between 0.0 and 1.0, 'up' increase volume by 0.20
 *              'down' decrease the volume by 0.20
 *
 * Returns   : Nothing
 */
function vm_do_set_volume(volume)
{
	if (volume == 'up')
		new_vol = vm_player_volume + 0.20;
	else if (volume == 'down')
		new_vol = vm_player_volume - 0.20;
	else
		new_vol = parseInt(volume);
	
	
	if (new_vol < 0) new_vol = 0;
	if (new_vol > 1) new_vol = 1;
		
	
	$('#vm-popup-btn-vol-down').toggleClass('disabled', (new_vol == 0));
	$('#vm-popup-btn-vol-up').toggleClass('disabled', (new_vol == 1));
	
	vm_player_volume = new_vol;
	objPlayer.jPlayer('volume', new_vol);
}


/*--------------------------------------------------------------------------
 * vm_set_vm_player_state() : Update the player UI controls accordingly to the player
 *                         to the player state.
 *
 * Arguments : 
 *   - state : current state of the player
 *
 * Returns   : Nothing
 */
function vm_set_vm_player_state(state)
{
	vm_player_state = state	
	
	
	switch (state) {
		case 'playing':

			$('#vm-popup-btn-stop').toggleClass('disabled', false);
			$('#vm-popup-btn-rew').toggleClass('disabled', false);
			$('#vm-popup-btn-ffwd').toggleClass('disabled', false);
			$('#vm-popup-btn-skip-start').toggleClass('disabled', false);
			$('#vm-popup-btn-play').toggleClass('disabled', false);

			$('#vm-popup-btn-play a img').addClass('icon16-pause');
			$('#vm-popup-btn-play a img').removeClass('icon16-play');
			
			break;

		case 'paused':
			$('#vm-popup-btn-stop').toggleClass('disabled', false);
			$('#vm-popup-btn-rew').toggleClass('disabled', false);
			$('#vm-popup-btn-ffwd').toggleClass('disabled', false);
			$('#vm-popup-btn-skip-start').toggleClass('disabled', false);
			$('#vm-popup-btn-play').toggleClass('disabled', false);

			$('#vm-popup-btn-play a img').addClass('icon16-play');
			$('#vm-popup-btn-play a img').removeClass('icon16-pause');

			break;

		default:
			$('#vm-popup-btn-stop').toggleClass('disabled', true);
			$('#vm-popup-btn-rew').toggleClass('disabled', true);
			$('#vm-popup-btn-ffwd').toggleClass('disabled', true);
			$('#vm-popup-btn-skip-start').toggleClass('disabled', true);
			$('#vm-popup-btn-play').toggleClass('disabled', false);

			$('#vm-popup-player-cursor').text('00:00');
			$('#vm-popup-player-total').text('00:00');

			$('#vm-popup-btn-play a img').addClass('icon16-play');
			$('#vm-popup-btn-play a img').removeClass('icon16-pause');
			
			break;
	}
	
}
