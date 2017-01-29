<?php
//******************************************************************************
// class_plugin_manager.php - plugin manager (loader)
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
class PluginManager
{
    public $_plugins = array();
    public $_tabs = array();
    public $_actions = array();
    public $_perm_table = array();


    /*--------------------------------------------------------------------------
     * load() : Load a plugin
     *
     * Arguments
     * ---------
     *  - name : plugin name. If null, loads all available plugins.
     *
     * Returns : last loaded plugin instance.
     */
    function load($name=null)
    {
        global $CONFIG;

        /* If no plugin name is specified, load all plugins defined in the config file */
        if (is_null($name))
            $plugin_list = $CONFIG["general"]["plugin"];
        else
            $plugin_list = array($name => true);


        $plugin = null;
        foreach ($plugin_list as $plugin_name => $enabled) {
            $plugin_name = strtolower($plugin_name);

            /* Ignore plugin if disabled in config */
            if ($enabled == false)
                continue;

            /* Check if that plugin is already loaded */
            if ($this->plugin_loaded($plugin_name)) {
                $plugin = $this->_plugins[$plugin_name];
                continue;
            }

            /* Load the plugin file */
            $plugin_dir = DOCUMENT_ROOT . "/plugins/$plugin_name";
            $plugin_def = $plugin_dir . "/plugin.php";


            if (!$this->plugin_exists($plugin_name))
                throw new Exception("Plugin '$plugin_name' does not exist.");

            require($plugin_def);

            /* Find the plugin class name in the file */
            $fp = fopen($plugin_def, 'r');
            $class = $buffer = '';
            $i = 0;
            while (!$class) {
                if (feof($fp)) break;

                $buffer .= fread($fp, 512);
                if (preg_match('/class\s+(\w+)\s+extends\s+Plugin/i', $buffer, $matches)) {
                    $plugin_class = "${matches[1]}";
                    break;
                }
            }

            if (empty($plugin_class))
                throw new Exception("Invalid plugin ($plugin_name). Class '$plugin_class' does not extends the 'Plugin' base class.");

            /* Create a new instance of the plugin */
            $plugin = new $plugin_class($plugin_name, $this);
            $this->_plugins[$plugin_name] = $plugin;

            /* Load plugin dependencies */
            foreach($plugin->dependencies as $dep) {
                if ($dep == $plugin_name)
                    throw new Exception("Circular plugin dependency");

                if (!$this->plugin_loaded($dep))
                    $this->load($dep);
            }

            /* Initialize plugin */
            $plugin->on_load($this);
        }

        return $plugin;
    }


    /*--------------------------------------------------------------------------
     * plugin_exists() : Check if a plugin exists.
     *
     * Arguments
     * ---------
     *  - name : plugin name to check.
     *
     * Returns : TRUE if plugin exists, false otherwise.
     */
    function plugin_exists($name)
    {
        $plugin_dir = DOCUMENT_ROOT . "/plugins/$name";
        $plugin_def = $plugin_dir . "/plugin.php";

        return (@filemtime($plugin_def) !== false);
    }


    /*--------------------------------------------------------------------------
     * plugin_loaded() : Check if the plugin is loaded
     *
     * Arguments
     * ---------
     *  - name : plugin name.
     *
     * Returns : TRUE if plugin was loaded, false otherwise
     */
    function plugin_loaded($name)
    {
        return isset($this->_plugins[$name]);
    }


    /*--------------------------------------------------------------------------
     * sort_tabs() : Sorts tabs according to priority.
     *
     * Arguments
     * ---------
     *  None
     *
     * Returns : Nothing
     */
    function sort_tabs()
    {
        uasort($this->_tabs, array("PluginManager", "cmp_tab"));
    }


    /*--------------------------------------------------------------------------
     * load() : plugin sort callback function
     *
     * Arguments
     * ---------
     *  - a : plugin A
     *  - b : plugin B
     *
     * Returns : Compare result
     */
    private static function cmp_tab($a, $b)
    {
        return ($a["order"] < $b["order"]) ? -1 : 1;
    }


