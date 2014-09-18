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
class OrganizationFeature extends FormItem {
	var $organization;
	var $parentValue;
	var $inputName;

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

	function renderJavascriptLibraries() {
		return "<script>
					(function($) {
						$.fn.getOrgFeatureValue = function() {
							return this.data('userValue');
						};
						$.fn.asOrgFeature = function(organization, initialParentValue) {
							var org = organization;
							var parentValue = initialParentValue;
							var userValue = this.filter(':checked').val();
							this.data('userValue', userValue);
							var changeEventPrefix = 'orgFeatureChange-';

							var radios = this;
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

	function renderJavascript($value) {
		$orgJson = json_encode($this->organization);
		$parentValue = addslashes($this->parentValue);
		return "jQuery('input[name=\"{$this->inputName}\"]').asOrgFeature($orgJson, '$parentValue');";
	}

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
		$this->options['validators'] = array('ValDupeCategoryName');
		parent::__construct($options);
	}

	function isAuthorized(&$get=array(), &$post=array(), &$request=array(), &$session=array()) {
		global $USER;
		return($USER->authorize('managesystem')); // open to the world, unconditionally!
	}

	function beforeLoad(&$get=array(), &$post=array(), &$request=array(), &$session=array()) {
	}

	function load(&$get=array(), &$post=array(), &$request=array(), &$session=array()) {
		// Get organization list from the API (/organizations/root)
		// Looks like { id: <id>, name: <name>, subOrganizations: [ <another org object>, ... ] }
		$rootOrg = $this->csApi->getOrganization('root');
		$orgQtFeatureList = $this->csApi->getFeature('quicktip');
		error_log(print_r($orgQtFeatureList, true));

//		$orgQtFeatureList = json_decode('[{
//				"organizationId": 123,
//				"isEnabled": true
//			},
//			{
//				"organizationId": 125,
//				"isEnabled": false
//		}]');

		$this->form = $this->formFactory($rootOrg, $orgQtFeatureList);
	}

	function afterLoad() {
		$this->form->handleRequest();

		// If the form was submitted...
		if ($this->form->getSubmit()) {
			$redirectLocation = 'settings.php';

			// Check if the data has changed and display a notification if so...
			if ($this->form->checkForDataChange()) {
				notice(_L("This form's data has been modified elsewhere. Please try again."));
				$redirectLocation = '';
			}

			// Check for validation errors
			if (($errors = $this->form->validate()) === false) {
				$postdata = $this->form->getData();
				$featureSettings = array();
				foreach ($postdata as $field => $value) {
					$fieldParts = preg_split('/#/', $field);
					$orgId = $fieldParts[1];
					if ($value != 'inherited') {
						$isEnabled = ($value === 'enabled');
						$featureSettings[] = array( 'organizationId' => $orgId, 'isEnabled' => $isEnabled);
					}
				}
				$success = $this->csApi->setFeature('quicktip', $featureSettings);

				if ($success) {
					$this->form->sendTo($redirectLocation);
				} else {
					$this->form->fireEvent(_L('Setting new values failed, please try your request again'));
				}
			}
		}
	}

	function render() {
		$this->options['title'] = _L('QuickTip Feature Management');
		$this->options['windowTitle'] = _L('Show/Hide QuickTip Organizations');
		$script =
			"<script>
				// watch for submit events and display them in an alert
				(function($) {
					$('#{$this->form->name}').on('Form:Submitted', function (event, data) {
						alert(data);
					});
				})(jQuery);
			</script>";
		$styleOverride =
			'<style>
				.newform .formtitle {
					width: 25%;
				}
				.newform .formcontrol {
					margin: 0 0 0 26%;
				}
			</style>';
		return $script . $styleOverride . parent::render();
	}

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
				'validators' => array(array('ValInArray', 'values' => $validValues)),
				'control' => array('OrganizationFeature', 'organization' => $org)
			)
		);
		if ($rootOrganization->subOrganizations) {
			$formData[] = _L('Sub Organizations');
			foreach($rootOrganization->subOrganizations as $org) {
				$formData["org#{$org->id}"] = array(
					'label' => escapeHtml($org->name),
					'value' => $orgFeatureValue($org->id),
					'validators' => array(array('ValInArray', 'values' => $validValues)),
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