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


class TagProcessorIf extends TagProcessorBase
{

    /*--------------------------------------------------------------------------
     * process_tag() : Process the template tag "if".
     *
     * Arguments
     * ---------
     *  - node_tag    : Node to process.
     *  - handle      : File handle to the template output.
     *  - data_type   : Type of the Current data source (odbc, array).
     *  - data_source : Current data source object.
     *  - close_tag   : If true, processes the child nodes and closes the if
     *                  statement, otherwize, it leave it open.
     *
     * Returns : None
     */
    public function process_tag($node_tag, $handle, $data_type=null, $data_source=null, $close_tag=true)
    {

        /* stand-alone if tag */
        if ($node_tag->nodeName == "if") {
            $type = strtolower($node_tag->getAttribute("type"));
            $name = $node_tag->getAttribute("name");
            $value = $node_tag->getAttribute("value");

        /* if contained in another tag */
        } else {
            $type = strtolower($node_tag->getAttribute("if"));
            $name = $node_tag->getAttribute("if-name");
            $value = $node_tag->getAttribute("if-value");

        }

        /* Check required attribute */
        if (empty($type))
            $this->throw_compile_exception($node_tag, "The tag 'if' requires a 'type' attribute.");

        switch ($type) {
            case "function":
            case "true":
            case "false":
            case "isset":
            case "empty":
            case "is_null":
            case "equal":
                if (empty($name) && $node_tag->nodeName == "if") {
                    $this->throw_compile_exception($node_tag, "The 'if' tag requires an 'if-name' attribute for '$type' type");
                } else if (empty($name)) {
                    $this->throw_compile_exception($node_tag, "The if=$type attribute requires an 'if-name' attribute");
                }
                break;

            case "permission":
            case "perm":
            case "function":
                if (empty($value) && $node_tag->nodeName == "if") {
                    $this->throw_compile_exception($node_tag, "The 'if' tag requires an 'if-value' attribute for '$type' type");
                } else if (empty($value)) {
                    $this->throw_compile_exception($node_tag, "The if=$type attribute requires an 'if-value' attribute");
                }
                break;
        }

        switch ($type) {

            /* Return value of a function */
            case "function":
                $params = $this->process_shortcode($value, $data_type, $data_source, false);
                $op = ($node_tag->hasAttribute("not") ? "!" : "");

                fwrite($handle, "<?php if({$op}\$this->$name($params)): ?>\n");
                break;


            /* Permission check */
            case "permission":
            case "perm":
                $op = ($node_tag->hasAttribute("not") ? "!" : "");

                fwrite($handle, "<?php if ({$op}check_permission(\"$value\")): ?>\n");
                break;

            /* Check if variable Boolean */
            case "true":
            case "false":
                $unset_value = ($type == "true" ? "false" : "true");
                $op = ($node_tag->hasAttribute("not") ? "!== $type" : "=== $type");
                $name = $this->process_filters($name, $data_type, $data_source, $unset_value);

                fwrite($handle, "<?php if ($name $op): ?>\n");
                break;

            /* Check if variable exists */
            case "isset":
            case "empty":
            case "is_null":
                $op = ($node_tag->hasAttribute("not") ? "!" : "");
                $name = $this->_engine->process_filters($name, $data_type, $data_source, null);

                fwrite($handle, "<?php if ({$op}$type($name)): ?>\n");
                break;

            /* Compare */
            case "equal":
            default:
                $op = ($node_tag->hasAttribute("not") ? "!=" : "==");
                $name = $this->process_filters($name, $data_type, $data_source);

                if (!(is_numeric($value)))
                    $value = "\"$value\"";

                fwrite($handle, "<?php if ($name $op $value): ?>\n");
                break;
        }

        if ($node_tag->nodeName == "if" && $close_tag) {
            $this->process_node($handle, $node_tag, false, true, $data_type, $data_source);

            fwrite($handle, "<?php endif; ?>");
        }
    }
}
