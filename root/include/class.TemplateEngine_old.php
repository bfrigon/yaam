<?php
//******************************************************************************
// class.TemplateEngine.php - Template engine
// 
// Project   : Asterisk Y.A.A.M (Yet another asterisk manager)
// Version   : 0.1
// Author    : Benoit Frigon
// Last mod. : 7 oct. 2012
// 
// Copyright (c) 2011 - 2012 Benoit Frigon <bfrigon@gmail.com>
// www.bfrigon.com
// All Rights Reserved.
//
// This software is released under the terms of the GNU Lesser General Public 
// License v2.1. 
// A copy of which is available from http://www.gnu.org/copyleft/lesser.html
// 
//******************************************************************************

define('BLOCK_FOREACH', 0x1);
define('BLOCK_FOREACH_IF', 0x02);
define('BLOCK_REMAINING', 0x04);
define('BLOCK_RESULTSET', 0x08);
define('BLOCK_IS', 0x10);


define('TOKEN_FUNCTION', 0x1);
define('TOKEN_STRING', 0x2);
define('TOKEN_VARIABLE', 0x4);
define('TOKEN_NUMBER', 0x8);
define('TOKEN_ANY', TOKEN_FUNCTION | TOKEN_STRING | TOKEN_VARIABLE | TOKEN_NUMBER);


class TemplateEngine
{
	private $template_dir = 'cache';		/* Default template directory */
	private $cache_dir = 'template';		/* Default cache directory */
	private $blocks;						/* Block stack */
	private $unique_base;
	private $unique_id = 0;
	public $compile_time = 0;
	
	private $functions = array();
	
	public $currency_format = '%i $';

	/*--------------------------------------------------------------------------
	 * __construct()
	 *
	 * Arguments : 
	 * 	- $template_dir : Template directory
	 * 	- $cache_dir    : Compiled template directory
	 *
	 * Return    : None
	 */
	function __construct($template_dir=null, $cache_dir=null) {
		$this->template_dir = ($template_dir != null) ? $template_dir : dirname(__FILE__) . '/../templates';
		$this->cache_dir = ($cache_dir != null) ? $cache_dir : dirname(__FILE__) . '/../cache';
		
	}


	/*--------------------------------------------------------------------------
	 * load() : Load the precompiled template or compile it if non-existant.
	 *
	 * Arguments : 
	 * 	- $template_name : Template file to load (relative to the template directory)
	 *
	 * Returns   : Filename of the compiled template
	 */
	function load($template_name, $use_theme=false)
	{
	
		if ($use_theme)
			$template_file = DOCUMENT_ROOT . '/themes/' . $_SESSION['ui_theme'] . '/templates/' . $template_name;
		else	
			$template_file = $this->template_dir . '/' . $template_name;

			
		$cache_file = $this->cache_dir . '/' . md5($template_file) . '.php';

		if (($template_mtime = @filemtime($template_file)) === False)
			throw new Exception('Can\'t load the template file (' . $template_file . ')');
			
		//$template_mtime = time();
	
		if (($cache_mtime = @filemtime($cache_file)) !== False && $template_mtime < $cache_mtime)
			return $cache_file;
	
		/* (re)-compile the template */
		$this->compile($template_file, $cache_file);
		
		return $cache_file;
	}


