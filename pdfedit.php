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

require_once('obj/Burst.obj.php');


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

class PDFEditPage extends PageForm {

	var $burst = null;		// DBMO for the burst record we're working on
	var $burstId = null;		// ID for the DBMO object to interface with
	var $burstTemplates = array();	// An array to collect all the available burst templates into
	function PDFEditPage() {
		$options = array();

		parent::PageForm($options);
	}

	function isAuthorized($get, $post, $request, $session) {
		return(true); // open to the world, unconditionally!
	}

	function beforeLoad($get, $post, $request, $session) {

		// The the query string has a burst ID specified, then grab it
		$this->burstId = (isset($get['id']) && intval($get['id'])) ? intval($get['id']) : null;

		// Special case for handling deletions
		if (isset($get['deleteid'])) {
			// best practice: use transaction whenever modifying data
			// (around whole section or logical atomic block)
			//Query("BEGIN");
			//FooDBMO::delete($get['deleteid']);
			//Query("COMMIT");
			redirect();
		}
		// Any other special case. early-exit operations needed for our page?
	}

	function load($get, $post, $request, $session) {

		// Make the burst DBMO
		$this->burst = new Burst($this->burstId);

		// Get a list of burst templates
		$this->loadBurstTemplates();

		// Make the edit FORM
		$this->form = $this->factoryFormPDFUpload();
	}

	function loadBurstTemplates() {
		$res = Query("
			SELECT
				`id`,
				`name`
			FROM
				`bursttemplate`
			WHERE
				NOT `deleted`;
		");

		if (is_object($res)) {
			while ($row = DBGetRow($res, true)) {
				$this->burstTemplates[$row['id']] = $row['name'];
			 }
		}
	}


	function factoryFormPDFUpload() {
		$formdata = array(
			"pdfname" => array(
				"label" => _L('Name'),
				"value" => "",
				"validators" => array(
					array("ValLength","min" => 3,"max" => 50)
				),
				"control" => array("TextField","size" => 30, "maxlength" => 50, "autocomplete" => "test"),
				"helpstep" => 1
			),
			"bursttemplate" => array(
				"label" => _L('Template'),
				"value" => "",
				"validators" => array(),
				"control" => array("SelectMenu", "values" => $this->burstTemplates),
				"helpstep" => 2
			)
		);

                $helpsteps = array (
			_L('Templatehelpstep 1'),
			_L('Templatehelpstep 2'),
			_L('Templatehelpstep 3')
		);

		$buttons = array(
			submit_button(_L('Upload'), 'submit', 'tick'),
			icon_button(_L('Cancel'), 'cross', null, 'pdfmanager.php')
		);

		return(new Form('pdfuploader', $formdata, $helpsteps, $buttons, 'vertical'));
	}

	function afterLoad() {
		$this->form->handleRequest();
		$this->options['title'] = ($this->burstId) ? _L('Edit PDF Properties') : _L('Upload New PDF');
	}

	function beforeRender() {
		// Do some extra work in the database to determine how many days are left `til Christmas
		$this->days = 50;
	}

	function render() {
		if ($this->burstId && ! $this->burst->isCreated()) {
			$html = "There is no PDF on file with the requested ID.<br/>\n";
		}
		else {
			$html = "{$this->form->render()}";
		}

/*
		if (count($this->burstTemplates)) {
			$html .= "Templates:<br/>\n<ul>\n";
			foreach ($this->burstTemplates as $id => $name) {
				$html .= "<li> {$id} - '{$name}'</li>\n";
			}
			$html .= "</ul>\n\n";
		}
*/
		return($html);
	}
}


// -----------------------------------------------------------------------------
// PAGE INSTANTIATION AND DISPLAY
// -----------------------------------------------------------------------------

executePage(new PDFEditPage());

