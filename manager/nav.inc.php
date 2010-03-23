<?
if ($MANAGERUSER->preference("favcustomers"))
	$favcid = "?cid=" . implode(",",$MANAGERUSER->preference("favcustomers"));
else
	$favcid= "";
?>

<html>
<head>
<?
if (isset($_GET['monitor'])) {
	$time = $_GET['monitor'] ? $_GET['monitor'] : 15;
	echo '<meta http-equiv="refresh" content="'.$time.'">';
} else {
	echo '<meta http-equiv="refresh" content="1800;url=index.php?logout=1&reason=timeout">';
}
?>
<style media="print">
.noprint {
	display: none;
}
</style>

	<script src="../script/prototype.js" type="text/javascript"></script>
	<script src="../script/scriptaculous.js" type="text/javascript"></script>
</head>
<body>
<image src="manager.png">
<script>
	function getObj(name)
	{
	  if (document.getElementById)
	  {
	  	this.obj = document.getElementById(name);
	  }
	  else if (document.all)
	  {
		this.obj = document.all[name];
	  }
	  else if (document.layers)
	  {
	   	this.obj = document.layers[name];
	  }
	  if(this.obj)
		this.style = this.obj.style;
	}
	function popup(url, width, height) {
		window.open(url, '_blank', 'width=' + width + ',height=' + height + 'location=no,menubar=no,resizable=yes,scrollbars=yes,status=no,titlebar=no,toolbar=no');
	}
</script>

<script src="sorttable.js"></script>
<script src='utils.js'></script>
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

<table border=0 cellpadding=5 class="imagelink noprint">
	<tr>
		<td><a href="customers.php" title="Customer&nbsp;List"><img src="img/custlist.png" border=0><div>Customer&nbsp;List</div></a></td>
	<? if ($MANAGERUSER->authorized("imports")) { ?>
		<td><a href="customerimports.php<?=$favcid?>" title="Customer&nbsp;Imports"><img src="img/custimports.png" border=0><div>Customer&nbsp;Imports</div></a></td>
	<? } ?>
	<? if ($MANAGERUSER->authorized("activejobs")) { ?>
		<td><a href="customeractivejobs.php" title="Active&nbsp;Jobs"><img src="img/activejobs.png" border=0><div>Active&nbsp;Jobs</div></a></td>
	<? } ?>
	<? if ($MANAGERUSER->authorized("newcustomer")) { ?>
		<td><a href="newcustomer.php" title="New&nbsp;Customer"><img src="img/newcustomer.png" border=0><div>New&nbsp;Customer</div></a></td>
	<? } ?>
	<? if ($MANAGERUSER->authorized("lockedusers")) { ?>
		<td><a href="lockedusers.php" title="Locked&nbsp;Users"><img src="img/lockedusers.png" border=0><div>Locked&nbsp;Users</div></a></td>
	<? } ?>
	<? if ($MANAGERUSER->authorized("editdm")) { ?>
		<td><a href="customerdms.php?clear" title="Flex Appliances"><img src="img/rdms.png" border=0><div>Flex</div></a></td>
	<? } ?>
	<? if ($MANAGERUSER->authorized("systemdm")) { ?>
		<td><a href="systemdms.php" title="System DMs"><img src="img/sysdm.png" border=0><div>System&nbsp;DMs</div></a></td>
	<? } ?>
	<? if ($MANAGERUSER->authorized("diskagent")) { ?>
		<td><a href="diskagents.php" title="DISK Agents"><img src="img/diskagent.png" border=0><div>DISK&nbsp;Agents</div></a></td>
	<? } ?>
	<? if ($MANAGERUSER->authorized("customercontacts")) { ?>
		<td><a href="customercontactsearch.php" title="Contact Search"><img src="img/search.png" border=0><div>Contact&nbsp;Search</div></a></td>
	<? } ?>
	<? if ($MANAGERUSER->authorized("smsblock")) { ?>
		<td><a href="smsblock.php" title="SMS Block"><img src="img/smsblock.png" border=0><div>SMS&nbsp;Block</div></a></td>
	<? } ?>
	<? if ($MANAGERUSER->authorizedAny(array("billablecalls","passwordcheck","bouncedemailsearch"))) { ?>
		<td><a href="advancedactions.php" title="Advanced Actions"><img src="img/config.png" border=0><div>Advanced</div></a></td>
	<? } ?>	
		<td><a href="./?logout=1&reason=request" title="Log&nbsp;Out"><img src="img/logout.png" border=0><div>Log&nbsp;Out</div></a></td>
	</tr>
</table>
