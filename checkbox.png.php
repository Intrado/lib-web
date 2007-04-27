<?
include_once("inc/common.inc.php");
include_once("inc/securityhelper.inc.php");
header ("Content-type: image/png");

$id = DBSafe($_GET['id']);
if ($id && $USER->authorize('createlist') && userOwns("list",$_SESSION['listid'])) {
	if ($_GET['type'] == "add") {
		if ($_GET['toggle'] == "true") {
			//insert into db
			$usersql = $USER->userSQL("p");
			$query = "select p.id
					from 		person p
								
					where p.id='$id' and (p.userid = 0 or
										p.userid = $USER->id or
										(1 $usersql))
				";
			if ($personid = QuickQuery($query)) {
				QuickUpdate("insert into listentry (listid,type,personid)"
							. "values ('" . $_SESSION['listid'] . "','A','$id')");
				readfile("img/checkbox-add.png");
			}
		} else {
			QuickUpdate("delete from listentry where listid='" . $_SESSION['listid']
						. "' and personid='$id'");
			readfile("img/checkbox-clear.png");
		}
	} else if ($_GET['type'] == "remove") {
		if ($_GET['toggle'] == "true") {
			//insert into db
			QuickUpdate("insert into listentry (listid,type,personid)"
						. "values ('" . $_SESSION['listid']
						. "','N','$id')");
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