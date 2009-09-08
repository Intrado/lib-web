<?
/*CSDELETEMARKER_START*/

$optoutkeywords = array("end","stop","quit","cancel","unsubscribe");
$optinkeywords = array("yes","subscribe","ok");

require_once("XML/RPC.php");
require_once("manager/authclient.inc.php");

//----------------------------------------------------------------------

$settings = parse_ini_file("inc/txtreply.ini.php",false);

$username = $settings['username'];
$password = $settings['password'];
$shortcode = $settings['shortcode'];
$logfile = isset($settings['logfile']) ? $settings['logfile'] : "/usr/commsuite/logs/txt.log";
$throttlefile = isset($settings['throttlefile']) ? $settings['throttlefile'] : "/tmp/txtsourceday.dat";

$javadir = isset($settings['javadir']) ? $settings['javadir'] : "/usr/commsuite/java/j2sdk/bin/java";
$emailjar = isset($settings['emailjar']) ? $settings['emailjar'] : "/usr/commsuite/server/simpleemail/simpleemail.jar";

$emails = isset($settings['email']) ? $settings['email'] : array("support@schoolmessenger.com");
if (!is_array($emails)) {
	$emails = array($emails);
}

$body="";
$line = date("Y-m-d H:i:s,");
$sourceaddress = trim($_GET["SourceAddr"]);
$message = strtolower($_GET["MessageText"]);
$message = str_replace("\n"," ",$message);
$message = str_replace("\r"," ",$message);
$message = trim($message);

$subject = "SMS from $sourceaddress: " . $_GET['MessageText'];

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

// Check if message starts with help
$splitmessage = explode(" ",$message,2);

if (
$splitmessage[0] === "help"   ||
$splitmessage[0] === "yes"    ||
$splitmessage[0] === "school" ||
$splitmessage[0] === "schooldemo") {
	//echo "help,yes,school or schooldemo : do nothing. ";// For testing
	exit(); // Nothing else to do, response handled by 3CI
}

//check to see if this txt message has any of our keywords
$hasoptout = false;
foreach ($optoutkeywords as $keyword) {
	if (stripos($splitmessage[0],$keyword) === 0) {
		//echo "Keyword found. \n";// For testing
		$hasoptout = true;
	}
}

$hasoptin = false;
foreach ($optinkeywords as $keyword) {
	if (stripos($splitmessage[0],$keyword) === 0) {
		//echo "Keyword found. \n";// For testing
		$hasoptin = true;
	}
}

if ($hasoptout) {
	// call authserver to update global blocked list
	blocksms($sourceaddress, 'block', 'automated block due to keyword '.$splitmessage[0]);
	
	// Send a email to support
	//echo "Emailing. ";// For testing
	foreach ($emails as $email) {
		simpleemail($subject,$body,$email,"noreply@schoolmessenger.com:SMS Listener");
	}
} else if ($hasoptin) {
	// call authserver to update global blocked list
	blocksms($sourceaddress, 'optin', 'automated optin due to keyword '.$splitmessage[0]);

} else {
	// Send a reply sms message if not in our keyword list, but only if we haven't already today

	// Check file mod time
	if (file_exists($throttlefile) && date('y m d', filemtime($throttlefile)) === date('y m d')) {
		$newfile = false;
		$throttlefp = fopen($throttlefile, 'r+');
	} else {
		$newfile = true;
		$throttlefp = fopen($throttlefile, 'w');
	}

	if (!$throttlefp) {
		error_log("Unable to open $throttlefile for writing, will not be able to check duplicate same-day replys");
	}
	//do we need to check for this number?
	if (!$newfile) {
		while(!feof($throttlefp)) {
			if(trim(fgets($throttlefp)) === $sourceaddress){
				//echo "Source found, exit. \n";// For testing
				fclose ($throttlefp);
				exit();
			}
		}
	}

	fseek($throttlefp,0,SEEK_END);
	fwrite($throttlefp,"$sourceaddress\n");
	fclose ($throttlefp);

	//now send the sms
	$client = new SoapClient(null,array("location" => "http://api.cmsmobilesuite.com:8080/axis2-1.3/services/NmApi", "uri" => "http://nmapi.cmsmobilesuite.com"));
	if ($client) {
		try {
			$response = $client->SubmitSMS($username,$password,$shortcode,$sourceaddress,"This is the SchoolMessenger automated notification system. For more information, reply HELP. Send STOP to opt out. Std rates/other chgs may apply.");
			//echo "Sending SMS. ";// For testing
		} catch (SoapFault $fault) {
			error_log("SOAP Fault: (faultcode: {$fault->faultcode}, faultstring: {$fault->faultstring})");
		}
	} else {
		error_log("Error with Soap client: could not send reply SMS");
	}
}

//requires $javadir, $emailjar to be set in order to use simple email
function simpleemail ($subject, $body, $to, $from) {
	global $javadir, $emailjar;
	$cmd = "$javadir -jar $emailjar";
	$cmd .= " -s " . escapeshellarg($subject);
	$cmd .= " -f " . escapeshellarg($from);
	$cmd .= " -t " . escapeshellarg($to);
	$process = popen($cmd, "w");
	if ($process) {
		fwrite($process, $body);
		fclose($process);
	} else {
		error_log("Unable to simpleemail from php to $to from $from");
	}
}

/*CSDELETEMARKER_END*/
?>
