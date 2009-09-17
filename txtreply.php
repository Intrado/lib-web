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
$optoutkeywords = array("end","stop","quit","cancel","unsubscribe");
$optinkeywords = array("optin","subscribe"); // special case "opt in" two words

require_once("XML/RPC.php");
require_once("manager/authclient.inc.php");

//----------------------------------------------------------------------
$SETTINGS = parse_ini_file("inc/settings.ini.php",true);

$username = $SETTINGS['txtreply']['txt_username'];
$password = $SETTINGS['txtreply']['txt_password'];

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
	$visitlink = "www.schoolmessenger.com/txt";
} else if ($inboundshortcode == "68453") {
	// air2web US
	$visitlink = "www.schoolmessenger.com/txtmsg";
} else {
	// TODO air2web canada
	// for now assume 3ci
	$visitlink = "www.schoolmessenger.com/txt";
	error_log("unexpected incoming shortcode ".$inboundshortcode);
}

$helptext = "Text Alert Service from SchoolMessenger. For additional info visit " . $visitlink . ". Send STOP to opt out. Other chgs may apply.";
$infotext = $helptext;
$optouttext = "You are now unsubscribed from this text alert service. Txt OPTIN to subscribe, HELP for help. Check out " . $visitlink . " for info. Other chgs may apply.";
$outintext = "You are now registered to receive text alerts. Txt STOP to quit, HELP for help. Check out " . $visitlink . " for info.";


$message = str_replace("\n"," ",$message);
$message = str_replace("\r"," ",$message);
$message = trim($message);

$splitmessage = explode(" ", $message, 2);

// Check if message starts with help
if ($splitmessage[0] === "help") {
	if (!$is3ci) // 3ci sends for us
		sendtxt($username, $password, $inboundshortcode, $sourceaddress, $helptext);
	
	logExit("HELP");
}

//check to see if this txt message has any of our keywords
$hasoptout = false;
foreach ($optoutkeywords as $keyword) {
	if (stripos($splitmessage[0],$keyword) === 0) {
		$hasoptout = true;
	}
}
$hasoptin = false;
foreach ($optinkeywords as $keyword) {
	if (stripos($splitmessage[0],$keyword) === 0) {
		$hasoptin = true;
	}
}
// special case 'opt in' two words
if (isset($splitmessage[1]) &&
	(stripos($splitmessage[0], 'opt') === 0) && 
	(stripos($splitmessage[1], 'in') === 0)) {
		$hasoptin = true;
}

if ($hasoptout) {
	// call authserver to update global blocked list
	$phonenumber = substr($sourceaddress, 1);
	blocksms($phonenumber, 'block', 'automated block due to keyword '.$splitmessage[0]);
	
	// reply back with confirmation
	if (!$is3ci) // 3ci sends for us
		sendtxt($username, $password, $inboundshortcode, $sourceaddress, $optouttext);
	
	logExit("OPTOUT");
} else if ($hasoptin) {
	// call authserver to update global blocked list
	$phonenumber = substr($sourceaddress, 1);
	blocksms($phonenumber, 'optin', 'automated optin due to keyword '.$splitmessage[0]);

	// reply back with confirmation
	if (!$is3ci) // 3ci not ready for optin keywords
		sendtxt($username, $password, $inboundshortcode, $sourceaddress, $optintext);
	
	logExit("OPTIN");
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
		error_log("txtreply Unable to open $throttlefile for writing, will not be able to check duplicate same-day replys");
	}
	//do we need to check for this number?
	if (!$newfile) {
		while (!feof($throttlefp)) {
			if (trim(fgets($throttlefp)) === $sourceaddress) {
				fclose($throttlefp);
				logExit("NONE");
			}
		}
	}

	fseek($throttlefp,0,SEEK_END);
	fwrite($throttlefp,"$sourceaddress\n");
	fclose($throttlefp);

	sendtxt($username, $password, $inboundshortcode, $sourceaddress, $infotext);
	logExit("INFO");
}


function sendtxt($username, $password, $shortcode, $sourceaddress, $replybody) {
	global $is3ci;
	if ($is3ci)
		sendtxt3ci($username, $password, $shortcode, $sourceaddress, $replybody);
	else
		sendtxtAir2Web($username, $password, $shortcode, $sourceaddress, $replybody);
}

function sendtxt3ci($username, $password, $shortcode, $sourceaddress, $replybody) {
	//now send the sms
	$client = new SoapClient(null,array("location" => "http://api.cmsmobilesuite.com:8080/axis2-1.3/services/NmApi", "uri" => "http://nmapi.cmsmobilesuite.com"));
	if ($client) {
		try {
			$response = $client->SubmitSMS($username,$password,$shortcode,$sourceaddress,"This is the SchoolMessenger automated notification system. For more information, reply HELP. Send STOP to opt out. Std rates/other chgs may apply.");
		} catch (SoapFault $fault) {
			error_log("txtreply SOAP Fault: (faultcode: {$fault->faultcode}, faultstring: {$fault->faultstring})");
		}
	} else {
		error_log("txtreply Error with Soap client: could not send reply SMS");
	}
}

function sendtxtAir2Web($username, $password, $shortcode, $sourceaddress, $replybody) {
	
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
		error_log("txtreply Unable to send to $url");
		exit();
	}
	$response = @stream_get_contents($fp);
	if ($response === false) {
		error_log("txtreply Unable to read from $url");
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
