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


class TagProcessorDatagrid extends TagProcessorBase
{

    /*--------------------------------------------------------------------------
     * process_tag() : Process the template tag "datagrid"
     *
     * Arguments
     * ---------
     *  - node_tag    : Node to process.
     *  - handle      : File handle to the template output.
     *
     * Returns : None
     */
    public function process_tag($node_tag, $handle)
    {
        /* Get child nodes */
        $node_row = $node_tag->getElementsByTagName("row")->item(0);
        $node_header = $node_tag->getElementsByTagName("header")->item(0);
        $node_empty = $node_tag->getElementsByTagName("if-empty")->item(0);
        $node_caption = $node_tag->getElementsByTagName("caption")->item(0);
        $node_footer = $node_tag->getElementsByTagName("footer")->item(0);

        if ($node_tag->hasAttribute("if"))
            $this->processors["if"]->process_tag($node_tag, $handle, null, null);

        /* Required child node */
        if (empty($node_row))
            $this->throw_compile_exception($node_tag, "The tag 'grid' requires a row element.");

        /* Get tag attributes */
        $data_source = $node_tag->getAttribute("data-source");
        $data_type = strtolower($node_tag->getAttribute("data-type"));
        $min_rows = $node_tag->getAttribute("min-rows");
        $class = $node_tag->getAttribute("class");

        /* Required attributes */
        if (empty($data_source))
            $this->throw_compile_exception($node_tag, "The tag 'grid' requires a 'data-source' attribute.");

        if (empty($data_type))
            $this->throw_compile_exception($node_tag, "The tag 'grid' requires a 'data-type' attribute.");

        if (substr($data_source, 0, 1) != "$")
            $data_source = "\$$data_source";

        fwrite($handle, "<table id=\"\" class=\"grid $class\">\n");

        /* Generate caption tag */
        if (!empty($node_caption)) {
            fwrite($handle, "<caption>");
            fwrite($handle, $this->process_shortcode($node_caption->textContent, $data_type, $data_source));
            fwrite($handle, "</caption>\n");
        }

        $var_row_count = $this->get_unique_varname();
        $var_hidden_col = $this->get_unique_varname();

        fwrite($handle, "<?php $var_row_count=0; ?>");


        /* Generate grid header if present */
        if (!empty($node_header)) {
            fwrite($handle, "<thead><tr>");

            $this->process_tag_grid_row($node_header, $handle, $data_type, $data_source, $var_hidden_col);

            fwrite($handle, "</tr></thead>\n");
        }

        fwrite($handle, "<tbody>");


        /* Insert rows iteration code */
        switch ($data_type) {
            case "odbc":
                fwrite($handle, "<?php while (@odbc_fetch_row($data_source)): ?>\n");
                break;

            case "dict":
                $var_array_row = $this->get_unique_varname();
                fwrite($handle, "<?php foreach($data_source as $var_array_row): ?>\n");
                break;

            /* invalid data type */
            default:
                $this->throw_compile_exception($node_tag, "The tag 'grid' has an invalid data-type : $data_type");
                break;
        }


        /* Insert grid rows */
        fwrite($handle, "<tr class=\"<?php echo !($var_row_count & 1) ? '':'alt' ?>\">");

        switch ($data_type) {
            case "odbc":
                $num_columns = $this->process_tag_grid_row($node_row, $handle, $data_type, $data_source, $var_hidden_col);
                break;

            case "dict":
                $num_columns = $this->process_tag_grid_row($node_row, $handle, $data_type, array($data_source, $var_array_row), $var_hidden_col);
                break;
        }

        fwrite($handle, "</tr>");


        /* Close rows iteration code */
        switch ($data_type) {
            case "odbc":
                fwrite($handle, "<?php $var_row_count++; endwhile; ?>");
                break;

            case "dict":
                fwrite($handle, "<?php $var_row_count++; endforeach; ?>");
                break;
        }


        /* Insert if-empty row */
        if (!empty($node_empty)) {
            fwrite($handle, "<?php if ($var_row_count==0):?>");
            fwrite($handle, "<tr class=\"<?php echo !($var_row_count & 1) ? '':'alt' ?>\">");
            fwrite($handle, "<td colspan=\"<?php echo ($num_columns-$var_hidden_col) ?>  \">");

            $this->process_node($handle, $node_empty, false, false);

            fwrite($handle, "</td>\n</tr>\n<?php $var_row_count++; endif; ?>\n");
        }


        /* Insert row filler code */
        if ($min_rows) {
            fwrite($handle, "<?php while($var_row_count < $min_rows): ?>");
            fwrite($handle, "<tr class=\"<?php echo !($var_row_count & 1) ? '':'alt' ?>\">");
            fwrite($handle, "<?php echo str_repeat('<td>&nbsp;</td>', ($num_columns-$var_hidden_col)) ?>");
            fwrite($handle, "</tr><?php $var_row_count++; endwhile; ?>");
        }

        fwrite($handle, "</tbody>");


        /* Generate grid footer if present */
        if (!empty($node_footer)) {
            fwrite($handle, "<tfoot><tr>\n");

            $this->process_tag_grid_row($node_footer, $handle, $data_type, $data_source);

            fwrite($handle, "</tr></tfoot>\n");
        }

        fwrite($handle, "</table>\n");

        if ($node_tag->hasAttribute("if"))
            fwrite($handle, "<?php endif; ?>");
    }


    /*--------------------------------------------------------------------------
     * process_tag_grid_row() : Process rows of a "datagrid" tag.
     *
     * Arguments
     * ---------
     *  - node_row       : Node to process.
     *  - handle         : File handle to the template output.
     *  - data_type      : Type of the current data source (odbc, dict).
     *  - data_source    : Current data source object.
     *  - var_hidden_col : Variable name for the total of hidden columns.
     *
     * Returns : None
     */
    private function process_tag_grid_row($node_row, $handle, $data_type, $data_source, $var_hidden_col=null)
    {
        $cell_type = ($node_row->nodeName == "row") ? "td" : "th";

        if (!(is_null($var_hidden_col)))
            fwrite($handle, "<?php $var_hidden_col=0; ?>");

        $columns = $node_row->childNodes;
        foreach ($columns as $node_column) {

            $style = $node_column->getAttribute("style");
            $type = $node_column->getAttribute("type");

            if ($node_column->hasAttribute("if"))
                $this->processors["if"]->process_tag($node_column, $handle, $data_type, $data_source);


            fwrite($handle, "<$cell_type style=\"$style\"");

            if (!empty($type))
                fwrite($handle, " class=\"column-$type\"");

            fwrite($handle, ">");

            $this->process_node($handle, $node_column, false, true, $data_type, $data_source);

            fwrite($handle, "</$cell_type>\n");


            if ($node_column->hasAttribute("if")) {

                if (is_null($var_hidden_col))
                    fwrite($handle, "<?php endif; ?>");
                else
                    fwrite($handle, "<?php else: $var_hidden_col++; endif; ?>");
            }
        }

        return $columns->length;
    }
}
