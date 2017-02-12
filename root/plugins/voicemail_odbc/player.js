//******************************************************************************
//
// Project : Asterisk Y.A.A.M (Yet another asterisk manager)
// Author  : Benoit Frigon <www.bfrigon.com>
//
// Contributors
// ============
//
//
//
// -----------------------------------------------------------------------------
//
// Copyright (c) 2017 Benoit Frigon
//
// Permission is hereby granted, free of charge, to any person obtaining a copy
// of this software and associated documentation files (the "Software"), to deal
// in the Software without restriction, including without limitation the rights
// to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
// copies of the Software, and to permit persons to whom the Software is
// furnished to do so, subject to the following conditions:
//
// The above copyright notice and this permission notice shall be included in
// all copies or substantial portions of the Software.
//
// THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
// IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
// FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
// AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
// LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
// OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
// THE SOFTWARE.
//
//******************************************************************************
var objPlayer = null;
var vm_player_state = 'stopped';
var vm_player_current_pos = 0;
var vm_player_volume = 0.6;


$(document).ready(function() {
    objPlayer = $('#vm_player').jPlayer({
        wmode: "window",
        swfPath: 'include/js/jquery.jplayer.swf',
        solution: "html, flash",
        supplied: 'wav',
        volume: vm_player_volume,

        play: function () {
            vm_set_player_state('playing');
        },

        ended: function() {
            vm_set_player_state('stopped');
            vm_player_current_pos = 0;
        },

        timeupdate: function(event) {
            vm_player_current_pos = event.jPlayer.status.currentTime;

            if (vm_player_state == 'playing') {
                $('#vm_time_cursor').text($.jPlayer.convertTime(event.jPlayer.status.currentTime));
                $('#vm_time_total').text($.jPlayer.convertTime(event.jPlayer.status.duration));
            }
        }
    });

    vm_set_player_state('stopped');


    $('#btn_vm_play').click(function(e) {

        e.preventDefault()

        switch (vm_player_state) {
            case 'stopped':
                filename = $('#btn_vm_download').attr('href');

                objPlayer.jPlayer('setMedia', {wav: filename});
                objPlayer.jPlayer('play');
                break;

            case 'paused':
                objPlayer.jPlayer('play');
                vm_set_player_state('playing');
                break;

            case 'playing':
                objPlayer.jPlayer('pause');
                vm_set_player_state('paused');
                break;
        }
    });


    $('#btn_vm_stop').click(function(e) {
        objPlayer.jPlayer('stop');
        vm_set_player_state('stopped');
    });


    $('#btn_vm_rewind').click(function(e) {
        objPlayer.jPlayer('play', vm_player_current_pos - 10);
    });


    $('#btn_vm_ffwd').click(function(e) {
        objPlayer.jPlayer('play', vm_player_current_pos + 10);
    });

    $('#btn_vm_vol_up').click(function(e) {
        vm_set_volume('up');
    });

    $('#btn_vm_vol_down').click(function(e) {
        vm_set_volume('down');
    });
});



/*--------------------------------------------------------------------------
* vm_set_volume() : Set JPlayer volume
*
* Arguments :
*   - volume : Value between 0.0 and 1.0, 'up' increase volume by 0.20
*              'down' decrease the volume by 0.20
*
* Returns   : Nothing
*/
function vm_set_volume(volume)
{
    if (volume == 'up')
        new_vol = vm_player_volume + 0.20;
    else if (volume == 'down')
        new_vol = vm_player_volume - 0.20;
    else
        new_vol = parseInt(volume);

    if (new_vol < 0)
        new_vol = 0;

    if (new_vol > 1)
        new_vol = 1;

    $('#btn_vm_vol_down').toggleClass('disabled', (new_vol == 0));
    $('#btn_vm_vol_up').toggleClass('disabled', (new_vol == 1));

    vm_player_volume = new_vol;
    objPlayer.jPlayer('volume', new_vol);
}


/*--------------------------------------------------------------------------
* vm_set_player_state() : Update the player UI controls accordingly to the
*                         state of the player.
*
* Arguments :
*   - state : current state of the player
*
* Returns   : Nothing
*/
function vm_set_player_state(state)
{
    vm_player_state = state

    switch (state) {
        case 'playing':
            $('#btn_vm_stop').toggleClass('disabled', false);
            $('#btn_vm_rewind').toggleClass('disabled', false);
            $('#btn_vm_ffwd').toggleClass('disabled', false);
            $('#btn_vm_play').toggleClass('disabled', false);

            $('#btn_vm_play img').addClass('icon16-pause');
            $('#btn_vm_play img').removeClass('icon16-play');
            break;

        case 'paused':
            $('#btn_vm_stop').toggleClass('disabled', false);
            $('#btn_vm_rewind').toggleClass('disabled', false);
            $('#btn_vm_ffwd').toggleClass('disabled', false);
            $('#btn_vm_play').toggleClass('disabled', false);

            $('#btn_vm_play img').addClass('icon16-play');
            $('#btn_vm_play img').removeClass('icon16-pause');
            break;

        default:
            $('#btn_vm_stop').toggleClass('disabled', true);
            $('#btn_vm_rewind').toggleClass('disabled', true);
            $('#btn_vm_ffwd').toggleClass('disabled', true);
            $('#btn_vm_play').toggleClass('disabled', false);

            $('#vm_time_cursor').text('--:--');
            $('#vm_time_total').text('--:--');

            $('#btn_vm_play img').addClass('icon16-play');
            $('#btn_vm_play img').removeClass('icon16-pause');
            break;
    }
}
