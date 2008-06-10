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


/*
Etag information assistance by: Matt Midboe
Found at: http://blog.rd2inc.com/archives/2004/12/29/cache_dynamic_images/
*/



$headers = apache_request_headers();

if (isset($headers['If-None-Match']) && strpos($headers['If-None-Match'], "asset-" . $_SESSION['etagstring'])){
// They already have the most up to date copy of the image so tell them
	header('HTTP/1.1 304 Not Modified');
	header("Cache-Control: private");
	// Turn off the no-cache pragma, expires and content-type header
	header("Pragma: ");
	header("Expires: ");
	header("Content-Type: ");
	// The Etag must be enclosed with double quotes

	if(isset($_SESSION['etagstring'])){
		header('ETag: "asset-' . $_SESSION['etagstring'] . '"');
	}
} else {
	$map = getCustomerData($CUSTOMERURL);
	if($map !== false){
		$data = base64_decode($map['customerLogo']);
		$contenttype = $map['contentType'];
		$ext = substr($contenttype, strpos($contenttype, "/")+1);
	} else {
		$data = file_get_contents("img/logo_large.gif");
		$contenttype = "image/gif";
		$ext = ".gif";
	}
	header("Content-disposition: filename=logo." . $ext);
	header("Cache-Control: private");
	header("Content-type: " . $contenttype);
	// Set the content-type to something like image/jpeg and set the length
	header("Pragma: ");
	header("Expires: ");

	// Send the browser the etag so they can cache it
	if(isset($_SESSION['etagstring'])){
		header('ETag: "asset-' . $_SESSION['etagstring'] . '"');
	}
	echo $data;
}

?>