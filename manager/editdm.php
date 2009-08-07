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
include_once("../inc/html.inc.php");
$dmType = '';

if (!$MANAGERUSER->authorized("editdm") && !$MANAGERUSER->authorized("systemdm"))
	exit("Not Authorized");

if(isset($_GET['dmid'])){
	$dmid = $_GET['dmid']+0;
	$dmType = QuickQuery("select type from dm where id = " . $dmid);
	if(!QuickQuery("select count(*) from dm where id = " . $dmid) || 
			!(($MANAGERUSER->authorized("editdm") && $dmType == "customer") ||
			($MANAGERUSER->authorized("systemdm") && $dmType == "system"))){
		echo "Invalid DM, or not authorized to edit this DM.";
		exit();
	}
	$_SESSION['dmid'] = $dmid;
	redirect();
} else {
	$dmid = $_SESSION['dmid'];
	$dmType = QuickQuery("select type from dm where id = " . $dmid);
}

//Fetch dm settings from dmsettings table

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

		TrimFormData($f, $s, "customerid");
		TrimFormData($f, $s, "telco_inboundtoken");
		TrimFormData($f, $s, "telco_calls_sec");
		TrimFormData($f, $s, "delmech_resource_count");
		TrimFormData($f, $s, "testweightedresults");
		//do check

		if( CheckFormSection($f, $s) ) {
			error('There was a problem trying to save your changes', 'Please verify that all required field information has been entered properly');
		} else {
			$callerid = Phone::parse(GetFormData($f, $s, "telco_caller_id"));
			
			$ip_pattern = "^([0-9]{1,3}\\.[0-9]{1,3}\\.[0-9]{1,3}\\.[0-9]{1,3})$";
			$slaship_pattern = "^([0-9]{1,3}\\.[0-9]{1,3}\\.[0-9]{1,3}\\.[0-9]{1,3})/([0-9]{1,2})$";
			$netmask_pattern = "^([0-9]{1,3}\\.[0-9]{1,3}\\.[0-9]{1,3}\\.[0-9]{1,3}) ([0-9]{1,3}\\.[0-9]{1,3}\\.[0-9]{1,3}\\.[0-9]{1,3})$";
			
			$authorizedip = TrimFormData($f, $s, "authorizedip");
			$authorizedippatternok = ereg($ip_pattern,$authorizedip) || ereg($slaship_pattern,$authorizedip) || ereg($netmask_pattern,$authorizedip);

			if (!ereg("[0-9]{10}",$callerid)) {
				error('Bad Caller ID, Try Again');
			} else if ($dmType == 'customer' && GetFormData($f, $s, "customerid") && !QuickQuery("select count(*) from customer where id = " . GetFormData($f, $s, "customerid"))){
				error('Invalid Customer ID');
			} else if (GetFormData($f, $s, "telco_inboundtoken") > GetFormData($f, $s, "delmech_resource_count")){
				error('Number of inbound tokens cannot exceed the max number of resources');
			} else if(GetFormData($f, $s, 'telco_calls_sec') && !ereg("^[0-9]*\.?[0-9]*$", GetFormData($f, $s, 'telco_calls_sec'))){
				error("Calls per second must be a positive number");
			} else if (!$authorizedippatternok) {
				error("Authorized IP must be in one of the 3 listed formats");
			} else {
				QuickUpdate("Begin");

				$enablestate = $dm['enablestate'];

				if(CheckFormSubmit($f, "authorize")){
					QuickUpdate("update dm set enablestate = 'active' where id = " . $dmid);
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
							('" . DBSafe($dmid) . "', 'test_has_delays', '" . DBSafe(GetFormData($f, $s, 'test_has_delays')) . "'),
							('" . DBSafe($dmid) . "', 'testweightedresults','" . DBSafe(GetFormData($f, $s, 'testweightedresults'))."'),
							('" . DBSafe($dmid) . "', 'disable_congestion_throttle', '" . DBSafe(GetFormData($f, $s, 'disable_congestion_throttle')) . "')
							");
				$newcustomerid = GetFormData($f, $s, "customerid") +0;
				QuickUpdate("update dm set
							authorizedip = '" . DBSafe($authorizedip) . "',
							customerid = " . $newcustomerid . "
							where id = '" . DBSafe($dmid) . "'");
				if ($dmType == 'customer') {
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
									(" . $dmid . ", '" . DBSafe($dm['name']) . "', '" . DBSafe($enablestate) . "', '" . DBSafe(GetFormData($f, $s, 'telco_type')) . "')
									", $custdb);
					} else {
						QuickUpdate("update custdm set enablestate = '" . DBSafe($enablestate) . "',
									telco_type = '" . DBSafe(GetFormData($f, $s, 'telco_type')) . "'
									where dmid = " . $dmid, $custdb);
					}
				}

				QuickUpdate("commit");
				if ($dmType == 'customer')
					redirect("customerdms.php");
				else
					redirect("systemdms.php");
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
	
	PutFormData($f,$s,"authorizedip",$dm['authorizedip'],"text","7","31",true);
	
	PutFormData($f, $s, "telco_calls_sec", getDMSetting($dmid, "telco_calls_sec"), "text", "nomin", "nomax", true);
	PutFormData($f, $s, "delmech_resource_count", getDMSetting($dmid, "delmech_resource_count"), "number", "nomin", "nomax", true);

	PutFormData($f, $s, "telco_caller_id", Phone::format(getDMSetting($dmid, "telco_caller_id")), "phone", "10", "10", true);
	PutFormData($f, $s, "telco_inboundtoken", getDMSetting($dmid, "telco_inboundtoken"), "number", "nomin", "nomax", true);
	if ($dmType == 'customer')
		PutFormData($f, $s, "customerid", $dm['customerid'], "number", "1", "nomax", true);
	
	PutFormData($f, $s, "telco_type", getDMSetting($dmid, "telco_type"), "array", $telco_types, "nomax", true);
	PutFormData($f, $s, "dm_enabled", getDMSetting($dmid, "dm_enabled"), "bool", 0, 1);

	PutFormData($f, $s, "test_has_delays", getDMSetting($dmid, "test_has_delays"), "bool", 0, 1);

	// throttle capacity on trunkbusy call result
	PutFormData($f, $s, "disable_congestion_throttle", getDMSetting($dmid, "disable_congestion_throttle"), "bool", 0, 1);

	PutFormData($f, $s, "testweightedresults", getDMSetting($dmid, "testweightedresults"), "text");
}


function getDMSetting($dmid, $setting){
	return QuickQuery("select value from dmsetting where name = '" . $setting . "' and dmid = '" . $dmid . "'");
}

include_once("nav.inc.php");

//custom newform declaration to catch if manager password is submitted
NewForm($f);
?>
<div>Settings for <?=$dm['name']?></div>
<table>
<?

?>
	<tr>
		<td>Enable DM: </td>
		<td><? NewFormItem($f, $s, "dm_enabled", "checkbox"); ?></td>
	</tr>
<?
if ($dmType == 'customer') {?>
	<tr>
		<td>Customer ID: </td>
		<td><? NewFormItem($f, $s, "customerid", "text", "5"); ?></td>
	</tr>
<?}
?>
	<tr>
		<td>Authorized: </td>
		<td><?

		switch ($dm['enablestate']) {
		case "active":
			echo "Authorized";
			break;
		case "new":
			echo "New";
			break;
		case "disabled":
			echo "Unauthorized";
			break;
		}

		?></td>
	</tr>
	<tr>
		<td>Type: </td>
		<td>
			<?
				NewFormItem($f, $s, "telco_type", "selectstart", null, null, "id='telco_type' onchange='if(this.value==\"Test\"){ $(\"weightedresult1\").show(); $(\"weightedresult2\").show(); $(\"hasdelay1\").show(); $(\"hasdelay2\").show(); } else { $(\"weightedresult1\").hide(); $(\"weightedresult2\").hide(); $(\"hasdelay1\").hide(); $(\"hasdelay2\").hide(); }'" );
				foreach($telco_types as $telco_type){
					NewFormItem($f, $s, "telco_type", "selectoption", $telco_type, $telco_type);
				}
				NewFormItem($f, $s, "telco_type", "selectend");
			?>
		</td>
	</tr>
	<tr>
		<td><div id='weightedresult1' style='display:none'>Test Weighted Results:</span></td>
		<td><div id='weightedresult2' style='display:none'><?=NewFormItem($f, $s, "testweightedresults", "text", 30, 250)?>  A=3&M=3&B=2&N=2&X=1&F=1</span></td>
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
		<td><div id='hasdelay1' style='display:none'>Test Has Delays: </span></td>
		<td><div id='hasdelay2' style='display:none'><? NewFormItem($f, $s, "test_has_delays", "checkbox", null, null, "id='test_has_delays'"); ?></span></td>
	</tr>
	<tr>
		<td>Disable Congestion Throttle: </td>
		<td><? NewFormItem($f, $s, "disable_congestion_throttle", "checkbox", null, null, "id='disable_congestion_throttle'"); ?></td>
	</tr>

	<tr>
		<td>Last IP: </td>
		<td><?=$dm['lastip']?> <a href="#" onclick="var field = new getObj('authorizedip'); field.obj.value = '<?=$dm['lastip']?>'; return false;" >Copy to authorized ip</a></td>
	</tr>
	<tr>
		<td valign=top>Authorized IP:</td>
		<td><? NewFormItem($f,$s,"authorizedip","text",31,31,'id="authorizedip"'); ?> <br>
		<em>Accepts either single IP format (11.22.33.44)<br>
		network slash notation (11.22.33.0/24)<br> 
		or netmask notation (11.22.33.0 255.255.255.0)<br>
		</em>
		</td>
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
EndForm();
?>
<br>
<a href="dmdatfiles.php?dmid=<?=$_SESSION['dmid']?>">Dat File History</a>
<?
include_once("navbottom.inc.php");
?>
<script>
if(new getObj('telco_type').obj.value == 'Test'){
	show('weightedresult1');
	show('weightedresult2');
	show('hasdelay1');
	show('hasdelay2');
}


function getObj(name)
{
  if (document.getElementById)
  {
  	this.obj = document.getElementById(name);
  }
  else if (document.all)
  {
	this.obj = document.all[name];
  }
  else if (document.layers)
  {
   	this.obj = document.layers[name];
  }
  if(this.obj)
	this.style = this.obj.style;
}

function show(name)
{
	var x = new getObj(name);
	if (x.style)
		x.style.display = "block";
}

function hide(name)
{
	var x = new getObj(name);
	if (x.style)
		x.style.display =  "none";
}


</script>
