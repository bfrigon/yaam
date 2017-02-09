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


class TagProcessorForeach extends TagProcessorBase
{

    /*--------------------------------------------------------------------------
     * process_tag() : Process the template tag "foreach".
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
        /* Get tag attributes */
        $data_source = $this->process_tokens($node_tag->getAttribute("data-source"), $data_type, $data_source);
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
            $this->processors["if"]->process_tag($node_tag, $handle, $data_type, $data_source);


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

                fwrite($handle, "<?php foreach( (isset($data_source) ? $data_source : array()) as $var_key => $var_value): ?>\n$prefix_html\n");

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
                fwrite($handle, "<?php foreach( (isset($data_source) ? $data_source : array()) as $var_value): ?>\n$prefix_html\n");

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
}
