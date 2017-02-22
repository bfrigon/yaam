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


class TagProcessorToolbar extends TagProcessorBase
{

    /*--------------------------------------------------------------------------
     * process_tag() : Process the template tag "toolbar"
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
    public function process_tag($node_tag, $handle, $data_type=null, $data_source=null)
    {
        if ($node_tag->hasAttribute("if"))
            $this->processors["if"]->process_tag($node_tag, $handle, $data_type, $data_source);

        $id = $node_tag->getAttribute("id");

        $class = $node_tag->getAttribute("class");
        if (empty($class))
            $class = "box";


        fwrite($handle, "<div class=\"toolbar $class\" id=\"$id\"><div class=\"content\">");

        $this->process_toolbar_tag_childs($node_tag, $handle, $data_type, $data_source);

        fwrite($handle, "</div></div>");

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

            /* Only process nodes */
            if ($node_item->nodeType != XML_ELEMENT_NODE)
                continue;

            switch ($node_item->nodeName) {

                /* Allow if tags */
                case "if":
                    $this->processors["if"]->process_tag($node_item, $handle, $data_type, $data_source, false);

                    $this->process_toolbar_tag_childs($node_item, $handle, $data_type, $data_source);

                    fwrite($handle, "<?php endif; ?>");
                    break;

                /* Allow action-list tags */
                case "action-list":
                case "action":
                    $this->processors["action-list"]->process_tag($node_item, $handle, $data_type, $data_source);
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
        $id = $node_item->getAttribute("id");
        $item_type = $node_item->getAttribute("type");

        switch ($item_type) {

            // -----------------------------------------------
            //  Buttons
            // -----------------------------------------------
            case "button":
                $li_class = ($this->get_attribute_boolean($node_item, "disabled")) ? "disabled" : "";

                fwrite($handle, "<div class=\"item $li_class\">");

                $this->processors["icon"]->process_tag($node_item, $handle, $data_type, $data_source);

                fwrite($handle, "</div>\r\n");
                break;

            // -----------------------------------------------
            //  Submit button
            // -----------------------------------------------
            case "submit":
                $icon = $node_item->getAttribute("icon");
                $name = $node_item->getAttribute("name");
                $value = $node_item->getAttribute("value");
                $action = $node_item->getAttribute("action");
                $title = $this->get_attribute_shortcode($node_item, "title", "", $data_type, $data_source);
                $caption = $this->process_shortcode($node_item->textContent, $data_type, $data_source);

                $btn_class = (!empty($icon) && empty($caption)) ? "icon-only" : "";
                $li_class = ($this->get_attribute_boolean($node_item, "disabled")) ? "disabled" : "";


                fwrite($handle, "<div class=\"item $li_class\">\n");
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
                fwrite($handle, "</button></div>\n");

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

                fwrite($handle, "<div class=\"item\"><input type=\"text\" name=\"$name\"");

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

                fwrite($handle, " /></div>\n");
                break;

            // -----------------------------------------------
            //  separator
            // -----------------------------------------------
            case "separator":
                fwrite($handle, "<div class=\"item separator\"></div>");
                break;

            // -----------------------------------------------
            //  label
            // -----------------------------------------------
            case "label":
            case "view":
                fwrite($handle, "<div class=\"item $item_type\"");

                if (!empty($id))
                    fwrite($handle, " id=\"$id\"");

                fwrite($handle, ">");

                $this->process_node($handle, $node_item, false, true);

                fwrite($handle, "</div>");
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

                fwrite($handle, "<div class=\"item dropdown\">\n");
                fwrite($handle, "<a tabindex=\"1\" href=\"#\">$prefix<?php echo (isset(\$current_page) ? \$current_page : 1); ?>$suffix</a>\n");
                fwrite($handle, "<img class=\"close-dropdown\" src=\"images/blank.png\" alt=\"\" />\n");

                fwrite($handle, "<div class=\"dropdown-list\">\n");
                fwrite($handle, "<?php for($var_counter = max(1, (isset(\$current_page) ? \$current_page : 1) - $range);");
                fwrite($handle, "$var_counter <= min((isset(\$current_page) ? \$current_page : 1) + $range, (isset(\$total_pages) ? \$total_pages : 1)); $var_counter++): ?>\n");

                fwrite($handle, "<div class=\"item\"><a tabindex=\"1\" href=\"$href\" ?>$prefix<?php echo $var_counter; ?>$suffix</a></div>\n");
                fwrite($handle, "<?php endfor; ?>\n");
                fwrite($handle, "</div></div>\n");
                break;

            // -----------------------------------------------
            //  Dropdown list
            // -----------------------------------------------
            case "list":
                $node_caption = $node_item->getElementsByTagName("caption")->item(0);
                $icon = $node_item->getAttribute("icon");
                $caption =!empty($node_caption) ? $node_caption->textContent : "";

                $a_class = (!empty($icon) && empty($caption)) ? "icon-only" : "";


                fwrite($handle, "<div class=\"item dropdown\" ");

                if (!empty($id))
                    fwrite($handle, " id=\"$id\"");

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

                fwrite($handle, "<div class=\"dropdown-list\">\n");


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

                            fwrite($handle, "<?php foreach( (isset($data_source) ? $data_source : array()) as $var_key => $var_value): ?>\n");

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
                            fwrite($handle, "<?php foreach( (isset($data_source) ? $data_source : array()) as $var_value): ?>\n");

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

                fwrite($handle, "</div></div>\n");
                break;
        }

        fwrite($handle, "\n");
    }
}
