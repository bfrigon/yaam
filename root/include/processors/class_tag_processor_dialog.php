<?php
//******************************************************************************
//
// Project : Asterisk Y.A.A.M (Yet another asterisk manager)
// Author  : Benoit Frigon <www.bfrigon.com>
//
// Contributors
// ============
//
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


class TagProcessorDialog extends TagProcessorBase
{

    /*--------------------------------------------------------------------------
     * process_tag() : Process the template tag "dialog".
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
    public function process_tag($node_tag, $handle, $data_type=null, $data_source=null)
    {
        $type = $node_tag->getAttribute("type");
        $class = $node_tag->getAttribute("class");

        switch ($type) {
            case "error":
            case "warning":
                $class .= " $type";
                break;
        }

        fwrite($handle, "<div class=\"box dialog $class\"><div class=\"content\">");

        $this->process_tag_dialog_childs($node_tag, $handle, $data_type, $data_source);

        fwrite($handle, "</div></div>");
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
    private function process_tag_dialog_childs($node_tag, $handle, $data_type=null, $data_source=null)
    {

        foreach ($node_tag->childNodes as $node_child) {


            switch ($node_child->nodeName) {
                case "if":
                    $this->processors["if"]->process_tag($node_child, $handle, $data_type, $data_source, false);

                    $this->process_tag_dialog_childs($node_child, $handle, $data_type, $data_source);

                    fwrite($handle, "<?php endif; ?>\n");
                    break;

                case "field":
                    $this->process_tag_field($node_child, $handle, $data_type, $data_source);
                    break;

                case "toolbar":
                    $this->processors["toolbar"]->process_tag($node_child, $handle, $data_type, $data_source);
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
            $this->processors["if"]->process_tag($node_tag, $handle, $data_type, $data_source);

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

                        fwrite($handle, "<?php foreach( (isset($data_source) ? $data_source : array()) as $var_key => $var_value): ?>\n");

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
                        fwrite($handle, "<?php $var_index=0; foreach( (isset($data_source) ? $data_source : array()) as $var_value): ?>\n");

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
                        $var_value = $this->process_tokens($node_tag->getAttribute("value"), $data_type, $data_source, "array()");
                        $var_checked = $this->get_unique_varname();
                        $var_item = $this->get_unique_varname();

                        fwrite($handle, "<?php foreach( (isset($data_source) ? $data_source : array()) as $var_item):");
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
}
