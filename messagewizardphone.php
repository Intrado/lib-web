<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("inc/common.inc.php");
require_once("inc/html.inc.php");
require_once("inc/table.inc.php");
require_once("inc/securityhelper.inc.php");
require_once("obj/Form.obj.php");
require_once("obj/Wizard.obj.php");
require_once("obj/Message.obj.php");
require_once("obj/MessagePart.obj.php");
require_once("obj/Phone.obj.php");
require_once("obj/SpecialTask.obj.php");
require_once("obj/AudioFile.obj.php");
require_once("obj/Language.obj.php");
require_once("obj/Voice.obj.php");
require_once("inc/translate.inc.php");
require_once("obj/MessageGroup.obj.php");
require_once("obj/FieldMap.obj.php");
require_once("obj/MessageAttachment.obj.php");
require_once("inc/previewfields.inc.php");
require_once("obj/PreviewModal.obj.php");

// form items
require_once("obj/FormItem.obj.php");
require_once("obj/Validator.obj.php");
require_once("obj/HtmlRadioButtonBigCheck.fi.php");
require_once("obj/PhoneMessageRecorder.fi.php");
require_once("obj/PhoneMessageRecorder.val.php");
require_once("obj/TextAreaPhone.fi.php");
require_once("obj/TextAreaPhone.val.php");
require_once("obj/HtmlTextArea.fi.php");
require_once("obj/ValMessageBody.val.php");
require_once("obj/traslationitem.obj.php");
require_once("obj/CheckBoxWithHtmlPreview.fi.php");
require_once("obj/PhoneMessageEditor.fi.php");
require_once("obj/InpageSubmitButton.fi.php");


////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
global $USER;
if (!$USER->authorize("sendphone"))
	redirect('unauthorized.php');

////////////////////////////////////////////////////////////////////////////////
// Passed parameter checking
////////////////////////////////////////////////////////////////////////////////
if (isset($_GET['mgid']) && $_GET['mgid']) {
	if (!userOwns('messagegroup', $_GET['mgid']))
		redirect('unauthorized.php');
	
	$_SESSION['wizard_message_mgid'] = ($_GET['mgid'] + 0);		
}

if (isset($_GET['debug']))
	$_SESSION['wizard_message']['debug'] = true;

////////////////////////////////////////////////////////////////////////////////
// Wizard step data
////////////////////////////////////////////////////////////////////////////////

