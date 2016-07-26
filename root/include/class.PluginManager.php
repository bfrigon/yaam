<?php
//******************************************************************************
// class.PluginManager.php - plugin manager (loader)
// 
// Project   : Asterisk Y.A.A.M (Yet another asterisk manager)
// Version   : 0.1
// Author    : Benoit Frigon
// Last mod. : 3 mar. 2013
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


class PluginManager
{
	public $_plugins = array();
	public $_tabs = array();
	

	/*--------------------------------------------------------------------------
	 * load() : Load a plugin
	 *
	 * Arguments : 
	 * 	- $name : plugin name. If null, loads all available plugins.
	 *
	 * Returns   : Plugin instance.
	 */
	function load($plugin_name = NULL)
	{
		if ($this->plugin_loaded($plugin_name))
			return $this->_plugins[$plugin_name];
	
		$plugin_dir = DOCUMENT_ROOT . '/plugins/' . $plugin_name;
		$plugin_def = $plugin_dir . '/plugin.php';

		if (!$this->plugin_exists($plugin_name))
			throw new Exception('Plugin "' . $plugin_name . '" does not exist.');		
				
		require($plugin_def);
		
		$plugin_class = 'Plugin' . $plugin_name;
		$plugin = new $plugin_class($plugin_name, $this->_tabs);
		
		$this->_plugins[$plugin_name] = $plugin;
		
		foreach($plugin->_dependencies as $dep)	{
			if ($dep == $plugin_name)
				throw new Exception('Circular plugin dependency');
		
			if (!$this->plugin_loaded($dep))
				$this->load($dep);
		}
		
		$plugin->on_load();

		return $plugin;
	}
	

	/*--------------------------------------------------------------------------
	 * plugin_exists() : Check if a plugin exists.
	 *
	 * Arguments : 
	 * 	- $name : plugin name to check.
	 *
	 * Returns   : True if plugin exists, false otherwise.
	 */
	function plugin_exists($name)
	{
		$plugin_dir = DOCUMENT_ROOT . '/plugins/' . $name;
		$plugin_def = $plugin_dir . '/plugin.php';
		
		return (@filemtime($plugin_def) !== false);
	}
	
	
	/*--------------------------------------------------------------------------
	 * plugin_loaded() : Check if the plugin is loaded
	 *
	 * Arguments : 
	 * 	- $name : plugin name.
	 *
	 * Returns   : True if plugin was loaded, false otherwise
	 */
	function plugin_loaded($name)
	{
		return isset($this->_plugins[$name]);
	}
	
	
	/*--------------------------------------------------------------------------
	 * sort_tabs() : Sorts tabs according to priority.
	 *
	 * Arguments : None
	 *
	 * Returns   : Nothing
	 */
	function sort_tabs()
	{
		uasort($this->_tabs, array('PluginManager', 'cmp_tab'));	
	}
	
	/*--------------------------------------------------------------------------
	 * load() : plugin sort callback
	 *
	 * Arguments : 
	 * 	- $a : plugin A
	 *  - $b : plugin B
	 *
	 * Returns   : Compare result
	 */
	private static function cmp_tab($a, $b)
	{	
		
		return ($a['order'] < $b['order']) ? -1 : 1;
	}
	

	/*--------------------------------------------------------------------------
	 * show_tab_content() : Print tab content.
	 *
	 * Arguments : 
	 * 	- $path : Tab path to print (PLUGIN_NAME.TAB[.CHILD])
	 *
	 * Returns   : Nothing
	 */
	function show_tab_content($path)
	{
		$path = preg_replace("_.*(/|\\\\)_", "", $path);
		$path_item = explode('.', $path, 3);
		
		/* Load the plugin that correspond to the requested path */
		$plugin = $this->load($path_item[0]);

		$plugin->REQUEST_JS_ENABLED = isset($_REQUEST['js']);

		
		if (!isset($path_item[1]))
			throw new Exception('Page not found (' . $path . ')');
		
		$tab = &$plugin->_tabs[$path_item[1]];
		
		if (isset($path_item[2]))
			$tab = &$tab['childs'][$path_item[2]];
		
		if (!isset($tab))
			throw new Exception('Page not found (' . $path . ')');

		
		$action = (isset($_REQUEST['action'])) ? $_REQUEST['action'] : '';
		
		/* IE7 submit 'bug' workaround */
		if (substr($action, 0, 4) == '<!--')
			$action = substr($action, 4, strpos($action, '-->', 4) - 4);

		/* Filter URI */
		$uri = $_GET;
		unset($uri['path'], $uri['page'], $uri['action'], $uri['output'], $uri['js']);
		$uri = http_build_query($uri);

		/* Initialize template engine */
		$template = new TemplateEngine($path, $plugin->PLUGIN_DIR);
		
		if (isset($tab['callback']))
			$plugin->$tab['callback']($template, $path, $action, $uri);
	}
}

?>