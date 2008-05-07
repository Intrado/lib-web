<?
include_once("inc/common.inc.php");
include_once("inc/table.inc.php");
include_once("inc/html.inc.php");
include_once("inc/form.inc.php");
include_once("obj/DMRoute.obj.php");

if (!$USER->authorize('managesystem')) {
	redirect('unauthorized.php');
}

$errormsg = false;
$dmname = "";
$curfilename = "";

if(isset($_SESSION['dmid'], $_SESSION['routeuploadfiles'][$_SESSION['dmid']]) ){
	$dmid = $_SESSION['dmid'];
	$dmname = QuickQuery("select name from custdm where dmid = " . $dmid);
	$curfilename = $_SESSION['routeuploadfiles'][$_SESSION['dmid']];
} else {
	$errormsg = "Please upload a file";
}

$dmroutes = DBFindMany("DMRoute", "from dmroute where dmid = " . $dmid);
$dmroutes = resequence($dmroutes, "match");
$routes = array();

$f="uploadroutepreview";
$s="main";
$reloadform = 0;

if($curfilename && !$errormsg){
	if($fp = @fopen($curfilename, "r")){
		while($row = fgetcsv($fp)){
			if(count($row) == 4){
				//validate each item in the row.
				//match, prefix, and suffix should be numbers or empty string
				//strip must be a number
				if(!ereg("^[0-9]*$", $row[0])){
					continue;
				}
				if(!ereg("^[0-9]+$", $row[1])){
					continue;
				}
				if(!ereg("^[0-9]*$", $row[2])){
					continue;
				}
				if(!ereg("^[0-9]*$", $row[3])){
					continue;
				}
				$routes[$row[0]] = $row;
			}
		}
	} else {
		$errormsg = "Unable to open the file";
	}
}
$routechange = 0;
if(CheckFormSubmit($f, "save")  && !$errormsg){
	// CSV format is match, strip, prefix, suffix
	foreach($routes as $row){
		//validate each item in the row.
		//match, prefix, and suffix should be numbers or empty string
		//strip must be a number
		if(!ereg("^[0-9]*$", $row[0])){
			continue;
		}
		if(!ereg("^[0-9]+$", $row[1])){
			continue;
		}
		if(!ereg("^[0-9]*$", $row[2])){
			continue;
		}
		if(!ereg("^[0-9]*$", $row[3])){
			continue;
		}
		if(isset($dmroutes[$row[0]])){
			if($dmroutes[$row[0]]->strip != $row[1]
				|| $dmroutes[$row[0]]->prefix != $row[2]
				|| $dmroutes[$row[0]]->suffix != $row[3]){
					$routechange = 1;
				}
			$dmroutes[$row[0]]->strip = $row[1];
			$dmroutes[$row[0]]->prefix = $row[2];
			$dmroutes[$row[0]]->suffix = $row[3];
			$dmroutes[$row[0]]->update();
		} else {
			$route = new DMRoute();
			$route->dmid = $dmid;
			$route->match = $row[0];
			$route->strip = $row[1];
			$route->prefix = $row[2];
			$route->suffix = $row[3];
			$route->create();
			$dmroutes[$row[0]] = $route;
			$routechange = 1;
		}
	}
	if($routechange){
		QuickUpdate("update custdm set routechange=1 where dmid = " . $dmid);
	}
	redirect("dmsettings.php");
} else {
	$reloadform=1;
}


if( $reloadform )
{
	ClearFormData($f);
}

if ($errormsg)
	error($errormsg);


$titles = array(0 => "Match",
				1 => "Strip",
				2 => "Prefix",
				3 => "Suffix");


NewForm($f);
$PAGE="admin:settings";
$TITLE="Upload Telco Settings Preview: " . $dmname;
include("nav.inc.php");
buttons(submit($f, "save", "Save"), button("Select Different File", null, "uploadroutes.php"), button("Cancel", null, "dmsettings.php"));
startWindow("Routes Preview");
?>
	<table cellpadding="3" cellspacing="1" class="list" width="100%">
<?
		showTable($routes, $titles, array());
?>
	</table>
<?
endWindow();
buttons();
EndForm();
include("navbottom.inc.php");
?>