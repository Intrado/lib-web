<?



////////////////////////////////////////////////////////////////////////////////
// Form Items
////////////////////////////////////////////////////////////////////////////////

class MsgWiz_start extends WizStep {
	function getForm($postdata, $curstep) {
		global $USER;

		// message icon button details
		$messagetypedetails = array (
			'sendphone' => array(
				"description" => _L("Create a new phone message"),
				"icon" => "img/largeicons/phonehandset.jpg",
				"label" => _L("Phone"),
				"enabled" => false),
			'sendemail' => array(
				"description" => _L("Create a new email message"),
				"icon" => "img/largeicons/email.jpg",
				"label" => _L("Email"),
				"enabled" => false),
			'sendsms' => array(
				"description" => _L("Create a new text message"),
				"icon" => "img/largeicons/smschat.jpg",
				"label" => _L("Text"),
				"enabled" => false)
		);
		
		// user authorized message types
		foreach (array('sendphone', 'sendemail', 'sendsms') as $type) {
			if ($USER->authorize($type)) {
				$messagetypedetails[$type]['enabled'] = true;
			}
		}
		
		$messagetypes = array();
		$values = array();
		foreach ($messagetypedetails as $type => $details) {
			if ($type['enabled']) {
				$values[] = $type;
				$messagetypes[$type] ='
					<table align="left" style="border: 0px; margin: 0px; padding: 0px">
						<tr>
							<td style="border: 0px; margin: 0px; padding: 0px" align="center" valign="center">
								<div style="width: 94px; height: 88px; background: url('.$details['icon'].') no-repeat;">
									<div style="position: relative; top: 67px; width: 100%; font-size: 10px">
										'.escapehtml($details['label']).'
									</div>
								</div>
							</td>
							<td style="border: 0px; margin: 0px; padding: 0px;" align="left" valign="center">
								'.escapehtml($details["description"]).'
							</td>
						</tr>
					</table>';
			}
		}
		

		$formdata = array(
			$this->title,
			"messagetype" => array(
				"label" => _L("Message Type"),
				"fieldhelp" => _L("Choose a message type."),
				"validators" => array(
					array("ValRequired"),
					array("ValInArray", "values" => array_keys($messagetypes))
				),
				"value" => "",
				"control" => array("HtmlRadioButtonBigCheck", "values" => $messagetypes),
				"helpstep" => 1)
		);
		
		return new Form("start",$formdata,array(_L("TODO: guide?")));
	}
}


