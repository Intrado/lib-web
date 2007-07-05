<?
include_once("inc/common.inc.php");
include_once("inc/securityhelper.inc.php");
include_once("obj/UserSetting.obj.php");
header ("Content-type: image/png");

$toggle = $_REQUEST['toggle'];
$field = $_REQUEST['field'];

if(!$_REQUEST['saved']){
	$usersetting = DBFind("UserSetting", "from usersetting where name ='" . DBSafe($field) . "' and userid = '$USER->id'");
	if($usersetting == null){
		$usersetting = new UserSetting();
		$usersetting->name = DBSafe($field);
		$usersetting->userid = $USER->id;
	}
	if($toggle == "true"){
		$_SESSION['fields'][$field] = true;
		$usersetting->value = "true";
		$usersetting->update();
		readfile("img/checkbox-check.png");
	} else if ($toggle == "false"){
		$_SESSION['fields'][$field] = false;
		$usersetting->value="false";
		$usersetting->update();
		readfile("img/checkbox-clear.png");
	}
} else {
	if($toggle == "true"){
		$_SESSION['fields'][$field] = true;
		readfile("img/checkbox-check.png");
	} else if ($toggle == "false"){
		$_SESSION['fields'][$field] = false;
		readfile("img/checkbox-clear.png");
	}
}
?>