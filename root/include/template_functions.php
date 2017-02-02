<?php
//******************************************************************************
// Template.Functions.php - Template engine functions
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
function format_phone_number($number)
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



function format_unix_time($date)
{

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
 * print_progressbar() : Print the code required to display a progress bar.
 *
 * Arguments
 * ---------
 *  - caption    : Caption of the progress bar
 *  - value      : Value
 *  - percentage : Percentage value
 *  - critical   : Show the value as critical.
 *
 * Returns : Nothing
 */
function print_progressbar($caption, $value, $percentage=-1, $critical=false)
{
    echo "<div class=\"progress", ($critical ? " critical" : ""), "\">";

    if ($percentage > -1)
        echo "<span class=\"bar\" style=\"width: ", $percentage, "%;\"></span>";

    echo "<span class=\"name\">$caption</span>";
    echo "<span class=\"value\">$value";

    if ($percentage > -1)
        sprintf(" (%.1f %%)", $percentage);

    echo "</span></div>";
}


function get_action_list($name)
{
    global $PLUGINS;

    if (!(isset($PLUGINS->_actions[$name])))
        return array();

    return $PLUGINS->_actions[$name];
}


function print_html_header($css="theme.css", $title="")
{
    print "<html>";
    print "<head>";
    print "<title>$title</title>";
    print "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\">";
    print "<link id=\"css_theme\" rel=\"stylesheet\" type=\"text/css\" href=\"themes/{$_SESSION["ui_theme"]}/$css?v=" . YAAM_VERSION . "\" />";
    print "</head>";
    print "<body>";
}