class MsgWiz_method extends WizStep {
	function getForm($postdata, $curstep) {
		global $USER;

		// message icon button details
		$methoddetails = array (
			'record' => array(
				"icon" => "img/record.gif",
				"label" => _L("Record"),
				"enabled" => false),
			'write' => array(
				"icon" => "img/write.gif",
				"label" => _L("Write"),
				"enabled" => false),
			'custom' => array(
				"icon" => "img/customize.gif",
				"label" => _L("Advanced"),
				"enabled" => false)
		);
		
		// enable appropriate items based on message type
		switch ($postdata["/start"]['messagetype']) {
			case "sendphone":
				if ($USER->authorize("starteasy"))
					$methoddetails["record"]["enabled"] = true;
				
				$methoddetails["write"]["enabled"] = true;
				$methoddetails["custom"]["enabled"] = true;
				
				$methoddetails["record"]["description"] = 
					'<ol>
						<li class="wizbuttonlist">'.escapehtml(_L("Record an audio phone message using EasyCall")).'</li>
						<li class="wizbuttonlist">'.escapehtml(_L("Record over the phone")).'</li>
						<li class="wizbuttonlist">'.escapehtml(_L("Call yourself or a colleague")).'</li>
					</ol>';
				$methoddetails["write"]["description"] = 
					'<ol>
						<li class="wizbuttonlist">'.escapehtml(_L("Write a simple phone message")).'</li>
						<li class="wizbuttonlist">'.escapehtml(_L("Text-to-speech")).'</li>
						<li class="wizbuttonlist">'.escapehtml(_L("Auto-translate available for English messages")).'</li>
					</ol>';
				$methoddetails["custom"]["description"] = 
					'<ol>
						<li class="wizbuttonlist">'.escapehtml(_L("Upload Pre-recorded Audio")).'</li>
						<li class="wizbuttonlist">'.escapehtml(_L("Wav, Mp3, Au Format Support")).'</li>
						<li class="wizbuttonlist">'.escapehtml(_L("Use advanced features like field inserts")).'</li>
					</ol>';
				break;
				
			case "sendemail":
				$methoddetails["write"]["enabled"] = true;
				$methoddetails["custom"]["enabled"] = true;
				
				$methoddetails["write"]["description"] = 
					'<ol>
						<li class="wizbuttonlist">'.escapehtml(_L("Write a simple email message")).'</li>
						<li class="wizbuttonlist">'.escapehtml(_L("Auto-translate available for English messages")).'</li>
					</ol>';
				$methoddetails["custom"]["description"] = 
					'<ol>
						<li class="wizbuttonlist">'.escapehtml(_L("Enhanced email message")).'</li>
						<li class="wizbuttonlist">'.escapehtml(_L("Use advanced features like field inserts")).'</li>
					</ol>';
				break;
				
			case "sendsms":
				// should never happen. This step is disabled for SMS
				break;
		}
			
		$methods = array();
		$values = array();
		foreach ($methoddetails as $type => $details) {
			if ($details['enabled']) {
				$values[] = $type;
				$methods[$type] ='
					<table align="left" style="border: 0px; margin: 0px; padding: 0px">
						<tr>
							<td style="border: 0px; margin: 0px; padding: 0px" align="center" valign="center">
								<div style="width: 94px; height: 88px; background: url('.$details['icon'].') no-repeat;">
									<div style="position: relative; top: 67px; width: 100%; font-size: 10px">
										'.escapehtml($details['label']).'
									</div>
								</div>
							</td>
							<td style="border: 0px; margin: 0px; padding: 0px;" align="left" valign="center">
								'.$details["description"].'
							</td>
						</tr>
					</table>';
			}
		}
		

		$formdata = array(
			$this->title,
			"method" => array(
				"label" => _L("Method"),
				"fieldhelp" => _L("Choose a method."),
				"validators" => array(
					array("ValRequired"),
					array("ValInArray", "values" => array_keys($methods))
				),
				"value" => "",
				"control" => array("HtmlRadioButtonBigCheck", "values" => $methods),
				"helpstep" => 1)
		);
		
		return new Form("method",$formdata,array(_L("TODO: guide?")));
	}

	//returns true if this step is enabled
	function isEnabled($postdata, $step) {
		if (isset($postdata["/start"]["messagetype"]) && $postdata["/start"]["messagetype"] == "sendsms")
			return false;
		
		return true;
	}
}


class MsgWiz_language extends WizStep {
	function getForm($postdata, $curstep) {
		
		$langs = array();
		$langs["en"] = '<div style="text-align:left">'. _L("Write the <b>Default/English</b> message"). '</div>';
		// TODO: alpha sort, but with english as the first entry
		foreach (Language::getLanguageMap() as $key => $lang) {
			if ($lang != "English")
				$langs[$key] = '<div style="text-align:left">'. _L("Write the <b>%s</b> message", $lang). '</div>';
		}
		
		$langs["autotranslate"] = "Automatically translate to other languages";
		
		$formdata = array(
			$this->title,
			"language" => array(
				"label" => _L("Language"),
				"fieldhelp" => _L("Choose a language."),
				"validators" => array(
					array("ValRequired"),
					array("ValInArray", "values" => array_keys($langs))
				),
				"value" => "",
				"control" => array("HtmlRadioButtonBigCheck", "values" => $langs),
				"helpstep" => 1)
		);
		
		return new Form("start",$formdata,array(_L("TODO: guide?")));
	}

	//returns true if this step is enabled
	function isEnabled($postdata, $step) {
		global $USER;
		
		// SMS only supports english
		if (isset($postdata["/start"]["messagetype"]) && $postdata["/start"]["messagetype"] == "sendsms")
			return false;
		
		// users who can't send multi lingual don't get language selection
		if ($USER->authorize("sendmulti"))
			return true;
		
		return false;
	}
}


