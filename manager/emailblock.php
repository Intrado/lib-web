<?
/**
 * Block email addresses system wide by searching for them in each customer account
 * 
 * Nickolas Heckman
 * Sep. 10, 2013
 */

require_once("common.inc.php");
require_once("../inc/table.inc.php");
require_once("../inc/html.inc.php");
require_once("../obj/Validator.obj.php");
require_once("../obj/Form.obj.php");
require_once("../obj/FormItem.obj.php");
require_once("../obj/Email.obj.php");
require_once("../obj/User.obj.php");


////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////

if (!$MANAGERUSER->authorized("emailblock"))
	exit("Not Authorized");


////////////////////////////////////////////////////////////////////////////////
// Form Data
////////////////////////////////////////////////////////////////////////////////

$helpstepnum = 1;

$formdata = array(
	"emailaddress" => array(
		"label" => _L('Email Address'),
		"value" => "",
		"validators" => array(
			array("ValRequired"),
			array("ValEmail")
		),
		"control" => array("TextField","size" => 30, "maxlength" => 255),
		"helpstep" => $helpstepnum
	),
	"description" => array(
		"label" => _L("Blocked Reason"),
		"value" => "",
		"validators" => array(
			array("ValRequired")
		),
		"control" => array("TextField", "size" => 120, "maxlength" => 255),
		"helpstep" => $helpstepnum
	)
);

$helpsteps = array("TODO");

$buttons = array(submit_button(_L('Save'),"submit","tick"));
$form = new Form("emailblock", $formdata, $helpsteps ,$buttons);


////////////////////////////////////////////////////////////////////////////////
// Form Data Handling
////////////////////////////////////////////////////////////////////////////////

//check and handle an ajax request (will exit early)
//or merge in related post data
$form->handleRequest();

$datachange = false;
$errors = false;
//check for form submission
if ($button = $form->getSubmit()) { //checks for submit and merges in post data
	$ajax = $form->isAjaxSubmit(); //whether or not this requires an ajax response	

	if ($form->checkForDataChange()) {
		$datachange = true;
	} else {
		if (($errors = $form->validate()) === false) { //checks all of the items in this form
			$postdata = $form->getData(); //gets assoc array of all values {name:value,...}

			// Keep track of which customers we blocked the email for
			$customersBlocked = array();

			// loads connection data for customers into a global
			loadManagerConnectionData();
			global $CUSTOMERINFO;

			// iterate over all the customer databases
			foreach ($CUSTOMERINFO as $cid => $cust) {

				// need to get a connection to the customer database, NOT read-only! We will be inserting data.
				$custdb = getPooledCustomerConnection($cid,false);
				
				// check if there is a person who matches the email address
				$email = DBFind("Email", "from email where email = ?", false, array($postdata['emailaddress']), $custdb);
				if ($email === false)
					continue;
				
				// need to get the schoolmessenger user so we can associated the block record with it
				$smUser = DBFind("User", "from user where login = 'schoolmessenger' and not deleted", false, null, $custdb);
				if ($smUser === false) {
					// This should never happen, there should always be a schoolmessenger super admin user account!
					error_log("Error blocking email address: '". $postdata['emailaddress']. "' reason: No 'schoolmessenger' user was found");
					continue;
				}
				// insert the block record (insert on duplicate key skip)
				$count = QuickUpdate("insert into blockeddestination (userid, description, destination, type, createdate, blockmethod)
						values (?, ?, ?, 'email', now(), 'manual') on duplicate key update userid = userid",
						$custdb, array($smUser->id, $postdata['description'], $postdata['emailaddress']));
				
				// keep track of which customers blocked this address
				if ($count > 0)
					$customersBlocked[$cid] = "Successfully Blocked";
				else
					$customersBlocked[$cid] = "Already Blocked";
			}
			
			// format some html to return to the client for display
			$html = "";
			if ($customersBlocked) {
				$html = "<table class='list'><tbody>
					<tr class='listHeader'><th>Customer Id</th><th>Block Status</th></tr>";
				$count = 1;
				foreach ($customersBlocked as $cid => $blockStatus) {
					if ($count++ % 2 == 0)
						$class = "listAlt";
					else
						$class = "";
					$html .= "<tr class = '$class'><td>$cid</td><td>$blockStatus</td></tr>";
				}
				$html .= "</tbody></table>";
			} else {
				$html = "<h2>No customers found which contain this email address</h2>";
			}
			
			if ($ajax) {
				$form->fireEvent($html);
			} else {
				redirect("emailblock.php");
			}
		} else {
			if ($ajax)
				$form->fireEvent(json_encode($errors));
		}
	}
}


////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$TITLE = _L('Block Email Address');
$PAGE = 'advanced:emailblock';

include_once("nav.inc.php");
startWindow($TITLE);
echo $form->render();
endWindow();

startWindow("Result(s)");
?><div id="resultData"></div><?
endWindow();
?>

<script type="text/javascript">
	function SubmitTimerDisplay(form, target) {
		var $ = jQuery;
		var timeout = 30;
		var lastTimestamp = 0;
		var interval = 0;
		
		var form = $(form);
		var target = $(target);
		
		// cannot only monitor form submit, because this may be an ajax submit...
		var submitButton = form.find("button[type='submit']");
		
		this.init = function () {
			submitButton.on("click", function (event) {
				lastTimestamp = new Date().getTime();
				showCountdown();
			});

			// If the form was submitted correctly and completed execution...
			// kill the countdown, keep track of how long it took and show the results
			form.on("Form:Submitted", function (event, data) {
				clearCountdown();
				var timeoutMs = (new Date().getTime()) - lastTimestamp;
				timeout = Math.ceil(timeoutMs / 1000);
				// Display the result data
				target.html(data);
				// clear the form fields
				form.find("input[type='text']").val("");
			});

			// If any of the fields failed validation on submit to the server...
			// We need to kill the countdown only!
			form.on("Form:ValidationErrors", function (event, data) {
				clearCountdown();
				// clear the result data area
				target.html("");
			});
		};
		
		function showCountdown () {
			target.html("<h2> Aproximate time left: <span class='timeleft' style='color:#0000ff'>" + timeout + "</span> seconds</h2>");
			var timeleft = target.find(".timeleft");
			clearCountdown();
			interval = setInterval(function () {
				timeleft.html(Math.max(timeout--, 0));
			}, 1000);
		}
		
		function clearCountdown() {
			if (interval != 0)
				clearInterval(interval);
		}
	}
	(function ($) {
		var submitTimerDisplay = new SubmitTimerDisplay($("#<?=$form->name?>"), $("#resultData"));
		
		submitTimerDisplay.init();
	})(jQuery);
</script>

<?
include_once("navbottom.inc.php");
?>