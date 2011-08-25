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
	if(isset($_GET['download'])){
		$filedata = QuickQuery("select data from dmdatfile where dmid=? and id =?",false,array($_SESSION['dmid'],$_GET['download']));
		header("Pragma: private");
		header("Cache-Control: private");
		header("Content-disposition: attachment; filename=datfile.dat");
		echo base64_decode($filedata);
		exit();
	} else if(isset($_GET['delete'])) {
		QuickUpdate("update dmdatfile set deleted = 1 where dmid=? and id =?",false,array($_SESSION['dmid'],$_GET['delete']));
	} else if(isset($_GET['undelete'])) {
		QuickUpdate("update dmdatfile set deleted = 0 where dmid=? and id =?",false,array($_SESSION['dmid'],$_GET['undelete']));
	}
	redirect();
}

if(!isset($_SESSION['dmid'])){
	echo "Error: no dmid found, please return to dm list page";
	exit();
}

list($dmname,$notes) = QuickQueryRow("select name,notes from dm where id=?",false,false,array($_SESSION['dmid']));

$extrasql = "";
if(!isset($_GET['showall'])){
	$extrasql =  " and deleted = 0";
}
$res = Query("select id, uploaddate, notes, dmid, deleted from dmdatfile where dmid=? $extrasql order by id ASC",false,array($_SESSION['dmid']));
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
//row 5 is deleted 
function dm_actions($row, $index){
	$url = '<a href="dmdatfiles.php?dmid=' . $row[4] . '&download=' . $row[1] . '">Download</a>';
	if ($row[5]) {
		$url .= ',<a href="dmdatfiles.php?dmid=' . $row[4] . '&undelete=' . $row[1] . '">Undelete</a>';
	} else {
		$url .= ',<a href="dmdatfiles.php?dmid=' . $row[4] . '&delete=' . $row[1] . '">Delete</a>';
	}
	return $url;
}

include_once("nav.inc.php");
?>
<div>Dat File History for: 
<table><tr><td>Name: </td><td><?=$dmname?></td></tr><tr><td>Notes: </td><td><?=$notes?></td></tr></table>
</div>
<hr />
<? if (isset($_GET['showall'])) {
	echo 'All Dat files: <a href="dmdatfiles.php">Show Undeleted Only</a>';
} else {
	echo 'Dat files: <a href="dmdatfiles.php?showall">Show All</a>';
}
?>
<table class="list">
<?
	showTable($data, $titles, $functions);
?>
</table>

<?
include_once("navbottom.inc.php");


?>