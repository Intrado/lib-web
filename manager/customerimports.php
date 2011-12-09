<?
require_once("common.inc.php");
require_once("../inc/html.inc.php");
require_once("../inc/formatters.inc.php");
require_once("../inc/form.inc.php");
require_once("../inc/table.inc.php");

if (!$MANAGERUSER->authorized("imports"))
	exit("Not Authorized");

if (isset($_GET['customer'])) {
	$_SESSION['customerid']= $_GET['customer']+0;
	redirect();
}

if (!$_SESSION['customerid']) {
	exit("Not Authorized");
}
$customerid = $_SESSION['customerid'];

define('SECONDSPERDAY', 86400);

////////////////////////////////////////////////////////////////////////////////
// formatters
////////////////////////////////////////////////////////////////////////////////


function fmt_import_date($row,$index) {
	if (isset($row[$index])) {
		$time = strtotime($row[$index]);
		if ($time !== -1 && $time !== false)
			return date("Y-m-d G:i:s",$time);
	}
	return "&nbsp;";
}

function fmt_timestamp($row, $index) {
	$timestamp = strtotime($row[$index]);
	if ($timestamp === false) {
		return "<div>- Never -</div>";
	}
	return fmt_import_date($row, $index);
}

function fmt_filesize($row, $index){
	return "<div style=\"width:100%; text-align:right;\">" . number_format($row[$index]) . "</div>";
}


//index 0 is customer id
//index 3 is import id
function fmt_importalerts($row, $index){
	global $customerid;
	$actions = array();
	$actions[] = action_link("Manager Alert", "eye",'importalertrules.php?cid=' . $customerid . '&importid=' . $row[0] . '&categoryid=1');
	$actions[] = action_link("Customer Alert", "transmit_error",'importalertrules.php?cid=' . $customerid . '&importid=' . $row[0] . '&categoryid=2');
	$actions[] = action_link("Edit Notes", "pencil",'editimport.php?cid=' . $customerid . '&importid=' . $row[0] . '');
	
	return action_links($actions);
}


function fmt_daysold($row, $index) {
	if (isset($row[6]) && $row[6]) {
		return intval((strtotime(date("Y-m-d G:i:s")) - strtotime($row[6])) / SECONDSPERDAY);
	} else {
		return '<div>99999</div>';
	}
}

function fmt_updatemethod($row, $index) {
	switch ($row[$index]) {
		case "full":
			return "Update, create, delete";
			break;
		case "updateonly":
			return "Update only";
			break;
		case "update":
			return "Update & create";
			break;
		default:
			return $row[$index];
	}
}

function fmt_alert($row, $index) {
	if ($row[12])
		return "Configured";
	return "None";
}

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////


$query = "select s.dbhost, c.dbusername, c.dbpassword from customer c inner join shard s on (c.shardid = s.id) where c.id=?";
$custinfo = QuickQueryRow($query,true,false,array($customerid));
$custdb = DBConnect($custinfo["dbhost"], $custinfo["dbusername"], $custinfo["dbpassword"], "c_$customerid");
if (!$custdb) {
	exit("Connection failed for customer: {$custinfo["dbhost"]}, db: c_$customerid");
}

$data = array();

$query = "SELECT i.id, i.name,i.description, i.status, i.type, i.updatemethod,i.datamodifiedtime,i.lastrun,i.datalength,i.datatype,i.notes,i.managernotes, count(ir.id) as alertrules
			FROM import i left join importalertrule ir on (i.id = ir.importid)
			where i.type in ('automatic', 'manual') and i.ownertype = 'system'
			group by i.id
			order by i.id";
$data = QuickQueryMultiRow($query,false,$custdb);
$timezone = getCustomerSystemSetting('timezone', false, true, $custdb);
date_default_timezone_set($timezone);


$titles = array(
	"alert" => "#Alerts",
	"0" => "@#Imp ID ",
	"1" => "#Imp Name",
	"2" => "@#Description",
	"3" =>  "@#Status",
	"4" => "@#Type",
	"5" => "@#Upd. Method",
	"6" => "#Last Modified",
	"daysold" => "#Days Old",
	"7" => "#Last Run",
	"8" => "#File Size in Bytes",
	"9" => "@#Data Type",
	"10" => "@#Notes",
	"11" => "Manager Notes",
	"actions" => "Actions"
);

setStickyColumns($titles,"customerimports");

$formatters = array(
	"alert" => "fmt_alert",
	"5" => "fmt_updatemethod",
	"6" => "fmt_timestamp",
	"7" => "fmt_timestamp",
	"8" => "fmt_filesize",
	"actions" => "fmt_importalerts",
	"daysold" => "fmt_daysold",
);



/////////////////////////////
// Display
/////////////////////////////

include("nav.inc.php");

$displayname = getCustomerSystemSetting('displayname', false, true, $custdb);
startWindow(_L('Imports for: %s',$displayname));

// Show the column data hide/select check boxes.
show_column_selector('customer_imports_table', $titles,array(),"customerimports");
?>
<table class="list sortable" id="customer_imports_table">
<?
showTable($data, $titles, $formatters);

?>
</table>
<?// assign row ids for the row filter function?>
<script type="text/javascript">
	var table = $('customer_imports_table');
	var trows = table.rows;
	for (var i = 0, length = trows.length; i < length; i++) {
		trows[i].id = 'row'+i;
	}
</script>
<div> All time stamps are in customer time. </div>
<?
endWindow();


date_default_timezone_set("US/Pacific");
include("navbottom.inc.php");
?>
