<?
include_once("inc/common.inc.php");
include_once("inc/securityhelper.inc.php");
include_once("obj/UserSetting.obj.php");
header ("Content-type: image/png");

$toggle = $_GET['toggle'];
$field = $_GET['field'];

if($_GET['saved']== "false"){
	$usersetting = DBFind("UserSetting", "from usersetting where name ='" . DBSafe($field) . "' and userid = '$USER->id'");
	if($usersetting == null){
		$usersetting = new UserSetting();
		$usersetting->name = $field;
		$usersetting->userid = $USER->id;
	}
	if($toggle == "true"){
		$_SESSION['report']['fields'][$field] = true;
		$usersetting->value = "true";
		$usersetting->update();
		readfile("img/checkbox-rule.png");
	} else if ($toggle == "false"){
		$_SESSION['report']['fields'][$field] = false;
		$usersetting->value="false";
		$usersetting->update();
		readfile("img/checkbox-clear.png");
	}
} else {
	if($toggle == "true"){
		$_SESSION['report']['fields'][$field] = true;
		readfile("img/checkbox-rule.png");
	} else if ($toggle == "false"){
		$_SESSION['report']['fields'][$field] = false;
		readfile("img/checkbox-clear.png");
	}
}
?>