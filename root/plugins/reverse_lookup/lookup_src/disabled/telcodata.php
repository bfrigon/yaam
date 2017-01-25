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

function search_telcodata($number)
{
	$agent = 'Mozilla/5.0 (Linux; U; Android 4.1.1; en-us; sdk Build/JR003E) AppleWebKit/534.3 (KHTML, like Gecko) Version/4.0 Mobile Safari/534.30';

	$fc = get_url_contents('http://api.telcodata.us/jsonfree?number=' . $number);
	$data = json_decode($fc);

	if ($data == NULL || $data->ratecenter == 'INVALID')
		return null;

	$result = array();

	switch ($data->type) {
		case 'PCS':
		case 'WIRELESS':
		case 'WRSL':
			$result['type'] = 'Cellular';	
			break;
			
		case 'ICO':
		case 'RBOC':
			$result['type'] = 'Land line';
			break;	
		
		case 'CLEC':
		case 'LRSL':
			$result['type'] = 'VOIP / ind.';
	}
	
	$result['state'] = $data->state;
	$result['carrier'] = ucwords(strtolower($data->company));
	
	if (!preg_match('#[0-9]#',$data->ratecenter))
		$result['city'] = ucwords(strtolower($data->ratecenter));
	
	if ($data->lat != NULL && $data->lon != NULL)
		$result['cord'] = $data->lat . ',' . $data->lon;
	
	
	
	if ($data->zip != NULL && intval($data->zip) > 0)
		$result['zip'] = $data->zip;

	return $result;
}

?>
