<?
$SETTINGS = parse_ini_file("../inc/settings.ini.php",true);

//get the customer URL
$CUSTOMERURL = substr($_SERVER["SCRIPT_NAME"],1);
$CUSTOMERURL = strtolower(substr($CUSTOMERURL,0,strpos($CUSTOMERURL,"/")));

require_once("XML/RPC.php");
require_once("authsubscriber.inc.php");


$map = getCustomerLoginPicture($CUSTOMERURL);
if ($map !== false) {
	$data = base64_decode($map['loginPicture']);
	$contenttype = $map['loginPictureType'];
	$ext = substr($contenttype, strpos($contenttype, "/")+1);
} else {
	$data = file_get_contents("../img/header_highered3.gif");  // TODO why need the ../ should symlink be set?
	$contenttype = "image/gif";
	$ext = ".gif";
}
header("Content-disposition: filename=picture." . $ext);
header("Cache-Control: private");
header("Content-type: " . $contenttype);

echo $data;

?>