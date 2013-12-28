<?

/**
 * Base class for single-form based pages
 *
 * See implementation notes in PageBase
 *
 * DEPENDENCIES
 * obj/PageBase.obj.php
 */
abstract class PageForm extends PageBase {

	public $form = null; // This is where the Form.obj will be instantiated

	function __construct($options = array()) {

		// Layer in support for 'validators' as options for Form-based pages
		$newoptions = array(
			'validators' => array()
		);

		// Then merge defaults with the options provided
		if (is_array($options) && count($options)) {
			$newoptions = array_merge($newoptions, $options);
		}

		parent::__construct($newoptions);
	}


	/**
	 * Load base data needed to handle submission
	 *
	 * All data required from the database, anything needed to drive a form
	 * must all be loaded at this time and by this method. If it was a form
	 * submission, we still need to load everything as if we were going to
	 * display it - this allows us to verify that the state of everything
	 * when they first pulled the edit form is still the same which permits
	 * the submission to to proceed. By the time we leave here there should
	 * be nothing left to be discovered to save the submitted data.
	 */
	function load() {

		// By default this method instantiates an empty form; the
		// derived class must implement this method to show a useful
		// form.
		if (strlen($this->options['formname'])) {

			// Initialize some bogus data
			$formdata = $helpsteps = $buttons = array();

			// And instantiate a form object
			$this->form = new TemplateForm($this->options['formname'], $formdata, $helpsteps, $buttons, 'vertical');
		}
	}

	/**
	 * Handle any processing needed after a data load
	 *
	 * Anything after loading the base data necessary for form submission
	 * and related to the actual process of submitting the form goes here.
	 *
	 * This method *may* be overridden, however its function is so basic
	 * that in most cases it will probably suffice as is. It will likely
	 * need an override for anypage that has more than one form on it...
	 */
	function afterLoad() {
		// If we have a primary form
		if (strlen($this->options['formname'])) {

			// Submit the form (if it is a submission!)
			$this->form->handleRequest(); // Calls $this->form->handleSubmit()
		}
	}

	/**
	 * After handling the form, load anything else needed
	 *
	 * Perhaps the page shows some information on it that has to be pulled
	 * from the database or some other source, but which is not pertinent
	 * to processing form submissions. If there are no special requirements
	 * then this stub will suffice, otherwise the derived class must
	 * override and implement.
	 *
	 * This happens just ahead of rendering the form and passing the form
	 * output to the final show() method.
	 */
	function beforeRender() {
		// By default we will do nothing with the data; the derived class
		// must implement this method to be able to show anything other
		// than the native form data on the page
	}

	/**
	 * Make some HTML to push into the page
	 *
	 * Any page wanting to show more than just a single form as the output
	 * would need to override this method to render whatever HTML it wants
	 * to output.
	 */
	function render() {
		$html = $this->form->render();
		return($html);
	}

	/**
	 * Override default snedPageOutput to include custom validators for forms
	 */
	function sendPageOutput() {
		// Optionally load extra form validators
		if (is_array($this->options['validators']) && count($this->options['validators'])) {
			print '<script type="text/javascript">';
			Validator::load_validators($this->options['validators']);
			print "</script>\n";
		}

		// Then use the default handling as normal
		parent::sendPageOutput();
	}
}

