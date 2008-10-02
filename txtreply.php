<?
$username = "schoolmessenger";
$password = "h3qfh221";
$shortcode = "45305";
$logfile = "/usr/commsuite/logs/txt.log";


$body="";
$line = date("Y-m-d H:i:s,");
foreach ($_GET as $k => $v) {
	$line .= "$k=$v&";
	$body .= "$k=$v\n";
}
// Always log all incomming texts
$fp = fopen($logfile,"a");
if ($fp) {
	fwrite($fp, $line . "\n");
	fclose($fp);
}
else {
	error_log("Unable to log SMS message from $replyto in $logfile");
}

$message = $_GET["MessageText"];
$replyto = $_GET["SourceAddr"];
$message = strtolower($message);

$keywords = array("end","stop","quit","cancel","unsubscribe");

// Check if message start with help
if (stripos($message,"help") === 0) {
	//echo "Help: do nothing";// For testing
	exit(); // Nothing else to do, response handled by 3CI
}

// Send a reply message if not in out keyword list
if (!in_array($message,$keywords)) {
	$client = new SoapClient(null,array("location" => "http://api.cmsmobilesuite.com:8080/axis2-1.3/services/NmApi", "uri" => "http://nmapi.cmsmobilesuite.com"));

	if (client) {
		try {
			//$response = $client->SubmitSMS($username,$password,$shortcode,$replyto,"This is the SchoolMessenger automated notification system. For more information, reply HELP. Send STOP to opt out. Std rates/other chgs may apply.");
			echo "Sending SMS";// For testing
		} catch (SoapFault $fault) {
			error_log("SOAP Fault: (faultcode: {$fault->faultcode}, faultstring: {$fault->faultstring})");
		}
	} else {
		error_log("Error with Soap client: could not send reply SMS");
	}

} else { // Send a email to support
	echo "Emailing";// For testing

	$cmd = "/usr/commsuite/java/j2sdk/bin/java -jar /usr/commsuite/server/simpleemail/simpleemail.jar";
	$cmd .= " -s \"New SMS Message\"";
	$cmd .= " -f \"noreply@schoolmessenger.com\"";
	$cmd .= " -t \"marnberg@schoolmessenger.com\"";
	$process = popen($cmd, "w");
	if ($process) {
		fwrite($process, $body);
		fclose($process);
	} else {
		error_log("Unable to email SMS message from $replyto");
	}
}




?>