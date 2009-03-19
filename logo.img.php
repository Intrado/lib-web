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

require_once("XML/RPC.php");
require_once("inc/auth.inc.php");


$map = getCustomerData($CUSTOMERURL);
if($map !== false){
	$data = base64_decode($map['customerLogo']);
	$contenttype = $map['contentType'];
	$ext = substr($contenttype, strpos($contenttype, "/")+1);
} else {
	$data = file_get_contents("img/logo_small.gif");
	$contenttype = "image/gif";
	$ext = ".gif";
}
//header("Content-disposition: filename=logo." . $ext);
//header("Cache-Control: private");
header("Content-type: " . $contenttype);
header("Pragma: ");
header("Cache-Control: private");
header("Expires: " . gmdate('D, d M Y H:i:s', time() + 60*60) . " GMT"); //exire in 1 hour, but if theme changes so will hash pointing to this file

echo $data;


?>