<?php
//******************************************************************************
// Template.Functions.php - Template engine functions
// 
// Project   : Asterisk Y.A.A.M (Yet another asterisk manager)
// Version   : 0.1
// Author    : Benoit Frigon
// Last mod. : 3 mar. 2013
// 
// Copyright (c) 2011 - 2012 Benoit Frigon <bfrigon@gmail.com>
// www.bfrigon.com
// All Rights Reserved.
//
// This software is released under the terms of the GNU Lesser General Public 
// License v2.1. 
// A copy of which is available from http://www.gnu.org/copyleft/lesser.html
// 
//******************************************************************************


/*--------------------------------------------------------------------------
 * format_byte() : Return a formated byte representation.
 *
 * Arguments : 
 * 	- $value : Byte value to format
 *
 * Returns   : Formated value
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
 * Arguments : 
 * 	- $number : Phone number to convert
 *
 * Returns   : Formated phone number
 */
function format_phone_number($number, $add_rlookup_link=true)
{
	if (strpos($number, "*") !== false)
		return $number;
		
	switch(strlen($number)) {
		case 11:
			$fmt_number = preg_replace("/(\d{1})(\d{3})(\d{3})(\d{4})/", "$1($2) $3-$4", $number);
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
	
	if ($add_rlookup_link && strlen($number) >= 10)
		return '<a href="?path=ReverseLookup.tools.rlookup&number=' . $number . '">' . $fmt_number . '</a>';
	else
		return $fmt_number;
}



/*--------------------------------------------------------------------------
 * format_time_seconds() : Returns a formated time string from seconds.
 *                         eg. 90 => 1:30
 *
 * Arguments : 
 * 	- $seconds : Number of seconds
 *
 * Returns   : Formated value
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
 * dumpfile() : Print the content of a file.
 *
 * Arguments : 
 * 	- $filename : File to dump.
 *
 * Returns   : Nothing
 */
function dumpfile($filename)
{
	if ($hfile = @fopen($filename, 'r')) {
		$i = 100;

		while (!feof($hfile)) {
			$buffer = fgets($hfile, 4096);
			echo htmlentities($buffer), '<br />';

			if ($i < 0) {
				break;
			}

			$i++;
		}

		fclose($hfile);
	} else {
		echo 'Permission denied.';
	}
}	

/*--------------------------------------------------------------------------
 * dumpgzfile() : Print the content of a gzipped file.
 *
 * Arguments : 
 * 	- $filename : File to dump.
 *
 * Returns   : Nothing
 */
function dumpgzfile($filename)
{
	$hfile = gzopen($filename, 'r');
	
	while (!gzeof($hfile)) {
   		$buffer = gzgets($hfile, 4096);
   		echo htmlentities($buffer), '<br />';
	}
	
	gzclose($hfile);
}	





?>