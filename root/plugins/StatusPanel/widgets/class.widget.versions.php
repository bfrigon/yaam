<?php
/***************************************************************************
 * Y.A.A.M (Yet Another Asterisk Manager)
 *
 * Copyright (c) 2011 - 2012 Benoit Frigon <bfrigon@gmail.com>
 * All Rights Reserved.
 *
 * This software is released under the terms of the GNU Lesser General Public License v2.1
 *  A copy of which is available from http://www.gnu.org/copyleft/lesser.html
 * 
 */
 
class WidgetVersions
{
	function needs_update()
	{
		return false;
	}

	function print_widget()
	{
			printf("<p><b>%s %s</b><br />%s</p>",
				php_uname('s'),
				php_uname('r'),
				php_uname('v'));		

	}
}

$WIDGETS['versions'] = new WidgetVersions();
?>
