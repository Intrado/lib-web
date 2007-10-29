<?
require_once("common.inc.php");
require_once("../inc/form.inc.php");
require_once("../inc/html.inc.php");
require_once("../inc/table.inc.php");

$error = 0;
$result = portalGetCustomerAssociations();
if($result['result'] == ""){
	$customerlist = $result['custmap'];
	$customeridlist = array_keys($customerlist);
} else {
	$customeridlist = array();
}

if(isset($customeridlist) && !(count($customeridlist) > 1)){
	if(count($customeridlist) == 1){
		$_SESSION['customerid'] = $customeridlist[0];
		$_SESSION['custname'] = $customerlist[$customeridlist[0]];
		$result = portalAccessCustomer($customeridlist[0]);
		if($result['result'] != ""){
			error("An error occurred, please try again");
			$error = 1;
		} else {
			$_SESSION['timezone'] = getSystemSetting("timezone");
			@date_default_timezone_set($_SESSION['timezone']);
			QuickUpdate("set time_zone='" . $_SESSION['timezone'] . "'");
		}
	} else {
		$_SESSION['custname'] = "";
	}
	redirect("start.php");
}

if(isset($_GET['customerid']) && $_GET['customerid']){
	$_SESSION['customerid'] = $_GET['customerid']+0;
	$_SESSION['custname'] = $customerlist[$_SESSION['customerid']];
	$result = portalAccessCustomer($_SESSION['customerid']);
	if($result['result'] != ""){
		error("An error occurred, please try again");
		$error = 1;
	}
	if(!$error){
		$_SESSION['timezone'] = getSystemSetting("timezone");
		QuickUpdate("set time_zone='" . $_SESSION['timezone'] . "'");
		redirect("start.php");
	}
}

if(isset($_GET['logoutcustomer'])){
	unset($_SESSION['customerid']);
	redirect();
}


$PAGE = ":";
$TITLE = "Choose A District/School";
$hidenav = 1;
include_once("nav.inc.php");
startWindow("Districts/Schools");
?>
<br>You have students in multiple districts/schools
<br>Please choose one of the districts/schools you are associated with:
	<table cellpadding="3" cellspacing="1">
<?
		foreach($customerlist as $index => $customername){
			?><tr><td><a href="choosecustomer.php?customerid=<?=$index?>"/><?=$customername?></a></td></tr><br><?
		}
?>
	</table>
<?
endWindow();
include_once("navbottom.inc.php");
?>