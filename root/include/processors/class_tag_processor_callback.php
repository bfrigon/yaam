<?php
//******************************************************************************
// class_tag_processor_callback.php - <callback> tag processor
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


class TagProcessorCallback extends TagProcessorBase
{

    /*--------------------------------------------------------------------------
     * process_tag : Process template "callback" tag. It insert the code to the
     *               output required to call a function inside the plugin
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
    public function process_tag($node_tag, $handle, $data_type=null, $data_source=null)
    {
        $name   = $node_tag->getAttribute("name");
        $return = $node_tag->getAttribute("return");
        $params = $this->get_attribute_shortcode($node_tag, "params", "", $data_type, $data_source, false);

        /* Required attribute */
        if (empty($name))
            $this->throw_compile_exception($node_tag, "The tag 'callback' requires a 'name' attribute.");


        if ($node_tag->hasAttribute("if"))
            $this->processors["if"]->process_tag($node_tag, $handle, $data_type, $data_source);

        fwrite($handle, "\r\n<?php ");

        if (!empty($return)) {
            $return = preg_split("/[\s,]+/", $return);

            if (count($return) > 1)
                $return = "@list(\$" . implode(", \$", $return) . ")";
            else
                $return = "\$$return[0]";

            fwrite($handle, "$return = ");
        }

        if (is_null($this->_engine->plugin)) {
            fwrite($handle, "$name($params); ?>");
        } else {
            fwrite($handle, "\$this->$name($params); ?>");
        }

        if ($node_tag->hasAttribute("if"))
            fwrite($handle, "<?php endif; ?>");
    }
}

