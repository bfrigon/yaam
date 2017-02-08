<?php
//******************************************************************************
// class_tag_processor_if.php - <if> tag processor
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


class TagProcessorIf extends TagProcessorBase
{


    /*--------------------------------------------------------------------------
     * process_tag() : Process the template tag "if".
     *
     * Arguments
     * ---------
     *  - node_tag    : Node to process.
     *  - handle      : File handle to the template output.
     *  - data_type   : Type of the Current data source (odbc, dict).
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
            case "boolean":
            case "bool":
            case "isset":
            case "empty":
            case "is_null":
            case "string":
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
            case "boolean":
            case "bool":
                $op = ($node_tag->hasAttribute("not") ? "false" : "true");
                $name = $this->process_tokens($name, $data_type, $data_source);

                fwrite($handle, "<?php if ($name === $op): ?>\n");
                break;

            /* Check if variable exists */
            case "isset":
            case "empty":
            case "is_null":
                $op = ($node_tag->hasAttribute("not") ? "!" : "");
                $name = $this->_engine->process_tokens($name, $data_type, $data_source);

                fwrite($handle, "<?php if ({$op}$type($name)): ?>\n");
                break;

            /* Compare string */
            case "string":
            default:
                $op = ($node_tag->hasAttribute("not") ? "!=" : "==");
                $name = $this->process_tokens($name, $data_type, $data_source);

                fwrite($handle, "<?php if ($name $op \"$value\"): ?>\n");
                break;
        }

        if ($node_tag->nodeName == "if" && $close_tag) {
            $this->process_node($handle, $node_tag, false, true, $data_type, $data_source);

            fwrite($handle, "<?php endif; ?>");
        }
    }
}
