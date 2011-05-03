<?

////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
global $USER;
if (!$USER->authorize("sendemail"))
	redirect('unauthorized.php');

////////////////////////////////////////////////////////////////////////////////
// Form Data
////////////////////////////////////////////////////////////////////////////////

// get value from passed message, or default some values if not set
// relys on including form having a $message and $messagegroup object already created.
$fromname = $USER->firstname . " " . $USER->lastname;
$fromemail = $USER->email;
$subject = $messagegroup->name;
$attachments = array();
$text = "";
if ($message) {
	// get the specific bits from the message if it exists
	$parts = DBFindMany("MessagePart", "from messagepart where messageid = ? order by sequence", false, array($message->id));
	$text = Message::format($parts);
	
	$fromname = $message->fromname;
	$fromemail = $message->fromemail;
	$subject = $message->subject;
	
	// get the attachments
	$message->readHeaders();
	$msgattachments = DBFindMany("MessageAttachment", "from messageattachment where not deleted and messageid = ?", false, array($message->id));
	foreach ($msgattachments as $msgattachment)
		$attachments[$msgattachment->contentid] = array("name" => $msgattachment->filename, "size" => $msgattachment->size);
}

if (!$languagecode)
	$languagecode = "en";

$language = Language::getName($languagecode);

$formdata = array($messagegroup->name. " (". $language. ")");

$helpsteps[] = array(_L("Enter the name this email will appear as coming from."));
$formdata["fromname"] = array(
	"label" => _L('From Name'),
	"fieldhelp" => _L('Recipients will see this name as the sender of the email.'),
	"value" => $fromname,
	"validators" => array(
			array("ValRequired"),
			array("ValLength","max" => 50)
			),
	"control" => array("TextField","size" => 25, "maxlength" => 50),
	"helpstep" => 1
);

$helpsteps[] = array(_L("Enter the address where you would like to receive replies."));
$formdata["from"] = array(
	"label" => _L("From Email"),
	"fieldhelp" => _L('This is the address the email is coming from. Recipients will also be able to reply to this address.'),
	"value" => $fromemail,
	"validators" => array(
		array("ValRequired"),
		array("ValLength","max" => 255),
		array("ValEmail", "domain" => getSystemSetting('emaildomain'))
		),
	"control" => array("TextField","max"=>255,"min"=>3,"size"=>35),
	"helpstep" => 2
);

$helpsteps[] = _L("Enter the subject of the email here.");
$formdata["subject"] = array(
	"label" => _L("Subject"),
	"fieldhelp" => _L('The Subject will appear as the subject line of the email.'),
	"value" => $subject,
	"validators" => array(
		array("ValRequired"),
		array("ValLength","max" => 255)
	),
	"control" => array("TextField","max"=>255,"min"=>3,"size"=>45),
	"helpstep" => 3
);

$helpsteps[] = _L("You may attach up to three files that are up to 2MB each. For greater security, only certain types of files are accepted.<br><br><b>Note:</b> Some email accounts may not accept attachments above a certain size and may reject your message.");
$formdata["attachments"] = array(
	"label" => _L('Attachments'),
	"fieldhelp" => _L("You may attach up to three files that are up to 2MB each. For greater security, certain file types are not permitted. Be aware that some email accounts may not accept attachments above a certain size and may reject your message."),
	"value" => ($attachments?json_encode($attachments):"{}"),
	"validators" => array(array("ValEmailAttach")),
	"control" => array("EmailAttach"),
	"helpstep" => 4
);

$helpsteps[] = _L("Email message body text goes here. Be sure to introduce yourself and give detailed information. For helpful message tips and ideas, click the Help link in the upper right corner of the screen.");
$formdata["message"] = array(
	"label" => _L("Email Message"),
	"fieldhelp" => _L('Enter the message you would like to send. Helpful tips for successful messages can be found at the Help link in the upper right corner.'),
	"value" => $text,
	"validators" => array(
		array("ValRequired"),
		array("ValMessageBody")
	),
	"control" => array("EmailMessageEditor", "subtype" => $subtype),
	"helpstep" => 5
);

$buttons = array(submit_button(_L('Save'),"submit","tick"),
				icon_button(_L('Cancel'),"cross",null,"mgeditor.php?id=".$messagegroup->id));
$form = new Form("emaileedit",$formdata,$helpsteps,$buttons);

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