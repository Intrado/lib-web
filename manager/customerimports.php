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
			return "<div style='background-color: #ffcccc'>" . fmt_date($row, $index) . "</div>";
		else
			return fmt_date($row, $index);
	}
}
//row index 13 contains an array of alert options
function fmt_last_modified($row, $index){
	date_default_timezone_set($row[2]);
	$timestamp = strtotime($row[$index]);
	$scheduledDow=array();
	if(isset($row[13]['dow'])){
		$scheduledDow = array_flip(explode(",", $row[13]['dow']));
	}

	if ($timestamp === false) {
		return "<div style='background-color: #ffcccc'>- Never -</div>";
	} else {
		if(isset($row[13]['daysold']) && $row[13]['daysold'] && ($timestamp < time() - 60*60*24* $row[13]['daysold'])){
			return "<div style='background-color: #ffcccc'>" . fmt_date($row, $index) . "</div>";
		} else if(isset($row[13]['dow'])){
			//if dow is set (schedule is set)
			//find the last weekday it should have run, including today.
			//if the last scheduled run is later than last run, display error
			$currentdow=date("w")+1;
			$daysago = 0;
			if(strtotime($row[13]['time']) > strtotime("now")){
				$currentdow--;
				$daysago++;
			}

			while(!isset($scheduledDow[$currentdow])){
				$daysago++;
				$currentdow--;
				if($currentdow < 1){
					$currentdow = $currentdow+7;
				}
			}
			//calculate unix time and allow 15 min leeway
			$scheduledlastrun = strtotime(" -$daysago days " . $row[13]['time']) - (60*15);
			if($scheduledlastrun > $timestamp){
				return "<div style='background-color: #ffcccc'>" . fmt_date($row, $index) . "</div>";
			}
		}

		return fmt_date($row, $index);
	}
}

function fmt_custurl($row, $index){
	$url = $row[1] . " (<a href=\"customerlink.php?id=". $row[0] . "\" >" . $row[3] . "</a>)";
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
	 if((isset($row[13]['minsize']) && $row[$index] < $row[13]['minsize']) || (isset($row[13]['maxsize']) && $row[$index] > $row[13]['maxsize']))
	 	return "<div style=\"background-color: #ffcccc; width:100%; text-align:right;\">" . number_format($row[$index]) . "</div>";
	 else if(!isset($row[13]['minsize']) && !isset($row[13]['maxsize']) && $row[$index] < 10)
	 	return "<div style=\"background-color: #ffcccc; width:100%; text-align:right;\">" . number_format($row[$index]) . "</div>";
	 else
	 	return "<div style=\"width:100%; text-align:right;\">" . number_format($row[$index]) . "</div>";
}


//index 0 is customer id
//index 3 is import id
function fmt_importalerts($row, $index){
	$url = "<a href='importalerts.php?cid=" . $row[0] . "&importid=" . $row[4] . "' title='Configure Alerts'><img src='img/s-config.png' border=0></a>";
	if(isset($row[13]['dow'])){
		$url = "*" . $url;
	}
	return $url;
}

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

if(isset($_GET['clear'])){
	unset($_SESSION['customerid']);
	redirect();
}

if(isset($_GET['customer'])){
	$_SESSION['customerid'] = $_GET['customer']+0;
	redirect();
}

if(isset($_SESSION['customerid'])){
	$customerID = $_SESSION['customerid'];
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
			$data[] = array_merge(array($cust[0], $displayname, $timezone, $cust[2]), $row);
		}
	}
}
$titles = array("0" => "ID",
		"url" => "Name",
		"4" => "Imp ID ",
		"5" => "Imp Name",
		"12" => "Description",
		"6" =>  "Status",
		"7" => "Type ",
		"8" => "Upd. Method",
		"2" => "TimeZone",
		"9" => "Last Run",
		"10" => "Last Modified",
		"11" => "File Size in Bytes",
		"actions" => "Alerts");
$formatters = array("url" => "fmt_custurl",
					"11" => "fmt_filesize",
					"9" => "fmt_alert_timestamp",
					"6" => "fmt_import_status",
					"10" => "fmt_last_modified",
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