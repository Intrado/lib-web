<?
/**
 * Manage individual organization quicktip feature
 *
 * User: nrheckman
 * Date: 9/15/14
 * Time: 3:33 PM
 */


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
// CUSTOM FORM ITEMS
// -----------------------------------------------------------------------------
/**
 * Class OrganizationFeature
 *
 * Used to display state of an system 'feature' for an individual organization, including inheritance from a parent organization.
 */
class OrganizationFeature extends FormItem {
	var $organization;
	var $parentValue;
	var $inputName;

	/**
	 * Constructs a new instance of this form item
	 *
	 * @param Form $form the form this item is injected into
	 * @param string $name a unique name for this instance of the form item
	 * @param array $args required and optional arguments. Requires that an 'organization' object be passed. Optionally accepts 'parentValue'
	 * as the enabled state of this organization's parent
	 */
	function __construct($form, $name, $args) {
		parent::FormItem($form, $name, $args);
		$this->organization = $args['organization'];
		$this->parentValue = isset($args['parentValue']) ? $args['parentValue'] : null;
		$this->inputName = escapeHtml("{$this->form->name}_{$this->name}");
	}

	function render ($value) {
		return "<div id='{$this->inputName}' class='radiobox'>
					<label>Show&nbsp;<input type='radio' name='{$this->inputName}' value='enabled' ". (($value == 'enabled')? 'checked': '') ."></label>
					<label>Hide&nbsp;<input type='radio' name='{$this->inputName}' value='disabled' ". (($value == 'disabled')? 'checked': '') ."></label>
					<input type='radio' name='{$this->inputName}' value='inherited' style='display:none;' ". (($value == 'inherited')? 'checked': '') .">
				</div>";
	}

	/**
	 * Creates a jQuery module attaching functions for managing the state and getting the value of this form item
	 * @return string as the <script> object containing the required javascript library to render this form item
	 */
	function renderJavascriptLibraries() {
		return "<script>
					(function($) {
						$.fn.getOrgFeatureValue = function() {
							return this.data('userValue');
						};
						/**
						* Attaches a listener which detects changes and emits a new event which other instances can listen to
						* Also listens for changes emitted by it's parent organization to update the display state
						*
						* @param {object} organization this organization object, containing the id and it's parent organization id
						* @param {string} initialParentValue optionally sets this organization's parent's value
						* @returns {object} this, after having attached org feature functionality
						*/
						$.fn.asOrgFeature = function(organization, initialParentValue) {
							var org = organization;
							var parentValue = initialParentValue;
							var userValue = this.filter(':checked').val();
							this.data('userValue', userValue);
							var changeEventPrefix = 'orgFeatureChange-';

							var radios = this;
							/**
							* Changes the radio display based on the value of this organization's parent.
							* If the value is NOT inherited from it's parent, it displays normally. Otherwise, the enabled state follows
							* the parent and is displayed as 'disabled' to indicate this state
							*/
							var updateRadioDisplay = function() {
								radios.prop('disabled', false);
								var inheritedRadio = radios.filter('[value=' + parentValue + ']');
								if (userValue != 'inherited') {
									// change nothing, the user's selection takes precedence
								} else {
									// set the radio to match the parent organization and disable it
									inheritedRadio.prop('checked', true).prop('disabled', true);
								}
							};

							// bind to change events
							this.on('change', function(e) {
								userValue = e.target.value;
								if (userValue == parentValue) {
									userValue = 'inherited';
								}
								updateRadioDisplay();
								// trigger a custom event to tell listeners that this organization's value has changed
								$.event.trigger({
									type: changeEventPrefix + org.id,
									message: { value: userValue }
								});
								radios.data('userValue', userValue);
							});

							// listen to change event's for this organization's parent org
							if (org.parentOrganizationId) {
								$(document).on(changeEventPrefix + org.parentOrganizationId, function(e) {
									parentValue = e.message.value;
									updateRadioDisplay();
								});
							}

							updateRadioDisplay();
							return this;
						};
					})(jQuery);
				</script>";
	}

	/**
	 * Attaches the jQuery module to this form item
	 * @return string the javascript to execute which sets up this form item
	 */
	function renderJavascript() {
		$orgJson = json_encode($this->organization);
		$parentValue = addslashes($this->parentValue);
		return "jQuery('input[name=\"{$this->inputName}\"]').asOrgFeature($orgJson, '$parentValue');";
	}

	/**
	 * @return string which is a javascript function to be executed, which will return the enabled state of the 'feature' for this organization
	 */
	function jsGetValue () {
		return "function func() { return jQuery('input[name=\"{$this->inputName}\"]').getOrgFeatureValue() }; func";
	}
}

// -----------------------------------------------------------------------------
// CUSTOM PAGE FUNCTIONALITY
// -----------------------------------------------------------------------------
class QuickTipManager extends PageForm {
	public $formName = 'quicktipmanager';
	var $csApi;

