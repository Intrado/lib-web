<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("inc/common.inc.php");
require_once("inc/securityhelper.inc.php");
require_once("inc/table.inc.php");
require_once("inc/html.inc.php");
require_once("inc/utils.inc.php");
require_once("obj/Validator.obj.php");
require_once("obj/Form.obj.php");
require_once("obj/FormItem.obj.php");
require_once("obj/Wizard.obj.php");
require_once("obj/Organization.obj.php");
require_once("obj/Publish.obj.php");

////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('publish')) {
	redirect('unauthorized.php');
}

////////////////////////////////////////////////////////////////////////////////
// Action/Request Processing
////////////////////////////////////////////////////////////////////////////////

if (isset($_GET['type']) && isset($_GET['id'])) {
	// if the requested object is owned by this user and they are authorized to publish the requested type
	if (userOwns($_GET['type'], $_GET['id']) && userCanPublish($_GET['type'])) {
		$_SESSION['publishtargetwiz'] = array("data" => array(), "type" => $_GET['type'], "id" => $_GET['id']);
		redirect("");
	}
	redirect("unauthorized.php");
}

if (!isset($_SESSION['publishtargetwiz']['type']) || !isset($_SESSION['publishtargetwiz']['id']))
	redirect("unauthorized.php");

////////////////////////////////////////////////////////////////////////////////
// Optional Form Items And Validators
////////////////////////////////////////////////////////////////////////////////

class ValUserOrganization extends Validator {
	var $onlyserverside = true;

	function validate ($value, $args) {
		if ($value) {
			$validorgs = QuickQueryList("select id, orgkey from organization where id in (". DBParamListString(count($value)) .")", true, false, $value);
			foreach ($value as $id) {
				if (!isset($validorgs[$id]) && ($id + 0) !== 0) {
					error_log($id);
					return _L('%s has invalid data selected.', $this->label);
				}
			}
		}
		return true;
	}
}

////////////////////////////////////////////////////////////////////////////////
// Form Data
////////////////////////////////////////////////////////////////////////////////

class PublishTargetWiz_publishtarget extends WizStep {
	function getForm($postdata, $curstep) {
		global $USER;
		
		$type = $_SESSION['publishtargetwiz']['type'];
		$id = $_SESSION['publishtargetwiz']['id'];
		
		// if it is currently published, get the published organizations
		switch ($type) {
			case "messagegroup":
				$orgs = QuickQueryList("select organizationid from publish where action = 'publish' and type = 'messagegroup' and messagegroupid = ?", false, false, array($id));
				break;
			case "list":
				$orgs = QuickQueryList("select organizationid from publish where action = 'publish' and type = 'list' and listid = ?", false, false, array($id));
				break;
			default:
				$orgs = array();
		}
		
		$value = "";
		// open for anyone to subscribe to it
		if (count($orgs) == 1 && $orgs[0] == null) {
			$value = "anyone";
		// only unrestricted users may subscribe
		} else if (count($orgs) == 1 && $orgs[0] == "0") {
			$value = "unrestricted";
		// one or more organizations may subscribe
		} else if (count($orgs) > 0) {
			$value = "organization";
		}
		
		// restricted users can publish to organizations they belong to
		// un-restricted users can publish to anything
		$userrestrictions = QuickQuery("select 1 from userassociation where userid = ? and (sectionid is not null or organizationid is not null) limit 1", false, array($USER->id));
		
		$values = array("nobody" => _L("Nobody May Subscribe"));
		if (!$userrestrictions) {
			$values["anyone"] = _L("Anyone May Subscribe");
			$values["unrestricted"] = _L("Top Level Users");
		}
		$values["organization"] = _L("Specific Organization(s)");
		
		$formdata['target'] = array(
			"label" => _L("Select who can subscribe"),
			"fieldhelp" => _L("TODO: Help"),
			"value" => $value,
			"validators" => array(
				array("ValRequired"),
				array("ValInArray","values" => array_keys($values))
			),
			"control" => array("RadioButton", "values" => $values),
			"helpstep" => 1
		);

		$helpsteps = array(_L("TODO: Help"));

		return new Form("publishtargetwiz-publishtarget",$formdata,$helpsteps);
	}
	function isEnabled($postdata, $step) {
		return true;
	}
}

class PublishTargetWiz_chooseorganizations extends WizStep {
	function getForm($postdata, $curstep) {
		global $USER;
		
		// if it is currently published, get the published organizations
		switch ($_SESSION['publishtargetwiz']['type']) {
			case "messagegroup":
				$orgs = QuickQueryList("select organizationid from publish where action = 'publish' and type = 'messagegroup' and organizationid is not null and messagegroupid = ?", false, false, array($_SESSION['publishtargetwiz']['id']));
				break;
			case "list":
				$orgs = QuickQueryList("select organizationid from publish where action = 'publish' and type = 'list' and organizationid is not null and listid = ?", false, false, array($_SESSION['publishtargetwiz']['id']));
				break;
			default:
		}
		
		$values = Organization::getAuthorizedOrgKeys();
		
		$formdata["organizationids"] = array(
			"label" => _L('Organization(s)'),
			"fieldhelp" => _L('Add or remove user organization associations'),
			"value" => (isset($orgs) && $orgs)?$orgs:array(),
			"validators" => array(
				array("ValRequired"),
				array("ValUserOrganization")
			),
			"control" => array("MultiCheckBox", "height" => "150px", "values" => $values),
			"helpstep" => 2
		);
		
		$helpsteps = array(_L("TODO: Help"));

		return new Form("publishtargetwiz-chooseorganizations",$formdata,$helpsteps);
	}
	//returns true if this step is enabled
	function isEnabled($postdata, $step) {
		$target = (isset($postdata['/publishtarget']['target'])?$postdata['/publishtarget']['target']:false);
		return ($target == "organization");
	}
}

