<?php
ini_set('error_reporting', E_ERROR);
ini_set('display_errors', '1');

//-------------------------------------
// Latest author: Kee-Yip Chan (kchan@schoolmessenger.com)
// In the dev wiki: [[Import alerts, emailer]]
//-------------------------------------
// Simple emailer script for import alerts.
// This script will connect to the authserver
// to find all customer connections to traverse for import alerts.
// It will only look at import alerts with email addresses.

$java = '/usr/commsuite/java/j2sdk/bin/java';
$emailer = '/usr/commsuite/server/simpleemail/simpleemail.jar';
$authpropertiesfile = '/usr/commsuite/server/authserver/authserver.properties';
$nextemailwaithours = 24;
$staledataleewayhours = 1;
$defaultwindowminutes = 10;
define('MINUTESPERHOUR', 60);
define('SECONDSPERHOUR', 3600);
define('SECONDSPERMINUTE', 60);
define('HOURSPERDAY', 24);

// Gather authserver properties
file_exists($authpropertiesfile) or die("Missing auth properties file: $authpropertiesfile");
$settings = parse_ini_file($authpropertiesfile);
if (empty($settings))
	die("No settings defined");

// Gather shardsinfo.
mysql_connect($settings['authdb.host'], $settings['authdb.username'], $settings['authdb.password'], true) or die(mysql_error());
mysql_select_db($settings['authdb.dbname']) or die(mysql_error());
$shardsinfo = array();
$shardquery = mysql_query("SELECT id, dbhost, dbusername, dbpassword FROM shard ORDER BY id");
while ($sh = mysql_fetch_assoc($shardquery)) {
	$shardsinfo[$sh['id']] = $sh;
}
if (empty($shardsinfo))
	die("No shardsinfo available");

