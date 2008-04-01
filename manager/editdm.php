<?
include_once("common.inc.php");
include_once("../inc/form.inc.php");
include_once("../inc/table.inc.php");
include_once("../obj/Phone.obj.php");
include_once("AspAdminUser.obj.php");
include_once("../inc/html.inc.php");


if(isset($_GET['dmid'])){
	$_SESSION['dmid'] = $dmid = $_GET['dmid'];
	redirect();
} else {
	$dmid = $_SESSION['dmid'];
}

//Fetch dm settings from dmsettings table

$accountcreator = new AspAdminUser($_SESSION['aspadminuserid']);

$states = array("new", "disabled", "active");
$types = array("customer", "system");
$dm = QuickQueryRow("select name, lastip, lastseen, customerid, enablestate, type from dm where dmuuid = '" . DBSafe($dmid) . "'", true);

$f = "dmedit";
$s = "main";
$reloadform = 0;

if(CheckFormSubmit($f,$s))
{
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
		} else {
			$callerid = Phone::parse(GetFormData($f, $s, "telco_caller_id"));

			if(!$accountcreator->runCheck(GetFormData($f, $s, "managerpassword"))) {
				error('Bad Manager Password');
			} else if (!ereg("[0-9]{10}",$callerid)) {
				error('Bad Caller ID, Try Again');
			} else {
				QuickUpdate("Begin");
				QuickUpdate("delete from dmsetting where dmuuid = '" . DBSafe($dmid) . "'");

				QuickUpdate("insert into dmsetting (dmuuid, name, value) values
							('" . DBSafe($dmid) . "', 'telco_calls_sec', '" . DBSafe(GetFormData($f, $s, 'telco_calls_sec')) . "'),
							('" . DBSafe($dmid) . "', 'delmech_resource_count', '" . DBSafe(GetFormData($f, $s, 'delmech_resource_count')) . "'),
							('" . DBSafe($dmid) . "', 'ast_channel', '" . DBSafe(GetFormData($f, $s, 'ast_channel')) . "'),
							('" . DBSafe($dmid) . "', 'telco_dial_timeout', '" . DBSafe(GetFormData($f, $s, 'telco_dial_timeout')) . "'),
							('" . DBSafe($dmid) . "', 'telco_caller_id', '" . Phone::parse($callerid) . "'),
							('" . DBSafe($dmid) . "', 'telco_inboundtoken', '" . DBSafe(GetFormData($f, $s, 'telco_inboundtoken')) . "')
							");
				$newcustomerid = GetFormData($f, $s, "customerid") +0;
				if($newcustomerid == 0){
					$newcustomerid = "null";
				}
				QuickUpdate("update dm set
							customerid = " . $newcustomerid . ",
							enablestate = '" . DBSafe(GetFormData($f, $s, "enablestate")) . "',
							type = '" . DBSafe(GetFormData($f, $s, "type")) . "'
							where dmuuid = '" . DBSafe($dmid) . "'");

				QuickUpdate("commit");
				redirect("customerdms.php");
			}
		}
	}
} else {
	$reloadform = 1;
}

if( $reloadform )
{
	ClearFormData($f);
	PutFormData($f, $s, "Submit", "");
	PutFormData($f, $s, "managerpassword", "");
	PutFormData($f, $s, "telco_calls_sec", getDMSetting($dmid, "telco_calls_sec"), "text", "nomin", "nomax", true);
	PutFormData($f, $s, "delmech_resource_count", getDMSetting($dmid, "delmech_resource_count"), "text", "nomin", "nomax", true);
	PutFormData($f, $s, "ast_channel", getDMSetting($dmid, "ast_channel"), "text", "nomin", "nomax", true);
	PutFormData($f, $s, "telco_dial_timeout", getDMSetting($dmid, "telco_dial_timeout"), "text", "nomin", "nomax", true);
	PutFormData($f, $s, "telco_caller_id", Phone::format(getDMSetting($dmid, "telco_caller_id")), "phone", "10", "10", true);
	PutFormData($f, $s, "telco_inboundtoken", getDMSetting($dmid, "telco_inboundtoken"), "text", "nomin", "nomax", true);
	PutFormData($f, $s, "enablestate", $dm['enablestate'], "array", $states, "nomax", true);
	PutFormData($f, $s, "customerid", $dm['customerid'], "number");
	PutFormData($f, $s, "type", $dm['type'], "array", $types, "nomax", true);
}


function getDMSetting($dmid, $setting){
	return QuickQuery("select value from dmsetting where name = '" . $setting . "' and dmuuid = '" . $dmid . "'");
}

include_once("nav.inc.php");

//custom newform declaration to catch if manager password is submitted
NewForm($f,"onSubmit='if(new getObj(\"managerpassword\").obj.value == \"\"){ window.alert(\"Enter Your Manager Password\"); return false;}'");
?>
<div>Settings for <?=$dm['name']?></div>
<table>
<?
//String DELMECH_ENABLED = "dm_enabled"; // remote
?>
	<tr>
		<td>Customer ID: </td>
		<td><? NewFormItem($f, $s, "customerid", "text", "5"); ?></td>
	</tr>
	<tr>
		<td>Type: </td>
		<td>
			<?
				NewFormItem($f, $s, "type", "selectstart");
				foreach($types as $type){
					NewFormItem($f, $s, "type", "selectoption", ucfirst($type), $type);
				}
				NewFormItem($f, $s, "type", "selectend");
			?>
		</td>
	</tr>
	<tr>
		<td>State: </td>
		<td>
			<?
				NewFormItem($f, $s, "enablestate", "selectstart");
				foreach($states as $state){
					NewFormItem($f, $s, "enablestate", "selectoption", ucfirst($state), $state);
				}
				NewFormItem($f, $s, "enablestate", "selectend");
			?>
		</td>
	</tr>
	<tr>
		<td>Calls per Second: </td>
		<td><? NewFormItem($f, $s, "telco_calls_sec", "text", "5");?></td>
	</tr>
	<tr>
		<td># of Resources:</td>
		<td><? NewFormItem($f, $s, "delmech_resource_count", "text", "5");?></td>
	</tr>
	<tr>
		<td>Asterisk Channel:</td>
		<td><? NewFormItem($f, $s, "ast_channel", "text", "30", "255");?></td>
	</tr>
	<tr>
		<td>Dial Timeout</td>
		<td><? NewFormItem($f, $s, "telco_dial_timeout", "text", "5");?></td>
	</tr>
	<tr>
		<td>Caller ID:</td>
		<td><? NewFormItem($f, $s, "telco_caller_id", "text", "14");?></td>
	</tr>
	<tr>
		<td>Inbound Resources:</td>
		<td><? NewFormItem($f, $s, "telco_inboundtoken", "text", "5");?></td>
	</tr>
<?
//String TEST_HAS_DELAYS = "test_delay"; //remote
?>
	<tr>
		<td><? NewFormItem($f, $s, "Submit", "submit"); ?></td>
	</tr>
</table>
<?
managerPassword($f, $s);
EndForm();
include_once("navbottom.inc.php");
?>