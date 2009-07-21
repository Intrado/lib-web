<?
require_once("common.inc.php");
require_once("../inc/html.inc.php");
require_once("../inc/table.inc.php");
require_once("../obj/Wizard.obj.php");
require_once("../obj/Phone.obj.php");
require_once("../obj/Email.obj.php");
require_once("../obj/Sms.obj.php");
require_once("subscriberutils.inc.php");


class ValEmailUnique extends Validator {
	var $onlyserverside = true;

	function validate ($value, $args) {
		if (0 == QuickQuery("select count(*) from email where personid=? and email=?", false, array($_SESSION['personid'], $value)) &&
			0 == QuickQuery("select count(*) from subscriberpending where subscriberid=? and type='email' and value=?", false, array($_SESSION['subscriberid'], $value)))
			return true;
		return "$this->label is not unique.  You have already added this Contact Information.";
    }
}

class ValPhoneUnique extends Validator {
	var $onlyserverside = true;

	function validate ($value, $args) {
		if (0 == QuickQuery("select count(*) from phone where personid=? and phone=?", false, array($_SESSION['personid'], $value)) &&
			0 == QuickQuery("select count(*) from subscriberpending where subscriberid=? and type='phone' and value=?", false, array($_SESSION['subscriberid'], $value)))
			return true;
		return "$this->label is not unique.  You have already added this Contact Information.";
    }
}

class ValSmsUnique extends Validator {
	var $onlyserverside = true;

	function validate ($value, $args) {
		if (0 == QuickQuery("select count(*) from sms where personid=? and sms=?", false, array($_SESSION['personid'], $value)) &&
			0 == QuickQuery("select count(*) from subscriberpending where subscriberid=? and type='sms' and value=?", false, array($_SESSION['subscriberid'], $value)))
			return true;
		return "$this->label is not unique.  You have already added this Contact Information.";
    }
}

class DestWiz_whattype extends WizStep {
	function getForm($postdata, $curstep) {

		// find remaining phone/email/sms available (some already active and pending)
		$available = findAvailableDestinationTypes();
				
		//  if sequence for phone or sms or email available, build the options
		$values = array();
		if (isset($available['phone']))
			$values["phone"] = _L("Phone Call");
		if (isset($available['phone']) && isset($available['sms']))
			$values["both"] = _L("Phone Call and Text Message");
		if (isset($available['sms']))
			$values["text"] = _L("Text Message");
		if (isset($available['email']))
			$values["email"] = _L("Email");
		
		$formdata = array();

		$formdata["whattype"] = array(
        	"label" => _L("Type"),
        	"value" => "",
        	"validators" => array(
					array("ValRequired")
        	),
        	"control" => array("RadioButton", "values"=>$values),
        	"helpstep" => 1
		);

		return new Form("whattype", $formdata, null);
	}
}

class DestWiz_collectdata extends WizStep {
	function getForm($postdata, $curstep) {

		$datatype = $postdata['/whattype']['whattype'];

		$formdata = array();

		if ($datatype == "email") {
			$formdata['newdata'] = array(
				"label" => _L("Email"),
				"value" => "",
				"validators" => array(
					array("ValRequired"),
					array("ValLength","max" => 50),
					array("ValEmail"),
					array("ValEmailUnique")
				),
				"control" => array("TextField","maxlength" => 50),
				"helpstep" => 1
			);
		} else {
			$valarray = array(
					array("ValRequired"),
					array("ValLength","max" => 50),
					array("ValPhone")
			);
			if ($datatype == "phone" || $datatype == "both") {
				$valarray[] = array("ValPhoneUnique");
			}
			if ($datatype == "text" || $datatype == "both") {
				$valarray[] = array("ValSmsUnique");
			}
			
			$formdata['newdata'] = array(
				"label" => _L("Phone"),
				"value" => "",
				"validators" => $valarray,
				"control" => array("TextField","maxlength" => 50),
				"helpstep" => 1
			);
		
		}
		
		return new Form("collectdata", $formdata, null);
	}
}

class FinishDestWizard extends WizFinish {
	
	function finish ($postdata) {
	}
	
	function getFinishPage ($postdata) {
		// start with failure condition
		$formhtml = '<div style="height: 200px; overflow:auto;">' . _L("Sorry, an error occurred.  Please try again later.") . '</div>';
	
		// if code generation success, then generate form html
		if ($postdata['/whattype']['whattype'] == "email") {
			if (subscriberPrepareNewEmail($postdata['/collectdata']['newdata'])) {
				//$formhtml = '<div style="height: 200px; overflow:auto;">' . _L("You must check your email for an activation code.  This code is required to complete the process.") . '</div>';
				$formhtml = getEmailReview($postdata['/collectdata']['newdata']);
			}
		} else {
	        $options = json_encode(array('phonetextoption' => $postdata['/whattype']['whattype']));
			if ($code = subscriberPrepareNewPhone($postdata['/collectdata']['newdata'], $options)) {
				//$formhtml = '<div style="height: 200px; overflow:auto;">Your activation code is: ' . $code . '</div>';
				$formhtml = getPhoneReview($postdata['/collectdata']['newdata'], $code);
			}
		}
		return $formhtml;
	}
}


$wizdata = array(
	"whattype" => new DestWiz_whattype(_L("Select Type")),
	"collectdata" => new DestWiz_collectdata(_L("Enter Contact Info"))
	);

$wizard = new Wizard("destwiz", $wizdata, new FinishDestWizard("Activate"));
$wizard->doneurl = "notificationpreferences.php";
$wizard->handleRequest();


////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$PAGE = "contacts:notificationdests";
$TITLE = _L("Add Contact Information");

require_once("nav.inc.php");

?>
<script type="text/javascript">
<?
Validator::load_validators(array("ValEmailUnique","ValPhoneUnique","ValSmsUnique"));
?>
</script>
<?

startWindow("");
echo $wizard->render();
endWindow();

require_once("navbottom.inc.php");
?>
