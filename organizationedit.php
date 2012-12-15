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

////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('metadata')) {
	redirect('unauthorized.php');
}

///////////////////////////////////////////////////////////////////////////////
// Request processing:
///////////////////////////////////////////////////////////////////////////////
if (isset($_GET['orgid'])) {
	if ($_GET['orgid'] != "new") { 
		// validate and set the orgkey
		$originalorgid = $_GET['orgid'];
		$originalorgkey = QuickQuery("select orgkey from organization where id = ? and not deleted", false, array($originalorgid));
		if (!$originalorgkey) {
			redirect('unauthorized.php');
		}
	}
} else {
	redirect("organizationdatamanager.php");
}

$originalorg = isset($originalorgid)?DBFind("Organization", "from organization where id = ? and not deleted", false, array($originalorgid)):null;

$hasTai = getSystemSetting("_dbtaiversion",false) !== false;

////////////////////////////////////////////////////////////////////////////////
// Optional Form Items And Validators
////////////////////////////////////////////////////////////////////////////////

class ValOrgKey extends Validator {
	var $onlyserverside = true;
	function validate ($value, $args) {
		// if they are just renaming to change case, allow it
		if (isset($args['originalorgkey']) && mb_strtolower($value) == mb_strtolower($args['originalorgkey']))
			return true;
		// look up this orgkey to see if it already exists
		$org = DBFind("Organization", "from organization where orgkey = ? and not deleted", false, array($value));
		if ($org)
			return $this->label . " " . _L("This organization name already exists. Please enter a unique organizaton name");
		return true;
	}
}

////////////////////////////////////////////////////////////////////////////////
// Form Data
////////////////////////////////////////////////////////////////////////////////

$organizations = QuickQueryList("select id, orgkey from organization where not deleted",true);

$namevalidators = array(
	array("ValRequired"),
	array("ValLength","min" => 1,"max" => 255)
);

// Remove current organization from parent organization list if editing. also validat original orgkey
if (isset($originalorgkey)) {
	unset($organizations[$originalorgid]);
	$namevalidators[] = array("ValOrgKey", "originalorgkey" => $originalorgkey);
} else {
	$namevalidators[] = array("ValOrgKey");
}

$formdata = array();
$helpsteps = array();

// To be enabled when needed
if (false && $hasTai) {
	
	if (isset($originalorg) && $originalorg->parentorganizationid == null) {
		// TODO could inform the user that he/she Is editing the name for root org and that the parent org picker is unavaliable
	} else {
		$helpsteps[] = _L('Organizations may be arranged in a hierarchy. Optionally select a parent organization to link the new organization in the hierarchy.');
		$formdata["parentorganization"] = array(
			"label" => _L('Parent Organization'),
			"value" => isset($originalorg)?$originalorg->parentorganizationid:"",
			"validators" => array(
					array("ValRequired"),
					array("ValInArray", "values" => array_keys($organizations))
			),
			"control" => array("SelectMenu", "values" => array("" =>_L("-- Select Parent --")) + $organizations),
			"helpstep" => count($helpsteps)
		);
	}
}
$helpsteps[] = _L('Enter a new name for this organization. It should clearly indicate what the organization is. The name must be unique within the system.');
$formdata["orgkey"] = array(
	"label" => _L('Organization Name'),
	"fieldhelp" => _L("Enter a unique name for the new organization."),
	"value" => isset($originalorg)?$originalorg->orgkey:"",
	"validators" => $namevalidators,
	"control" => array("TextField","size" => 30, "maxlength" => 255),
	"helpstep" => count($helpsteps)
);

$buttons = array(submit_button(_L('Save'),"submit","tick"),
				icon_button(_L('Cancel'),"cross",null,"organizationdatamanager.php"));
$form = new Form("editorgform",$formdata,$helpsteps,$buttons);

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
		
		$orgkey = trim($postdata['orgkey']);
		
		if ($hasTai) {
			// Only one root org is allowed, if editing the root org parentorganization should be null
			$parentorganization = DBFind("Organization", "from organization where parentorganizationid is null and orgkey != ? and not deleted", false, array($orgkey));
		} else {
			$parentorganization = false;
		}
		Query("BEGIN");
		
		$existingorg = DBFind("Organization", "from organization where orgkey = ?", false, array($orgkey));
		// if the org already exists make it our target org
		if ($existingorg) {
			
			$org = $existingorg;
			$org->parentorganizationid = $parentorganization?$parentorganization->id:null;
			$org->orgkey = $orgkey;
			$org->deleted = 0;
			$org->modifiedtimestamp = time();
			$org->update();				
		// if it's a new org then crete a new one
		} else {
			$org = new Organization();
			$isnewroot = isset($originalorg) && $originalorg->parentorganizationid == null;
							
			if ($isnewroot || !$parentorganization)	{
				$org->parentorganizationid = null;
			} else {
				$org->parentorganizationid = $parentorganization->id;
			}
			$org->createdtimestamp = time();
			$org->modifiedtimestamp = $org->createdtimestamp;
			$org->orgkey = $orgkey;
			$org->create();
		}
		
		// if the original org and the new org are not the same, update associations and delete the original
		if (isset($originalorg)) {
			if ($org->id !== $originalorg->id) {
				QuickUpdate("update userassociation set organizationid = ? where organizationid = ?", false, array($org->id, $originalorgid));
				QuickUpdate("update personassociation set organizationid = ? where organizationid = ?", false, array($org->id, $originalorgid));
				QuickUpdate("update listentry set organizationid = ? where organizationid = ?", false, array($org->id, $originalorgid));
				QuickUpdate("update organization set parentorganizationid = ? where parentorganizationid = ?", false, array($org->id, $originalorgid));
				
				// check persondatavalues and update/create/delete entries
				$originalorgpdvid = QuickQuery("select id from persondatavalues where fieldnum = 'oid' and value = ?", false, array($originalorgid));
				// if the original org exists in persondatavalues, remove it and insert the new org id
				if ($originalorgpdvid) {
					QuickUpdate("delete from persondatavalues where id = ?", false, array($originalorgpdvid));
					QuickUpdate("insert into persondatavalues values (null, 'oid', ?, 0, 1)", false, array($org->id));
				}
				
				$originalorg->deleted = 1;
				$originalorg->update();
			}
			notice(_L('Organization %1$s has been updated', $orgkey));
		} else {
			notice(_L('%1$s has been created.', $orgkey));
		}
		
		Query("COMMIT");
		
		if ($ajax)
			$form->sendTo("organizationdatamanager.php");
		else
			redirect("organizationdatamanager.php");
	}
}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$PAGE = "admin:settings";
$TITLE = "";

include_once("nav.inc.php");

// Optional Load Custom Form Validators
?>
<script type="text/javascript">
<? Validator::load_validators(array("ValOrgKey")); ?>
</script>
<?

startWindow(isset($originalorgid)?_L('Edit Organization'):_L('Create Organization'));
echo $form->render();
endWindow();
include_once("navbottom.inc.php");
?>