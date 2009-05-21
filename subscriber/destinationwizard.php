<?
require_once("common.inc.php");
require_once("../inc/html.inc.php");
require_once("../inc/table.inc.php");
require_once("../obj/Wizard.obj.php");
require_once("../obj/Phone.obj.php");
require_once("../obj/Email.obj.php");
require_once("../obj/Sms.obj.php");
require_once("subscriberutils.inc.php");


class DestWiz_whattype extends WizStep {
	function getForm($postdata, $curstep) {

		// TODO maxphones minus phones already entered to validate adding a new phone (email, sms)
		
		$formdata = array();

		$formdata["whattype"] = array(
        	"label" => "Communication Method",
        	"value" => "",
        	"validators" => array(
					array("ValRequired")
        	),
        	"control" => array("RadioButton", "values"=>array("Phone","Email","Text")),
        	"helpstep" => 1
		);
		
		$helpsteps = array (
			"Welcome",
			"blah, blah"
		);
		
		return new Form("whattype", $formdata, $helpsteps);
	}
}

class DestWiz_collectdata extends WizStep {
	function getForm($postdata, $curstep) {

		$datatype = $postdata['/whattype']['whattype'];

		$formdata = array();

		if ($datatype == 1) {
			$formdata['newdata'] = array(
				"label" => _L("Email"),
				"value" => "",
				"validators" => array(
					array("ValRequired"),
					array("ValLength","max" => 50),
					array("ValEmail")
				),
				"control" => array("TextField","maxlength" => 50),
				"helpstep" => 1
			);
		} else {
			if ($datatype == 0)
				$label = _L("Phone");
			else
				$label = _L("Text");
			$formdata['newdata'] = array(
				"label" => $label,
				"value" => "",
				"validators" => array(
					array("ValRequired"),
					array("ValLength","max" => 50),
					array("ValPhone")
				),
				"control" => array("TextField","maxlength" => 50),
				"helpstep" => 1
			);
			// TODO if sequence available
			if (true)
				$formdata['phonetextoption'] = array(
    	    		"label" => _L("Usage"),
        			"value" => "",
        			"validators" => array(
							array("ValRequired")
        			),
        			"control" => array("RadioButton", "values"=>array("phone"=>"Only Phone", "text"=>"Only Text", "both"=>"Both Phone and Text")),
        			"helpstep" => 1
				);
			
		}
		
		$helpsteps = array (
			"Welcome",
			"blah, blah"
		);
		
		return new Form("collectdata", $formdata, $helpsteps);
	}
}

class DestWiz_review extends WizStep {
	function getForm($postdata, $curstep) {

		// start with failure condition
		$formhtml = '<div style="height: 200px; overflow:auto;">' . _L("Sorry, an error occurred.  Please try again later.") . '</div>';
	
		// if code generation success, then generate form html
		if ($postdata['/whattype']['whattype'] == 0 || $postdata['/whattype']['whattype'] == 2) {
	        $options = json_encode(array('phonetextoption' => $postdata['/collectdata']['phonetextoption']));
			if ($code = subscriberPrepareNewPhone($postdata['/collectdata']['newdata'], $options)) {
				//$formhtml = '<div style="height: 200px; overflow:auto;">Your activation code is: ' . $code . '</div>';
				$formhtml = getPhoneReview($postdata['/collectdata']['newdata'], $code);
			}
		} else {
			if (subscriberPrepareNewEmail($postdata['/collectdata']['newdata'])) {
				//$formhtml = '<div style="height: 200px; overflow:auto;">' . _L("You must check your email for an activation code.  This code is required to complete the process.") . '</div>';
				$formhtml = getEmailReview($postdata['/collectdata']['newdata']);
			}
		}
	
		$formdata = array();

    	$formdata["review"] = array(
        	"label" => "Confirmation",
        	"control" => array("FormHtml","html" => $formhtml),
			"helpstep" => 1
		);
		
		$helpsteps = array (
			"Welcome",
			"blah, blah"
		);
		
		return new Form("review", $formdata, $helpsteps);
	}
}



$wizdata = array(
	"whattype" => new DestWiz_whattype(_L("Add Destination")),
	"collectdata" => new DestWiz_collectdata(_L("Provide Information")),
	"review" => new DestWiz_review(_L("Review"))
	);

$wizard = new Wizard("destwiz", $wizdata);
$wizard->handleRequest();

if ($wizard->isDone()) {
	$postdata = $_SESSION['destwiz']['data'];
	// TODO grab the data

	
	redirect("notificationpreferences.php");
}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$PAGE = "contacts:notificationdests";
$TITLE = _L("Destination Wizard");

require_once("nav.inc.php");

//echo dataChangeAlert($datachange, $_SERVER['REQUEST_URI']);

startWindow("");
echo $wizard->render();
endWindow();

require_once("navbottom.inc.php");
?>
