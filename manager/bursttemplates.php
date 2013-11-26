<?
require_once("common.inc.php");
require_once("../inc/form.inc.php");
require_once("../inc/html.inc.php");
require_once("../inc/table.inc.php");
require_once("../inc/utils.inc.php");

if (!$MANAGERUSER->authorized("edittemplate"))
	exit("Not Authorized");

if (!isset($_GET['cid']))
	exit("Missing customer id");

$currentid = $_GET['cid'] + 0;
$custinfo = QuickQueryRow("select s.dbhost, c.dbusername, c.dbpassword, c.urlcomponent from customer c inner join shard s on (c.shardid = s.id) where c.id = '$currentid'");
$custdb = DBConnect($custinfo[0], $custinfo[1], $custinfo[2], "c_$currentid");
if (!$custdb) {
	exit("Connection failed for customer: $custinfo[0], db: c_$currentid");
}


// index 0 is type
// index 1 is messagegroupid
function fmt_actions($row, $index) {
	global $currentid;
	$url =  '<a href="editbursttemplate.php?cid=' . $currentid . '&id=' . $row[1] . '" title="Edit"><img src="mimg/s-edit.png" border=0></a>&nbsp;' ;
	return $url;
}

$TITLE = 'Customer Templates';
$PAGE = 'commsuite:customers';

include_once("nav.inc.php");

startWindow(_L('Edit PDF Burst Templates for Customer: ' . $custinfo[3]));

?>
<table class="list sortable" id="customer_templates_table">
<?
	$templates = array();
	$result = Query("select name, id from burst_template order by name", $custdb);
	if (is_object($result)) {
		while ($row = DBGetRow($result)) {
			$templates[] = $row;
		}
	}
	$titles = array('PDF Template', 'actions' => 'Actions');
	$formatters = array("actions" => "fmt_actions");

	showTable($templates, $titles, $formatters);
?>
</table>
<br />
<script language="javascript">
	var table = new getObj('customer_templates_table').obj;
	var trows = table.rows;
	for (var i = 0, length = trows.length; i < length; i++) {
		trows[i].id = 'row'+i;
	}
</script>
<?
endWindow();

include_once("navbottom.inc.php");
?>

