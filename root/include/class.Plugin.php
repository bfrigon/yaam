<?php
//******************************************************************************
// class.plugin.php - Base Plugin class
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


class Plugin
{
	public $_dependencies = array();
	public $_tabs = array();

	public $REQUEST_JS_ENABLED = false;
	public $PLUGIN_DIR = '';
	public $NAME = '';
	

	/*--------------------------------------------------------------------------
	 * Plugin() : initialize class instance
	 *
	 * Arguments : 
	 * 	- $name : plugin name
	 *  - &$tabs : Global tabs collection
	 *
	 * Returns   : Nothing
	 */
	function Plugin($name, &$tabs)
	{
		$this->_tabs = &$tabs;
		
		$this->NAME = $name;
		$this->PLUGIN_DIR = DOCUMENT_ROOT . '/plugins/' . $name;
	}


	/*--------------------------------------------------------------------------
	 * on_load() : Called after the plugin manager initialize the plugin.
	 *
	 * Arguments : None
	 *
	 * Returns   : Nothing
	 */
	function on_load()
	{
		
	}
	

	
	/*--------------------------------------------------------------------------
	 * redirect() : Rediret the output to another tab or url.
	 *
	 * Arguments : 
	 *  - $url : new url
	 *
	 * Returns   : Nothing
	 */
	function redirect($url)
	{
		if (!$this->REQUEST_JS_ENABLED) {
			header('location: ' . $url);
			
		} else {
			echo '<script>window.location=convert_tab_url("', $url, '");</script>';
		}
				
		exit();
	}


	/*--------------------------------------------------------------------------
	 * include_js_script() : Include a jacscript file to the tab content output.
	 *
	 * Arguments : 
	 *  - $name : script filename
	 *
	 * Returns   : Nothing
	 */
	function include_js_script($name)
	{
		echo '<script>';
		readfile($this->PLUGIN_DIR . '/' . $name);
		echo '</script>';
	}


	/*--------------------------------------------------------------------------
	 * create_tab() : Create a new tab
	 *
	 * Arguments : 
	 *  - $parent      : Parent tab
	 *  - $id          : New tab id
	 *  - $caption     : Tab caption
	 *  - $permissions : Required permission to open the tab
	 *  - $order       : Tab priority (lower first)
	 *
	 * Returns   : Nothing
	 */
	function register_tab($callback, $id, $parent, $caption, $permissions, $order = 100)
	{
		if ($parent != NULL) {
			if (!isset($this->_tabs[$parent]))
				throw new Exception('Parent tab does not exist');
		
			$tab = &$this->_tabs[$parent];
			
			if (!isset($tab['childs']))
				$tab['childs'] = array();
			
			$tab = &$tab['childs'][$id];
		
		} else {
			$tab = &$this->_tabs[$id];
		}
		
		$tab['id'] = $id;
		$tab['callback'] = $callback;
		$tab['plugin'] = $this->NAME;
		$tab['perm'] = $permissions;
		$tab['caption'] = $caption;
		$tab['order'] = $order;
	}	
}
?>
