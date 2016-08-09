<?php
//******************************************************************************
// class.plugin.php - Base Plugin class
//
// Project   : Asterisk Y.A.A.M (Yet another asterisk manager)
// Author    : Benoit Frigon <benoit@frigon.info>
//
// Copyright (c) Benoit Frigon
// www.bfrigon.com
//
// This software is released under the terms of the GNU Lesser General Public
// License v2.1.
// A copy of which is available from http://www.gnu.org/copyleft/lesser.html
//
//******************************************************************************
class Plugin
{
    public $_dependencies = array();
    public $_tabs = array();
    public $_tab_url = array();

    public $REQUEST_JS_ENABLED = false;
    public $PLUGIN_DIR = '';
    public $NAME = '';


    /*--------------------------------------------------------------------------
     * Plugin() : initialize class instance
     *
     * Arguments
     * ---------
     *  - name : plugin name
     *  - tabs : Global tabs collection
     *
     * Returns : Nothing
     */
    function Plugin($name, &$tabs)
    {
        $this->_tabs = &$tabs;

        $this->NAME = $name;
        $this->PLUGIN_DIR = DOCUMENT_ROOT . "/plugins/$name";

        $tab_url = $_GET;
        unset($tab_url["js"], $tab_url["output"]);

        $this->_tab_url = $tab_url;
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
    function on_load()
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
        if (!$this->REQUEST_JS_ENABLED) {
            header("location: $url");

        } else {
            echo "<script>window.location=convert_tab_url('$url');</script>";
        }

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
        $url = $this->_tab_url;

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
        if (isset($_GET["referrer"])) {
            return $_GET["referrer"];

        } else {

            if (!(isset($_GET["path"])))
                return "";

            $uri = array("path" => $_GET["path"]);

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
            $uri = $_GET;
            unset($uri["js"], $uri["output"]);

            $params = array_merge($uri, $params);
        }

        if ($no_referrer)
            unset($params["referrer"]);

        if (!(isset($params["path"])))
            $params["path"] = $_GET["path"];

        return "?" . http_build_query($params);
    }


    /*--------------------------------------------------------------------------
     * include_js_script() : Include a jacscript file to the tab content output.
     *
     * Arguments
     * ---------
     *  - $name : script filename
     *
     * Returns : Nothing
     */
    function include_js_script($name)
    {
        echo "<script>";
        readfile($this->PLUGIN_DIR . "/$name");
        echo "</script>";
    }


    /*--------------------------------------------------------------------------
     * register_tab() : Create a new tab
     *
     * Arguments
     * ---------
     *  - callback    : Function to call when displaying the tab content
     *  - id          : New tab id
     *  - parent      : Parent tab
     *  - caption     : Tab caption
     *  - permissions : Required permission to open the tab
     *  - order       : Tab priority (lower first)
     *
     * Returns : Nothing
     */
    function register_tab($callback, $id, $parent, $caption, $req_perms, $order = 100)
    {
        if (!check_permissions($req_perms))
            return;

        if ($parent != NULL) {
            if (!isset($this->_tabs[$parent]))
                throw new Exception("Parent tab does not exist");

            $tab = &$this->_tabs[$parent];

            if (!isset($tab["childs"]))
                $tab["childs"] = array();

            $tab = &$tab["childs"][$id];

        } else {
            $tab = &$this->_tabs[$id];
        }

        $tab["id"] = $id;
        $tab["callback"] = $callback;
        $tab["plugin"] = $this->NAME;
        $tab["perm"] = $permissions;
        $tab["caption"] = $caption;
        $tab["order"] = $order;
    }
}
