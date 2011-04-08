<?
require_once("common.inc.php");
require_once("../inc/html.inc.php");
require_once("../inc/formatters.inc.php");
require_once("../inc/form.inc.php");
require_once("../inc/table.inc.php");

if (!$MANAGERUSER->authorized("imports"))
	exit("Not Authorized");

$staledataleewayhours = 1;
$defaultwindowminutes = 10;
define('SECONDSPERHOUR', 3600);
define('SECONDSPERMINUTE', 60);
define('HOURSPERDAY', 24);
define('SECONDSPERDAY', 86400);

////////////////////////////////////////////////////////////////////////////////
// formatters
////////////////////////////////////////////////////////////////////////////////

function fmt_custid($row, $index){
	global $favcustomers;

	if (isset($favcustomers[$row[$index]]))
		return "<img style='margin-right: 4px;' src='img/fav.png' border=0/>" . $row[$index];
	else
		return $row[$index];
}

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
	date_default_timezone_set($row[2]);
	$timestamp = strtotime($row[$index]);
	if ($timestamp === false) {
		return "<div style='background-color: #ff0000'>- Never -</div>";
	} else {
		if ($timestamp + ($staledataleewayhours * SECONDSPERHOUR) < strtotime($row[10]))
			return "<div style='background-color: #ff0000'>" . fmt_import_date($row, $index) . "</div>";
	}

	return fmt_import_date($row, $index);
}

//row index 14 contains an array of alert options
function fmt_last_modified($row, $index) {
	global $staledataleewayhours;
	global $defaultwindowminutes;
	date_default_timezone_set($row[2]);
	$timestamp = strtotime($row[$index]);
	if ($timestamp === false) {
		return "<div style='background-color: #ff0000'>- Never -</div>";
	} else {
		// Stale Data
		if (isset($row[14]['daysold']) && $row[14]['daysold']) {
			$timediffallowed = ($row[14]['daysold'] * HOURSPERDAY * SECONDSPERHOUR) + ($staledataleewayhours * SECONDSPERHOUR);
			$timediff = time() - $timestamp;
			if ($timediff > $timediffallowed)
				return "<div style='background-color: #ffcccc'>" . fmt_import_date($row, $index) . "</div>";
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
						return "<div style='background-color: #ffcccc'>" . fmt_import_date($row, $index) . "</div>";
				}
			}
		}

		return fmt_import_date($row, $index);
	}
}

function fmt_custurl($row, $index){
	$url = $row[1] . " (<a href=\"customerlink.php?id=". $row[0] . "\" target=\"_blank\">" . $row[3] . "</a>)";
	return $url;
}

function fmt_import_status($row, $index){

	if($row[$index] == 'error')
		return "<div style=\"background-color: red;\">" . $row[$index] . "</div>";
	else
		return $row[$index];
}


//row index 12 contains an array of alert options
function fmt_filesize($row, $index){
	 if((isset($row[14]['minsize']) && $row[$index] < $row[14]['minsize']) || (isset($row[14]['maxsize']) && $row[$index] > $row[14]['maxsize']))
	 	return "<div style=\"background-color: #FFCCCC; width:100%; text-align:right;\">" . number_format($row[$index]) . "</div>";
	 else if(!isset($row[14]['minsize']) && !isset($row[14]['maxsize']) && $row[$index] < 10 && $row[$index] > 0)
	 	return "<div style=\"background-color: #FFFF00; width:100%; text-align:right;\">" . number_format($row[$index]) . "</div>";
	 else if($row[$index] == 0)
	 	return "<div style=\"background-color: #FF0000; width:100%; text-align:right;\">" . number_format($row[$index]) . "</div>";
	 else
	 	return "<div style=\"width:100%; text-align:right;\">" . number_format($row[$index]) . "</div>";
}


//index 0 is customer id
//index 3 is import id
function fmt_importalerts($row, $index){
	$url = '<a href="importalerts.php?cid=' . $row[0] . '&importid=' . $row[4] . '" title="Configure Alerts"><img src="img/s-config.png" border=0></a>';
	if(isset($row[14]) && $row[14] != null && $row[14] != ""){
		$url = '<div style="background-color: #3cff00">' . $url . "</div>";
	}
	return $url;
}

