<?php
//******************************************************************************
// Plugins/Tools/plugin.php - Tools plugin
//
// Project : Asterisk Y.A.A.M (Yet another asterisk manager)
// Author  : Benoit Frigon <benoit@frigon.info>
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


class PluginTools extends Plugin
{


    /*--------------------------------------------------------------------------
     * on_load() : Called after the plugin has been initialized.
     *
     * Arguments :
     * ---------
     *  None
     *
     * Return : None
     */
    function on_load()
    {

        $this->register_tab(null, "tools", null, "Tools", "user", 200);
        $this->register_tab("on_show_profile", "profile", "tools", "Edit profile", "user");
        $this->register_tab("on_show_originate", "originate", "tools", "Originate Call", "user");
    }


    /*--------------------------------------------------------------------------
     * on_show_profile() : Called when the 'profile' tab content is requested.
     *
     * Arguments :
     * ---------
     *  - template : Instance of the template engine.
     *  - tab_path : Path to the current tab.
     *  - action   : Requested action.
     *
     * Return : None
     */
    function on_show_profile($template, $tab_path, $action)
    {
        global $DB;

        try {
            $user = $_SESSION["user"];

            $list_themes = array("default" => "Default");
            $list_date_formats = get_dateformat_list();

            $user_data = array(
                "user" => $_SESSION["user"],
                "fullname"    => isset($_POST["fullname"])    ? $_POST["fullname"]    : $_SESSION["fullname"],
            );

            if (isset($_POST["submit"])) {

                if ((!empty($_POST["old_pwd"])) || (!empty($_POST["pwd"])) || (!empty($_POST["pwd_check"]))) {

                    $old_pwhash = hash(sha256, $_POST["old_pwd"]);
                    $pwhash = hash(sha256, $_POST["pwd"]);

                    /* Validate new password */
                    if (empty($_POST["pwd"]))
                        throw new Exception("The new password cannot be empty");

                    if (strlen($_POST["pwd"]) < 6)
                        throw new Exception("The new password must have at least 6 characters");

                    if ($_POST["pwd"] != $_POST["pwd_check"])
                        throw new Exception("The new password does not match");

                    /* Validate old password */
                    $old_pwhash_db = $DB->exec_query_simple(
                        "SELECT pwhash FROM users WHERE user=?",
                        "pwhash", array($user));

                    if ($old_pwhash_db != $old_pwhash)
                        throw new Exception("The old password is incorrect.");

                    /* Add the password to the fields to update. */
                    $user_data["pwhash"] = $pwhash;
                }


                $filters = array(
                    array("user=?", $user),
                );

                $DB->exec_update_query("users", $user_data, $filters, 1);

                /* Save user config */
                $_SESSION["date_format"] = $_POST["date_format"];
                $_SESSION["ui_theme"] = $_POST["ui_theme"];
                save_user_config();

                $message = "Your profile was updated.";
                $url_ok = $this->get_tab_referrer();

                require($template->load("dialog_message.tpl", true));
                return;
            }
        } catch (Exception $e) {

            print_message($e->getmessage(), true);
        }

        /* Load template */
        require($template->load("profile.tpl"));
    }


    /*--------------------------------------------------------------------------
     * on_show_originate() : Called when the 'originate' tab content is requested.
     *
     * Arguments :
     * ---------
     *  - template : Instance of the template engine.
     *  - tab_path : Path to the current tab.
     *  - action   : Requested action.
     *
     * Return : None
     */
    function on_show_originate($template, $tab_path, $action)
    {


    }



/*


    function do_originate($template, $tab_path, $action, $uri)
    {
        global $CONFIG;

        try {

            $f_chan = isset($_POST['chan']) ? $_POST['chan'] : $_SESSION['user_chan'];
            $f_exten = isset($_POST['exten']) ? $_POST['exten'] : "";
            $f_context = isset($_POST['context']) ? $_POST['context'] : "";
            $f_cid_num = isset($_POST['cid_num']) ? $_POST['cid_num'] : "";
            $f_cid_name = isset($_POST['cid_name']) ? $_POST['cid_name'] : "";
            $h_chan = false;
            $h_exten = false;

            if (!function_exists("ami_connect"))
                throw new Exception("Extension php-ami is not installed");

            if (count($_POST) > 0) {

                if (empty($f_chan)) {
                    $h_chan = true;
                    throw new Exception('The destination channel is missing.');
                }

                if (empty($f_exten)) {
                    $h_exten = true;
                    throw new Exception('The destination extension is missing.');
                }


                if (empty($context))
                    $context = "default";

                $cid = NULL;
                $priority = "1";
                $exten = $f_exten;
                $context = $f_context;

                if (!empty($f_cid_num) || !empty($f_cid_name))
                    $cid = "$f_cid_name <$f_cid_num>";

                if (strstr($exten, ",") != 0)
                    list($exten, $priority) = explode(",", $f_exten, 2);

                if (empty($context))
                    $context = "default";



                if (($conn = @ami_connect($CONFIG['ami_host'], $CONFIG['ami_user'], $CONFIG['ami_pass'], $CONFIG['ami_port'])) == NULL)
                    throw new Exception("Cannot connect to the Asterisk manager interface.");

                if (@ami_originate($conn, $f_chan, $context, $exten, $priority, true, $cid)) {

                    $date = date(DATE_RFC2822);
                    print_message("Originate call succeded.<br />\"$f_chan\" -> \"$context/$exten/$priority\" <br />at $date");

                } else {
                    throw new Exception("Originate call failed!<br />" . ami_lasterror());
                }
            }
        } catch (Exception $e) {

            print_message($e->getmessage(), true);
        }

        require($template->load('originate.tpl'));
    }
*/

}
