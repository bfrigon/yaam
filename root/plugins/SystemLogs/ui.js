//******************************************************************************
// ui.js - System logs plugin user interface
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



//*****************************************************************************
//
// Events
//
//*****************************************************************************

// ----------------------------------------------
// Event: Document ready
// ----------------------------------------------
$(document).ready(function () {

	$('.page#tab_logs').one('tab.content_change', function (e) {

		$('#log_content').contents().filter(function(){return this.nodeType !== 1;}).each(function() {
	
			if ($(this).text().match(/error|fatal|panic/i)) {
				$(this).wrap('<span class="log-error" />');
			
			} else if ($(this).text().match(/warning/i)) {
				$(this).wrap('<span class="log-warning" />');
			}
		});
	});

});







