<?
/*
Simple emailer script for import alerts.  The script will connect to the authserver
to find all customer connections to traverse for import alerts.  It will only look at
import alerts that have email addresses.
*/


//flag for windows pathing
$IS_WINDOWS=true;



//need trailing "/" for simple emailer path
$simpleemailerpath = "/usr/commsuite/server/simpleemail/";

if(file_exists("/usr/commsuite/server/authserver/authserver.properties")){
	$authsettings = @parse_ini_file("/usr/commsuite/server/authserver/authserver.properties");
	$authhost = $authsettings['authdb.host'];
	$authuser = $authsettings['authdb.username'];
	$authpass = $authsettings['authdb.password'];
} else {
	$authhost="";
	$authuser="";
	$authpass="";
}



if($IS_WINDOWS)
	$windows = "/cygdrive/c";
else
	$windows = "";

$authconn = mysql_connect($authhost, $authuser, $authpass, true);
mysql_select_db("authserver", $authconn);

//gather shard data so we can use super user
//to traverse customer db's instead of opening a new connection to each customer
$res = mysql_query("select id, dbhost, dbusername, dbpassword from shard order by id");
$shardinfo = array();
while($row = mysql_fetch_row($res)){
	$shardinfo[$row[0]] = array($row[1], $row[2], $row[3]);
}

$customerquery = mysql_query("select id, shardid, urlcomponent from customer order by shardid, id");
$customers = array();
while($row = mysql_fetch_row($customerquery)){
	$customers[] = $row;
}
$currhost = "";
$custdb;
$emailmessages = array();
foreach($customers as $cust) {
	echo "Doing customer " . $cust[0] . "\n";
	if($currhost != $cust[1]){
		$custdb = mysql_connect($shardinfo[$cust[1]][0],$shardinfo[$cust[1]][1], $shardinfo[$cust[1]][2], true)
			or die("Could not connect to customer database: " . mysql_error());
		$currhost = $cust[1];
	}
	mysql_select_db("c_" . $cust[0]);

	//use customer timezone for all calculations
	$res = mysql_query("select value from setting where name = 'timezone'", $custdb);
	$row = mysql_fetch_row($res);
	$timezone = $row[0];
	date_default_timezone_set($timezone);

	$res = mysql_query("select id, name, datamodifiedtime, length(data), alertoptions from import where alertoptions != ''", $custdb);
	$imports = array();
	while($row = mysql_fetch_row($res)){
		$imports[] = $row;
	}
	foreach($imports as $import){
		$notified = false;
		$alertoptions = sane_parsestr($import[4]);
		//skip all import alerts with no email addresses

		if(!isset($alertoptions['emails']) || $alertoptions['emails'] == "")
			continue;

		if(isset($alertoptions['lastnotified']) && $alertoptions['lastnotified'] >= (time() - 60*60*8))
			continue;

		$emaillist = explode(";",$alertoptions['emails']);

		if(isset($alertoptions['minsize']) && ($import[3] + 0) < $alertoptions['minsize']){
			$message = "Customer ID: " . $cust[0] . " Import ID: " . $import[0] . " Import Name: " . $import[1] . " has a data file too small. " . number_format($import[3]) . " < " . number_format($alertoptions['minsize']) . " (bytes).";
			$emailmessages = generateMessage($emailmessages, $emaillist, $message);
			$notified = true;
		}
		if(isset($alertoptions['maxsize']) && ($import[3] + 0) > $alertoptions['maxsize']){
			$message = "Customer ID: " . $cust[0] . " Import ID: " . $import[0] . " Import Name: " . $import[1] . " has a data file too large." . number_format($import[3]) . " > " . number_format($alertoptions['maxsize']) . " (bytes).";
			$emailmessages = generateMessage($emailmessages, $emaillist, $message);
			$notified = true;
		}
		if(isset($alertoptions['daysold']) && $alertoptions['daysold'] && ($import[2] < (time() - (15 + 60*60*24*$alertoptions['daysold'])))){
			$message = "Customer ID: " . $cust[0] . " Import ID: " . $import[0] . " Import Name: " . $import[1] . " has a file modified date before " . $alertoptions['daysold'] . " days ago. " . date("M j, Y g:i a (e)",$import[2]);
			$emailmessages = generateMessage($emailmessages, $emaillist, $message);
			$notified = true;
		}
		if(isset($alertoptions['dow'])){
			$scheduledDow=array();
			$scheduledDow = array_flip(explode(",", $alertoptions['dow']));

			//if dow is set (schedule is set)
			//find the last weekday it should have run, including today.
			//if the last scheduled run is later than last run, display error
			$currentdow=date("w")+1;
			$daysago = 0;
			if(strtotime($alertoptions['time']) > strtotime("now")){
				$currentdow--;
				$daysago++;
			}

			while(!isset($scheduledDow[$currentdow])){
				$daysago++;
				$currentdow--;
				if($currentdow < 1){
					$currentdow = $currentdow+7;
				}
			}
			//calculate unix time and allow 15 min leeway
			$scheduledlastrun = strtotime(" -$daysago days " . $alertoptions['time']);
			if($import[2] > ($scheduledlastrun + 60*15) || $import[2] < ($scheduledlastrun - 60*15)){
				$message = "Customer ID: " . $cust[0] . " Import ID: " . $import[0] . " Import Name: " . $import[1] . " did not run at the scheduled time: " . date("M d, Y g:i a", $import[2]);
				$emailmessages = generateMessage($emailmessages, $emaillist, $message);
				$notified = true;
			}
		}
		if($notified){
			$alertoptions['lastnotified'] = strtotime("now");
			$importalerturl = http_build_query($alertoptions, false, "&");
			mysql_query("update import set alertoptions = '" . mysql_escape_string($importalerturl) . "' where id = " . $import[0], $custdb);
		}
	}
}

