<?
include_once("inc/common.inc.php");
include_once("inc/securityhelper.inc.php");
header ("Content-type: image/png");

//FIXME if this is a system person, then check the user's dataview rules.

$id = DBSafe($_GET['id']);
if ($id && $USER->authorize('createlist') && userOwns("list",$_SESSION['listid'])) {
	if ($_GET['type'] == "add") {
		if ($_GET['toggle'] == "true") {
			//insert into db
			$usersql = $USER->userSQL("p", "pd");
			$query = "select p.id
					from 		person p
								left join	persondata pd on
										(p.id=pd.personid)
					where p.id='$id' and $usersql
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