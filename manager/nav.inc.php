<?
if (isset($_COOKIE["favcustomers"]))
	$favcid = "?cid=" . urlencode($_COOKIE["favcustomers"]);
else
	$favcid= "";
?>

<html>
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

<script src="../script/sorttable.js"></script>
<script src='../script/utils.js'></script>
<style>

.imagelink td {
	text-align: center;
	font-size: 60%;
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

<table border=0 cellpadding=5 class=imagelink>
	<tr>
		<td><a href="customers.php" title="Customer&nbsp;List"><img src="img/custlist.png" border=0><div>Customer&nbsp;List</div></a></td>
		<td><a href="customerimports.php<?=$favcid?>" title="Customer&nbsp;Imports"><img src="img/custimports.png" border=0><div>Customer&nbsp;Imports</div></a></td>
		<td><a href="customeractivejobs.php" title="Active&nbsp;Jobs"><img src="img/activejobs.png" border=0><div>Active&nbsp;Jobs</div></a></td>
		<td><a href="newcustomer.php" title="New&nbsp;Customer"><img src="img/newcustomer.png" border=0><div>New&nbsp;Customer</div></a></td>
		<td><a href="lockedusers.php" title="Locked&nbsp;Users"><img src="img/lockedusers.png" border=0><div>Locked&nbsp;Users</div></a></td>
		<td><a href="customerdms.php?clear" title="Flex Appliances"><img src="img/rdms.png" border=0><div>Flex</div></a></td>
		<td><a href="customercontactsearch.php" title="Contact Search"><img src="img/search.png" border=0><div>Contact&nbsp;Search</div></a></td>
		<td><a href="smsblock.php" title="SMS Block"><img src="img/s-smsblock.png" border=0><div>SMS&nbsp;Block</div></a></td>
		<td><a href="./?logout=1" title="Log&nbsp;Out"><img src="img/logout.png" border=0><div>Log&nbsp;Out</div></a></td>
	</tr>
</table>
