<?

////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
global $USER;
if (!$USER->authorize("sendphone") || !$USER->authorize("starteasy"))
	redirect('unauthorized.php');

////////////////////////////////////////////////////////////////////////////////
// Form Data
////////////////////////////////////////////////////////////////////////////////

// get value from passed message, or default some values if not set
// relys on including form having a $message and $messagegroup object already created.
$text = "";
if ($message) {
	// get the specific bits from the message if it exists
	$parts = DBFindMany("MessagePart", "from messagepart where messageid = ? order by sequence", false, array($message->id));
	$text = Message::format($parts);
}

if (!$languagecode)
	$languagecode = "en";

$language = Language::getName($languagecode);

$formdata = array($messagegroup->name. " (". $language. ")");

$formdata["message"] = array(
	"label" => _L("SMS Text"),
	"value" => $text,
	"validators" => array(
		array("ValRequired"),
		array("ValLength","max"=>160),
		array("ValRegExp","pattern" => getSmsRegExp())
	),
	"control" => array("TextArea","rows"=>5,"cols"=>35,"counter"=>160),
	"helpstep" => 1
);

$helpsteps = array(_L("Enter the message you wish to deliver via SMS Text."));

$buttons = array(submit_button(_L('Save'),"submit","tick"),
				icon_button(_L('Cancel'),"cross",null,"mgeditor.php?id=".$messagegroup->id));
$form = new Form("phonerecord",$formdata,$helpsteps,$buttons);

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
	} else if (($errors = $form->validate()) === false) { //checks all of the items in this form
		$postdata = $form->getData(); //gets assoc array of all values {name:value,...}
		Query("BEGIN");
		
		//save data here	
		
		
		Query("COMMIT");
		if ($ajax)
			$form->sendTo("mgeditor.php?id=".$messagegroup->id);
		else
			redirect("mgeditor.php?id=".$messagegroup->id);
	}
}
?>