	/*--------------------------------------------------------------------------
	 * compile(): Template compiler
	 *
	 * Arguments : 
	 * 	- $template_name : Template file to load (relative to the template directory)
	 *
	 * Returns   : Filename of the compiled template
	 */
	function compile($template, $cache)
	{
		$compile_start = microtime(true);
	
		/* Reset block stack */
		$this->blocks = array();
		$this->unique_base = hash('crc32b', $template);
			
		/* Open template file */
		$fh_template = @fopen($template, 'r');
		if (!$fh_template)
			throw new Exception('Template file not found (' . $template . ')');
		
		/* Open cache file */
		$fh_cache = @fopen($cache, 'w');
		if (!$fh_cache)
			throw new Exception('Cannot compile template! <br/>The cache directory does not exists or does not have write permissions.');
		
		while(!feof($fh_template)) {
			$line = fgets($fh_template);
			$line = preg_replace_callback('/{{\s*((?:[^}]*?"[^"\\\\]*(?:\\\\.[^"\\\\]*)*")*[^}]*)}}/', array($this, 'parse_tag_callback'), $line);
			
			fputs($fh_cache, $line);
		}
		
		fclose($fh_cache);
		fclose($fh_template);
		
		$this->compile_time = microtime(true) - $compile_start;
		printf('Compile time: %0.4f s', $this->compile_time);
	}
	
	
	/*--------------------------------------------------------------------------
	 * parse_tag_callback() : 
	 *
	 * Arguments : 
	 * 	- $matches : 
	 *
	 * Returns   : Filename of the compiled template
	 */
	function parse_tag_callback($matches)
	{
		$tag = $matches[1];
	
		/* Ignore comments */
		if ($tag[0] == '*')
			return null;

		$regex = '#(\$\w+(?:\[[^\]]+\])*|/?\w+)|"([^"\\\\]*(?:\\\\.[^"\\\\]*)*)"#i';
		if (!preg_match_all($regex, $tag, $tokens, PREG_SET_ORDER))
			return null;
		
		$use_echo = false;
		$output = '';
		
		for($i=0; $i<count($tokens); $i++) {
		
			$token = $this->get_token($tokens, $i, TOKEN_FUNCTION | TOKEN_VARIABLE);
			if (is_null($token))
				continue;
			
		
			/*===================================================================*/
			/* Variable */
			/*===================================================================*/		
			if ($token[0] == '$') {
				if (!empty($output))
					continue;
					
				$output = $token;
				$use_echo = true;
				
				continue;
			}

			/*===================================================================*/
			/* Functions */
			/*===================================================================*/					
			switch ($token) {
			
				/*------------------------------------------------- */
				/* Foreach block
				/*------------------------------------------------- */
				case 'foreach':
					if (!empty($output))
						return null;

					$var = $this->get_unique_varname();
					
					$array = $this->get_token($tokens, $i+1, TOKEN_VARIABLE);
					if (is_null($array))
						return null;
					
					array_push($this->blocks, array(BLOCK_FOREACH_IF, $array));
					array_push($this->blocks, array(BLOCK_FOREACH, $var));
				
					$output = "if (!empty($array)) { foreach($array as $var) {";
					$use_echo = false;
					
					/* Ignore remaining tokens */
					$i = count($tokens);
					break;
				

				/*------------------------------------------------- */
				/* read gzip file content
				/*------------------------------------------------- */
				case 'dumpgzfile':
					$filename = $this->get_token($tokens, $i+1, TOKEN_STRING | TOKEN_VARIABLE);
					if (is_null($filename))
						return null;

					$output = "dumpgzfile($filename)";
					$use_echo = false;

					/* Ignore remaining tokens */
					$i = count($tokens);
					break;
					
				
				
				/*------------------------------------------------- */
				/* ODBC Result set
				/*------------------------------------------------- */
				case 'resultset':
					
					$results = $this->get_token($tokens, $i+1, TOKEN_STRING | TOKEN_VARIABLE);
					
					array_push($this->blocks, array(BLOCK_RESULTSET, $results));
					
					$output = "while (odbc_fetch_row($results)) {";
									
					/* Ignore remaining tokens */
					$i = count($tokens);
					break;
				
				
				/*------------------------------------------------- */
				/* End block
				/*------------------------------------------------- */
				case '/foreach':
				case '/resultset':
				case '/is':
				case 'end':
					if (!empty($output))
						return null;
						
					list($type, $var) = array_pop($this->blocks);
					
					switch($type) {
						case BLOCK_FOREACH:
							array_pop($this->blocks);
							$output = '}}';
							break;
							
						case BLOCK_FOREACH_IF:
						case BLOCK_RESULTSET:
						case BLOCK_REMAINING:
						case BLOCK_IS:
							$output = '}';
							break;
							
						default:
							return null;
					}
					
					/* Ignore remaining tokens */
					$i = count($tokens);
					break;
				
				
				/*------------------------------------------------- */
				/* Else block
				/*------------------------------------------------- */
				case 'else':
					list($type) = end($this->blocks);
					
					switch($type) {
						case BLOCK_FOREACH:
							array_pop($this->blocks);
							$output = '}} else {';
							break;
					
						case BLOCK_IS:
							$output = '} else {';
							break;
					
						default:
							return null;
					}
					
					/* Ignore remaining tokens */
					$i = count($tokens);
					break;
					
					
				/*------------------------------------------------- */
				/* Column statement
				/*------------------------------------------------- */
				case 'column':
					list($type, $var) = $this->block_find_nearest(BLOCK_FOREACH | BLOCK_RESULTSET);
					
					switch(intval($type)) {
						case BLOCK_FOREACH:
							$key = $this->get_token($tokens, $i+1, TOKEN_STRING | TOKEN_NUMBER);
							if (is_null($key))
								return null;

							$output = $var . '[' . $key . ']';
							$use_echo = true;
							break;
						
						case BLOCK_RESULTSET:
							$column = $this->get_token($tokens, $i+1, TOKEN_STRING);
							if (is_null($column))
								return null;

							$output = "odbc_result($var,$column)";
							$use_echo = true;
							break;
							
						default:
							return null;
					}
		
					$i++;
					break;


				/*------------------------------------------------- */
				/* Row statement
				/*------------------------------------------------- */
				case 'row':
					list($type, $var) = $this->block_find_nearest(BLOCK_FOREACH);

					switch(intval($type)) {
						case BLOCK_FOREACH:
							$output = $var;
							$use_echo = true;
							break;
							

						default:
							return null;
					}
		
					break;
				

				/*------------------------------------------------- */
				/* Conditional echo
				/*------------------------------------------------- */
				case 'is':
					$cond = $this->get_token($tokens, $i+1, TOKEN_STRING | TOKEN_VARIABLE);
					if (is_null($cond))
						return null;

					$cond_true = $this->get_token($tokens, $i+2, TOKEN_STRING | TOKEN_VARIABLE);
					if (is_null($cond_true))
						return null;
						
					$cond_false = $this->get_token($tokens, $i+3, TOKEN_STRING | TOKEN_VARIABLE);
					if (is_null($cond_false))
						$cond_false = '\'\'';
					
					$cond = trim($cond, '\'');
					
					
					$output = "($cond)?$cond_true:$cond_false";
					$use_echo = true;
					break;


				case 'is_phonenumber':
					$output = "if (is_valid_phonenumber($output)) {";
					$use_echo = false;
					
					array_push($this->blocks, array(BLOCK_IS, null));
					break;
				
				case 'if_empty':
					$default = $this->get_token($tokens, $i+1, TOKEN_STRING);
					if ($default == null)
						return null;
				
					$output = "!empty($output)?$output:$default";
					
					$i++;
					break;


				
				/*------------------------------------------------- */
				/* Make a string lowercase
				/*------------------------------------------------- */
				case 'lower':
					$output = "strtolower($output)";
					break;
			
				
				/*------------------------------------------------- */
				/* Make a string uppercase
				/*------------------------------------------------- */
				case 'upper':
					$output = "strtoupper($output)";
					break;


				/*------------------------------------------------- */
				/* Make a string uppercase
				/*------------------------------------------------- */
				case 'ucwords':
					$output = "ucwords($output)";
					break;


				/*------------------------------------------------- */
				/* Make a string lowercase
				/*------------------------------------------------- */
				case 'ucfirst':
					$output = "ucfirst($output)";
					break;


				/*------------------------------------------------- */
				/* Alternate between two strings
				/*------------------------------------------------- */
				case 'altern':
					$id = $this->get_token($tokens, $i+1, TOKEN_FUNCTION);
					if (is_null($id))
						return null;

					$cond_true = $this->get_token($tokens, $i+2, TOKEN_STRING | TOKEN_VARIABLE);
					if (is_null($cond_true))
						return null;

					$cond_false = $this->get_token($tokens, $i+3, TOKEN_STRING | TOKEN_VARIABLE);
					if (is_null($cond_false))
						$cond_false = '\'\'';
						
					$var = '$alt_' . $id;
				
					$output = "if (!isset($var)) $var=0; echo ($var=!$var) ? $cond_true:$cond_false";
					$use_echo = false;
					
					/* Ignore remaining tokens */
					$i = count($tokens);
					break;
					
				case 'remaining':
					if (!empty($output))
						return null;
						
					list($type, $array) = array_pop($this->blocks);
					
					switch($type) {
						case BLOCK_FOREACH:
							list($type, $array) = array_pop($this->blocks);
							$output = '}}';
							break;
							
						case BLOCK_FOREACH_IF:
						case BLOCK_RESULTSET:
							$output = '}';
							break;
							
						default:
							return null;
					}
					
					$number = $this->get_token($tokens, $i+1, TOKEN_NUMBER | TOKEN_VARIABLE);

					$var = $this->get_unique_varname();
					
					array_push($this->blocks, array(BLOCK_REMAINING, $var));
					
					$output .= " $var=max(0, $number-@count($array)); while ($var-- > 0) {";
					
					
					
					/* Ignore remaining tokens */
					$i = count($tokens);
					break;
					
					
				/*------------------------------------------------- */
				/* Convert special characters to HTML entities
				/*------------------------------------------------- */
				case 'tohtml':
					$output = "htmlspecialchars($output)";
					break;
					
					
				/*------------------------------------------------- */
				/* Currency format
				/*------------------------------------------------- */
				case 'money_format':
				case 'format_money':
					$output = "money_format('$this->currency_format', $output)";
					break;
					
				
				/*------------------------------------------------- */
				/* Regex
				/*------------------------------------------------- */
				case 'regex':
					if (empty($output))
						return null;
				
					$regex = $this->get_token($tokens, ++$i, TOKEN_STRING | TOKEN_VARIABLE);
					if (is_null($regex))
						return null;
					
					$results = $this->get_unique_varname();
					
					$vars = array();
					while ($i < count($tokens))
						array_push($vars, $this->get_token(&$tokens, ++$i, TOKEN_VARIABLE));
					
					
					$vars = implode(',', $vars);
					$num_vars = count($vars);
					
					$output = "preg_match($regex,$output,$results); @list($vars) = $results;";
					
					$use_echo = false;
					
					break;
					
				case 'format_phone':
					if (empty($output))
						return null;
				
					$output = "format_phone_number($output)";
					break;
					
				case 'format_seconds':
					if (empty($output))
						return null;

					$output = "format_time_seconds($output)";
					break;
					
				case 'json':
					$output = "json_encode($output)";

					/* Ignore remaining tokens */
					$i = count($tokens);
					break;
			}
		}
		
		if (empty($output))
			return null;
		
		if ($use_echo)
			return "<?=$output?>";
		else
			return "<?php $output ?>";
	}
	
	
	/*--------------------------------------------------------------------------
	 * get_token()
	 *
	 * Arguments : 
	 * 	- &$tokens      : Token list
	 *  - $offset       : Offset in the token list
	 *  - $allowed_type : Allowed token types
	 *
	 * Returns   : Token or null if the token type is not allowed.
	 */
	function get_token(&$tokens, $offset, $expected_type)
	{
		if (!isset($tokens[$offset]))
			return null;

		$token = $tokens[$offset][0];
		
		if ($token[0] == '$') {
			if ($expected_type & TOKEN_VARIABLE)
				return $token;
			else
				return null;
				
		} else if ($token[0] == '"') {
			if ($expected_type & TOKEN_STRING) {
				$string = $tokens[$offset][2];
			
				return '\'' . str_replace('\"', '"', $string) . '\'';
			} else {
				return null;
			}

		} else if (is_numeric($token)) {
			if ($expected_type & TOKEN_NUMBER)
				return $token;

			
		}				

		if (($expected_type & TOKEN_STRING) && !($expected_type & TOKEN_FUNCTION))
			return '\'' . $token . '\'';
			
		else if ($expected_type & TOKEN_FUNCTION)
			return $token;
				
				
		
		return null;
	}
	
	
	/*--------------------------------------------------------------------------
	 * block_find_nearest
	 * Find the nearest block of the specified types from the top of the block stack.
	 *
	 * Arguments : 
	 * 	Types : types of block to find
	 *
	 * Returns   : Found block or NULL if none was found.
	 */	
	function block_find_nearest($types)
	{
		end($this->blocks);

		while (($block = current($this->blocks)) != null) {
			
			list($type) = $block;
			if ($types & $type)
				return $block;
			
			prev($this->blocks);
		}
		
		return $block;
	}
	
	
	/*--------------------------------------------------------------------------
	 * get_unique_varname
	 *
	 * Arguments : 
	 * 	None
	 *
	 * Returns   : Unique variable name.
	 */
	function get_unique_varname()
	{
		return '$v' . $this->unique_base . $this->unique_id++;
	}
	
}
?>
