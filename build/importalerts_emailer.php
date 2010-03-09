<?

//-------------------------------------
// Author Joshua Lai
// Modified by Kee-Yip Chan (kchan@schoolmessenger.com)
// Modified by Ben
// In the dev wiki: [[Import alerts, emailer]]
//-------------------------------------
// Simple emailer script for import alerts.
// This script will connect to the authserver
// to find all customer connections to traverse for import alerts.
// It will only look at import alerts with email addresses.

$logfile = "/usr/commsuite/logs/importalerts.log";
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


$logfp = fopen($logfile,"a") or die("Can't open log file for writing");
$scripttz = date_default_timezone_get(); //keep a copy of the current TZ so we don't go insane while processing customer imports
wlog("Starting");

// Gather authserver properties
file_exists($authpropertiesfile) or wlogdie("Missing auth properties file: $authpropertiesfile");
$settings = @parse_ini_file($authpropertiesfile);
if (empty($settings))
	wlogdie("No settings defined");

// Gather shardsinfo.
mysql_connect($settings['authdb.host'], $settings['authdb.username'], $settings['authdb.password'], true) or wlogdie("Error connecting to authserver mysql:" . mysql_error());
mysql_select_db($settings['authdb.dbname']) or wlogdie(mysql_error());
$shardsinfo = array();
$shardquery = mysql_query("SELECT id, dbhost, dbusername, dbpassword FROM shard ORDER BY id");
while ($sh = mysql_fetch_assoc($shardquery)) {
	$shardsinfo[$sh['id']] = $sh;
}
if (empty($shardsinfo))
	wlogdie("No shardsinfo available");

// Process customers.
$customerquery = mysql_query("SELECT id, shardid, urlcomponent FROM customer ORDER BY shardid, id");
while ($c = mysql_fetch_assoc($customerquery)) {
	$cshardinfo = $shardsinfo[$c['shardid']];
	mysql_connect($cshardinfo['dbhost'], $cshardinfo['dbusername'], $cshardinfo['dbpassword'], true) or wlogdie("Error connecting to customer mysql:" . mysql_error());
	mysql_select_db("c_{$c['id']}") or wlogdie("Error selcting customer db: " . mysql_error());
	$settingquery = mysql_query("SELECT value FROM setting WHERE name = 'displayname'");
	$displayname = mysql_fetch_assoc($settingquery);
	$displayname = $displayname['value'];
	$settingquery = mysql_query("SELECT value FROM setting WHERE name = 'timezone'");
	$timezone = mysql_fetch_assoc($settingquery);
	$timezone = $timezone['value'];
	if (!$timezone)
		wlogdie("Customer {$c['id']} No timezone found in settings!!!");
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
//		wlog("Processing cid:" . $c['id'] . " importid:" . $import['id']);

		// Alert if a file has never been uploaded
		if (empty($import['lastuploaded'])) {
			$alerts[] = "No file has ever been uploaded.";
		// Alert if upload status indicates a problem
		} else if ($import['status'] === 'error') {
			$alerts[] = "Upload status is ERROR";
		} else {
			// Stale Data Alert
			if (isset($alertoptions['daysold']) && ($alertoptions['daysold'] > 0)) {
				$timediffallowed = ($alertoptions['daysold'] * HOURSPERDAY * SECONDSPERHOUR) + ($staledataleewayhours * SECONDSPERHOUR);
				$timediff = $currenttimestamp - $import['lastuploaded'];
				if ($timediff > $timediffallowed)
					$alerts[] = "Data is stale; specified {$alertoptions['daysold']} day(s) until stale, but last uploadeded " . date("F d, Y h:i a", $import['lastuploaded']) . " ($timezone)";
			// Scheduled Days Alert
			} else if (isset($alertoptions['dow'])) {
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
			
			foreach ($alerts as $alert)
				wlog("Alert for cid:" . $c['id'] . " importid:" . $import['id'] . " alert:" . $alert);
			
			// Determine if an alert was recently sent; if so, skip this import alert
			if (!empty($alertoptions['lastnotified'])) {
				$hoursuntil = SECONDSPERHOUR * $nextemailwaithours;
				$timediff = $currenttimestamp - $alertoptions['lastnotified'];
				if ($timediff < $hoursuntil) {
					wlog("Not emailing, already notified " . seconds_to_str($timediff) . " ago");
					continue;
				}
			}
			
			
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
				$retval = pclose($process);
				if ($retval)
					wlog("Simple email exited with non zero value: $retval");
			}
			wlog("Sent alert email to " . implode(";", $emailaddresses));

			// Update database
			$alertoptions['lastnotified'] = $currenttimestamp;
			$importalerturl = http_build_query($alertoptions, false, "&");
			mysql_query("UPDATE import SET alertoptions='" . mysql_escape_string($importalerturl) . "' WHERE id = " . $import['id']);
		// No alerts found
		} else {
			wlog("No alerts for cid:" . $c['id'] . " importid:" . $import['id']);
		}
	}
}

wlog("Done");
fclose($logfp);


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

function seconds_to_str ($time) {
	$time = abs($time);
	$h = floor($time/(60*60));
	$time = $time % (60*60);
	$m = floor($time/60);
	$s = $time % (60);
	return sprintf("%02d:%02d:%02d",$h,$m,$s);
}

function wlog ($str) {
	global $logfp,$scripttz;
	$oldtz = date_default_timezone_get();
	date_default_timezone_set($scripttz);
	fwrite($logfp, date("Y-m-d H:i:s - ") . $str . "\n");
	date_default_timezone_set($oldtz);
}

function wlogdie ($str) {
	wlog($str);
	die($str);
}

?>
