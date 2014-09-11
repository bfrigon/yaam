//******************************************************************************
// map.js - ReverseLookup plugin map
// 
// Project   : Asterisk Y.A.A.M (Yet another asterisk manager)
// Version   : 0.1
// Author    : Benoit Frigon
// Last mod. : 3 mar. 2013
// 
// Copyright (c) 2011 - 2013 Benoit Frigon <bfrigon@gmail.com>
// All Rights Reserved.
//
// This software is released under the terms of the GNU Lesser General Public 
// License v2.1. 
// A copy of which is available from http://www.gnu.org/copyleft/lesser.html
// 
//******************************************************************************
var objMap = null, objMarker = null, objInfoWindow = null, objRect = null;
var CurrentLocation = null;

var objxhrlookup = null;



//*****************************************************************************
//
// Events
//
//*****************************************************************************

// ----------------------------------------------
// Event: Document ready
// ----------------------------------------------
$(document).ready(function () {

	$('#dialog_map_lookup').dialog({
		parent: $('#map_viewport'),
		modal: true,
		width: 200,
	});


	$('.page#tab_tools').one('tab.aftershow', function(event) {
		if (objMap == null) {
			map_initialize();
			map_lookup_number();
		}		
	});

	$('#toolbar_map #btn_lookup').on('click', function(event) {
		map_lookup_number();

		event.preventDefault();
  		event.stopPropagation();
		return false;
	});

	$('#toolbar_map #btn_clear').on('click', function(event) {
		map_reset(true);
		
		event.preventDefault();
  		event.stopPropagation();
		return false;
	});
	
	/* Number text field keypress event */
	$("#number").keypress(function(event) {
	    if (event.keyCode == 13) {
	    	map_lookup_number();

			event.preventDefault();
	  		event.stopPropagation();
			return false;
		}
	});	
});




/*--------------------------------------------------------------------------
 * map_initialize() : Initialize google maps api
 *
 * Arguments : None
 *
 * Returns   : Nothing
 */
function map_initialize() {

	objMap = new google.maps.Map(document.getElementById("map_viewport"), {
		noClear: true,
		mapTypeId: google.maps.MapTypeId.ROADMAP,
		zoom: 10, 
		overviewMapControl: true,
		overviewMapControlOptions: {
			opened: true
		},
		mapTypeControl: true,
		mapTypeControlOptions: {
			style: google.maps.MapTypeControlStyle.DROPDOWN_MENU
		}
	});
	
	objMarker = new google.maps.Marker();
	objInfoWindow = new google.maps.InfoWindow();

	objRect = new google.maps.Rectangle({
		strokeColor: "#0000FF",
		strokeOpacity: 0.4,
		strokeWeight: 3,
		fillColor: "#0000FF",
		fillOpacity: 0.15
	});

	google.maps.event.addListener(objMarker, 'click', function() {
  		objInfoWindow.open(objMap, objMarker);
  	});
  	
  	google.maps.event.addListener(objRect, 'click', function() {
  		objInfoWindow.open(objMap, objMarker);
  	});
	
	map_reset();
}


/*--------------------------------------------------------------------------
 * map_reset() : 
 *
 * Arguments : None
 *  - reset_controls : 
 *
 * Returns   : Nothing
 */
function map_reset(reset_controls) {
	if (objMap == null) {
		alert('The Google map API was not initialized.');
		return;
	}

	if (reset_controls)
		$("#toolbar_map #txt_number").attr("value", '');
	
	
	$('#toolbar_map #no_result').hide();


	objMap.getStreetView().setVisible(false);
	
	objMap.setMapTypeId(google.maps.MapTypeId.ROADMAP);

	viewport = new google.maps.LatLngBounds(
		new google.maps.LatLng(65, -160), 
		new google.maps.LatLng(30,-50));

	map_set_location(null, viewport, null, null);

	objInfoWindow.close();
	objMarker.setVisible(false);
}




/*--------------------------------------------------------------------------
 *  map_cancel_lookup() : 
 *
 * Arguments : None
 *
 * Returns   : Nothing
 */
function map_cancel_lookup() {
	
	if (objxhrlookup != null)
		objxhrlookup.abort();
		
	map_lookup_done(false);
}



/*--------------------------------------------------------------------------
 *  map_lookup_done() : 
 *
 * Arguments : None
 *
 * Returns   : Nothing
 */
function map_lookup_done(results) {
	$('#dialog_map_lookup').dialog('close');

	if (!results) {
		$('#toolbar_map #no_result').show();
		$('#toolbar_map #no_result').effect('highlight', {}, 2000);
	} else {
		$('#toolbar_map #no_result').hide();
	}
	
}



/*--------------------------------------------------------------------------
 * map_loopup_number() : 
 *
 * Arguments : None
 *
 * Returns   : Nothing
 */
