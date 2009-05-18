<?
require_once("common.inc.php");
require_once("../inc/html.inc.php");
require_once("../inc/table.inc.php");
require_once("../obj/Wizard.obj.php");
require_once("../obj/Phone.obj.php");
require_once("../obj/Email.obj.php");
require_once("../obj/Sms.obj.php");


class DestWiz_howmany extends WizStep {
	function getForm($postdata, $curstep) {

		// TODO maxphones minus phones already entered
		$newphone = array_combine(range(0,4),range(0,4));
		$newemail = array_combine(range(0,3),range(0,3));
		$newsms = array_combine(range(0,2),range(0,2));

		$formdata = array();

		$formdata["numphone"] = array(
        	"label" => "Phone",
        	"value" => "0",
        	"validators" => array(
        	),
        	"control" => array("SelectMenu", "values"=>$newphone),
        	"helpstep" => 1
		);
		$formdata["numemail"] = array(
        	"label" => "Email",
        	"value" => "0",
        	"validators" => array(
        	),
        	"control" => array("SelectMenu", "values"=>$newemail),
        	"helpstep" => 1
		);
		$formdata["numsms"] = array(
        	"label" => "SMS",
        	"value" => "0",
        	"validators" => array(
        	),
        	"control" => array("SelectMenu", "values"=>$newsms),
        	"helpstep" => 1
		);
		
		$helpsteps = array (
			"Welcome",
			"blah, blah"
		);
		
		return new Form("howmany", $formdata, $helpsteps);
	}
}

class DestWiz_collectdata extends WizStep {
	function getForm($postdata, $curstep) {

		$formdata = array();

		for ($i=1; $i<=$postdata['/howmany']['numphone']; $i++) {		
			$formdata["phone".$i] = array(
				"label" => "Phone ".$i,
				"value" => "",
				"validators" => array(
					array("ValRequired"),
					array("ValLength","max" => 50),
					array("ValPhone")
				),
				"control" => array("TextField","maxlength" => 50),
				"helpstep" => 1
			);
		}
		for ($i=1; $i<=$postdata['/howmany']['numemail']; $i++) {		
			$formdata["email".$i] = array(
				"label" => "Email ".$i,
				"value" => "",
				"validators" => array(
					array("ValRequired"),
					array("ValLength","max" => 50),
					array("ValEmail")
				),
				"control" => array("TextField","maxlength" => 50),
				"helpstep" => 1
			);
		}
		for ($i=1; $i<=$postdata['/howmany']['numsms']; $i++) {		
			$formdata["sms".$i] = array(
				"label" => "SMS ".$i,
				"value" => "",
				"validators" => array(
					array("ValRequired"),
					array("ValLength","max" => 50),
					array("ValPhone")
				),
				"control" => array("TextField","maxlength" => 50),
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

		$formdata = array();

		$formdata["name"] = array(
			"label" => "Name",
			"value" => "",
			"validators" => array(
				array("ValRequired"),
				array("ValLength","max" => 50)
			),
			"control" => array("TextField","maxlength" => 50),
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
	"howmany" => new DestWiz_howmany(_L("Add Destination")),
	"collectdata" => new DestWiz_collectdata(_L("Provide Information")),
	"review" => new DestWiz_review(_L("Review"))
	);

$wizard = new Wizard("destwiz", $wizdata);
$wizard->handleRequest();

if ($wizard->isDone()) {
	$postdata = $_SESSION['destwiz']['data'];
	// TODO grab the data

	// phone
	$newPhones = array();
	for ($i=1; $i<=$postdata['/howmany']['numphone']; $i++) {
		$newPhones[] = $postdata['/collectdata']['phone'.$i];
	}
	subscriberPrepareNewPhone($newPhones);
	
	// email TODO prepare email list, not one at a time
	for ($i=1; $i<=$postdata['/howmany']['numemail']; $i++) {
		$newemail = $postdata['/collectdata']['email'.$i];
		subscriberPrepareNewEmail($newemail);
	}
	
	redirect("notificationdestinations.php");
}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$PAGE = "contacts:notificationdests";
$TITLE = _L("Destination Wizard");

require_once("nav.inc.php");

echo "<font color=\"red\">TODO DO NOT TEST YET, will have wizard to add and confirm phone, etc</font><BR><BR>";

//echo dataChangeAlert($datachange, $_SERVER['REQUEST_URI']);

startWindow($stepdata->title);
echo $wizard->render();
endWindow();

require_once("navbottom.inc.php");
?>
