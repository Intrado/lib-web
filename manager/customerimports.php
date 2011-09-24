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


$staledataleewayhours = 1;
$defaultwindowminutes = 10;
define('SECONDSPERHOUR', 3600);
define('SECONDSPERMINUTE', 60);
define('HOURSPERDAY', 24);
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

function fmt_alert_timestamp($row, $index) {
	global $staledataleewayhours;
	$timestamp = strtotime($row[$index]);
	if ($timestamp === false) {
		return "<div style='background-color: #ff0000'>- Never -</div>";
	} else {
		if ($timestamp + ($staledataleewayhours * SECONDSPERHOUR) < strtotime($row[6]))
			return "<div style='background-color: #ff0000'>" . fmt_import_date($row, $index) . "</div>";
	}

	return fmt_import_date($row, $index);
}

function fmt_last_modified($row, $index) {
	global $staledataleewayhours;
	global $defaultwindowminutes;
	$timestamp = strtotime($row[$index]);
	if ($timestamp === false) {
		return "<div style='background-color: #ff0000'>- Never -</div>";
	} else {
		return fmt_import_date($row, $index);
	}
}


function fmt_import_status($row, $index){

	if($row[$index] == 'error')
		return "<div style=\"background-color: red;\">" . $row[$index] . "</div>";
	else
		return $row[$index];
}


function fmt_filesize($row, $index){
	if($row[$index] == 0)
	 	return "<div style=\"background-color: #FF0000; width:100%; text-align:right;\">" . number_format($row[$index]) . "</div>";
	else
	 	return "<div style=\"width:100%; text-align:right;\">" . number_format($row[$index]) . "</div>";
}


//index 0 is customer id
//index 3 is import id
function fmt_importalerts($row, $index){
	global $customerid;
	$actions = array();
	$actions[] = action_link("Manager Alert", "eye",'importalertrules.php?cid=' . $customerid . '&importid=' . $row[0] . '&categoryid=1');
	$actions[] = action_link("Customer Alert", "transmit_error",'importalertrules.php?cid=' . $customerid . '&importid=' . $row[0] . '&categoryid=2');
	$actions[] = action_link("Edit", "pencil",'editimport.php?cid=' . $customerid . '&importid=' . $row[0] . '');
	
	return action_links($actions);
}


function fmt_daysold($row, $index) {
	if (isset($row[6]) && $row[6]) {
		return intval((strtotime(date("Y-m-d G:i:s")) - strtotime($row[6])) / SECONDSPERDAY);
	} else {
		return '<div style="background-color: #ff0000">99999</div>';
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
	/*
	global $staledataleewayhours;
	global $defaultwindowminutes;
	$timestamp = strtotime($row[10]);
	if (isset($row[14]['daysold']) && $row[14]['daysold']) {
		$timediffallowed = ($row[14]['daysold'] * HOURSPERDAY * SECONDSPERHOUR) + ($staledataleewayhours * SECONDSPERHOUR);
		$timediff = time() - $timestamp;
		if ($timediff > $timediffallowed)
			return "Alert";
	// Scheduled Days
	} else if(isset($row[14]['dow'])) {
		if (!isset($row[14]['scheduledwindowminutes']))
			$row[14]['scheduledwindowminutes'] = $defaultwindowminutes;
		$daytocheck =  date('w', time() - ($row[14]['scheduledwindowminutes']*SECONDSPERMINUTE));

		// ['dow'] is a string of possibly more than one days
		if (strpos($row[14]['dow'], $daytocheck) !== false) {
			$timestampforscheduledday = strtotime($row[14]['time'] . ":00 " . date("F d, Y", time() - ($row[14]['scheduledwindowminutes']*SECONDSPERMINUTE)));
			$lowerbound = $timestampforscheduledday - ($row[14]['scheduledwindowminutes']*SECONDSPERMINUTE);
			// Check for the alert only if the current time is past the scheduled time window
			if ($lowerbound <= time() - 2*($row[14]['scheduledwindowminutes']*SECONDSPERMINUTE)) {
				$diffuploadtime = $timestamp - $lowerbound;
				if ($diffuploadtime < 0 || $diffuploadtime > 2*($row[14]['scheduledwindowminutes']*SECONDSPERMINUTE))
					return "Alert";
			}
		}
	} else if (strtotime($row[9]) + ($staledataleewayhours * SECONDSPERHOUR) < $timestamp) {
		return "Alert";
	} else if ((isset($row[14]['minsize']) && $row[11] < $row[14]['minsize']) || (isset($row[14]['maxsize']) && $row[11] > $row[14]['maxsize'])) {
		return "Alert";
	} else if(!isset($row[14]['minsize']) && !isset($row[14]['maxsize']) && $row[11] < 10 && $row[11] > 0) {
		return "Alert";
	} else if($row[11] == 0) {
		return "Alert";
	} else {
		return "None";
	}
	*/
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

$currhost="";
$data = array();

$query = "SELECT id, name,description, status, type, updatemethod,datamodifiedtime,lastrun,datalength,datatype,notes, nsticketid,managernotes 
			FROM import
			where type in ('automatic', 'manual') and ownertype = 'system'
			order by id";
$data = QuickQueryMultiRow($query,false,$custdb);
$timezone = getCustomerSystemSetting('timezone', false, true, $custdb);
date_default_timezone_set($timezone);


$titles = array(
	"alert" => "#Alert",
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
	"11" => "NSTicketid",
	"12" => "Manager Notes",
	"actions" => "Actions"
);

setStickyColumns($titles,"customerimports");


$formatters = array(
	"alert" => "fmt_alert",
	"3" => "fmt_import_status",
	"5" => "fmt_updatemethod",
	"6" => "fmt_last_modified",
	"7" => "fmt_alert_timestamp",
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

<div> Automatic jobs have the "Import when uploaded" checkbox checked, manual jobs do not.  Both are from imports page.</div>
<div> All time stamps are in customer time. </div>
<table class="list">
	<tr>
		<th align="left" class="listheader">&nbsp;</th>
		<th align="left" class="listheader">Last Run</th>
		<th align="left" class="listheader">File Date</th>
		<th align="left" class="listheader">File Size</th>
		<th align="left" class="listheader">Days Old</th>
	</tr>
	<tr>
		<th class="listheader">No Alert</th>
		<td><span style="background-color: #FFFF00">Yellow</span> if older than Last Modified</td>
		<td><span style="background-color: #FFFF00"></span></td>
		<td><span style="background-color: #FFFF00">Yellow</span> if data size less than 10 bytes</td>
		<td><span style="background-color: #FFFF00"></span></td>
	</tr>
	<tr>
		<th class="listheader">With Alerts</th>
		<td><span style="background-color: #FFCCCC">Light Red</span> based on import alert</td>
		<td><span style="background-color: #FFCCCC">Light Red</span> based on import alert</td>
		<td><span style="background-color: #FFCCCC">Light Red</span> based on import alert</td>
		<td><span style="background-color: #FFCCCC">Light Red</span> based on import alert</td>
	</tr>
	<tr>
		<th class="listheader"></th>
		<td><span style="background-color: #FF0000">Red</span> if older than File Date</td>
		<td><span style="background-color: #FF0000">Red</span> if does not exist</td>
		<td><span style="background-color: #FF0000">Red</span> if does not exist</td>
		<td><span style="background-color: #FF0000">Red</span> if does not exist</td>
	</tr>
</table>
<?
endWindow();


date_default_timezone_set("US/Pacific");
include("navbottom.inc.php");
?>
