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

	/**
	 * Custom constructor sets up the form data that we need
	 *
	 */
	function SearchForm($name, $rawdata) {

		// TODO - Plug the $rawdata into the $formitems' values as needed
		$formitems = array(
			'terms' => array(
				'label' => _L('Search'),
				'value' => '',
				'validators' => array(),
				'control' => array('TextField', 'size' => 30, 'maxlength' => 51),
				'helpstep' => 1
			)
		);

		$helpsteps = array ();

		$buttons = array(
			submit_button(_L('Search'), 'submit', 'tick')
		);

		parent::Form($name, $formitems, $helpsteps, $buttons);
	}

	/**
	 * Custom handling for the form submission
	 *
	 * Return boolean false if we want to continue processing in
	 * Page::afterLoad() following the Form::handleRequest() operation
	 * (which allows us to return a JSON response to the client)
	 *
	 * @param string $button The name of the form button that was clicked to
	 * submit the form
	 * @param array $formData The pre-validated data submitted with the form
	 *
	 * @return mixed string location to redirect to after submission handling
	 * is done, or boolean false to return to caller without redirection
	 */
	function handleSubmit($button, $formData) {
		//Query('BEGIN');
		// TODO: Save form data
		//Query('COMMIT');

		// Where do we want the client to be sent after submission?
		return(false);
	}
}


// -----------------------------------------------------------------------------
// CUSTOM PAGE FUNCTIONALITY
// -----------------------------------------------------------------------------

class TemplatePage extends PageMultiPart {

	function isAuthorized($get, $post) {
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

			// If the form was submitted...
			if ($this->form->getSubmit()) {

				// Use our form-submitted search terms to find messages
				$formData = $this->form->getData();
				$query = "
					SELECT
						mg.id,
						mg.name
					FROM
						messagegroup mg
					WHERE
						mg.name like ?
						AND ! mg.deleted;
				";
				$messageGroups = QuickQueryList($query, true, false, array("%{$formData['terms']}%"));

				// Return some JSON data for what the form submitted
				header('Content-Type: application/json');
				print json_encode($messageGroups);
				exit;
			}
		}
	}
		


	function beforeRender() {
		// Do some extra work in the database to determine how many days are left `til Christmas
		$this->days = 50;
	}

	function render() {
		$html = "Find a message group by name:<br/>\n";
		$html .= $this->form->render();
		$this->addPart('Search', $html);

		// If any search terms were supplied
		if (strlen($this->data['terms'])) {
			// Show some results in a separate part
			$this->addPart('Results', "Search results for: '{$this->data['terms']}'");
		}


		$this->addPart('Results', "Some super-useful search results would go here.");

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

executePage($page);

