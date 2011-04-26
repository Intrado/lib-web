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
						<li class="wizbuttonlist">'.escapehtml(_L("Text-to-speech")).'</li>'.
						($USER->authorize('sendmulti')?'<li class="wizbuttonlist">'.escapehtml(_L("Auto-translate available")).'</li>':'').'
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
						<li class="wizbuttonlist">'.escapehtml(_L("Auto-translate available for English messages")).'</li>
					</ol>';
				break;
				
			case "sendsms":
				// TODO: should never happen? This step is "currently" disabled for SMS
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
		global $USER;
		
		$langs = array();
		// alpha sorted, but with english as the first entry
		$langs["en"] = _L("Create the <b>Default/English</b> message");
		foreach (Language::getLanguageMap() as $key => $lang) {
			if ($lang != "English")
				$langs[$key] = _L("Create the <b>%s</b> message", $lang);
		}
		
		// only allow auto translation on "write" messages when the user can send multi lingual
		if ($USER->authorize('sendmulti') 
				&& isset($postdata["/method"]["method"]) && $postdata["/method"]["method"] == "write") {
			$langs["autotranslate"] = "Automatically translate to other languages";
		}
		
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
				"control" => array("RadioButton", "values" => $langs, "ishtml" => true),
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

		// TODO: Note here on auto-translate that this message should be in English and it will be used to generate the other languages
		
		$formdata = array($this->title);
		
		// if auto-translate, give the user a hint that this is the ENGLISH version from which translations will be created.
		if (isset($postdata["/create/language"]["language"]) && $postdata["/create/language"]["language"] == "autotranslate") {
			$formdata['tips'] = array(
				"label" => _L('Message Tips'),
				"control" => array("FormHtml", "html" => '
					<ul>
						<li class="wizbuttonlist">'._L('This is the <b>English</b> version of the message').'</li>
						<li class="wizbuttonlist">'._L('Automatically translated messages will use this as the source').'</li>
						<li class="wizbuttonlist">'._L('After entering the English version, click <b>Next</b> to review/edit the translations').'</li>
					</ul>
					'),
				"helpstep" => 1
			);
		}
		
		$formdata["message"] = array(
			"label" => _L("Phone Message"),
			"fieldhelp" => _L('Enter the message you would like to send. Helpful tips for successful messages can be found at the Help link in the upper right corner.'),
			"value" => "",
			"validators" => array(
				array("ValRequired"),
				array("ValTextAreaPhone")
			),
			"control" => array("TextAreaPhone","width"=>"80%","rows"=>10,"language"=>"en","voice"=>"female"),
			"helpstep" => 1
		);

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
		$msgdata = isset($postdata['/create/phonetext']['message'])?json_decode($postdata['/create/phonetext']['message']):json_decode('{"gender": "female", "text": ""}');
		$existingtranslations = isset($postdata["/create/phonetranslate"])?$postdata["/create/phonetranslate"]:array();
		
		return new AutoTranslateForm("phoneTranslate", $this->title, $existingtranslations, $msgdata->text, $msgdata->gender, "phone");
	}

	//returns true if this step is enabled
	function isEnabled($postdata, $step) {
		global $USER;
		if (!$USER->authorize("sendphone") || !$USER->authorize("sendmulti"))
			return false;
		
		if (isset($postdata["/start"]["messagetype"]) && $postdata["/start"]["messagetype"] == "sendphone"
				&& isset($postdata["/method"]["method"]) && $postdata["/method"]["method"] == "write"
				&& isset($postdata["/create/language"]["language"]) && $postdata["/create/language"]["language"] == "autotranslate")
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

		// if auto-translate, give the user a hint that this is the ENGLISH version from which translations will be created.
		if (isset($postdata["/create/language"]["language"]) && $postdata["/create/language"]["language"] == "autotranslate") {
			$formdata['tips'] = array(
				"label" => _L('Message Tips'),
				"control" => array("FormHtml", "html" => '
					<ul>
						<li class="wizbuttonlist">'._L('This is the <b>English</b> version of the message').'</li>
						<li class="wizbuttonlist">'._L('Automatically translated messages will use this as the source').'</li>
						<li class="wizbuttonlist">'._L('After entering the English version, click <b>Next</b> to review/edit the translations').'</li>
					</ul>
					'),
				"helpstep" => 1
			);
		}
		
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
		$existingtranslations = isset($postdata["/create/emailtranslate"])?$postdata["/create/emailtranslate"]:array();
		
		return new AutoTranslateForm("emailTranslate", $this->title, $existingtranslations, $postdata['/create/emailtext']['message'], false, "email");
	}

	//returns true if this step is enabled
	function isEnabled($postdata, $step) {
		global $USER;
		if (!$USER->authorize("sendemail") || !$USER->authorize("sendmulti"))
			return false;
		
		if (isset($postdata["/start"]["messagetype"]) && $postdata["/start"]["messagetype"] == "sendemail"
				&& isset($postdata["/method"]["method"]) && $postdata["/method"]["method"] == "write"
				&& isset($postdata["/create/language"]["language"]) && $postdata["/create/language"]["language"] == "autotranslate")
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
		
		$formdata = array($this->title);
		
		$formdata["info"] = array(
			"label" => _L("Message Info"),
			"control" => array("FormHtml", "html" => "TODO: Confirm message"),
			"helpstep" => 1
		);
		
		$formdata["confirm"] = array(
			"label" => _L("Confirm"),
			"value" => "",
			"validators" => array(
				array("ValRequired")
			),
			"control" => array("CheckBox"),
			"transient" => true,
			"helpstep" => 1
		);
		
		return new Form("submitConfirm",$formdata,array());
		
	}

	//returns true if this step is enabled
	function isEnabled($postdata, $step) {
		return true;
	}
}

