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
	// validate and set the orgkey
	$originalorgid = $_GET['orgid'];
	$originalorgkey = QuickQuery("select orgkey from organization where id = ?", false, array($originalorgid));
} else {
	redirect("organizationdatamanager.php");
}

if (!$originalorgkey)
	redirect('unauthorized.php');

////////////////////////////////////////////////////////////////////////////////
// Optional Form Items And Validators
////////////////////////////////////////////////////////////////////////////////

class ValOrgKey extends Validator {
	var $onlyserverside = true;
	function validate ($value, $args) {
		// if they are just renaming to change case, allow it
		if (mb_strtolower($value) == mb_strtolower($args['originalorgkey']))
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

$formdata = array(
	"origorg" => array(
		"label" => _L('Original Name'),
		"fieldhelp" => _L("This is the original organization name."),
		"control" => array("FormHtml", "html" => $originalorgkey),
		"helpstep" => 1
	),
	"neworg" => array(
		"label" => _L('New Name'),
		"fieldhelp" => _L("Enter a new name for this organization."),
		"value" => "",
		"validators" => array(
			array("ValRequired"),
			array("ValOrgKey", "originalorgkey" => $originalorgkey),
			array("ValLength", "min" => 1,"max" => 255)
		),
		"control" => array("TextField","size" => 30, "maxlength" => 255),
		"helpstep" => 1
	)
);

$helpsteps = array (
	_L('Enter a new name for this organization. It should clearly indicate what the organization is.')
);

$buttons = array(submit_button(_L('Save'),"submit","tick"),
				icon_button(_L('Cancel'),"cross",null,"organizationdatamanager.php"));
$form = new Form("templateform",$formdata,$helpsteps,$buttons);

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
		
		$orgkey = trim($postdata['neworg']);
		Query("BEGIN");

		$originalorg = DBFind("Organization", "from organization where id = ? and not deleted", false, array($originalorgid));
		
		$existingorg = DBFind("Organization", "from organization where orgkey = ?", false, array($orgkey));
		// if the org already exists make it our target org
		if ($existingorg) {
			$org = $existingorg;
			$org->orgkey = $orgkey;
			$org->deleted = 0;
			$org->update();
						
		// if it's a new org then crete a new one
		} else {
			$org = new Organization();
			$org->orgkey = $orgkey;
			$org->create();
		}
		
		// if the original org and the new org are not the same, update associations and delete the original
		if ($org->id !== $originalorg->id) {
			QuickUpdate("update userassociation set organizationid = ? where organizationid = ?", false, array($org->id, $originalorgid));
			QuickUpdate("update personassociation set organizationid = ? where organizationid = ?", false, array($org->id, $originalorgid));
			QuickUpdate("update listentry set organizationid = ? where organizationid = ?", false, array($org->id, $originalorgid));

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
		

		notice(_L('%1$s has been renamed to %2$s.', $originalorgkey, $orgkey));
		
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
$TITLE = _L('Rename Organization');

include_once("nav.inc.php");

// Optional Load Custom Form Validators
?>
<script type="text/javascript">
<? Validator::load_validators(array("ValOrgKey")); ?>
</script>
<?

startWindow(_L('Settings'));
echo $form->render();
endWindow();
include_once("navbottom.inc.php");
?>