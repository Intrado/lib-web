<?
include_once("inc/common.inc.php");
include_once("inc/table.inc.php");
include_once("inc/html.inc.php");
include_once("inc/form.inc.php");
include_once("obj/Phone.obj.php");
include_once("inc/formatters.inc.php");
include_once("obj/DMCallerIDRoute.obj.php");

if (!$USER->authorize('managesystem')) {
	redirect('unauthorized.php');
}

$errormsg = false;
$dmname = "";
$curfilename = "";

if(isset($_SESSION['dmid'], $_SESSION['calleridrouteuploadfiles'][$_SESSION['dmid']]) ){
	$dmid = $_SESSION['dmid'];
	$dmname = QuickQuery("select name from custdm where dmid = " . $dmid);
	$curfilename = $_SESSION['calleridrouteuploadfiles'][$_SESSION['dmid']];
} else {
	$errormsg = "Please upload a file";
}

$calleridroutes = DBFindMany("DMCallerIDRoute", "from dmcalleridroute where dmid = " . $dmid);
$calleridroutes = resequence($calleridroutes, "callerid");
$callerids = array();

$f="uploadcalleridpreview";
$s="main";
$reloadform = 0;

$count=5000;
if($curfilename && !$errormsg){
	if($fp = @fopen($curfilename, "r")){
		while($row = fgetcsv($fp)){
			if($count > 0){
				if(count($row) == 2){
					//validate each item in the row.
					//callerid, prefix
					$callerid = Phone::parse($row[0]);
					if(strlen($callerid) != 10){
						$row[0]= "Invalid";
					} else {
						$row[0] = $callerid;
					}
					if(!preg_match("/^[0-9]*$/", $row[1])){
						$row[1]= "Invalid";
					}
					if(!CheckFormSubmit($f, "save")){
						$count--;
					}
					$callerids[$callerid] = $row;
				} else {
					$callerids[] = array("Invalid", "");
				}
			}
		}
	} else {
		$errormsg = "Unable to open the file";
	}
}
$routechange = 0;
if(CheckFormSubmit($f, "save")  && !$errormsg){
	// CSV format is match, strip, prefix, suffix
	$newcalleridroutes = array();
	foreach($callerids as $row){
		//validate each item in the row.
		//callerid, prefix
		$row[0] = Phone::parse($row[0]);
		if(strlen($row[0]) != 10){
			continue;
		}
		if(!preg_match("/^[0-9]*$/", $row[1])){
			continue;
		}
		if(isset($calleridroutes[$row[0]])){
			if($calleridroutes[$row[0]]->prefix != $row[1]){
				$routechange = 1;
			}
			$calleridroutes[$row[0]]->prefix = $row[1];
			$calleridroutes[$row[0]]->update();
		} else {
			$newcalleridroutes[] = "('" . $dmid . "','" . implode("','", $row) . "')";
			$routechange = 1;
		}
	}
	if(count($newcalleridroutes)){
		QuickUpdate("insert into dmcalleridroute (dmid, callerid, prefix) values " . implode(",",$newcalleridroutes));
	}
	if($routechange){
		QuickUpdate("update custdm set routechange=1 where dmid = " . $dmid);
	}
	redirect("calleridroute.php");
} else {
	$reloadform=1;
}


if( $reloadform )
{
	ClearFormData($f);
}

if ($errormsg)
	error($errormsg);


$titles = array(0 => "Caller ID",
				1 => "Prefix");

//custom formatter to detect if its a phone number or not
function fmt_callerid_upload($row, $index){
	if(Phone::parse($row[$index])){
		return fmt_phone($row, $index);
	} else {
		return $row[$index];
	}
}


NewForm($f);
$PAGE="admin:settings";
$TITLE="Upload Caller ID Routes Preview: " . escapehtml($dmname);
include("nav.inc.php");
buttons(submit($f, "save", "Save"), button("Select Different File", null, "uploadcallerid.php"), button("Cancel", null, "calleridroute.php"));
startWindow("Routes Preview" . ($count <= 0 ? " - First 5000 Records" : ""));
?>
	<table cellpadding="3" cellspacing="1" class="list" width="100%">
<?
		showTable($callerids, $titles, array(0 => "fmt_callerid_upload"));
?>
	</table>
<?
endWindow();
buttons();
EndForm();
include("navbottom.inc.php");
?>