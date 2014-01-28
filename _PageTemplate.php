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

// TODO - move this guy's javascript component to its own method where it belongs
// also this->form->name is entirely invalid...
class TemplateItem extends FormItem {
	function render ($value) {
		$n = $this->form->name."_".$this->name;
		if($value == null || $value == "") // Handle empty value to combind this validator with ValRequired
			$value = array("left" => "false","right" => "false");
		// edit input type from "hidden" to "text" to debug the form value
		$str = '<input id="'.$n.'" name="'.$n.'" type="hidden" value="'.escapehtml(json_encode($value)).'"/>';
		$str .= '<input id="'.$n.'left" name="'.$n.'left" type="checkbox" onchange="setValue_'.$n.'()" value="" '. ($value["left"] == "true" ? 'checked' : '').' />';
		$str .= '<input id="'.$n.'right" name="'.$n.'right" type="checkbox" onchange="setValue_'.$n.'()" value="" '. ($value["right"] == "true" ? 'checked' : '').' />';
		$str .= '<script>
				function setValue_'.$n.'(){
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
// CUSTOM FORMATTERS
// -----------------------------------------------------------------------------

// TODO: Make better examples of working formatters (extend Formatters.obj.php?)
function fmt_template ($obj, $field) {
	return $obj->$field;
}


// -----------------------------------------------------------------------------
// CUSTOM PAGE FUNCTIONALITY
// -----------------------------------------------------------------------------

// TODO - replace 'TEMPLATE' occurrences with your own fancy jargony name
class TemplatePage extends PageForm {

	private $id = null; // The ID of the TEMPLATE thing that our form is for

	private $data1;
	private $data2;
	private $data3;

	public $formName = 'ourcustomformname';

	function isAuthorized(&$get=array(), &$post=array(), &$request=array(), &$session=array()) {
		return(true); // open to the world, unconditionally!
	}

	function beforeLoad(&$get=array(), &$post=array(), &$request=array(), &$session=array()) {

                if (isset($request['id']) && intval($request['id'])) {

			// Peel the ID off the URL, stash it in the session...
			$session['TEMPLATEid'] = intval($request['id']);

			// .. then redirect back to ourselves to clean up the URL
			redirect();

		} else if (isset($session['TEMPLATEid'])) {

			$this->id = $session['TEMPLATEid'];
		} else {
			$this->id = null;
		}
			
		// Special case for handling deletions - WARNING: this is NOT the idempotent way...
		if (isset($get['deleteid'])) {
			// best practice: use transaction whenever modifying data
			// (around whole section or logical atomic block)
			//Query("BEGIN");
			//FooDBMO::delete($get['deleteid']);
			//Query("COMMIT");
			redirect();
		}
	}

	function load(&$get=array(), &$post=array(), &$request=array(), &$session=array()) {

                // If we're editing an existing one, get its data
		if (! is_null($this->id)) {

			// Pull in current data for this TEMPLATE record
			list($this->data1, $this->data2, $this->data3) = array(1,2,3);
		}
		else {
			$this->data1 = 1;
			$this->data2 = 1;
			$this->data3 = 1;
		}

		// If there was a data reload issue
		if (! is_null($this->burstId)) {

			// If we have been flagged as having reloaded on account of data having changed...
			if (isset($session['TEMPLATEreload'])) {

				// Clear the flag and set the error message; this will allow the message to display, but
				// upon dismissal show the form repopulated with the freshly loaded burst data from above
				unset($session['TEMPLATEreload']);

				// Add this error message to the page output, but still
				// allow the form to be displayed to take edits and resubmit
				$this->error = _L("This TEMPLATE was changed in another window or session during the time you've had it open. Please review the current data. You may need to redo your changes and resubmit.");
			}
		}


		$this->form = $this->factoryTemplatePageForm();
	}

	function afterLoad() {

		// Normal form handling makes form->getData() work...
		$this->form->handleRequest();

		// If the form was submitted...
		if ($this->form->getSubmit()) {

			// Check if the data has changed and display a notification if so...
			if (! is_null($this->id) && $this->form->checkForDataChange()) {

				// Flag the problem, then redirect back to ourselves to redisplay the form with now-current data
				$_SESSION['TEMPLATEreload'] = true;
				redirect("?id={$this->id}");
			}

			// Check for validation errors
			$action = (is_null($this->burstId)) ? 'created' : 'updated';
			if (($errors = $this->form->validate()) === false) {

				// TODO store a new record if id is null, otherwise update an existing record
				if (true) {

					// For success, we redirect back to some list page with this notice to be shown on that page:
					unset($_SESSION['TEMPLATEid']);
					notice(_L("The TEMPLATE was successfully stored."));
				}
				else {
					notice(_L("The TEMPLATE failed to be stored correctly."));
				}

				redirect('somepage.php');
			}
		}
	}

	function beforeRender() {
		// Do any additional work needed to prepare for rendering a page
		$this->date = date('Y-m-d');
	}

	function render() {

		// Note: This is the title of the page. It should not also be the header of the form "window".
		// This 'title' is set to global $TITLE in the base class.
		$this->options['title'] = _L('TEMPLATEs');
		$this->options['windowTitle'] = _L('Editing TEMPLATE: ') . $this->id;

		$html = sprintf(_L("Today's date is %s"), $this->date) . "<br/><br/>\n";
		$html .= parent::render();
		return($html);
	}

	function factoryTemplatePageForm() {
		$formdata = array(
			_L('Template Section 1'), // Optional
			"templatetextfield" => array(
				"label" => _L('TextField'),
				"value" => $this->data1,
				"validators" => array(
					array("ValLength","min" => 3,"max" => 50)
				),
				"control" => array("TextField","size" => 30, "maxlength" => 51, "autocomplete" => "test"),
				"helpstep" => 1
			),
			_L('Template Section 2'), // Optional
			"templatecheckbox" => array(
				"label" => _L('Checkbox'),
				"value" => $this->data2,
				"validators" => array(
					array("ValRequired")
				),
				"control" => array("CheckBox"),
				"helpstep" => 2
			),
			"templateitem" => array(
				"label" => _L('TemplateItem'),
				"value" => $this->data3,
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

		$form = new Form($this->formName, $formdata, $helpsteps, $buttons);
		$form->ajaxsubmit = true; // Set to false if your form can't be handled via AJAX submission

		return($form);
	}
}


// -----------------------------------------------------------------------------
// PAGE INSTANTIATION AND DISPLAY
// -----------------------------------------------------------------------------

$page = new TemplatePage(Array(
	'formname' => 'templateform',
	'validators' => Array('ValTemplateItem')
));

executePage($page);

