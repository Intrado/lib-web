<?
include_once("common.inc.php");
include_once("../inc/formatters.inc.php");
include_once("../inc/form.inc.php");
include_once("../inc/table.inc.php");

////////////////////////////////////////////////////////////////////////////////
// formatters
////////////////////////////////////////////////////////////////////////////////

function fmt_alert_timestamp($row, $index) {
	date_default_timezone_set($row[2]);
	$timestamp = strtotime($row[$index]);
	if ($timestamp === false) {
		return "<div style='background-color: #ffcccc'>- Never -</div>";
	} else {
		if ($timestamp < time() - 60 * 60 * 24 * 3)
			return "<div style='background-color: #ffcccc'>" . date("M j, g:i a", $timestamp) . "</div>";
		else
			return date("M j, g:i a", $timestamp);
	}
}
//row index 12 contains an array of alert options
function fmt_last_modified($row, $index){
	date_default_timezone_set($row[2]);
	$timestamp = strtotime($row[$index]);
	$scheduledDow=array();
	if(isset($row[12]['dow'])){
		$scheduledDow = array_flip(explode(",", $row[12]['dow']));
	}

	if ($timestamp === false) {
		return "<div style='background-color: #ffcccc'>- Never -</div>";
	} else {
		if(isset($row[12]['daysold']) && $row[12]['daysold'] && ($timestamp < time() - 60*60*24* $row[12]['daysold'])
			|| (isset($row[12]['dow']) && !isset($scheduledDow[date("w", $timestamp)+1]))
			|| (isset($row[12]['time']) && (strtotime($row[12]['time'])+60*15 < strtotime(date("H:i", $timestamp)) || strtotime($row[12]['time'])-60*15 > strtotime(date("H:i", $timestamp))))
			)
				return "<div style='background-color: #ffcccc'>" . date("M j, g:i a", $timestamp) . "</div>";
		else
			return date("M j, g:i a", $timestamp);
	}
}

function fmt_custurl($row, $index){
	$url = "<a href=\"customerlink.php?id=". $row[0] . "\" >" . $row[1] . "</a>";
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
	 if((isset($row[12]['minsize']) && $row[$index] < $row[12]['minsize']) || (isset($row[12]['maxsize']) && $row[$index] > $row[12]['maxsize']))
	 	return "<div style=\"background-color: #ffcccc; width:100%; text-align:right;\">" . number_format($row[$index]) . "</div>";
	 else if(!isset($row[12]['minsize']) && !isset($row[12]['maxsize']) && $row[$index] < 10)
	 	return "<div style=\"background-color: #ffcccc; width:100%; text-align:right;\">" . number_format($row[$index]) . "</div>";
	 else
	 	return "<div style=\"width:100%; text-align:right;\">" . number_format($row[$index]) . "</div>";
}


//index 0 is customer id
//index 3 is import id
function fmt_importalerts($row, $index){
	$url = "<a href='importalerts.php?cid=" . $row[0] . "&importid=" . $row[3] . "'>Edit Alerts</a>";
	return $url;
}

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////


if(isset($_GET['customer'])){
	$customerID = $_GET['customer']+0;
	$queryextra = "AND ID='$customerID'";
} else {
	$queryextra=" and enabled";
}


$f = "form";
$s = "imports";
$reloadform = 0;
$types = array("automatic" => "automatic", "manual" => "manual", "list" => "list", "addressbook" => "addressbook");
$selected = array("automatic", "manual");
$querytypes = "";


if(CheckFormSubmit($f, $s)) {
	//check to see if formdata is valid
	if(CheckFormInvalid($f)) {
		error('Form was edited in another window, reloading data');
		$reloadform = 1;
	} else {
		MergeSectionFormData($f, $s);
		if( CheckFormSection($f, $s) ){
			?><div>An error occurred somehow </div><?
		} else {
			$selected = GetFormData($f, $s, 'importtypes');
		}
	}
} else {
	$reloadform = 1;
}
if($reloadform){
	ClearFormData($f);
	PutFormData($f, $s, 'importtypes', $selected, "array", array_keys($selected));
	PutFormData($f, $s, "submit", "");
}




$res = Query("select id, dbhost, dbusername, dbpassword from shard order by id");
$shardinfo = array();
while($row = DBGetRow($res)){
	$shardinfo[$row[0]] = array($row[1], $row[2], $row[3]);
}
$custquery = Query("select id, shardid, urlcomponent from customer where 1 $queryextra order by shardid, id");
$customers = array();
while($row= DBGetRow($custquery)){
	$customers[] = $row;
}

if($selected){
	$querytypes = "and import.type in ('" . implode("','", $selected) . "')";
}

$currhost="";
$data = array();
foreach($customers as $cust) {

	if($currhost != $cust[1]){
		$custdb = mysql_connect($shardinfo[$cust[1]][0], $shardinfo[$cust[1]][1],$shardinfo[$cust[1]][2])
			or die("Could not connect to customer database: " . mysql_error());
		$currhost = $cust[1];
	}
	mysql_select_db("c_" . $cust[0]);
	if($custdb){
		$query = "SELECT id, name, status, type, updatemethod, lastrun, datamodifiedtime, length(data), description, alertoptions
					FROM import
					where 1
					$querytypes
					order by id";
		$list = Query($query, $custdb);
		$timezone = getCustomerSystemSetting('timezone', false, true, $custdb);
		$displayname = getCustomerSystemSetting('displayname', false, true, $custdb);
		while($row = DBGetRow($list)){
			$alertoptions = sane_parsestr($row[9]);
			$row[9] = $alertoptions;
			$data[] = array_merge(array($cust[0], $displayname, $timezone), $row);
		}
	}
}
$titles = array("0" => "Customer ID",
		"1" => "Customer Name",
		"url" => "Customer URL",
		"3" => "Import ID ",
		"4" => "Import Name",
		"11" => "Description",
		"5" =>  "Status",
		"6" => "Type ",
		"7" => "Upd. Method",
		"2" => "TimeZone",
		"8" => "Last Run",
		"9" => "Last Modified",
		"10" => "File Size in Bytes",
		"actions" => "Actions");
$formatters = array("url" => "fmt_custurl",
					"10" => "fmt_filesize",
					"8" => "fmt_alert_timestamp",
					"5" => "fmt_import_status",
					"9" => "fmt_last_modified",
					"actions" => "fmt_importalerts");

/////////////////////////////
// Display
/////////////////////////////

include("nav.inc.php");
NewForm($f);
?>
<table>
	<tr><td>
		<?
			NewFormItem($f, $s, 'importtypes', 'selectmultiple', "4", $types);
		?>
	</td></tr>
	<tr><td><? NewFormItem($f, $s, 'submit', 'submit'); ?></td><tr>
</table>
<?
EndForm();

?>
<table border=1>
<?
showTable($data, $titles, $formatters);
?>
</table>
<div> Automatic jobs have the "Import when uploaded" checkbox checked, manual jobs do not.  Both are from imports page.<div>
<div > All time stamps are in customer time. </div>
<div>Red cells indicate import or file dates that are more than 3 days old</div>

<?
date_default_timezone_set("US/Pacific");
include("navbottom.inc.php");
?>