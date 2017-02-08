<?php
//******************************************************************************
// class_template_engine.php - Template engine
//
// Project : Asterisk Y.A.A.M (Yet another asterisk manager)
// Author  : Benoit Frigon <benoit@frigon.info>
//
// Contributors
// ------------
//  Rafael G. Dantas <rafagd@gmail.com>
//
// Copyright (c) Benoit Frigon
// www.bfrigon.com
//
// This software is released under the terms of the GNU Lesser General Public
// License v2.1.
// A copy of which is available from http://www.gnu.org/copyleft/lesser.html
//
//******************************************************************************

if(realpath(__FILE__) == realpath($_SERVER["SCRIPT_FILENAME"])) {
    header("Location:../index.php");
    exit();
}

class TemplateEngine
{
    private $_template_dir;
    private $_cache_dir;
    private $_plugin;

    private $_template_file;

    public $currency_format = "%.3i $";


    /*--------------------------------------------------------------------------
     * __construct()
     *
     * Arguments :
     * ---------
     *  - plugin : The plugin class the template engine is initialized for. The
     *             engine is going to load the template from the plugin directory.
     *             If sets to 'null', the engine is going to use the global template
     *             directory instead.
     *
     * Returns : None
     */
    function __construct($plugin=null)
    {
        $this->_plugin = $plugin;

        if (is_null($plugin))
            $this->_template_dir = dirname(__FILE__) . "/../templates";
        else
            $this->_template_dir = $plugin->dir;

        $this->_cache_dir = dirname(__FILE__) . "/../cache";
    }


    /*--------------------------------------------------------------------------
     * load() : Load the precompiled template or compile it if non-existant.
     *
     * Arguments
     * ---------
     *  - template_name : Template file to load (relative to the template directory)
     *  - use_global    : Force loading from global template directory.
     *  - top_level     : If false, the template is a part of another template.
     *
     * Returns : Filename of the compiled template.
     */
    function load($template_name, $use_global=false, $top_level=false)
    {
        try {

            if ($use_global)
                $template_file = DOCUMENT_ROOT . "/templates/$template_name";
            else
                $template_file = $this->_template_dir . "/$template_name";


            $cache_file = $this->_cache_dir . "/" . md5($template_file) . ".php";

            if (($template_mtime = @filemtime($template_file)) === False)
                throw new Exception("Can't load the template file ($template_file)");

            /* Force re-compiling the template if enabled */
            if (FORCE_RECOMPILE_TEMPLATE)
                $template_mtime = time();

            if (($cache_mtime = @filemtime($cache_file)) !== False && $template_mtime < $cache_mtime)
                return $cache_file;

            /* (re)-compile the template */
            $this->compile($template_file, $cache_file, $top_level);

            return $cache_file;

        } catch (Exception $e) {
            if ($top_level == false)
                throw $e;

            die("Template engine error: " . $e->getmessage());
        }
    }


