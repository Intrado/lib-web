<?

$SETTINGS = parse_ini_file("inc/settings.ini.php",true);

//get the customer URL
$CUSTOMERURL = substr($_SERVER["SCRIPT_NAME"],1);
$CUSTOMERURL = strtolower(substr($CUSTOMERURL,0,strpos($CUSTOMERURL,"/")));

apache_note("CS_APP","cs"); //for logging
apache_note("CS_CUST",urlencode($CUSTOMERURL)); //for logging

require_once("XML/RPC.php");
require_once("inc/auth.inc.php");
require_once("inc/utils.inc.php");


$map = getCustomerLoginPicture($CUSTOMERURL);
if ($map !== false) {
	$data = base64_decode($map['loginPicture']);
	$contenttype = $map['loginPictureType'];
	$ext = substr($contenttype, strpos($contenttype, "/")+1);
} else {
	$data = file_get_contents("img/classroom_girl.jpg");
	$contenttype = "image/jpeg";
	$ext = ".jpg";
}
header("Content-disposition: filename=picture." . $ext);
header("Cache-Control: private");
header("Content-type: " . $contenttype);

echo $data;

?>
