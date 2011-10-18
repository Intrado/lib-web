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
$count=5000;
if($curfilename && !$errormsg){
	if($fp = @fopen($curfilename, "r")){
		while($row = fgetcsv($fp)){
			if($count > 0 && count($row) >= 2){
				//validate each item in the row.
				//match, prefix, and suffix should be numbers or empty string
				//strip must be a number
				//keep track of the original match so that different invalid entries are no combined
				$match = $row[0];
				if(preg_replace("/[^0-9]*/", "", $row[0]) == ""){
					$row[0] = "Invalid";
				} else {
					$row[0] = preg_replace("/[^0-9]*/", "", $row[0]);
					$match = $row[0];
				}
				if(!preg_match("/^[0-9]+$/", $row[1])){
					$row[1] = "Invalid";
				}
				if(isset($row[2]) && !preg_match("/^[0-9]*$/", $row[2])){
					$row[2] = "Invalid";
				}
				if(isset($row[3]) && !preg_match("/^[0-9]*$/", $row[3])){
					$row[3] = "Invalid";
				}
				if(!CheckFormSubmit($f, "save")){
					$count--;
				}
				if(!isset($row[2]))
					$row[2] = "";
				if(!isset($row[3]))
					$row[3] = "";
				$routes[$match] = $row;
			} else {
				$routes[$row[0]] = array("Invalid", "", "", "");
			}
		}
	} else {
		$errormsg = "Unable to open the file";
	}
}
$routechange = 0;
if(CheckFormSubmit($f, "save")  && !$errormsg){
	// CSV format is match, strip, prefix, suffix
	$newroutes = array();
	foreach($routes as $row){
		//validate each item in the row.
		//match, prefix, and suffix should be numbers
		//strip must be a number
		if(preg_replace("/[^0-9]*/", "", $row[0]) == ""){
			continue;
		} else {
			$row[0] = preg_replace("/[^0-9]*/", "", $row[0]);
		}
		if(!preg_match("/^[0-9]+$/", $row[1])){
			continue;
		}
		if(isset($row[2]) && !preg_match("/^[0-9]*$/", $row[2])){
			continue;
		}
		if(isset($row[3]) && !preg_match("/^[0-9]*$/", $row[3])){
			continue;
		}
		if(!isset($row[2]))
			$row[2] = "";
		if(!isset($row[3]))
			$row[3] = "";
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
			$newroutes[] = "('" . $dmid . "','" . implode("','", $row) . "')";
			$routechange = 1;
		}
	}
	if(count($newroutes)){
		QuickUpdate("insert into dmroute (dmid, `match`, strip, prefix, suffix) values " . implode(",", $newroutes));
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

function fmt_route($row, $index){
	if($row[$index] == "")
		return "Default";
	else
		return $row[$index];
}

$titles = array(0 => "Match",
				1 => "Strip",
				2 => "Prefix",
				3 => "Suffix");


NewForm($f);
$PAGE="admin:settings";
$TITLE="Upload Telco Settings Preview: " . escapehtml($dmname);
include("nav.inc.php");
buttons(submit($f, "save", "Save"), button("Select Different File", null, "uploadroutes.php"), button("Cancel", null, "dmsettings.php"));
startWindow("Routes Preview" . ($count <= 0 ? " - First 5000 Records" : ""));
?>
	<table cellpadding="3" cellspacing="1" class="list" width="100%">
<?
		showTable($routes, $titles, array("0" => "fmt_route"));
?>
	</table>
<?
endWindow();
buttons();
EndForm();
include("navbottom.inc.php");
?>