class MsgWiz_method extends WizStep {
	function getForm($postdata, $curstep) {
		global $USER;

		// message icon button details
		$methoddetails = array ();
		
		if ($USER->authorize("starteasy"))
			$methoddetails["record"] = array(
				"icon" => "img/record.gif",
				"label" => _L("Record"),
				"description" => 
					'<ol>
						<li class="wizbuttonlist">'.escapehtml(_L("Record an audio phone message using EasyCall")).'</li>
						<li class="wizbuttonlist">'.escapehtml(_L("Record over the phone")).'</li>
						<li class="wizbuttonlist">'.escapehtml(_L("Call yourself or a colleague")).'</li>
					</ol>');
		
		$methoddetails['write'] = array(
			"icon" => "img/write.gif",
			"label" => _L("Write"),
				"description" =>
					'<ol>
						<li class="wizbuttonlist">'.escapehtml(_L("Text-to-speech")).'</li>
						<li class="wizbuttonlist">'.escapehtml(_L("Upload Pre-recorded Audio")).'</li>
						<li class="wizbuttonlist">'.escapehtml(_L("Use advanced features like field inserts")).'</li>'.
						($USER->authorize('sendmulti')?'<li class="wizbuttonlist">'.escapehtml(_L("Auto-translate available")).'</li>':'').'
					</ol>');
					
		$methods = array();
		$values = array();
		foreach ($methoddetails as $type => $details) {
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
		

		$formdata = array(
			$this->title,
			"method" => array(
				"label" => _L("Method"),
				"fieldhelp" => _L("TODO: fieldhelp"),
				"validators" => array(
					array("ValRequired"),
					array("ValInArray", "values" => array_keys($methods))
				),
				"value" => (($USER->authorize("starteasy"))?"":"write"),
				"control" => array("HtmlRadioButtonBigCheck", "values" => $methods),
				"helpstep" => 1)
		);
		
		$helpsteps = array(_L("Select the method you wish to use to create your phone message. Please note that automatic translation is only available for written messages."));
		
		return new Form("method",$formdata,$helpsteps);
	}

	//returns true if this step is enabled
	function isEnabled($postdata, $step) {
		return true;
	}
}

class MsgWiz_language extends WizStep {
	function getForm($postdata, $curstep) {
		global $USER;
		
		$langs = array();
		
		// only allow auto translation on "write" messages when the user can send multi lingual
		$method = (isset($postdata["/method"]["method"])?$postdata["/method"]["method"]:false);
		if ($USER->authorize('sendmulti') && ($method == "advanced" || $method == "write")) {
			$langs["autotranslate"] = _L("Automatically <b>Translate</b> from English to other languages");
			$langs[] = "#-#"; //insert an <hr>
		}
		
		// alpha sorted, but with english as the first entry
		$langs["en"] = _L("Create the <b>English</b> message");
		
		// If the method chosen will be rendered via TTS, we should only allow the user to select TTSable languages
		$ttslanguages = Voice::getTTSLanguageMap();
		$alllanguages = Language::getLanguageMap();
		if ($method == "advanced" || $method == "write") {
			$excludedlanguages = array_diff_key($alllanguages, $ttslanguages);
			$languages = $ttslanguages;
		} else {
			$excludedlanguages = array();
			$languages = $alllanguages;
		}
		
		foreach ($languages as $key => $lang) {
			if ($key != "en")
				$langs[$key] = _L("Create the <b>%s</b> message", ucfirst($lang));
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
		
		$helpsteps = array(_L("Select whether or not you would like to automatically translate your message using Google Translate. If you prefer to write your own translations, leave this option unchecked.<br><br>
		Next, select the language of the message you're creating."));
		
		// if there are excluded languages, show them
		if ($excludedlanguages) {
			$html = '<div>'. _L('The following languages cannot be spoken via Text to Speech.'). '
				<ul>';
			$count = 0;
			foreach ($excludedlanguages as $code => $language)
				$html .= "<li>$language</li>";
			$html .= "</ul></div>";
			
			$formdata["excludes"] = array(
				"label" => _L("Non-TTS Languages"),
				"control" => array("FormHtml", "html" => $html),
				"helpstep" => 2
			);
			
			$helpsteps[] = "These languages are not available for Text-to-Speech messaging. To create a message for one of these Languages, click Previous and choose Record";
		}		
		return new Form("start",$formdata,$helpsteps);
	}

	//returns true if this step is enabled
	function isEnabled($postdata, $step) {
		global $USER;
		// users who can't send multi lingual don't get language selection
		if ($USER->authorize("sendmulti"))
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
			"label" => _L("Voice Recording"),
			"fieldhelp" => _L("Enter the 10-digit phone number where you can be reached."),
			"value" => "",
			"validators" => array(
				array("ValRequired"),
				array("PhoneMessageRecorderValidator")
			),
			"control" => array( "PhoneMessageRecorder", "phone" => $USER->phone, "name" => $language),
			"helpstep" => 1
		);
		
		$helpsteps = array(_L("The system will call you at the number you enter in this form and guide you through a series of prompts to record your message. The default message is always required and will be sent to any recipients who do not have a language specified.<br><br>
		Enter the phone number where the system can reach you. Then click \"Call Me to Record\" to get started. Listen carefully to the prompts when you receive the call. You may record as many different langauges as you need.
		"));

		return new Form("phoneEasyCall",$formdata,$helpsteps);
	}

	//returns true if this step is enabled
	function isEnabled($postdata, $step) {
		global $USER;
		
		if (!$USER->authorize("sendphone") || !$USER->authorize("starteasy"))
			return false;
			
		if (isset($postdata["/method"]["method"]) && $postdata["/method"]["method"] == "record")
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
		
		$formdata = array($messagegroup->name. " (". $language. ")");
		$helpsteps = array();
		$helpstep = 1;
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
				"helpstep" => $helpstep++);
			$helpsteps[] = "TODO: Help me!";
		}
		
		$formdata["message"] = array(
				"label" => _L("Advanced Message"),
				"value" => "",
				"validators" => array(array("ValRequired")),
				"control" => array("PhoneMessageEditor", "langcode" => $langcode, "messagegroupid" => $messagegroup->id),
				"helpstep" => $helpstep++);
		$formdata["gender"] = array(
				"label" => _L("Gender"),
				"value" => "",
				"validators" => array(array("ValRequired")),
				"control" => array("RadioButton", "values" => array("female" => _L("Female"), "male" => _L("Male"))),
				"helpstep" => $helpstep++);
		$formdata["preview"] = array(
				"label" => "",
				"value" => "",
				"validators" => array(),
				"control" => array("InpageSubmitButton", "name" => "preview", "icon" => "fugue/control"),
				"helpstep" => $helpstep++);
		
		$helpsteps[] = _L("<p>You can use a variety of techniques to build your message in this screen, but ideally you should use this to assemble snippets of audio with dynamic data field inserts. You can use 'Call me to Record' to create your audio snippets or upload pre-recorded audio from your computer. To record multiple audio snippets, you can use 'Call me to Record' for each snippet. </p><p>To insert data fields, set the cursor where the data should appear. Be careful to not delete any of the brackets that appear around audio snippets or other data fields. Select the data field you wish to insert and enter a default value which will display if a recipient does not have data in the chosen field. Click the 'Insert' button to add the data field to your message.</p>");
		$helpsteps[] =_L("If your message contains pieces that will be read by a text-to-speech voice, such as data fields or other text, select the gender of the text-to-speech voice. For best results, it's a good idea to select the same gender as the speaker in the audio files.");
		$helpsteps[] =_L("Click the preview button to hear a preview of your message.");
		
		return new Form("phoneAdvanced",$formdata,$helpsteps,null,"vertical");
	}

	//returns true if this step is enabled
	function isEnabled($postdata, $step) {
		global $USER;
		if (!$USER->authorize("sendphone"))
			return false;
		
		if (isset($postdata["/method"]["method"]) && $postdata["/method"]["method"] == "write")
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
		
		// msgdata from phone
		$sourcetext = $postdata['/create/phoneadvanced']['message'];
		
		static $translations = false;
		static $translationlanguages = false;

		$warning = "";
		if(mb_strlen($sourcetext) > 4000) {
			$warning = _L('Warning. Only the first 4000 characters are translated.');
		}

		//Get available languages
		$translationlanguages = Voice::getTTSLanguageMap();
		$voices = Voice::getTTSVoices();
		
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
		if ($USER->authorize('sendmulti') && 
				isset($postdata["/create/language"]["language"]) && $postdata["/create/language"]["language"] == "autotranslate" &&
				isset($postdata["/method"]["method"]) && $postdata["/method"]["method"] == "write") {
			return true;
		}
		
		return false;
	}
}

class MsgWiz_submitConfirm extends WizStep {
	function getForm($postdata, $curstep) {
		global $USER;
		
		if ($USER->authorize('sendmulti'))
			$srclanguagecode = isset($postdata['/create/language']['language'])?$postdata['/create/language']['language']:"en";
				
		$languagecodes = array();
		if ($srclanguagecode == "autotranslate") {
			$languagecodes[] = "en";
			foreach ($postdata['/create/translatepreview'] as $langcode => $enabled) {
				if ($enabled === "true")
					$languagecodes[] = $langcode;
			}
		} else {
			$languagecodes[] = $srclanguagecode;
		}
		
		// get the message group we are modifying
		$messagegroup = new MessageGroup($_SESSION['wizard_message']['mgid']);
			
		$html = '<div>'._L('The following messages will be overwritten').'</div>
				<table class="list">
					<tr class="listHeader">
						<th>'. _L("Language"). '&nbsp;&nbsp;</th>
						<th>'. _L("Type"). '&nbsp;&nbsp;</th>
					</tr>';
		$count = 0;
		foreach ($languagecodes as $languagecode) {
			if ($messagegroup->hasMessage('phone', 'voice', $languagecode)) {
				$html .= '
					<tr '. (($count % 2)?'class="listAlt"':''). '>
						<td>'. Language::getName($languagecode).'</td>
						<td>'. escapehtml(_L("Phone/voice")). '</td>
					</tr>';
				$count++;
			}
		}
		$html .= '</table>';
		
		$formdata = array($this->title);
		
		$formdata["info"] = array(
			"label" => _L("Overwritten Messages"),
			"control" => array("FormHtml", "html" => $html),
			"helpstep" => 1
		);
		
		$helpsteps = ("TODO: guide data");
		
		return new Form("submitConfirm",$formdata,$helpsteps);
		
	}
	
	//returns true if this step is enabled
	function isEnabled($postdata, $step) {
		global $USER;
		
		// if the method isnt chosen
		if (!isset($postdata['/method']))
			return false;
		
		// if the user has multilingual but hasn't selected a language
		if  ($USER->authorize('sendmulti') && !isset($postdata['/create/language']['language']))
			return false;
		
		// only show the confirm step if the creation of this message will overwrite an existing message
		if ($USER->authorize('sendmulti'))
			$languagecode = isset($postdata['/create/language']['language'])?$postdata['/create/language']['language']:"en";
		else
			return false;
		
		$args = array();
		
		// if it's an auto translate, we have to look up each of the trasnlated languages and english
		if ($languagecode == "autotranslate") {
			// autotranslate always overwrites the english message
			$args[] = "en";
			// need the translated step's session data to get the enabled languages
			if (isset($postdata['/create/translatepreview'])) {
				foreach ($postdata['/create/translatepreview'] as $langcode => $enabled) {
					if ($enabled === "true")
						$args[] = $langcode;
				}
			}
		} else {
			// not auto translate so...
			// query the messages to see if a message exists already for this language code
			$args[] = $languagecode;
		}
		
		// need a list of ? for each language code we are going to look up to put in the query
		$langqueryargs = repeatWithSeparator("?",",",count($args));
		
		// add additional query arguments
		$args[] = $_SESSION['wizard_message']['mgid'];
		
		// query for any messages matching one of these language codes
		$hasmessage = QuickQuery(
			"select 1 from message 
			where languagecode in (".$langqueryargs.") 
			and messagegroupid = ? 
			and type = 'phone' 
			and subtype = 'voice' 
			and autotranslate in ('none', 'translated', 'overridden') 
			and not deleted", false, $args);
		
		if ($hasmessage)
			return true;
		
		return false;
	}
}

class FinishMessageWizard extends WizFinish {
	function finish ($postdata) {
		global $USER;
		global $ACCESS;
		
		// start a transaction
		QuickQuery("BEGIN");
		
		// is the messagegroup id still valid?
		$messagegroup = new MessageGroup($_SESSION['wizard_message']['mgid']);
		if (!userOwns("messagegroup", $messagegroup->id) || $messagegroup->deleted)
			redirect('unauthorized.php');
		
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
			// get either the 'none', 'overridden' or 'translated' message for overwriting
			$message = DBFind("Message", 
					"from message 
					where messagegroupid = ? 
					and autotranslate in ('overridden', 'none', 'translated')
					and type = 'phone' 
					and languagecode = ?", false, array($messagegroup->id, $sourcelangcode));
			
			// if there is an existing message in the DB, must remove it's parts
			if ($message) {
				QuickUpdate("delete from messagepart where messageid = ?", false, array($message->id));
				// delete existing messages
				QuickUpdate("delete from message 
						where messagegroupid = ?
						and type = 'phone'
						and languagecode = ?
						and id != ?", false, array($messagegroup->id, $sourcelangcode, $message->id));
			} else {
				// no message, create a new one!
				$message = new Message();
			}
				
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
		} else {
		
			// #################################################################
			// Text based messages
			
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
						if ($enabled === "true") {
							$messages['phone'][$translatedlangcode]['translated'] = $messages['phone']['en']['none'];
							$messages['phone'][$translatedlangcode]['source'] = $messages['phone']['en']['none'];
						}
					}
				}
			}
			
			// #################################################################
			// create a message for each one
			
			// for each language code
			foreach ($msgdata as $langcode => $autotranslatevalues) {
				// for each autotranslate value
				foreach ($autotranslatevalues as $autotranslate => $data) {
					
					// check for an existing message with this language code for this message group 
					$message = DBFind("Message", 
						"from message 
						where messagegroupid = ? 
						and type = 'phone' 
						and subtype = 'voice' 
						and languagecode = ? 
						and autotranslate = ?", false, array($messagegroup->id, $type, $subtype, $langcode, $autotranslate));
				
					if (!$message) {
						// no message, create a new one!
						$message = new Message();
					}
					
					$message->messagegroupid = $messagegroup->id;
					$message->type = 'phone';
					$message->subtype = 'voice';
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
					
					$message->stuffHeaders();
					if (!$message->id)
						$message->create();
					else
						$message->update();
					
					// create the message parts
					$message->recreateParts($data['text'], null, isset($data['gender'])?$data['gender']:false);
					
					// remove old messages based on the auto translate value
					// we need to get rid of recorded messages if this is now a TTS message for example
					switch ($autotranslate) {
						case "translated":
							// delete everything except this message and the 'source'
							$autotranslateclause = "and autotranslate != 'source'";
							break;
						case "source":
							// delete everything except this one and the 'translated' one
							$autotranslateclause = "and autotranslate != 'translated'";
							break;
						case "overridden":
						case "none":
						default:
							// delete everything except this message
							$autotranslateclause = "";
					}
					
					QuickUpdate("delete from message 
						where messagegroupid = ?
						and type = 'phone'
						and subtype = 'voice'
						$autotranslateclause
						and languagecode = ?
						and id != ?", false, array($messagegroup->id, $type, $subtype, $langcode, $message->id));
				}
			}
		}
		
		// refresh any stale auto translations
		$messagegroup->reTranslate();
		
		// end the transaction
		QuickQuery("COMMIT");
	}
	
	function getFinishPage ($postdata) {
		// remove this wizard's session data
		unset($_SESSION['wizard_message']);
		
		$html = '<h3>Success! Your message has been saved</h3>';
		return $html;
	}
}

$wizdata = array(
	"method" => new MsgWiz_method(_L("Method")),
	"create" => new WizSection ("Create",array(
		"language" => new MsgWiz_language(_L("Language")),
		"callme" => new MsgWiz_phoneEasyCall(_L("Record")),
		"phoneadvanced" => new MsgWiz_phoneAdvanced(_L("Advanced")),
		"translatepreview" => new MsgWiz_translatePreview(_L("Translations")),
	)),
	"submit" => new WizSection ("Confirm",array(
		"confirm" => new MsgWiz_submitConfirm(_L("To be Overwritten"))
	))
);

$wizard = new Wizard("wizard_message",$wizdata, new FinishMessageWizard("Finish"));
$wizard->doneurl = "mgeditor.php";
$wizard->handlerequest();

// After reload check session data for messagegroup information
if (isset($_SESSION['wizard_message_mgid'])) {
	
	// check that this is a valid message group
	$messagegroup = new MessageGroup($_SESSION['wizard_message_mgid']);
	if (!userOwns("messagegroup", $messagegroup->id) || $messagegroup->deleted) {
		unset($_SESSION['wizard_message_mgid']);
		redirect('unauthorized.php');
	}
	
	$_SESSION['wizard_message']['mgid'] = $_SESSION['wizard_message_mgid'];
	unset($_SESSION['wizard_message_mgid']);
}

// if the message group id isn't set in session data, redirect to unauth
if (!isset($_SESSION['wizard_message']['mgid']))
	redirect('unauthorized.php');

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$PAGE = _L("notifications").":"._L("messages");
$TITLE = false;

require_once("nav.inc.php");

?>
<script type="text/javascript">
<?	Validator::load_validators(array("PhoneMessageRecorderValidator", "ValTextAreaPhone", "ValMessageBody"));?>
</script>
<?

startWindow(_L("Add Phone Message Wizard"));
echo $wizard->render();
endWindow();
if (isset($_SESSION['wizard_message']['debug']) && $_SESSION['wizard_message']['debug']) {
	startWindow("Wizard Data");
	echo "<pre>";
	var_dump($_SESSION['wizard_message']);
	echo "</pre>";
	endWindow();
}
require_once("navbottom.inc.php");

?>