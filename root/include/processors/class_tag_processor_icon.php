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


class TagProcessorIcon extends TagProcessorBase
{

    /*--------------------------------------------------------------------------
     * process_tag() : Process the template tag "icon".
     *
     * Arguments
     * ---------
     *  - node_tag    : Node to process.
     *  - handle      : File handle to the template output.
     *  - data_type   : Type of the Current data source (odbc, array).
     *  - data_source : Current data source object.
     *
     * Returns : None
     */
    public function process_tag($node_tag, $handle, $data_type=null, $data_source=null)
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
            $this->processors["if"]->process_tag($node_tag, $handle, $data_type, $data_source);


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
                        if (is_null($this->plugin))
                            $url_params["referrer"] = "( isset(\$_REQUEST['referrer']) ? \$_REQUEST['referrer'] : '')";
                        else
                            $href = "<?php echo \$this->get_tab_referrer() ?>";

                        $btn_class .= " action-cancel ";
                        break;

                    case "first-page":
                        $url_params["page"] = "1";
                        $href = $this->func_build_tab_url($url_params, true);

                        $btn_class .= " <?php echo (!isset(\$current_page) || \$current_page <= 1) ? 'disabled' : '' ?>";
                        break;

                    case "prev-page":
                        $url_params["page"] = "max(\$current_page - 1, 1)";
                        $href = $this->func_build_tab_url($url_params, true);

                        $btn_class .= " <?php echo (!isset(\$current_page) || \$current_page <= 1) ? 'disabled' : '' ?>";
                        break;

                    case "next-page":
                        $url_params["page"] = "((isset(\$current_page) && isset(\$total_pages)) ? min(\$current_page + 1, \$total_pages) : 1)";
                        $href = $this->func_build_tab_url($url_params, true);

                        $btn_class .= " <?php echo ((!isset(\$current_page) && !isset(\$total_pages)) || \$current_page >= \$total_pages) ? 'disabled' : '' ?>";
                        break;

                    case "last-page":
                        $url_params["page"] = "(isset(\$total_pages) ? \$total_pages : 1)";
                        $href = $this->func_build_tab_url($url_params, true);

                        $btn_class .= " <?php echo ((!isset(\$current_page) && !isset(\$total_pages)) || \$current_page >= \$total_pages) ? 'disabled' : '' ?>";
                        break;

                    default:
                        $url_params["action"] = "'$action'";

                        if (is_null($this->plugin)) {

                            $url_params["referrer"] = "(isset(\$_REQUEST['referrer']) ? \$_REQUEST['referrer'] : '')";
                        } else if ($keep_referrer) {

                            $url_params["referrer"] = "\$this->get_tab_referrer()";
                        } else {

                            $url_params["referrer"] = "\$this->get_tab_url(true)";
                        }

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
}
