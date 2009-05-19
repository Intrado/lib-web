<?



class JobWiz_basic extends WizStep {
	function getForm($postdata, $curstep) {
		
		$expresshtml = '
			<table style="text-align: center; font-size: 8pt;" cellpadding="0" cellspacing="0">
			<caption>ExpressCall:</caption>
				<tr>
					<th><img src="img/icon_phone.gif" alt="Phone"></th>
					<th><img src="img/icon_email.gif" alt="Email"></th>
					<th><img src="img/icon_sms.gif" alt="SMS"></th>
				</tr>
				<tr>
					<td valign="top"><img src="img/textfield.png" alt=""><br>Text-to-speach Phone with Translate.</td>
					<td valign="top"><img src="img/textfield.png" alt=""><br>Email with Translate.</td>
					<td valign="top"><img src="img/textfield.png" alt=""><br>Text SMS.</td>
				</tr>
			</table>';
		
		$formdata = array(
			"name" => array(
				"label" => "Name",
				"value" => "",
				"validators" => array(
					array("ValRequired"),
					array("ValLength","max" => 50)
				),
				"control" => array("TextField","maxlength" => 50),
				"helpstep" => 1
			),
			"jobtype" => array(
				"label" => "Type/Category",
				"value" => "",
				"validators" => array(
					array("ValRequired")
				),
				"control" => array("RadioButton", "values" => array(
					"general" => "General Announcement",
					"event" => "Special Event",
					"attendance" => "Attendance or Lunch Balance reminder",
					"emergency" => "Emergency!"
				)),
				"helpstep" => 2
			),
			"listmethod" => array(
				"label" => "Contact List",
				"validators" => array(
					array("ValRequired")
				),
				"value" => "",
				"control" => array("RadioButton", "values" => array(
					"rules" => "Match contacts using rules",
					//"enter" => "Manually enter contacts",
					"pick" => "Pick from existing Lists",
					//"book" => "Choose contacts from your address book",
					"search" => "Upload, Manually Enter, Choose from Address Book, or Search for individual contacts"
				)),
				"helpstep" => 3
			),
			"package" => array(
				"label" => "Notification Method",
				"validators" => array(
					array("ValRequired")
				),
				"value" => "",
				"control" => array("HtmlRadioButton", "values" => array(
					"easycall" => " EasyCall <br>(Record via Phone -> Automatic Email Recording -> Automatic SMS Message)",
					"express" => $expresshtml,
					"personalized" => "Personalized (Record via Phone / Type Email -> SMS)",
					"custom" => "Custom (Pick your own detailed options)"
				)),
				"helpstep" => 4
			),
		);
		$helpsteps = array (
			"Welcome to the Job Wizard. This is a guided 5 step process.",
			"This is your Job's name and Type. Job names are important, and should be descriptive. 
			A good example is 'Standardized testing reminder', or 'Early dismissal'.",
			"Job Types are used to determine which phones or emails we should notify. Choosing the appropriate Job Type is important for effective communication.",
			"Adding contacts based on rules allows you to specify rules like 'Everyone from school XYZ'.<br><br>You may also have predefined Lists, and use them here.<br><br>If you need to use the list upload feature, manually enter contacts, or create a custom list of contacts, you will need to use the List Builder.",
			"blah blah blah",
		);
		return new Form("basic",$formdata,$helpsteps);
	}
}

class JobWiz_list extends WizStep {
	function getForm($postdata, $curstep) {
		return new ListForm("list");
	}
}

/* ===================================================================*/
/* ===================================================================*/
/* ===================================================================*/



