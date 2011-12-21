<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("common.inc.php");
require_once("../inc/table.inc.php");
require_once("../inc/html.inc.php");
require_once("../inc/utils.inc.php");
require_once("../obj/Validator.obj.php");
require_once("../obj/Form.obj.php");
require_once("../obj/FormItem.obj.php");
require_once("../obj/Phone.obj.php");
////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////

$dmType = '';

if (!$MANAGERUSER->authorized("editdm") && !$MANAGERUSER->authorized("systemdm"))
	exit("Not Authorized");

if(isset($_GET['dmid'])){
	$dmid = $_GET['dmid'] +0;
	$dmType = QuickQuery("select type from dm where id=?",false,array($dmid));
	if(!QuickQuery("select count(*) from dm where id = ?",false,array($dmid)) || 
			!(($MANAGERUSER->authorized("editdm") && $dmType == "customer") ||
			($MANAGERUSER->authorized("systemdm") && $dmType == "system"))){
		echo "Invalid DM, or not authorized to edit this DM.";
		exit();
	}
	$_SESSION['dmid'] = $dmid;
	redirect();
} else {
	if (!isset($_SESSION['dmid'])) {
		exit("Not Authorized");
	}
	$dmid = $_SESSION['dmid'];
	$dmType = QuickQuery("select type from dm where id=?",false,array($dmid));
}

////////////////////////////////////////////////////////////////////////////////
// Action/Request Processing
////////////////////////////////////////////////////////////////////////////////

