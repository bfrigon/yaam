<?php
//******************************************************************************
// class_tag_processor_action_list.php - <action-list> tag processor
//
// Project : Asterisk Y.A.A.M (Yet another asterisk manager)
// Author  : Benoit Frigon <benoit@frigon.info>
//
// Contributors
// ------------
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


class TagProcessorActionList extends TagProcessorBase
{


    /*--------------------------------------------------------------------------
     * process_tag() : Process the template tag "action-list".
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
            $this->processors["if"]->process_tag($node_tag, $handle, $data_type, $data_source);

        /* Build items href */
        $url_params = array();
        $url_params["path"] = "{$var_action}[\"path\"]";
        $url_params["action"] = "{$var_action}[\"name\"]";

        if (!(is_null($this->plugin)))
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
}
