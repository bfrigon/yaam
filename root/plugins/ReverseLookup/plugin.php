<?php

if(realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME'])) {
	header('Location:../index.php');
	exit();
}


class PluginReverseLookup extends Plugin
{
	public $_dependencies = array('Tools');


	function on_load() 
	{
		$this->register_tab($this->draw, 'rlookup', 'tools', 'Reverse lookup', 'user');
	}
	
	
	function ajax_lookup()
	{
		$number = (isset($_REQUEST['number']) ? $_REQUEST['number'] : '');
		
		
		
		
		$results = array();
		$results['number'] = $number;
		
		try {
			
			foreach (glob($this->PLUGIN_DIR . '/lookup_src/*.php') as $src_script) {
			
				$src_name = basename($src_script, '.php');
				
				/* Load lookup script */
				include($src_script);
				
				$src_function = 'search_' . $src_name;

				if (!function_exists($src_function))
					throw new Exception('Invalid lookup source. function ' . $src_function . ' does not exists');
				
				if (($result = call_user_func($src_function, $number)) == null)
					continue;
				
				foreach ($result as $key => $value)
					if (!isset($results[$key]))
						$results[$key] = $value;
			}

			/* Prepare address for geocoding lookup */
			$enc_address = '';
			if (isset($results['address']))
				$enc_address .= $results['address'] . ',';
		
			if (isset($results['city']))
				$enc_address .= $results['city'] . ',';

			if (isset($results['state']))
				$enc_address .= $results['state'] . ',';

			if (isset($results['country']))
				$enc_address .= $results['country'] . ',';

			if (isset($results['zip']) && (empty($results['address']) || empty($results['city'])))
				$enc_address .= $results['zip'] . ',';
			
			$results['enc_address'] = $enc_address;
		
		} catch (Exception $e) {
			$results = array('_error', $e->getmessage());
		}
		
		if (!isset($results['name']))
			$results['name'] = 'Unknown';

		if (!isset($results['type']))
			$results['type'] = 'Unknown';

		if (!isset($results['carrier']))
			$results['carrier'] = 'Unknown';

		return $results;
	}
	
	
	function draw($template, $tab_id, $tab_path)
	{
		$number = (isset($_REQUEST['number']) ? $_REQUEST['number'] : '');
		$static = (isset($_REQUEST['js']) ? intval($_REQUEST['js']) != 1 : true);
		
		$number = preg_replace('/[^0-9]/', '', $number);

		$result_address = '';
		$result_carrier = '';
		$result_line_type = '';
		$result_address = '';
		$result_name = '';
		$static_map_url = '';
		
		if ($static) {
		
			if (strlen($number) > 0) {
				$result = $this->ajax_lookup();
				
				$result_name = $result['name'];
				$result_carrier = $result['carrier'];
				$result_line_type = $result['type'];
			} else {
				$result = array();
			}
		
			$map_address = urlencode(!empty($result['enc_address']) ? $result['enc_address'] : 'North america');
		
			$static_map_url = 'http://maps.googleapis.com/maps/api/staticmap?markers=' . $map_address . '&size=450x450&sensor=false';
		

		
			if (isset($result['address']))
				$result_address .= $result['address'] . '<br />';
			
			$address = array();
		
			if (isset($result['city']))
				array_push($address, $result['city']);

			if (isset($result['state']))
				array_push($address, $result['state']);
			
			if (isset($result['country']))
				array_push($address, $result['country']);
		
			if (count($address) > 0) 
				$result_address .= implode(', ', $address) . '<br />';
		
			if (isset($result['zip']))
				$result_address .= $result['zip'];
		}		
	
		require($template->load('template.tpl'));
		$this->include_js_script('map.js');
	}

}

?>
