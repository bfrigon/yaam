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
define("PERM_ORIGINATE_CALL", "originate_call");
define("PERM_ORIGINATE_FROM_OTHER_EXT", "originate_from_other_ext");


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

            if (isset($_POST["call"])) {

                $this->do_originate_call($ext, $number, $caller_num, $caller_name, $timeout);

                $date = date(DATE_RFC2822);
                print_message("Originate call succeded.<br />\"$ext\" -> \"$number\" on $date");

            }
        } catch (Exception $e) {

            print_message($e->getmessage(), true);
        }

        require($template->load("originate.tpl"));
        require("{$this->dir}/js_originate.php");
    }


    /*--------------------------------------------------------------------------
     * do_originate_call() : Send the originate call request to AMI for each channels
     *                       associated with the extension.
     *
     * Arguments :
     * ---------
     *  - from_ext    : Extension to originate the call from.
     *  - number      : Number to call when the user pick up.
     *  - caller_num  : CallerID number override
     *  - caller_name : CallerID name override
     *  - timeout     : Time to wait before the user pick up.
     *  - unique_id   : Unique ID to set on channels opened by the originate request.
     *
     * Return : Nothing
     */
    private function do_originate_call($from_ext, $number, $caller_num="", $caller_name="", $timeout=30, $unique_id=null)
    {
        global $MANAGER;

        if (empty($from_ext))
            throw new exception("Missing the extension to originate the call from!");

        if (empty($number))
            throw new exception("Missing the number to call!");

        if (!(check_permission(PERM_ORIGINATE_FROM_OTHER_EXT))
            && (intval($_SESSION["extension"]) != intval($from_ext))) {

            throw new exception("You do not have the required permissions to originate a call from another extension than your own!");
        }

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

            if ($MANAGER->send("Originate", $params) === false) {
                throw new Exception("Failed to originate call on Ext. $ext. " . $MANAGER->last_error);
            }
        }
    }


    /*--------------------------------------------------------------------------
     * ajax_originate() : Ajax function for originating call.
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
            $ext = (isset($_POST["ext"])) ? $_POST["ext"] : $_SESSION["extension"];
            $number = (isset($_POST["number"])) ? $_POST["number"] : "";
            $caller_num = (isset($_POST["caller_num"])) ? $_POST["caller_num"] : "";
            $caller_name = (isset($_POST["caller_name"])) ? $_POST["caller_name"] : "";
            $timeout = intval(get_global_config_item("click2dial", "timeout", 30));
            $unique_id = (isset($_POST["uniqueid"]) ? $_POST["uniqueid"] : $MANAGER->gen_unique_id());



            $action = $_POST["action"];


            switch ($action) {
                /* Initiate the origination */
                case "call":
                    $this->do_originate_call($ext, $number, $caller_num, $caller_name, $timeout, $unique_id);

                    print json_encode(array(
                        "status" => "ok",
                        "message" => "Dialing extension $ext...",
                        "call_id" => $unique_id,
                    ));
                    break;


                /* Returns the status of the origination */
                case "progress":
                    $response = array();
                    $response["channels"] = array();
                    $response["answered"] = 0;
                    $response["message"] = "";
                    $response["status"] = "ringing";

                    $channels = $MANAGER->send("status", array());
                    foreach ($channels as $channel) {

                        /* Filter out channels that were not originated from this request */
                        if (substr($channel["uniqueid"], 0, -1) != $unique_id)
                            continue;

                        $chan_state = $channel["channelstate"];
                        $chan_name = $channel["channel"];
                        $response["channels"][] = $chan_name;

                        if ($chan_state == AST_CHANNEL_STATE_UP || $chan_state == AST_CHANNEL_STATE_OFF_HOOK) {
                            $response["answered"]++;
                        }
                    }

                    /* Originate call failed, all channels has timed out */
                    if (count($response["channels"]) == 0) {
                        $response["message"] = "Extension $ext did not pick up. Timed out.";
                        $response["status"] = "error";
                    }

                    /* Originate call was successful, at least one extension picked up */
                    if ($response["answered"] > 0) {
                        $response["message"] = "Extension $ext picked up. Done.";
                        $response["status"] = "done";
                    }

                    print json_encode($response);
                    break;


                /* Hangup channels that were not picked up, but still ringing */
                case "hangup":
                    $response["status"] = "ok";

                    $channels = $MANAGER->send("status", array());
                    foreach($channels as $channel) {

                        /* Filter out channels that were not originated from this request */
                        if (substr($channel["uniqueid"], 0, -1) != $unique_id)
                            continue;

                        $chan_state = $channel["channelstate"];
                        $chan_name = $channel["channel"];

                        /* Hangup remaining channels that were not picked up */
                        if ($chan_state != AST_CHANNEL_STATE_UP && $chan_state != AST_CHANNEL_STATE_OFF_HOOK) {

                            $request = $MANAGER->send("hangup", array(
                                "channel" => $chan_name,
                            ));

                            if ($request === false)
                                throw new Exception("Unable to hangup channel '$chan_name'");
                        }
                    }

                    print json_encode($response);

                    break;


                default:
                    throw new Exception("Invalid action for ajax method 'originate' ($action)");
            }

        } catch (Exception $e) {

            print(json_encode(array(
                "status" => "error",
                "message" => $e->getmessage()
            )));
        }
    }
}
