<?
require_once("common.inc.php");
require_once("../inc/form.inc.php");
require_once("../inc/html.inc.php");
require_once("../inc/utils.inc.php");
require_once("../obj/Phone.obj.php");
require_once("../inc/themes.inc.php");
require_once("AspAdminQuery.obj.php");

if (!$MANAGERUSER->authorized("runqueries"))
	exit("Not Authorized");

if (isset($_GET['cid']))
	$cid = $_GET['cid'] + 0;

// get the lists of queries (single and multiple customer)
if ($MANAGERUSER->queries == "unrestricted") {
	$allCustomerManagerQueries = DBFindMany("AspAdminQuery", "from aspadminquery where options not like '%singlecustomer%' order by name");
	if (isset($cid) && $cid)
		$singleCustomerManagerQueries = DBFindMany("AspAdminQuery", "from aspadminquery where options like '%singlecustomer%' order by name");
} else if ($MANAGERUSER->queries) {
	$allCustomerManagerQueries = DBFindMany("AspAdminQuery", "from aspadminquery where id in ($MANAGERUSER->queries) and options not like '%singlecustomer%' order by name");
	if (isset($cid) && $cid)
		$singleCustomerManagerQueries = DBFindMany("AspAdminQuery", "from aspadminquery where id in ($MANAGERUSER->queries) and options like '%singlecustomer%' order by name");
}

include_once("nav.inc.php");


if (isset($cid) && $cid) {
	$custurl = QuickQuery("select c.urlcomponent from customer c where c.id = ?", false, array($cid));
?>
<h2>Queries for customer: <?=$custurl?></h2>

<?
}?>
<table class=list width="100%" style="table-layout: fixed;">
<?
	$counter = 0;

	// if querying for a single customer and it's a single customer query
	if (isset($singleCustomerManagerQueries) && $singleCustomerManagerQueries) {
?>
	<tr class="listHeader">
		<th align="left" colspan=3>Single Customer Queries</th>
	</tr>
	<tr class="listHeader">
		<th align="left">Name</th>
		<th align="left">Notes</th>
		<th align="left">Action</th>
	</tr>
<?
		foreach ($singleCustomerManagerQueries as $id => $managerquery) {
?>
			<tr <?= $counter++ % 2 == 1 ? 'class="listAlt"' : ''?>>
			<td><?=escapehtml($managerquery->name)?></td>
			<td><div style="overflow: hidden; white-space:nowrap;"><?=escapehtml($managerquery->notes)?></div></td>
			<td><a href="queryrun.php?id=<?="$id"?>&cid=<?="$cid"?>" title="Run"><img src="img/application_go.png" border=0></a></td>
			</tr>
<?				
		}
	}
	
	$counter = 0;
	
	// show all customer queries
	if (isset($allCustomerManagerQueries) && $allCustomerManagerQueries) {
?>
	<tr class="listHeader">
		<th align="left" colspan=3>All Customers Queries</th>
	</tr>
	<tr class="listHeader">
		<th align="left">Name</th>
		<th align="left">Notes</th>
		<th align="left">Action</th>
	</tr>
<?
		foreach ($allCustomerManagerQueries as $id => $managerquery) {
?>
			<tr <?= $counter++ % 2 == 1 ? 'class="listAlt"' : ''?>>
			<td><?=escapehtml($managerquery->name)?></td>
			<td><div style="overflow: hidden; white-space:nowrap;"><?=escapehtml($managerquery->notes)?></div></td>
			<td><a href="queryrun.php?id=<?="$id"?>" title="Run"><img src="img/application_go.png" border=0></a></td>
			</tr>
<?
		}
	}
?>
</table>

<?
include_once("navbottom.inc.php");