////////////////////////////////////////////////////////////////////////////////
// Custom Form Items and Validators
////////////////////////////////////////////////////////////////////////////////
class TestWeightField extends FormItem {
	function render ($value) {
		$n = $this->form->name."_".$this->name;
		return '<input id="'.$n.'" name="'.$n.'" type="text" value="'.escapehtml($value).'" /> ' . 
		"Example: A=3&M=3&B=2&N=2&X=1&F=1" .
		' <a href="#" onclick="$(\'editdm_testweightedresults\').value=\'3&M=3&B=2&N=2&X=1&F=1\';
			form_do_validation($(\'editdm\'), $(\'editdm_testweightedresults\')); return false;">Copy example test weight</a>';
	}
}

class ValCustomerId extends Validator {
	var $onlyserverside = true;
	function validate ($value, $args) {
		if ($args["dmtype"] == 'customer' && !QuickQuery("select count(*) from customer where id = ?",false,array($value))){
			return _L("There is no customer this id");
		}
		return true;
	}
}

class ValIp extends Validator {
	var $onlyserverside = true;
	function validate ($value) {
		$ip_pattern = "/^[0-9]{1,3}\\.[0-9]{1,3}\\.[0-9]{1,3}\\.[0-9]{1,3}$/";
		$slaship_pattern = "/^[0-9]{1,3}\\.[0-9]{1,3}\\.[0-9]{1,3}\\.[0-9]{1,3}\/[0-9]{1,2}$/";
		$netmask_pattern = "/^[0-9]{1,3}\\.[0-9]{1,3}\\.[0-9]{1,3}\\.[0-9]{1,3} [0-9]{1,3}\\.[0-9]{1,3}\\.[0-9]{1,3}\\.[0-9]{1,3}$/";
			
		$authorizedippatternok = preg_match($ip_pattern,$value) || preg_match($slaship_pattern,$value) || preg_match($netmask_pattern,$value);
		if (!$authorizedippatternok) {
			return _L("Accepts either single IP format (11.22.33.44)<br />\n
						network slash notation (11.22.33.0/24)<br />\n
						or netmask notation (11.22.33.0 255.255.255.0)");
		}
		return true;
	}
}

class ValInbound extends Validator {
	var $onlyserverside = true;
	function validate ($value, $args, $requiredvalues) {
		if ($requiredvalues[$args['field']] < $value)
			return "$this->label must be less or equal to the number of resources.";
		return true;
	}
}

////////////////////////////////////////////////////////////////////////////////
// Form Data
////////////////////////////////////////////////////////////////////////////////

// DM settings defaults 
$dmsettings = array(
	"dm_enabled" => 0,
	"telco_type" => "asterisk",
	"test_has_delays" => '',
	"testweightedresults" => 'A=3&M=3&B=2&N=2&X=1&F=1',
	"telco_caller_id" => '',
	"telco_calls_sec" => '',
	"delmech_resource_count" => '',
	"telco_inboundtoken" => '',
	"telco_dial_timeout" => false,
	"disable_congestion_throttle" => ''

);
$dmsettings = array_merge($dmsettings,QuickQueryList("select name,value from dmsetting where dmid=?",true,false,array($dmid)));
$dminfo = QuickQueryRow("select name,dmgroupid, lastip, lastseen, customerid, enablestate, type, authorizedip, lastip,routetype, notes from dm where id=?", true,false,array($dmid));

$helpstepnum = 1;

$helpsteps = array("TODO: Enable and Authorized");
$formdata["enabled"] = array(
	"label" => _L('Enabled'),
	"value" => $dmsettings["dm_enabled"],
	"validators" => array(),
	"control" => array("CheckBox"),
	"helpstep" => $helpstepnum
);

$enablestates = array("new" => "New", "active" => "Authorized","disabled" => "Unathorized","deleted" => "Deleted");
if ($dminfo["enablestate"] != "new")
	unset($enablestates["new"]);

$formdata["authorized"] = array(
	"label" => _L('State'),
	"value" => $dminfo["enablestate"],
	"validators" => array(
		array("ValRequired"),
		array("ValInArray", "values" => array_keys($enablestates))
	),
	"control" => array("SelectMenu", "values" => $enablestates),
	"helpstep" => $helpstepnum
);
if ($dmType == 'customer') {
	$helpstepnum++;
	$helpsteps[] = "TODO: Customerid";
	$formdata["customerid"] = array(
		"label" => _L('Customer ID'),
		"value" => $dminfo['customerid'],
		"validators" => array(
			array("ValRequired"),
			array("ValNumber"),
			array("ValCustomerId", "dmtype" => $dmType)
		),
		"control" => array("TextField","size" => 5, "maxlength" => 51),
		"helpstep" => $helpstepnum
	);
}
$helpstepnum++;
$helpsteps[] = "TODO: DM Testmode ";
$types = array("Test" => "Test", "Asterisk" => "Asterisk");
$formdata["type"] = array(
	"label" => _L('Type'),
	"value" => $dmsettings['telco_type'],
	"validators" => array(
		array("ValRequired"),
		array("ValInArray", "values" => array_keys($types))
	),
	"control" => array("SelectMenu", "values" => $types),
	"helpstep" => $helpstepnum
);
$formdata["testhasdelays"] = array(
	"label" => _L('Test Has Delays'),
	"value" => $dmsettings['test_has_delays'],
	"validators" => array(),
	"control" => array("CheckBox"),
	"helpstep" => $helpstepnum
);
$formdata["testweightedresults"] = array(
	"label" => _L('Test Weighted Results'),
	"value" => $dmsettings['testweightedresults'],
	"validators" => array(),
	"control" => array("TestWeightField"),
	"helpstep" => $helpstepnum
);

if ($dmType == 'system') {
	$dmgroups = QuickQueryList("select id, concat(carrier,' - ',state) from dmgroup",true);
	$formdata["dmgroup"] = array(
		"label" => _L('DM Group'),
		"value" => $dminfo["dmgroupid"],
		"validators" => array(
			array("ValInArray", "values" => array_keys($dmgroups))
		),
		"control" => array("SelectMenu", "values" => array('' => "None") + $dmgroups),
		"helpstep" => $helpstepnum
	);
	$routetypes = array("firstcall" => "Firstcall","lastcall" => "Lastcall","" => "Other");
	$formdata["routetype"] = array(
			"label" => _L('Route Type'),
			"value" => $dminfo["routetype"],
			"validators" => array(
				array("ValInArray", "values" => array_keys($routetypes))
			),
			"control" => array("SelectMenu", "values" => $routetypes),
			"helpstep" => $helpstepnum
	);
}
$helpstepnum++;
$helpsteps[] = "TODO: Callerid";
$formdata["callerid"] = array(
	"label" => _L('Caller ID'),
	"value" => $dmsettings['telco_caller_id'],
	"validators" => array(
		array("ValRequired"),
		array("ValPhone")
	),
	"control" => array("TextField","size" => 15, "maxlength" => 20),
	"helpstep" => $helpstepnum
);
$helpstepnum++;
$helpsteps[] = "TODO: Resouces";
$formdata["callspersecond"] = array(
	"label" => _L('Calls per Second'),
	"value" => $dmsettings['telco_calls_sec'],
	"validators" => array(
		array("ValRequired"),
		array("ValNumber")
	),
	"control" => array("TextField","size" => 15, "maxlength" => 20),
	"helpstep" => $helpstepnum
);
$formdata["numberofresources"] = array(
	"label" => _L('Number of Resources'),
	"value" => $dmsettings['delmech_resource_count'],
	"validators" => array(
		array("ValRequired"),
		array("ValNumber")
	),
	"control" => array("TextField","size" => 15, "maxlength" => 20),
	"helpstep" => $helpstepnum
);
$formdata["inboundresouces"] = array(
	"label" => _L('Inbound Resouces'),
	"value" => $dmsettings['telco_inboundtoken'],
	"validators" => array(
		array("ValRequired"),
		array("ValNumber"),
		array("ValInbound", "field" => "numberofresources")
	),
	"requires" => array("numberofresources"),
	"control" => array("TextField","size" => 15, "maxlength" => 20),
	"helpstep" => $helpstepnum
);
$formdata["disablethrottle"] = array(
	"label" => _L('Disable Congestion Throttle'),
	"value" => $dmsettings["disable_congestion_throttle"],
	"validators" => array(),
	"control" => array("CheckBox"),
	"helpstep" => $helpstepnum
);
$helpstepnum++;
$helpsteps[] = "TODO: Authorized IP";
$formdata["lastip"] = array(
	"label" => _L("Last IP"),
	"control" => array("FormHtml","html"=> escapehtml($dminfo["lastip"]) .
		' <a href="#" onclick="$(\'editdm_authorizedip\').value=\'' . $dminfo["lastip"] . 
		'\';form_do_validation($(\'editdm\'), $(\'editdm_authorizedip\')); return false;">Copy to authorized ip</a>'),	
	"helpstep" => $helpstepnum
);
$formdata["authorizedip"] = array(
	"label" => _L('Authorized IP'),
	"value" => $dminfo['authorizedip'],
	"validators" => array(
		array("ValRequired"),
		array("ValIp")
	),
	"control" => array("TextField","size" => 32, "maxlength" => 32),
	"helpstep" => $helpstepnum
);
$helpstepnum++;
$helpsteps[] = "TODO: Notes";
$formdata["notes"] = array(
	"label" => _L('Notes'),
	"value" => $dminfo['notes'],
	"validators" => array(),
	"control" => array("TextArea", "rows" => 3, "cols" => 40),
	"helpstep" => $helpstepnum
);

$buttons = array(submit_button(_L('Save'),"submit","tick"),
				icon_button(_L('Cancel'),"cross",null,($dmType == 'customer'?"customerdms.php":"systemdms.php")));
$form = new Form("editdm",$formdata,null,$buttons);

////////////////////////////////////////////////////////////////////////////////
// Form Data Handling
////////////////////////////////////////////////////////////////////////////////

//check and handle an ajax request (will exit early)
//or merge in related post data
$form->handleRequest();

$datachange = false;
$errors = false;
//check for form submission
if ($button = $form->getSubmit()) { //checks for submit and merges in post data
	$ajax = $form->isAjaxSubmit(); //whether or not this requires an ajax response	
	
	if ($form->checkForDataChange()) {
		$datachange = true;
	} else if (($errors = $form->validate()) === false) { //checks all of the items in this form
		$postdata = $form->getData(); //gets assoc array of all values {name:value,...}
		Query("BEGIN");
		
		QuickUpdate("update dm set enablestate=? where id=?",false,array($postdata["authorized"],$dmid));
		$enablestate = $postdata["authorized"];
		
		$dialtimeout = $dmsettings["telco_dial_timeout"];
		if($dialtimeout == false){
			$dialtimeout = 45000;
		}

		QuickUpdate("delete from dmsetting where dmid=?",false,array($dmid));
		QuickUpdate("insert into dmsetting (dmid, name, value) values 
					(?,'dm_enabled',?),
					(?,'telco_type',?),
					(?,'test_has_delays',?),
					(?,'testweightedresults',?),
					(?,'telco_caller_id',?),
					(?,'telco_calls_sec',?),
					(?,'delmech_resource_count',?),
					(?,'telco_inboundtoken',?),
					(?,'telco_dial_timeout',?),
					(?,'disable_congestion_throttle',?)
					",
				false,
				array(
					$dmid,$postdata["enabled"]?1:0,
					$dmid,$postdata["type"],
					$dmid,$postdata["testhasdelays"]?1:0,
					$dmid,$postdata["testweightedresults"],
					$dmid,Phone::parse($postdata["callerid"]),
					$dmid,$postdata["callspersecond"],
					$dmid,$postdata["numberofresources"],
					$dmid,$postdata["inboundresouces"],
					$dmid,$dialtimeout,
					$dmid,$postdata["disablethrottle"]?1:0)
		);

		$newcustomerid = isset($postdata["customerid"])?$postdata["customerid"] + 0:0;
		$dmgroupid = isset($postdata["dmgroup"]) && $postdata["dmgroup"]!=''?$postdata["dmgroup"]:null;
		$routetype = isset($postdata["routetype"]) && $postdata["routetype"] != ''?$postdata["routetype"]:null;

		QuickUpdate("update dm set	authorizedip=?,
									customerid=?,
									dmgroupid=?,
									routetype=?,
									notes=?
									where id=?",false,
									array($postdata["authorizedip"],$newcustomerid,$dmgroupid,$routetype,$postdata["notes"],$dmid));
		
		if ($dmType == 'customer') {
			if($dminfo['customerid'] != null && $newcustomerid != $dminfo['customerid']){
				$custinfo = QuickQueryRow("select s.dbhost, s.dbusername, s.dbpassword from shard s inner join customer c on (c.shardid = s.id)
																where c.id = ?",false,false,array($dminfo['customerid']));				
				$custdb = DBConnect($custinfo[0], $custinfo[1], $custinfo[2], "c_" . $dminfo['customerid']);
				if(QuickQuery("select count(*) from custdm where dmid=?", $custdb,array($dmid))){
					QuickUpdate("delete from custdm where dmid=?", $custdb,array($dmid));
				}
			}				
			$custinfo = QuickQueryRow("select s.dbhost, s.dbusername, s.dbpassword from shard s inner join customer c on (c.shardid = s.id)
										where c.id=?",false,false,array($newcustomerid));
			$custdb = DBConnect($custinfo[0], $custinfo[1], $custinfo[2], "c_" . $newcustomerid);
			
			if(!QuickQuery("select count(*) from custdm where dmid=?", $custdb,array($dmid))){
				QuickUpdate("insert into custdm (dmid, name, enablestate, telco_type,notes) values (?,?,?,?,?)", $custdb,
								array($dmid,$dminfo['name'],$enablestate,$postdata["type"],$postdata["notes"]));
			} else {
				QuickUpdate("update custdm set enablestate=?,telco_type=?,notes=? where dmid=?",$custdb,
								array($enablestate,$postdata["type"],$postdata["notes"],$dmid));
			}
		}

		
		Query("COMMIT");
		if ($ajax)
			$dmType == 'customer'?$form->sendTo("customerdms.php"):$form->sendTo("systemdms.php");
		else
			$dmType == 'customer'?$redirect("customerdms.php"):redirect("systemdms.php");
	}
}


////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$TITLE = _L('DM Settings');

include_once("nav.inc.php");

?>
<script type="text/javascript">
<? Validator::load_validators(array("ValCustomerId","ValIp","ValInbound")); ?>

function displaytestitems() {
	var type = $('editdm_type').getValue();
	if (type == 'Asterisk') {
		$('editdm_testhasdelays_fieldarea').hide();
		$('editdm_testweightedresults_fieldarea').hide();
	} else {
		$('editdm_testhasdelays_fieldarea').show();
		$('editdm_testweightedresults_fieldarea').show()
	}
}

document.observe('dom:loaded', function() {
	displaytestitems();
	$('editdm_type').observe('change', displaytestitems);
});

</script>
<?

startWindow(_L('DM Settings: %s', escapehtml($dminfo["name"])) . ($dminfo["enablestate"]=="new"?_L(" (New)"):''));
echo $form->render();
endWindow();

startWindow(_L("DM Information"));
echo "<ul><li><a href=\"dmdatfiles.php?dmid=$dmid\">Dat File History</a></li></ul>";
endWindow();
include_once("navbottom.inc.php");
?>