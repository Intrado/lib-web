<?

/**
 * Abstract base class for pages
 *
 * Derived classes inherit much, but not all, of the code required to deliver a
 * given page to the browser. There are some functions of a page that are quite
 * page-specific and therefore must be implemented in the derived class. Some
 * things are optional, and some things are required. For the optional things
 * we provide stubs that do little or nothing allowing the derived class to
 * implement if desired and automatically get picked up, but they are not
 * required to be implemented. This is handled with a stub method here which
 * the derived class would override with desired functionality. There are also
 * some skeletal implementations of some methods which the derived class should
 * override but then probably call parent::method_name() to get that method's
 * base implementation either before or after doing some of its work to retain
 * the default behavior and not have to re-implement it.
 *
 * is_authorized()
 *
 * Each PageBase allows the caller to supply some options to control the behavior
 * of the page implementation. There are some predefined options with established
 * defaults, but the derived class may also leverage the mechanism to accept any
 * number of custom options that it needs. This allows us to offer infinitely
 * variable constructor options in a uniform manner. If the derived class offers
 * additional options, the options can simply be passed into the constructor by
 * the caller and the derived class can access them with the get_option() method.
 *
 * The predefined options are as follows:
 *
 * 'testmode' - boolean; false (default) to disable test mode; true disables some
 * 	some pieces of the implementation in order to facilitate test isolation
 * 	(such as not sending output headers or including external source files,
 * 	etc.)
 * 'noauth_redirect' - string; URL to redirect the client to if the user is not
 * 	authorized to view/access the requested page/action.
 * 'title' - The literal, readable string title of the page.
 * 'page' - The primary:secondary navigation tabs to indicate as selected when
 * 	showing this page to show its relationship to other pages in the nav.
 * 'formname' - The name for the primary form that we're going to be working on
 * 	If it is blank, then the default implementation will not initialize or
 * 	invoke form-related opteraions.
 */
class PageBase {

	/**
	 * All our operational options, protectively set, publicly readable
	 */
	var $options;
	var $form;
	var $konadir;

	/**
	 * Base constructor; just manages options 
	 */
	function PageBase($options = Array()) {

		// Figure out the base directory for includes
		$this->konadir = dirname(dirname(__FILE__));

		// Set some default options
		$this->options = Array(
			'testmode' => 'false',
			'noauth_redirect' => 'unauthorized.php',
			'title' => 'Default Page Title',
			'page' => 'default:default',
			'formname' => '',
			'validators' => Array()
		);

		// Then merge in the options provided
		$this->options = array_merge($options);
	}

	/**
	 * Wrap the request handler's output with the full navigation chrome/template
	 *
	 * Sends output directly to stdout!
	 */
	function show() {
		global $PAGE, $TITLE;

		// First handle the request; this might exit early with a redirect
		$pageOutput = $this->handleRequest($_GET, $_POST);

		$PAGE = $this->options['page'];
		$TITLE = _L($this->options['title']);
		include_once("{$this->konadir}/nav.inc.php");

		// Optionally load extra form validators
		if (is_array($this->options['validators']) && count($this->options['validators'])) {
			print '<script type="text/javascript">' . Validator::load_validators($this->options['validators']) . "</script>\n";
		}

		startWindow($TITLE);
		echo $pageOutput;
		endWindow();

		include_once("{$this->konadir}/navbottom.inc.php");
	}

