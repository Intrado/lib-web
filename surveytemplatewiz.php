<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
include_once("inc/common.inc.php");
include_once("inc/securityhelper.inc.php");
include_once("inc/table.inc.php");
include_once("inc/html.inc.php");
include_once("inc/utils.inc.php");
include_once("inc/form.inc.php");
include_once("obj/Setting.obj.php");
include_once("obj/Phone.obj.php");
include_once("obj/FieldMap.obj.php");

require_once("obj/Wizard.obj.php");

require_once("obj/Form.obj.php");
require_once("obj/FormItem.obj.php");
require_once("obj/Validator.obj.php");


////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!getSystemSetting('_hassurvey', true) || !$USER->authorize('survey')) {
	redirect('unauthorized.php');
}

////////////////////////////////////////////////////////////////////////////////
// Optional Form Items And Validators
////////////////////////////////////////////////////////////////////////////////

class SurveyType extends FormItem {
	function render ($value) {
		$n = $this->form->name."_".$this->name;
		if($value == null || $value == "") // Handle empty value to combind this validator with ValRequired
			$value = array("phone" => "false","web" => "false");
			
		// edit input type from "hidden" to "text" to debug the form value
		$str = '<input id="'.$n.'" name="'.$n.'" type="text" value="'.escapehtml(json_encode($value)).'"/>';
		$str .= '<input id="'.$n.'phone" name="'.$n.'left" type="checkbox" onchange="setValue_'.$n.'()" value="" '. ($value["phone"] == "true" ? 'checked' : '').' />&nbsp;Phone';
		$str .= '<input id="'.$n.'web" name="'.$n.'right" type="checkbox" onchange="setValue_'.$n.'()" value="" '. ($value["web"] == "true" ? 'checked' : '').' />&nbsp;Web';
		$str .= '<script>function setValue_'.$n.'(){
								$("'.$n.'").value = Object.toJSON({
									"phone": $("'.$n.'phone").checked.toString(),
									"web": $("'.$n.'web").checked.toString()
							});
							form_do_validation($("' . $this->form->name . '"), $("' . $n . '"));
						 }
				</script>';
		return $str;
	}
}

class ValSurveyType extends Validator {
	function validate ($value, $args) {
		if(!is_array($value)) {
			$value = json_decode($value,true);
		}
		if (!($value["phone"] == "true" || $value["web"] == "true"))
			return "Phone or Web survey is required for " . $this->label;
		else
			return true;

	}
	function getJSValidator () {
		return
			'function (name, label, value, args) {
				checkval = value.evalJSON();
				if (!(checkval.phone == "true" || checkval.web == "true"))
					return "Phone or Web survey is required for " + label;
				return true;
			}';
	}
}


class SurveyQuestion extends FormItem {
	function render ($value) {
		$n = $this->form->name."_".$this->name;
		if($value == null || $value == "") // Handle empty value to combind this validator with ValRequired
			$value = array("phone" => "false","web" => "false");

		// edit input type from "hidden" to "text" to debug the form value
		$str = '<input id="'.$n.'" name="'.$n.'" type="text" value="'.escapehtml(json_encode($value)).'"/>';
		$str .= '<input id="'.$n.'phone" name="'.$n.'left" type="checkbox" onchange="setValue_'.$n.'()" value="" '. ($value["phone"] == "true" ? 'checked' : '').' />&nbsp;Phone';
		$str .= '<input id="'.$n.'web" name="'.$n.'right" type="checkbox" onchange="setValue_'.$n.'()" value="" '. ($value["web"] == "true" ? 'checked' : '').' />&nbsp;Web';
		$str .= '<script>function setValue_'.$n.'(){
								$("'.$n.'").value = Object.toJSON({
									"phone": $("'.$n.'phone").checked.toString(),
									"web": $("'.$n.'web").checked.toString()
							});
							form_do_validation($("' . $this->form->name . '"), $("' . $n . '"));
						 }
				</script>';
		return $str;
	}
}

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

class SurveyTempleteWiz_settings extends WizStep {
	function getForm($postdata, $curstep) {

		$formdata = array();
		$formdata["name"] = array(
			"label" => _L('Name'),
			"value" => "",
			"validators" => array(
				array("ValRequired"),
				array("ValLength","max" => 30)
			),
			"control" => array("TextField","size" => 30, "maxlength" => 51),
			"helpstep" => 1
		);
		$formdata["description"] = array(
			"label" => _L('Description'),
			"value" => "",
			"validators" => array(
				array("ValLength","min" => 3,"max" => 50)
			),
			"control" => array("TextField","size" => 30, "maxlength" => 51),
			"helpstep" => 1
		);
/*
		$formdata["surveytype"] = array(
			"label" => _L('Survey Type'),
			"fieldhelp" => _L(''),
			"value" => "",//array("left" => "true","right" => "false"),
			"validators" => array(array("ValRequired"),array("ValSurveyType")),
			"control" => array("SurveyType"),
			"helpstep" => 2
		);
*/
		$formdata["phonesurvey"] = array(
			"label" => _L('Phone Survey'),
			"fieldhelp" => _L(''),
			"value" => "",
			"validators" => array(),
			"control" => array("CheckBox"),
			"helpstep" => 2
		);

		$formdata["websurvey"] = array(
			"label" => _L('Web Survey'),
			"fieldhelp" => _L(''),
			"value" => "",
			"validators" => array(),
			"control" => array("CheckBox"),
			"helpstep" => 3
		);

		$formdata["numberofquestions"] = array(
       		"label" => _L("Number of Questions"),
       		"fieldhelp" => _L(''),
       		"value" => "",
       		"validators" => array(
            	array("ValRequired")
       		),
       		"control" => array("SelectMenu","values" => array("" => "-- Select a Number --") + array_combine(range(1,99),range(1,99))),
       		"helpstep" => 4
   		);
		return new Form("settings", $formdata, null);
	}
}

