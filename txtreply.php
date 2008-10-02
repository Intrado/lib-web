<?

$username = "schoolmessenger";
$password = "h3qfh221";
$shortcode = "45305";
$logfile = "/usr/commsuite/logs/txt.log";
$sourcelog = "/usr/commsuite/logs/txtsourceday.log";

$javadir = "/usr/commsuite/java/j2sdk/bin/java";
$emailjar = "/usr/commsuite/server/simpleemail/simpleemail.jar";

$supportemail = "support@schoolmessenger.com";

$keywords = array("end","stop","quit","cancel","unsubscribe");

$body="";
$line = date("Y-m-d H:i:s,");
$sourceaddress = trim($_GET["SourceAddr"]);
$message = strtolower($_GET["MessageText"]);


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
	error_log("Unable to log SMS message from $sourceaddress in $logfile");
}


// Check if message start with help
if (stripos($message,"help") === 0) {
	//echo "Help: do nothing. ";// For testing
	exit(); // Nothing else to do, response handled by 3CI
}

// Send a reply message if not in out keyword list
if (!in_array($message,$keywords)) {
	// Check file mod time
	if (date('y m d', filemtime($sourcelog)) === date('y m d')) {
		//echo "Same day check and append. ";
		$numbers = fopen($sourcelog, 'r+');
		if ($numbers) {
			while(!feof($numbers) && $foundrecord != true ) {
				$phonenumber = fgets($numbers, 20);
				if(trim($phonenumber) === $sourceaddress){
					//echo "Source found, exit. \n";// For testing
					fclose ($numbers);
					exit();
				}
			}
			fseek($numbers,0,SEEK_END);
			fwrite($numbers,"$sourceaddress\n");
			fclose ($numbers);
		} else {
			error_log("Unable to search previous reply sources from $sourcelog. May send reply to same address more than once.");
		}
	} else {
		//echo "New day write new file. ";// For testing
		$numbers = fopen($sourcelog, 'w');
		if ($numbers) {
			fwrite($numbers,"$sourceaddress\n");
			fclose ($numbers);
		} else {
			error_log("Unable to delete phone records and add phonenumer to $sourcelog. May send reply to same address more than once.");
		}

	}

	$client = new SoapClient(null,array("location" => "http://api.cmsmobilesuite.com:8080/axis2-1.3/services/NmApi", "uri" => "http://nmapi.cmsmobilesuite.com"));
	if (client) {
		try {
			$response = $client->SubmitSMS($username,$password,$shortcode,$sourceaddress,"This is the SchoolMessenger automated notification system. For more information, reply HELP. Send STOP to opt out. Std rates/other chgs may apply.");
			//echo "Sending SMS. ";// For testing
		} catch (SoapFault $fault) {
			error_log("SOAP Fault: (faultcode: {$fault->faultcode}, faultstring: {$fault->faultstring})");
		}
	} else {
		error_log("Error with Soap client: could not send reply SMS");
	}

} else { // Send a email to support
	//echo "Emailing. ";// For testing

	$cmd = "$javadir -jar $emailjar";
	$cmd .= " -s \"New SMS Message\"";
	$cmd .= " -f \"noreply@schoolmessenger.com\"";
	$cmd .= " -t \"$supportemail\"";
	$process = popen($cmd, "w");
	if ($process) {
		fwrite($process, $body);
		fclose($process);
	} else {
		error_log("Unable to email SMS message from $sourceaddress");
	}
}




?>