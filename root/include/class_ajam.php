<?php
//******************************************************************************
// class_ajam.php - Asterisk manager interface
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

define("PACKET_HEADER", 0);
define("PACKET_RESPONSE", 1);
define("PACKET_EVENT", 2);


class AJAM
{
    private $_user;
    private $_baseurl;
    private $_secret;
    private $_curl;

    public $last_error = "";


    /*--------------------------------------------------------------------------
     * __construct() : Initialize a new instance of AJAM.
     *
     * Arguments
     * ---------
     *  - dsn     : Manager username.
     *  - secret  : Manager user password.
     *  - baseurl : Url of the manager interface (e.g. http://127.0.0.1:8088)
     *
     * Returns : Nothing
     */
    public function __construct($user, $secret, $baseurl)
    {
        $this->_user = $user;
        $this->_secret = $secret;
        $this->_baseurl = $baseurl;

        $this->_curl = curl_init();
        curl_setopt($this->_curl, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($this->_curl, CURLOPT_HEADER, true);
        curl_setopt($this->_curl, CURLOPT_RETURNTRANSFER, true);

    }


    /*--------------------------------------------------------------------------
     * login() : Open a session on the manager interface.
     *
     * Arguments
     * ---------
     *  None
     *
     * Returns : Nothing
     */
    public function login()
    {
        $errno = 0;
        $errstr = "";

        /* Initiate the session to AMI */
        $response = $this->send("login", array(
            "username" => $this->_user,
            "secret" => $this->_secret
        ));

        if ($response === false)
            throw new Exception("Failed to login on the Asterisk manager interface: {$this->last_error}");
    }


    /*--------------------------------------------------------------------------
     * send() : Sends a command to the manager interface.
     *
     * Arguments
     * ---------
     *  - action : Action to perform.
     *  - params : Parameters to send.
     *
     * Returns : Array containing the response.
     */
    public function send($action, $params)
    {
        $this->last_error = "";
        $action = strtolower($action);


        /* Build query url */
        $url = $this->_baseurl . "/rawman?action=$action&" . http_build_query($params);
        curl_setopt($this->_curl, CURLOPT_URL, $url);

        /* Set previously stored cookies */
        if (!(empty($_SESSION["tmp_ajam_cookies"]))) {
            curl_setopt($this->_curl, CURLOPT_COOKIE, $_SESSION["tmp_ajam_cookies"]);
        }

        /* Execute the query */
        $data = curl_exec($this->_curl);


        /* Extract all the lines from the raw response data */
        $lines = preg_match_all("/(.+):\s+([^\r\n]+)|\r\n\r\n/", $data, $matches);


        $response = array();
        $packet = array();
        $packet_type = PACKET_HEADER;

        for ($i=0; $i < $lines; $i++) {
            $key = strtolower($matches[1][$i]);
            $value = trim($matches[2][$i]);


            /* If the line is empty, add the packet to the response and start a new one */
            if (empty($key)) {

                /* Don't add the header to the response */
                if ($packet_type == PACKET_HEADER)
                    continue;

                $response[] = $packet;
                $packet = array();

            } else {

                if (empty($packet)) {
                    if ($key == "response")
                        $packet_type = PACKET_RESPONSE;

                    if ($key == "event")
                        $packet_type = PACKET_EVENT;
                }

                /* Store cookies */
                if ($key == "set-cookie" and $packet_type == PACKET_HEADER)
                    $_SESSION["tmp_ajam_cookies"] = $value;

                /* Add the line to the current packet */
                if ($packet_type != PACKET_HEADER)
                    $packet[$key] = $value;
            }

            //print "Key='$key'  value='$value'<br>";
        }

        if (strtolower($response[0]["response"]) == "error") {
            $this->last_error = $response[0]["message"];
            return false;
        }

        if (isset($response[0]["eventlist"]))
            $response = array_slice($response, 1, -1);

        if ($action == "waitevent")
            $response = array_slice($response, 1, -1);

        return $response;
    }


    /*--------------------------------------------------------------------------
     * gen_unique_id() : Generates an unique ID to use as ActionID.
     *
     * Arguments
     * ---------
     *  - prefix : Prepend the following to the generated ID.
     *  - suffix : Append the following to the generated ID.
     *
     * Returns : Array containing the response.
     */
    public function gen_unique_id($prefix="", $suffix="")
    {
        return sprintf("%s%d.%d%s", $prefix, time(), rand(0,9999), $suffix);
    }
}