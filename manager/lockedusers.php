<?
include_once("common.inc.php");
include_once("../inc/table.inc.php");
include_once("../inc/form.inc.php");
include_once("../inc/html.inc.php");
include_once("AspAdminUser.obj.php");

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

$accountcreator = new AspAdminUser($_SESSION['aspadminuserid']);

$lockedusers = array();

$result = Query("select customerid, login, ipaddress, attempts, lastattempt, status from loginattempt");
while($row = DBGetRow($result)){
	$lockedusers[] = $row;
}

$titles = array("0" => "Customer ID",
				"1" => "Login",
				"2" => "IP Address",
				"3" => "Attempts",
				"4" => "Last Attempt",
				"5" => "Status",
				"Actions" => "Actions");

$formatters = array("4" => "fmt_last_attempt",
					"Actions" => "lockeduser_actions");

$f="lockedusers";
$s="main";
$reloadform = 0;
$submitteduser = null;

$checkformsubmit = false;
foreach($lockedusers as $lockeduser){
	if(CheckFormSubmit($f, $lockeduser[1])){
		$checkformsubmit = true;
		$submitteduser = $lockeduser;
		break;
	}
}

if($checkformsubmit){
	//check to see if formdata is valid
	if(CheckFormInvalid($f))
	{
		error('Form was edited in another window, reloading data');
		$reloadform = 1;
	}
	else
	{
		MergeSectionFormData($f, $s);

		//do check

		if( CheckFormSection($f, $s) ) {
			error('There was a problem trying to save your changes', 'Please verify that all required field information has been entered properly');
		} else if(!$accountcreator->runCheck(GetFormData($f, $s, 'managerpassword'))) {
			error('Bad Manager Password');
		} else {
			$customerinfo = QuickQueryRow("select s.dbhost, s.dbusername, s.dbpassword from shard s inner join customer c on (c.shardid = s.id) where c.id = $submitteduser[0]");
			$cust_db = DBConnect($customerinfo[0], $customerinfo[1], $customerinfo[2],"c_" . $submitteduser[0]);
			if($submitteduser[5] == "disabled"){
				QuickUpdate("update loginattempt set status = 'enabled', attempts = 0 where login = '" . $submitteduser[1] . "'");
				QuickUpdate("update user set enabled = 1 where login = '" . $submitteduser[1] . "'", $cust_db);
			} else if($submitteduser[5] == "lockout"){
				QuickUpdate("update loginattempt set status = 'enabled', attempts = 0 where login = '" . $submitteduser[1] . "'");
			}
			redirect();
		}
	}
} else {
	$reloadform = 1;
}

if($reloadform){
	ClearFormData($f);
	foreach($lockedusers as $lockeduser){
		if($lockeduser[5] == "disabled"){
			$actions = PutFormData($f,$lockeduser[1], "Enable");
		}
		if($lockeduser[5] == "lockout"){
			$actions = PutFormData($f,$lockeduser[1], "Unlock");
		}
	}
	PutFormData($f, $s, "managerpassword", "", "text");
}


////////////////////////////////////////////////////////////////////////////////
// Functions
////////////////////////////////////////////////////////////////////////////////

function fmt_last_attempt($row, $index){
	if(strtotime($row[$index]))
		return date("M d, Y h:i:s", strtotime($row[$index]));
	else
		return $row[$index];
}

//index 5 is status
function lockeduser_actions($row, $index){
	global $f, $s;
	$actions = "";
	if($row[5] == "disabled"){
		$actions = NewFormItem($f,$row[1], "Enable", "submit");
	}
	if($row[5] == "lockout"){
		$actions = NewFormItem($f,$row[1], "Unlock", "submit");
	}
	return $actions;
}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
include("nav.inc.php");

NewForm($f, "onSubmit='if(new getObj(\"managerpassword\").obj.value == \"\"){ window.alert(\"Enter Your Manager Password\"); return false;}'");
?>
<table border = 1>
<?
showTable($lockedusers, $titles, $formatters);
?>
</table>

<?
managerPassword($f, $s);
EndForm($f);
include("navbottom.inc.php");
?>