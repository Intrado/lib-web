<?
/*CSDELETEMARKER_START*/
echo "TXTREPLY";

// Air2Web or 3ci
$is3ci = true;
if (!isset($_GET['DestAddr']) && isset($_POST['inbound_address'])) {
	$is3ci = false; // isAir2Web
}

// Air2Web postback status
if (!$is3ci && !isset($_POST['message'])) {
	error_log("unexpected postback status from air2web");
	exit();
}

//----------------------------------------------------------------------
$SETTINGS = parse_ini_file("inc/settings.ini.php",true);

// log files
$tmplogfile = isset($SETTINGS['txtreply']['txt_datfile']) ? $SETTINGS['txtreply']['txt_datfile'] : "/usr/commsuite/cache/txtreply.dat";
//----------------------------------------------------------------------


if ($is3ci) { // 3ci
	$sourceaddress = trim($_GET['SourceAddr']);
	$inboundshortcode = trim($_GET['DestAddr']);
	$message = strtolower($_GET['MessageText']);
	$message_id = "none";
} else { // air2web
	$sourceaddress = $_POST['device_address'];
	$inboundshortcode = $_POST['inbound_address'];
	$message = strtolower($_POST['message']);
	$message_id = $_POST['message_id'];
}

if ($inboundshortcode != "45305" && // 3ci
	$inboundshortcode != "68453" && // air2web US
	$inboundshortcode != "724665") { // air2web Canada
	error_log("unexpected incoming shortcode ".$inboundshortcode);
}


apache_note("CS_CUST", $sourceaddress);
apache_note("CS_USER", $message_id);


// build up name-value pairs
$data = array();
$data['date'] = date("Y-m-d H:i:s");
$data['shortcode'] = $inboundshortcode;
$data['smsnumber'] = $sourceaddress;
$data['message_id'] = $message_id;
$data['message'] = $message;

$httpdata = http_build_query($data);

// write to log file	
$fp = fopen($tmplogfile,"a");
if ($fp) {
	fwrite($fp, $httpdata ."\n");
	fclose($fp);
}
else {
	error_log("Unable to log SMS message ".$httpdata." in $tmplogfile");
}

/*CSDELETEMARKER_END*/
?>
