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
if (!$USER->authorize('managesystem')) {
	redirect('unauthorized.php');
}


////////////////////////////////////////////////////////////////////////////////
// Action/Request Processing
////////////////////////////////////////////////////////////////////////////////

if (isset($_GET['deleteid'])) {
	//...
}

////////////////////////////////////////////////////////////////////////////////
// Optional Form Items And Validators
////////////////////////////////////////////////////////////////////////////////

// Example of a custom form FormItem
class TemplateItem extends FormItem {
	function render ($value) {
		$n = $this->form->name."_".$this->name;
		if($value == null || $value == "") // Handle empty value to combind this validator with ValRequired
			$value = array("left" => "false","right" => "false");
		// edit input type from "hidden" to "text" to debug the form value
		$str = '<input id="'.$n.'" name="'.$n.'" type="hidden" value="'.escapehtml(json_encode($value)).'"/>';
		$str .= '<input id="'.$n.'left" name="'.$n.'left" type="checkbox" onchange="setValue_'.$n.'()" value="" '. ($value["left"] == "true" ? 'checked' : '').' />';
		$str .= '<input id="'.$n.'right" name="'.$n.'right" type="checkbox" onchange="setValue_'.$n.'()" value="" '. ($value["right"] == "true" ? 'checked' : '').' />';
		$str .= '<script>function setValue_'.$n.'(){
								$("'.$n.'").value = Object.toJSON({
									"left": $("'.$n.'left").checked.toString(),
									"right": $("'.$n.'right").checked.toString()
							});
							form_do_validation($("' . $this->form->name . '"), $("' . $n . '"));
						 }
				</script>';		
		return $str;
	}
}

// Example of a custom form Validator
class ValTemplateItem extends Validator {
	function validate ($value, $args) {
		if(!is_array($value)) {
			$value = json_decode($value,true);
		}
		if (!($value["left"] == "true" || $value["right"] == "true"))	
			return "One item is required for " . $this->label;
		else
			return true;

	}
	function getJSValidator () {
		return 
			'function (name, label, value, args) {			
				checkval = value.evalJSON();
				if (!(checkval.left == "true" || checkval.right == "true"))
					return "One item is required for " + label;
				return true;
			}';
	}
}

////////////////////////////////////////////////////////////////////////////////
// Form Data
////////////////////////////////////////////////////////////////////////////////

$formdata = array(
	_L('Template Section 1'), // Optional
	"templatetextfield" => array(
		"label" => _L('TextField'),
		"value" => "a",
		"validators" => array(
			array("ValLength","min" => 3,"max" => 50)
		),
		"control" => array("TextField","size" => 30, "maxlength" => 51, "autocomplete" => "test"),
		"helpstep" => 1
	),
	_L('Template Section 2'), // Optional
	"templatecheckbox" => array(
		"label" => _L('Checkbox'),
		"value" => false,
		"validators" => array(
			array("ValRequired")
		),
		"control" => array("CheckBox"),
		"helpstep" => 2
	),
	"templateitem" => array( 
		"label" => _L('TemplateItem'),
		"value" => "",//array("left" => "true","right" => "false"),
		"validators" => array(array("ValRequired"),array("ValTemplateItem")),
		"control" => array("TemplateItem"),
		"helpstep" => 3
	)
);

$helpsteps = array (
	_L('Templatehelpstep 1'),
	_L('Templatehelpstep 2'),
	_L('Templatehelpstep 3')
);

$buttons = array(submit_button(_L('Save'),"submit","tick"),
				icon_button(_L('Cancel'),"cross",null,"start.php"));
$form = new Form("templateform",$formdata,$helpsteps,$buttons, "vertical");

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
		
		//save data here	
		
		
		Query("COMMIT");
		if ($ajax)
			$form->sendTo("start.php");
		else
			redirect("start.php");
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
$PAGE = "template:template";
$TITLE = _L('template');

include_once("nav.inc.php");

// Optional Load Custom Form Validators
?>
<script type="text/javascript">
<? Validator::load_validators(array("ValTemplateItem")); ?>
</script>
<?

startWindow(_L('template'));
echo $form->render();
endWindow();

include_once("navbottom.inc.php");
?>