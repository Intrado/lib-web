<?
$SETTINGS = parse_ini_file("subscribersettings.ini.php",true);
$IS_COMMSUITE = $SETTINGS['feature']['is_commsuite'];

//get the customer URL
if ($IS_COMMSUITE) {
	$CUSTOMERURL = "default";
} /*CSDELETEMARKER_START*/ else {
	$CUSTOMERURL = substr($_SERVER["SCRIPT_NAME"],1);
	$CUSTOMERURL = strtolower(substr($CUSTOMERURL,0,strpos($CUSTOMERURL,"/")));
} /*CSDELETEMARKER_END*/

require_once("XML/RPC.php");
require_once("authsubscriber.inc.php");

// if not logged in, get from authserver, else get from cust db
$row = array();
if (!isset($_SESSION['subscriberid'])) {
	$map = getCustomerLogo($CUSTOMERURL);
	if ($map !== false) {
		$row[0] = $map['contentType'];
		$row[1] = $map['customerLogo'];
	}
} else {
	$row = DBQueryRow("select c.contenttype, c.data from setting s inner join content c on (c.id = s.value) where s.name = '_logocontentid'");
}

if (count($row) > 0) {
	$data = base64_decode($row[1]);
	$contenttype = $row[0];
	$ext = substr($contenttype, strpos($contenttype, "/")+1);
} else {
	$data = file_get_contents("../img/logo_small.gif"); // TODO why need the ../ should symlink be set?
	$contenttype = "image/gif";
	$ext = ".gif";
}

header("Content-type: " . $contenttype);
header("Pragma: ");
header("Cache-Control: private");
header("Expires: " . gmdate('D, d M Y H:i:s', time() + 60*60) . " GMT"); //expire in 1 hour, but if theme changes so will hash pointing to this file

echo $data;
?>