class MsgWiz_phoneText extends WizStep {
	function getForm($postdata, $curstep) {
		global $USER;
		// Form Fields.
		$helpsteps = array(_L("Enter your message text in the provided text area. Be sure to introduce yourself and give detailed information, including call back information if appropriate."));
		$formdata = array(
			$this->title,
			"message" => array(
				"label" => _L("Phone Message"),
				"fieldhelp" => _L('Enter the message you would like to send. Helpful tips for successful messages can be found at the Help link in the upper right corner.'),
				"value" => "",
				"validators" => array(
					array("ValRequired"),
					array("ValTextAreaPhone")
				),
				"control" => array("TextAreaPhone","width"=>"80%","rows"=>10,"language"=>"en","voice"=>"female"),
				"helpstep" => 1
			)
		);

		if ($USER->authorize('sendmulti')
				&& isset($postdata["/create/language"]["language"]) && $postdata["/create/language"]["language"] == "autotranslate") {
			$helpsteps[] = _L("Automatically translate into alternate languages powered by Google Translate.");
			$formdata["translate"] = array(
				"label" => _L("Translate"),
				"fieldhelp" => _L('Check here if you would like to use automatic translation. Remember automatic translation is improving all the time, but it\'s not perfect yet. Be sure to preview and try reverse translation in the next screen.'),
				"value" => false,
				"validators" => array(),
				"control" => array("CheckBox"),
				"helpstep" => 2
			);
		}

		return new Form("phoneText",$formdata,$helpsteps);
	}

	//returns true if this step is enabled
	function isEnabled($postdata, $step) {
		if (isset($postdata["/start"]["messagetype"]) && $postdata["/start"]["messagetype"] == "sendphone"
				&& isset($postdata["/method"]["method"]) && $postdata["/method"]["method"] == "write")
			return true;
		
		return false;
	}
}


class MsgWiz_phoneTranslate extends WizStep {

	function getForm($postdata, $curstep) {
		$formdata['todo'] = array(
			"label" => _L('todo'),
			"control" => array("FormHtml", "html" => 'TODO: implement me'),
				"helpstep" => 1
			);

		return new Form("phoneTranslate",$formdata,array());
	}

	//returns true if this step is enabled
	function isEnabled($postdata, $step) {
		global $USER;
		if (!$USER->authorize("sendphone") || !$USER->authorize("sendmulti"))
			return false;
		
		if (isset($postdata["/start"]["messagetype"]) && $postdata["/start"]["messagetype"] == "sendphone"
				&& isset($postdata["/language"]["language"]) && $postdata["/language"]["language"] == "en"
				&& isset($postdata["/method"]["method"]) && $postdata["/method"]["method"] == "write"
				&& isset($postdata["/create/phonetext"]["translate"]) && $postdata["/create/phonetext"]["translate"])
			return true;
		
		return false;
	}
}


