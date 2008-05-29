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


$telco_types = array("Test", "Asterisk", "Jtapi");
$dm = QuickQueryRow("select name, lastip, lastseen, customerid, enablestate, type, authorizedip, lastip from dm where id = '" . DBSafe($dmid) . "'", true);


$f = "dmedit";
$s = "main";
$reloadform = 0;
$refreshdm = false;

if(CheckFormSubmit($f,$s) || CheckFormSubmit($f, "authorize") || CheckFormSubmit($f, "unauthorize"))
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
			} else if (GetFormData($f, $s, "customerid") && !QuickQuery("select count(*) from customer where id = " . GetFormData($f, $s, "customerid"))){
				error('Invalid Customer ID');
			} else if (GetFormData($f, $s, "telco_inboundtoken") > GetFormData($f, $s, "delmech_resource_count")){
				error('Number of inbound tokens cannot exceed the max number of resources');
			} else if(GetFormData($f, $s, 'telco_calls_sec') && !ereg("^[0-9]+\.?[0-9]*$", GetFormData($f, $s, 'telco_calls_sec'))){
				error("Calls per second must be a positive number");
			} else {
				QuickUpdate("Begin");

				$enablestate = $dm['enablestate'];

				if(CheckFormSubmit($f, "authorize")){
					QuickUpdate("update dm set authorizedip = lastip, enablestate = 'active' where id = " . $dmid);
					$enablestate = "active";
				} else if(CheckFormSubmit($f, "unauthorize")){
					QuickUpdate("update dm set enablestate = 'disabled' where id = " . $dmid);
					$enablestate = "disabled";
				}

				$dialtimeout = getDMSetting($dmid, "telco_dial_timeout");
								if($dialtimeout == false){
									$dialtimeout = 45000;
				}

				QuickUpdate("delete from dmsetting where dmid = '" . DBSafe($dmid) . "'");
				QuickUpdate("insert into dmsetting (dmid, name, value) values
							('" . DBSafe($dmid) . "', 'telco_calls_sec', '" . DBSafe(GetFormData($f, $s, 'telco_calls_sec')) . "'),
							('" . DBSafe($dmid) . "', 'delmech_resource_count', '" . DBSafe(GetFormData($f, $s, 'delmech_resource_count')) . "'),
							('" . DBSafe($dmid) . "', 'telco_dial_timeout', '" . $dialtimeout . "'),
							('" . DBSafe($dmid) . "', 'telco_caller_id', '" . Phone::parse($callerid) . "'),
							('" . DBSafe($dmid) . "', 'telco_inboundtoken', '" . DBSafe(GetFormData($f, $s, 'telco_inboundtoken')) . "'),
							('" . DBSafe($dmid) . "', 'telco_type', '" . DBSafe(GetFormData($f, $s, 'telco_type')) . "'),
							('" . DBSafe($dmid) . "', 'dm_enabled', '" . DBSafe(GetFormData($f, $s, 'dm_enabled')) . "'),
							('" . DBSafe($dmid) . "', 'test_has_delays', '" . DBSafe(GetFormData($f, $s, 'test_has_delays')) . "')
							");
				$newcustomerid = GetFormData($f, $s, "customerid") +0;
				QuickUpdate("update dm set
							customerid = " . $newcustomerid . "
							where id = '" . DBSafe($dmid) . "'");

				if($dm['customerid'] != null && $newcustomerid != $dm['customerid']){
					$custinfo = QuickQueryRow("select s.dbhost, s.dbusername, s.dbpassword from shard s inner join customer c on (c.shardid = s.id)
																	where c.id = " . $dm['customerid']);
					$custdb = DBConnect($custinfo[0], $custinfo[1], $custinfo[2], "c_" . $dm['customerid']);
					if(QuickQuery("select count(*) from custdm where dmid = " . $dmid, $custdb)){
						QuickUpdate("delete from custdm where dmid = " . $dmid, $custdb);
					}
				}

				$custinfo = QuickQueryRow("select s.dbhost, s.dbusername, s.dbpassword from shard s inner join customer c on (c.shardid = s.id)
											where c.id = " . $newcustomerid);
				$custdb = DBConnect($custinfo[0], $custinfo[1], $custinfo[2], "c_" . $newcustomerid);
				if(!QuickQuery("select count(*) from custdm where dmid = " . $dmid, $custdb)){
					QuickUpdate("insert into custdm (dmid, name, enablestate, telco_type) values
								(" . $dmid . ", '" . $dm['name'] . "', '" . $dm['enablestate'] . "', '" . DBSafe(GetFormData($f, $s, 'telco_type')) . "')
								", $custdb);
				} else {
					QuickUpdate("update custdm set enablestate = '" . DBSafe($enablestate) . "',
								telco_type = '" . DBSafe(GetFormData($f, $s, 'telco_type')) . "'
								where dmid = " . $dmid, $custdb);
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
	PutFormData($f, "authorize", "Authorize", "");
	PutFormData($f, "unauthorize", "Un-authorize", "");
	PutFormData($f, $s, "managerpassword", "");
	PutFormData($f, $s, "telco_calls_sec", getDMSetting($dmid, "telco_calls_sec"), "text", "nomin", "nomax", true);
	PutFormData($f, $s, "delmech_resource_count", getDMSetting($dmid, "delmech_resource_count"), "number", "nomin", "nomax", true);

	PutFormData($f, $s, "telco_caller_id", Phone::format(getDMSetting($dmid, "telco_caller_id")), "phone", "10", "10", true);
	PutFormData($f, $s, "telco_inboundtoken", getDMSetting($dmid, "telco_inboundtoken"), "number", "nomin", "nomax", true);
	PutFormData($f, $s, "customerid", $dm['customerid'], "number", "1", "nomax", true);
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
		<td>Enable State: </td>
		<td><?=$dm['enablestate']?></td>
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
		<td>Caller ID:</td>
		<td><? NewFormItem($f, $s, "telco_caller_id", "text", "14");?></td>
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
		<td>Inbound Resources:</td>
		<td><? NewFormItem($f, $s, "telco_inboundtoken", "text", "5");?></td>
	</tr>
	<tr>
		<td>Test Has Delays: </td>
		<td><? NewFormItem($f, $s, "test_has_delays", "checkbox", null, null, "id='test_has_delays'"); ?></td>
	</tr>
	<tr>
		<td>Authorized IP:</td>
		<td><?=$dm['authorizedip']?></td>
	</tr>
	<tr>
		<td>Last IP: </td>
		<td><?=$dm['lastip']?></td>
	</tr>
	<tr>
		<td colspan="3">
<?
			NewFormItem($f, $s, "Submit", "submit");
			NewFormItem($f, "authorize", "Authorize", "submit");
			NewFormItem($f, "unauthorize", "Un-authorize", "submit");
?>
		</td>
	</tr>
</table>
<?
managerPassword($f, $s);
EndForm();
include_once("navbottom.inc.php");
?>