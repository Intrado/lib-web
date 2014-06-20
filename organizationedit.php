<?
/*
 * Creates and/or renames organizations based off a provided "key" which is the name of the organization
 */
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
			return $this->label . " " . _L("This organization name already exists. Please enter a unique organization name");
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

// Remove current organization from parent organization list if editing. also validate original orgkey
if (isset($originalorgkey)) {
	unset($organizations[$originalorgid]);
	$namevalidators[] = array("ValOrgKey", "originalorgkey" => $originalorgkey);
} else {
	$namevalidators[] = array("ValOrgKey");
}

$formdata = array();
$helpsteps = array();
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

		// Did any data change?
		if (isset($originalorg) && (strtolower($originalorg->orgkey) == strtolower($orgkey))) {
			// nothing to do, organization key matches original
		} else {
			// Name change occurred
			Query("BEGIN");

			// if the org already exists make it our target org (We can only get this if it's deleted due to form validation)
			$orgWithTargetKey = DBFind("Organization", "from organization where orgkey = ?", false, array($orgkey));
			if ($orgWithTargetKey) {
				$org = $orgWithTargetKey;
				$org->orgkey = $orgkey;
				$org->deleted = 0;
				$org->modifiedtimestamp = time();
				// if it's a new org then crete a new one
			} else {
				$org = new Organization();
				$org->orgkey = $orgkey;
				$org->deleted = 0;
				$org->createdtimestamp = time();
				$org->modifiedtimestamp = $org->createdtimestamp;
			}

			// Always set the organization's parent to the original objects parent
			if (isset($originalorg)) {
				$org->parentorganizationid = $originalorg->parentorganizationid;
			}

			if ($org->id)
				$org->update();
			else
				$org->create();

			// if the original org and the new org are not the same, update associations and delete the original
			if (isset($originalorg)) {
				// update the original organization first.
				$originalorg->deleted = 1;
				$originalorg->modifiedtimestamp = time();
				$originalorg->update();

				QuickUpdate("update role set organizationid = ? where organizationid = ?", false, array($org->id, $originalorg->id));
				QuickUpdate("update userassociation set organizationid = ? where organizationid = ?", false, array($org->id, $originalorg->id));
				QuickUpdate("update personassociation set organizationid = ? where organizationid = ?", false, array($org->id, $originalorg->id));
				QuickUpdate("update listentry set organizationid = ? where organizationid = ?", false, array($org->id, $originalorg->id));

				if ($hasTai) {
					QuickUpdate('UPDATE tai_organizationtopic SET organizationid = ? WHERE organizationid = ?', false, array($org->id, $originalorg->id));
				}
				// check persondatavalues and update/create/delete entries
				$originalorgpdvid = QuickQuery("select id from persondatavalues where fieldnum = 'oid' and value = ?", false, array($originalorg->id));
				// if the original org exists in persondatavalues, remove it and insert the new org id
				if ($originalorgpdvid) {
					QuickUpdate("delete from persondatavalues where id = ?", false, array($originalorgpdvid));
					QuickUpdate("insert into persondatavalues values (null, 'oid', ?, 0, 1)", false, array($org->id));
				}
				notice(_L('Organization %1$s has been updated', $orgkey));
			} else {
				notice(_L('%1$s has been created.', $orgkey));
			}

			if ($hasTai) {
				// It is assumed that there is only ever one root organization and it has no parent
				$rootOrganization = DBFind("Organization", "from organization where parentorganizationid is null and not deleted");

				// TODO: This is a sledgehammer approach to making sure all orgs have the correct parent, Probably not necessary for all changes
				QuickUpdate("UPDATE organization SET parentorganizationid = ? WHERE id != ?", false, array($rootOrganization->id, $rootOrganization->id));
			}

			Query("COMMIT");
		}

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