/*


class JobWiz_confirm_done extends WizStep {
	function getForm() {
		// Form Fields.
		$formdata = array(
			"confirmdone" => array(
				"label" => "",
				"validators" => array(
				),
				"value" => "",
				"control" => array("FormHtml", "html" => "Thank you."),
				"helpstep" => 1
			)
		);

		// Help Steps.
		$helpsteps = array(
			"Welcome to the Guide system. You can use this guide to walk through the form, or access it as needed by clicking to the right of a section",
			"What best describes your notification?"
		);

		return new Form("confirm_done",$formdata,$helpsteps);
	}
}


class JobWiz_confirm_testing extends WizStep {
	function getForm() {
		// Form Fields.	
		$formdata = array(
			"testdone" => array(
				"label" => "",
				"validators" => array(
				),
				"value" => "",
				"control" => array("FormHtml", "html" => "You will now receive a test notification, click Next when it has finished."),
				"helpstep" => 1
			)
		);

		// Help Steps.
		$helpsteps = array(
			"Welcome to the Guide system. You can use this guide to walk through the form, or access it as needed by clicking to the right of a section",
			"What best describes your notification?"
		);
		
		return new Form("confirm_testing",$formdata,$helpsteps);
	}
}


class JobWiz_confirm_test extends WizStep {
	function getForm() {
		// Form Fields.
		$formdata = array(
			"phone" => array(
				"label" => "Please enter your phone number",
				"validators" => array(
				),
				"value" => "8316001337",
				"control" => array("TextField"),
				"helpstep" => 1
			),
			"email" => array(
				"label" => "Please enter your Email",
				"validators" => array(
				),
				"value" => "kchan@schoolmessenger.com",
				"control" => array("TextField"),
				"helpstep" => 1
			),
			"sms" => array(
				"label" => "Please enter your Sms number",
				"validators" => array(
				),
				"value" => "8316001337",
				"control" => array("TextField"),
				"helpstep" => 1
			),
		);

		// Help Steps.
		$helpsteps = array(
			"Welcome to the Guide system. You can use this guide to walk through the form, or access it as needed by clicking to the right of a section",
			"What best describes your notification?"
		);
		
		return new Form("confirm_test",$formdata,$helpsteps);
	}
}


class JobWiz_confirm extends WizStep {
	function getForm() {
		// Form Fields.
		$formdata = array(
			"review" => array(
				"label" => "Please review your job:",
				"validators" => array(
				),
				"value" => "",
				"control" => array("FormHtml",
					"html"=> "A summary of the job, with play buttons and such.."
				),
				"helpstep" => 1
			),
			"confirm" => array(
				"label" => "How would you like to proceed?",
				"validators" => array(
					array("ValRequired")
				),
				"value" => "",
				"control" => array("RadioButton",
					"values"=> array("test" => "Send a Test to Myself", "submitjob" => "Submit Job"
				)),
				"helpstep" => 1
			)
		);

		// Help Steps.
		$helpsteps = array(
			"Welcome to the Guide system. You can use this guide to walk through the form, or access it as needed by clicking to the right of a section",
			"What best describes your notification?"
		);

		return new Form("confirm",$formdata,$helpsteps);
	}
}

class JobWiz_schedule extends WizStep {
	function getForm() {
		// Form Fields.
		$formdata = array(
			"scheduling" => array(
				"label" => "When would you like to send this job?",
				"validators" => array(
					array("ValRequired")
				),
				"value" => "now",
				"control" => array("RadioButton",
					"values"=> array("now" => "Immediately", "future" => "Future", "repeating" => "Repeating Schedule", "template" => "Do Not Schedule, Save as Template"
				)),
				"helpstep" => 1
			)
		);

		// Help Steps.
		$helpsteps = array(
			"Welcome to the Guide system. You can use this guide to walk through the form, or access it as needed by clicking to the right of a section",
			"What best describes your notification?"
		);

		return new Form("schedule",$formdata,$helpsteps);
	}
}



class JobWiz_schedule_more extends WizStep {
	function getForm() {
		// Form Fields.
		$formdata = array(
			"schedulemore" => array(
				"label" => "Scheduling Details....",
				"validators" => array(
					array("ValRequired")
				),
				"value" => "now",
				"control" => array("RadioButton",
					"values"=> array("special scheduling widget 1", "special scheduling widget 2", "special scheduling widget 3"
				)),
				"helpstep" => 1
			)
		);

		// Help Steps.
		$helpsteps = array(
			"Welcome to the Guide system. You can use this guide to walk through the form, or access it as needed by clicking to the right of a section",
			"What best describes your notification?"
		);
		
		return new Form("schedule_more",$formdata,$helpsteps);
	}
}


class JobWiz_email_translate extends WizStep {
	function getForm() {

		// Form Fields.
		$formdata = array(
			"translations" => array(
				"label" => "Here are your translations...",
				"validators" => array(
				),
				"value" => "",
				"control" => array("FormHtml", "html"=>"Include translation javascript code, which lets you override as well."),
				"helpstep" => 1
			)
		);

		// Help Steps.
		$helpsteps = array(
			"Welcome to the Guide system. You can use this guide to walk through the form, or access it as needed by clicking to the right of a section",
			"What best describes your notification?"
		);

		return new Form("email_translate",$formdata,$helpsteps);
	}
}

class JobWiz_phone_translate extends WizStep {
	function getForm() {

		// Form Fields.
		$formdata = array(
			"translations" => array(
				"label" => "Here are your translations...",
				"validators" => array(
				),
				"value" => "",
				"control" => array("FormHtml", "html"=>"Include translation javascript code, which lets you override as well."),
				"helpstep" => 1
			)
		);

		// Help Steps.
		$helpsteps = array(
			"Welcome to the Guide system. You can use this guide to walk through the form, or access it as needed by clicking to the right of a section",
			"What best describes your notification?"
		);

		return new Form("phone_translate",$formdata,$helpsteps);
	}
}

class JobWiz_sms_type extends WizStep {
	function getForm($postdata, $step) {
		$body = firstset($postdata['phone_type']['body'],$postdata['email_type']['body'],"");
		// Form Fields.
		$formdata = array(
			"body" => array(
				"label" => "Message body",
				"validators" => array(
					array("ValRequired")
				),
				"value" => $body,
				"control" => array("TextArea"),
				"helpstep" => 1
			)
		);

		// Help Steps.
		$helpsteps = array(
			"Welcome to the Guide system. You can use this guide to walk through the form, or access it as needed by clicking to the right of a section",
			"What best describes your notification?"
		);
		
		return new Form("sms_type",$formdata,$helpsteps;
	}
}

class JobWiz_phone_type extends WizStep {
	function getForm() {
		$formdata = array( // Message Body
			"body" => array(
				"label" => "Message body",
				"validators" => array(
					array("ValRequired")
				),
				"value" => "",
				"control" => array('TextArea', 'rows' => 6),
				"helpstep" => 1
			),
			"phonetranslatereview" => array( // Translation Checkbox.
				"label" => "Do you want to review translations?",
				"validators" => array(
				),
				"value" => "",
				"control" => array("Checkbox"),
				"helpstep" => 1
			)
		);

		// Help Steps.
		$helpsteps = array(
			"Welcome to the Guide system. You can use this guide to walk through the form, or access it as needed by clicking to the right of a section",
			"What best describes your notification?"
		);
		
		return new Form("phone_type",$formdata,$helpsteps);
	}
}



class JobWiz_email_type extends WizStep {
	function getForm() {		
		$jobname = $postdata['/basic']['name'];
		$body = firstset($postdata['phone_type']['body'],"");
		// Form Fields.
		$formdata = array(
			"subject" => array(
				"label" => 'Subject',
				"validators" => array(
					array("ValRequired")
				),
				"value" => $jobname,
				"control" => array("TextField"),
				"helpstep" => 1
			),
			"body" => array(
				"label" => "Message body",
				"validators" => array(
					array("ValRequired")
				),
				"value" => $body,
				"control" => array("TextArea"),
				"helpstep" => 1
			),
			"attachment" => array(
				"label" => "Attachment",
				"validators" => array(
				),
				"value" => "",
				"control" => array("TextField"),
				"helpstep" => 1
			),
			
			"emailtranslatereview" => array(
				"label" => "Do you want to review translations?",
				"validators" => array(
				),
				"value" => "",
				"control" => array("Checkbox"),
				"helpstep" => 1
			)
		);

		// Help Steps.
		$helpsteps = array(
			"Welcome to the Guide system. You can use this guide to walk through the form, or access it as needed by clicking to the right of a section",
			"What best describes your notification?"
		);
		
		return new Form("email_type",$formdata,$helpsteps);
	}

	

}

class JobWiz_email extends WizStep {
	function getForm() {

		
		if ($_SESSION['package'] == 'express' || $_SESSION['package'] == 'record') {
			// Form Fields.
			$formdata = array(
				"emailmethod" => array(
					"label" => "How would you like to send email messages?",
					"validators" => array(
						array("ValRequired")
					),
					"value" => "type",
					"control" => array("RadioButton", "values" => array(
						"type" => "Type a new message",
						"pick" => "Pick an existing message"
					)),
					"helpstep" => 1
				),
				"emailadvanced" => array(
					"label" => "Advanced Settings",
					"validators" => array(
					),
					"value" => "checked",
					"control" => array("FormHtml",
						"html" => "javascript toggle for advanced settings, Skip Duplicates"
				),
				"helpstep" => 1)
			);
		} else {
			// Form Fields.
			$formdata = array(
				"emailmethod" => array(
					"label" => "How would you like to send email messages?",
					"validators" => array(
						array("ValRequired")
					),
					"value" => "",
					"control" => array("RadioButton", "values" => array(
						"type" => "Type a new message",
						"pick" => "Pick an existing message"
					)),
					"helpstep" => 1
				),
				"emailadvanced" => array(
					"label" => "Advanced Settings",
					"validators" => array(
					),
					"value" => "checked",
					"control" => array("FormHtml",
						"html" => "javascript toggle for advanced settings, Skip Duplicates"
					),
					"helpstep" => 1
				)
			);
		}

		// Help Steps.
		$helpsteps = array(
			"Welcome to the Guide system. You can use this guide to walk through the form, or access it as needed by clicking to the right of a section",
			"What best describes your notification?"
		);
	
		return new Form("email",$formdata,$helpsteps);
	}
}


class JobWiz_sms extends WizStep {
	function getForm() {


		if ($_SESSION['package'] == 'express' || $_SESSION['package'] == 'record') {
			// Form Fields.
			$formdata = array(
				"smsmethod" => array(
					"label" => "How would you like to send sms messages?",
					"validators" => array(
						array("ValRequired")
					),
					"value" => 'type',
					"control" => array("RadioButton", "values" => array(
						"type" => "Type a new message",
						"pick" => "Pick an existing message"
					)),
					"helpstep" => 1
				)
			);
		} else {
			// Form Fields.
			$formdata = array(
				"smsmethod" => array(
					"label" => "How would you like to send sms messages?",
					"validators" => array(
						array("ValRequired")
					),
					"value" => 'type',
					"control" => array("RadioButton", "values" => array(
						"type" => "Type a new message",
						"pick" => "Pick an existing message"
					)),
					"helpstep" => 1
				)
			);
		}

		// Help Steps.
		$helpsteps = array(
			"Welcome to the Guide system. You can use this guide to walk through the form, or access it as needed by clicking to the right of a section",
			"What best describes your notification?"
		);

		return new Form("sms",$formdata,$helpsteps);
	}
	

}


class JobWiz_phone extends WizStep {
	function getForm() {
		if ($_SESSION['package'] == 'express') {
			// Form Fields.
			$formdata = array(
				"phonemethod" => array(
					"label" => "How would you like to send phone messages?",
					"validators" => array(
						array("ValRequired")
					),
					"value" => "type",
					"control" => array("RadioButton", "values" => array(
						"record" => "Call me to record",
						"type" => "Type a text-to-speech message",
						"pick" => "Pick an existing message"
					)),
					"helpstep" => 1
				),
				"phoneadvanced" => array(
					"label" => "Advanced Settings",
					"validators" => array(
					),
					"value" => "",
					"control" => array("FormHtml",
						"html" => "javascript toggle for advanced settings, includes Caller ID, Call Window, Skip Duplicates"
					),
					"helpstep" => 1
				)
			);
		} else if ($_SESSION['package'] == 'record') {
			// Form Fields.
			$formdata = array(
				"phonemethod" => array(
					"label" => "How would you like to send phone messages?",
					"validators" => array(
						array("ValRequired")
					),
					"value" => "record",
					"control" => array("RadioButton", "values" => array(
						"record" => "Call me to record",
						"type" => "Type a text-to-speech message",
						"pick" => "Pick an existing message"
					)),
					"helpstep" => 1
				),
				"phoneadvanced" => array(
					"label" => "Advanced Settings",
					"validators" => array(
					),
					"value" => "",
					"control" => array("FormHtml",
						"html" => "javascript toggle for advanced settings, includes Caller ID, Call Window, Skip Duplicates"
					),
					"helpstep" => 1
				)
			);
		} else {
			// Form Fields.
			$formdata = array(
				"phonemethod" => array(
					"label" => "How would you like to send phone messages?",
					"validators" => array(
						array("ValRequired")
					),
					"value" => "record",
					"control" => array("RadioButton", "values" => array(
						"record" => "Call me to record",
						"type" => "Type a text-to-speech message",
						"pick" => "Pick an existing message"
					)),
					"helpstep" => 1
				),
				"phoneadvanced" => array(
					"label" => "Advanced Settings",
					"validators" => array(
					),
					"value" => "",
					"control" => array("FormHtml",
						"html" => "javascript toggle for advanced settings, includes Caller ID, Call Window, Skip Duplicates"
					),
					"helpstep" => 1
				)
			);
		}

		// Help Steps.
		$helpsteps = array(
			"Welcome to the Guide system. You can use this guide to walk through the form, or access it as needed by clicking to the right of a section",
			"What best describes your notification?"
		);

		return new Form("phone",$formdata,$helpsteps);
	}
}

class JobWiz_settings extends WizStep {
	function getForm() {
		// Form Fields.
		$formdata = array(
			"callerid" => array(
				"label" => "Caller ID",
				"validators" => array(
				),
				"value" => "8316001337",
				"control" => array(
					"TextField"
				),
				"helpstep" => 1
			),
			"duplicates" => array(
				"label" => "Skip Duplicates",
				"validators" => array(
				),
				"value" => "1",
				"control" => array(
					"Checkbox"
				),
				"helpstep" => 1
			)
		);

		// Help Steps.
		$helpsteps = array(
			"Welcome to the Guide system. You can use this guide to walk through the form, or access it as needed by clicking to the right of a section",
			"What best describes your notification?"
		);
			
		return new Form("settings",$formdata,$helpsteps);
	}
}

class JobWiz_phone_record_refresh extends WizStep {
	function getForm() {
		// Form Fields.
		$formdata = array(
			"recording" => array(
				"label" => "",
				"validators" => array(
				),
				"value" => "",
				"control" => array(
					"FormHtml",
						"html" => "Calling 8316001337..., when you are finished recording, click Next to continue."
				),
				"helpstep" => 1
			)
		);

		// Help Steps.
		$helpsteps = array(
			"Welcome to the Guide system. You can use this guide to walk through the form, or access it as needed by clicking to the right of a section",
			"What best describes your notification?"
		);
		
		return new Form("phone_record_refresh",$formdata,$helpsteps);
	}
}

class JobWiz_phone_record extends WizStep {
	function getForm() {
		// Form Fields.
		$formdata = array(
			"recordphone" => array(
				"label" => "Please enter your phone number",
				"validators" => array(
					array("ValRequired"),
					array("ValNumeric")
				),
				"value" => "8316001337",
				"control" => array(
					"TextField"
				),
				"helpstep" => 1
			)
		);

		// Help Steps.
		$helpsteps = array(
			"Welcome to the Guide system. You can use this guide to walk through the form, or access it as needed by clicking to the right of a section",
			"What best describes your notification?"
		);
	
		return new Form("phone_record",$formdata,$helpsteps);
	}
}

class JobWiz_describe extends WizStep {
	function getForm() {
		unset($_SESSION['samemessage']);
		
		// Form Fields.
		$formdata = array(
			"package" => array(
				"label" => "What notification package would you like to use?",
				"validators" => array(
					array("ValRequired")
				),
				"value" => "express",
				"control" => array("RadioButton", "values" => array(
					"express" => "Express TTS/Email/SMS",
					"record" => "Record/Email/SMS",
					"custom" => "Custom"
				)),
				"helpstep" => 1
			)
		);

		// Help Steps.
		$helpsteps = array(
			"Welcome to the Guide system. You can use this guide to walk through the form, or access it as needed by clicking to the right of a section",
			"Express TTS/Email/SMS with Autotranslate will automatically translate messages and reuse the same message for all delivery types.<br/>Record/Email/SMS will let you record your phone message, then let you type an Email message, which will automatically be set for the SMS message."
		);
		
		return new Form("describe",$formdata,$helpsteps);
	}
}




class JobWiz_jobname extends WizStep {
	function getForm() {


	
		
		// Form Fields.
		$formdata = array(
			"jobname" => array(
				"label" => "Job Name",
				"validators" => array(
					array("ValRequired")
				),
				"value" => '',
				"control" => array("TextField"),
				"helpstep" => 1
			),
			"jobtype" => array(
				"label" => "What best describes the job?",
				"validators" => array(
					array("ValRequired")
				),
				"value" => "",
				"control" => array("RadioButton", "values" => array(
					"general" => "General Announcement",
					"event" => "Special Event",
					"attendance" => "Attendance or Lunch Balance reminder",
					"emergency" => "Emergency!"
				)),
				"helpstep" => 1
			),
			"package" => array(
				"label" => "Choose an appropriate Package",
				"validators" => array(
					array("ValRequired")
				),
				"value" => "",
				"control" => array("RadioButton", "values" => array(
					"quickcall" => "Quick Call (Record Phone/audio attachment Email/callback # SMS)",
					"express" => "Express TTS (TTS Phone/paste Email/paste SMS)",
					"record" => "Basic Combo (Record Phone/type Email/paste SMS)",
					"custom" => "Custom"
				)),
				"helpstep" => 1
			),
			"listmethod" => array(
				"label" => "How would you like to add contacts?",
				"validators" => array(
					array("ValRequired")
				),
				"value" => "",
				"control" => array("RadioButton", "values" => array(
					"rules" => "Match contacts using rules",
					//"enter" => "Manually enter contacts",
					"pick" => "Pick an existing list",
					//"book" => "Choose contacts from your address book",
					"search" => "Upload, Manually Enter, Choose from Address Book, or Search for individual contacts"
				)),
				"helpstep" => 3
			)
		);

		// Help Steps.
		$helpsteps = array(
			"Welcome to the Guide system. You can use this guide to walk through the form, or access it as needed by clicking to the right of a section",
			"Express TTS/Email/SMS with Autotranslate will automatically translate messages and reuse the same message for all delivery types.<br/>Record/Email/SMS will let you record your phone message, then let you type an Email message, which will automatically be set for the SMS message."
		);
	
		return new Form("jobname",$formdata,$helpsteps);
	}
}


class JobWiz_list_addmore extends WizStep {
	function getForm() {
		$formdata = array(
			"sessioncontacts" => array(
				"label" => "",
				"validators" => array(
				),
				"value" => "",
				"control" => array("FormHtml",
					"html" => "Your lists:<ul>" . implode(' ', $_SESSION['sessioncontacts']) . "</li>"
				),
				"helpstep" => 3
			),
			"listmethod" => array(
				"label" => "Would you like to add more contacts?",
				"validators" => array(
				),
				"value" => "",
				"control" => array("RadioButton",
					"values" => array(
						"done" => "No, I'm done adding contacts.",
						"rules" => "Match contacts using rules",
						//"enter" => "Manually enter contacts",
						"pick" => "Pick an existing list",
						//"book" => "Choose contacts from your address book",
						"search" => "Upload, Manually Enter, Choose from Address Book, or Search for individual contacts"
					)
				),
				"helpstep" => 3
			)
		);

		// Help Steps.
		$helpsteps = array(
			"Welcome to the Guide system. You can use this guide to walk through the form, or access it as needed by clicking to the right of a section",
			"Please choose a method for entering your contacts"
		);

		return new Form("list_addmore",$formdata,$helpsteps);
	}
}

class JobWiz_list_rules extends WizStep {
	function getForm() {
		// Form Fields.
		$formdata = array(
			"rules" => array(
				"label" => "Apply Rules to Match Contacts",
				"validators" => array(
				),
				"value" => "",
				"control" => array("FormHtml", "html" => '<iframe style="border: 0" scrolling="no" width="100%" height="400px" src="http://10.25.25.133/mockup2"></iframe>'),
				"helpstep" => 3
			)
		);

		// Help Steps.
		$helpsteps = array(
			"Welcome to the Guide system. You can use this guide to walk through the form, or access it as needed by clicking to the right of a section",
			"Please choose a method for entering your contacts"
		);

		return new Form("list_rules",$formdata,$helpsteps);
	}
}

class JobWiz_emergency extends WizStep {
	function getForm() {
		// Form Fields.
		$formdata = array(
			"emergency" => array(
				"label" => "What best describes the emergency?",
				"validators" => array(
					array("ValRequired")
				),
				"value" => "",
				"control" => array("RadioButton", "values" => array(
					"emergency1" => "There is an immediate threat, people need to be notified right away.",
					"emergency2" => "School is closed due to unexpected weather or hazards.",
				)),
				"helpstep" => 3
			)
		);

		// Help Steps.
		$helpsteps = array(
			"Welcome to the Guide system. You can use this guide to walk through the form, or access it as needed by clicking to the right of a section",
			"Please choose a method for entering your contacts"
		);
	
		return new Form("emergency",$formdata,$helpsteps);
	}
}


class JobWiz_message_method extends WizStep {
	function getForm() {


		if ($_SESSION['package'] === 'express') {
			// Form Fields.
			$formdata = array(
				"messagemethods" => array(
					"label" => "Your contacts can receive the following types of messages, choose the types you'd like to send:",
					"validators" => array(
						array("ValRequired")
					),
					"value" => array('phone', 'email', 'sms'),
					"control" => array("MultiCheckbox", "values" => array(
						"phone" => "90% can receive Phone messages",
						"email" => "60% can receive Email messages",
						"sms" => "22% can receive SMS messages"
					)),
					"helpstep" => 3
				),
				"messagelanguages" => array(
					"label" => "Your contacts speak the following languages, check the ones to include for your messages:",
					"validators" => array(
						array("ValRequired")
					),
					"value" => array('English', 'Spanish', 'Chinese'),
					"control" => array("MultiCheckbox", "values" => array(
						"English" => "98% speak English",
						"Spanish" => "15% speak Spanish",
						"Chinese" => "8% speak Chinese"
					)),
					"helpstep" => 3
				)
			);
		} else if ($_SESSION['package'] === 'record') {
			// Form Fields.
			$formdata = array(
				"messagemethods" => array(
					"label" => "Your contacts can receive the following types of messages, choose the types you'd like to send:",
					"validators" => array(
						array("ValRequired")
					),
					"value" => array('phone', 'email', 'sms'),
					"control" => array("MultiCheckbox", "values" => array(
						"phone" => "90% can receive Phone messages",
						"email" => "60% can receive Email messages",
						"sms" => "22% can receive SMS messages"
					)),
					"helpstep" => 3
				),
				"messagelanguages" => array(
					"label" => "Your contacts speak the following languages, check the ones to include for your messages:",
					"validators" => array(
						array("ValRequired")
					),
					"value" => array('English', 'Spanish', 'Chinese'),
					"control" => array("MultiCheckbox", "values" => array(
						"English" => "98% speak English",
						"Spanish" => "15% speak Spanish",
						"Chinese" => "8% speak Chinese"
					)),
					"helpstep" => 3
				)
			);
		} else if ($_SESSION['package'] === 'custom') {
			// Form Fields.
			$formdata = array(
				"messagemethods" => array(
					"label" => "Your contacts can receive the following types of messages, choose the types you'd like to send:",
					"validators" => array(
						array("ValRequired")
					),
					"value" => "phone",
					"control" => array("MultiCheckbox", "values" => array(
						"phone" => "Phone: 90%",
						"email" => "Email: 60%",
						"sms" => "SMS: 22%"
					)),
					"helpstep" => 3
				),
				"messagelanguages" => array(
					"label" => "Your contacts speak the following languages, check the ones to include for your messages:",
					"validators" => array(
						array("ValRequired")
					),
					"value" => "English",
					"control" => array("MultiCheckbox", "values" => array(
						"English" => "English: 98%",
						"Spanish" => "Spanish: 1%",
						"Chinese" => "Chinese: 1%"
					)),
					"helpstep" => 3
				)
			);
		}

		// Help Steps.
		$helpsteps = array(
			"Welcome to the Guide system. You can use this guide to walk through the form, or access it as needed by clicking to the right of a section",
			"Please choose a method for entering your contacts"
		);
	
		return new Form("message_method",$formdata,$helpsteps);
	}
}

class JobWiz_list_method extends WizStep {
	function getForm() {
		// Form Fields.
		$formdata = array(
			"listmethod" => array(
				"label" => "How would you like to add contacts?",
				"validators" => array(
					array("ValRequired")
				),
				"value" => "rules",
				"control" => array("RadioButton", "values" => array(
					"rules" => "Match contacts using rules",
					//"enter" => "Manually enter contacts",
					"upload" => "Upload a set of contacts",
					"pick" => "Pick an existing list",
					//"book" => "Choose contacts from your address book",
					"search" => "Manually Enter, Choose from Address Book, or Search for individual contacts"
				)),
				"helpstep" => 3
			)
		);

		// Help Steps.
		$helpsteps = array(
			"Welcome to the Guide system. You can use this guide to walk through the form, or access it as needed by clicking to the right of a section",
			"Please choose a method for entering your contacts"
		);

		return new Form("list_method",$formdata,$helpsteps);
	}
}


class JobWiz_list_search extends WizStep {
	function getForm() {
		// Form Fields.
		$formdata = array(
			"listsearch" => array(
				"label" => "",
				"validators" => array(
				),
				"value" => "",
				"control" => array("FormHtml",
					"html" => "Sorry, searching for contacts does not fit within the scope of the job builder. If you would like to do so, please use the List Builder."
				),
				"helpstep" => 3
			)
		);

		// Help Steps.
		$helpsteps = array(
			"Welcome to the Guide system. You can use this guide to walk through the form, or access it as needed by clicking to the right of a section",
			"Please use the List Builder if you'd like to search for individual contact."
		);
		
		return new Form("list_search",$formdata,$helpsteps);
	}
}
*/
?>
