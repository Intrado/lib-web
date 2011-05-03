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
	$parts = DBFindMany("MessagePart", "from messagepart where messageid = ? order by sequence", false, array($message->id));
	$text = Message::format($parts);
	$message->readHeaders();
}

if (!$languagecode)
	$languagecode = "en";

$language = Language::getName($languagecode);

$formdata = array($messagegroup->name. " (". $language. ")");

// upload audio needs this session data
$_SESSION['messagegroupid'] = $messagegroup->id;

$formdata = array(
	$messagegroup->name. " (". $language. ")",
	"message" => array(
		"label" => _L("Advanced Message"),
		"value" => "",
		"validators" => array(array("ValRequired")),
		"control" => array("PhoneMessageEditor", "langcode" => $languagecode, "messagegroupid" => $messagegroup->id),
		"helpstep" => 1
	),
	"gender" => array(
		"label" => _L("Gender"),
		"value" => "",
		"validators" => array(array("ValRequired")),
		"control" => array("RadioButton", "values" => array("female" => _L("Female"), "male" => _L("Male"))),
		"helpstep" => 2
	),
	"preview" => array(
		"label" => "",
		"value" => "",
		"validators" => array(),
		"control" => array("InpageSubmitButton", "name" => "preview", "icon" => "fugue/control"),
		"helpstep" => 3
	)
);

$helpsteps = array(_L("TODO: Help me!"),
				_L("TODO: Help me!"),
				_L("TODO: Help me!"));
		
$buttons = array(submit_button(_L('Save'),"submit","tick"),
				icon_button(_L('Cancel'),"cross",null,"mgeditor.php?id=".$messagegroup->id));
$form = new Form("phoneadvanced",$formdata,$helpsteps,$buttons,"vertical");

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