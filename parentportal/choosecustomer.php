<?
require_once("common.inc.php");
require_once("../inc/form.inc.php");
require_once("../inc/html.inc.php");

$customerlist = portalGetCustomerAssociations(session_id());
$customeridlist = array_keys($customerlist);
if(isset($customeridlist) && !(count($customeridlist) > 1)){
	if(count($customeridlist) == 1){
		$_SESSION['customerid'] = $customeridlist[0];
		$_SESSION['custname'] = $customerlist[$customeridlist[0]];
		portalAccessCustomer(session_id(), $customeridlist[0]);
	}
	redirect("start.php");
}

if(isset($_GET['customerid']) && $_GET['customerid']){
	$_SESSION['customerid'] = $_GET['customerid']+0;
	$_SESSION['custname'] = $customerlist[$_SESSION['customerid']];
	portalAccessCustomer(session_id(), $_SESSION['customerid']);
	redirect("start.php");
}

if(isset($_GET['logoutcustomer'])){
	unset($_SESSION['customerid']);
	redirect();
}

?>
<br>You have students in multiple districts/schools
<br>Please choose one of the districts/schools you are associated with:
<br>
<?
foreach($customerlist as $index => $customername){
	?><a href="choosecustomer.php?customerid=<?=$index?>"/><?=$customername?></a><br><?
}
?>