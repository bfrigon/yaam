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

            $child = $node->firstChild;
            do {
                if ($recursive) {

                    switch ($child->nodeType) {
                        case XML_ELEMENT_NODE:

                            switch ($child->nodeName) {

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
                                    $this->processors[$child->nodeName]->process_tag($child, $handle, $data_type, $data_source);
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

                                case "noparse":
                                    $html = $node->ownerDocument->saveXML($child);
                                    fwrite($handle, $html);
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
    protected function process_tokens($tokens, $data_type=null, $data_source=null, $unset_variable="''")
    {
        if (empty(trim($tokens)))
            return "";

        /* Split the tokens */
        $tokens =  preg_split("/[\s|]+/", $tokens);

        foreach($tokens as $token) {

            /* The first token is the variable name, following tokens contains modifier */
            if (empty($output)) {

                /* Single variable */
                if (substr($token, 0, 1) == "$") {

                    if (strpos($token, "@") !== false) {
                        $brackets = explode("@", $token);
                        $variable = $brackets[0];

                        foreach (array_slice($brackets,1) as $bracket) {
                            $variable .= (is_numeric($bracket) ? "[$bracket]" : "['$bracket']");
                        }

                        if (is_null($unset_variable))
                            $output = $varaible;
                        else
                            $output = "(isset($variable) ? $variable : $unset_variable)";

                    } else {

                        if (is_null($unset_variable))
                            $output = $token;
                        else
                            $output = "(isset($token) ? $token : $unset_variable)";
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

                                if (is_null($unset_varaible))
                                    $output = "\$$token";
                                else
                                    $output = "(isset(\$$token) ? \$$token : $unset_variable)";
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

                                        if (is_null($unset_varaible))
                                            $output = "\$$token";
                                        else
                                            $output = "(isset(\$$token) ? \$$token : $unset_variable)";

                                        break;
                                }
                            } else {

                                $output = $data_source[1] . "['$token']";
                            }
                            break;

                        /* Single variable */
                        default:

                            if (is_null($unset_variable))
                                $output = "\$$token";
                            else
                                $output = "(isset(\$$token) ? \$$token : $unset_variable)";

                            break;
                    }
                }
            } else {

                $params = explode(":", $token);

                switch ($params[0]) {
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
    protected function process_shortcode($text, $data_type=null, $data_source=null, $wrap=true)
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
}
