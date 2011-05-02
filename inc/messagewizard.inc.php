<?
/* TODO: See below items...
 * 	Translation is stripping out field and audio file inserts
 * 	Email messages, do they need a plain version stored also?
 * 	Should we undelete deleted messages and modify them or create new ones?
 */

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
			'advanced' => array(
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
				$methoddetails["advanced"]["enabled"] = true;
				
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
				$methoddetails["advanced"]["description"] = 
					'<ol>
						<li class="wizbuttonlist">'.escapehtml(_L("Upload Pre-recorded Audio")).'</li>
						<li class="wizbuttonlist">'.escapehtml(_L("Wav, Mp3, Au Format Support")).'</li>
						<li class="wizbuttonlist">'.escapehtml(_L("Use advanced features like field inserts")).'</li>
					</ol>';
				break;
				
			case "sendemail":
				// This step is "currently" disabled for email
				
			case "sendsms":
				// This step is "currently" disabled for SMS
			
			default:
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
		if (isset($postdata["/start"]["messagetype"]) && $postdata["/start"]["messagetype"] == "sendphone")
			return true;
		
		return false;
	}
}


class MsgWiz_language extends WizStep {
	function getForm($postdata, $curstep) {
		global $USER;
		
		$langs = array();
		// alpha sorted, but with english as the first entry
		$langs["en"] = _L("Create the <b>English</b> message");
		
		// only allow auto translation on "write" messages when the user can send multi lingual
		$sendphone = (isset($postdata["/start"]["messagetype"]) && $postdata["/start"]["messagetype"] == "sendphone");
		$phonemethod = (isset($postdata["/method"]["method"])?$postdata["/method"]["method"]:false);
		$sendemail = (isset($postdata["/start"]["messagetype"]) && $postdata["/start"]["messagetype"] == "sendemail");
		if ($USER->authorize('sendmulti') && 
				($sendemail || ($sendphone && ($phonemethod == "advanced" || $phonemethod == "write")))) {
			$langs["autotranslate"] = "Automatically <b>Translate</b> from English to other languages";
		}
		
		foreach (Language::getLanguageMap() as $key => $lang) {
			if ($lang != "English")
				$langs[$key] = _L("Create the <b>%s</b> message", $lang);
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
		global $USER;
		if (!$USER->authorize("sendphone"))
			return false;
			
		if (isset($postdata["/start"]["messagetype"]) && $postdata["/start"]["messagetype"] == "sendphone"
				&& isset($postdata["/method"]["method"]) && $postdata["/method"]["method"] == "write")
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
		global $USER;
		
		if (!$USER->authorize("sendphone") || !$USER->authorize("starteasy"))
			return false;
			
		if (isset($postdata["/start"]["messagetype"]) && $postdata["/start"]["messagetype"] == "sendphone"
				&& isset($postdata["/method"]["method"]) && $postdata["/method"]["method"] == "record")
			return true;
		
		return false;
	}
}


class MsgWiz_phoneAdvanced extends WizStep {

	function getForm($postdata, $curstep) {
		
		// get the language code we are createing a message for
		$langcode = "en";
		if (isset($postdata["/create/language"]["language"])) {
			if ($postdata["/create/language"]["language"] != "autotranslate")
				$langcode = $postdata["/create/language"]["language"];
		}
		
		$messagegroup = new MessageGroup($_SESSION['wizard_message']['mgid']);
		$language = Language::getName($langcode);
		
		// upload audio needs this session data
		$_SESSION['messagegroupid'] = $messagegroup->id;
		
		$formdata = array(
			$messagegroup->name. " (". $language. ")",
			"message" => array(
				"label" => _L("Advanced Message"),
				"value" => "",
				"validators" => array(array("ValRequired")),
				"control" => array("PhoneMessageEditor", "langcode" => $langcode, "messagegroupid" => $messagegroup->id),
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
		
		return new Form("phoneAdvanced",$formdata,$helpsteps,null,"vertical");
	}

	//returns true if this step is enabled
	function isEnabled($postdata, $step) {
		global $USER;
		if (!$USER->authorize("sendphone"))
			return false;
		
		if (isset($postdata["/start"]["messagetype"]) && $postdata["/start"]["messagetype"] == "sendphone"
				&& isset($postdata["/method"]["method"]) && $postdata["/method"]["method"] == "advanced")
			return true;
		
		return false;
	}
}


class MsgWiz_emailText extends WizStep {
	function getForm($postdata, $curstep) {
		global $USER;
		$msgdata = isset($postdata['/message/phone/text']['message'])?json_decode($postdata['/message/phone/text']['message']):json_decode('{"text": ""}');
		
		$messagegroup = new MessageGroup($_SESSION['wizard_message']['mgid']);
		
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
			"value" => $messagegroup->name,
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
			"control" => array("EmailMessageEditor"),
			"helpstep" => 5
		);
		
		return new Form("emailText",$formdata,$helpsteps);
	}

	//returns true if this step is enabled
	function isEnabled($postdata, $step) {
		global $USER;
		if (!$USER->authorize("sendemail"))
			return false;
			
		if (isset($postdata["/start"]["messagetype"]) && $postdata["/start"]["messagetype"] == "sendemail")
			return true;
		
		return false;
	}
}


class MsgWiz_translatePreview extends WizStep {
	function escapeFieldInserts($text) {
		return str_replace(">>", "&#062;&#062;", str_replace("<<", "&#060;&#060;", $text));
	}
	
