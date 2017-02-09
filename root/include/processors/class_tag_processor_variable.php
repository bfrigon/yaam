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
