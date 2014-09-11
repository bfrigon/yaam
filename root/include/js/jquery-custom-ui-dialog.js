//******************************************************************************
// jquery-custom-ui-dialog.js - JQuery custom dialog plugin
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

(function( $ ){
	var instance_count = 0;


	//=========================================================================
	// Methods
	//=========================================================================
	var methods = {
	
		//---------------------------------------------------------------------
		// Initialize
		//---------------------------------------------------------------------
		init: function(options) {
		
			
		
			/* defaults options */
			var options = $.extend({
				parent: window,
				modal: true,
				width: 300,
				fadein: 250,
				closebtn: false,
			}, options);
			
			this.data('dialog', options);

			return this.each(function () {
			
				if (options.parent.find('.overlay').length == 0) {
					
					var objOverlay = $('<div/>')
						.addClass('overlay')
						.css({
							'position': 'absolute',
							'left' : 0,
							'top' : 0,
							'right' : 0,
							'bottom' : 0,
							'z-index' : 9999
						});
					
					options.parent.append(objOverlay);
					
					options.parent.css({'position':'relative'});
				}
				
				objTitle = $(this).find('h1');
				
				if (options.closebtn && objTitle.length > 0 && objTitle.find('.close').length == 0) {
					
					objClosebtn = $('<a>')
						.addClass('close')
						.attr('href', 'javascript:void(0);');
					
					objClosebtn.on('click', function() {
						
						objDialog = $(this).closest('.popup');

						methods.close.apply(objDialog);
					});
					
					objTitle.append(objClosebtn);
				}
				
				
				$(this)
					.addClass('popup')
					.css({
						'position': 'absolute',
						'z-index': 10000,
					})
			});		
		},
		
		//---------------------------------------------------------------------
		// Show dialog
		//---------------------------------------------------------------------
		show: function() {
			if (!(options = this.data('dialog'))) {
				methods.init.apply(this);
				
				if (!(options = this.data('dialog')))
					return;
			}
			
			this.width(options.width);
			
			/* Show the overlay */
			if (options.modal) {
				options.parent.find('.overlay')
					.stop().hide().fadeIn(options.fadein);
			}

			this.show();
			
			this.position({
				my: 'center',
				at: 'center',
				of: options.parent
			});
			

			/* Trigger openned dialog event */
			$(this).trigger('dialog.open');
		},
			
		//---------------------------------------------------------------------
		// Close dialog
		//---------------------------------------------------------------------			
		close: function() {
		
			if (!(options = this.data('dialog')))
				return this;
			
			$(this).hide();
			
			options.parent.find('.overlay').
				hide();
				
			/* Trigger closed dialog event */
			$(this).trigger('dialog.close');
		}
		
	};


	$.fn.dialog = function( method ) {
    
		// Method calling logic
		if ( methods[method] ) {
		  return methods[ method ].apply( this, Array.prototype.slice.call( arguments, 1 ));
		  
		} else if ( typeof method === 'object' || ! method ) {
		  return methods.init.apply( this, arguments );
		  
		} else {
		  $.error( 'Method ' +  method + ' does not exist on jQuery.ui.dialog' );
		}    
  	};


})( jQuery );
