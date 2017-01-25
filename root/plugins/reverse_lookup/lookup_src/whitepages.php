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

function search_whitepages($number)
{
	$api_key = '908620353bb8ea655e360d26118256f7';
	
	$fc = get_url_contents('http://api.whitepages.com/reverse_phone/1.0/?phone=' . $number . ';api_key=' . $api_key . ';outputtype=JSON');
	$data = json_decode($fc);
	
	if (!isset($data->result->type))
		return null;
	
	if ($data->result->type != 'success')
		return null;
	
	$result = array();
	
	if (isset($data->listings[0])) {
		$listing = &$data->listings[0];	

		
		if (isset($listing->displayname))
			$result['name'] = $listing->displayname;
	
		if (isset($listing->address->city))
			$result['city'] = $listing->address->city;

		if (isset($listing->address->state))
			$result['state'] = $listing->address->state;
	
		if (isset($listing->address->fullstreet))
			$result['address'] = $listing->address->fullstreet;
	
		if (isset($listing->address->zip))
			$result['zip'] = $listing->address->zip;

		if (isset($listing->address->country)) {
			switch ($listing->address->country) {
				case 'CA':
					$result['country'] = 'Canada';
					break;
					
				case 'US':
					$result['country'] = 'USA';
					break;
					
				default:
					$result['country'] = $listing->address->country;
			}
		}
		
		if (isset($listing->geodata->longitude) && isset($listing->geodata->latitude))
			$result['cord'] = $listing->geodata->latitude . ',' . $listing->geodata->longitude;
			
		
		if (isset($listing->phonenumbers[0])) {
			
			$number_data = &$listing->phonenumbers[0];
			
			if (isset($number_data->carrier))
				$result['carrier'] = $number_data->carrier;
				
			if (isset($number_data->type)) {
				switch($number_data->type) {
					case 'landline':
						$result['type'] = 'Land line';
						break;
						
					case 'mobile':
						$result['type'] = 'Cellular';
						break;
				
				}
			}
		}
	}
	
	return $result;
}

?>
