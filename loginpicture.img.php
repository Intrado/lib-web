<?

$SETTINGS = parse_ini_file("inc/settings.ini.php",true);
$IS_COMMSUITE = $SETTINGS['feature']['is_commsuite'];

//get the customer URL
if ($IS_COMMSUITE) {
	$CUSTOMERURL = "default";
} /*CSDELETEMARKER_START*/ else {
	$CUSTOMERURL = substr($_SERVER["SCRIPT_NAME"],1);
	$CUSTOMERURL = strtolower(substr($CUSTOMERURL,0,strpos($CUSTOMERURL,"/")));
} /*CSDELETEMARKER_END*/

apache_note("CS_CUST",urlencode($CUSTOMERURL)); //for logging

require_once("XML/RPC.php");
require_once("inc/auth.inc.php");


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
