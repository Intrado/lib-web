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
			return $this->label . " " . _L("This organization key already exists. Please enter a unique organizaton key");
		return true;
	}
}

////////////////////////////////////////////////////////////////////////////////
// Form Data
////////////////////////////////////////////////////////////////////////////////

$formdata = array(
	"neworg" => array(
		"label" => _L('Organization Key'),
		"fieldhelp" => _L("TODO: Help describing new org key generation"),
		"value" => "",
		"validators" => array(
			array("ValRequired"),
			array("ValOrgKey"),
			array("ValLength","min" => 3,"max" => 255)
		),
		"control" => array("TextField","size" => 30, "maxlength" => 255),
		"helpstep" => 1
	)
);

$helpsteps = array (
	_L('TODO: Help describing new org key generation')
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
		
		$org = new Organization();
		$org->orgkey = $postdata['neworg'];
		Query("BEGIN");
		$org->create();
		Query("COMMIT");
		
		if ($ajax)
			$form->sendTo("organizationdatamanager.php");
		else
			redirect("organizationdatamanager.php");
	}
}

////////////////////////////////////////////////////////////////////////////////
// Display Functions
////////////////////////////////////////////////////////////////////////////////

function fmt_template ($obj, $field) {
	return $obj->$field;
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

startWindow(_L('Create Organization Key'));
echo $form->render();
endWindow();
include_once("navbottom.inc.php");
?>