class PublishTargetWiz_confirm extends WizStep {
	function getForm($postdata, $curstep) {
		
		$formdata['confirmationtext'] = array(
			"label" => _L(""),
			"fieldhelp" => _L("TODO: Help"),
			"control" => array("FormHtml", "html" => "<div>TODO: Confirmation text goes here</div>"),
			"helpstep" => 1
		);
		$formdata['confirm'] = array(
			"label" => _L("Confirm"),
			"fieldhelp" => _L("TODO: Help"),
			"value" => "",
			"validators" => array(
				array("ValRequired")),
			"control" => array("CheckBox"),
			"helpstep" => 1
		);
		$helpsteps = array(_L("TODO: Help"));
		return new Form("publishtargetwiz-confirm",$formdata,$helpsteps);
	}
	//returns true if this step is enabled
	function isEnabled($postdata, $step) {
		return true;
	}
}

class FinishPublishTargetWiz extends WizFinish {

	function finish ($postdata) {
		global $USER;
		
		$type = $_SESSION['publishtargetwiz']['type'];
		$id = $_SESSION['publishtargetwiz']['id'];
		
		$target = $postdata['/publishtarget']["target"];
		
		// look up existing publications for this object
		$publications = getPublications($type, $id);
		
		$publishedorgs = array();
		if ($publications)
			foreach ($publications as $pubid => $publish)
				$publishedorgs[$publish->organizationid] = $publish;
		
		$addorgs = array();
		if ($target == "anyone")
			$addorgs[] = null;
		else if ($target == "unrestricted")
			$addorgs[] = 0;
		else if ($target == "organization")
			$addorgs = $postdata['/chooseorganizations']["organizationids"];
		
		Query("BEGIN");
		
		// create publish objects where they don't already exist
		foreach ($addorgs as $orgid) {
			// if the requested org isn't in the list of already published orgs, create a new publish object
			if (!isset($publishedorgs[$orgid])) {
				$publish = new Publish();
				$publish->userid = $USER->id;
				$publish->action = 'publish';
				$publish->type = $type;
				$publish->setTypeId($id);
				$publish->organizationid = $orgid;
				$publish->create();
			}
		}
		
		// remove publish objects that arn't valid
		foreach ($publishedorgs as $orgid => $publish) {
			if (!in_array($orgid, $addorgs, true))
				$publish->destroy();
		}

		// remove subscriptions that are no longer authorized
		$subscriptions = getSubscriptions($type, $id);
		// check each of the subscriptions user access to see if we should remove the subscription
		foreach ($subscriptions as $subscribe) {
			// check if the user has restrictions
			$userrestrictions = QuickQuery("select 1 from userassociation where userid = ? and (sectionid is not null or organizationid is not null) limit 1", false, array($subscribe->userid));
			$authorgs = array();
			// if they are restricted, get their authorized organizations
			if ($userrestrictions)
				$authorgs = Organization::getAuthorizedOrgKeys($subscribe->userid);
			// if the user is restriced and the organization id is not null and they don't have association to any of the publish orgs, remove the subscription
			if ($userrestrictions && !in_array(null, $addorgs, true) && !array_intersect_key($authorgs, $addorgs))
				$subscribe->destroy();
		}
		
		Query("COMMIT");
	}
	
	function getFinishPage ($postdata) {
		return "<h1>Finshed!</h1>";
	}
}

/**************************** wizard setup ****************************/
$wizdata = array(
	"publishtarget" => new PublishTargetWiz_publishtarget(_L("Publish Target")),
	"chooseorganizations" => new PublishTargetWiz_chooseorganizations(_L("Organizations")),
	"confirm" => new PublishTargetWiz_confirm(_L("Confirm"))
	);

$wizard = new Wizard("publishtargetwiz", $wizdata, new FinishPublishTargetWiz(_L("Finish")));
$wizard->doneurl = "lists.php";
$wizard->handleRequest();

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$subtab = "";
switch ($_SESSION['publishtargetwiz']['type']) {
	case "messagegroup":
		$subtab = "messages";
		break;
	case "list":
		$subtab = "lists";
		break;
	default:
}

$PAGE = "notifications:$subtab";
$TITLE = "Publication Editor";

require_once("nav.inc.php");

?>
<script type="text/javascript">
<? Validator::load_validators(array("ValUserOrganization"));?>
</script>
<?

startWindow($wizard->getStepData()->title);

echo $wizard->render();

endWindow();

if (true) {
	startWindow("Wizard Data");
	echo "<pre>";
	var_dump($_SESSION['publishtargetwiz']);
	//var_dump($_SERVER);
	echo "</pre>";
	endWindow();
}

require_once("navbottom.inc.php");
?>
