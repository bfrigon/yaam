<?php
//******************************************************************************
// class_tag_processor_variable.php - <variable> tag processor
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


class TagProcessorVariable extends TagProcessorBase
{


    /*--------------------------------------------------------------------------
     * process_tag() : Process template tag "variable"
     *
     * Arguments
     * ---------
     *  - node_tag : The node to process.
     *  - handle   : File handle to the template output.
     *
     * Returns : None
     */
    public function process_tag($node_tag, $handle)
    {
        $node_empty = $node_tag->getElementsByTagName("if-empty")->item(0);

        $if_empty = $node_tag->getAttribute("if-empty");
        $format = $node_tag->getAttribute("format");
        $vars = explode(',', $node_tag->getAttribute("name"));

        if (!empty($format))
            $instr = "<?php printf('$format',";
        else
            $instr = "<?php echo ";

        foreach ($vars as $key => $var) {

            $var = trim($var);
            if (empty($var)) {
                unset($vars[$key]);
                continue;
            }

            $vars[$key] = "\$$var";
        }

        if (empty($vars))
            $this->throw_compile_exception($node_tag, "The tag 'variable' requires at least one variable name.");

        if (!empty($node_empty) || !empty($if_empty)) {
            fwrite($handle, "<?php if(empty(" . implode(") || empty(", $vars) . ")): ?>");

            if (!empty($node_empty))
                $this->process_node($handle, $node_empty, false, false);
            else
                fwrite($handle, $if_empty);

            fwrite($handle, "<?php else: ?>");
        }

        fwrite($handle, $instr . implode(',', $vars));

        if (!empty($format))
            fwrite($handle, ") ?>");
        else
            fwrite($handle, " ?>");

        if (!empty($node_empty) || !empty($if_empty))
            fwrite($handle, "<?php endif; ?>");
    }
}
