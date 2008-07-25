<?

$CUSTOMERURL = substr($_SERVER["SCRIPT_NAME"],1);
$CUSTOMERURL = strtolower(substr($CUSTOMERURL,0,strpos($CUSTOMERURL,"/")));

if(isset($_GET['wsdl'])){

	$wsdl = file_get_contents("smapi.wsdl");
	$wsdl = preg_replace("[smapiurl]", 'http://' . $_SERVER["SERVER_NAME"] .'/' . $CUSTOMERURL . '/api',$wsdl);

	header("Pragma: private");
	header("Cache-Control: private");
	header("Content-disposition: attachment; filename=smapi.wsdl");
	header("Content-type: text");

	echo $wsdl;
} else {
	include_once("smapi.php");
}

?>