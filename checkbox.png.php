<?
include_once("inc/common.inc.php");
require_once("obj/PeopleList.obj.php");
include_once("inc/securityhelper.inc.php");
header ("Content-type: image/png");

$id = $_GET['id'] + 0;
if ($id && $USER->authorize('createlist') && userOwns("list",$_SESSION['listid'])) {
	if ($_GET['toggle'] == "true") {
		if (!userOwns('person', $id)) {
			error_log("user trying to add person they don't own");
			readfile("img/checkbox-clear.png");
			exit();
		}

		//make sure the person doesn't already exist in the list
		QuickUpdate("begin");
		
		$list = new PeopleList($_SESSION['listid']);
		$list->modifydate = date("Y-m-d H:i:s");
		$list->update(array("modifydate"));
		
		if(!QuickQuery("select count(*) from listentry where personid = " . $id . " and listid = " . $_SESSION['listid'])){
			QuickUpdate("insert into listentry (listid,type,personid)"
						. "values ('" . $_SESSION['listid'] . "','add','$id')");
		}
		QuickUpdate("commit");
		//if the person already exists in the list, keep displaying the checkbox
		readfile("img/checkbox-add.png");
	} else {
		QuickUpdate("begin");
		
		$list = new PeopleList($_SESSION['listid']);
		$list->modifydate = date("Y-m-d H:i:s");
		$list->update(array("modifydate"));
		
		QuickUpdate("delete from listentry where listid='" . $_SESSION['listid']
					. "' and personid='$id'");
		QuickUpdate("commit");
					
		readfile("img/checkbox-clear.png");
	}

}

?>