class SurveyTemplateWiz_phone extends WizStep {
	function getForm($postdata, $curstep) {

		$formdata = array();

		$formdata["amsweringmachine"] = array(
			"label" => _L('Answering Machine Message'),
			"fieldhelp" => _L('Leave message on answering machines'),
			"value" => "",
			"validators" => array(),
			"control" => array("CheckBox"),
			"helpstep" => 1
		);
		$formdata["intromessage"] = array(
			"label" => _L('Play introductory message'),
			"fieldhelp" => _L('Play introductory message'),
			"value" => "",
			"validators" => array(),
			"control" => array("CheckBox"),
			"helpstep" => 1
		);
		$formdata["goodbyemessage"] = array(
			"label" => _L('Play goodbye message'),
			"fieldhelp" => _L('Play goodbye message'),
			"value" => "",
			"validators" => array(),
			"control" => array("CheckBox"),
			"helpstep" => 1
		);
		$formdata["replymessage"] = array(
			"label" => _L('Allow call recipients to leave a message'),
			"fieldhelp" => _L('Allow call recipients to leave a message'),
			"value" => "",
			"validators" => array(),
			"control" => array("CheckBox"),
			"helpstep" => 1
		);

		return new Form("phonesurvey", $formdata, null);
	}

		//returns true if this step is enabled
	function isEnabled($postdata, $step) {
		if (isset($postdata['/settings']))
			return ($postdata['/settings']['phonesurvey'] == "true");
		return true;
	}
}
class SurveyTemplateWiz_web extends WizStep {
	function getForm($postdata, $curstep) {

		$formdata = array();

		$formdata["emailmessage"] = array(
			"label" => _L('Email Message'),
			"value" => "",
			"validators" => array(
				array("ValRequired")
			),
			"control" => array("TextField","size" => 30, "maxlength" => 51),
			"helpstep" => 1
		);

		$formdata["webtitle"] = array(
			"label" => _L('Web Page Title'),
			"value" => "",
			"validators" => array(),
			"control" => array("TextField","size" => 30, "maxlength" => 51),
			"helpstep" => 1
		);

		$formdata["webmessage"] = array(
			"label" => _L('Web Thank You Message'),
			"value" => "",
			"validators" => array(),
			"control" => array("TextField","size" => 30, "maxlength" => 51),
			"helpstep" => 1
		);

		$formdata["htmlinsurvey"] = array(
			"label" => _L('Use HTML in Web Survey'),
			"fieldhelp" => _L('Use HTML in Web Survey'),
			"value" => "",
			"validators" => array(),
			"control" => array("CheckBox"),
			"helpstep" => 1
		);

		return new Form("phonesurvey", $formdata, null);
	}

	//returns true if this step is enabled
	function isEnabled($postdata, $step) {
		if (isset($postdata['/settings']))
			return ($postdata['/settings']['websurvey'] == "true");
		return true;
	}
}

class SurveyTemplateWiz_questions extends WizStep {
	function getForm($postdata, $curstep) {

		$formdata = array();

		$formdata["placeholder"] = array(
			"label" => _L('Placeholder'),
			"fieldhelp" => _L(''),
			"value" => "",
			"validators" => array(),
			"control" => array("CheckBox"),
			"helpstep" => 1
		);

		return new Form("phonesurvey", $formdata, null);
	}
}


class FinishSurveyTemplateWizard extends WizFinish {
	
	function finish ($postdata) {
	}
	
	function getFinishPage ($postdata) {
		return "<h1>Survey Template Created</h1>";
	}
}


$wizdata = array(
	"settings" => new SurveyTempleteWiz_settings(_L("Settings")),
	"phonesurvey" => new SurveyTemplateWiz_phone(_L("Phone Features")),
	"websurvey" => new SurveyTemplateWiz_web(_L("Web Features")),
	"questions" => new SurveyTemplateWiz_questions(_L("Questions"))
	);

$wizard = new Wizard("surveytemplatewiz", $wizdata, new FinishSurveyTemplateWizard(_L("Finish")));
$wizard->doneurl = "surveys.php";
$wizard->handleRequest();

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$PAGE = "notifications:survey";
$TITLE = "Survey Template Editor";

include_once("nav.inc.php");

// Load Custom Form Validators
?>
<script type="text/javascript">
<? Validator::load_validators(array("ValSurveyType")); ?>
</script>
<?


startWindow($wizard->getStepData()->title);

echo $wizard->render();

endWindow();

include_once("navbottom.inc.php");
?>
