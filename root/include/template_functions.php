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


/*--------------------------------------------------------------------------
 * format_byte() : Return a formated byte representation.
 *
 * Arguments
 * ---------
 *  - value : Byte value to format
 *
 * Returns : Formated value
 */
function format_byte($value)
{
    if ($value > 1099511627776)
        return sprintf("%.3f TB", $value / 1099511627776);
    if ($value > 1073741824)
        return sprintf("%.2f GB", $value / 1073741824);
    else if ($value > 1048576)
        return sprintf("%.2f MB", $value / 1048576);
    else if ($value > 1024)
        return sprintf("%.2f KB", $value / 1024);
    else
        return $value;
}


/*--------------------------------------------------------------------------
 * format_phone_number() : Returns a formated phone number. (xxx) xxx-xxxx
 *
 * Arguments
 *  - number : Phone number to convert
 *
 * Returns : Formated phone number
 */
function format_phone_number($number, $country="us")
{
    if (strpos($number, "*") !== false)
        return $number;

    switch(strlen($number)) {
        case 11:
            $fmt_number = preg_replace("/(\d{1})(\d{3})(\d{3})(\d{4})/", "($2) $3-$4", $number);
            break;

        case 10:
            $fmt_number = preg_replace("/(\d{3})(\d{3})(\d{4})/", "($1) $2-$3", $number);
            break;

        case 7:
            $fmt_number = preg_replace("/(\d{3})(\d{4})/", "$1-$2", $number);
            break;

        default:
            $fmt_number = $number;
            break;
    }

    return $fmt_number;
}


/*--------------------------------------------------------------------------
 * format_time_seconds() : Returns a formated time string from seconds.
 *                         eg. 90 => 1:30
 *
 * Arguments
 * ---------
 *  - seconds : Number of seconds
 *
 * Returns : Formated value
 */
function format_time_seconds($seconds)
{
    $hour = 0;
    $min = 0;

    if($seconds >= 3600){
      $hour = floor($seconds/3600);
      $seconds %= 3600;
    }

    if($seconds >= 60){
      $min = floor($seconds/60);
      $seconds %= 60;
    }

    $seconds = floor($seconds);

    return sprintf("%d:%02d.%02d", $hour, $min, $seconds);
}


/*--------------------------------------------------------------------------
 * format_unix_time() : Returns a formated date string.
 *
 * Arguments
 * ---------
 *  - date : Input date
 *
 * Returns : Formated value
 */
function format_unix_time($date)
{
    if (empty($date))
        return "";


    $fmt = get_user_dateformat(DATE_FORMAT_DATETIME);

    return strftime($fmt, $date);
}


/*--------------------------------------------------------------------------
 * dumpfile() : Print the content of a file.
 *
 * Arguments
 * ---------
 *  - $filename : File to dump.
 *
 * Returns : Nothing
 */
function dumpfile($filename)
{
    if ($hfile = @gzopen($filename, "r")) {
        $i = 0;

        while (!gzeof($hfile)) {
            $buffer = gzgets($hfile, 4096);
            echo htmlentities($buffer), "<br />";

            /* flush output every ~40k */
            $i++;
            if ($i > 10) {
                $i = 0;

                ob_flush();
            }
       }

        gzclose($hfile);
    } else {
        echo "Unable to open the file : '$filename'.";
    }
}


/*--------------------------------------------------------------------------
 * get_action_list() : Get a list of available actions registred in a category
 *
 * Arguments
 * ---------
 *  - name : Category name
 *
 * Returns : Nothing
 */
function get_action_list($name)
{
    global $PLUGINS;

    if (!(isset($PLUGINS->actions[$name])))
        return array();

    return $PLUGINS->actions[$name];
}
