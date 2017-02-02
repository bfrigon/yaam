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


define("PERM_ORIGINATE_CALL", "originate_call");
define("PERM_ORIGINATE_FROM_OTHER_EXT", "originate_from_other_ext");
define("PERM_CHANNEL_STATUS_VIEW", "channel_status_view");

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
    public function on_load(&$manager)
    {

        $manager->register_tab($this, null, "tools", null, "Tools", PERM_NONE, 200);
        $manager->register_tab($this, "on_show_profile", "profile", "tools", "Edit profile", PERM_NONE);
        $manager->register_tab($this, "on_show_originate", "originate", "tools", "Click-2-dial", PERM_ORIGINATE_CALL);
        $manager->register_tab($this, "on_show_channels", "channels", "tools", "Channel status", PERM_CHANNEL_STATUS_VIEW);

        $manager->register_action(
            $this,
            "phone_number_tools",
            "dial",
            "tools.originate",
            "Click to dial",
            "call",
            "Call this number",
            PERM_ORIGINATE_CALL);

        $manager->declare_permissions($this, array(
            PERM_ORIGINATE_CALL,
            PERM_ORIGINATE_FROM_OTHER_EXT,
        ));
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
    public function on_show_profile($template, $tab_path, $action)
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

            if (isset($_POST["user"])) {

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

                $query = $DB->create_query("users");
                $query->where("user", "=", $user);
                $query->limit(1);

                $query->run_query_update($user_data);

                $_SESSION["fullname"] = $user_data["fullname"];

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
    public function on_show_originate($template, $tab_path, $action)
    {

        if (!(check_permission(PERM_ORIGINATE_CALL)))
            throw new Exception("You do not have the required permissions to originate a call!");

        try {

            $ext = (isset($_POST["ext"])) ? $_POST["ext"] : $_SESSION["extension"];
            $number = (isset($_POST["number"])) ? $_POST["number"] : "";
            $caller_num = (isset($_POST["caller_num"])) ? $_POST["caller_num"] : "";
            $caller_name = (isset($_POST["caller_name"])) ? $_POST["caller_name"] : "";
            $timeout = intval(get_global_config_item("click2dial", "timeout", 30));

            if (isset($_GET["number"]) && empty($number))
                $number = $_GET["number"];

            if (isset($_POST["number"])) {

                if (!(check_permission(PERM_ORIGINATE_FROM_OTHER_EXT))
                    && (intval($_SESSION["extension"]) != intval($ext))) {

                    throw new exception("You do not have the required permissions to originate a call from another extension than your own!");
                }

                if ($this->do_originate_call($ext, $number, $caller_num, $caller_name, $timeout) == false)
                    throw new Exception("Failed to originate call on Ext. $ext");

                $date = date(DATE_RFC2822);
                print_message("Originate call succeded.<br />\"$ext\" -> \"$number\" on $date");

            }
        } catch (Exception $e) {

            print_message($e->getmessage(), true);
        }

        require($template->load("originate.tpl"));
    }



    private function do_originate_call($from_ext, $number, $caller_num="", $caller_name="", $timeout=30, $unique_id=null)
    {
        global $MANAGER;

        $ext_info = get_extension_info($from_ext);
        $channels = explode("&", $ext_info["dial_string"]);

        $i = 0;
        foreach($channels as $channel) {

            $params = array(
                "Channel" => $channel,
                "Exten" => $number,
                "Context" => get_global_config_item("click2dial", "context", "default"),
                "Timeout" => $timeout * 1000,
                "Priority" => 1,
                "Async" => "true",
                "CallerID" => "Click-2-dial <$number>",
                "Variable" => "from_ext=$from_ext,caller_name=$caller_name,caller_num=$caller_num"
            );

            if (!(is_null($unique_id)))
                $params["ChannelId"] = sprintf("%s%d", $unique_id, $i++);

            $MANAGER->send("Originate", $params);
        }

        return true;
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
    public function ajax_originate()
    {
        global $MANAGER;

        try {
            $number = $_GET["number"];
            $ext = $_GET["ext"];
            $caller_num = $_GET["caller_num"];
            $caller_name = $_GET["caller_name"];
            $timeout = intval(get_global_config_item("click2dial", "timeout", 30));
            $unique_id = (isset($_GET["uniqueid"]) ? $_GET["uniqueid"] : $MANAGER->gen_unique_id());


            $action = $_GET["action"];


            switch ($action) {
                case "call":
                    $MANAGER->discard_events();

                    $this->do_originate_call($ext, $number, $caller_num, $caller_name, $timeout, $unique_id);

                    print json_encode(array(
                        "status" => "ok",
                        "message" => "Dialing extension $ext...",
                        "call_id" => $unique_id,
                    ));
                    break;

                case "progress":
                    $response = array();

                    $channels = $MANAGER->send("status", array());
                    foreach ($channels as $channel) {

                        if (substr($channel["uniqueid"], 0, -1) != $unique_id)
                            continue;


                        $response[] = $channel;
                    }

                    $response["status"] = "ok";



                    print json_encode($response);
                    break;

                case "cancel":
                    break;


                default:
                    throw new Exception("Invalid action");
            }

        } catch (Exception $e) {

            print(json_encode(array(
                "status" => "error",
                "error" => $e->getmessage()
            )));
        }
    }


    /*--------------------------------------------------------------------------
     * on_show_channels() : Called when the 'channel status' tab content is requested.
     *
     * Arguments :
     * ---------
     *  - template : Instance of the template engine.
     *  - tab_path : Path to the current tab.
     *  - action   : Requested action.
     *
     * Return : None
     */
    public function on_show_channels($template, $tab_path, $action)
    {
        global $MANAGER;

        $channels = $MANAGER->send("Status", array());

        print_r($channels);

    }
}
