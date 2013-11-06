<?

require_once('inc/common.inc.php');
require_once('inc/securityhelper.inc.php');
require_once('inc/table.inc.php');
require_once('inc/html.inc.php');
require_once('inc/utils.inc.php');
require_once('obj/Validator.obj.php');
require_once('obj/Form.obj.php');
require_once('obj/FormItem.obj.php');


require_once('ifc/Page.ifc.php');
require_once('obj/PageBase.obj.php');
require_once('obj/PageForm.obj.php');


// -----------------------------------------------------------------------------
// CUSTOM VALIDATORS
// -----------------------------------------------------------------------------

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


// -----------------------------------------------------------------------------
// CUSTOM FORM ITEMS
// -----------------------------------------------------------------------------

// TODO - move this guy's javascript component to its own method where it belogs
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




// -----------------------------------------------------------------------------
// CUSTOM FORM FOR THIS PAGE
// -----------------------------------------------------------------------------

class TemplateForm extends Form {

	function TemplateForm($name, $rawdata) {
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

		$buttons = array(
			submit_button(_L('Save'), 'submit', 'tick'),
			icon_button(_L('Cancel'), 'cross', null, 'start.php')
		);

		parent::Form($name, $formdata, $helpsteps, $buttons, 'vertical');
	}

	function handleSubmit($button, $data) {
		//Query('BEGIN');
		// TODO: Save form data
		//Query('COMMIT');

		// Where do we want the client to be sent after submission?
		return('start.php');
	}
}


// -----------------------------------------------------------------------------
// CUSTOM FORMATTERS
// -----------------------------------------------------------------------------

// TODO: Make better examples of working formatters (extend Formatters.obj.php?)
function fmt_template ($obj, $field) {
	return $obj->$field;
}


// -----------------------------------------------------------------------------
// CUSTOM PAGE FUNCTIONALITY
// -----------------------------------------------------------------------------

class TemplatePage extends PageForm {

	function is_authorized($get, $post) {
		return(true); // open to the world, unconditionally!
	}

	function beforeLoad($get, $post) {

		// Special case for handling deletions
		if (isset($get['deleteid'])) {
			// best practice: use transaction whenever modifying data
			// (around whole section or logical atomic block)
			//Query("BEGIN");
			//FooDBMO::delete($get['deleteid']);
			//Query("COMMIT");
			retirect();
		}
		// Any other special case. early-exit operations needed for our page?

		// Set up data that we're going to need for the form
		$this->data['number'] = isset($post['number']) ? intval($post['number']) : 0;
	}

	function load() {
		$this->form = new TemplateForm($this->options['formname'], $this->data);

	}

	function beforeRender() {
		// Do some extra work in the database to determine how many days are left `til Christmas
		$this->days = 50;
	}

	function render() {
		$html = "There are {$this->days} days left `til Christmas!<br/><br/>\n{$this->form->render()}";
		return($html);
	}
}


// -----------------------------------------------------------------------------
// PAGE INSTANTIATION AND DISPLAY
// -----------------------------------------------------------------------------

$page = new TemplatePage(Array(
	'formname' => 'templateform',
	'validators' => Array('ValTemplateItem')
));

$page->execute();