    /*--------------------------------------------------------------------------
     * show_tab_content() : Print tab content.
     *
     * Arguments
     * ---------
     *  - path : Path to a plugin's tab to show the content of.
     *           e.g. PLUGIN_NAME.TAB[.CHILD]
     *
     * Returns : Nothing
     */
    function show_tab_content($path)
    {
        $path = preg_replace("_.*(/|\\\\)_", "", $path);
        $path_item = explode(".", $path, 3);

        /* Load the plugin that correspond to the requested path */
        $plugin = $this->load($path_item[0]);


        if (!isset($path_item[1]))
            throw new Exception("Page not found ($path)");

        $tab = $this->_tabs[$path_item[1]];

        if (isset($path_item[2]))
            $tab = &$tab["childs"][$path_item[2]];

        if (!isset($tab))
            throw new Exception("Page not found ($path)");


        if (isset($_POST["action"]))
            $action = $_POST["action"];
        else
            $action = (isset($_GET["action"])) ? $_GET["action"] : "";

        if (!empty($action))
            $plugin->tab_url["action"] = $action;


        /* Initialize the template engine */
        $template = new TemplateEngine($plugin);


        if (isset($tab["callback"]))
            $plugin->$tab["callback"]($template, $path, $action);
    }


    /*--------------------------------------------------------------------------
     * register_tab() : Create a new tab
     *
     * Arguments
     * ---------
     *  - plugin   : Instance of the plugin registering the tab.
     *  - callback : Function to call when displaying the tab content
     *  - id       : New tab id
     *  - parent   : Parent tab
     *  - caption  : Tab caption
     *  - perm     : Required permission to open the tab
     *  - order    : Tab priority (lower first)
     *
     * Returns : Nothing
     */
    function register_tab(&$plugin, $callback, $id, $parent, $caption, $perm='', $order=100)
    {
        /* Do not register the tab if the user don't have access to it. */
        if (!(check_permission($perm)))
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
        $tab["plugin"] = $plugin->name;
        $tab["perm"] = $perm;
        $tab["caption"] = $caption;
        $tab["order"] = $order;
    }


    /*--------------------------------------------------------------------------
     * register_action() : Register a global plugin action.
     *
     * Arguments
     * ---------
     *  - plugin  : Instance of the plugin registering the action.
     *  - type    : Action category
     *  - name    : Name of the action
     *  - path    : path to the plugin where the action is executed,
     *              excluding the plugin name (PARENT_TAB.CHILD_TAB)
     *  - caption : Action description
     *  - icon    : Icon
     *  - tooltip : Help text to display
     *  - perm    : Required permission to execute the action
     *
     * Returns : Nothing
     */
    function register_action(&$plugin, $type, $name, $path, $caption, $icon, $tooltip="", $perm='')
    {
        /* Do not register the action if the user don't have permission for it */
        if (!(check_permission($perm)))
            return;

        if (!(isset($this->_actions[$type])))
            $this->_actions[$type] = array();

        $action = array();
        $action['type'] = $type;
        $action['name'] = $name;
        $action["path"] = "{$plugin->name}.$path";
        $action["caption"] = $caption;
        $action["tooltip"] = $tooltip;
        $action["icon"] = $icon;
        $action["req_level"] = $req_level;
        $action["perm"] = $perm;

        $this->_actions[$type][] = $action;
    }


    /*--------------------------------------------------------------------------
     * declare_permissions() : Adds a list of available permissions that the plugin
     *                         uses to the permission table.
     *
     * Arguments
     * ---------
     *  - plugin : Instance of the plugin declaring the permissions.
     *  - perms  : Array containing the permissions.
     */
    function declare_permissions(&$plugin, $perms)
    {
        if (!(is_array($perms)))
            throw new Exception("Invalid argument for 'declare_permission' in '{$plugin->name}'. 'perms' expected an array.");

        $this->_perm_table = array_merge($this->_perm_table, $perms);
    }


    /*--------------------------------------------------------------------------
     * get_permissions() : Get a list of available permissions
     *
     * Arguments
     * ---------
     *  None
     *
     * Returns : Array containing the permission list
     */
    function get_permissions_list()
    {
        return $this->_perm_table;
    }
}