// Process customers.
$customerquery = mysql_query("SELECT id, shardid, urlcomponent FROM customer ORDER BY shardid, id");
while ($c = mysql_fetch_assoc($customerquery)) {
	$cshardinfo = $shardsinfo[$c['shardid']];
	mysql_connect($cshardinfo['dbhost'], $cshardinfo['dbusername'], $cshardinfo['dbpassword'], true) or die(mysql_error());
	mysql_select_db("c_{$c['id']}") or die(mysql_error());
	$settingquery = mysql_query("SELECT value FROM setting WHERE name = 'displayname'");
	$displayname = mysql_fetch_assoc($settingquery);
	$displayname = $displayname['value'];
	$settingquery = mysql_query("SELECT value FROM setting WHERE name = 'timezone'");
	$timezone = mysql_fetch_assoc($settingquery);
	$timezone = $timezone['value'];
	if (!$timezone)
		die("Customer {$c['urlcomponent']}: Can't go further, need timezone settings to be specified\n");
	// Use this customer's timezone for all date related calculations
	date_default_timezone_set($timezone);

	// Process this customer's imports.
	$importsquery = mysql_query("SELECT id, name, status, datamodifiedtime AS lastuploaded, LENGTH(data) AS actualsize, alertoptions FROM import WHERE alertoptions != ''");
	while ($import = mysql_fetch_assoc($importsquery)) {
		$import['lastuploaded'] = strtotime($import['lastuploaded']);
		$alertoptions = sane_parsestr($import['alertoptions']);
		if (!isset($alertoptions['emails']))
			continue;

		$currenttimestamp = time();
		$alerts = array();
		print "Customer {$c['urlcomponent']}, import {$import['id']}:\n";
		print_r($alertoptions);

		// Determine if an alert was recently sent; if so, skip this import alert
		if (!empty($alertoptions['lastnotified'])) {
			$hoursuntil = SECONDSPERHOUR * $nextemailwaithours;
			$timediff = $currenttimestamp - $alertoptions['lastnotified'];
			if ($timediff < $hoursuntil) {
				print "Don't want to spam; last notified = " . date("F d, Y h:i:s a", $alertoptions['lastnotified']) . "; now = " . date("F d, Y h:i:s a", $currenttimestamp) . " ($timezone)\n";
				continue;
			}
		// Alert if a file has never been uploaded
		} else if (empty($import['lastuploaded'])) {
			$alerts[] = "No file has ever been uploaded.";
		// Alert if upload status indicates a problem
		} else if ($import['status'] === 'error') {
			$alerts[] = "Upload status is ERROR";
		} else {
			// Stale Data Alert
			if (isset($alertoptions['daysold']) && ($alertoptions['daysold'] > 0)) {
				print "current time: " . date("F d, Y h:i a", $currenttimestamp) . " ($timezone)\n";
				$timediffallowed = ($alertoptions['daysold'] * HOURSPERDAY * SECONDSPERHOUR) + ($staledataleewayhours * SECONDSPERHOUR);
				$timediff = $currenttimestamp - $import['lastuploaded'];
				if ($timediff > $timediffallowed)
					$alerts[] = "Data is stale; specified {$alertoptions['daysold']} day(s) until stale, but last uploadeded " . date("F d, Y h:i a", $import['lastuploaded']) . " ($timezone)";
			// Scheduled Days Alert
			} else if (isset($alertoptions['dow'])) {
				print "current time: " . date("F d, Y h:i a", $currenttimestamp) . " ($timezone)\n";
				if (!isset($alertoptions['scheduledwindowminutes']))
					$alertoptions['scheduledwindowminutes'] = $defaultwindowminutes;
				$daytocheck =  date('w', $currenttimestamp - ($alertoptions['scheduledwindowminutes'] * SECONDSPERMINUTE));
				// Determine if now is the appropriate time to check for a Scheduled Days Alert
				// First, $daytocheck must be a scheduled day
				if (strpos($alertoptions['dow'], $daytocheck) !== false) {
					$timestampforscheduledday = strtotime($alertoptions['time'] . ":00 " . date("F d, Y", $currenttimestamp - ($alertoptions['scheduledwindowminutes'] * SECONDSPERMINUTE)));
					$lowerbound = $timestampforscheduledday - ($alertoptions['scheduledwindowminutes'] * SECONDSPERMINUTE);
					// Then, check for the alert only if the current time is past the scheduled time window
					if ($lowerbound <= $currenttimestamp - 2*($alertoptions['scheduledwindowminutes'] * SECONDSPERMINUTE)) {
						$diffuploadtime = $import['lastuploaded'] - $lowerbound;
						if ($diffuploadtime < 0)
							$alerts[] = "Import data out of date; scheduled time " . date("F d, Y h:i a", $timestampforscheduledday) . ", but last upload occurred " . date("F d, Y h:i a", $import['lastuploaded']) . " ($timezone)";
						else if($diffuploadtime > 2*($alertoptions['scheduledwindowminutes'] * SECONDSPERMINUTE))
							$alerts[] = "Import data uploaded late; scheduled time " . date("F d, Y h:i a", $timestampforscheduledday) . ", but the upload occurred at " . date("F d, Y h:i a", $import['lastuploaded']) . " ($timezone)";
					}
				}
			}

			// Filesize Minimum Alert
			if (isset($alertoptions['minsize']) && ($alertoptions['minsize'] > 0)) {
				if ($import['actualsize'] < $alertoptions['minsize'])
					$alerts[] = "File too small; minimum " . number_format($alertoptions['minsize']) . " bytes, but actual size is " . number_format($import['actualsize']) . " byte(s).";
			}

			// Filesize Maximum Alert
			if (isset($alertoptions['maxsize']) && ($alertoptions['maxsize'] > 0)) {
				if ($import['actualsize'] > $alertoptions['maxsize'])
					$alerts[] = "File too large; maximum " . number_format($alertoptions['maxsize']) . " bytes, but actual size is " . number_format($import['actualsize']) . " byte(s).";
			}
		}

		// Email any alerts and update this customer's database
		if (!empty($alerts)) {
			$subject = "Import Alert: cid " . $c['id'] . ", " . $c['urlcomponent'] . ", import " . $import['id'] . ", " . $import['name'] . ", " . count($alerts) . " alert(s)";
			$body = "Customer, $displayname,\n" . implode("\n", $alerts);
			$emailaddresses = explode(";", $alertoptions['emails']);
			$emailaddresses = array_unique($emailaddresses);
			foreach ($emailaddresses as $i => $address) {
				$emailaddresses[$i] = trim($address);
				if (trim($address) == "")
					continue;
				$cmd = $java . " -jar " . $emailer;
				$cmd .= " -s \"$subject\"";
				$cmd .= " -f \"noreply@schoolmessenger.com\"";
				$cmd .= " -t \"" . trim($address) . "\"";
				$process = popen($cmd, "w");
				fwrite($process, $body);
				fclose($process);
			}
			print implode(";", $emailaddresses) . "\n" .  $subject . "\n" . $body . "\n";

			// Update database
			$alertoptions['lastnotified'] = $currenttimestamp;
			$importalerturl = http_build_query($alertoptions, false, "&");
			mysql_query("UPDATE import SET alertoptions='" . mysql_escape_string($importalerturl) . "' WHERE id = " . $import['id']);
		// No alerts found
		} else {
			print "Nothing to worry about.\n";
		}
	}
}

//----------------------------------
function sane_parsestr($url) {
	$data = array();
	if($url == "")
		return $data;
	$pairs = explode("&",$url);
	foreach ($pairs as $pair) {
		$parts = explode("=",$pair);
		if (count($parts) == 2) {
			$name = urldecode($parts[0]);
			$value = urldecode($parts[1]);
			$data[$name] = $value;
		} else if (count($parts) == 1) {
			$name = urldecode($parts[0]);
			$data[$name] = "";
		}
	}

	return $data;
}
?>
