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

