<?
/*CSDELETEMARKER_START*/
echo "Hello World, TXTREPLY";

// Air2Web or 3ci
$is3ci = true;
if (!isset($_GET['DestAddr']) && isset($_POST['inbound_address'])) {
	$is3ci = false; // isAir2Web
}

// Air2Web postback status
if (!$is3ci && !isset($_POST['message'])) {
//	error_log("txtreply OK for now, no message, this must be a postback status");
	exit();
}

// keywords
$helpkeywords = array("help", "info", "aide");
$optoutkeywords = array("end","stop","quit","cancel","unsubscribe","arret","delete","remove"); // special case "wrong XXX" two words anywhere
$optinkeywords = array("y","yes","optin","subscribe","yea","yeah","ok","okay","register"); // special case "opt in" two words

require_once("XML/RPC.php");
require_once("manager/authclient.inc.php");

//----------------------------------------------------------------------
$SETTINGS = parse_ini_file("inc/settings.ini.php",true);

// 3ci user/pass
$username = isset($SETTINGS['txtreply']['txt_username']) ? $SETTINGS['txtreply']['txt_username'] : "";
$password = isset($SETTINGS['txtreply']['txt_password']) ? $SETTINGS['txtreply']['txt_password'] : "";

// air2web user/pass
$air2username = isset($SETTINGS['txtreply']['txt_air2username']) ? $SETTINGS['txtreply']['txt_air2username'] : "";
$air2password = isset($SETTINGS['txtreply']['txt_air2password']) ? $SETTINGS['txtreply']['txt_air2password'] : "";

// log files
$logfile = isset($SETTINGS['txtreply']['txt_logfile']) ? $SETTINGS['txtreply']['txt_logfile'] : "/usr/commsuite/logs/txtreply.log";
$throttlefile = isset($SETTINGS['txtreply']['txt_throttlefile']) ? $SETTINGS['txtreply']['txt_throttlefile'] : "/tmp/txtreply_sourceday.dat";
//----------------------------------------------------------------------


if ($is3ci) { // 3ci
	$sourceaddress = trim($_GET['SourceAddr']);
	$inboundshortcode = trim($_GET['DestAddr']);
	$message = strtolower($_GET['MessageText']);
//error_log("3ci ".$inboundshortcode);
} else { // air2web
	$sourceaddress = $_POST['device_address'];
	$inboundshortcode = $_POST['inbound_address'];
	$message = strtolower($_POST['message']);
//error_log("air2web ".$inboundshortcode);
}

if ($inboundshortcode == "45305") {
	// 3ci
	$visitlink = "schoolmessenger.com/txt";
} else if ($inboundshortcode == "68453") {
	// air2web US
	$visitlink = "schoolmessenger.com/tm";
} else if ($inboundshortcode == "724665") {
	// air2web Canada
	$visitlink = "schoolmessenger.com/tm";
} else {
	// for now assume air2web
	$visitlink = "schoolmessenger.com/tm";
	error_log("unexpected incoming shortcode ".$inboundshortcode);
}

// Text Message for US up to 160 chars
$helptext = "Text alerts by SchoolMessenger. Reply Y for aprox 5 msgs/mo. Text STOP to quit. Msg&data rates may apply. " . $visitlink;
$infotext = "Unknown response. Reply Y to subscribe for aprox 5 msgs/mo. Text STOP to quit. For more information reply HELP.";
$optouttext = "You are now unsubscribed. Reply Y to re-subscribe for aprox 5 msgs/mo. HELP for help. Msg&data rates may apply. " . $visitlink;
$optintext = "You are now registered to receive aprox 5 msgs/mo. Txt STOP to quit, HELP for help. Msg&data rates may apply. " . $visitlink;

// Text Message for Canada up to 136 chars
/*
if ($inboundshortcode == "724665") {
	$helptext = "Text Alert Service from SchoolMessenger. Send STOP or ARRET to opt out. Msg&data rates may aply";
	$infotext = $helptext;
	$optouttext = "You are now unsubscribed from the text alerts. Txt OPTIN to subscribe, HELP for help. Msg&data rates may aply";
	$optintext = "You are now registered to receive text alerts. Txt STOP to quit, HELP for help.";
}
*/

