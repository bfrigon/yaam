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


class Plugin
{
    private $_template = null;


    public $manager = null;
    public $name = '';
    public $dir = '';
    public $tab_url = '';

    /* List of plugins incompatible with this one */
    public $conflicts = array();

    /* Other plugins required */
    public $dependencies = array();

    /* Files (css, javascript) to include in the html header */
    public $static_files = array();


    /*--------------------------------------------------------------------------
     * Plugin() : initialize class instance
     *
     * Arguments
     * ---------
     *  - name    : Plugin name
     *  - manager : Instance of the plugin manager.
     *
     * Returns : Nothing
     */
    function Plugin($name, &$manager)
    {
        $this->manager = &$manager;

        $this->name = $name;
        $this->dir = DOCUMENT_ROOT . "/plugins/$name";

        $tab_url = $_GET;
        unset($tab_url["output"]);

        $this->tab_url = $tab_url;
    }


    /*--------------------------------------------------------------------------
     * on_load() : Called after the plugin manager initialize the plugin.
     *
     * Arguments
     * ---------
     * None
     *
     * Returns : Nothing
     */
    function on_load(&$manager)
    {

    }


    /*--------------------------------------------------------------------------
     * redirect() : Redirect the output to another tab or url.
     *
     * Arguments
     * ---------
     *  - url : url to redirect to.
     *
     * Returns : Nothing
     */
    function redirect($url)
    {
        header("location: $url");
        exit();
    }


    /*--------------------------------------------------------------------------
     * get_tab_url() : Get the full url of the current tab.
     *
     * Arguments
     * ---------
     *  - exclude_referrer : Remove the "referrer" parameter from the url.
     *
     * Returns : The current tab url.
     */
    function get_tab_url($exclude_referrer=true)
    {
        $url = $this->tab_url;

        if ($exclude_referrer)
            unset($url["referrer"]);

        return ("?" . http_build_query($url));
    }


    /*--------------------------------------------------------------------------
     * get_tab_referrer() : Get the referrer url of the current tab.
     *
     * Arguments
     * ---------
     *  None
     *
     * Returns : The referrer url.
     */
    function get_tab_referrer()
    {
        if (isset($_REQUEST["referrer"])) {
            return $_REQUEST["referrer"];

        } else {

            if (!(isset($_REQUEST["path"])))
                return "";

            $uri = array("path" => $_REQUEST["path"]);

            return ("?" . http_build_query($uri));
        }
    }


    /*--------------------------------------------------------------------------
     * build_tab_url() : Assemble a url based on the current parameters in $_GET
     *                   and the given ones.
     *
     * Arguments
     * ---------
     *  - params      : Parameters to include in the url.
     *  - keep_uri    : If false, only the parameters in $params will be used.
     *  - no_referrer : If true, removes the "referrer" parameter from the url.
     *
     * Returns : The assembled url.
     */
    function build_tab_url($params, $keep_uri=true, $no_referrer=false)
    {
        if ($keep_uri) {
            $uri = $_REQUEST;
            unset($uri["output"]);

            $params = array_merge($uri, $params);
        }

        if ($no_referrer)
            unset($params["referrer"]);

        if (!(isset($params["path"])))
            $params["path"] = $_GET["path"];

        return "?" . http_build_query($params);
    }


    /*--------------------------------------------------------------------------
     * get_template_engine() : Return the template engine instance for this plugin
     *                         or create one if not done already.
     *
     * Arguments
     * ---------
     *  None
     *
     * Returns : The template engine instance.
     */
    function get_template_engine()
    {
        if (is_null($this->_template))
            $this->_template = new TemplateEngine($this);

        return $this->_template;
    }


    /*--------------------------------------------------------------------------
     * show_messagebox() : Load and display the template for a message box
     *
     * Arguments
     * ---------
     *  - type        : Message box type
     *  - message     : Message to display.
     *  - has_buttons : Show dialog buttons.
     *  - url_ok      : The url to redirect to when the OK button is pressed.
     *
     * Returns : None
     */
    function show_messagebox($msg_type, $message, $has_buttons=true, $url_ok=null)
    {
        $message = preg_replace("/\n/", "<br />", $message);

        if ($has_buttons) {

            if (is_null($url_ok))
                $url_ok = (empty($url_ok) ? $this->get_tab_referrer() : $url_ok);
        }

        require($this->_template->load("messagebox.tpl", true));
    }
}