	/**
	 * Create a new instance of this page object
	 *
	 * @param array $options which contain information necessary for display of the page with relation to the rest of the application
	 * @param object $csApi An instance of CommsuiteApiClient
	 */
	public function __construct($options, $csApi) {
		$this->csApi = $csApi;
		parent::__construct($options);
	}

	/**
	 * Requires the user have the profile setting 'managesystem'
	 *
	 * @return bool true if the currently authenticated user has access to this page
	 */
	function isAuthorized() {
		global $USER;
		return($USER->authorize('managesystem'));
	}

	function load() {
		// Get organization and feature list from the API (/organizations/root)
		// Looks like { id: <id>, name: <name>, subOrganizations: [ <another org object>, ... ] }
		$rootOrg = $this->csApi->getOrganization('root');
		// Looks like [ { organizationId: <id>, isEnabled: <bool> }, ... ]
		$orgQtFeatureList = $this->csApi->getFeature('quicktip');
		$this->form = $this->formFactory($rootOrg, $orgQtFeatureList);
	}

	function afterLoad() {
		$this->form->handleRequest();

		// If the form was submitted...
		if ($this->form->getSubmit()) {
			$postData = $this->form->getData();
			$featureSettings = array();
			foreach ($postData as $field => $value) {
				$fieldParts = preg_split('/#/', $field);
				$orgId = $fieldParts[1];
				if ($value != 'inherited') {
					$isEnabled = ($value === 'enabled');
					$featureSettings[] = array( 'organizationId' => $orgId, 'isEnabled' => $isEnabled);
				}
			}
			$success = $this->csApi->setFeature('quicktip', $featureSettings);

			if ($success) {
				$this->form->sendTo('settings.php');
			} else {
				$this->form->sendTo('', array(
					'status' => 'fail'
				));
			}
		}
	}

	function render() {
		$this->options['title'] = _L('QuickTip Feature Management');
		$this->options['windowTitle'] = _L('Show/Hide QuickTip Organizations');
		$styleOverride =
			'<style>
				.newform .formtitle {
					width: 25%;
				}
				.newform .formcontrol {
					margin: 0 0 0 26%;
				}
			</style>';
		return $styleOverride . parent::render();
	}

	/**
	 * @param object $rootOrganization object which represents the root organization and contains it's sub organizations
	 * @param object $orgQtFeatureList list of objects which indicate organization id and feature enabled state
	 * @return Form the constructed form object
	 */
	function formFactory($rootOrganization, $orgQtFeatureList) {
		$hasQt = array();
		foreach($orgQtFeatureList as $orgQtFeature) {
			$hasQt[$orgQtFeature->organizationId] = $orgQtFeature->isEnabled;
		}
		$orgFeatureValue = function($orgId) use ($hasQt) {
			$value = 'inherited';
			if (isset($hasQt[$orgId])) {
				$value = ($hasQt[$orgId]) ? 'enabled' : 'disabled';
			}
			return $value;
		};

		$tipHtml = '<p>Use the controls below to set the visibility of individual organizations for the QuickTip feature.<br />
					The root organization\'s state will be inherited by all sub organizations, unless overridden.</p>';

		$validValues = array('enabled', 'disabled', 'inherited');
		$org = new stdClass();
		$org->id = $rootOrganization->id;
		$org->name = $rootOrganization->name;
		$rootOrgValue = ($orgFeatureValue($org->id) === 'inherited')? 'enabled' : 'disabled';
		$formData = array(
			_L('Root Organization'),
			'tip' => array(
				'label' => '',
				'control' => array("FormHtml", "html" => $tipHtml)
			),
			"org#{$org->id}" => array(
				'label' => escapeHtml($org->name),
				'value' => $rootOrgValue,
				'validators' => array(
					array('ValRequired'),
					array('ValInArray', 'values' => $validValues)),
				'control' => array('OrganizationFeature', 'organization' => $org)
			)
		);
		if ($rootOrganization->subOrganizations) {
			$formData[] = _L('Sub Organizations');
			foreach($rootOrganization->subOrganizations as $org) {
				$formData["org#{$org->id}"] = array(
					'label' => escapeHtml($org->name),
					'value' => $orgFeatureValue($org->id),
					'validators' => array(
						array('ValRequired'),
						array('ValInArray', 'values' => $validValues)),
					"control" => array("OrganizationFeature", "organization" => $org, "parentValue" => $rootOrgValue)
				);
			}
		}

		$buttons = array(
			submit_button(_L('Save'), 'submit', 'tick'),
			icon_button(_L('Cancel'), 'cross', null, 'start.php')
		);

		$form = new Form($this->formName, $formData, null, $buttons);
		$form->ajaxsubmit = true;
		return $form;
	}
}

// -----------------------------------------------------------------------------
// PAGE INSTANTIATION AND DISPLAY
// -----------------------------------------------------------------------------
$page = new QuickTipManager(array('page' => 'admin:settings'), $csApi);
executePage($page);

?>