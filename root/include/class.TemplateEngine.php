<?php
//******************************************************************************
// class.TemplateEngine.php - Template engine
// 
// Project   : Asterisk Y.A.A.M (Yet another asterisk manager)
// Version   : 0.2
// Author    : Benoit Frigon
// Last mod. : 9 sept. 2013
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

class TemplateEngine
{
	private $template_dir = 'templates';		/* Default template directory */
	private $cache_dir = 'cache';		/* Default cache directory */
	
	private $tab_path = '';
	private $tab_id = '';
	private $plugin_name = '';
	
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
	function __construct($tab_path, $template_dir=null, $cache_dir=null) {
		
		$this->template_dir = ($template_dir != null) ? $template_dir : dirname(__FILE__) . '/../templates';
		$this->cache_dir = ($cache_dir != null) ? $cache_dir : dirname(__FILE__) . '/../cache';
		
		
		$path_item = explode('.', $tab_path, 3);

		

		if (count($path_item) > 1) {
			$this->plugin_name = $path_item[0];
			$this->tab_id = $path_item[1];
		}
		
		if (count($path_item) > 2)
			$this->tab_id .= '_' . $path_item[2];
		
		$this->tab_path = $tab_path;
	}



	/*--------------------------------------------------------------------------
	 * load() : Load the precompiled template or compile it if non-existant.
	 *
	 * Arguments : 
	 * 	- $template_name : Template file to load (relative to the template directory)
	 *
	 * Returns   : Filename of the compiled template
	 */
	function load($template_name, $use_global=false)
	{
	
		if ($use_global)
			$template_file = DOCUMENT_ROOT . '/templates/' . $template_name;
		else	
			$template_file = $this->template_dir . '/' . $template_name;

			
		$cache_file = $this->cache_dir . '/' . md5($template_file . $this->tab_path) . '.php';

		if (($template_mtime = @filemtime($template_file)) === False)
			throw new Exception('Can\'t load the template file (' . $template_file . ')');
			
		$template_mtime = time();
	
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
	private function compile($template_file, $cache_file)
	{
	
		$compile_start = microtime(true);	
		
		$this->unique_base = hash('crc32b', $template_file);		

		/* Open template file */
		$dom_input = new DOMDocument();
		@$dom_input->loadHTMLFile($template_file);
		if (!$dom_input)
			throw new Exception('Template file not found (' . $template_file . ')');	
			
		/* Open cache file */
		$handle = @fopen($cache_file, 'w');
		if (!$handle)
			throw new Exception('Cannot compile template! <br/>The cache directory does not exists or does not have write permissions.');

		$html = $dom_input->getElementsByTagName('html')->item(0);
		$body = $html->getElementsByTagName('body')->item(0);
		
		$this->process_node($handle, $body, false, true);
		
		$compile_time = microtime(true) - $compile_start;
		printf('Compile time: %0.4f s', $compile_time);
		
		fwrite($handle, "
			<script type='text/javascript'>
			// <![CDATA[
			
			$('.dateinput').dateinput({
				format: 'dd/mm/yyyy'
			});

			// ]]>
			</script>
		");

		fclose($handle);
	}



	/*--------------------------------------------------------------------------
	 * process_node() : 
	 *
	 * Arguments : 
	 * 	- 
	 *
	 * Returns   : None
	 */
	private function process_node($handle, $node, $outer=true, $recursive=true, $data_type=null, $data_source=null)
	{
	
		if ($outer) {
			$output = '<' . $node->nodeName;
						
			if ($node->hasAttributes()) {
				foreach ($node->attributes as $attrib)
					$output .= ' ' . $attrib->nodeName . '="' . $attrib->value . '"';
			}
							
			$output = $this->convert_shortcode($output, $data_type, $data_source);
		}
		
		if ($node->hasChildNodes()) {
		
			if ($outer)
				fwrite($handle, $output . '>');
		
			$child = $node->firstChild;
			do {
				if ($recursive) {
					
					switch ($child->nodeType) {
						case XML_ELEMENT_NODE:
				
							switch ($child->nodeName) {
								case 'form':
									/* insert the current tab path in an hidden input */
									$element = $child->ownerDocument->createElement('input');
									$element->setAttribute('type', 'hidden');
									$element->setAttribute('name', 'path');
									$element->setAttribute('value', $this->tab_path);
									$child->appendChild($element);

									$element = $child->ownerDocument->createElement('input');
									$element->setAttribute('type', 'hidden');
									$element->setAttribute('name', 'action');
									$element->setAttribute('value', '<?php echo isset($action) ? $action : \'\' ?>');
									$child->appendChild($element);

									
									$this->process_node($handle, $child, true, true, $data_type, $data_source);
									break;
									
								case 'icon':
									$this->process_tag_icon($child, $handle, $data_type, $data_source);
									break;
									
								case 'toolbar':
									$this->process_tag_toolbar($child, $handle);
									break;
				
								case 'grid':
								case 'datagrid':
									$this->process_tag_grid($child, $handle);
									break;
				
								case 'var':
								case 'variable':
									$this->process_tag_variable($child, $handle, $data_type, $data_source);
									break;
					
								case 'noparse':
									$html = $node->ownerDocument->saveXML($child);
									fwrite($handle, $html);
									break;
					
								case 'include':
									$this->process_tag_include($child, $handle);
									break;
									
								case 'call':
									$this->process_tag_call($child, $handle, $data_type, $data_source);
									break;
						
								default:
									$this->process_node($handle, $child, true, true, $data_type, $data_source);
									break;
							}
							break;
							
						case XML_PI_NODE:
						case XML_COMMENT_NODE:
							$html = $node->ownerDocument->saveXML($child);
							fwrite($handle, $html);
							break;
							
						case XML_TEXT_NODE:
							$html = $this->convert_shortcode($node->ownerDocument->saveXML($child), $data_type, $data_source);
							fwrite($handle, $html);
							break;						
					}					
				} else {
				
					
					$html = $this->convert_shortcode($child->ownerDocument->saveXML($child), $data_type, $data_source);
					fwrite($handle, $html);
				}
		
			} while(($child = $child->nextSibling) != null);
			
			if ($outer)
				fwrite($handle, '</' . $node->nodeName . '>');

		} elseif ($outer) {
			fwrite($handle, $output . ' />');
		}
	}


	/*--------------------------------------------------------------------------
	 * process_tag_icon()
	 *
	 * Arguments : 
	 * 	- 
	 *
	 * Returns   : None
	 */	
	private function process_tag_icon($node_tag, $handle, $data_type=null, $data_source=null)
	{
		$icon = $this->convert_shortcode($node_tag->getAttribute('icon'), $data_type, $data_source);
		$action = $this->convert_shortcode($node_tag->getAttribute('action'), $data_type, $data_source);
		$title = $this->convert_shortcode($node_tag->getAttribute('title'), $data_type, $data_source);
		$caption = $this->convert_shortcode($node_tag->textContent, $data_type, $data_source);
		$href = $this->convert_shortcode($node_tag->getAttribute('href'), $data_type, $data_source);;
		$id = $node_tag->getAttribute('id');
		
		$btn_class = (!empty($icon) && empty($caption)) ? 'icon-only' : '';
			
		if (!empty($action) && empty($href)) {
			$href = '?path=' . $this->tab_path . '<?php echo !empty($uri) ? \'&\' . $uri : \'\' ?>';

			switch ($action) {
				case 'refresh':
					$href .= '&force=1<?php echo isset($current_page) ? \'&page=\' . $current_page : \'\' ?>';
					break;
			
				case 'clear':
					$href = '?path=' . $this->tab_path;
					break;
			
				case 'first-page':
					$btn_class .= ' <?php echo ($current_page <= 1) ? \'disabled\' : \'\' ?>';
					$href .= '&page=1';
					break;
		
				case 'prev-page':
					$btn_class .= ' <?php echo ($current_page <= 1) ? \'disabled\' : \'\' ?>';
					$href .= '&page=<?php echo max($current_page - 1, 1); ?>';
					break;
		
				case 'next-page':
					$btn_class .= ' <?php echo ($current_page >= $total_pages) ? \'disabled\' : \'\' ?>';
					$href .= '&page=<?php echo min($current_page + 1, $total_pages); ?>';
					break;
		
				case 'last-page':
					$btn_class .= ' <?php echo ($current_page >= $total_pages) ? \'disabled\' : \'\' ?>';
					$href .= '&page=<?php echo $total_pages; ?>';
					break;

				default:
					$href .= '&action=' . $action;
					break;
			}
			
			$params = $this->convert_shortcode($node_tag->getAttribute('params'), $data_type, $data_source);
			if (!empty($params))
				$href .= '&' . $params;
		}
		
		if (!empty($href))
			fwrite($handle, "<a id=\"$id\"href=\"$href\" class=\"$btn_class\" tabindex=\"1\" title=\"$title\">");
	
		if (!empty($icon)) {
	
			$icon_class = $node_tag->getAttribute('icon-class');
			if (empty($icon_class))
				$icon_class = 'icon16';
	
			fwrite($handle, "<img src=\"images/blank.png\" class=\"$icon_class $icon_class-$icon\" />");
		}
	
		fwrite($handle, $caption);
		
		if (!empty($href))
			fwrite($handle, '</a>');
	}



	/*--------------------------------------------------------------------------
	 * process_tag_toolbar()
	 *
	 * Arguments : 
	 * 	- 
	 *
	 * Returns   : None
	 */	
	private function process_tag_toolbar($node_input, $handle)
	{
		$id = $node_input->getAttribute('id');
		if (empty($id))
			$id = $this->tab_id . '_toolbar';
			
		$class = 'box toolbar';

		fwrite($handle, "<div class=\"$class\" id=\"$id\"><ul>");	
		
		$this->process_list_items($node_input, $handle);
		
		fwrite($handle, '</ul><div class="clear"></div>');
		fwrite($handle, '</div>');
	}



	/*--------------------------------------------------------------------------
	 * process_list_items
	 *
	 * Arguments : 
	 * 	- 
	 *
	 * Returns   : None
	 */	
	private function process_list_items($node_list, $handle, $data_type=null, $data_source=null)
	{
		
		foreach ($node_list->childNodes as $node_item) {
			
			if ($node_item->nodeName != 'item')
				continue;
				
	
			$item_type = $node_item->getAttribute('type');
			switch ($item_type) {
		
				// -----------------------------------------------
				//  Buttons
				// -----------------------------------------------
				case 'button':
					$li_class = ($node_item->hasAttribute('disabled')) ? 'disabled' : '';
				
					fwrite($handle, "<li class=\"$li_class\">");
					
					$this->process_tag_icon($node_item, $handle, $data_type, $data_source);
					
					fwrite($handle, '</li>');
					break;
				
				case 'submit':
					$icon = $node_item->getAttribute('icon');
					$action = $this->convert_shortcode($node_item->getAttribute('action'), $data_type, $data_source);
					$title = $this->convert_shortcode($node_item->getAttribute('title'), $data_type, $data_source);
					$caption = $this->convert_shortcode($node_item->textContent, $data_type, $data_source);
					$id = $node_item->getAttribute('id');
				
					$btn_class = (!empty($icon) && empty($caption)) ? 'icon-only' : '';
					$li_class = ($node_item->hasAttribute('disabled')) ? 'disabled' : '';
					
					
					fwrite($handle, '<li class="' . $li_class . '"><button id="' . $id . '" class="' . $btn_class . '" type="submit" name="action" value="' . $action . '" title="' . $title . '">');
					fwrite($handle, '<!--' . $action . '-->');	// IE submit bug fix
				
					if (!empty($icon)) {
				
						$icon_class = $node_item->getAttribute('icon-class');
						if (empty($icon_class))
							$icon_class = 'icon16';
				
						fwrite($handle, "<img src=\"images/blank.png\" class=\"$icon_class $icon_class-$icon\" />");
					}
				
					fwrite($handle, $caption);
					fwrite($handle, '</button></li>');
				
					break;
		
				// -----------------------------------------------
				//  text box
				// -----------------------------------------------
				case 'textbox':
					$name = $node_item->getAttribute('name');
					$id = $node_item->getAttribute('id');
					
					fwrite($handle, '<li><input type="text" name="' . $name . '"');
					
					if (!empty($id))
						fwrite($handle, ' id="' . $id . '"');
					
					if ($node_item->hasAttribute('value')) {
						$value = $node_item->getAttribute('value');
						fwrite($handle, ' value="' . $this->convert_shortcode($value) . '"');
					} else {
						fwrite($handle, ' value="<?php echo isset($_REQUEST[\'' . $name . '\']) ? $_REQUEST[\'' . $name . '\'] : \'\'; ?>"');
					}
				
					if ($node_item->hasAttribute('width'))
						fwrite($handle, 'style="width: ' . $node_item->getAttribute('width') . ';"');
				
					fwrite($handle, ' /></li>');
					break;
		
				// -----------------------------------------------
				//  date box
				// -----------------------------------------------
				case 'datebox':
					$name = $node_item->getAttribute('name');
					$id = $node_item->getAttribute('id');
					
					fwrite($handle, '<li><input type="text" name="' . $name . '" class="dateinput"');
					
					if (!empty($id))
						fwrite($handle, ' id="' . $id . '"');
					
					if ($node_item->hasAttribute('value')) {
						$value = $node_item->getAttribute('value');
						fwrite($handle, ' value="' . $this->convert_shortcode($value) . '"');
					} else {
						fwrite($handle, ' value="<?php echo isset($_REQUEST[\'' . $name . '\']) ? $_REQUEST[\'' . $name . '\'] : \'\'; ?>"');
					}
				
					if ($node_item->hasAttribute('width'))
						fwrite($handle, 'style="width: ' . $node_item->getAttribute('width') . ';"');
				
					fwrite($handle, ' /></li>');
					break;

				// -----------------------------------------------
				//  separator
				// -----------------------------------------------
				case 'separator':
					fwrite($handle, '<li class="separator"></li>');
					break;

				// -----------------------------------------------
				//  label
				// -----------------------------------------------
				case 'label':
					fwrite($handle, '<li class="text">');
					$this->process_node($handle, $node_item, false, true);
					fwrite($handle, '</li>');
					break;
			
				// -----------------------------------------------
				//  Dropdown page list
				// -----------------------------------------------
				case 'page-list':
					$var_counter = $this->get_unique_varname();
				
					$range = max(intval($node_item->getAttribute('range')), 1);
					$prefix = $node_item->getAttribute('prefix');
					$suffix = $node_item->getAttribute('suffix');
			
					$keep_search = '';

					if (isset($_GET['d'])) {
						$keep_search .= '&d='.urlencode($_GET['d']);
					}

					if (isset($_GET['s'])) {
						$keep_search .= '&s='.urlencode($_GET['s']);
					}

					fwrite($handle, '<li class="dropdown">');
					fwrite($handle, '<a tabindex="1" href="#">' . $prefix . '<?php echo $current_page; ?>' . $suffix . '</a>');
					fwrite($handle, '<img class="close-dropdown" src="images/blank.png" alt="" /><ul>');
				
					fwrite($handle, '<?php for(' . $var_counter . '=max(1, $current_page - ' . $range . '); ' . $var_counter . '<=min($current_page + ' . $range . ', $total_pages); ' . $var_counter . '++) { ?>');
					fwrite($handle, '<li><a tabindex="1" href="?path=' . $this->tab_path . $keep_search . '&page=<?php echo ' . $var_counter . '; ?>">');
					fwrite($handle, $prefix . '<?php echo ' . $var_counter . '; ?>' . $suffix);
					fwrite($handle, '</a></li>');
					fwrite($handle, '<?php } ?>');
				
					fwrite($handle, '</ul></li>');
				
					break;
			
			
				// -----------------------------------------------
				//  Dropdown list
				// -----------------------------------------------
				case 'list':
					$node_caption = $node_item->getElementsByTagName('caption')->item(0);
					$icon = $node_item->getAttribute('icon');
					$caption =!empty($node_caption) ? $node_caption->textContent : '';
					
					$a_class = (!empty($icon) && empty($caption)) ? 'icon-only' : '';

			
					fwrite($handle, '<li class="dropdown" ');
					
					if ($node_item->hasAttribute('width'))
						fwrite($handle, 'style="width: ' . $node_item->getAttribute('width') . ';"');
					
					fwrite($handle, '><a href="#" tabindex="1" class="' . $a_class . '">');

					if (!empty($icon)) {
				
						$icon_class = $node_item->getAttribute('icon-class');
						if (empty($icon_class))
							$icon_class = 'icon16';
				
						fwrite($handle, "<img src=\"images/blank.png\" class=\"$icon_class $icon_class-$icon\" />");
					}
				
					fwrite($handle, $this->convert_shortcode($caption));
					fwrite($handle, '</a><img class="close-dropdown" src="images/blank.png" alt="" />');
				
					fwrite($handle, '<ul>');
					
					
					$node_row = $node_item->getElementsByTagName('row')->item(0);
					$node_empty = $node_item->getElementsByTagName('if-empty')->item(0);
					$data_source = $node_item->getAttribute('data-source');
					$data_type = $node_item->getAttribute('data-type');
					
					if (!empty($node_row) && !empty($data_source)) {
					
						if (!empty($node_empty)) {
							fwrite($handle, '<?php if (empty($' . $data_source . ')): ?>');
							$this->process_list_items($node_empty, $handle);
							fwrite($handle, '<?php else: ?>');
						}
					
						switch ($data_type) {

							/* hashtable array */
							case 'dict':
								$var_value = $this->get_unique_varname();
								$var_key = $this->get_unique_varname();
							
								fwrite($handle, '<?php foreach($' . $data_source . ' as ' . $var_key . ' => ' . $var_value . '): ?>');
								
								$this->process_list_items($node_row, $handle, $data_type, array($data_source, $var_key, $var_value));
								break;
							
							/* ODBC query result */
							case 'odbc':
								
							
								break;
							
							/* Ordinary array */
							default:
								$var_value = $this->get_unique_varname();
								fwrite($handle, '<?php foreach($' . $data_source . ' as ' . $var_value . '): ?>');
								
								$this->process_list_items($node_row, $handle, $data_type, array($data_source, $var_value));
								break;
						}	
						
						fwrite($handle, '<?php endforeach; ?>');
						
						
						if (!empty($node_empty))
							fwrite($handle, '<?php endif; ?>');
						
					} else if (empty($node_row)) {
						
						$this->process_list_items($node_item, $handle);
					}
				
					fwrite($handle, "</ul></li>\r\n");
					break;
			}
		}
		
		fwrite($handle, "\r\n");		
	}
	


	/*--------------------------------------------------------------------------
	 * process_tag_grid()
	 *
	 * Arguments : 
	 * 	- 
	 *
	 * Returns   : None
	 */	
	private function process_tag_grid($node, $handle)
	{
		/* Get child nodes */
		$node_row = $node->getElementsByTagName('row')->item(0);
		$node_header = $node->getElementsByTagName('header')->item(0);
		$node_empty = $node->getElementsByTagName('if-empty')->item(0);
		
		/* Required child */
		if (empty($node_row))
			return;
		
		/* Get tag attributes */
		$data_source = $node->getAttribute('data-source');
		$data_type = $node->getAttribute('data-type');
		$min_rows = $node->getAttribute('min-rows');
		
		/* Required attributes */
		if (empty($data_source) || empty($data_type))
			return;
		
		
		fwrite($handle, '<table id="" class="grid">');
		
		$var_count = $this->get_unique_varname();
		fwrite($handle, '<?php ' . $var_count . '=0 ?>');
		
		/* Generate grid header if present */
		if (!empty($node_header)) {
			fwrite($handle, '<tr>');
			
			foreach ($node_header->getElementsByTagName('column') as $node_column) {
				$style = $node_column->getAttribute('style');
				$type = $node_column->getAttribute('type');
			
				fwrite($handle, '<th style="' . $style . '" class="column-' . $type . '">');
				
				$this->process_node($handle, $node_column, false, false, $data_type, $data_source);

				fwrite($handle, '</th>');
			}
		
			fwrite($handle, '</tr>');
		}
		
		
		
		switch ($data_type) {
			case 'odbc':
				fwrite($handle, '<?php while (@odbc_fetch_row($' . $data_source . ')): ?>');
				break;
		
			case 'dict':
				break;
				
			default:
		}
		
		
		fwrite($handle, "<tr class=\"<?php echo !($var_count & 1) ? '':'alt' ?>\">");

		$columns = $node_row->getElementsByTagName('column');
		$num_columns = $columns->length;
		
		foreach ($columns as $node_column) {
			fwrite($handle, '<td');
			
			$type = $node_column->getAttribute('type');
			if (!empty($type))
				fwrite($handle, ' class="column-' . $type . '"');
		
			$style = $node_column->getAttribute('style');
			if (!empty($style))
				fwrite($handle, ' style="' . $style . '"');
				
			fwrite($handle, '>');
			
			$this->process_node($handle, $node_column, false, true, $data_type, $data_source);
			fwrite($handle, '</td>');
			
		} while(($node_column = $node_column->nextSibling) != NULL);
	
		fwrite($handle, '</tr>');
		
		switch ($data_type) {
			case 'odbc':
				fwrite($handle, '<?php ' . $var_count . '++; endwhile; ?>');
				
				if (!empty($node_empty)) {
					fwrite($handle, '<?php if (' . $var_count . '==0):?>');
					fwrite($handle, '<tr class="<?php echo !(' . $var_count . ' & 1) ? \'\':\'alt\' ?>">');
					fwrite($handle, '<td colspan="'. $num_columns . '">');
					
					$this->process_node($handle, $node_empty, false, false);
					
					fwrite($handle, '</td></tr><?php ' . $var_count . '++; endif; ?>');
				}
				
				break;
		}
		
		
		if ($min_rows) {
			fwrite($handle, '<?php while(' . $var_count . ' < ' . $min_rows . '): ?>');
			fwrite($handle, '<tr class="<?php echo !(' . $var_count . ' & 1) ? \'\':\'alt\' ?>">');
			fwrite($handle, str_repeat('<td>&nbsp;</td>', $num_columns));
			fwrite($handle, '</tr><?php ' . $var_count . '++; endwhile; ?>');
		}
		
		fwrite($handle, '</table>');
	}
	/*
<form id="cnam_frm_filters" action="" method="get">
	<input type="hidden" name="path" value="{{$tab_path}}" />

	<table id="cnam" class="grid">
		<tr>
			<th class="column-icon">&nbsp;</th>
			<th style="width: 170px" >Number</th>
			<th style="width: 200px">Caller ID (CNAM)</th>
			<th style="width: 230px">Full name</th>
			<th class="column-actions"></th>
		</tr>
		{{resultset|$results}}
			<tr class="{{altern|cnam|alt}} highlight">
				<td class="column-icon"><input type="checkbox" name="id[]" value="{{column|id}}" {{is|"$action==select_all"|checked}} /></td>
				<td class="clickable"><a tabindex="1" href="?path=CNAM.tools.cnam&action=edit&id={{column|id}}">{{column|number|format_phone}}</a></td>
				<td class="clickable"><a tabindex="1" href="?path=CNAM.tools.cnam&action=edit&id={{column|id}}">{{column|cidname}}</a></td>
				<td class="clickable"><a tabindex="1" href="?path=CNAM.tools.cnam&action=edit&id={{column|id}}">{{column|fullname}}</a></td>
				<td class="column-actions">
					<a tabindex="1" href="?path=CNAM.tools.cnam&action=edit&id={{column|id}}"><img alt="Edit" class="icon16 icon16-edit" src="images/blank.png" /></a>
					<a tabindex="1" href="?path=CNAM.tools.cnam&action=delete&id[]={{column|id}}"><img alt="Delete" class="icon16 icon16-delete" src="images/blank.png" /></a>
				</td>
			</tr>
		{{/resultset}}
	</table>
</form>
*/



	/*--------------------------------------------------------------------------
	 * process_tag_variable()
	 *
	 * Arguments : 
	 * 	- 
	 *
	 * Returns   : None
	 */	
	private function process_tag_variable($node, $handle)
	{
		$node_empty = $node->getElementsByTagName('if-empty')->item(0);

		$if_empty = $node->getAttribute('if-empty');
		$format = $node->getAttribute('format');
		$vars = explode(',', $node->getAttribute('name'));

		if (!empty($format))
			$instr = '<?php printf(\'' . $format . '\',';
		else
			$instr = '<?php echo ';
		
		foreach ($vars as $key => $var) {
			
			$var = trim($var);
			if (empty($var)) {
				unset($vars[$key]);
				continue;
			}
			
			$vars[$key] = '$' . $var;
		}

		if (empty($vars))
			return;
		
		if (!empty($node_empty) || !empty($if_empty)) {
			fwrite($handle, '<?php if(empty(' . implode(') || empty(', $vars) . ')): ?>');
			
			if (!empty($node_empty))
				$this->process_node($handle, $node_empty, false, false);
			else
				fwrite($handle, $if_empty);

			fwrite($handle, '<?php else: ?>');
		}
		
		fwrite($handle, $instr . implode(',', $vars));
		
		if (!empty($format))
			fwrite($handle, ') ?>');
		else
			fwrite($handle, ' ?>');
		
		if (!empty($node_empty) || !empty($if_empty))
			fwrite($handle, '<?php endif; ?>');
	}



	/*--------------------------------------------------------------------------
	 * process_tag_include()
	 *
	 * Arguments : 
	 * 	- 
	 *
	 * Returns   : None
	 */	
	private function process_tag_include($node, $handle)
	{
		
		
			
	}
	
	
	
	/*--------------------------------------------------------------------------
	 * process_tag_callback()
	 *
	 * Arguments : 
	 * 	- 
	 *
	 * Returns   : None
	 */		
	private function process_tag_call($node, $handle, $data_type=null, $data_source=null)
	{
		$name   = $node->getAttribute('name');
		$return = $node->getAttribute('return');
		$params = $this->convert_shortcode($node->getAttribute('params'), $data_type, $data_source, false);
		
		/* Required attribute */
		if (empty($name))
			return;
			
		fwrite($handle, "\r\n");
			
		if (!empty($return)) {
			$return = preg_split('/[\s,]+/', $return);
			
			if (count($return) > 1)
				$return = '@list($' . implode(', $', $return) . ')';
			else
				$return = '$' . $return;

		
			fwrite($handle, '<?php ' . $return . ' = ');
		}		

		fwrite($handle, '$this->' . $name . '(' . $params . '); ?>');
	}
	
	

	/*--------------------------------------------------------------------------
	 * process_tag_grid()
	 *
	 * Arguments : 
	 * 	- 
	 *
	 * Returns   : None
	 */		
	private function convert_shortcode($text, $data_type=null, $data_source=null, $insert_pi=true)
	{
		$offset = 0;
		while(($offset = strpos($text, '[[', $offset)) !== false) {

			if (($end = strpos($text, ']]', $offset)) === false)
				break;
			
			$tokens =  preg_split('/[\s|]+/', trim(substr($text, $offset + 2, $end - $offset - 2)));
			
			$output = '';
			foreach($tokens as $token) {
				if ((substr($token, 0, 2) != 'if') && empty($output)) {
					
					if ($token[0] == '$') {
						$output = $token;
					} else {
				
						switch ($data_type) {
							case null:
								$output = '$' . $token;
								break;
						
							case 'odbc':
								$output = 'odbc_result($' . $data_source . ', \'' . $token . '\')';
								break;
					
							case 'dict':
								switch (strtolower($token)) {
									case 'key':   $output = $data_source[1]; break;
									case 'value': $output = $data_source[2]; break;
									default:      $output = $token;          break;
								}
								break;

							default:
								$output = $data_source;
								break;
						}
					}
				} else {
				
					$params = explode(':', $token);
					
					if (!empty($output) && ($params[0][0] == '#')) {
                                                $p = substr($params[0], 1);

						if (!is_numeric($p)) {
							if ($p[0] != '$') {
								$p = '\''.$p.'\'';

							} else if ($data_type == 'odbc') {
							        $p = 'odbc_result($' . $data_source . ', \'' . substr($p, 1) . '\')'; 
						
							} else if ($data_type == 'dict') {
								switch (strtolower($p)) {
									case 'key':   $output = $data_source[1]; break;
									case 'value': $output = $data_source[2]; break;
									default:      $output = $p;              break;
								}
							}
						}
					        
						$output .= '['.$p.']';						

					} else {

						switch ($params[0]) {
							case 'if':
								$output = '(('.$params[1].') ? ('.$params[2].') : ('.$params[3].'))';
								break;
							case 'lower':
								$output = 'strtolower(' . $output . ')';
								break;
						
							case 'upper':
								$output = 'strtoupper(' . $output . ')';
								break;
							
							case 'ucfirst':
								$output = 'ucfirst(' . $output . ')';
								break;
								
							case 'ucwords':
								$output = 'ucwords(' . $output . ')';
								break;
							
							case 'var_dump':
								$output = 'var_dump('. $output . ')';
								break;

							case 'explode':
								$sep = ' ';

								if (!empty($params[1])) {
									$sep = $params[1];
								}

								$output = 'explode("'.addslashes($sep).'", '.$output.')';
								break;
		
							case 'format_phone':
								$output = 'format_phone_number(' . $output;
							
								if (isset($params[1]) && strtolower($params[1]) == 'false')
									$output .= ', false';
									
								$output .= ')';
								
								break;

							case 'format_time_seconds':
								$output = 'format_time_seconds(' . $output . ')';
								break;

							case 'money_format':
							case 'format_money':
								$output = 'money_format(\'' . $this->currency_format . '\', ' . $output . ')';
								break;

							case 'dumpfile':
								$output = 'dumpfile('. $output . ')';
								break;
							case 'dumpgzfile':
								$output = 'dumpgzfile('. $output . ')';
								break;
							case 'get_group':
								$output = 'get_group('. $output . ')';
								break;
						}

					}
				}
			}
			
			if (empty($output))
				continue;

			if ($insert_pi)
				$output = '<?php echo ' . $output . ' ?>';
			
			$text = substr_replace($text, $output, $offset, $end - $offset + 2);
			$offset = $offset + strlen($output);
		}
			
		return $text;
	}



	/*--------------------------------------------------------------------------
	 * get_unique_varname
	 *
	 * Arguments : 
	 * 	None
	 *
	 * Returns   : Unique variable name.
	 */
	private function get_unique_varname()
	{
		return '$v' . $this->unique_base . $this->unique_id++;
	}
}
?>
