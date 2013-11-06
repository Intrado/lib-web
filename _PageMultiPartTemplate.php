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
require_once('obj/PageMultiPart.obj.php');

// -----------------------------------------------------------------------------
// CUSTOM FORM FOR THIS PAGE
// -----------------------------------------------------------------------------

class SearchForm extends Form {

	function SearchForm($name, $rawdata) {
		$formdata = array(
			'terms' => array(
				'label' => _L('Search'),
				'value' => '',
				'validators' => array(),
				'control' => array('TextField', 'size' => 30, 'maxlength' => 51, 'autocomplete' => 'test"'),
				'helpstep' => 1
			)
		);

		$helpsteps = array ();

		$buttons = array(
			submit_button(_L('Search'), 'submit', 'tick')
		);

		parent::Form($name, $formdata, $helpsteps, $buttons);
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

class TemplatePage extends PageMultiPart {

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
		$this->data['terms'] = isset($post['terms']) ? intval($post['terms']) : '';
	}

	function load() {
		$this->form = new SearchForm($this->options['formname'], $this->data);

	}

	function afterLoad() {

		// If we have a primary form
		if (strlen($this->options['formname'])) {

			// Submit the form (if it is a submission!)
			$this->form->handleRequest(); // Calls $this->form->handleSubmit()
		}
	}
		


	function beforeRender() {
		// Do some extra work in the database to determine how many days are left `til Christmas
		$this->days = 50;
	}

	function render() {
		$html = "Find something very interesting here:<br/>\n";
		$html .= $this->form->render();
		$this->addPart('Search', $html);

		// If any search terms were supplied
		if (strlen($this->data['terms'])) {
			// Show some results in a separate part
			$this->addPart('Results', "Search results for: '{$this->data['terms']}'");
		}
		
		return('');
	}
}


// -----------------------------------------------------------------------------
// PAGE INSTANTIATION AND DISPLAY
// -----------------------------------------------------------------------------

$page = new TemplatePage(Array(
	'formname' => 'searchform',
	'validators' => Array('ValTemplateItem')
));

$page->execute();

