<?php
//******************************************************************************
//
// Project : Asterisk Y.A.A.M (Yet another asterisk manager)
// Author  : Benoit Frigon <www.bfrigon.com>
//
// Contributors
// ============
//
//  Rafael G. Dantas <rafagd@gmail.com>
//
//
// -----------------------------------------------------------------------------
//
// Copyright (c) 2017 Benoit Frigon
//
// Permission is hereby granted, free of charge, to any person obtaining a copy
// of this software and associated documentation files (the "Software"), to deal
// in the Software without restriction, including without limitation the rights
// to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
// copies of the Software, and to permit persons to whom the Software is
// furnished to do so, subject to the following conditions:
//
// The above copyright notice and this permission notice shall be included in
// all copies or substantial portions of the Software.
//
// THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
// IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
// FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
// AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
// LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
// OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
// THE SOFTWARE.
//
//******************************************************************************

if(realpath(__FILE__) == realpath($_SERVER["SCRIPT_FILENAME"])) {
    header("Location:../index.php");
    exit();
}


class TagProcessorBase
{

    protected $_engine = null;

    public $processors = null;
    public $currency_format = null;

    /*--------------------------------------------------------------------------
     * __construct()
     *
     * Arguments :
     * ---------
     *
     * Returns : None
     */
    function __construct($engine)
    {
        $this->_engine = $engine;
        $this->plugin = $engine->plugin;

        $this->processors = &$engine->processors;
        $this->currency_format = &$engine->currency_format;
        $this->template_file = &$engine->template_file;
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
    protected function process_node($handle, $node, $outer=true, $recursive=true, $data_type=null, $data_source=null, $process_top_level=false)
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

            $node_child = $node->firstChild;
            do {
                if ($recursive) {

                    switch ($node_child->nodeType) {
                        case XML_ELEMENT_NODE:

                            switch ($node_child->nodeName) {

                                case "dialog":
                                case "toolbar":
                                case "icon":
                                case "grid":
                                case "datagrid":
                                case "var":
                                case "variable":
                                case "call":
                                case "callback":
                                case "if":
                                case "foreach":
                                case "form":
                                case "action-list":
                                case "actions":
                                    $this->processors[$node_child->nodeName]->process_tag($node_child, $handle, $data_type, $data_source);
                                    break;

                                case "head":
                                    if ($process_top_level == false)
                                        break;

                                    /* fall-through */

                                case "script":
                                case "html":
                                case "body":
                                    $this->process_node($handle, $node_child, $process_top_level, true, $data_type, $data_source, $process_top_level);
                                    break;

                                case "noparse":
                                case "raw":
                                    foreach ($node_child->childNodes as $node_raw) {
                                        $html = $node_raw->ownerDocument->saveXML($node_raw);
                                        fwrite($handle, $html);
                                    }
                                    break;

                                case "br":
                                    fwrite($handle, "<br />");
                                    break;

                                default:
                                    $this->process_node($handle, $node_child, true, true, $data_type, $data_source, $process_top_level);
                                    break;
                            }
                            break;

                        case XML_PI_NODE:
                            fwrite($handle, $node_child->ownerDocument->saveXML($node_child));
                            break;

                        case XML_COMMENT_NODE:
                            $comment = explode(":", $node_child->textContent, 2);

                            if ((trim(strtolower($comment[0])) != "syntax") || !(isset($comment[1])))
                                break;

                            $text = trim(htmlentities($comment[1]));

                            fwrite($handle, "<div class=\"box viewer code\"><div class=\"content\">");
                            fwrite($handle, $text);
                            fwrite($handle, "</div></div>");
                            break;

                        case XML_TEXT_NODE:
                            $html = $this->process_shortcode($node_child->ownerDocument->saveXML($node_child), $data_type, $data_source);
                            fwrite($handle, $html);
                            break;

                        case XML_DOCUMENT_TYPE_NODE:
                            if ($process_top_level)
                                fwrite($handle, $node_child->ownerDocument->saveXML($node_child));

                            break;
                    }
                } else {

                    $html = $this->process_shortcode($node_child->ownerDocument->saveXML($node_child), $data_type, $data_source);
                    fwrite($handle, $html);
                }

            } while(($node_child = $node_child->nextSibling) != null);
        }

