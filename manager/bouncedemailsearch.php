<?
require_once("common.inc.php");
require_once("../inc/form.inc.php");
require_once("../inc/html.inc.php");
require_once("../inc/utils.inc.php");
require_once("../obj/Phone.obj.php");
require_once("../inc/themes.inc.php");
require_once("../inc/table.inc.php");
require_once("../inc/formatters.inc.php");


if (!$MANAGERUSER->authorized("bouncedemailsearch"))
	exit("Not Authorized");
	
function fmt_custurl($row, $index){
	global $MANAGERUSER, $CUSTOMERINFO;
	
	if ($row[0] == "Total")
		return '';

	if ($MANAGERUSER->authorized("logincustomer"))
		return "<a href='customerlink.php?id=" . $row[0] ."' target=\"_blank\">" . escapehtml($CUSTOMERINFO[$row[0]]['urlcomponent']) . "</a>";
	else
		return escapehtml(escapehtml($CUSTOMERINFO[$row[0]]['urlcomponent']));
}

include_once("nav.inc.php");
?>

<h2>Bounced email search</h2>

<form method=post>
<label>Email (or partial):<input type=text name=email value=""></label>
<button type=submit>Go</button>
</form>
<hr></hr>

<?

if (!isset($_POST['email'])) {
	echo "<h2>enter search email</h2>";
} else {
	
	loadManagerConnectionData();
	
	// With the list of customers ready, connect to each customer's shard and retrieve a bunch of helpful information about the customer.
	$data = array();
	foreach ($CUSTOMERINFO as $cid => $cust) {
		
		$custdb = getPooledCustomerConnection($cid,true);
		
		//do stuff here
		
		$query = "select rs.userid, concat(u.login,if(u.deleted,' (deleted)',if(not u.enabled,' (disabled)',''))), rs.email, rs.name, rs.description from reportsubscription rs left join user u on (rs.userid = u.id) where rs.email like '%" . DBSafe($_POST['email']) . "%'";
		$res = Query($query,$custdb);

		while ($row = DBGetRow($res)) {
			$data[] = array(
				$cid,
				$row[0],
				$row[1],
				$row[2],
				'report sub',
				$row[3] . " -- " . $row[4]
			);
		}		
		
		$query = "select id, concat(login,if(deleted,' (deleted)',if(not enabled,' (disabled)',''))), email, aremail from user where email like '%" . DBSafe($_POST['email']) . "%' or aremail like '%" . DBSafe($_POST['email']) . "%'";
		$res = Query($query,$custdb);
		while ($row = DBGetRow($res)) {
			$data[] = array(
				$cid,
				$row[0],
				$row[1],
				$row[2] . "," . $row[3],
				'user',
				''
			);
		}	
	}
	

	$titles = array(
		0 => "#ID",
		"url" => "#Name",
		1 => "#User ID",
		2 => "#Username",
		4 => "#Type",
		3 => "#Email(s)",
		5 => "#Desc"
	);
	$formatters = array(
		"url" => "fmt_custurl",
	);

	echo "<h3>Bounced email search for $_POST[email]</h3>";
	echo '<table id="bounced" class="list sortable">';
	
	showTable($data,$titles,$formatters);
	
	echo '</table>';

}


include_once("navbottom.inc.php");