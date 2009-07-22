<?
/*CSDELETEMARKER_START*/
echo "Hello World, TXTPOSTBACK";

// Air2Web

if (!isset($_POST['message'])) {
//	error_log("OK for now, no message, this must be a postback status");
	exit;
}

error_log("postback recieved: ".http_build_query($_POST));

$keywords_optout = array("end","stop","quit","cancel","unsubscribe");

//----------------------------------------------------------------------

$settings = parse_ini_file("inc/settings.ini.php",false);

$username = $settings['txt_username'];
$password = $settings['txt_password'];
//$shortcode = $settings['txt_shortcode']; // use the shortcode the message was sent to
$logfile = isset($settings['txt_logfile']) ? $settings['txt_logfile'] : "/usr/commsuite/logs/txtpostback.log";
$throttlefile = isset($settings['txt_throttlefile']) ? $settings['txt_throttlefile'] : "/tmp/txtpostback_sourceday.dat";

$javadir = isset($settings['txt_javadir']) ? $settings['txt_javadir'] : "/usr/commsuite/java/j2sdk/bin/java";
$emailjar = isset($settings['txt_emailjar']) ? $settings['txt_emailjar'] : "/usr/commsuite/server/simpleemail/simpleemail.jar";

$emails = isset($settings['txt_email']) ? $settings['txt_email'] : array("support@schoolmessenger.com");
if (!is_array($emails)) {
	$emails = array($emails);
}
//----------------------------------------------------------------------

$line = date("Y-m-d H:i:s,");

$sourceaddress = $_POST["device_address"];
$inboundshortcode = $_POST['inbound_address'];

$message = strtolower($_POST['message']);
$message = str_replace("\n"," ",$message);
$message = str_replace("\r"," ",$message);
$message = trim($message);

$subject = "SMS from $sourceaddress: " . $message;

// Always log all incomming texts
$fp = fopen($logfile,"a");
if ($fp) {
	fwrite($fp, $line . http_build_query($_POST) . "\n");
	fclose($fp);
}
else {
	error_log("Unable to log SMS message from $sourceaddress in $logfile");
}

// Check if message starts with help
$splitmessage = explode(" ",$message,2);

if ($splitmessage[0] === "help") {
	$body = "School Messenger Alerts approx 3msg/mo. Visit www.schoolmessenger.com/txtmsg or email support@schoolmessenger.com 4info. Txt STOP 2quit.  Std msg charges apply.";
	sendtxt($username, $password, $inboundshortcode, $sourceaddress, $body);
	exit();
}

//check to see if this txt message has any of our keywords
$hasoptout = false;
foreach ($keywords_optout as $keyword) {
	if (stripos($splitmessage[0],$keyword) === 0) {
		//echo "Keyword found. \n";// For testing
		$hasoptout = true;
	}
}

if (!$hasoptout) {
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

	$body = "This is the SchoolMessenger automated notification system. For more information, reply HELP. Send STOP to opt out. Std rates/other chgs may apply.";
	sendtxt($username, $password, $inboundshortcode, $sourceaddress, $body);
	
} else {
	$body = "You have been unsubscribed from School Messenger Alerts and will no longer receive msgs. 4 info visit www.schoolmessenger.com/txtmsg. Text HELP for info.";
	sendtxt($username, $password, $inboundshortcode, $sourceaddress, $body);
	
	// Send a email to support
	//echo "Emailing. ";// For testing

	foreach ($emails as $email) {
		simpleemail($subject ,http_build_query($_POST), $email, "noreply@schoolmessenger.com:SMS Listener");
	}
}


function sendtxt($username, $password, $shortcode, $sourceaddress, $replybody) {
	error_log("sending txt : ".$replybody." to ".$sourceaddress);
	
	// build the xml
	$sendrequest = ''.
'<?xml version="1.0" encoding="UTF-8"?>'.
'<router-api client_id="2notify" version="2.0" customer_id="2920">'.
'      <request request_id="2notify">'.
'                <send_message return_ids="true" priority="bulk">'.
'                <carrier_message carrier="default">'.
'                      <message_content>'.
'                              <body type="text">'.$replybody.'</body>'.
'                                <subject/>'.
'                        <reply_to>'.$shortcode.'</reply_to>'.
'                        </message_content>'.
'                        <modifications>'.
'                                <shorten type="truncate"/>'.
'                                <globalization us_only="false"/>'.
'                        </modifications>'.
'                                <client_data>'.
'                                        <context_id>contextId</context_id>'.
'                                        <reporting_key1>RK1</reporting_key1>'.
'                                        <reporting_key2>RK2</reporting_key2>'.
'                                </client_data>'.
'                                <billing service_level="standard">'.
'                                      <billing_id>billId</billing_id>'.
'                                      <description>desc</description>'.
'                                </billing>'.
'                   </carrier_message>'.
'                        <recipient_list>'.
'                                <recipient>'.$sourceaddress.'</recipient>'.
'                        </recipient_list>'.
'                </send_message>'.
'        </request>'.
'</router-api>';
	
	//now send the sms
	$host = "mrr.air2web.com";
	$uri = 'http://mrr.air2web.com/a2w_preRouter/xmlApiRouter';
	$auth = 'Basic '.base64_encode($username.":".$password);


	$contentlength = strlen($sendrequest);

	$reqheader =  "POST $uri HTTP/1.1\r\n".
		"Host: $host\r\n".
		"Authorization: ".$auth."\r\n".
		"Content-Type: text/xml\r\n".
		"Content-Length: $contentlength\r\n\r\n".
		"$sendrequest\r\n";

	$socket = fsockopen($host, 80, $errno, $errstr, 0.5);

	if (!$socket) {
   		$result["errno"] = $errno;
   		$result["errstr"] = $errstr;
   		error_log("ERROR failure to send reply txt".$errno.$errstr);
   		exit;
	}

	fputs($socket, $reqheader);

	while (!feof($socket)) {
   		$result[] = fgets($socket, 4096);
	}

	fclose($socket);

	if (!stripos($result[0], "200 OK")) {
		error_log("ERROR sending txt reply : ".http_build_query($result));
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
		error_log("Unable to simpleemail from txtpostback.php to $to from $from");
	}
}

/*CSDELETEMARKER_END*/
?>