// strip leading +1 or leading 1 from source address (use phonenumber for authserver block list)
// result should be a valid 10 digit phone number
$phonenumber = ereg_replace("[^0-9]*","",$sourceaddress); // strip non-numeric
$phonenumber = substr($phonenumber, -10); // save last 10 digits

// parse the message
$message = str_replace("\n"," ",$message);
$message = str_replace("\r"," ",$message);
$message = trim($message);

//split on any non alpha character, do not return empty elements
$words = preg_split("/[^a-zA-Z]+/",$message,-1,PREG_SPLIT_NO_EMPTY);

//remove all ignored words from the beginning of the message
$ignorewords = array("re","reply","please","plz","hi","hello");
while (count($words) && in_array($words[0],$ignorewords))
        array_shift($words); //remove first element

$splitmessage = $words;

//check to see if this txt message has any of our keywords
$hashelp = false;
foreach ($helpkeywords as $keyword) {
	if ($splitmessage[0] == $keyword) {
		$hashelp = true;
	}
}
$hasoptout = false;
foreach ($optoutkeywords as $keyword) {
	if ($splitmessage[0] == $keyword) {
		$hasoptout = true;
	}
}
// special case 'wrong' phrases
$wrongindex = array_search('wrong', $splitmessage);
if ($wrongindex !== false && isset($splitmessage[$wrongindex+1])) {
	$secondwords = array("number","no","num","phone","person","contact");
	foreach ($secondwords as $keyword) {
		if ($splitmessage[$wrongindex+1] == $keyword) {
			$hasoptout = true;
		}
	}
}
$hasoptin = false;
foreach ($optinkeywords as $keyword) {
	if ($splitmessage[0] == $keyword) {
		$hasoptin = true;
	}
}
// special case 'opt in' two words
if (isset($splitmessage[1]) &&
	($splitmessage[0] == 'opt') && 
	($splitmessage[1] == 'in')) {
		$hasoptin = true;
}

if ($hashelp) {
	sendtxt($inboundshortcode, $sourceaddress, $helptext);
	
	logExit("HELP");
} else if ($hasoptout) {
	// call authserver to update global blocked list
	blocksms($phonenumber, 'block', 'automated block due to keyword '.$splitmessage[0]);
	
	// reply back with confirmation
	sendtxt($inboundshortcode, $sourceaddress, $optouttext);
	
	logExit("OPTOUT");
} else if ($hasoptin) {
	// call authserver to update global blocked list
	blocksms($phonenumber, 'optin', 'automated optin due to keyword '.$splitmessage[0]);

	// reply back with confirmation
	sendtxt($inboundshortcode, $sourceaddress, $optintext);
	
	logExit("OPTIN");
} else {
	// Send a reply sms message if not in our keyword list, after 5 times today, skip to prevent auto-spamming

	// Check file mod time
	if (file_exists($throttlefile) && date('y m d', filemtime($throttlefile)) === date('y m d')) {
		$newfile = false;
		$throttlefp = fopen($throttlefile, 'r+');
	} else {
		$newfile = true;
		$throttlefp = fopen($throttlefile, 'w');
	}

	if (!$throttlefp) {
		error_log("txtreply Unable to open $throttlefile for writing, will not be able to check duplicate same-day replys");
	}
	//do we need to check for this number?
	if (!$newfile) {
		$infotoday = 0; // count how many responses they sent today, reply up to 5 times (prevent auto-spamming)
		while (!feof($throttlefp)) {
			if (trim(fgets($throttlefp)) === $sourceaddress) {
				$infotoday++;
				if ($infotoday >= 5) {
					fclose($throttlefp);
					logExit("NONE");
				}
			}
		}
	}

	fseek($throttlefp,0,SEEK_END);
	fwrite($throttlefp,"$sourceaddress\n");
	fclose($throttlefp);

	sendtxt($inboundshortcode, $sourceaddress, $infotext);
	logExit("INFO");
}


function sendtxt($shortcode, $sourceaddress, $replybody) {
	global $is3ci, $username, $password, $air2username, $air2password;
	
	if ($is3ci)
		sendtxt3ci($username, $password, $shortcode, $sourceaddress, $replybody);
	else
		sendtxtAir2Web($air2username, $air2password, $shortcode, $sourceaddress, $replybody);
	
}