class FinishMessageWizard extends WizFinish {
	function finish ($postdata) {
		// If the message has not ben confirmed, don't try to process the data.
		if (!isset($postdata["/submit/confirm"]["confirm"])  || !$postdata["/submit/confirm"]["confirm"] )
			return false;
		
		global $USER;
		global $ACCESS;
		
		// start a transaction
		QuickQuery("BEGIN");
		
		// TODO: is the messagegroup id still valid?
		$messagegroup = new MessageGroup($_SESSION['wizard_message']['mgid']);
		
		// get the language code from postdata
		$langcode = (isset($postdata["/create/language"]["language"])?$postdata["/create/language"]["language"]:"en");
		
		// auto translations and english need the source stored as english. everything else stores as an overridden autotranslation 
		if ($langcode == "autotranslate" || $langcode == "en") {
			$sourcelangcode = "en";
			$autotrans = 'none';
		} else {
			$sourcelangcode = $langcode;
			$autotrans = 'overridden';
		}
		
		// #################################################################
		// CallMe based message
		// phone -> record
		
		if (MsgWiz_phoneEasyCall::isEnabled($postdata, false)) {
			// pull the audiofileid from post data
			$audiofileidmap = json_decode($postdata["/create/callme"]["message"]);
			$audiofileid = $audiofileidmap->af;
			
			// check for an existing message with this language code for this message group 
			$message = DBFind("Message", "from message where messagegroupid = ? and type = 'phone' and languagecode = ?", false, array($messagegroup->id, $sourcelangcode));
			
			// no message in the db? create a new one.
			if (!$message->id)
				$message = new Message();
				
			$message->messagegroupid = $messagegroup->id;
			$message->type = 'phone';
			$message->subtype = 'voice';
			$message->autotranslate = 'none';

			$message->name = $messagegroup->name;
			$message->description = Language::getName($sourcelangcode);
			$message->userid = $USER->id;
			$message->modifydate = date("Y-m-d H:i:s");
			$message->languagecode = $sourcelangcode;
			$message->deleted = 0;
			$message->stuffHeaders();
			if (!$message->id)
				$message->create();
			else
				$message->update();
			
			$part = new MessagePart();
			$part->messageid = $message->id;
			$part->type = "A";
			$part->audiofileid = $audiofileid;
			$part->sequence = 0;
			$part->create();
		}
		
		// #################################################################
		// Text based messages
		// (phone,email,sms) -> write
		
		// keep track of the message data we are going to create messages for
		// format msgArray(typeArray(translateflagArray(data)))
		$messages = array(
			'phone' => array(),
			'email' => array(),
			'sms' => array());
		
		// phone message
		if (MsgWiz_phoneText::isEnabled($postdata, false)) {
			$sourcemessage = json_decode($postdata["/create/phonetext"]["message"]);
			
			// this is the default 'en' message so it's autotranslate value is 'none'
			$messages['phone'][$sourcelangcode][$autotrans]['text'] = $sourcemessage->text;
			$messages['phone'][$sourcelangcode][$autotrans]['gender'] = $sourcemessage->gender;
			
			//also set the messagegroup preferred gender
			$messagegroup->preferredgender = $sourcemessage->gender;
			$messagegroup->stuffHeaders();
			$messagegroup->update(array("data"));
			
			// check for and retrieve translations
			if (MsgWiz_phoneTranslate::isEnabled($postdata, false) && $langcode == "autotranslate") {
				foreach ($postdata["/create/phonetranslate"] as $translatedlangcode => $msgdata) {
					$translatedmessage =json_decode($msgdata);
					// if this translation message is enabled
					if ($translatedmessage->enabled) {
						// if the translation text is overridden, don't attach a source message
						// it isn't applicable since we have no way to know what they changed the text to.
						if ($translatedmessage->override) {
							$messages['phone'][$translatedlangcode]['overridden']['text'] = $translatedmessage->text;
							$messages['phone'][$translatedlangcode]['overridden']['gender'] = $translatedmessage->gender;
						} else {
							$messages['phone'][$translatedlangcode]['translated']['text'] = $translatedmessage->text;
							$messages['phone'][$translatedlangcode]['translated']['gender'] = $translatedmessage->gender;
							$messages['phone'][$translatedlangcode]['source'] = $messages['phone']['en']['none'];
						}
					}
				}
			}
		}
		
		// email message
		if (MsgWiz_emailText::isEnabled($postdata, false)) {
			$messages['email'][$sourcelangcode][$autotrans]['text'] = $postdata["/create/emailtext"]["message"];
			$messages['email'][$sourcelangcode][$autotrans]["fromname"] = $postdata["/create/emailtext"]["fromname"];
			$messages['email'][$sourcelangcode][$autotrans]["from"] = $postdata["/create/emailtext"]["from"];
			$messages['email'][$sourcelangcode][$autotrans]["subject"] = $postdata["/create/emailtext"]["subject"];
			$messages['email'][$sourcelangcode][$autotrans]['attachments'] = json_decode($postdata["/create/emailtext"]['attachments']);
			if ($messages['email'][$sourcelangcode][$autotrans]['attachments'] == null) 
				$messages['email'][$sourcelangcode][$autotrans]['attachments'] = array();
			
			// check for and retrieve translations
			if (MsgWiz_emailTranslate::isEnabled($postdata, false) && $langcode == "autotranslate") {
				foreach ($postdata["/create/emailtranslate"] as $translatedlangcode => $enabled) {
					// emails don't have any actual translation text in session data other than the source message
					// when the message group is created. the modify date will be set in the past and retranslation will
					// get called before attaching to the message group
					if ($enabled) {
						$messages['email'][$translatedlangcode]['translated'] = $messages['email']['en']['none'];
						$messages['email'][$translatedlangcode]['source'] = $messages['email']['en']['none'];
					}
				}
			}
		}
		
		// sms message
		if (MsgWiz_smsText::isEnabled($postdata, false))
			$messages['sms']['en']['none']['text'] = $postdata["/create/smstext"]["message"];
		
		// #################################################################
		// create a message for each one
		
		// for each message type
		foreach ($messages as $type => $msgdata) {
			// for each language code
			foreach ($msgdata as $langcode => $autotranslatevalues) {
				// for each autotranslate value
				foreach ($autotranslatevalues as $autotranslate => $data) {
					// check for an existing message with this language code for this message group 
					$message = DBFind("Message", "from message where messagegroupid = ? and type = ? and languagecode = ?", false, array($messagegroup->id, $type, $langcode));
					
					error_log($message->id);
					
					// no message in the db? create a new one.
					if (!$message->id)
						$message = new Message();
					
					$message->messagegroupid = $messagegroup->id;
					$message->type = $type;
					switch($type) {
						case 'phone':
							$message->subtype = 'voice';
							break;
						case 'email':
							$message->subtype = 'html';
							break;
						default:
							$message->subtype = 'plain';
							break;
					}
					
					$message->autotranslate = $autotranslate;
					$message->name = $messagegroup->name;
					$message->description = Language::getName($langcode);
					$message->userid = $USER->id;
					
					// if this is an autotranslated message and an email. set the modify date in the past
					// this way re-translate will populate the message parts for us
					if ($autotranslate == 'translated' && $type == 'email')
						$message->modifydate = date("Y-m-d H:i:s", '1');
					else
						$message->modifydate = date("Y-m-d H:i:s");
					
					$message->languagecode = $langcode;
					$message->deleted = 0;
					
					if ($type == 'email') {
						$message->subject = $data["subject"];
						$message->fromname = $data["fromname"];
						$message->fromemail = $data["from"];
					}
					
					$message->stuffHeaders();
					if (!$message->id)
						$message->create();
					else
						$message->update();
						
					// create the message parts
					$message->recreateParts($data['text'], null, isset($data['gender'])?$data['gender']:false);
					
					// if there are message attachments, attach them
					if (isset($data['attachments']) && $data['attachments']) {
						foreach ($data['attachments'] as $cid => $details) {
							$msgattachment = new MessageAttachment();
							$msgattachment->messageid = $message->id;
							$msgattachment->contentid = $cid;
							$msgattachment->filename = $details->name;
							$msgattachment->size = $details->size;
							$msgattachment->deleted = 0;
							$msgattachment->create();
						}
					}
				}
			}
		}
		
		// refresh any stale auto translations
		$messagegroup->reTranslate();
		
		// end the transaction
		QuickQuery("COMMIT");
	}
	
	function getFinishPage ($postdata) {
		$html = '<h3>Success! Your message has been saved</h3>';
		return $html;
	}
}
?>