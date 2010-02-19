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
	// validate and set the org id
	$sourceOrg = DBFind("Organization", "from organization where id = ? and not deleted", false, array($_GET['orgid']));
} else {
	redirect("organizationdatamanager.php");
}

if (!$sourceOrg)
	redirect('unauthorized.php');

////////////////////////////////////////////////////////////////////////////////
// Optional Form Items And Validators
////////////////////////////////////////////////////////////////////////////////

class MergeOrganizations extends FormItem {
	function render ($value) {
		$n = $this->form->name."_".$this->name;
		$data = isset($this->args['data'])?$this->args['data']:array();
		$vals = json_decode($value);
		// get the source org
		$sourceOrgKey = isset($this->args['sourceorgkey'])?$this->args['sourceorgkey']:"";
		$sourceOrgId = isset($this->args['sourceorgid'])?$this->args['sourceorgid']:"";
		
		// input, just used for validation
		$str = '<input id="'.$n.'" name="'.$n.'" type="hidden" value="'.escapehtml($value).'"/>';
		
		// source org
		$str .= 
		'<div style="padding: 3px;">
			<div style="float: left; padding: 3px; font-size: 12px; border: 1px solid gray">
				' . $sourceOrgKey . '
			</div>';
		// dest org
		$str .= 
		'	<div style="float: left; padding: 2px; font-size: 14px;">-></div>
			<div style="float: left; padding: 1px">
				<select onchange="$(\''.$n.'\').value = Object.toJSON(['.$sourceOrgId.', this.value]);form_do_validation($(\''.$this->form->name.'\'), $(\''.$n.'\'));">
					<option value="0">-- '._L("Select One").' --</option>';
		foreach ($data as $id => $orgkey)
			$str .= '<option value="'. $id .'" >'.escapehtml($orgkey).'</option>';
		$str .= 
		'		</select>
			</div>
			<div style="clear:both"></div>
		</div>';
		return $str;
	}
}

class ValOrgs extends Validator {
	var $onlyserverside = true;
	function validate ($value) {
		$vals = json_decode($value);
		
		// verify that the orgs are valid
		$srcorg = DBFind("Organization", "from organization where id = ? and not deleted", false, array($vals[0]));
		$dstorg = DBFind("Organization", "from organization where id = ? and not deleted", false, array($vals[1]));
		// if there is a valid source org and the destination org
		if ($srcorg && $dstorg)
			return true;
		return $this->label . " " . _L("has invalid organization data selected");
	}
}

////////////////////////////////////////////////////////////////////////////////
// Form Data
////////////////////////////////////////////////////////////////////////////////

// get all the current non deleted orgs (except the source org)
$data = QuickQueryList("select id, orgkey from organization where id != ? and not deleted order by orgkey, id", true, false, array($sourceOrg->id));

$formdata = array(
	"mergeorg" => array(
		"label" => _L('Organization Target'),
		"fieldhelp" => _L("TODO: Help describing org merge"),
		"value" => "",
		"validators" => array(
			array("ValRequired"),
			array("ValOrgs")
		),
		"control" => array("MergeOrganizations", "data" => $data, "sourceorgid" => $sourceOrg->id, "sourceorgkey" => $sourceOrg->orgkey),
		"helpstep" => 1
	)
);

$helpsteps = array (
	_L('TODO: Help describing org merge')
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
		
		$orgs = json_decode($postdata['mergeorg']);
		
		// validate source and target orgs
		$source = DBFind("Organization", "from organization where id = ? and not deleted", false, array($orgs[0]));
		$dest = DBFind("Organization", "from organization where id = ? and not deleted", false, array($orgs[1]));
		
		if ($source && $dest) {
			Query("BEGIN");
			QuickUpdate("update userassociation set organizationid = ? where organizationid = ?", false, array($dest->id, $source->id));
			QuickUpdate("update listentry set organizationid = ? where organizationid = ?", false, array($dest->id, $source->id));
			Query("COMMIT");
		}
		
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
$TITLE = _L('Merge Organization');

include_once("nav.inc.php");

// Optional Load Custom Form Validators
?>
<script type="text/javascript">
<? Validator::load_validators(array("ValOrgs")); ?>
</script>
<?

startWindow(_L('Select Target Organization'));
echo $form->render();
endWindow();
include_once("navbottom.inc.php");
?>