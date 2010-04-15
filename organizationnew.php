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

////////////////////////////////////////////////////////////////////////////////
// Optional Form Items And Validators
////////////////////////////////////////////////////////////////////////////////

class ValOrgKey extends Validator {
	var $onlyserverside = true;
	function validate ($value) {
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
	"neworg" => array(
		"label" => _L('Organization Name'),
		"fieldhelp" => _L("Enter a unique name for the new organization."),
		"value" => "",
		"validators" => array(
			array("ValRequired"),
			array("ValOrgKey"),
			array("ValLength","min" => 1,"max" => 255)
		),
		"control" => array("TextField","size" => 30, "maxlength" => 255),
		"helpstep" => 1
	)
);

$helpsteps = array (
	_L('Enter a unique name for the new organization. It should clearly indicate what the organization is.')
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
		// check if this org already exists
		$existingorg = DBFind("Organization", "from organization where orgkey = ?", false, array($orgkey));
		
		// if the org exists
		if ($existingorg) {
			// if it's deleted, undelete it
			if ($existingorg->deleted) {
				$existingorg->deleted = 0;
				$existingorg->update();
				notice(_L("%s was un-deleted.", $orgkey));
			} else {
				notice(_L("%s already exists.", $orgkey));
			}
		} else {
			// create a new organization
			$org = new Organization();
			$org->orgkey = $orgkey;
			$org->create();
			notice(_L("%s has been created.", $orgkey));
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
$TITLE = _L('New Organization');

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