	/**
	 * Render and deliver the output for this page
	 *
	 * Ideally, all output from the page, headers excluded, would be passed
	 * back to the caller through this method. This allows 100% of the HTML
	 * page content to be captured in a single operation where the result
	 * may then be redirected to a file, sent to standard out, or processed
	 * for testing to verify its output. In reality, there is quite a bit
	 * of code within the application that directly echoes/prints to stdout
	 * which would prevent this method from buffering it. Over time, such
	 * mechanisms should be converted to assemble a page bit by bit and not
	 * output directlry.
	 *
	 * An exception to this would be for "pages" that contain large data
	 * sets such as CSV output where it would be detrimental to the system
	 * performance to buffer all that data in memory before sending to
	 * stdout.
	 *
	 * @return mixed HTML output string for the requested page, or nothing
	 * if it was a form submission that redirected and exited.
	 */
	function handleRequest($get, $post) {

		// Check authorization
		if (! $this->is_authorized($get, $post)) {

			// Redirect if unauthorized
			$location = $this->options['noauth_redirect'];

			// In test mode we don't want to send redirect
			// headers. Just return some output instead
			if ($this->options['testmode']) {
				return("REDIRECT: $location\n");
			}

			// Send headers and exit
			redirect($location);
		}

		return($this->process($get, $post));
	}

	/**
	 * Determine whether the user is authorized to access the functions of this page
	 *
	 * This method must be overridden in the derived class; by default, access is
	 * denied! There are any number of different factors that could be taken into
	 * consideration to determine whether the request is authorized. The method
	 * accepts get and post data from the caller which could come from the super
	 * globals or be stubbed/overridden such as for test purposes, etc. There may
	 * also be a need to inspect some permissions in the global $USER object.
	 *
	 * @param array $get Associative array of name/value pairs akin to $_GET
	 * @param array $post Associative array of name/value pairs akin to $_POST
	 *
	 * @return boolean true if the user is authorized to continue, else false
	 */
	function is_authorized($get, $post) {
		return(false);
	}

	/**
	 * Start processing this page request
	 *
	 * @param array $get Associative array of name/value pairs akin to $_GET
	 * @param array $post Associative array of name/value pairs akin to $_POST
	 */
	function process($get, $post) {

		// Pull request data into instance properties
		$this->beforeLoadForm($get, $post);

		// Load the form and any supplemental database data that it needs
		$this->loadForm();

		// Handle the form; might not return if it was an ajax submit
		$this->handleForm();

		// Anything else we need to do before rendering?
		$this->beforeRender();

		// Finally render
		return($this->render());
	}

	/**
	 * Before loading form data, convert request arguments into local vars
	 *
	 * Turn our request arguments into instance properties; this is the last
	 * time that we will access request arguments directly - everything else
	 * will be based on instance properties from that point forward.
	 *
	 * If there are any "special" action handlers that need to kick in, now
	 * is the time. e.g. if we supply $_POST['deleteid'] to delete a record,
	 * then we can check that, delete the record, then redirect without ever
	 * continuing on to load form data.
	 *
	 * @param array $get Associative array of name/value pairs akin to $_GET
	 * @param array $post Associative array of name/value pairs akin to $_POST
	 */
	function beforeLoadForm($get, $post) {
		// By default we will do nothing with the data; the derived class
		// must implement this method to be able to pull any of the
		// request data into the page processing.
	}

	/**
	 * Load base data needed to handle form submission
	 *
	 * All data required from the database, anything needed to drive a form
	 * must all be loaded at this time and by this method. If it was a form
	 * submission, we still need to load everything as if we were going to
	 * display it - this allows us to verify that the state of everything
	 * when they first pulled the edit form is still the same which permits
	 * the submission to to proceed. By the time we leave here there should
	 * be nothing left to be discovered to save the submitted data.
	 */
	function loadForm() {

		// By default this method instantiates an empty form; the
		// derived class must implement this method to show a userful
		// form.
		if (strlen($this->options['formname'])) {

			// Initialize some bogus data
			$formdata = $helpsteps = $buttons = array();

			// And instantiate a form object
			$this->form = new PageForm($this->options['formname'], $formdata, $helpsteps, $buttons, 'vertical');
		}
	}

	/**
	 * Handle form submission
	 *
	 * Anything after loading the base data necessary for form submission
	 * and related to the actual process of submitting the form goes here.
	 *
	 * This method *may* be overridden, however its function is so basic
	 * that in most cases it will probably suffice as is. It will likely
	 * need an override for anypage that has more than one form on it...
	 */
	function handleForm() {
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
}

