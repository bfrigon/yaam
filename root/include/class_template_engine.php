<?php
//******************************************************************************
// class_template_engine.php - Template engine
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


require(DOCUMENT_ROOT . "/include/processors/class_tag_processor_base.php");
require(DOCUMENT_ROOT . "/include/processors/class_tag_processor_dialog.php");
require(DOCUMENT_ROOT . "/include/processors/class_tag_processor_toolbar.php");
require(DOCUMENT_ROOT . "/include/processors/class_tag_processor_icon.php");
require(DOCUMENT_ROOT . "/include/processors/class_tag_processor_datagrid.php");
require(DOCUMENT_ROOT . "/include/processors/class_tag_processor_if.php");
require(DOCUMENT_ROOT . "/include/processors/class_tag_processor_foreach.php");
require(DOCUMENT_ROOT . "/include/processors/class_tag_processor_callback.php");
require(DOCUMENT_ROOT . "/include/processors/class_tag_processor_variable.php");
require(DOCUMENT_ROOT . "/include/processors/class_tag_processor_form.php");
require(DOCUMENT_ROOT . "/include/processors/class_tag_processor_action_list.php");


class TemplateEngine extends TagProcessorBase
{
    private $_template_dir;
    private $_cache_dir;

    public $template_file;
    public $currency_format = "%.3i $";
    public $processors = array();
    public $plugin = null;



    /*--------------------------------------------------------------------------
     * __construct()
     *
     * Arguments :
     * ---------
     *  - plugin : The plugin class the template engine is initialized for. The
     *             engine is going to load the template from the plugin directory.
     *             If sets to 'null', the engine is going to use the global template
     *             directory instead.
     *
     * Returns : None
     */
    function __construct($plugin=null)
    {
        $this->plugin = $plugin;

        if (is_null($plugin))
            $this->_template_dir = dirname(__FILE__) . "/../templates";
        else
            $this->_template_dir = $plugin->dir;

        $this->_cache_dir = dirname(__FILE__) . "/../cache";


        /* Initialize tag processors */
        $this->processors["dialog"] = new TagProcessorDialog($this);
        $this->processors["toolbar"] = new TagProcessorToolbar($this);
        $this->processors["icon"] = new TagProcessorIcon($this);
        $this->processors["datagrid"] = new TagProcessorDatagrid($this);
        $this->processors["grid"] = $this->processors["datagrid"];
        $this->processors["if"] = new TagProcessorIf($this);
        $this->processors["foreach"] = new TagProcessorForeach($this);
        $this->processors["callback"] = new TagProcessorCallback($this);
        $this->processors["call"] = $this->processors["callback"];
        $this->processors["variable"] = new TagProcessorVariable($this);
        $this->processors["var"] = $this->processors["variable"];
        $this->processors["form"] = new TagProcessorForm($this);
        $this->processors["action-list"] = new TagProcessorActionList($this);
        $this->processors["actions"] = $this->processors["action-list"];
    }


    /*--------------------------------------------------------------------------
     * load() : Load the precompiled template or compile it if non-existant.
     *
     * Arguments
     * ---------
     *  - template_name : Template file to load (relative to the template directory)
     *  - use_global    : Force loading from global template directory.
     *  - top_level     : If false, the template is a part of another template.
     *
     * Returns : Filename of the compiled template.
     */
    function load($template_name, $use_global=false, $top_level=false)
    {
        try {

            if ($use_global)
                $template_file = DOCUMENT_ROOT . "/templates/$template_name";
            else
                $template_file = $this->_template_dir . "/$template_name";


            $cache_file = $this->_cache_dir . "/" . md5($template_file) . ".php";

            if (($template_mtime = @filemtime($template_file)) === False)
                throw new Exception("Can't load the template file ($template_file)");

            /* Force re-compiling the template if enabled */
            if (FORCE_RECOMPILE_TEMPLATE)
                $template_mtime = time();

            if (($cache_mtime = @filemtime($cache_file)) !== False && $template_mtime < $cache_mtime)
                return $cache_file;

            /* (re)-compile the template */
            $this->compile($template_file, $cache_file, $top_level);

            return $cache_file;

        } catch (Exception $e) {
            if ($top_level == false)
                throw $e;

            die("Template engine error: " . $e->getmessage());
        }
    }


    /*--------------------------------------------------------------------------
     * compile(): Compile the template
     *
     * Arguments
     * ---------
     *  - template_file     : Source template file(relative to the template directory)
     *  - cache_file        : Compiled template file
     *  - process_top_level : Process top-level elements <doctype>, <html>, <head>, <body>
     *
     * Returns : Filename of the compiled template.
     */
    private function compile($template_file, $cache_file, $top_level=false)
    {
        global $DEBUGINFO_TEMPLATE_ENGINE;

        $compile_start = microtime(true);

        $this->template_file = $template_file;

        /* Open template file */
        $dom_input = new DOMDocument();
        if (!(@$dom_input->loadHTMLFile($template_file)))
            throw new Exception("Template file not found ($template_file)");

        /* Open cache file */
        $handle = @fopen($cache_file, "w");
        if (!$handle)
            throw new Exception("Cannot compile template! <br/>
                The cache directory does not exists or does not have write permissions.");

        /* Insert code that prevents the cache file from being executed directly */
        fwrite($handle, "<?php\n");
        fwrite($handle, "if(realpath(__FILE__) == realpath(\$_SERVER[\"SCRIPT_FILENAME\"]))\n");
        fwrite($handle, "    die();\n");
        fwrite($handle, "?>\n\n");

        $this->process_node($handle, $dom_input, false, true, null, null, $top_level);

        $compile_time = (microtime(true) - $compile_start) * 1000;

        $DEBUGINFO_TEMPLATE_ENGINE .= sprintf("Compiled : %s to %s - compile time: %0.1f ms<br />",
            $template_file, $cache_file, $compile_time);

        fclose($handle);
    }


    /*--------------------------------------------------------------------------
     * throw_compile_exception() : Throw a compile exception
     *
     * Arguments
     * ---------
     *  - message : Reason for the exception.
     *
     * Returns : Nothing
     */
    private function throw_compile_exception($node, $message)
    {
        $file = $this->template_file;
        $line = $node->getLineNo();

        throw new Exception("Template compile error in '$file' at line $line<p>$message</p>");
    }
}
