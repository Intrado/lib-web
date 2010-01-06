<?
include_once("inc/common.inc.php");
include_once("inc/securityhelper.inc.php");
header ("Content-type: image/png");

$id = $_GET['id'] + 0;
if ($id && $USER->authorize('createlist') && userOwns("list",$_SESSION['listid'])) {
	if ($_GET['type'] == "add") {
		if ($_GET['toggle'] == "true") {
			if (!userOwns('person', $id))
				exit();

			QuickUpdate("begin");
			//insert into db
			$usersql = $USER->userSQL("p");
			$query = "select p.id
					from 		person p

					where p.id='$id' and (p.userid = 0 or
										p.userid = $USER->id or
										(1 $usersql))
				";
			if ($personid = QuickQuery($query)) {
				//make sure the person doesn't already exist in the list
				if(!QuickQuery("select count(*) from listentry where personid = " . $id . " and listid = " . $_SESSION['listid'])){
					QuickUpdate("insert into listentry (listid,type,personid)"
								. "values ('" . $_SESSION['listid'] . "','add','$id')");
				}
				//if the person already exists in the list, keep displaying the checkbox
				readfile("img/checkbox-add.png");
			}
			QuickUpdate("commit");
		} else {
			QuickUpdate("delete from listentry where listid='" . $_SESSION['listid']
						. "' and personid='$id'");
			readfile("img/checkbox-clear.png");
		}
	} else if ($_GET['type'] == "remove") {
		if ($_GET['toggle'] == "true") {
			//insert into db
			QuickUpdate("begin");
			if(!QuickQuery("select count(*) from listentry where personid = " . $id . " and listid = " . $_SESSION['listid'])){
				QuickUpdate("insert into listentry (listid,type,personid)"
							. "values ('" . $_SESSION['listid']
							. "','negate','$id')");
			}
			QuickUpdate("commit");
			readfile("img/checkbox-remove.png");
		} else {
			QuickUpdate("delete from listentry where listid='" . $_SESSION['listid']
						. "' and personid='$id'");
			readfile("img/checkbox-rule.png");
		}
	} else {
		readfile("img/checkbox-clear.png");
	}
}

?>