        if ($outer)
            fwrite($handle, "</{$node->nodeName}>");
    }


    /*--------------------------------------------------------------------------
     * process_variable() : Process variable syntax.
     *
     * Arguments
     * ---------
     *  - variable       : Variable syntax to process.
     *  - unset_variable : Default value if variable is not set.
     *
     * Returns : The variable display code.
     */
    private function process_variable($variable, $unset_variable="''")
    {
        if (is_numeric($variable))
            return $variable;

        if (substr($variable, 0, 1) != "$")
            $variable = "\$$variable";

        if (strpos($variable, ".") !== false) {
            $brackets = explode(".", $variable);
            $variable = $brackets[0];

            foreach (array_slice($brackets,1) as $bracket) {
                $variable .= (is_numeric($bracket) ? "[$bracket]" : "['$bracket']");
            }

            if (is_null($unset_variable))
                return $variable;
            else
                return "(isset($variable) ? $variable : $unset_variable)";

        } else {

            if (is_null($unset_variable))
                return $variable;
            else
                return "(isset($variable) ? $variable : $unset_variable)";
        }
    }


    /*--------------------------------------------------------------------------
     * split_filters() : Split filters and their parameters
     *
     * Arguments
     * ---------
     *  - input          : List of filters to process.
     *
     * Returns : The filter list in an array.
     */
    private function split_filters($input)
    {
        $filters = array();
        $filter = array();
        $string_delimiter = "";
        $segment = "";

        foreach (preg_split("/(\\\\\"|\\\\'|'|\"|:|\\|)/", $input, null, PREG_SPLIT_DELIM_CAPTURE) as $text) {
            switch ($text) {

                /* Filter delimiter */
                case "|":
                    /* Include the '|' character if currently inside a string */
                    if (!(empty($string_delimiter))) {
                        $segment = "$segment|";
                        continue;
                    }

                    $filter[] = $segment;
                    $segment = "";

                    $filters[] = $filter;
                    $filter = array();
                    break;


                /* Parameter delimiter */
                case ":":
                    /* Include the ':' character if currently inside a string */
                    if (!(empty($string_delimiter))) {
                        $segment = "$segment:";
                        continue;
                    }

                    $filter[] = $segment;
                    $segment = "";

                    break;

                /* String delimiter */
                case '"':
                case "'":
                    /* Open a string */
                    if (empty($string_delimiter)) {
                        $string_delimiter = $text;
                        continue;
                    }

                    /* Make it part of the string if different from the delimiter */
                    if ($string_delimiter != $text) {
                        $segment .= $text;
                        continue;
                    }

                    /* Close the string */
                    $string_delimiter = "";
                    break;

                /* Remove slashes for single and double quotes */
                case "\\'":
                case "\\\"":
                    $text = substr($text, 1);
                    /* Fall-throught */


                /* text */
                default:
                    /* Trim segments if outside of a string */
                    if (empty($string_delimiter)) {
                        $segment .= trim($text);
                    } else {
                        $segment .= $text;
                    }

                    break;
            }
        }

        $filter[] = $segment;
        $filters[] = $filter;

        return $filters;
    }


    /*--------------------------------------------------------------------------
     * process_filters() : Convert a list of filters contained in a single
     *                     shortcode bracket [[...]]
     *
     * Arguments
     * ---------
     *  - input          : List of filters to process.
     *  - data_type      : Type of the current data source (odbc, dict).
     *  - data_source    : Current data source object.
     *  - unset_variable : Default value if variable is not set.
     *
     * Returns : The converted filters code.
     */
    protected function process_filters($input, $data_type=null, $data_source=null, $unset_variable="''")
    {
        $input = trim($input);

        if (empty($input))
            return (is_null($unset_variable) ? '' : $unset_variable);

        foreach($this->split_filters($input) as $filter) {

            /* The first one in the list is the variable name */
            if (empty($output)) {

                /* Single variable */
                if (substr($filter[0], 0, 1) == "$") {
                    $output = $this->process_variable($filter[0], $unset_variable);

                /* Constant */
                } else if (substr($filter[0], 0, 1) == "#") {
                    $output = substr($filter[0], 1);

                } else {

                    switch ($data_type) {
                        /* ODBC resultset */
                        case "odbc":
                            $output = "@odbc_result($data_source, '{$filter[0]}')";
                            break;

                        /* Dictionary array */
                        case "array":

                            $brackets = null;
                            if (strpos($filter[0], ".") !== false) {
                                $brackets = explode(".", $filter[0]);
                                $filter[0] = $brackets[0];

                                $brackets = array_slice($brackets, 1);
                            }

                            switch (strtolower($filter[0])) {
                                case "key":
                                    $output = ((count($data_source) == 3) ? $data_source[1] : "key({$data_source[0]})");
                                    break;

                                case "value":
                                case "row":
                                case "":
                                    $output = ((count($data_source) == 3) ? $data_source[2] : $data_source[1]);

                                    if (!(empty($brackets))) {
                                        foreach ($brackets as $bracket) {
                                            $output .= "['$bracket']";
                                        }
                                    }

                                    if (!(is_null($unset_variable))) {
                                        $output = "(isset($output) ? $output : $unset_variable)";
                                    }
                                    break;

                                default:
                                    $output = $this->process_variable($filter[0], $unset_variable);
                                    break;
                            }
                            break;

                        /* Single variable */
                        default:
                            $output = $this->process_variable($filter[0], $unset_variable);
                            break;
                    }
                }
            } else {


                switch ($filter[0]) {
                    case "if":
                        $var_true = (isset($filter[1]) ? addslashes($filter[1]) : "");
                        $var_false = (isset($filter[2]) ? addslashes($filter[2]) :  "");

                        $output = "($output ? '$var_true' : '$var_false')";
                        break;

                    case "pluralize":
                        $var_true = (isset($filter[1]) ? addslashes($filter[1]) : "");
                        $var_false = (isset($filter[2]) ? addslashes($filter[2]) : "");

                        $output = "((intval($output) > 1) ? '$var_true' : '$var_false')";
                        break;

                    case "count":
                        $output = "intval(count($output))";
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

                    case "wrap":
                        $num = (isset($filter[1]) ? intval($filter[1]) : 50);
                        $output = "wordwrap($output, $num, '<br/>')";
                        break;

                    case "ellipses":
                        $num = (isset($filter[1]) ? intval($filter[1]) : 50);
                        $output = "(strlen($output) > $num) ? substr($output, 0, $num) . '&hellip;' : $output";
                        break;

                    case "length":
                        $output = "intval(strlen($output))";
                        break;

                    case "var_dump":
                        $output = "var_dump($output)";
                        break;

                    case "limit":
                        $num = (isset($filter[1]) ? intval($filter[1]) : 1);
                        $output = "array_slice($output, 0, $num)";
                        break;

                    case "to_indexed":
                        $output = "array_values($output)";
                        break;

                    case "first":
                        $output = "reset($output)";
                        break;

                    case "last":
                        $output = "end($output)";
                        break;

                    case "index":
                        $index = (isset($filter[1]) ? intval($filter[1]) : 0);
                        $output = "{$output}[$index]";
                        break;

                    case "explode":
                        $sep = " ";

                        if (!empty($filter[1])) {
                            $sep = $filter[1];
                        }

                        $output = "explode('" . addslashes($sep) . "', $output)";
                        break;

                    case "format_phone":
                        $output = "format_phone_number($output";

                        if (isset($filter[1]))
                            $output .= ", '" . addslashes($filter[1]) . "'";

                        $output .= ")";

                        break;

                    case "format_time_seconds":
                    case "format_seconds":
                        $output = "format_time_seconds($output)";
                        break;

                    case "money_format":
                    case "format_money":
                        if (isset($filter[1])) {
                            $output = "money_format('{$filter[1]}', floatval($output))";
                        } else {
                            $output = "money_format('{$this->currency_format}', floatval($output))";
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
    protected function process_shortcode($text, $data_type=null, $data_source=null, $wrap=true)
    {
        $offset = 0;
        while(($offset = strpos($text, "[[", $offset)) !== false) {

            if (($end = strpos($text, "]]", $offset)) === false)
                break;

            $filters = trim(substr($text, $offset + 2, $end - $offset - 2));

            $output = $this->process_filters($filters, $data_type, $data_source, null);

            if (empty($output))
                continue;

            if ($wrap)
                $output = "<?php @print $output ?>";

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
    protected function get_unique_varname()
    {
        return "\$v" . $this->_engine->unique_base . $this->_engine->unique_id++;
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
    protected function get_attribute_shortcode($node, $name, $default="", $data_type=null, $data_source=null, $wrap=true)
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
    protected function get_attribute_boolean($node, $name)
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
    protected function func_build_tab_url($params, $keep_uri, $no_referrer=false)
    {
        if (is_null($this->plugin)) {
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


        if (is_null($this->plugin)) {
            $output .= ") ?>";
        } else {
            $output .= ($keep_uri ? "true" : "false") . ", ";
            $output .= ($no_referrer ? "true" : "false") . ") ?>";
        }

        return $output;
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
    protected function throw_compile_exception($node, $message)
    {
        $file = $this->template_file;
        $line = $node->getLineNo();

        throw new Exception("Template compile error in '$file' at line $line<p>$message</p>");
    }
}