// row index 14 is the alert options. The whole mess is in a text field.
function fmt_alert_email($row, $index) {
	if (isset($row[$index]['emails']) && $row[$index]['emails']) {
		return str_replace(";", "\n", $row[$index]['emails']);
	} else {
		return "";
	}
}

function fmt_daysold($row, $index) {
	if (isset($row[10]) && $row[10]) {
		return intval((strtotime(date("Y-m-d G:i:s")) - strtotime($row[10])) / SECONDSPERDAY);
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
}

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

$f = "form";
$s = "imports";
$reloadform = 0;
$queryextra = "";
$querytypes = "";
$alerttxt = "";
$custtxt = "";

if (CheckFormSubmit($f, $s)) {
	//check to see if formdata is valid
	if (CheckFormInvalid($f)) {
		error('Form was edited in another window, reloading data');
		$reloadform = 1;
	} else {
		MergeSectionFormData($f, $s);
		if ( CheckFormSection($f, $s) ) {
			?><div>An error occurred somehow </div><?
		}
	}
} else {
	$reloadform = 1;
}

if ($reloadform) {
	ClearFormData($f);
}

// FAVORITES
// Favorite customers are indexed by customer ID.
if ($MANAGERUSER->preference("favcustomers")) {
	$favcustomers = array_flip($MANAGERUSER->preference("favcustomers"));
}

if (isset($_GET['clear'])) {
	unset($_SESSION['customerid']);
	redirect();
}

if (isset($_GET['customer'])) {
	$_SESSION['customerid'] = $_GET['customer']+0;
	redirect();
}

if (isset($_GET['cid'])) {
	if ($_GET['cid']) {
		$queryextra = " AND id in (";
		foreach (explode(",", $_GET['cid']) as $cid)
			$queryextra .= "'". DBSafe($cid) . "',";

		$queryextra = substr($queryextra, 0, -1) . ") ";
	}
} else if (!empty($favcustomers) && !isset($_GET['showall']) && !isset($_POST['showmatch'])) {
	$queryextra = " AND id in (";
	foreach ($favcustomers as $cid => $junk)
		$queryextra .= "'". DBSafe($cid) . "',";

	$queryextra = substr($queryextra, 0, -1) . ") ";
}

if (isset($_POST['showmatch'])) {
	if (isset($_POST['alerttxt']) && trim($_POST['alerttxt'])) {
		$alerttxt = escapehtml(trim($_POST['alerttxt']));
		// email addresses are urlencoded in the options i.e. @ => %40. DBSafe will not escape the % so escape it to make it work with mysql like
		$querytypes = " and alertoptions like '%" . str_replace("%", "\%",DBSafe(urlencode(trim($_POST['alerttxt'])))) . "%' ";
	}
	if (isset($_POST['custtxt']) && trim($_POST['custtxt'])) {
		$custtxt = escapehtml(trim($_POST['custtxt']));
		$queryextra = " and urlcomponent like '%" . DBSafe(trim($_POST['custtxt'])) . "%'";
	}
}

if (isset($_SESSION['customerid'])) {
	$customerID = $_SESSION['customerid'];
	$queryextra = " AND ID='$customerID' ";
} else {
	$queryextra .= " and enabled ";
}

$res = Query("select id, dbhost, dbusername, dbpassword from shard order by id");
$shardinfo = array();
while ($row = DBGetRow($res)) {
	$shardinfo[$row[0]] = array($row[1], $row[2], $row[3]);
}
$custquery = Query("select id, shardid, urlcomponent from customer where 1 $queryextra order by shardid, id");
$customers = array();
while ($row= DBGetRow($custquery)) {
	$customers[] = $row;
}

$currhost="";
$data = array();
foreach ($customers as $cust) {

	if ($currhost != $cust[1]) {
		$dsn = 'mysql:dbname=c_'.$cust[0].';host='.$shardinfo[$cust[1]][0];
		$custdb = new PDO($dsn, $shardinfo[$cust[1]][1], $shardinfo[$cust[1]][2]);
		$custdb->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
		$currhost = $cust[1];
	}
	Query("use c_" . $cust[0], $custdb);

	if ($custdb) {
		$query = "SELECT id, name, status, type, updatemethod, lastrun, datamodifiedtime, length(data), description, notes, alertoptions, datatype
					FROM import
					where type in ('automatic', 'manual') and ownertype = 'system'
					$querytypes
					order by id";
		$list = Query($query, $custdb);
		$timezone = getCustomerSystemSetting('timezone', false, true, $custdb);
		$displayname = getCustomerSystemSetting('displayname', false, true, $custdb);
		while($row = DBGetRow($list)){
			$alertoptions = sane_parsestr($row[10]);
			$row[10] = $alertoptions;
			$data[] = array_merge(array($cust[0], $displayname, $timezone, $cust[2]), $row);
		}
	}
}

$titles = array("0" => "#ID",
		"alert" => "#Alert",
		"url" => "#Cust Name",
		"4" => "@#Imp ID ",
		"5" => "#Imp Name",
		"12" => "@#Description",
		"13" => "@#Notes",
		"6" =>  "@#Status",
		"7" => "@#Type",
		"15" => "@#Data Type",
		"8" => "@#Upd. Method",
		"2" => "#TimeZone",
		"9" => "#Last Run",
		"10" => "#Last Modified",
		"daysold" => "#Days Old",
		"11" => "#File Size in Bytes",
		"14" => "@#Alert Email",
		"actions" => "Actions");

$formatters = array("0" => "fmt_custid",
					"url" => "fmt_custurl",
					"11" => "fmt_filesize",
					"9" => "fmt_alert_timestamp",
					"6" => "fmt_import_status",
					"10" => "fmt_last_modified",
					"14" => "fmt_alert_email",
					"actions" => "fmt_importalerts",
					"daysold" => "fmt_daysold",
					"8" => "fmt_updatemethod",
					"alert" => "fmt_alert");

// Do not provide a checkbox to hide these columns.
$lockedTitles = array(0, "actions", "url", 5);

// only format these fields when filtering
$filterFormatters = array("8" => "fmt_updatemethod",
					"alert" => "fmt_alert");

// allow these fields for filtering
$filterTitles = array("alert", 6, 7, 15, 8, 2);

/////////////////////////////
// Display
/////////////////////////////

include("nav.inc.php");

?>
<form method="POST" action="customerimports.php">
<table>
	<tr>
		<td valign="top">
			<table border="0" cellpadding="2" cellspacing="1" class="list">
				<tr class="listHeader" align="left" valign="bottom">
					<td>
						Search (can match partial urls/emails)
					</td>
				</tr>
				<tr>
					<td valign="top">
						<table>
							<tr>
								<td valign="top" align="left">
									Cust URL:
								</td>
								<td>
									<input type="text" name="custtxt" id="custtxt" value="<?=$custtxt?>" size="20" maxlength="50" />
								</td>
							</tr>
							<tr>
								<td valign="top" align="left">
									Alert Emails:
								</td>
								<td>
									<input type="text" name="alerttxt" id="alerttxt" value="<?=$alerttxt?>" size="20" maxlength="50" />
								</td>
							</tr>
							<tr>
								<td colspan="2">
									<input type="submit" name="showmatch" id="showmatch" value="Search" />
								</td>
							</tr>
						</table>
					</td>
				</tr>
			</table>
		</td>
		<td valign="top">
			<?
			// show the row data filters
			show_row_filter('customer_imports_table', $data, $titles, $filterTitles, $filterFormatters);

			?>
		</td>
	</tr>
</table>
<?
if ((!isset($_GET["showall"]) && !empty($favcustomers)) && !isset($_GET["search"]))
	print "<a href='customerimports.php?showall'>Show All Customers</a>";
else
	print "<a href='customerimports.php'> <img src='img/fav.png' border=0/>Show Favorites</a>";
?>
</form>
<?

// Show the column data hide/select check boxes.
show_column_selector('customer_imports_table', $titles, $lockedTitles);

?>
<table class="list sortable" id="customer_imports_table">
<?
showTable($data, $titles, $formatters);

?>
</table>

<?// assign row ids for the row filter function?>
<script language="javascript">
	var table = new getObj('customer_imports_table').obj;
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
date_default_timezone_set("US/Pacific");
include("navbottom.inc.php");
?>
