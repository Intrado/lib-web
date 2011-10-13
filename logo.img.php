<?

//NOTE: this file uses purely authserver calls based on customer url, doesn't touch sessions

$SETTINGS = parse_ini_file("inc/settings.ini.php",true);

//get the customer URL
$CUSTOMERURL = substr($_SERVER["SCRIPT_NAME"],1);
$CUSTOMERURL = strtolower(substr($CUSTOMERURL,0,strpos($CUSTOMERURL,"/")));

apache_note("CS_APP","cs"); //for logging
apache_note("CS_CUST",urlencode($CUSTOMERURL)); //for logging

require_once("XML/RPC.php");
require_once("inc/auth.inc.php");
require_once("inc/utils.inc.php");


$map = getCustomerLogo($CUSTOMERURL);
if ($map !== false) {
	$data = base64_decode($map['customerLogo']);
	$contenttype = $map['contentType'];
} else {
	$data = file_get_contents("img/logo_small.gif");
	$contenttype = "image/gif";
}


header("Content-type: " . $contenttype);
header("Pragma: ");
header("Cache-Control: private");
header("Expires: " . gmdate('D, d M Y H:i:s', time() + 60*60) . " GMT"); //exire in 1 hour, but if theme changes so will hash pointing to this file

echo $data;

?>
