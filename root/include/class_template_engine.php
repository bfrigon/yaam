<?php
//******************************************************************************
// class.TemplateEngine.php - Template engine
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
class TemplateEngine
{
    private $_template_dir;
    private $_cache_dir;
    private $_plugin;

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
            $this->_template_dir = $plugin->PLUGIN_DIR;

        $this->_cache_dir = dirname(__FILE__) . "/../cache";
    }


    /*--------------------------------------------------------------------------
     * load() : Load the precompiled template or compile it if non-existant.
     *
     * Arguments
     * ---------
     *  - template_name : Template file to load (relative to the template directory)
     *  - use_global : Force loading from global template directory.
     *
     * Returns : Filename of the compiled template
     */
    function load($template_name, $use_global=false)
    {

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
        $this->compile($template_file, $cache_file);

        return $cache_file;
    }


    /*--------------------------------------------------------------------------
     * compile(): Compile the template
     *
     * Arguments
     * ---------
     *  - template_file : Source template file(relative to the template directory)
     *  - cache_file    : Compiled template file
     *
     * Returns : Filename of the compiled template
     */
    private function compile($template_file, $cache_file)
    {

        $compile_start = microtime(true);

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

        $this->process_node($handle, $dom_input, false, true);

        $compile_time = microtime(true) - $compile_start;
        printf("Compile time: %0.4f s", $compile_time);

        fclose($handle);
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

        return $this->convert_shortcode($value, $data_type, $data_source, $wrap);
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
        $output = "<?php echo \$this->build_tab_url(";

        if (is_null($params)) {
            $output .= "null, ";

        } else {

            $output .= "array(";

            foreach($params as $name => $value)
                $output .= "\"$name\" => $value, ";

            $output .= "), ";
        }

        $output .= ($keep_uri ? "true" : "false") . ", ";
        $output .= ($no_referrer ? "true" : "false") . ") ?>";
        return $output;
    }


    /*--------------------------------------------------------------------------
     * process_node() : Compile a node from the template file
     *
     * Arguments
     * ---------
     *  - handle      : File handle to the template output.
     *  - node        : Node to process.
     *  - outer       : Include the node in the output. If false, only process child nodes.
     *  - recursive   : Process all child nodes recursivly.
     *  - data_type   : Type of the Current data source (odbc, dict).
     *  - data_source : Current data source object.
     *
     * Returns : None
     */
    private function process_node($handle, $node, $outer=true, $recursive=true, $data_type=null, $data_source=null)
    {

        if ($outer) {
            fwrite($handle, "<" . $node->nodeName);

            if ($node->hasAttributes()) {
                foreach ($node->attributes as $attrib) {

                    $value = $this->convert_shortcode($attrib->value, $data_type, $data_source);

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

                                case "foreach":
                                    $this->process_tag_foreach($child, $handle, $data_type, $data_source);
                                    break;

                                case "icon":
                                    $this->process_tag_icon($child, $handle, $data_type, $data_source);
                                    break;

                                case "toolbar":
                                    $this->process_tag_toolbar($child, $handle, $data_type, $data_source);
                                    break;

                                case "field":
                                    $this->process_tag_field($child, $handle, $data_type, $data_source);
                                    break;

                                case "if":
                                    $this->process_tag_if($child, $handle, $data_type, $data_source);
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

                                case "clear":
                                    fwrite($handle, "<div class=\"clear\"></div>");
                                    break;

                                case "call":
                                case "callback":
                                    $this->process_tag_callback($child, $handle, $data_type, $data_source);
                                    break;

                                case "script":
                                    fwrite($handle, $child->ownerDocument->saveHTML($child));
                                    break;

                                default:
                                    $this->process_node($handle, $child, true, true, $data_type, $data_source);
                                    break;
                            }
                            break;

                        case XML_PI_NODE:
                        case XML_COMMENT_NODE:
                            fwrite($handle, $child->ownerDocument->saveHTML($child));
                            break;

                        case XML_TEXT_NODE:
                            $html = $this->convert_shortcode($child->ownerDocument->saveHTML($child), $data_type, $data_source);
                            fwrite($handle, $html);
                            break;

                        case XML_DOCUMENT_TYPE_NODE:
                            fwrite($handle, $child->ownerDocument->saveXML($child));
                            break;
                    }
                } else {


                    $html = $this->convert_shortcode($child->ownerDocument->saveXML($child), $data_type, $data_source);
                    fwrite($handle, $html);
                }

            } while(($child = $child->nextSibling) != null);
        }

        if ($outer)
            fwrite($handle, "</{$node->nodeName}>");
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
    private function process_tag_form($node_form, $handle, $data_type=null, $data_source=null)
    {
        $method = strtolower($node_form->getAttribute("method"));

        $element = $node_form->ownerDocument->createElement("input");
        $element->setAttribute("type", "hidden");
        $element->setAttribute("name", "path");
        $element->setAttribute("value", "<?php echo \$_GET[\"path\"] ?>");

        $node_form->appendChild($element);

        $element = $node_form->ownerDocument->createElement("input");
        $element->setAttribute("type", "hidden");
        $element->setAttribute("name", "referrer");
        $element->setAttribute("value", "<?php echo \$this->get_tab_url(true) ?>");

        $node_form->appendChild($element);


        $this->process_node($handle, $node_form, true, true, $data_type, $data_source);
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
    private function process_tag_foreach($node_foreach, $handle, $data_type=null, $data_source=null)
    {
        /* Get tag attributes */

        $data_source = (is_null($data_source)) ? $node_foreach->getAttribute("data-source") : $data_source;
        $data_type = strtolower((is_null($data_type)) ? $node_foreach->getAttribute("data-type") : $data_type);
        $type = strtolower($node_foreach->getAttribute("type"));
        $class = $node_foreach->getAttribute("class");

        $prefix_html = "";
        $suffix_html = "";


        /* Required attributes */
        if (is_null($data_type) || is_null($data_source))
            return;

        if ($type == "list") {
            fwrite($handle, "<ul class=\"list $class\">");
            $prefix_html = "<li>";
            $suffix_html = "</li>";
        }

        /* Insert rows iteration code */
        switch ($data_type) {

            /* hashtable array */
            case "dict":
                $var_value = $this->get_unique_varname();
                $var_key = $this->get_unique_varname();

                fwrite($handle, "<?php foreach(\$$data_source as $var_key => $var_value): ?>$prefix_html");

                $this->process_node($handle, $node_foreach, false, true, $data_type, array($data_source, $var_key, $var_value));

                fwrite($handle, "$suffix_html<?php endforeach; ?>");
                break;

            /* ODBC query result */
            case "odbc":

                fwrite($handle, "<?php while (@odbc_fetch_row($$data_source)): ?>$prefix_html");

                $this->process_node($handle, $node_foreach, false, true, $data_type, $data_source);

                fwrite($handle, "$suffix_html<?php endwhile; ?>");
                break;

            /* Ordinary array */
            case "array":
                $var_value = $this->get_unique_varname();
                fwrite($handle, "<?php foreach(\$$data_source as $var_value): ?>$prefix_html");

                $this->process_node($handle, $node_foreach, false, true, $data_type, array($data_source, $var_value));

                fwrite($handle, "$suffix_html<?php endforeach; ?>");
                break;

            /* Invalid data type */
            default:
                break;
        }

        if ($type == "list")
            fwrite($handle, "</ul>");
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
     *
     * Returns : None
     */
    private function process_tag_if($node_if, $handle, $data_type=null, $data_source=null)
    {

        $type = strtolower($node_if->getAttribute("type"));
        $name = $node_if->getAttribute("name");
        $value = $this->get_attribute_shortcode($node_if, "value", "", $data_type, $data_source);


        switch ($type) {

            /* Permission check */
            case "permission":
            case "perm":
                fwrite($handle, "<?php if (check_permission('$value')): ?>");
                break;

            /* Check if variable Boolean */
            case "boolean":
            case "bool":
                fwrite($handle, "<?php if (\$$name === true): ?>");
                break;

            case "is":
            default:
                fwrite($handle, "<?php if (\$$name == '$value'): ?>");
                break;

       }

        $this->process_node($handle, $node_if, false, true, $data_type, $data_source);

        fwrite($handle, "<?php endif; ?>");
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
        $action = $this->get_attribute_shortcode($node_tag, "action", "", $data_type, $data_source);
        $title = $this->get_attribute_shortcode($node_tag, "title", "", $data_type, $data_source);
        $params = $this->get_attribute_shortcode($node_tag, "params", "", $data_type, $data_source, false);
        $href = $this->get_attribute_shortcode($node_tag, "href", "", $data_type, $data_source);
        $id = $node_tag->getAttribute("id");
        $caption = $this->convert_shortcode($node_tag->textContent, $data_type, $data_source);

        $keep_uri = $this->get_attribute_boolean($node_tag, "keep-uri", false);
        $keep_referrer = $this->get_attribute_boolean($node_tag, "keep-referrer", false);
        $force = $this->get_attribute_boolean($node_tag, "force-update", false);

        $btn_class = (!empty($icon) && empty($caption)) ? "icon-only" : "";

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

                        if ($keep_referrer)
                            $url_params["referrer"] = "\$_GET['referrer']";
                        else
                            $url_params["referrer"] = "\$this->get_tab_url(true)";

                        $href = $this->func_build_tab_url($url_params, $keep_uri, $no_referrer);
                        break;
                }
            }
        }

        if ($force)
            $btn_class .= " force-update ";

        if (!empty($href))
            fwrite($handle, "<a id=\"$id\" href=\"$href\" class=\"$btn_class\" tabindex=\"1\" title=\"$title\" >");

        if (!empty($icon)) {

            $icon_class = $node_tag->getAttribute("icon-class");
            if (empty($icon_class))
                $icon_class = "icon16";

            fwrite($handle, "<img src=\"images/blank.png\" class=\"$icon_class $icon_class-$icon\" />");
        }

        fwrite($handle, $caption);

        if (!empty($href))
            fwrite($handle, "</a>");
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

        fwrite($handle, "<label for=\"$name\">$caption :</label>");

        switch ($type) {

            // -----------------------------------------------
            //  Select box
            // -----------------------------------------------
            case "select":
                $data_type = strtolower(is_null($data_type) ? trim($node_tag->getAttribute("data-type")) : $data_type);
                $data_source = (is_null($data_source) ? trim($node_tag->getAttribute("data-source")) : $data_source);
                $column_key = $node_tag->getAttribute("column-key");
                $column_value = $node_tag->getAttribute("column-value");

                if (empty($data_type) || empty($data_source))
                    break;

                fwrite($handle, "<select name=\"$name\" id=\"$id\">");

                /* Insert rows iteration code */
                switch ($data_type) {

                    /* hashtable array */
                    case "dict":
                        $var_value = $this->get_unique_varname();
                        $var_key = $this->get_unique_varname();

                        fwrite($handle, "<?php foreach(\$$data_source as $var_key => $var_value): ?>\n");

                        fwrite($handle, "<option value=\"<?php echo $var_key ?>\"");
                        fwrite($handle, " <?php echo ($var_key == $value) ? 'selected' : '' ?> >");
                        fwrite($handle, "<?php echo $var_value ?></option>\n");

                        fwrite($handle, "<?php endforeach; ?>");
                        break;

                    /* ODBC query result */
                    case "odbc":
                        fwrite($handle, "<?php while (@odbc_fetch_row($$data_source)): ?>");

                        fwrite($handle, "<option value=\"<?php echo odbc_result($$data_source, '$column_key') ?>\"");
                        fwrite($handle, " <?php echo (odbc_result($$data_source, '$column_key') == \"$value\") ? 'selected' : '' ?> >");
                        fwrite($handle, "<?php echo odbc_result($$data_source, '$column_value') ?></option>\n");

                        fwrite($handle, "<?php endwhile; ?>");
                        break;

                    /* Ordinary array */
                    case "array":
                        $var_value = $this->get_unique_varname();
                        $var_index = $this->get_unique_varname();
                        fwrite($handle, "<?php $var_index=0; foreach(\$$data_source as $var_value): ?>");

                        fwrite($handle, "<option value=\"<?php echo $var_index ?>\"");
                        fwrite($handle, " <?php echo ($var_index == intval(\"$value\")) ? 'selected' : '' ?> >");
                        fwrite($handle, "<?php echo $var_value ?></option>\n");

                        fwrite($handle, "<?php $var_index++; endforeach; ?>");
                        break;

                    /* Invalid data type */
                    default:
                        break;
                }

                fwrite($handle, "</select>\n");
                break;

            // -----------------------------------------------
            //  Read-only textbox
            // -----------------------------------------------
            case "view":
            case "readonly":
                fwrite($handle, "<input type=\"text\"");

                if (!empty($id))
                    fwrite($handle, " id=\"$id");

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
            fwrite($handle, $node_help->textContent);
            fwrite($handle, "</span></a>");
        }
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
        $id = $node_tag->getAttribute("id");
        if (empty($id))
            $id = "{$this->tab_id}_toolbar";

        $class = $node_tag->getAttribute("class");
        if (empty($class))
            $class = "box";


        fwrite($handle, "<div class=\"clear\"></div>");
        fwrite($handle, "<div class=\"toolbar $class\" id=\"$id\"><ul>");

        $this->process_toolbar_items($node_tag, $handle, $data_type, $data_source);

        fwrite($handle, "</ul><div class=\"clear\"></div>");
        fwrite($handle, "</div><div class=\"clear\"></div>");
    }


    /*--------------------------------------------------------------------------
     * process_toolbar_items() : Process nodes "item" in "toolbar" tag.
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
    private function process_toolbar_items($node_list, $handle, $data_type=null, $data_source=null)
    {

        foreach ($node_list->childNodes as $node_item) {


            if ($node_item->nodeName == "group") {

                $id = $node_item->getAttribute("id");

                fwrite($handle, "</ul><ul");

                if (!empty($id))
                    fwrite($handle, " id=\"$id\"");

                fwrite($handle, ">");


                $this->process_toolbar_items($node_item, $handle, $data_type, $data_source);

                fwrite($handle, "</ul><ul>");
            }


            /* Ignore other tags */
            if ($node_item->nodeName != "item")
                continue;


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
                    $caption = $this->convert_shortcode($node_item->textContent, $data_type, $data_source);

                    $btn_class = (!empty($icon) && empty($caption)) ? "icon-only" : "";
                    $li_class = ($this->get_attribute_boolean($node_item, "disabled")) ? "disabled" : "";


                    fwrite($handle, "<li class=\"$li_class\">\n");
                    fwrite($handle, "<button class=\"$btn_class\" type=\"submit\"");

                    if (!empty($id))
                        fwrite($handle, " id=\"$id\"");

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

                    fwrite($handle, "<li><input type=\"text\" name=\"$name\"");

                    if ($item_type == "date")
                        fwrite($handle, " class=\"dateinput\"");

                    if (!empty($id))
                        fwrite($handle, " id=\"$id\"");

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

                    fwrite($handle, $this->convert_shortcode($caption));
                    fwrite($handle, "</a>\n<img class=\"close-dropdown\" src=\"images/blank.png\" alt=\"\" />\n");

                    fwrite($handle, "<ul>\n");


                    $node_row = $node_item->getElementsByTagName("row")->item(0);
                    $node_ifempty = $node_item->getElementsByTagName("if-empty")->item(0);
                    $data_source = $node_item->getAttribute("data-source");
                    $data_type = strtolower($node_item->getAttribute("data-type"));

                    if (!empty($node_row) && !empty($data_source)) {

                        if (!empty($node_ifempty)) {
                            fwrite($handle, "<?php if (empty(\$$data_source)): ?>");
                            $this->process_toolbar_items($node_ifempty, $handle);
                            fwrite($handle, "<?php else: ?>");
                        }

                        switch ($data_type) {

                            /* hashtable array */
                            case "dict":
                                $var_value = $this->get_unique_varname();
                                $var_key = $this->get_unique_varname();

                                fwrite($handle, "<?php foreach(\$$data_source as $var_key => $var_value): ?>");

                                $this->process_toolbar_items($node_row, $handle, $data_type, array($data_source, $var_key, $var_value));

                                fwrite($handle, "<?php endforeach; ?>");
                                break;

                            /* ODBC query result */
                            case "odbc":

                                fwrite($handle, "<?php while (@odbc_fetch_row($$data_source)): ?>");

                                $this->process_toolbar_items($node_row, $handle, $data_type, $data_source);

                                fwrite($handle, "<?php endwhile; ?>");

                                break;

                            /* Ordinary array */
                            case "array":
                                $var_value = $this->get_unique_varname();
                                fwrite($handle, "<?php foreach(\$$data_source as $var_value): ?>");

                                $this->process_toolbar_items($node_row, $handle, $data_type, array($data_source, $var_value));

                                fwrite($handle, "<?php endforeach; ?>");
                                break;

                            /* Invalid data type */
                            default:
                                break;

                        }

                        if (!empty($node_empty))
                            fwrite($handle, "<?php endif; ?>");

                    } else if (empty($node_row)) {

                        $this->process_toolbar_items($node_item, $handle);
                    }

                    fwrite($handle, "</ul></li>\n");
                    break;
            }
        }

        fwrite($handle, "\n");
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

        /* Required child node */
        if (empty($node_row))
            return;

        /* Get tag attributes */
        $data_source = $node_tag->getAttribute("data-source");
        $data_type = strtolower($node_tag->getAttribute("data-type"));
        $min_rows = $node_tag->getAttribute("min-rows");
        $class = $node_tag->getAttribute("class");

        /* Required attributes */
        if (empty($data_source) || empty($data_type))
            return;

        fwrite($handle, "<table id=\"\" class=\"grid $class\">\n");

        /* Generate caption tag */
        if (!empty($node_caption)) {
            fwrite($handle, "<caption>");
            fwrite($handle, $this->convert_Shortcode($node_caption->textContent, $data_type, $data_source));
            fwrite($handle, "</caption>\n");
        }

        $var_count = $this->get_unique_varname();
        fwrite($handle, "<?php $var_count=0 ?>");

        /* Generate grid header if present */
        if (!empty($node_header)) {
            fwrite($handle, "<thead><tr>");

            foreach ($node_header->getElementsByTagName("column") as $node_column) {
                $style = $node_column->getAttribute("style");
                $type = $node_column->getAttribute("type");

                fwrite($handle, "<th style=\"$style\" class=\"column-$type\">");

                $this->process_node($handle, $node_column, false, false, $data_type, $data_source);

                fwrite($handle, "</th>");
            }

            fwrite($handle, "</tr></thead>\n");
        }

        fwrite($handle, "<tbody>");

        /* Insert rows iteration code */
        switch ($data_type) {
            case "odbc":
                fwrite($handle, "<?php while (@odbc_fetch_row($$data_source)): ?>");
                break;

            /* invalid data type */
            default:
                break;
        }

        /* Insert grid rows */
        fwrite($handle, "<tr class=\"<?php echo !($var_count & 1) ? '':'alt' ?>\">");

        $columns = $node_row->getElementsByTagName("column");
        $num_columns = $columns->length;


        foreach ($columns as $node_column) {
            fwrite($handle, "<td");

            $type = $node_column->getAttribute("type");
            if (!empty($type))
                fwrite($handle, " class=\"column-$type\"");

            $style = $node_column->getAttribute("style");
            if (!empty($style))
                fwrite($handle, " style=\"$style\"");

            fwrite($handle, ">");

            $this->process_node($handle, $node_column, false, true, $data_type, $data_source);
            fwrite($handle, "</td>");

        } while(($node_column = $node_column->nextSibling) != null);

        fwrite($handle, "</tr>");

        switch ($data_type) {
            case "odbc":
                fwrite($handle, "<?php $var_count++; endwhile; ?>");
                break;

            /* Invalid data type */
            default:
                break;
        }

        if (!empty($node_empty)) {
            fwrite($handle, "<?php if ($var_count==0):?>");
            fwrite($handle, "<tr class=\"<?php echo !($var_count & 1) ? '':'alt' ?>\">");
            fwrite($handle, "<td colspan=\"$num_columns\">");

            $this->process_node($handle, $node_empty, false, false);

            fwrite($handle, "</td></tr><?php $var_count++; endif; ?>");
        }

        if ($min_rows) {
            fwrite($handle, "<?php while($var_count < $min_rows): ?>");
            fwrite($handle, "<tr class=\"<?php echo !($var_count & 1) ? '':'alt' ?>\">");
            fwrite($handle, str_repeat("<td>&nbsp;</td>", $num_columns));
            fwrite($handle, "</tr><?php $var_count++; endwhile; ?>");
        }

        fwrite($handle, "</tbody>");


        /* Generate grid footer if present */
        if (!empty($node_footer)) {
            fwrite($handle, "<tfoot><tr>");

            foreach ($node_footer->getElementsByTagName("column") as $node_column) {
                $style = $node_column->getAttribute("style");
                $type = $node_column->getAttribute("type");

                fwrite($handle, "<th style=\"$style\" class=\"column-$type\">");

                $this->process_node($handle, $node_column, false, false, $data_type, $data_source);

                fwrite($handle, "</th>");
            }

            fwrite($handle, "</tr></tfoot>");
        }

        fwrite($handle, "</table>");
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
            return;

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
            return;

        fwrite($handle, "\r\n");

        if (!empty($return)) {
            $return = preg_split("/[\s,]+/", $return);

            if (count($return) > 1)
                $return = "@list(\$" . implode(", \$", $return) . ")";
            else
                $return = "\$$return";


            fwrite($handle, "<?php $return = ");
        }

        fwrite($handle, "\$this->$name($params); ?>");
    }


    /*--------------------------------------------------------------------------
     * convert_shortcode() : Convert the shortcode syntax contained in a string.
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
    private function convert_shortcode($text, $data_type=null, $data_source=null, $wrap=true)
    {
        $offset = 0;
        while(($offset = strpos($text, "[[", $offset)) !== false) {

            if (($end = strpos($text, "]]", $offset)) === false)
                break;

            /* Split the tokens */
            $tokens =  preg_split("/[\s|]+/", trim(substr($text, $offset + 2, $end - $offset - 2)));

            $output = '';
            foreach($tokens as $token) {

                /* The first token is the variable name, following tokens contains modifier
                   functions, except for "if".  */
                if ((substr($token, 0, 2) != "if") && empty($output)) {

                    if ($token[0] == "$") {

                        if (strpos($token, "@") !== false) {
                            $token = explode("@", $token);

                            $output = "{$token[0]}[" . implode("][", array_slice($token, 1)) . "]";

                        } else {
                            $output = $token;
                        }

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
                                $output = "odbc_result(\$$data_source, '$token')";
                                break;

                            /* Dictionary array */
                            case "dict":
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

                        case "dumpgzfile":
                            $output = "dumpgzfile($output)";
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
     * Returns   : Unique variable name.
     */
    private function get_unique_varname()
    {
        return "\$v" . $this->unique_base . $this->unique_id++;
    }
}
