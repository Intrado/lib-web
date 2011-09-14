<?
header('Content-type: text/html; charset=UTF-8') ;

if ($MANAGERUSER->preference("favcustomers"))
	$favcid = "?cid=" . implode(",",$MANAGERUSER->preference("favcustomers"));
else
	$favcid= "";
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<meta http-equiv="Content-type" value="text/html; charset=UTF-8" />
	<meta http-equiv="X-UA-Compatible" content="IE=EmulateIE7" />

<?
if (isset($_GET['monitor'])) {
	$time = $_GET['monitor'] ? $_GET['monitor'] : 15;
	echo '<meta http-equiv="refresh" content="'.$time.'">';
} else {
	$autologoutminutes = isset($SETTINGS['feature']['autologoutminutes']) ? $SETTINGS['feature']['autologoutminutes'] : 30;
	echo '<meta http-equiv="refresh" content="'. 60*$autologoutminutes  .';url=index.php?logout=1&reason=timeout">';
}
?>

	<script src="script/prototype.js" type="text/javascript"></script>
	<script src="script/scriptaculous.js" type="text/javascript"></script>
	<script src="script/prototip.js.php" type="text/javascript"></script>
	<script src="script/utils.js"></script>
	<script src="script/sorttable.js"></script>
	<script src="script/form.js.php" type="text/javascript"></script>
	<link href="../css.php?hash=<?=crc32(serialize($favcid))?>&nocommoninc=1" type="text/css" rel="stylesheet" media="screen, print">
	<link href="css/form.css.php" type="text/css" rel="stylesheet">
	<link href="css/datepicker.css.php" type="text/css" rel="stylesheet">
	<link href="css/prototip.css.php" type="text/css" rel="stylesheet">
	<link href="css/style_print.css" type="text/css" rel="stylesheet" media="print">
	

	<!--[if lte IE 6]>
	    <link href="css/ie6.css" type="text/css" rel="stylesheet"/>
	<![endif]-->
	
	<!--[if lte IE 7]>
	    <link href="css/ie7.css" type="text/css" rel="stylesheet"/>
	<![endif]-->

	
	<link rel="SHORTCUT ICON" href="mimg/manager_favicon.ico" />
	
	
</head>
<body>
	<script>
		var _brandtheme = "classroom";
	</script>

	<IFRAME src="blank.html" id="blocker" style="DISPLAY: none; LEFT: 0px; POSITION: absolute; TOP: 0px" frameBorder="0" scrolling="no"></IFRAME>

<!-- ********************************************************************* -->
<image src="manager.png">

<style>

table {
	font-size: 9pt;
}

.imagelink td {
	text-align: center;
	font-size: 7pt;
}

.listAlt {
	background-color: #dddddd;
}

.listHeader {
	color: white;
	background-color: #999999;
}

.list {
	border: 1px solid gray;
}

</style>

<table border="0" cellpadding="5" class="imagelink noprint">
	<tr>
		<td><a href="customers.php" title="Customer&nbsp;List"><img src="mimg/custlist.png" border=0><div>Customer&nbsp;List</div></a></td>
	<? if ($MANAGERUSER->authorized("imports")) { ?>
		<td><a href="importalerts.php" title="Import&nbsp;Alerts"><img src="mimg/custimports.png" border=0><div>Import&nbsp;Alerts</div></a></td>
	<? } ?>
	<? if ($MANAGERUSER->authorized("activejobs")) { ?>
		<td><a href="customeractivejobs.php" title="Active&nbsp;Jobs"><img src="mimg/activejobs.png" border=0><div>Active&nbsp;Jobs</div></a></td>
	<? } ?>
	<? if ($MANAGERUSER->authorized("newcustomer")) { ?>
		<td><a href="customeredit.php?id=new" title="New&nbsp;Customer"><img src="mimg/newcustomer.png" border=0><div>New&nbsp;Customer</div></a></td>
	<? } ?>
	<? if ($MANAGERUSER->authorized("lockedusers")) { ?>
		<td><a href="lockedusers.php" title="Locked&nbsp;Users"><img src="mimg/lockedusers.png" border=0><div>Locked&nbsp;Users</div></a></td>
	<? } ?>
	<? if ($MANAGERUSER->authorized("editdm")) { ?>
		<td><a href="customerdms.php?clear" title="SmartCall"><img src="mimg/rdms.png" border=0><div>SmartCall</div></a></td>
	<? } ?>
	<? if ($MANAGERUSER->authorized("systemdm")) { ?>
		<td><a href="systemdms.php" title="System DMs"><img src="mimg/sysdm.png" border=0><div>System&nbsp;DMs</div></a></td>
	<? } ?>
	<? if ($MANAGERUSER->authorized("diskagent")) { ?>
		<td><a href="diskagents.php" title="SwiftSync"><img src="mimg/diskagent.png" border=0><div>SwiftSync</div></a></td>
	<? } ?>
	<? if ($MANAGERUSER->authorized("customercontacts")) { ?>
		<td><a href="customercontactsearch.php" title="Contact Search"><img src="mimg/search.png" border=0><div>Contact&nbsp;Search</div></a></td>
	<? } ?>
	<? if ($MANAGERUSER->authorized("smsblock")) { ?>
		<td><a href="smsblock.php" title="SMS Block"><img src="mimg/smsblock.png" border=0><div>SMS&nbsp;Block</div></a></td>
	<? } ?>
	<? if ($MANAGERUSER->authorizedAny(array("billablecalls","passwordcheck","bouncedemailsearch"))) { ?>
		<td><a href="advancedactions.php" title="Advanced Actions"><img src="mimg/config.png" border=0><div>Advanced</div></a></td>
	<? } ?>	
		<td><a href="./?logout=1&reason=request" title="Log&nbsp;Out"><img src="mimg/logout.png" border=0><div>Log&nbsp;Out</div></a></td>
	</tr>
</table>
<div class="maincontent">

	<?
		if (!empty($_SESSION['confirmnotice'])) {
			echo "<div class='confirmnoticecontainer noprint'><div class='confirmnoticecontent noprint'>";
				echo implode("<hr />", $_SESSION['confirmnotice']);
			echo "</div></div>";
		}
		unset($_SESSION['confirmnotice']);
	?>