function map_lookup_number() {
	if (objMap == null) {
		alert('The Google map was not initialized.');
		return;
	}

	number =$('#toolbar_map #txt_number').attr("value");
	if (number.length == 0)
		return;
	
	number = number.replace(/[^0-9]/g, '');


	$('#dialog_map_lookup #lookup_number').html(format_phone_number(number));
	
	$('#dialog_map_lookup').dialog('show');

	objxhrlookup = $.ajax({
		url: 'ajax.php?function=ReverseLookup/lookup&output=json&number=' + number,
		dataType: 'json',
		success: function(data) {
			objxhrlookup = null;
		
			if (data._error != undefined) {
				alert(data._error);
				return;
			}
			
			if (data.enc_address.length == 0) {
				map_lookup_done(false);
				return;	
			}
			
			map_find_location(data);
		}
	});
}



/*--------------------------------------------------------------------------
 * map_set_location() : 
 *
 * Arguments : 
 *  - location :
 *  - viewport :
 *  - approx   :
 *
 * Returns   : Nothing
 */
function map_set_location(location, viewport, approx, data)
{
	objRect.setMap(null);

	objMap.fitBounds(viewport);
	objMap.getStreetView().setVisible(false);
	
	CurrentLocation = location;
	
	if (location != null) {
		map_lookup_done(true);
	
		objMap.setCenter(location);
		
		objMarker.setPosition(location);
		objMarker.setVisible(true);
		objMarker.setMap(objMap);

		objInfoWindow.setPosition(location);
		objInfoWindow.open(objMap);

		if (approx) {
			objRect.setBounds(viewport)
			objRect.setMap(objMap);
		}
	}
	
	if (data != null) {
		objResultInfo = $('#map_result_info');
		objResultInfo.addClass('results');
	
		if (objResultInfo.length == 0)
			return;

		var address = '';
		if (data.address != undefined)
			address += data.address + '<br />';
		
		address_comp = [];
		if (data.city != undefined)
			address_comp.push(data.city);
		
		if (data.state != undefined)
			address_comp.push(data.state);
		
		if (data.country != undefined)
			address_comp.push(data.country);
		
		if (address_comp.length > 0)
			address += address_comp.join(', ') + '<br />';
		
		if (data.zip != undefined)
			address += data.zip;
	
		objResultInfo.find('#result_number').html(format_phone_number(data.number));
		objResultInfo.find('#result_name').html(data.name);
		objResultInfo.find('#result_address').html(address);
		objResultInfo.find('#result_carrier').html(data.carrier);
		objResultInfo.find('#result_line_type').html(data.type);

		objResultInfo.show();

		objInfoWindow.setContent(objResultInfo.parent().html());
	}
}


/*--------------------------------------------------------------------------
 * map_find_location() :
 *
 * Arguments : 
 *  - data :
 *
 * Returns   : Nothing
 */
function map_find_location(data) {
	console.log('Geocoding address requested: ' + data.enc_address);
	
	var geocoder = new google.maps.Geocoder();
	geocoder.geocode({'address': data.enc_address}, function (results, status) {

		if (status != google.maps.GeocoderStatus.OK) {
			map_lookup_done(false);
		
			console.log('Geocoder returned status : ' + status);
			return;
		}
		
		for (i=0; i < results.length; i++) {
			type = results[i].address_components[0].types[0];

			if (data.address != undefined)
				break;

			if (data.address == undefined && (type == 'locality' || type == 'neighborhood' || type == 'sublocality'))
				break;
		}
		
		result = results[i >= results.length ? 0 : i];
		
		
		if (result.address_components.length > 0) {	
			data.address = '';
			for (i=0; i < result.address_components.length; i++) {
				comp = result.address_components[i];
		
				switch (comp.types[0]) {
					case 'locality':
					case 'sublocality':
					case 'neighborhood':
						data.city = comp.long_name;
						break;
					
					case 'postal_code':
						data.zip = comp.long_name;
						break;
					
					case 'street_number':
					case 'intersection':
						data.address += comp.long_name + ' ';
						break;
						
					case 'route':
						data.address += comp.long_name;
						break;
						
					case 'country':
						data.country = comp.long_name;
						break;
						
					case 'street_address':
						data.address = comp.long_name;
						break;
				}
			}
		}
		
		if (data.address != undefined && data.address.length == 0)
			delete data.address;
			
		is_approx = (result.geometry.location_type == google.maps.GeocoderLocationType.APPROXIMATE);
		
		map_set_location(result.geometry.location, result.geometry.viewport, is_approx, data);
	});
}


function format_phone_number(number) {
	var regexObj = /^(?:\+?1[-. ]?)?(?:\(?([0-9]{3})\)?[-. ]?)?([0-9]{3})[-. ]?([0-9]{4})$/;

	if (number.indexOf("*") != -1)
		return number;
	
	if (regexObj.test(number)) {
		var parts = number.match(regexObj);
		var phone = "";
		
		if (number.length == 11)
			phone += '1 ';
		
		if (parts[1])
			phone += "(" + parts[1] + ") ";
			
		phone += parts[2] + "-" + parts[3];
		return phone;

	} else {
		//invalid phone number
		return number;
	}
}