	function getForm($postdata, $curstep) {
		global $TRANSLATIONLANGUAGECODES;
		
		// msgdata from phone or email
		switch ($postdata["/start"]["messagetype"]) {
			case "sendphone":
				if (isset($postdata["/method"]["method"]) && $postdata["/method"]["method"] == "write") {
					$msg = json_decode($postdata['/create/phonetext']['message']);
					$sourcetext = $msg->message;
				} else {
					$sourcetext = $postdata['/create/phoneadvanced']['message'];
				}
				$msgtype = "phone";
				break;
			case "sendemail":
				$sourcetext = $postdata['/create/emailtext']['message'];
				$msgtype = "email";
				break;
				
			default:
				$sourcetext = "";
				$msgtype = "";
		}
		
		static $translations = false;
		static $translationlanguages = false;

		$warning = "";
		if(mb_strlen($sourcetext) > 4000) {
			$warning = _L('Warning. Only the first 4000 characters are translated.');
		}

		//Get available languages
		switch ($msgtype) {
			case "phone":
				$translationlanguages = Voice::getTTSLanguageMap();
				$voices = Voice::getTTSVoices();
				break;
			
			default:
				$alllanguages = Language::getLanguageMap();
				$translationlanguages = array_intersect_key($alllanguages, array_flip($TRANSLATIONLANGUAGECODES));
				$voices = array();
		}
		unset($translationlanguages['en']);
		$translationlanguagecodes = array_keys($translationlanguages);
		$translations = translate_fromenglish(makeTranslatableString($sourcetext),$translationlanguagecodes);

		// Form Fields.
		$formdata = array($this->title);

		if ($warning)
			$formdata["warning"] = array(
				"label" => _L("Warning"),
				"control" => array("FormHtml","html"=>'<div style="font-size: medium; color: red">'.escapehtml($warning).'</div><br>'),
				"helpstep" => 1
			);

		$formdata["englishtext"] = array(
			"label" => _L("English"),
			"control" => array("FormHtml","html"=>'<div style="font-size: medium;">'.$this->escapeFieldInserts($sourcetext).'</div><br>'),
			"helpstep" => 1
		);

		// Debug output when no translation is available
		if(!$translations) {
			$formdata["Translationinfo"] = array(
				"label" => _L("Info"),
				"control" => array("FormHtml","html"=>'<div style="font-size: medium;">'._L('No Translations Available').'</div><br>'),
				"helpstep" => 2
			);
		} else {
			if(is_array($translations)){
				foreach($translations as $obj){
					$languagecode = array_shift($translationlanguagecodes);
					$formdata[] = Language::getName($languagecode);
					$formdata[$languagecode] = array(
						"label" => _L("Enabled"),
						"fieldhelp" => _L('Check this box to automatically translate your message using Google Translate.'),
						"value" => 1,
						"validators" => array(),
						"control" => array("CheckBoxWithHtmlPreview", "checkedhtml" => $this->escapeFieldInserts($obj->responseData->translatedText), "uncheckedhtml" => addslashes(_L("People tagged with this language will receive the English version."))),
						"helpstep" => 2
					);
				}
			} else {
				$languagecode = reset($translationlanguagecodes);
					$formdata[] = Language::getName($languagecode);
				$formdata[$languagecode] = array(
						"label" => _L("Enabled"),
						"fieldhelp" => _L('Check this box to automatically translate your message using Google Translate.'),
						"value" => 1,
						"validators" => array(),
						"control" => array("CheckBoxWithHtmlPreview", "checkedhtml" => $this->escapeFieldInserts($translations->translatedText), "uncheckedhtml" => addslashes(_L("People tagged with this language will receive the English version."))),
						"helpstep" => 2
					);
			}
		}
		if(!isset($formdata["Translationinfo"])) {
			$formdata["Translationinfo"] = array(
				"label" => " ",
				"control" => array("FormHtml","html"=>'
					<div id="branding">
						<div style="color: rgb(103, 103, 103);float: right;" class="gBranding">
							<span style="vertical-align: middle; font-family: arial,sans-serif; font-size: 11px;" class="gBrandingText">
								'._L('Translation powered by').'<img style="padding-left: 1px; vertical-align: middle;" alt="Google" src="' . (isset($_SERVER['HTTPS'])?"https":"http") . '://www.google.com/uds/css/small-logo.png">
							</span>
						</div>
					</div>
				'),
				"helpstep" => 2
			);
		}

		$helpsteps = array(
			_L("This is the message that all contacts will receive if they do not have any other language message specified"),
			_L("This is an automated translation powered by Google Translate. Please note that although machine translation is always improving, it is not perfect yet. You can try reverse translation for an idea of how well your message was translated.")
		);
		
		return new Form("translatePreview",$formdata,$helpsteps);
	}

	//returns true if this step is enabled
	function isEnabled($postdata, $step) {
		global $USER;
			// only allow auto translation on "write" messages when the user can send multi lingual
		$sendphone = (isset($postdata["/start"]["messagetype"]) && $postdata["/start"]["messagetype"] == "sendphone");
		$phonemethod = (isset($postdata["/method"]["method"])?$postdata["/method"]["method"]:false);
		$sendemail = (isset($postdata["/start"]["messagetype"]) && $postdata["/start"]["messagetype"] == "sendemail");
		if ($USER->authorize('sendmulti') && 
				isset($postdata["/create/language"]["language"]) && $postdata["/create/language"]["language"] == "autotranslate" &&
				($sendemail || ($sendphone && ($phonemethod == "advanced" || $phonemethod == "write")))) {
			return true;
		}
		
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
		global $USER;
		if (!$USER->authorize("sendsms"))
			return false;
			
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
		
		// TODO: is the messagegroup id still valid? do we even care?
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
		// (phone,email,sms)
		
		// keep track of the message data we are going to create messages for
		// format msgArray(typeArray(translateflagArray(data)))
		$messages = array(
			'phone' => array(),
			'email' => array(),
			'sms' => array());
		
		// phone message
		if (MsgWiz_phoneText::isEnabled($postdata, false) || MsgWiz_phoneAdvanced::isEnabled($postdata, false)) {
			
			if (MsgWiz_phoneText::isEnabled($postdata, false)) {
				$sourcemessage = json_decode($postdata["/create/phonetext"]["message"]);
				$text = $sourcemessage->text;
				$gender = $sourcemessage->gender;
			} else {
				$text = $postdata["/create/phoneadvanced"]["message"];
				$gender = $postdata["/create/phoneadvanced"]["gender"];
			}
			
			// if this is the default 'en' message, it's autotranslate value is 'none'
			$messages['phone'][$sourcelangcode][$autotrans]['text'] = $text;
			$messages['phone'][$sourcelangcode][$autotrans]['gender'] = $gender;
			
			//also set the messagegroup preferred gender
			$messagegroup->preferredgender = $gender;
			$messagegroup->stuffHeaders();
			$messagegroup->update(array("data"));
		
			// check for and retrieve translations
			if (MsgWiz_translatePreview::isEnabled($postdata, false) && $langcode == "autotranslate") {
				foreach ($postdata["/create/translatepreview"] as $translatedlangcode => $enabled) {
					// when the message is created, the modify date will be set in the past and retranslation will
					// get called before attaching to the message group
					if ($enabled) {
						$messages['phone'][$translatedlangcode]['translated'] = $messages['phone']['en']['none'];
						$messages['phone'][$translatedlangcode]['source'] = $messages['phone']['en']['none'];
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
			if (MsgWiz_translatePreview::isEnabled($postdata, false) && $langcode == "autotranslate") {
				foreach ($postdata["/create/translatepreview"] as $translatedlangcode => $enabled) {
					// when the message is created, the modify date will be set in the past and retranslation will
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
					$message = DBFind("Message", "from message where messagegroupid = ? and type = ? and languagecode = ? and autotranslate = ?", false, array($messagegroup->id, $type, $langcode, $autotranslate));
					
					error_log("working on ". $type. ", ". $langcode. ", ". $autotranslate);
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
					
					// if this is an autotranslated message. set the modify date in the past
					// this way re-translate will populate the message parts for us
					if ($autotranslate == 'translated')
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
					
					error_log("created/updated message: ". $message->id. " for languagecode: ". $langcode. " and autotranslate: ". $autotranslate);
					
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