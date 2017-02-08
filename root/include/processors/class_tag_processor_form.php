<?php
//******************************************************************************
// class_tag_processor_form.php - <form> tag processor
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


class TagProcessorForm extends TagProcessorBase
{


    /*--------------------------------------------------------------------------
     * process_tag() : Process the template tag "form".
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
        if (!(is_null($this->plugin))) {
            $element = $node_tag->ownerDocument->createElement("input");
            $element->setAttribute("type", "hidden");
            $element->setAttribute("name", "path");
            $element->setAttribute("value", "<?php echo \$_GET[\"path\"] ?>");

            $node_tag->appendChild($element);

            $element = $node_tag->ownerDocument->createElement("input");
            $element->setAttribute("type", "hidden");
            $element->setAttribute("name", "referrer");
            $element->setAttribute("value", "<?php echo \$this->get_tab_url(true) ?>");

            $node_tag->appendChild($element);
        }

        $this->process_node($handle, $node_tag, true, true, $data_type, $data_source);
    }
}
