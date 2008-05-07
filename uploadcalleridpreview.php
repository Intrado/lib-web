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

if($curfilename && !$errormsg){
	if($fp = @fopen($curfilename, "r")){
		while($row = trim(fgets($fp))){
			$row = explode("=", $row);
			if(count($row) == 2){
				//validate each item in the row.
				//callerid, prefix
				$row[0] = Phone::parse($row[0]);
				if(!ereg("^[0-9]*$", $row[1])){
					continue;
				}
				$callerids[$row[0]] = $row;
			}
		}
	} else {
		$errormsg = "Unable to open the file";
	}
}
$routechange = 0;
if(CheckFormSubmit($f, "save")  && !$errormsg){
	// CSV format is match, strip, prefix, suffix
	foreach($callerids as $row){
		//validate each item in the row.
		//callerid, prefix
		if(!ereg("^[0-9]+$", $row[0])){
			continue;
		}
		if(!ereg("^[0-9]*$", $row[1])){
			continue;
		}
		if(isset($calleridroutes[$row[0]])){
			if($calleridroutes[$row[0]]->prefix != $row[1]){
				$routechange = 1;
			}
			$calleridroutes[$row[0]]->prefix = $row[1];
			$calleridroutes[$row[0]]->update();
		} else {
			$route = new DMCallerIDRoute();
			$route->dmid = $dmid;
			$route->callerid = $row[0];
			$route->prefix = $row[1];
			$route->create();
			$calleridroutes[$row[0]] = $route;
			$routechange = 1;
		}
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


NewForm($f);
$PAGE="admin:settings";
$TITLE="Upload Caller ID Routes Preview: " . $dmname;
include("nav.inc.php");
buttons(submit($f, "save", "Save"), button("Select Different File", null, "uploadcallerid.php"), button("Cancel", null, "calleridroute.php"));
startWindow("Routes Preview");
?>
	<table cellpadding="3" cellspacing="1" class="list" width="100%">
<?
		showTable($callerids, $titles, array(0 => "fmt_phone"));
?>
	</table>
<?
endWindow();
buttons();
EndForm();
include("navbottom.inc.php");
?>