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

//TODO implement individual query permissions
$managerqueries = DBFindMany("AspAdminQuery", "from aspadminquery order by name");


include_once("nav.inc.php");

?>

<table class=list width="100%" style="table-layout: fixed;">
	<tr class="listHeader">
		<th align="left">Name</th>
		<th align="left">Notes</th>
		<th align="left">Action</th>
	</tr>
<?
	$counter = 0;
	foreach ($managerqueries as $id => $managerquery) {
?>
		<tr <?= $counter++ % 2 == 1 ? 'class="listAlt"' : ''?>>
		<td><?=escapehtml($managerquery->name)?></td>
		<td><div style="overflow: hidden; white-space:nowrap;"><?=escapehtml($managerquery->notes)?></div></td>
		<td><a href="queryrun.php?id=<?=$id?>" title="Run"><img src="img/application_go.png" border=0></a></td>
		</tr>
<?
	}
?>
</table>

<?
include_once("navbottom.inc.php");


