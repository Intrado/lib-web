<?
include_once("common.inc.php");
include_once("../inc/html.inc.php");

if (!$MANAGERUSER->authorized("logincustomer"))
	exit("Not Authorized");
	
////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

if (isset($_GET['id'])) {
	$custid = $_GET['id'] + 0;
}

$customerurl = QuickQuery("select urlcomponent from customer where id = '$custid'");
$string = md5($MANAGERUSER->login . microtime() . $customerurl);
// TODO we may want to set the expiration interval in the properties file
QuickUpdate("update customer set logintoken = '$string', logintokenexpiretime = now() + interval 5 minute where id = '$custid'");

redirect($SETTINGS['feature']['tai_backdoor_url'] ."/#backdoor?asptoken=$string"); // NOTE customerid not passed, looked up via authserver

?>