function sendtxt3ci($username, $password, $shortcode, $sourceaddress, $replybody) {
	//now send the sms
	$client = new SoapClient(null,array("location" => "http://api.cmsmobilesuite.com:8080/axis2-1.3/services/NmApi", "uri" => "http://nmapi.cmsmobilesuite.com"));
	if ($client) {
		try {
			$response = $client->SubmitSMS($username,$password,$shortcode,$sourceaddress,$replybody);
			if (!stripos($response, "code=\"2\"")) {
				global $message;
				error_log("txtreply bad response : $response : $sourceaddress $message");
				exit();
			}
		} catch (SoapFault $fault) {
			global $message;
			error_log("txtreply SOAP Fault: (faultcode: {$fault->faultcode}, faultstring: {$fault->faultstring}) : $sourceaddress $message");
			exit();
		}
	} else {
		global $message;
		error_log("txtreply Error with Soap client: could not send reply SMS : $sourceaddress $message");
		exit();
	}
}

function sendtxtAir2Web($username, $password, $shortcode, $sourceaddress, $replybody) {
	
	// all ampersand must be treated for xml
	$replybody = str_replace("&", "&amp;", $replybody);
	
	// build the xml
	$sendrequest =
'<?xml version="1.0" encoding="UTF-8"?>
<router-api client_id="2notify" version="2.0" customer_id="2920">
      <request request_id="2notify">
                <send_message return_ids="true" priority="bulk">
                <carrier_message carrier="default">
                      <message_content>
                              <body type="text">'.$replybody.'</body>
                                <subject/>
                        <reply_to>'.$shortcode.'</reply_to>
                        </message_content>
                        <modifications>
                                <shorten type="truncate"/>
                                <globalization us_only="false"/>
                        </modifications>
                                <client_data>
                                        <context_id>contextId</context_id>
                                        <reporting_key1>RK1</reporting_key1>
                                        <reporting_key2>RK2</reporting_key2>
                                </client_data>
                                <billing service_level="standard">
                                      <billing_id>billId</billing_id>
                                      <description>desc</description>
                                </billing>
                   </carrier_message>
                        <recipient_list>
                                <recipient>'.$sourceaddress.'</recipient>
                        </recipient_list>
                </send_message>
        </request>
</router-api>';
	
	//now send the sms
	$host = "mrr.air2web.com";
	$url = 'http://mrr.air2web.com/a2w_preRouter/xmlApiRouter';
	$auth = 'Basic '.base64_encode($username.":".$password);

	$context_options = array ('http' => array ('method' => 'POST', 'header' => "Authorization: $auth\r\nContent-Type: text/xml\r\n", 'content' => $sendrequest));
	$context = stream_context_create($context_options);
	$fp = @fopen($url, 'r', false, $context);
	if (!$fp) {
		global $message;
		error_log("txtreply Unable to send to $url : $sourceaddress $message");
		exit();
	}
	$response = @stream_get_contents($fp);
	
	//error_log("request ".$sendrequest);
	//error_log("response ".$response);

	if ($response === false) {
		global $message;
		error_log("txtreply Unable to read from $url : $sourceaddress $message");
		exit();
	}
	
	if (!stripos($response, "<code>100</code>")) {
		global $message;
		error_log("txtreply Failure to send : $response  : $sourceaddress $message");
		exit();
	}
}

// log the post message, action, then exit script
function logExit($smaction) {
	global $logfile;
	global $sourceaddress;
	global $inboundshortcode;
	global $message;
	
	$fp = fopen($logfile,"a");
	if ($fp) {
		$thedate = date("Y-m-d H:i:s, ");
		$data = "&shortcode=".$inboundshortcode . "&smsnumber=".$sourceaddress . "&message=".$message  . "&smaction=".$smaction;
		fwrite($fp, $thedate . $data ."\n");
		fclose($fp);
	}
	else {
		error_log("txtreply Unable to log SMS message ".$data." in $logfile");
	}
	exit();
}

/*CSDELETEMARKER_END*/
?>
