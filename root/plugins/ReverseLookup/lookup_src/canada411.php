<?php
/***************************************************************************
 * Y.A.A.M (Yet Another Asterisk Manager)
 *
 * Copyright (c) 2011 - 2012 Benoit Frigon <bfrigon@gmail.com>
 * All Rights Reserved.
 *
 * This software is released under the terms of the GNU Lesser General Public License v2.1
 *  A copy of which is available from http://www.gnu.org/copyleft/lesser.html
 * 
 */

function search_canada411($number)
{
	$result = search_canada411_bus($number);
	if ($result != null)
		return $result;
		
	return search_canada411_res($number);
}


function search_canada411_res($number)
{
	$result = array();

	$fc = get_url_contents('http://mobile.canada411.ca/person/' . $number . '/Canada');

	$regex = '/data-class="(\w*)".*?<h1>([^<]*)<\/h1>.*?<p>([^<]*?)(?:&nbsp;)<\/p>/s';
	preg_match($regex, $fc, $match);

	if (!count($match))
		return null;

	$result['country'] = "Canada";
	$result['name'] = ucwords($match[2]);

	$address = preg_split('/\s*,\s*/', $match[3]);

	if (count($address) < 3) {
		$result['address'] = $match[3];
		$result['city'] = "";
		$result['state'] = "";

	} else {
		$result['address'] = implode(', ', array_slice($address, 0, count($address) - 2));
		$result['city'] = $address[count($address) - 2];
		$result['state'] = $address[count($address) - 1];
	}
	
	return $result;
}


function search_canada411_bus($number)
{
	$result = array();
	
	$api_key = 'sa4f24jwkvrceduxvrq4v6xa';
	
	$fc = get_url_contents('http://api.sandbox.yellowapi.com/FindBusiness/?&what=' . $number . 
			'&where=null&lang=en&fmt=json&apikey=' . $api_key . '&UID=null');
	
	$data = json_decode($fc);

	if (!isset($data->listings[0]))
		return null;
	
	$listing = &$data->listings[0];	
	
	if (isset($listing->name))
		$result['name'] = $listing->name;
	
	if (isset($listing->address->street) && preg_match('#[0-9]#', $listing->address->street))
		$result['address'] = $listing->address->street;
		
	if (isset($listing->address->city))
		$result['city'] = $listing->address->city;
		
	if (isset($listing->address->state))
		$result['state'] = $listing->address->state;
		
	if (isset($listing->address->pcode))
		$result['zip'] = $listing->address->pcode;
	
	if (isset($listing->geoCode->longitude) && isset($listing->geoCode->latitude))
		$result['cord'] = $listing->geoCode->latitude . ',' . $listing->geoCode->longitude;	
	
	$result['country'] = 'Canada';
	
	return $result;
}


?>
