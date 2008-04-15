<?
/*
	DM Settings Manager
	List of settings:
		// remote properties
		 String dmType; // Test, Asterisk, Jtapi
		 boolean dmEnabled;
		 int resourceCount;
		 int inboundCount;
		 int callsPerSecond;
		 String callerID;

		// test voice
		 boolean testDelay; // remote

		// these are in jtapi.props, why are they here?
		 String jtapi2cmIP; // (phase2) remote - write to jtapi config and restart
		 String jtapi2cmUser; // remote - write to jtapi config and restart
		 String jtapi2cmPass; // remote - write to jtapi config and restart



*/

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
$telco_types = array("Test", "Asterisk", "Jtapi");
$dm = QuickQueryRow("select name, lastip, lastseen, customerid, enablestate, type, authorizedip from dm where id = '" . DBSafe($dmid) . "'", true);

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
			} else if (GetformData($f, $s, "customerid") === 0){
				error('Invalid Customer ID');
			} else if (GetFormData($f, $s, "customerid") && !QuickQuery("select count(*) from customer where id = " . GetFormData($f, $s, "customerid"))){
				error('Invalid Customer ID');
			} else if (GetFormData($f, $s, "telco_inboundtoken") > GetFormData($f, $s, "delmech_resource_count")){
				error('Number of inbound tokens cannot exceed the max number of resources');
			} else {
				QuickUpdate("Begin");
				QuickUpdate("delete from dmsetting where dmid = '" . DBSafe($dmid) . "'");

				QuickUpdate("insert into dmsetting (dmid, name, value) values
							('" . DBSafe($dmid) . "', 'telco_calls_sec', '" . DBSafe(GetFormData($f, $s, 'telco_calls_sec')) . "'),
							('" . DBSafe($dmid) . "', 'delmech_resource_count', '" . DBSafe(GetFormData($f, $s, 'delmech_resource_count')) . "'),
							('" . DBSafe($dmid) . "', 'telco_dial_timeout', '" . DBSafe(GetFormData($f, $s, 'telco_dial_timeout')) . "'),
							('" . DBSafe($dmid) . "', 'telco_caller_id', '" . Phone::parse($callerid) . "'),
							('" . DBSafe($dmid) . "', 'telco_inboundtoken', '" . DBSafe(GetFormData($f, $s, 'telco_inboundtoken')) . "'),
							('" . DBSafe($dmid) . "', 'telco_type', '" . DBSafe(GetFormData($f, $s, 'telco_type')) . "'),
							('" . DBSafe($dmid) . "', 'dm_enabled', '" . DBSafe(GetFormData($f, $s, 'dm_enabled')) . "'),
							('" . DBSafe($dmid) . "', 'test_has_delays', '" . DBSafe(GetFormData($f, $s, 'test_has_delays')) . "')
							");
				$newcustomerid = GetFormData($f, $s, "customerid") +0;
				if($newcustomerid == 0){
					$newcustomerid = "null";
				}
				QuickUpdate("update dm set
							customerid = " . $newcustomerid . ",
							enablestate = '" . DBSafe(GetFormData($f, $s, "enablestate")) . "'
							where id = '" . DBSafe($dmid) . "'");
				if(!$dm['authorizedip'] && GetFormData($f, $s, 'enablestate') == "active"){
					QuickUpdate("update dm set authorizedip = '" . $dm['lastip'] . "'");
				}

				if($dm['customerid'] != null && $newcustomerid != $dm['customerid']){
					$custinfo = QuickQueryRow("select s.dbhost, s.dbusername, s.dbpassword from shard s inner join customer c on (c.shardid = s.id)
																	where c.id = " . $dm['customerid']);
					$custdb = DBConnect($custinfo[0], $custinfo[1], $custinfo[2], "c_" . $dm['customerid']);
					if(QuickQuery("select count(*) from custdm where dmid = " . $dmid, $custdb)){
						QuickUpdate("delete from custdm where dmid = " . $dmid, $custdb);
					}
				}

				if($newcustomerid != "null"){


					$custinfo = QuickQueryRow("select s.dbhost, s.dbusername, s.dbpassword from shard s inner join customer c on (c.shardid = s.id)
												where c.id = " . $newcustomerid);
					$custdb = DBConnect($custinfo[0], $custinfo[1], $custinfo[2], "c_" . $newcustomerid);
					if(!QuickQuery("select count(*) from custdm where dmid = " . $dmid, $custdb)){
						QuickUpdate("insert into custdm (dmid, name, enablestate) values
									(" . $dmid . ", '" . $dm['name'] . "', '" . $dm['enablestate'] . "')
									", $custdb);
					} else {
						QuickUpdate("update custdm set enablestate = '" . DBSafe(GetformData($f, $s, 'enablestate')) . "'
									where dmid = " . $dmid, $custdb);
					}

				}

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

	PutFormData($f, $s, "telco_dial_timeout", getDMSetting($dmid, "telco_dial_timeout"), "text", "nomin", "nomax", true);
	PutFormData($f, $s, "telco_caller_id", Phone::format(getDMSetting($dmid, "telco_caller_id")), "phone", "10", "10", true);
	PutFormData($f, $s, "telco_inboundtoken", getDMSetting($dmid, "telco_inboundtoken"), "text", "nomin", "nomax", true);
	PutFormData($f, $s, "enablestate", $dm['enablestate'], "array", $states, "nomax", true);
	PutFormData($f, $s, "customerid", $dm['customerid'], "number");
	PutFormData($f, $s, "telco_type", getDMSetting($dmid, "telco_type"), "array", $telco_types, "nomax", true);
	PutFormData($f, $s, "dm_enabled", getDMSetting($dmid, "dm_enabled"), "bool", 0, 1);

	PutFormData($f, $s, "test_has_delays", getDMSetting($dmid, "test_has_delays"), "bool", 0, 1);

}


function getDMSetting($dmid, $setting){
	return QuickQuery("select value from dmsetting where name = '" . $setting . "' and dmid = '" . $dmid . "'");
}

include_once("nav.inc.php");

//custom newform declaration to catch if manager password is submitted
NewForm($f,"onSubmit='if(new getObj(\"managerpassword\").obj.value == \"\"){ window.alert(\"Enter Your Manager Password\"); return false;}'");
?>
<div>Settings for <?=$dm['name']?></div>
<table>
<?

?>
	<tr>
		<td>Enable DM: </td>
		<td><? NewFormItem($f, $s, "dm_enabled", "checkbox"); ?></td>
	</tr>

	<tr>
		<td>Customer ID: </td>
		<td><? NewFormItem($f, $s, "customerid", "text", "5"); ?></td>
	</tr>
	<tr>
		<td>Type: </td>
		<td>
			<?
				NewFormItem($f, $s, "telco_type", "selectstart");
				foreach($telco_types as $telco_type){
					NewFormItem($f, $s, "telco_type", "selectoption", $telco_type, $telco_type);
				}
				NewFormItem($f, $s, "telco_type", "selectend");
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
		<td>Caller ID:</td>
		<td><? NewFormItem($f, $s, "telco_caller_id", "text", "14");?></td>
	</tr>
	<tr>
		<td>Calls per Second: </td>
		<td><? NewFormItem($f, $s, "telco_calls_sec", "text", "5");?></td>
	</tr>
	<tr>
		<td>Dial Timeout</td>
		<td><? NewFormItem($f, $s, "telco_dial_timeout", "text", "5");?></td>
	</tr>
	<tr>
		<td># of Resources:</td>
		<td><? NewFormItem($f, $s, "delmech_resource_count", "text", "5");?></td>
	</tr>
	<tr>
		<td>Inbound Resources:</td>
		<td><? NewFormItem($f, $s, "telco_inboundtoken", "text", "5");?></td>
	</tr>
	<tr>
		<td>Test Has Delays: </td>
		<td><? NewFormItem($f, $s, "test_has_delays", "checkbox", null, null, "id='test_has_delays'"); ?></td>
	</tr>
	<tr>
		<td><? NewFormItem($f, $s, "Submit", "submit"); ?></td>
	</tr>
</table>
<?
managerPassword($f, $s);
EndForm();
include_once("navbottom.inc.php");
?>