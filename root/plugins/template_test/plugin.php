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


/* --- Plugin permissions --- */
define("PERM_TEMPLATE_TEST", "template_test");


class PluginTemplateTest extends Plugin
{

    /* List of plugins incompatible with this one */
    public $conflicts = array();

    /* Other plugins required */
    public $dependencies = array();

    /* Files (css, javascript) to include in the html header */
    public $static_files = array();


    /*--------------------------------------------------------------------------
     * on_load() : Called after the plugin has been initialized.
     *
     * Arguments :
     * ---------
     *  None
     *
     * Return : None
     */
    function on_load(&$plugins)
    {
        $plugins->register_tab($this, null, "template", null, "Template test", PERM_TEMPLATE_TEST);
        $plugins->register_tab($this, "on_show_filters", "filters", "template", "Filters", PERM_TEMPLATE_TEST);
        $plugins->register_tab($this, "on_show_datagrid", "datagrid", "template", "Datagrid tag", PERM_TEMPLATE_TEST);
        $plugins->register_tab($this, "on_show_foreach", "foreach", "template", "Foreach tag", PERM_TEMPLATE_TEST);

    }


    /*--------------------------------------------------------------------------
     * on_show_datagrid() : Called when the tab content is requested.
     *
     * Arguments :
     * ---------
     *  - template : Instance of the template engine.
     *  - tab_path : Path to the current tab.
     *  - action   : Requested action.
     *
     * Return : None
     */
    function on_show_datagrid($template, $tab_path, $action)
    {
        $row = "Variable \$row";
        $var_test = "Other variable";
        $test_grid_array_multi = array(
            array("name" => "Louis Randell", "phone" => "2125551102"),
            "named" => array("name" => "Blake  Baker", "phone" => "2125556622", "data" => "string"),
            "empty" => array(),
            array("name" => "Norman Graves", "phone_missing" => "2125552233", "data" => array(
                "test" => "value1",
                "test2" => "value2",
            )),
            array("name" => "Edith  Willis", "phone" => "2125551122"),
            "null" => null,
        );

        $test_grid_array_single = array(
            "Norman Graves",
            "named" => "Louis Randell",
            "Edith Willis",
            "number" => 10.2,
            null,
        );

        $test_grid_array_null = null;

        require($template->load("datagrid.tpl"));
    }


    /*--------------------------------------------------------------------------
     * on_show_filters() : Called when the tab content is requested.
     *
     * Arguments :
     * ---------
     *  - template : Instance of the template engine.
     *  - tab_path : Path to the current tab.
     *  - action   : Requested action.
     *
     * Return : None
     */
    function on_show_filters($template, $tab_path, $action)
    {
        /* Test filters */
        $var_count_23 = 23;
        $var_count_1 = 1;
        $var_null = null;
        $var_test = "this is a test!";
        $var_phone = "4015551125";
        $var_integer_567 = 567;
        $var_float_23_11 = 23.11;

        $var_array_single = array("item1", "item2", "item3", "item4");
        $var_array_dict = array("item1" => "Value 1", "item2" => "Value 2", "item3" => "Value 3");
        $var_array_multi = array(
            "item1" => array(
                "subitem1" => "item1.subitem1",
                "subitem2" => "item1.subitem2",
            ),
            "item2" => array(
                "subitem1" => "item2.subitem1"
            ),
        );

        require($template->load("filters.tpl"));
    }


    /*--------------------------------------------------------------------------
     * on_show_foreach() : Called when the tab content is requested.
     *
     * Arguments :
     * ---------
     *  - template : Instance of the template engine.
     *  - tab_path : Path to the current tab.
     *  - action   : Requested action.
     *
     * Return : None
     */
    function on_show_foreach($template, $tab_path, $action)
    {
        $var_array_single = array("item1", "item2", "item3", "item4");
        $var_array_dict = array("item1" => "Value 1", "item2" => "Value 2", "item3" => "Value 3");
        $var_array_multi = array(
            "item1" => array(
                "subitem1" => "item1.subitem1",
                "subitem2" => "item1.subitem2",
            ),
            "item2" => array(
                "subitem1" => "item2.subitem1"
            ),
        );


        require($template->load("foreach.tpl"));
    }
}
