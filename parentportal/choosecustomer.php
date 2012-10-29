<?
require_once("common.inc.php");
require_once("../inc/form.inc.php");
require_once("../inc/html.inc.php");
require_once("../inc/table.inc.php");

// Dis-asssociate Customer ID
if (isset($_GET['disassociate']) && $_GET['disassociate']) {
	$customerid = $_GET['disassociate'] + 0;

	$result = portalDisassociateCustomer($customerid);
	if ($result['result'] != "") {
		error("An error occurred, please try again");
		$error = 1;
	} else {
		redirect("choosecustomer.php");
	}
}

// find customerassociations
$error = 0;
$result = portalGetCustomerAssociations();
if($result['result'] == ""){
	$customerlist = $result['custmap'];
	$customeridlist = array_keys($customerlist);
} else {
	$customeridlist = array();
}

// redirect if only one customer
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

// Choose Customer ID
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


$PAGE = ":";
$TITLE = _L("Select an Account");
$hidenav = 1;
include_once("nav.inc.php");
startWindow(_L("Select Account"));
?>
<div style="margin:5px">
	<?=_L("You have contacts associated with more than one customer account.")?>
	<br><?=_L("Please select the account you would like to access")?>:
	<table cellpadding="3" cellspacing="1" class="list">
		<tr><th class="listHeader"><?=_L("Customer Accounts")?></th></tr>
<?
		$alt = 0;
		foreach($customerlist as $index => $customername){
			$class = "";
			if($alt++ % 2)
				$class="class=\"listAlt\"";
			?><tr>
				<td <?=$class?> ><a href="choosecustomer.php?customerid=<?=$index?>"/><?=escapehtml($customername)?></a></td>
				<td <?=$class?> ><a href="choosecustomer.php?disassociate=<?=$index?>"/><?=_L("Dis-associate")?></a></td>
			</tr><br><?
		}
?>
	</table>
</div>
<?
endWindow();
include_once("navbottom.inc.php");
?>