foreach($emailmessages as $address => $messages){
	echo "Sending alert to " . $address . "\n";
	//implode all the messages together and write it to a file so that new lines are executed correctly
	//then read the file back into a temp variable and output to command line
	$body = implode("\n", $messages);
	if(!$tempfile = secure_tmpname()){
		exit("Failed to create temp file name\n");
	}
	if(!$fp = fopen($tempfile, "w")){
		exit("Failed to open temp file: " . $tempfile . "\n");
	}
	fwrite($fp, $body);
	fclose($fp);
	if($body == ""){
		echo "Body is empty\n";
		continue;
	}
	$cmd = "cat " . $windows . $tempfile . " | ";
	$cmd .= "java -jar " . $simpleemailerpath . "simpleemail.jar ";
	$cmd .= "-s \"Import Alerts\" ";
	$cmd .= "-f noreply@schoolmessenger.com ";
	$cmd .= "-t \"$address\" ";
	$result = exec($cmd);
	unlink($tempfile);
}

echo "Finished sending out alerts";

function generateMessage($emailmessages, $emaillist, $message){
	foreach($emaillist as $email){
		//trip all emails before using
		$email = trim($email);
		if(!isset($emailmessages[$email])){
			$emailmessages[$email] = array();
		}
		$emailmessages[$email][] = $message;
	}
	return $emailmessages;
}

// use sane_parsestr since old parse_str has a bug in an old version of php
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

function secure_tmpname($prefix = 'tmp', $postfix = '.dat') {
	$dir = "/tmp";

   // validate arguments
	if (! (isset($postfix) && is_string($postfix))) {
		return false;
	}
	if (! (isset($prefix) && is_string($prefix))) {
		return false;
	}

	$filename = $dir . "/" . $prefix . microtime(true) . mt_rand() . $postfix;

	$fp = fopen($filename, "w");
	if(file_exists($filename)){
		fclose($fp);
		return $filename;
	} else {
	   return false;
	}
}
?>