class MsgWiz_phoneEasyCall extends WizStep {
	function getForm($postdata, $curstep) {
		global $USER;
		
		// Phone message recorder will store the audiofile with this name
		$language = Language::getName(isset($postdata["/language"]["language"])?$postdata["/language"]["language"]:"en");
		
		$formdata = array($this->title);
		$formdata['tips'] = array(
			"label" => _L('Message Tips'),
			"control" => array("FormHtml", "html" => '
				<ul>
					<li class="wizbuttonlist">'.escapehtml(_L('Introduce yourself')).'</li>
					<li class="wizbuttonlist">'.escapehtml(_L('Clearly state the reason for the call')).'</li>
					<li class="wizbuttonlist">'.escapehtml(_L('Repeat important information')).'</li>
					<li class="wizbuttonlist">'.escapehtml(_L('Instruct recipients what to do should they have questions ')).'</li>
				</ul>
				'),
				"helpstep" => 1
			);
		$formdata["message"] = array(
			"label" => _L("Voice Recordings"),
			"fieldhelp" => _L("TODO: field help"),
			"value" => "",
			"validators" => array(
				array("ValRequired"),
				array("PhoneMessageRecorderValidator")
			),
			"control" => array( "PhoneMessageRecorder", "phone" => $USER->phone, "name" => $language),
			"helpstep" => 1
		);
		$helpsteps[] = _L("The system will call you at the number you enter in this form and guide you through a series of prompts to record your message. The default message is always required and will be sent to any recipients who do not have a language specified.<br><br>
		Choose which language you will be recording in and enter the phone number where the system can reach you. Then click \"Call Me to Record\" to get started. Listen carefully to the prompts when you receive the call. You may record as many different langauges as you need.
		");

		return new Form("phoneEasyCall",$formdata,$helpsteps);
	}

	//returns true if this step is enabled
	function isEnabled($postdata, $step) {
		if (isset($postdata["/start"]["messagetype"]) && $postdata["/start"]["messagetype"] == "sendphone"
				&& isset($postdata["/method"]["method"]) && $postdata["/method"]["method"] == "record")
			return true;
		
		return false;
	}
}


class MsgWiz_emailText extends WizStep {
	function getForm($postdata, $curstep) {
		global $USER;
		$msgdata = isset($postdata['/message/phone/text']['message'])?json_decode($postdata['/message/phone/text']['message']):json_decode('{"text": ""}');
		// Form Fields.
		$formdata = array($this->title);

		$helpsteps = array(_L("Enter the name for the email account."));
		$formdata["fromname"] = array(
			"label" => _L('From Name'),
			"fieldhelp" => _L('Recipients will see this name as the sender of the email.'),
			"value" => $USER->firstname . " " . $USER->lastname,
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
			"value" => $USER->email,
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
			"value" => "", // TODO: message group name
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
			"value" => "",
			"validators" => array(array("ValEmailAttach")),
			"control" => array("EmailAttach"),
			"helpstep" => 4
		);

		$helpsteps[] = _L("Email message body text goes here. Be sure to introduce yourself and give detailed information. For helpful message tips and ideas, click the Help link in the upper right corner of the screen.");
		$formdata["message"] = array(
			"label" => _L("Email Message"),
			"fieldhelp" => _L('Enter the message you would like to send. Helpful tips for successful messages can be found at the Help link in the upper right corner.'),
			"value" => $msgdata->text,
			"validators" => array(
				array("ValRequired"),
				array("ValMessageBody")
			),
			"control" => array("HtmlTextArea","rows"=>10),
			"helpstep" => 5
		);

		if ($USER->authorize('sendmulti')
				&& isset($postdata["/create/language"]["language"]) && $postdata["/create/language"]["language"] == "autotranslate") {
			$helpsteps[] = _L("Automatically translate into alternate languages. Please note that automatic translation is always improving, but is not perfect yet. Try reverse translating your message for a preview of how well it translated.");
			$formdata["translate"] = array(
				"label" => _L("Translate"),
				"fieldhelp" => _L('Check this box to automatically translate your message using Google Translate.'),
				"value" => false,
				"validators" => array(),
				"control" => array("CheckBox"),
				"helpstep" => 6
			);
		}

		return new Form("emailText",$formdata,$helpsteps);
	}

	//returns true if this step is enabled
	function isEnabled($postdata, $step) {
		if (isset($postdata["/start"]["messagetype"]) && $postdata["/start"]["messagetype"] == "sendemail"
				&& isset($postdata["/method"]["method"]) && $postdata["/method"]["method"] == "write")
			return true;
		
		return false;
	}
}


class MsgWiz_emailTranslate extends WizStep {
	function getForm($postdata, $curstep) {
		$formdata['todo'] = array(
			"label" => _L('todo'),
			"control" => array("FormHtml", "html" => 'TODO: implement me'),
				"helpstep" => 1
			);
		return new Form("emailTranslate",$formdata,array());
		
	}

	//returns true if this step is enabled
	function isEnabled($postdata, $step) {
		if (isset($postdata["/start"]["messagetype"]) && $postdata["/start"]["messagetype"] == "sendemail"
				&& isset($postdata["/method"]["method"]) && $postdata["/method"]["method"] == "write"
				&& isset($postdata["/create/emailtext"]["translate"]) && $postdata["/create/emailtext"]["translate"])
			return true;
		
		return false;
	}
}


class MsgWiz_smsText extends WizStep {
	function getForm($postdata, $curstep) {
		// Form Fields.
		$formdata = array($this->title);
		$helpsteps = array(_L("Enter the message you wish to deliver via SMS Text."));
		$formdata["message"] = array(
			"label" => _L("SMS Text"),
			"value" => "",
			"validators" => array(
				array("ValRequired"),
				array("ValLength","max"=>160),
				array("ValRegExp","pattern" => getSmsRegExp())
			),
			"control" => array("TextArea","rows"=>5,"cols"=>35,"counter"=>160),
			"helpstep" => 1
		);
		
		return new Form("smsText",$formdata,array());
		
	}

	//returns true if this step is enabled
	function isEnabled($postdata, $step) {
		if (isset($postdata["/start"]["messagetype"]) && $postdata["/start"]["messagetype"] == "sendsms")
			return true;
		
		return false;
	}
}


class MsgWiz_submitConfirm extends WizStep {
	function getForm($postdata, $curstep) {
		$formdata['todo'] = array(
			"label" => _L('todo'),
			"control" => array("FormHtml", "html" => 'TODO: implement me'),
				"helpstep" => 1
			);
		return new Form("submitConfirm",$formdata,array());
		
	}

	//returns true if this step is enabled
	function isEnabled($postdata, $step) {
		return true;
	}
}
?>