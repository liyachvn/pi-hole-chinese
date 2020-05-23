<?php
/* Pi-hole: A black hole for Internet advertisements
*  (c) 2017 Pi-hole, LLC (https://pi-hole.net)
*  Network-wide ad blocking via your own hardware.
*
*  This file is copyright under the latest version of the EUPL.
*  Please see LICENSE file for your rights under this license. */

require "scripts/pi-hole/php/database.php";

function gravity_last_update($raw = false)
{
	$db = SQLite3_connect(getGravityDBFilename());
	$date_file_created_unix = $db->querySingle("SELECT value FROM info WHERE property = 'updated';");
	if($date_file_created_unix === false)
	{
		if($raw)
		{
			// Array output
			return array("file_exists" => false);
		}
		else
		{
			// String output
			return "规则数据库不可用。";
		}
	}
	// Now that we know that $date_file_created_unix is a valid response, we can convert it to an integer
	$date_file_created_unix = intval($date_file_created_unix);
	$date_file_created = date_create("@".$date_file_created_unix);
	$date_now = date_create("now");
	$gravitydiff = date_diff($date_file_created,$date_now);
	if($raw)
	{
		// Array output
		return array(
			"file_exists"=> true,
			"absolute" => $date_file_created_unix,
			"relative" => array(
				"days" =>  intval($gravitydiff->format("%a")),
				"hours" =>  intval($gravitydiff->format("%H")),
				"minutes" =>  intval($gravitydiff->format("%I")),
				)
			);
	}

	if($gravitydiff->d > 1)
	{
		// String output (more than one day ago)
		return $gravitydiff->format("阻止列表已于 %a 天 %H:%I (hh:mm) 前更新。");
	}
	elseif($gravitydiff->d == 1)
	{
		// String output (one day ago)
		return $gravitydiff->format("阻止列表已于 1 天 %H:%I (hh:mm) 前更新。");
	}

	// String output (less than one day ago)
	return $gravitydiff->format("阻止列表已于 %H:%I (hh:mm) 前更新。");
}
?>
