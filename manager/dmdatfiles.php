<?
include_once("common.inc.php");
include_once("../inc/form.inc.php");
include_once("../inc/html.inc.php");
include_once("../inc/table.inc.php");
include_once("../inc/formatters.inc.php");

if (!$MANAGERUSER->authorized("editdm"))
	exit("Not Authorized");

if(isset($_GET['dmid'])){
	$_SESSION['dmid'] = $_GET['dmid']+0;
	if(isset($_GET['version'])){
		$filedata = QuickQuery("select data from dmdatfile where dmid = " . $_SESSION['dmid'] . " and id = " . ($_GET['version']+0));
		header("Pragma: private");
		header("Cache-Control: private");
		header("Content-disposition: attachment; filename=datfile.dat");
		echo base64_decode($filedata);
		exit();
	}
	redirect();
}

if(!isset($_SESSION['dmid'])){
	echo "Error: no dmid found, please return to dm list page";
	exit();
}

list($dmname,$notes) = QuickQueryRow("select name,notes from dm where id=?",false,false,array($_SESSION['dmid']));

$res = Query("select id, uploaddate, notes, dmid from dmdatfile where dmid = " . $_SESSION['dmid'] . " order by id ASC");
$data= array();
$count=1;
while($row = DBGetRow($res)){
	$data[] = array_merge(array($count), $row);
	$count++;
}

$titles = array(0 => "Version",
				2 => "Upload Date",
				3 => "Notes",
				"actions" => "Actions");

$functions = array("actions" => "dm_actions",
					2 => "fmt_date");

//row 4 is dmid
//row 1 is dmdatfile id
function dm_actions($row, $index){
	$url = '<a href="dmdatfiles.php?dmid=' . $row[4] . '&version=' . $row[1] . '">Download</a>';
	return $url;
}


include_once("nav.inc.php");
?>
<div>Dat File History for: 
<table><tr><td>Name: </td><td><?=$dmname?></td></tr><tr><td>Notes: </td><td><?=$notes?></td></tr></table>
</div>
<table class="list">
<?
	showTable($data, $titles, $functions);
?>
</table>

<?
include_once("navbottom.inc.php");


?>