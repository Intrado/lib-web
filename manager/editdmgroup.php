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

if (!$MANAGERUSER->authorized("systemdm"))
	exit("Not Authorized");

if(isset($_GET['dmgroupid'])){
	if ($_GET['dmgroupid'] === "new") {
		$_SESSION['dmgroupid'] = null;
	} else {
		$dmgroupid = $_GET['dmgroupid'] + 0;
		if (!QuickQuery("select count(*) from dmgroup where id = ?", false, array($dmgroupid))) {
			echo "Invalid DM Group.";
			exit();
		}
		$_SESSION['dmgroupid'] = $dmgroupid;
	}
	redirect();
} else {
	$dmgroupid = $_SESSION['dmgroupid'];
}

////////////////////////////////////////////////////////////////////////////////
// Action/Request Processing
////////////////////////////////////////////////////////////////////////////////

////////////////////////////////////////////////////////////////////////////////
// Custom Form Items and Validators
////////////////////////////////////////////////////////////////////////////////

////////////////////////////////////////////////////////////////////////////////
// Form Data
////////////////////////////////////////////////////////////////////////////////

// DM Group settings defaults
$dmgroupsettings = array( );

$dmgroupsettings = array_merge($dmgroupsettings,QuickQueryList("select name,value from dmgroupsetting where dmgroupid=?",true,false,array($dmgroupid)));
//FIXME make a DBMO, don't pull out an entire object to a name indexed row
$dmgroupinfo = QuickQueryRow("select name,carrierRateModelId, dispatchType, routeType, notes from dmgroup where id=?", true,false,array($dmgroupid));

$helpstepnum = 1;

$helpsteps[] = "TODO: Name";
$formdata["name"] = array(
	"label" => _L('Name'),
	"value" => $dmgroupinfo['name'],
	"validators" => array(
		array("ValRequired")
	),
	"control" => array("TextField","size" => 50, "maxlength" => 255),
	"helpstep" => $helpstepnum
);
$helpstepnum++;

$helpsteps[] = "TODO: Rare Model";
$lcrdbcon = DBConnect($SETTINGS['lcrdb']['host'], $SETTINGS['lcrdb']['user'], $SETTINGS['lcrdb']['pass'], $SETTINGS['lcrdb']['db']);
$carrierratemodels = QuickQueryList("select id, name from carrierratemodel",true,$lcrdbcon);
$formdata["carrierRateModelId"] = array(
	"label" => _L('Rate Model'),
	"value" => $dmgroupinfo["carrierRateModelId"],
	"validators" => array(
		array("ValRequired"),
		array("ValInArray", "values" => array_keys($carrierratemodels))
	),
	"control" => array("SelectMenu", "values" => array('' => "None") + $carrierratemodels),
	"helpstep" => $helpstepnum
);

$helpstepnum++;