    /*--------------------------------------------------------------------------
     * compile(): Compile the template
     *
     * Arguments
     * ---------
     *  - template_file     : Source template file(relative to the template directory)
     *  - cache_file        : Compiled template file
     *  - process_top_level : Process top-level elements <doctype>, <html>, <head>, <body>
     *
     * Returns : Filename of the compiled template.
     */
    private function compile($template_file, $cache_file, $top_level=false)
    {
        global $DEBUGINFO_TEMPLATE_ENGINE;

        $compile_start = microtime(true);

        $this->_template_file = $template_file;
        $this->unique_base = hash("crc32b", $template_file);

        /* Open template file */
        $dom_input = new DOMDocument();
        if (!(@$dom_input->loadHTMLFile($template_file)))
            throw new Exception("Template file not found ($template_file)");

        /* Open cache file */
        $handle = @fopen($cache_file, "w");
        if (!$handle)
            throw new Exception("Cannot compile template! <br/>
                The cache directory does not exists or does not have write permissions.");

        /* Insert code that prevents the cache file from being executed directly */
        fwrite($handle, "<?php\n");
        fwrite($handle, "if(realpath(__FILE__) == realpath(\$_SERVER[\"SCRIPT_FILENAME\"]))\n");
        fwrite($handle, "    die();\n");
        fwrite($handle, "?>\n\n");

        $this->process_node($handle, $dom_input, false, true, null, null, $top_level);

        $compile_time = (microtime(true) - $compile_start) * 1000;

        $DEBUGINFO_TEMPLATE_ENGINE .= sprintf("Compiled : %s to %s - compile time: %0.1f ms<br />",
            $template_file, $cache_file, $compile_time);

        fclose($handle);
    }


    /*--------------------------------------------------------------------------
     * throw_compile_exception() : Throw a compile exception
     *
     * Arguments
     * ---------
     *  - message : Reason for the exception.
     *
     * Returns : Nothing
     */
    private function throw_compile_exception($node, $message)
    {
        $file = $this->_template_file;
        $line = $node->getLineNo();

        throw new Exception("Template compile error in '$file' at line $line<p>$message</p>");
    }


    /*--------------------------------------------------------------------------
     * get_attribute_shortcode() : Convert shortcode contained in a node attribute
     *
     * Arguments
     * ---------
     *  - node        : The node to get the attribute from.
     *  - name        : Attribute name
     *  - default     : Default value if attribute does not exists.
     *  - data_type   : Type of the Current data source (odbc, dict).
     *  - data_source : Current data source object
     *  - wrap        : Wrap the return value within php tags. <?php echo ... ?>
     *
     * Returns : The attribute value
     */
    private function get_attribute_shortcode($node, $name, $default="", $data_type=null, $data_source=null, $wrap=true)
    {

        if (!($node->hasAttribute($name)))
            return $default;

        $value = trim($node->getAttribute($name));

        return $this->process_shortcode($value, $data_type, $data_source, $wrap);
    }


    /*--------------------------------------------------------------------------
     * get_attribute_boolean() : Get the boolean value from a node attribute.
     *
     * Arguments
     * ---------
     *  - node        : The node to get the attribute from.
     *  - name        : Attribute name
     *
     * Returns : The attribute value
     */
    private function get_attribute_boolean($node, $name)
    {
        if (!($node->hasAttribute($name)))
            return false;

        $value = strtolower(trim($node->getAttribute($name)));

        switch ($value) {
            case "true":
            case "1":
            case "":
                return true;

            default:
                return false;

        }
    }


    /*--------------------------------------------------------------------------
     * func_build_tab_url() : Generates the code to call the plugin function
     *                        "build_tab_url" and adds it to the template output.
     *                        This function creates an url based on the content
     *                        of $_GET and the given parameters.
     *
     * Arguments
     * ---------
     *  - params      : Parameters to include in the url.
     *  - keep_uri    : If true, the content of "$_GET" will be merged.
     *  - no_referrer : If true, discards the referrer from $_GET
     *
     * Returns : The generated code required to call the "build_tab_url" function.
     */
    private function func_build_tab_url($params, $keep_uri, $no_referrer=false)
    {
        if (is_null($this->_plugin)) {
            $output = "?<?php echo http_build_query(";
        } else {
            $output = "<?php echo \$this->build_tab_url(";
        }


        if (is_null($params)) {
            $output .= "null, ";

        } else {

            $output .= "array(";

            foreach($params as $name => $value)
                $output .= "\"$name\" => $value, ";

            $output .= "), ";
        }


        if (is_null($this->_plugin)) {
            $output .= ") ?>";
        } else {
            $output .= ($keep_uri ? "true" : "false") . ", ";
            $output .= ($no_referrer ? "true" : "false") . ") ?>";
        }

        return $output;
    }


    /*--------------------------------------------------------------------------
     * process_node() : Compile a node from the template file
     *
     * Arguments
     * ---------
     *  - handle            : File handle to the template output.
     *  - node              : Node to process.
     *  - outer             : Include the node in the output. If false, only process child nodes.
     *  - recursive         : Process all child nodes recursivly.
     *  - data_type         : Type of the Current data source (odbc, dict).
     *  - data_source       : Current data source object.
     *  - process_top_level : Process top-level elements <doctype>, <html>, <head>, <body>
     *
     * Returns : None
     */
    private function process_node($handle, $node, $outer=true, $recursive=true, $data_type=null, $data_source=null, $process_top_level=false)
    {

        if ($outer) {
            fwrite($handle, "<" . $node->nodeName);

            if ($node->hasAttributes()) {
                foreach ($node->attributes as $attrib) {

                    $value = $this->process_shortcode($attrib->value, $data_type, $data_source);

                    fwrite($handle, " {$attrib->nodeName}=\"$value\"");
                }
            }

            fwrite($handle, ">");
        }

        if ($node->hasChildNodes()) {

            $child = $node->firstChild;
            do {
                if ($recursive) {

                    switch ($child->nodeType) {
                        case XML_ELEMENT_NODE:

                            switch ($child->nodeName) {
                                case "form":
                                    $this->process_tag_form($child, $handle, $data_type, $data_source);
                                    break;

                                case "action":
                                case "action-list":
                                    $this->process_tag_action_list($child, $handle, $data_type, $data_source);
                                    break;

                                case "foreach":
                                    $this->process_tag_foreach($child, $handle, $data_type, $data_source);
                                    break;

                                case "icon":
                                    $this->process_tag_icon($child, $handle, $data_type, $data_source);
                                    break;

                                case "toolbar":
                                    $this->process_tag_toolbar($child, $handle, $data_type, $data_source);
                                    break;

                                case "if":
                                    $this->process_tag_if($child, $handle, $data_type, $data_source);
                                    break;

                                case "dialog":
                                    $this->process_tag_dialog($child, $handle, $data_type, $data_source);
                                    break;

                                case "grid":
                                case "datagrid":
                                    $this->process_tag_grid($child, $handle);
                                    break;

                                case "var":
                                case "variable":
                                    $this->process_tag_variable($child, $handle, $data_type, $data_source);
                                    break;

                                case "noparse":
                                    $html = $node->ownerDocument->saveXML($child);
                                    fwrite($handle, $html);
                                    break;

                                case "call":
                                case "callback":
                                    $this->process_tag_callback($child, $handle, $data_type, $data_source);
                                    break;

                                case "script":
                                    fwrite($handle, $child->ownerDocument->saveHTML($child));
                                    break;

                                case "head":
                                    if ($process_top_level == false)
                                        break;

                                    $html = $node->ownerDocument->saveXML($child);

                                    $html = $this->process_shortcode($html, $data_type, $data_source);
                                    fwrite($handle, $html);
                                    break;

                                case "html":
                                case "body":
                                    $this->process_node($handle, $child, $process_top_level, true, $data_type, $data_source, $process_top_level);
                                    break;

                                default:
                                    $this->process_node($handle, $child, true, true, $data_type, $data_source, $process_top_level);
                                    break;
                            }
                            break;

                        case XML_PI_NODE:
                        case XML_COMMENT_NODE:
                            fwrite($handle, $child->ownerDocument->saveHTML($child));
                            break;

                        case XML_TEXT_NODE:
                            $html = $this->process_shortcode($child->ownerDocument->saveHTML($child), $data_type, $data_source);
                            fwrite($handle, $html);
                            break;

                        case XML_DOCUMENT_TYPE_NODE:
                            if ($process_top_level)
                                fwrite($handle, $child->ownerDocument->saveXML($child));

                            break;
                    }
                } else {


                    $html = $this->process_shortcode($child->ownerDocument->saveXML($child), $data_type, $data_source);
                    fwrite($handle, $html);
                }

            } while(($child = $child->nextSibling) != null);
        }

        if ($outer)
            fwrite($handle, "</{$node->nodeName}>");
    }


    /*--------------------------------------------------------------------------
     * process_toolbar_tag_childs() : Process child nodes of the "toolbar" tag.
     *
     * Arguments
     * ---------
     *  - node_list   : The node containing "item" tags.
     *  - handle      : File handle to the template output.
     *  - data_type   : Type of the current data source (odbc, dict).
     *  - data_source : Current data source object.
     *
     * Returns : None
     */
    private function process_dialog_tag_childs($node_tag, $handle, $data_type=null, $data_source=null)
    {

        foreach ($node_tag->childNodes as $node_child) {


            switch ($node_child->nodeName) {
                case "if":
                    $this->process_tag_if($node_child, $handle, $data_type, $data_source, false);

                    $this->process_dialog_tag_childs($node_child, $handle, $data_type, $data_source);

                    fwrite($handle, "<?php endif; ?>\n");
                    break;

                case "field":
                    $this->process_tag_field($node_child, $handle, $data_type, $data_source);
                    break;

                case "toolbar":
                    $this->process_tag_toolbar($node_child, $handle, $data_type, $data_source);
                    break;

                case "message":
                    fwrite($handle, "<div>");

                    $this->process_node($handle, $node_child, false, true, $data_type, $data_source);

                    fwrite($handle, "</div>");
                    break;

                case "h1":
                case "h2":
                    $this->process_node($handle, $node_child, true, true, $data_type, $data_source);
                    break;

                default:
                    break;

            }
        }
    }

    /*--------------------------------------------------------------------------
     * process_tag_dialog() : Process the template tag "dialog".
     *
     * Arguments
     * ---------
     *  - node_tag    : Node to process.
     *  - handle      : File handle to the template output.
     *  - data_type   : Type of the Current data source (odbc, dict).
     *  - data_source : Current data source object.
     *
     * Returns : None
     */
    private function process_tag_dialog($node_tag, $handle, $data_type=null, $data_source=null)
    {
        $type = $node_tag->getAttribute("type");

        switch ($type) {
            case "error":
            case "warning":
                $class = $type;
                break;
        }

        fwrite($handle, "<div class=\"box dialog $class\"><div class=\"content\">");

        $this->process_dialog_tag_childs($node_tag, $handle, $data_type, $data_source);

        fwrite($handle, "</div></div>");
    }


    /*--------------------------------------------------------------------------
     * process_tag_action_list() : Process the template tag "action-list".
     *
     * Arguments
     * ---------
     *  - node_tag    : Node to process.
     *  - handle      : File handle to the template output.
     *  - data_type   : Type of the Current data source (odbc, dict).
     *  - data_source : Current data source object.
     *
     * Returns : None
     */
    private function process_tag_action_list($node_tag, $handle, $data_type=null, $data_source=null)
    {
        $type = strtolower($node_tag->getAttribute("type"));
        $name = strtolower($node_tag->getAttribute("name"));
        $class = $node_tag->getAttribute("class");
        $keep_uri = $this->get_attribute_boolean($node_tag, "keep-uri", false);
        $keep_referrer = $this->get_attribute_boolean($node_tag, "keep-referrer", false);

        $var_action = $this->get_unique_varname();

        /* Required attributes */
        if (empty($type))
            $this->throw_compile_exception($node_tag, "The tag 'action-list' requires a 'type' attribute.");

        if (empty($name))
            $this->throw_compile_exception($node_tag, "The tag 'action-list' requires a 'name' attribute.");

        if ($node_tag->hasAttribute("if"))
            $this->process_tag_if($node_tag, $handle, $data_type, $data_source);

        /* Build items href */
        $url_params = array();
        $url_params["path"] = "{$var_action}[\"path\"]";
        $url_params["action"] = "{$var_action}[\"name\"]";

        if (!(is_null($this->_plugin)))
            $url_params["referrer"] = "\$this->get_tab_url(true)";


        foreach ($node_tag->getElementsByTagName("param") as $node_param) {

            $param_name = $node_param->getAttribute("name");
            $param_value = $this->process_tokens($node_param->getAttribute("value"), $data_type, $data_source);

            if (empty($param_name) || empty($param_value))
                continue;

            $url_params[$param_name] = trim($param_value);
        }

        $href = $this->func_build_tab_url($url_params, $keep_uri, false);


        fwrite($handle, "<span class=\"action-list $class\">");

        fwrite($handle, "<?php foreach(get_action_list(\"$name\") as $var_action): ?>\n");

        switch ($type) {

            /* Icon list */
            default:
                $icon_size = intval($node_tag->getAttribute("icon-size"));
                if ($icon_size == 0)
                    $icon_size = 16;

                $icon_class = "icon$icon_size";

                fwrite($handle, "<a class=\"icon-only\" href=\"$href\" title=\"<?php echo {$var_action}['tooltip'] ?>\" >");

                fwrite($handle, "<img src=\"images/blank.png\" class=\"$icon_class $icon_class-<?php echo {$var_action}['icon'] ?>\" />");
                fwrite($handle, "</a>\n");
        }

        fwrite($handle, "<?php endforeach; ?>\n");
        fwrite($handle, "</span>");

        if ($node_tag->hasAttribute("if"))
            fwrite($handle, "<?php endif; ?>");
    }


    /*--------------------------------------------------------------------------
     * process_tag_form() : Process the template tag "form".
     *
     * Arguments
     * ---------
     *  - node_tag    : Node to process.
     *  - handle      : File handle to the template output.
     *  - data_type   : Type of the Current data source (odbc, dict).
     *  - data_source : Current data source object.
     *
     * Returns : None
     */
    private function process_tag_form($node_tag, $handle, $data_type=null, $data_source=null)
    {
        if (!(is_null($this->_plugin))) {
            $element = $node_tag->ownerDocument->createElement("input");
            $element->setAttribute("type", "hidden");
            $element->setAttribute("name", "path");
            $element->setAttribute("value", "<?php echo \$_GET[\"path\"] ?>");

            $node_tag->appendChild($element);

            $element = $node_tag->ownerDocument->createElement("input");
            $element->setAttribute("type", "hidden");
            $element->setAttribute("name", "referrer");
            $element->setAttribute("value", "<?php echo \$this->get_tab_url(true) ?>");

            $node_tag->appendChild($element);
        }

        $this->process_node($handle, $node_tag, true, true, $data_type, $data_source);
    }


    /*--------------------------------------------------------------------------
     * process_tag_foreach() : Process the template tag "foreach".
     *
     * Arguments
     * ---------
     *  - node_tag    : Node to process.
     *  - handle      : File handle to the template output.
     *  - data_type   : Type of the Current data source (odbc, dict).
     *  - data_source : Current data source object.
     *
     * Returns : None
     */
    private function process_tag_foreach($node_tag, $handle, $data_type=null, $data_source=null)
    {
        /* Get tag attributes */
        $data_source = $this->process_tokens($node_tag->getAttribute("data-source"), null, null);
        $data_type = $node_tag->getAttribute("data-type");
        $type = strtolower($node_tag->getAttribute("type"));
        $class = $node_tag->getAttribute("class");

        $prefix_html = "";
        $suffix_html = "";


        /* Required attributes */
        if (empty($data_type))
            $this->throw_compile_exception($node_tag, "The tag 'foreach' requires a 'data-type' attribute.");

        if (empty($data_source))
            $this->throw_compile_exception($node_tag, "The tag 'foreach' requires a 'data-source' attribute.");


        if ($node_tag->hasAttribute("if"))
            $this->process_tag_if($node_tag, $handle, $data_type, $data_source);


        if ($type == "list") {
            fwrite($handle, "<ul class=\"list $class\">");
            $prefix_html = "<li>";
            $suffix_html = "</li>";
        }


        if ($data_source[0] != "$")
            $data_source = "\$$data_source";

        /* Insert rows iteration code */
        switch ($data_type) {

            /* hashtable array */
            case "dict":
                $var_value = $this->get_unique_varname();
                $var_key = $this->get_unique_varname();

                fwrite($handle, "<?php foreach($data_source as $var_key => $var_value): ?>\n$prefix_html\n");

                $this->process_node($handle, $node_tag, false, true, $data_type, array($data_source, $var_key, $var_value));

                fwrite($handle, "$suffix_html<?php endforeach; ?>");
                break;

            /* ODBC query result */
            case "odbc":

                fwrite($handle, "<?php while (@odbc_fetch_row($data_source)): ?>\n$prefix_html\n");

                $this->process_node($handle, $node_tag, false, true, $data_type, $data_source);

                fwrite($handle, "$suffix_html<?php endwhile; ?>");
                break;

            /* Ordinary array */
            case "array":
                $var_value = $this->get_unique_varname();
                fwrite($handle, "<?php foreach($data_source as $var_value): ?>\n$prefix_html\n");

                $this->process_node($handle, $node_tag, false, true, $data_type, array($data_source, $var_value));

                fwrite($handle, "$suffix_html<?php endforeach; ?>");
                break;

            /* Invalid data type */
            default:
                break;
        }

        if ($type == "list")
            fwrite($handle, "</ul>");

        if ($node_tag->hasAttribute("if"))
            fwrite($handle, "<?php endif; ?>");
    }


    /*--------------------------------------------------------------------------
     * process_tag_if() : Process the template tag "if".
     *
     * Arguments
     * ---------
     *  - node_tag    : Node to process.
     *  - handle      : File handle to the template output.
     *  - data_type   : Type of the Current data source (odbc, dict).
     *  - data_source : Current data source object.
     *  - close_tag   : If true, processes the child nodes and closes the if
     *                  statement, otherwize, it leave it open.
     *
     * Returns : None
     */
    private function process_tag_if($node_tag, $handle, $data_type=null, $data_source=null, $close_tag=true)
    {


        /* stand-alone if tag */
        if ($node_tag->nodeName == "if") {
            $type = strtolower($node_tag->getAttribute("type"));
            $name = $node_tag->getAttribute("name");
            $value = $node_tag->getAttribute("value");

        /* if contained in another tag */
        } else {
            $type = strtolower($node_tag->getAttribute("if"));
            $name = $node_tag->getAttribute("if-name");
            $value = $node_tag->getAttribute("if-value");

        }

        /* Check required attribute */
        if (empty($type))
            $this->throw_compile_exception($node_tag, "The tag 'if' requires a 'type' attribute.");

        switch ($type) {
            case "function":
            case "boolean":
            case "bool":
            case "isset":
            case "empty":
            case "is_null":
            case "string":
                if (empty($name) && $node_tag->nodeName == "if") {
                    $this->throw_compile_exception($node_tag, "The 'if' tag requires an 'if-name' attribute for '$type' type");
                } else if (empty($name)) {
                    $this->throw_compile_exception($node_tag, "The if=$type attribute requires an 'if-name' attribute");
                }
                break;

            case "permission":
            case "perm":
            case "function":
                if (empty($value) && $node_tag->nodeName == "if") {
                    $this->throw_compile_exception($node_tag, "The 'if' tag requires an 'if-value' attribute for '$type' type");
                } else if (empty($value)) {
                    $this->throw_compile_exception($node_tag, "The if=$type attribute requires an 'if-value' attribute");
                }
                break;
        }

        switch ($type) {

            /* Return value of a function */
            case "function":
                $params = $this->process_shortcode($value, $data_type, $data_source, false);
                $op = ($node_tag->hasAttribute("not") ? "!" : "");

                fwrite($handle, "<?php if({$op}\$this->$name($params)): ?>\n");
                break;


            /* Permission check */
            case "permission":
            case "perm":
                $op = ($node_tag->hasAttribute("not") ? "!" : "");

                fwrite($handle, "<?php if ({$op}check_permission(\"$value\")): ?>\n");
                break;

            /* Check if variable Boolean */
            case "boolean":
            case "bool":
                $op = ($node_tag->hasAttribute("not") ? "false" : "true");
                $name = $this->process_tokens($name, $data_type, $data_source);

                fwrite($handle, "<?php if ($name === $op): ?>\n");
                break;

            /* Check if variable exists */
            case "isset":
            case "empty":
            case "is_null":
                $op = ($node_tag->hasAttribute("not") ? "!" : "");
                $name = $this->process_tokens($name, $data_type, $data_source);

                fwrite($handle, "<?php if ({$op}$type($name)): ?>\n");
                break;

            /* Compare string */
            case "string":
            default:
                $op = ($node_tag->hasAttribute("not") ? "!=" : "==");
                $name = $this->process_tokens($name, $data_type, $data_source);

                fwrite($handle, "<?php if ($name $op \"$value\"): ?>\n");
                break;
        }

        if ($node_tag->nodeName == "if" && $close_tag) {
            $this->process_node($handle, $node_tag, false, true, $data_type, $data_source);

            fwrite($handle, "<?php endif; ?>");
        }
    }


    /*--------------------------------------------------------------------------
     * process_tag_icon() : Process the template tag "icon".
     *
     * Arguments
     * ---------
     *  - node_tag    : Node to process.
     *  - handle      : File handle to the template output.
     *  - data_type   : Type of the Current data source (odbc, dict).
     *  - data_source : Current data source object.
     *
     * Returns : None
     */
    private function process_tag_icon($node_tag, $handle, $data_type=null, $data_source=null)
    {
        $icon = $this->get_attribute_shortcode($node_tag, "icon", "", $data_type, $data_source);
        $icon_size = intval($node_tag->getAttribute("icon-size"));
        $action = $this->get_attribute_shortcode($node_tag, "action", "", $data_type, $data_source);
        $title = $this->get_attribute_shortcode($node_tag, "title", "", $data_type, $data_source);
        $params = $this->get_attribute_shortcode($node_tag, "params", "", $data_type, $data_source, false);
        $href = $this->get_attribute_shortcode($node_tag, "href", "", $data_type, $data_source);
        $id = $node_tag->getAttribute("id");
        $caption = $this->process_shortcode($node_tag->textContent, $data_type, $data_source);

        $keep_uri = $this->get_attribute_boolean($node_tag, "keep-uri", false);
        $keep_referrer = $this->get_attribute_boolean($node_tag, "keep-referrer", false);
        $force = $this->get_attribute_boolean($node_tag, "force-update", false);

        $btn_class = (!empty($icon) && empty($caption)) ? "icon-only" : "";

        if ($node_tag->hasAttribute("if"))
            $this->process_tag_if($node_tag, $handle, $data_type, $data_source);


        if (empty($href) && (!empty($params) || !empty($action))) {

            $url_params = array();
            if (!empty($params))
                parse_str($params, $url_params);

            if ($action == "") {
                $href = $this->func_build_tab_url($url_params, $keep_uri);

            } else {
                switch ($action) {
                    case "refresh":
                        $href = $this->func_build_tab_url($url_params, true);

                        $btn_class .= " action-refresh ";
                        break;

                    case "clear":
                        $href = $this->func_build_tab_url($url_params, false);

                        $btn_class .= " action-clear ";
                        break;

                    case "cancel":
                        if (is_null($this->_plugin))
                            $url_params["referrer"] = "\$_GET['referrer']";
                        else
                            $href = "<?php echo \$this->get_tab_referrer() ?>";

                        $btn_class .= " action-cancel ";
                        break;

                    case "first-page":
                        $url_params["page"] = "1";
                        $href = $this->func_build_tab_url($url_params, true);

                        $btn_class .= " <?php echo (\$current_page <= 1) ? 'disabled' : '' ?>";
                        break;

                    case "prev-page":
                        $url_params["page"] = "max(\$current_page - 1, 1)";
                        $href = $this->func_build_tab_url($url_params, true);

                        $btn_class .= " <?php echo (\$current_page <= 1) ? 'disabled' : '' ?>";
                        break;

                    case "next-page":
                        $url_params["page"] = "min(\$current_page + 1, \$total_pages)";
                        $href = $this->func_build_tab_url($url_params, true);

                        $btn_class .= " <?php echo (\$current_page >= \$total_pages) ? 'disabled' : '' ?>";
                        break;

                    case "last-page":
                        $url_params["page"] = "\$total_pages";
                        $href = $this->func_build_tab_url($url_params, true);

                        $btn_class .= " <?php echo (\$current_page >= \$total_pages) ? 'disabled' : '' ?>";
                        break;

                    case "select-none":
                    case "select-all":
                        $url_params["action"] = "'$action'";

                        $href = $this->func_build_tab_url($url_params, true);
                        break;

                    default:
                        $url_params["action"] = "'$action'";

                        if ($keep_referrer || is_null($this->_plugin))
                            $url_params["referrer"] = "\$_GET['referrer']";
                        else
                            $url_params["referrer"] = "\$this->get_tab_url(true)";

                        $href = $this->func_build_tab_url($url_params, $keep_uri, false);
                        break;
                }
            }
        }

        if ($force)
            $btn_class .= " force-update ";

        if (!empty($href))
            fwrite($handle, "<a id=\"$id\" href=\"$href\" class=\"link $btn_class\" tabindex=\"1\" title=\"$title\" >");

        if (!empty($icon)) {
            if ($icon_size == 0)
                $icon_size = 16;

            $icon_class = "icon$icon_size";

            fwrite($handle, "<img src=\"images/blank.png\" class=\"$icon_class $icon_class-$icon\" />");
        }

        fwrite($handle, $caption);

        if (!empty($href))
            fwrite($handle, "</a>");

        if ($node_tag->hasAttribute("if"))
            fwrite($handle, "<?php endif; ?>");
    }


    /*--------------------------------------------------------------------------
     * process_tag_field() : Process the template tag "field"
     *
     * Arguments
     * ---------
     *  - node_tag    : Node to process.
     *  - handle      : File handle to the template output.
     *  - data_type   : Type of the current data source (odbc, dict).
     *  - data_source : Current data source object.
     *
     * Returns : None
     */
    private function process_tag_field($node_tag, $handle, $data_type=null, $data_source=null)
    {

        $name = $node_tag->getAttribute("name");
        $type = $node_tag->getAttribute("type");
        $id = $node_tag->getAttribute("id");
        $caption = $this->get_attribute_shortcode($node_tag, "caption", "", $data_type, $data_source);
        $value = $this->get_attribute_shortcode($node_tag, "value", "", $data_type, $data_source, false);
        $placeholder = $this->get_attribute_shortcode($node_tag, "placeholder", "", $data_type, $data_source);

        if ($node_tag->hasAttribute("if"))
            $this->process_tag_if($node_tag, $handle, $data_type, $data_source);

        fwrite($handle, "<div class=\"field\"><label for=\"$name\">$caption :</label>");


        switch ($type) {

            // -----------------------------------------------
            //  Select box
            // -----------------------------------------------
            case "select":
                $data_type = strtolower(is_null($data_type) ? trim($node_tag->getAttribute("data-type")) : $data_type);
                $data_source = (is_null($data_source) ? trim($node_tag->getAttribute("data-source")) : $data_source);
                $column_key = $node_tag->getAttribute("column-key");
                $column_value = $node_tag->getAttribute("column-value");

                if (empty($data_type))
                    $this->throw_compile_exception($node_tag, "The tag 'field' of type 'select' requires a 'data-type' attribute.");

                if (empty($data_source))
                    $this->throw_compile_exception($node_tag, "The tag 'field' of type 'select' requires a 'data-source' attribute.");

                if ($data_source[0] != "$")
                    $data_source = "\$$data_source";

                fwrite($handle, "<select name=\"$name\" id=\"$id\">");


                /* Insert rows iteration code */
                switch ($data_type) {

                    /* hashtable array */
                    case "dict":
                        $var_value = $this->get_unique_varname();
                        $var_key = $this->get_unique_varname();

                        fwrite($handle, "<?php foreach($data_source as $var_key => $var_value): ?>\n");

                        fwrite($handle, "<option value=\"<?php echo $var_key ?>\"");
                        fwrite($handle, " <?php echo ($var_key == $value) ? 'selected' : '' ?> >");
                        fwrite($handle, "<?php echo $var_value ?></option>\n");

                        fwrite($handle, "<?php endforeach; ?>\n");
                        break;

                    /* ODBC query result */
                    case "odbc":
                        fwrite($handle, "<?php while (@odbc_fetch_row($data_source)): ?>\n");

                        fwrite($handle, "<option value=\"<?php echo @odbc_result($$data_source, '$column_key') ?>\"");
                        fwrite($handle, " <?php echo (@odbc_result($$data_source, '$column_key') == \"$value\") ? 'selected' : '' ?> >");
                        fwrite($handle, "<?php echo @odbc_result($$data_source, '$column_value') ?></option>\n");

                        fwrite($handle, "<?php endwhile; ?>\n");
                        break;

                    /* Ordinary array */
                    case "array":
                        $var_value = $this->get_unique_varname();
                        $var_index = $this->get_unique_varname();
                        fwrite($handle, "<?php $var_index=0; foreach($data_source as $var_value): ?>\n");

                        fwrite($handle, "<option value=\"<?php echo $var_index ?>\"");
                        fwrite($handle, " <?php echo ($var_index == intval(\"$value\")) ? 'selected' : '' ?> >");
                        fwrite($handle, "<?php echo $var_value ?></option>\n");

                        fwrite($handle, "<?php $var_index++; endforeach; ?>\n");
                        break;

                    /* Invalid data type */
                    default:
                        break;
                }

                fwrite($handle, "</select>\n");
                break;

            // -----------------------------------------------
            //  Listbox
            // -----------------------------------------------
            case "listbox":
                $data_type = strtolower(is_null($data_type) ? trim($node_tag->getAttribute("data-type")) : $data_type);
                $data_source = (is_null($data_source) ? trim($node_tag->getAttribute("data-source")) : $data_source);
                $column_key = $node_tag->getAttribute("column-key");
                $column_value = $node_tag->getAttribute("column-value");

                if (substr($name, -2) != "[]")
                    $name = "{$name}[]";

                if (empty($data_type))
                    $this->throw_compile_exception($node_tag, "The tag 'field' of type 'listbox' requires a 'data-type' attribute.");

                if (empty($data_source))
                    $this->throw_compile_exception($node_tag, "The tag 'field' of type 'listbox' requires a 'data-source' attribute.");

                if ($data_source[0] != "$")
                    $data_source = "\$$data_source";

                fwrite($handle, "<div class=\"listbox\">");

                /* Insert rows iteration code */
                switch ($data_type) {


                    /* Ordinary array */
                    case "array":
                        $var_value = $this->process_tokens($node_tag->getAttribute("value"), $data_type, $data_source);
                        $var_checked = $this->get_unique_varname();
                        $var_item = $this->get_unique_varname();

                        fwrite($handle, "<?php foreach($data_source as $var_item):");
                        fwrite($handle, "$var_checked=(in_array($var_item, $var_value) ? 'checked' : ''); ?>\n");

                        fwrite($handle, "<label>");
                        fwrite($handle, "<input type=\"checkbox\" name=\"$name\" value=\"<?php echo $var_item ?>\" <?php echo $var_checked ?> />");
                        fwrite($handle, "<?php echo $var_item ?></label>");

                        fwrite($handle, "<?php endforeach; ?>");
                        break;
                }

                fwrite($handle, "</div>");
                break;

            // -----------------------------------------------
            //  Read-only textbox
            // -----------------------------------------------
            case "view":
            case "readonly":
                fwrite($handle, "<input type=\"text\"");

                if (!empty($id))
                    fwrite($handle, " id=\"$id");

                if (!empty($placeholder))
                    fwrite($handle, " placeholder=\"$placeholder\"");

                if (!empty($name))
                    fwrite($handle, " name=\"$name\"");

                if (!empty($value))
                    fwrite($handle, " value=\"<?php echo $value ?>\"");

                fwrite($handle, " readonly />\n");
                break;

            // -----------------------------------------------
            //  Text box
            // -----------------------------------------------
            case "text":
            case "password":
                fwrite($handle, "<input type=\"$type\"");

                if (!empty($id))
                    fwrite($handle, " id=\"$id");

                if (!empty($name))
                    fwrite($handle, " name=\"$name\"");

                if (!empty($placeholder))
                    fwrite($handle, " placeholder=\"$placeholder\"");

                if (!empty($value))
                    fwrite($handle, " value=\"<?php echo $value ?>\"");

                fwrite($handle, " />\n");
                break;
        }

        $node_help = $node_tag->getElementsByTagName("help")->item(0);

        if (!empty($node_help)) {

            fwrite($handle, "<a href=\"#\" class=\"tooltip\">");
            fwrite($handle, "<img src=\"images/blank.png\" class=\"icon16 icon16-help\" />");
            fwrite($handle, "<span><img class=\"callout\" src=\"images\blank.png\" />");
            fwrite($handle, $this->process_shortcode($node_help->textContent, $data_type, $data_source));
            fwrite($handle, "</span></a>\n");
        }

        fwrite($handle, "</div>");

        if ($node_tag->hasAttribute("if"))
            fwrite($handle, "<?php endif; ?>");
    }


    /*--------------------------------------------------------------------------
     * process_tag_toolbar() : Process the template tag "toolbar"
     *
     * Arguments
     * ---------
     *  - node_tag    : Node to process.
     *  - handle      : File handle to the template output.
     *  - data_type   : Type of the current data source (odbc, dict).
     *  - data_source : Current data source object.
     *
     * Returns : None
     */
    private function process_tag_toolbar($node_tag, $handle, $data_type=null, $data_source=null)
    {
        if ($node_tag->hasAttribute("if"))
            $this->process_tag_if($node_tag, $handle, $data_type, $data_source);

        $id = $node_tag->getAttribute("id");

        $class = $node_tag->getAttribute("class");
        if (empty($class))
            $class = "box";


        fwrite($handle, "<div class=\"toolbar $class\" id=\"$id\"><ul>");

        $this->process_toolbar_tag_childs($node_tag, $handle, $data_type, $data_source);

        fwrite($handle, "</ul><div class=\"clear\"></div>");
        fwrite($handle, "</div>");

        if ($node_tag->hasAttribute("if"))
            fwrite($handle, "<?php endif; ?>");
   }


    /*--------------------------------------------------------------------------
     * process_toolbar_tag_childs() : Process child nodes of the "toolbar" tag.
     *
     * Arguments
     * ---------
     *  - node_list   : The node containing "item" tags.
     *  - handle      : File handle to the template output.
     *  - data_type   : Type of the current data source (odbc, dict).
     *  - data_source : Current data source object.
     *
     * Returns : None
     */
    private function process_toolbar_tag_childs($node_list, $handle, $data_type=null, $data_source=null)
    {

        foreach ($node_list->childNodes as $node_item) {


            switch ($node_item->nodeName) {
                case "group":
                    $id = $node_item->getAttribute("id");

                    fwrite($handle, "</ul><ul");

                    if (!empty($id))
                        fwrite($handle, " id=\"$id\"");

                    fwrite($handle, ">");


                    $this->process_toolbar_tag_childs($node_item, $handle, $data_type, $data_source);

                    fwrite($handle, "</ul><ul>");
                    break;

                /* Allow if tags */
                case "if":
                    $this->process_tag_if($node_item, $handle, $data_type, $data_source, false);

                    $this->process_toolbar_tag_childs($node_item, $handle, $data_type, $data_source);

                    fwrite($handle, "<?php endif; ?>");
                    break;

                /* Allow action-list tags */
                case "action-list":
                case "action":
                    $this->process_tag_action_list($node_item, $handle, $data_type, $data_source);
                    break;

                /* Toolbar item */
                case "item":
                    $this->process_toolbar_items($node_item, $handle, $data_type, $data_source);
                    break;
            }
        }
    }


    /*--------------------------------------------------------------------------
     * process_toolbar_items() : Process "item" inside "toolbar" tags.
     *
     * Arguments
     * ---------
     *  - node_list   : The node containing "item" tags.
     *  - handle      : File handle to the template output.
     *  - data_type   : Type of the current data source (odbc, dict).
     *  - data_source : Current data source object.
     *
     * Returns : None
     */
    private function process_toolbar_items($node_item, $handle, $data_type=null, $data_source=null)
    {
        $item_type = $node_item->getAttribute("type");
        switch ($item_type) {

            // -----------------------------------------------
            //  Buttons
            // -----------------------------------------------
            case "button":
                $li_class = ($this->get_attribute_boolean($node_item, "disabled")) ? "disabled" : "";

                fwrite($handle, "<li class=\"$li_class\">");

                $this->process_tag_icon($node_item, $handle, $data_type, $data_source);

                fwrite($handle, "</li>\r\n");
                break;

            // -----------------------------------------------
            //  Submit button
            // -----------------------------------------------
            case "submit":
                $icon = $node_item->getAttribute("icon");
                $id = $node_item->getAttribute("id");
                $name = $node_item->getAttribute("name");
                $value = $node_item->getAttribute("value");
                $action = $node_item->getAttribute("action");
                $title = $this->get_attribute_shortcode($node_item, "title", "", $data_type, $data_source);
                $caption = $this->process_shortcode($node_item->textContent, $data_type, $data_source);

                $btn_class = (!empty($icon) && empty($caption)) ? "icon-only" : "";
                $li_class = ($this->get_attribute_boolean($node_item, "disabled")) ? "disabled" : "";


                fwrite($handle, "<li class=\"$li_class\">\n");
                fwrite($handle, "<button class=\"$btn_class\" type=\"submit\"");

                if (!empty($id))
                    fwrite($handle, " id=\"$id\"");

                if (empty($name) && empty($action))
                    $name = "submit";

                if (!empty($name)) {
                    fwrite($handle, " name=\"$name\" value=\"$value\"");

                } else if (!empty($action)) {
                    fwrite($handle, " name=\"action\" value=\"$action\"");
                }

                if (!empty($title))
                    fwrite($handle, " title=\"$title\"");

                fwrite($handle, ">\n");


                if (!empty($icon)) {

                    $icon_class = $node_item->getAttribute("icon-class");
                    if (empty($icon_class))
                        $icon_class = "icon16";

                    fwrite($handle, "<img src=\"images/blank.png\" class=\"$icon_class $icon_class-$icon\" />");
                }

                fwrite($handle, $caption);
                fwrite($handle, "</button></li>\n");

                break;

            // -----------------------------------------------
            //  text box
            // -----------------------------------------------
            case "text":
            case "date":
                $name = $node_item->getAttribute("name");
                $id = $node_item->getAttribute("id");
                $title = $this->get_attribute_shortcode($node_item, "title", "", $data_type, $data_source);
                $placeholder = $this->get_attribute_shortcode($node_item, "placeholder", "", $data_type, $data_source);

                fwrite($handle, "<li><input type=\"text\" name=\"$name\"");

                if ($item_type == "date")
                    fwrite($handle, " class=\"dateinput\"");

                if (!empty($id))
                    fwrite($handle, " id=\"$id\"");

                if (!empty($placeholder))
                    fwrite($handle, " placeholder=\"$placeholder\"");

                if (!empty($title))
                    fwrite($handle, " title=\"$title\"");

                if ($node_item->hasAttribute("value")) {
                    $value = $this->get_attribute_shortcode($node_item, "value", "", $data_type, $data_source);
                    fwrite($handle, " value=\"$value\"");

                } else {
                    fwrite($handle, " value=\"<?php echo isset(\$_REQUEST['$name']) ? \$_REQUEST['$name'] : ''; ?>\"");
                }

                if ($node_item->hasAttribute("width"))
                    fwrite($handle, " style=\"width: " . $node_item->getAttribute("width") . ";\"");

                fwrite($handle, " /></li>\n");
                break;

            // -----------------------------------------------
            //  separator
            // -----------------------------------------------
            case "separator":
                fwrite($handle, "<li class=\"separator\"></li>");
                break;

            // -----------------------------------------------
            //  label
            // -----------------------------------------------
            case "label":
                fwrite($handle, "<li class=\"text\">");
                $this->process_node($handle, $node_item, false, true);

                fwrite($handle, "</li>");
                break;

            // -----------------------------------------------
            //  Dropdown page list
            // -----------------------------------------------
            case "page-list":
                $var_counter = $this->get_unique_varname();

                $range = max(intval($node_item->getAttribute("range")), 1);
                $prefix = $node_item->getAttribute("prefix");
                $suffix = $node_item->getAttribute("suffix");

                $params = array("page" => "$var_counter");
                $href = $this->func_build_tab_url($params, true);

                fwrite($handle, "<li class=\"dropdown\">\n");
                fwrite($handle, "<a tabindex=\"1\" href=\"#\">$prefix<?php echo \$current_page; ?>$suffix</a>\n");
                fwrite($handle, "<img class=\"close-dropdown\" src=\"images/blank.png\" alt=\"\" />\n");

                fwrite($handle, "<ul>\n");
                fwrite($handle, "<?php for($var_counter=max(1, \$current_page-$range); $var_counter<=min(\$current_page+$range, \$total_pages); $var_counter++) { ?>\n");
                fwrite($handle, "<li><a tabindex=\"1\" href=\"$href\" ?>$prefix<?php echo $var_counter; ?>$suffix</a></li>\n");
                fwrite($handle, "<?php } ?>\n");
                fwrite($handle, "</ul></li>\n");
                break;

            // -----------------------------------------------
            //  Dropdown list
            // -----------------------------------------------
            case "list":
                $node_caption = $node_item->getElementsByTagName("caption")->item(0);
                $icon = $node_item->getAttribute("icon");
                $caption =!empty($node_caption) ? $node_caption->textContent : "";

                $a_class = (!empty($icon) && empty($caption)) ? "icon-only" : "";


                fwrite($handle, "<li class=\"dropdown\" ");

                if ($node_item->hasAttribute("width"))
                    fwrite($handle, "style=\"width: " . $node_item->getAttribute('width') . ";\"");

                fwrite($handle, ">\r\n<a href=\"#\" tabindex=\"1\" class=\"$a_class\">");

                if (!empty($icon)) {

                    $icon_class = $node_item->getAttribute("icon-class");
                    if (empty($icon_class))
                        $icon_class = "icon16";

                    fwrite($handle, "<img src=\"images/blank.png\" class=\"$icon_class $icon_class-$icon\" />");
                }

                fwrite($handle, $this->process_shortcode($caption));
                fwrite($handle, "</a>\n<img class=\"close-dropdown\" src=\"images/blank.png\" alt=\"\" />\n");

                fwrite($handle, "<ul>\n");


                $node_row = $node_item->getElementsByTagName("row")->item(0);
                $node_ifempty = $node_item->getElementsByTagName("if-empty")->item(0);
                $data_source = $node_item->getAttribute("data-source");
                $data_type = strtolower($node_item->getAttribute("data-type"));

                if (!empty($node_row) && !empty($data_source)) {

                    if (!empty($node_ifempty)) {
                        fwrite($handle, "<?php if (empty(\$$data_source)): ?>\n");
                        $this->process_toolbar_tag_childs($node_ifempty, $handle);
                        fwrite($handle, "<?php else: ?>\n");
                    }

                    if ($data_source[0] != "$")
                        $data_source = "\$$data_source";

                    switch ($data_type) {

                        /* hashtable array */
                        case "dict":
                            $var_value = $this->get_unique_varname();
                            $var_key = $this->get_unique_varname();

                            fwrite($handle, "<?php foreach($data_source as $var_key => $var_value): ?>\n");

                            $this->process_toolbar_tag_childs($node_row, $handle, $data_type, array($data_source, $var_key, $var_value));

                            fwrite($handle, "<?php endforeach; ?>\n");
                            break;

                        /* ODBC query result */
                        case "odbc":

                            fwrite($handle, "<?php while (@odbc_fetch_row($data_source)): ?>\n");

                            $this->process_toolbar_tag_childs($node_row, $handle, $data_type, $data_source);

                            fwrite($handle, "<?php endwhile; ?>\n");

                            break;

                        /* Ordinary array */
                        case "array":
                            $var_value = $this->get_unique_varname();
                            fwrite($handle, "<?php foreach($data_source as $var_value): ?>\n");

                            $this->process_toolbar_tag_childs($node_row, $handle, $data_type, array($data_source, $var_value));

                            fwrite($handle, "<?php endforeach; ?>\n");
                            break;

                        /* Invalid data type */
                        default:
                            break;

                    }

                    if (!empty($node_empty))
                        fwrite($handle, "<?php endif; ?>\n");

                } else if (empty($node_row)) {

                    $this->process_toolbar_tag_childs($node_item, $handle);
                }

                fwrite($handle, "</ul></li>\n");
                break;
        }

        fwrite($handle, "\n");
    }


    /*--------------------------------------------------------------------------
     * process_tag_grid_row() : Process rows of a "datagrid" tag.
     *
     * Arguments
     * ---------
     *  - node_row       : Node to process.
     *  - handle         : File handle to the template output.
     *  - data_type      : Type of the current data source (odbc, dict).
     *  - data_source    : Current data source object.
     *  - var_hidden_col : Variable name for the total of hidden columns.
     *
     * Returns : None
     */
    private function process_tag_grid_row($node_row, $handle, $data_type, $data_source, $var_hidden_col=null)
    {
        $cell_type = ($node_row->nodeName == "row") ? "td" : "th";

        if (!(is_null($var_hidden_col)))
            fwrite($handle, "<?php $var_hidden_col=0; ?>");

        $columns = $node_row->childNodes;
        foreach ($columns as $node_column) {

            $style = $node_column->getAttribute("style");
            $type = $node_column->getAttribute("type");

            if ($node_column->hasAttribute("if"))
                $this->process_tag_if($node_column, $handle, $data_type, $data_source);


            fwrite($handle, "<$cell_type style=\"$style\"");

            if (!empty($type))
                fwrite($handle, " class=\"column-$type\"");

            fwrite($handle, ">");

            $this->process_node($handle, $node_column, false, true, $data_type, $data_source);

            fwrite($handle, "</$cell_type>\n");


            if ($node_column->hasAttribute("if")) {

                if (is_null($var_hidden_col))
                    fwrite($handle, "<?php endif; ?>");
                else
                    fwrite($handle, "<?php else: $var_hidden_col++; endif; ?>");
            }
        }

        return $columns->length;
    }


    /*--------------------------------------------------------------------------
     * process_tag_grid() : Process the template tag "datagrid"
     *
     * Arguments
     * ---------
     *  - node_tag    : Node to process.
     *  - handle      : File handle to the template output.
     *
     * Returns : None
     */
    private function process_tag_grid($node_tag, $handle)
    {
        /* Get child nodes */
        $node_row = $node_tag->getElementsByTagName("row")->item(0);
        $node_header = $node_tag->getElementsByTagName("header")->item(0);
        $node_empty = $node_tag->getElementsByTagName("if-empty")->item(0);
        $node_caption = $node_tag->getElementsByTagName("caption")->item(0);
        $node_footer = $node_tag->getElementsByTagName("footer")->item(0);

        if ($node_tag->hasAttribute("if"))
            $this->process_tag_if($node_tag, $handle, null, null);

        /* Required child node */
        if (empty($node_row))
            $this->throw_compile_exception($node_tag, "The tag 'grid' requires a row element.");

        /* Get tag attributes */
        $data_source = $node_tag->getAttribute("data-source");
        $data_type = strtolower($node_tag->getAttribute("data-type"));
        $min_rows = $node_tag->getAttribute("min-rows");
        $class = $node_tag->getAttribute("class");

        /* Required attributes */
        if (empty($data_source))
            $this->throw_compile_exception($node_tag, "The tag 'grid' requires a 'data-source' attribute.");

        if (empty($data_type))
            $this->throw_compile_exception($node_tag, "The tag 'grid' requires a 'data-type' attribute.");

        if (substr($data_source, 0, 1) != "$")
            $data_source = "\$$data_source";

        fwrite($handle, "<table id=\"\" class=\"grid $class\">\n");

        /* Generate caption tag */
        if (!empty($node_caption)) {
            fwrite($handle, "<caption>");
            fwrite($handle, $this->process_shortcode($node_caption->textContent, $data_type, $data_source));
            fwrite($handle, "</caption>\n");
        }

        $var_row_count = $this->get_unique_varname();
        $var_hidden_col = $this->get_unique_varname();

        fwrite($handle, "<?php $var_row_count=0; ?>");


        /* Generate grid header if present */
        if (!empty($node_header)) {
            fwrite($handle, "<thead><tr>");

            $this->process_tag_grid_row($node_header, $handle, $data_type, $data_source, $var_hidden_col);

            fwrite($handle, "</tr></thead>\n");
        }

        fwrite($handle, "<tbody>");


        /* Insert rows iteration code */
        switch ($data_type) {
            case "odbc":
                fwrite($handle, "<?php while (@odbc_fetch_row($data_source)): ?>\n");
                break;

            case "dict":
                $var_array_row = $this->get_unique_varname();
                fwrite($handle, "<?php foreach($data_source as $var_array_row): ?>\n");
                break;

            /* invalid data type */
            default:
                $this->throw_compile_exception($node_tag, "The tag 'grid' has an invalid data-type : $data_type");
                break;
        }


        /* Insert grid rows */
        fwrite($handle, "<tr class=\"<?php echo !($var_row_count & 1) ? '':'alt' ?>\">");

        switch ($data_type) {
            case "odbc":
                $num_columns = $this->process_tag_grid_row($node_row, $handle, $data_type, $data_source, $var_hidden_col);
                break;

            case "dict":
                $num_columns = $this->process_tag_grid_row($node_row, $handle, $data_type, array($data_source, $var_array_row), $var_hidden_col);
                break;
        }

        fwrite($handle, "</tr>");


        /* Close rows iteration code */
        switch ($data_type) {
            case "odbc":
                fwrite($handle, "<?php $var_row_count++; endwhile; ?>");
                break;

            case "dict":
                fwrite($handle, "<?php $var_row_count++; endforeach; ?>");
                break;
        }


        /* Insert if-empty row */
        if (!empty($node_empty)) {
            fwrite($handle, "<?php if ($var_row_count==0):?>");
            fwrite($handle, "<tr class=\"<?php echo !($var_row_count & 1) ? '':'alt' ?>\">");
            fwrite($handle, "<td colspan=\"<?php echo ($num_columns-$var_hidden_col) ?>  \">");

            $this->process_node($handle, $node_empty, false, false);

            fwrite($handle, "</td>\n</tr>\n<?php $var_row_count++; endif; ?>\n");
        }


        /* Insert row filler code */
        if ($min_rows) {
            fwrite($handle, "<?php while($var_row_count < $min_rows): ?>");
            fwrite($handle, "<tr class=\"<?php echo !($var_row_count & 1) ? '':'alt' ?>\">");
            fwrite($handle, "<?php echo str_repeat('<td>&nbsp;</td>', ($num_columns-$var_hidden_col)) ?>");
            fwrite($handle, "</tr><?php $var_row_count++; endwhile; ?>");
        }

        fwrite($handle, "</tbody>");


        /* Generate grid footer if present */
        if (!empty($node_footer)) {
            fwrite($handle, "<tfoot><tr>\n");

            $this->process_tag_grid_row($node_footer, $handle, $data_type, $data_source);

            fwrite($handle, "</tr></tfoot>\n");
        }

        fwrite($handle, "</table>\n");

        if ($node_tag->hasAttribute("if"))
            fwrite($handle, "<?php endif; ?>");
    }


    /*--------------------------------------------------------------------------
     * process_tag_variable() : Process template tag "variable"
     *
     * Arguments
     * ---------
     *  - node_tag : The node to process.
     *  - handle   : File handle to the template output.
     *
     * Returns : None
     */
    private function process_tag_variable($node_tag, $handle)
    {
        $node_empty = $node_tag->getElementsByTagName("if-empty")->item(0);

        $if_empty = $node_tag->getAttribute("if-empty");
        $format = $node_tag->getAttribute("format");
        $vars = explode(',', $node_tag->getAttribute("name"));

        if (!empty($format))
            $instr = "<?php printf('$format',";
        else
            $instr = "<?php echo ";

        foreach ($vars as $key => $var) {

            $var = trim($var);
            if (empty($var)) {
                unset($vars[$key]);
                continue;
            }

            $vars[$key] = "\$$var";
        }

        if (empty($vars))
            $this->throw_compile_exception($node_tag, "The tag 'variable' requires at least one variable name.");

        if (!empty($node_empty) || !empty($if_empty)) {
            fwrite($handle, "<?php if(empty(" . implode(") || empty(", $vars) . ")): ?>");

            if (!empty($node_empty))
                $this->process_node($handle, $node_empty, false, false);
            else
                fwrite($handle, $if_empty);

            fwrite($handle, "<?php else: ?>");
        }

        fwrite($handle, $instr . implode(',', $vars));

        if (!empty($format))
            fwrite($handle, ") ?>");
        else
            fwrite($handle, " ?>");

        if (!empty($node_empty) || !empty($if_empty))
            fwrite($handle, "<?php endif; ?>");
    }


    /*--------------------------------------------------------------------------
     * process_tag_callback() : Process template "callback" tag. It insert the code
     *                          to the output required to call a function inside
     *                          the plugin.
     *
     * Arguments
     * ---------
     *  - node_tag : The tag to process.
     *  - handle   : File handle to the template output.
     *  - data_type   : Type of the current data source (odbc, dict).
     *  - data_source : Current data source object.
     *
     * Returns : None
     */
    private function process_tag_callback($node_tag, $handle, $data_type=null, $data_source=null)
    {
        $name   = $node_tag->getAttribute("name");
        $return = $node_tag->getAttribute("return");
        $params = $this->get_attribute_shortcode($node_tag, "params", "", $data_type, $data_source, false);

        /* Required attribute */
        if (empty($name))
            $this->throw_compile_exception($node_tag, "The tag 'callback' requires a 'name' attribute.");


        if ($node_tag->hasAttribute("if"))
            $this->process_tag_if($node_tag, $handle, $data_type, $data_source);

        fwrite($handle, "\r\n<?php ");

        if (!empty($return)) {
            $return = preg_split("/[\s,]+/", $return);

            if (count($return) > 1)
                $return = "@list(\$" . implode(", \$", $return) . ")";
            else
                $return = "\$$return[0]";

            fwrite($handle, "$return = ");
        }

        if (is_null($this->_plugin)) {
            fwrite($handle, "$name($params); ?>");
        } else {
            fwrite($handle, "\$this->$name($params); ?>");
        }

        if ($node_tag->hasAttribute("if"))
            fwrite($handle, "<?php endif; ?>");
    }


    /*--------------------------------------------------------------------------
     * process_tokens() : Convert a list of tokens contained in a single
     *                    shortcode bracket [[...]]
     *
     * Arguments
     * ---------
     *  - tokens      : List of tokens to process.
     *  - data_type   : Type of the current data source (odbc, dict).
     *  - data_source : Current data source object.
     *
     * Returns : The converted tokens.
     */
    private function process_tokens($tokens, $data_type=null, $data_source=null)
    {
        if (empty(trim($tokens)))
            return "";

        /* Split the tokens */
        $tokens =  preg_split("/[\s|]+/", $tokens);

        foreach($tokens as $token) {

            /* The first token is the variable name, following tokens contains modifier
               functions, except for "if".  */
            if ((substr($token, 0, 2) != "if") && empty($output)) {

                /* Single variable */
                if (substr($token, 0, 1) == "$") {

                    if (strpos($token, "@") !== false) {
                        $token = explode("@", $token);

                        $output = "{$token[0]}['" . implode("][", array_slice($token, 1)) . "']";

                    } else {
                        $output = $token;
                    }

                /* Constant */
                } else if (substr($token, 0, 1) == "#") {
                    $output = substr($token, 1);

                } else {


                    switch ($data_type) {
                        /* Ordinary array */
                        case "array":
                            if (strtolower($token) == "value") {
                                $output = $data_source[1];
                            } else {
                                $output = "\$$token";
                            }

                            break;

                        /* ODBC resultset */
                        case "odbc":
                            $output = "@odbc_result($data_source, '$token')";
                            break;

                        /* Dictionary array */
                        case "dict":
                            if (count($data_source) == 3) {

                                switch (strtolower($token)) {
                                    case "key":
                                        $output = $data_source[1];
                                        break;

                                    case "value":
                                        $output = $data_source[2];
                                        break;

                                    default:
                                        $output = "\$$token";
                                        break;
                                }
                            } else {

                                $output = $data_source[1] . "['$token']";
                            }
                            break;

                        /* Single variable */
                        default:
                            $output = "\$$token";
                            break;
                    }
                }
            } else {

                $params = explode(":", $token);

                switch ($params[0]) {
                    case 'if':


                        $output = "(({$params[1]}) ? {$params[2]} : {$params[3]})";
                        break;

                    case "lower":
                        $output = "strtolower($output)";
                        break;

                    case "upper":
                        $output = "strtoupper($output)";
                        break;

                    case "ucfirst":
                        $output = "ucfirst($output)";
                        break;

                    case "ucwords":
                        $output = "ucwords($output)";
                        break;

                    case "var_dump":
                        $output = "var_dump($output)";
                        break;

                    case "explode":
                        $sep = " ";

                        if (!empty($params[1])) {
                            $sep = $params[1];
                        }

                        $output = "explode('" . addslashes($sep) . "', $output)";
                        break;

                    case "format_phone":
                        $output = "format_phone_number($output";

                        if (isset($params[1]) && strtolower($params[1]) == "false")
                            $output .= ", false";

                        $output .= ")";

                        break;

                    case "format_time_seconds":
                    case "format_seconds":
                        $output = "format_time_seconds($output)";
                        break;

                    case "money_format":
                    case "format_money":
                        if (isset($params[1])) {
                            $output = "money_format('{$params[1]}', $output)";
                        } else {
                            $output = "money_format('" . $this->currency_format . "', $output)";
                        }

                        break;

                    case "dumpfile":
                        $output = "dumpfile($output)";
                        break;

                    case "format_unix_time":
                        $output = "format_unix_time($output)";
                        break;

                    case "format_byte":
                        $output = "format_byte($output)";
                        break;
               }
            }
        }

        return $output;
    }


    /*--------------------------------------------------------------------------
     * process_shortcode() : Convert the shortcode syntax contained in a string.
     *
     * Arguments
     * ---------
     *  - text        : The text containing the shortcodes.
     *  - data_type   : Type of the current data source (odbc, dict).
     *  - data_source : Current data source object.
     *  - wrap        : Wrap each shortcode element within php tags. <?php echo ... ?>
     *
     * Returns : The converted text.
     */
    private function process_shortcode($text, $data_type=null, $data_source=null, $wrap=true)
    {
        $offset = 0;
        while(($offset = strpos($text, "[[", $offset)) !== false) {

            if (($end = strpos($text, "]]", $offset)) === false)
                break;

            $tokens = trim(substr($text, $offset + 2, $end - $offset - 2));

            $output = $this->process_tokens($tokens, $data_type, $data_source);

            if (empty($output))
                continue;

            if ($wrap)
                $output = "<?php echo $output ?>";

            $text = substr_replace($text, $output, $offset, $end - $offset + 2);
            $offset = $offset + strlen($output);
        }

        return $text;
    }


    /*--------------------------------------------------------------------------
     * get_unique_varname() : Generate an unique variable name to be used in
     *                        the template output.
     *
     * Arguments
     * ---------
     *  None
     *
     * Returns : Unique variable name.
     */
    private function get_unique_varname()
    {
        return "\$v" . $this->unique_base . $this->unique_id++;
    }
}
