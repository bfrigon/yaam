<?php
//******************************************************************************
// class_plugin.php - Base Plugin class
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

if(realpath(__FILE__) == realpath($_SERVER["SCRIPT_FILENAME"])) {
    header("Location:../index.php");
    exit();
}

class Plugin
{
    public $manager = null;
    public $name = '';
    public $dir = '';
    public $dependencies = array();
    public $tab_url = '';


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
}