$helpsteps[] = "TODO: Jms Connection Name";
$jmsSettings = QuickQueryRow("select dgjs.jmsConnectionFactoryName as jmsConnectionFactoryName,
								  dgjs.resultQueueName as resultQueueName,
								  dgjs.statusTopicName as statusTopicName,
								  dgjs.taskQueueName as taskQueueName
							from dmgroupjmssetting dgjs
								join dmgroupjmsprofile dgjp on dgjp.dispatcherJmsSettingId = dgjs.id
								join dmgroup on dmgroup.dmGroupJmsProfileId = dgjp.id and dmgroup.id = " . $dmgroupid ,true, false, false);

error_log(print_r($jmsSettings,true));

$jmsConnectionFactoryNames = array();
foreach ($SETTINGS['jms']['connectionFactoryName'] as $connectionName) {
	$jmsConnectionFactoryNames[$connectionName] = $connectionName;
}
$formdata["jmsConnectionName"] = array(
	"label" => _L('JMS Name'),
	"value" => $jmsSettings["jmsConnectionFactoryName"],
	"validators" => array(
		array("ValInArray", "values" => array_keys($jmsConnectionFactoryNames))
	),
	"control" => array("SelectMenu", "values" => array('' => "None") + $jmsConnectionFactoryNames),
	"helpstep" => $helpstepnum
);

$helpstepnum++;
$dmGroupSettings = array();
$settings = QuickQueryMultiRow("select name, value from dmgroupsetting where dmgroupid = ?", true, false, array($dmgroupid));
foreach ((array)$settings as $setting) {
	$dmGroupSettings[$setting["name"]] = $setting["value"];
}
$helpsteps[] = "TODO: Jms Back pressure";
$formdata["jmsBackPressure"] = array(
	"label" => _L('Jms Back Pressure'),
	"value" => $dmGroupSettings['taskJms.resourceBackPressure'],
	"validators" => array(
		array("ValNumeric")
	),
	"control" => array("TextField","size" => 25, "maxlength" => 25),
	"helpstep" => $helpstepnum
);
$helpstepnum++;

$queueNameParts = explode(".",$jmsSettings["taskQueueName"]);
$prefix = $queueNameParts[0];
$helpsteps[] = "TODO: Queue/Topic Prefix";
$formdata["queuePrefix"] = array(
	"label" => _L('Queue/Topic prefix'),
	"value" => $prefix,
	"validators" => array(
		array("ValAlphaNumeric", "min" => 4)
	),
	"control" => array("TextField","size" => 50, "maxlength" => 255),
	"helpstep" => $helpstepnum
);
$helpstepnum++;

$helpsteps[] = "TODO: Route Type";
$routetypes = array("firstcall" => "Firstcall","lastcall" => "Lastcall","" => "Other");
$formdata["routeType"] = array(
	"label" => _L('Route Type'),
	"value" => $dmgroupinfo["routeType"],
	"validators" => array(
		array("ValInArray", "values" => array_keys($routetypes))
	),
	"control" => array("SelectMenu", "values" => $routetypes),
	"helpstep" => $helpstepnum
);

$helpstepnum++;
$helpsteps[] = "TODO: Notes";
$formdata["notes"] = array(
	"label" => _L('Notes'),
	"value" => $dmgroupinfo['notes'],
	"validators" => array(),
	"control" => array("TextArea", "rows" => 3, "cols" => 40),
	"helpstep" => $helpstepnum
);

$buttons = array(submit_button(_L('Save'),"submit","tick"),
				icon_button(_L('Cancel'),"cross",null,"systemdmgroups.php"));
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
		$lcrdbcon = DBConnect($SETTINGS['lcrdb']['host'], $SETTINGS['lcrdb']['user'], $SETTINGS['lcrdb']['pass'], $SETTINGS['lcrdb']['db']);
		$carrierratemodel = QuickQueryRow("select id, name, classname, params from carrierratemodel where id = " . $postdata["carrierRateModelId"],true,$lcrdbcon,false);

		Query("BEGIN");

		if ($postdata["queuePrefix"] == "") {
			// if no postfix was passed in use the name
			$queuePrefix = preg_replace('/[^A-Za-z0-9]/', '', $postdata["name"]); // Removes special chars.
		} else {
			$queuePrefix =  $postdata["queuePrefix"];
		}
		$resultQueueName = $queuePrefix . ".result";
		$statusTopicName = $queuePrefix . ".status";
		$taskQueueName = $queuePrefix . ".task";

		if ($dmgroupid) {
			$originalDmGroup = QuickQueryRow("select * from dmgroup where id = " . $dmgroupid, true, false, false);
			if (strlen($postdata["jmsConnectionName"]) > 0) {
				if ($originalDmGroup["dmGroupJmsProfileId"] > 0) {
					$dmGroupJmsProfile = QuickQueryRow("select dgjp.* from dmgroupjmsprofile dgjp where dgjp.id = ?", true, false, array($originalDmGroup["dmGroupJmsProfileId"]));
					QuickUpdate("update dmgroupjmssetting set jmsConnectionFactoryName=?,
 									resultQueueName=?,
 									statusTopicName=?,
 									taskQueueName=? where id = ?", false,
						array($postdata["jmsConnectionName"], $resultQueueName, $statusTopicName, $taskQueueName, $dmGroupJmsProfile["dispatcherJmsSettingId"]));
					$jmsProfileId = $originalDmGroup["dmGroupJmsProfileId"];
				} else {
					QuickUpdate("insert into dmgroupjmssetting (jmsConnectionFactoryName, resultQueueName, statusTopicName, taskQueueName)
						values (?,?,?,?)", false, array($postdata["jmsConnectionName"], $resultQueueName, $statusTopicName, $taskQueueName));
					$result = QuickQueryRow("select LAST_INSERT_ID() as id", true, false);
					$dmGroupJmsSettingId =$result["id"];
					QuickUpdate("insert into dmgroupjmsprofile (name, dispatcherJmsSettingId, dmJmsSettingId )
						values (?,?,?)", false, array($queuePrefix, $dmGroupJmsSettingId, $dmGroupJmsSettingId));
					$result = QuickQueryRow("select LAST_INSERT_ID() as id", true, false);
					$jmsProfileId =$result["id"];
				}
			} else {
				if ($originalDmGroup["dmGroupJmsProfileId"] > 0) {
					$otherDmsWithThisJmsProfile = QuickQueryMultiRow("select id from dmgroup where dmGroupJmsProfileId = ?", true, false, array($originalDmGroup["dmGroupJmsProfileId"]));
					// see if we are the only one using this JmsProfile remove it since we are not using it any more
					if (size($otherDmsWithThisJmsProfile) == 1) {
						$dmGroupJmsProfile = QuickQueryRow("select dgjp.* from dmgroupjmsprofile dgjp where dgjp.id = ?", true, false, array($originalDmGroup["dmGroupJmsProfileId"]));
						$otherJmsProfilesWithThisSetting = QuickQueryMultiRow("select id from dmgroupjmsprofile where dmJmsSettingId in (?,?) OR dispatcherJmsSettingId in (?,?)", true, false,
							array($dmGroupJmsProfile["dmJmsSettingId"], $dmGroupJmsProfile["dispatcherJmsSettingId"],$dmGroupJmsProfile["dmJmsSettingId"], $dmGroupJmsProfile["dispatcherJmsSettingId"]));
						QuickQuery("delete from dmgroupjmsprofile where id = ?", false, array($dmGroupJmsProfile["id"]));
						// see if this jmsProfile is the only one using these settings remove it since we are not using it any more.
						if (size($otherJmsProfilesWithThisSetting) == 1) {
							QuickQuery("delete from dmgroupjmssetting where id in (?,?)", false, array($dmGroupJmsProfile["dmJmsSettingId"],$dmGroupJmsProfile["dispatcherJmsSettingId"]));
						}
					}
				}
				$jmsProfileId = 0;
			}
			QuickUpdate("update dmgroup set	name=?,
								dmGroupJmsProfileId=?,
								carrierRateModelId=?,
								carrierRateModelClassname=?,
								carrierRateModelParams=?,
								routeType=?,
								notes=?
								where id=?", false,
				array($postdata["name"], $jmsProfileId, $postdata["carrierRateModelId"], $carrierratemodel["classname"], $carrierratemodel["params"], $postdata["routeType"], $postdata["notes"], $dmgroupid));
			if (isset($postdata["jmsBackPressure"]) && $postdata["jmsBackPressure"] > 0) {
				QuickUpdate("insert into dmgroupsetting (dmgroupid, name, value) values (?,?,?) ON DUPLICATE KEY UPDATE value=? ", false,
					array($dmgroupid, "taskJms.resourceBackPressure", $postdata["jmsBackPressure"], $postdata["jmsBackPressure"]));
			}
	} else {
			if (strlen($postdata["jmsConnectionName"]) > 0) {
				QuickUpdate("insert into dmgroupjmssetting (jmsConnectionFactoryName, resultQueueName, statusTopicName, taskQueueName)
						values (?,?,?,?)", false, array($postdata["jmsConnectionName"], $resultQueueName, $statusTopicName, $taskQueueName));
				$result = QuickQueryRow("select LAST_INSERT_ID() as id", true, false);
				$dmGroupJmsSettingId = $result["id"];
				QuickUpdate("insert into dmgroupjmsprofile (name, dispatcherJmsSettingId, dmJmsSettingId )
						values (?,?,?)", false, array($queuePrefix, $dmGroupJmsSettingId, $dmGroupJmsSettingId));
				$result = QuickQueryRow("select LAST_INSERT_ID() as id", true, false);
				$dmGroupJmsProfileId = $result["id"];
			} else {
				$dmGroupJmsProfileId = 0;
			}
			QuickUpdate("insert into dmgroup (name, carrierRateModelId, carrierRateModelClassname, carrierRateModelParams, dmGroupJmsProfileId, routeType, notes) values (?,?,?,?,?,?,?)" , false,
				array($postdata["name"], $postdata["carrierRateModelId"], $carrierratemodel["classname"], $carrierratemodel["params"],
					$dmGroupJmsProfileId, $postdata["routeType"], $postdata["notes"]));
			$result = QuickQueryRow("select LAST_INSERT_ID() as id", true, false);
			$dmgroupid =$result["id"];
			if (isset($postdata["jmsBackPressure"]) && $postdata["jmsBackPressure"] > 0) {
				QuickUpdate("insert into dmgroupsetting (dmgroupid, name, value) values (?,?,?)", false,
					array($dmgroupid, "taskJms.resourceBackPressure", $postdata["jmsBackPressure"]));
			}

		}

		Query("COMMIT");
		if ($ajax)
			$dmType == $form->sendTo("systemdmgroups.php");
		else
			$dmType == redirect("systemdmgroups.php");
	}
}


////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$TITLE = _L('DM Group Settings');
$PAGE = "dm:systemdmgroups";

include_once("nav.inc.php");

startWindow(_L('DM Group Settings: %s', escapehtml($dmgroupinfo["name"])));
echo $form->render();
endWindow();

include_once("navbottom.inc.php");
?>
