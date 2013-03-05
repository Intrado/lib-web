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
require_once("obj/EmailAttach.fi.php");
require_once("obj/EmailAttach.val.php");
require_once("obj/HtmlTextArea.fi.php");
require_once("obj/ValMessageBody.val.php");
require_once("obj/RetranslationItem.fi.php");
require_once("obj/CheckBoxWithHtmlPreview.fi.php");
require_once("obj/EmailMessageEditor.fi.php");
require_once("obj/PreviewButton.fi.php");
require_once("obj/ValTranslationLength.val.php");

// appserver and thrift includes
require_once("inc/appserver.inc.php");

////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
global $USER;
if (!$USER->authorize("sendemail"))
	redirect('unauthorized.php');

////////////////////////////////////////////////////////////////////////////////
// Passed parameter checking
////////////////////////////////////////////////////////////////////////////////
if (isset($_GET['mgid']) && $_GET['mgid']) {
	if (!userOwns('messagegroup', $_GET['mgid']))
		redirect('unauthorized.php');
	
	$_SESSION['wizard_message_mgid'] = ($_GET['mgid'] + 0);		
}

if (isset($_GET['subtype']) && $_GET['subtype']) {
	if (!in_array($_GET['subtype'], array("plain", "html")))
		redirect('unauthorized.php');
	
	// Mini wizard is unavailable when restricted to use stationery
	if ($USER->authorize('forcestationery') && $_GET['subtype'] == "html")
		redirect('unauthorized.php');
	
	$_SESSION['wizard_message_subtype'] = $_GET['subtype'];		
}

if (isset($_GET['debug']))
	$_SESSION['wizard_message']['debug'] = true;

////////////////////////////////////////////////////////////////////////////////
// Wizard step data
////////////////////////////////////////////////////////////////////////////////

class MsgWiz_language extends WizStep {
	function getForm($postdata, $curstep) {
		global $USER;
		
		$langs = array();
		if ($USER->authorize('sendmulti')) {
			$langs["autotranslate"] = _L("Automatically <b>Translate</b> from English to other languages");
			$langs[] = "#-#"; //insert an <hr>
		}
		
		// alpha sorted, but with english as the first entry
		$langs["en"] = _L("Create the <b>English</b> message");
		
		$languages = Language::getLanguageMap();
		
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
		
		$helpsteps = array(_L("Select whether or not you would like to automatically translate your message using Google Translate. If you prefer to write your own translations, leave this option unchecked.<br><br>Next, select the language of the message you're creating."));
		
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

class MsgWiz_emailText extends WizStep {
	function getForm($postdata, $curstep) {
		global $USER;
		$msgdata = isset($postdata['/message/phone/text']['message'])?json_decode($postdata['/message/phone/text']['message']):json_decode('{"text": ""}');
		
		$messagegroup = new MessageGroup($_SESSION['wizard_message_mgid']);
		
		$subtype = $_SESSION['wizard_message_subtype'];
		
		// Form Fields.
		$formdata = array($this->title);
		$messagevalidators = array(
			array("ValRequired"),
			array("ValMessageBody", "messagegroupid" => $_SESSION['wizard_message_mgid']),
			array("ValLength","max" => 256000)
		);
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
			$messagevalidators[] = array("ValTranslationLength");
		}
		
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

		$formdata["subject"] = array(
			"label" => _L("Subject"),
			"fieldhelp" => _L('The Subject will appear as the subject line of the email.'),
			"value" => "",
			"validators" => array(
				array("ValRequired"),
				array("ValLength","max" => 255)
			),
			"control" => array("TextField","max"=>255,"min"=>3,"size"=>45),
			"helpstep" => 3
		);

		$formdata["attachments"] = array(
			"label" => _L('Attachments'),
			"fieldhelp" => _L("You may attach up to three files that are up to 2MB each. For greater security, certain file types are not permitted. Be aware that some email accounts may not accept attachments above a certain size and may reject your message."),
			"value" => "{}",
			"validators" => array(array("ValEmailAttach")),
			"control" => array("EmailAttach"),
			"helpstep" => 4
		);

		$formdata["message"] = array(
			"label" => _L("Email Message"),
			"fieldhelp" => _L('Enter the message you would like to send. Helpful tips for successful messages can be found at the Help link in the upper right corner.'),
			"value" => $msgdata->text,
			"validators" => $messagevalidators,
			"control" => array("EmailMessageEditor", "subtype" => $subtype),
			"helpstep" => 5
		);
		
		$langcode = (isset($postdata["/create/language"]["language"])?$postdata["/create/language"]["language"]:Language::getDefaultLanguageCode());
		if ($langcode == "autotranslate")
			$langcode = "en";
		$formdata["preview"] = array(
			"label" => null,
			"value" => "",
			"validators" => array(),
			"control" => array("PreviewButton",
				"language" => $langcode,
				"subtype" => $_SESSION['wizard_message_subtype'],
				"fromnametarget" => "fromname",
				"fromtarget" => "from",
				"subjecttarget" => "subject",
				"texttarget" => "message",
			),
			"helpstep" => 3
		);
		
		$helpsteps = array();
		$helpsteps[] = _L("Enter the name for the email account.");
		$helpsteps[] = _L("Enter the address where you would like to receive replies.");
		$helpsteps[] = _L("Enter the subject of the email here.");
		$helpsteps[] =	_L("You may attach up to three files that are up to 2MB each. For greater security, only certain types of files are accepted.<br><br><b>Note:</b> Some email accounts may not accept attachments above a certain size and may reject your message.");
		if ($subtype == "html"){
			$helpsteps[] = 	_L("<p>Enter your HTML email in this field. You may use the HTML editing tools to format your email.</p> <p>To insert data fields, set the cursor where the data should appear. Be careful to not delete any of the brackets that appear around inserted data fields. Select the data field you wish to insert and enter a default value which will display if a recipient does not have data in the chosen field. Click the 'Insert' button to add the data field to your message.</p><p><b>Note:</b> <i>Date inserts will insert the date relative to when the job is sent. For example, if you insert 'Date' and send the message immediately, it will have today's date. However, if you send the message tomorrow, it would insert tomorrow's date.</i></p>");
		} else {
			$helpsteps[] = 	_L("Enter your plain text version of your email in this field. <br><br>Be sure to introduce yourself and give detailed information. For helpful message tips and ideas, click the Help link in the upper right corner of the screen.");
		}
		
		return new Form("emailText",$formdata,$helpsteps);
	}

	//returns true if this step is enabled
	function isEnabled($postdata, $step) {
		return true;
	}
}

class MsgWiz_translatePreview extends WizStep {
	function escapeFieldInserts($text) {
		return str_replace(">>", "&#062;&#062;", str_replace("<<", "&#060;&#060;", $text));
	}
	
	function getForm($postdata, $curstep) {
		global $TRANSLATIONLANGUAGECODES;
		
		$subtype = $_SESSION['wizard_message_subtype'];
		$ishtml = ($subtype == "html"?true:false);
		
		// msgdata from email
		$sourcetext = $this->parent->dataHelper('/create/email:message');
		
		static $translations = false;
		static $translationlanguages = false;

		$warning = "";
		if(mb_strlen($sourcetext) > 5000) {
			$warning = _L('Warning. Only the first 5000 characters are translated.');
		}

		//Get available languages
		$translationlanguages = array_intersect_key(Language::getLanguageMap(), array_flip($TRANSLATIONLANGUAGECODES));
		
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
			"control" => array("FormHtml","html"=>'<div class="translate">'. ($ishtml?$this->escapeFieldInserts($sourcetext):escapehtml($sourcetext)) .'</div>'),
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
			foreach($translations as $translation){
				if ($translation === false)
					continue;
				
				$languagecode = array_shift($translationlanguagecodes);
				$formdata[] = Language::getName($languagecode);
				$formdata[$languagecode] = array(
					"label" => _L("Enabled"),
					"fieldhelp" => _L('Check this box to automatically translate your message using Google Translate.'),
					"value" => true,
					"validators" => array(),
					"control" => array("RetranslationItem",
						"type" => "email",
						"subject" => $this->parent->dataHelper("/create/email:subject"),
						"fromname" => $this->parent->dataHelper("/create/email:fromname"),
						"from" => $this->parent->dataHelper("/create/email:from"),
						"ishtml" => $ishtml,
						"langcode" => $languagecode,
						"message" => $translation,
						"disabledmessage" => _L("People tagged with this language will receive the English version.")),
					"helpstep" => 2
				);
			}
		}
		if(!isset($formdata["Translationinfo"])) {
			$formdata["Translationinfo"] = array(
				"label" => " ",
				"control" => array("FormHtml","html"=>'
					<div id="branding">
						<div class="gBranding">
							<span class="gBrandingText">
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
		if ($USER->authorize('sendmulti') && $this->parent->dataHelper("/create/language:language") == "autotranslate") {
			return true;
		}
		
		return false;
	}
}

class MsgWiz_submitConfirm extends WizStep {
	function getForm($postdata, $curstep) {
		global $USER;
		
		$srclanguagecode = $this->parent->dataHelper('/create/language:language',false,Language::getDefaultLanguageCode());
				
		$subtype = $_SESSION['wizard_message_subtype'];
		
		$languagecodes = array();
		if ($srclanguagecode == "autotranslate") {
			$languagecodes[] = "en";
			foreach ($this->parent->dataHelper('/create/translatepreview',false,array()) as $langcode => $enabled) {
				if ($enabled === "true")
					$languagecodes[] = $langcode;
			}
		} else {
			$languagecodes[] = $srclanguagecode;
		}
		
		// get the message group we are modifying
		$messagegroup = new MessageGroup($_SESSION['wizard_message_mgid']);
			
		$html = '<div>'._L('The following messages will be overwritten').'</div>
				<table class="list">
					<tr class="listHeader">
						<th>'. _L("Language"). '&nbsp;&nbsp;</th>
						<th>'. _L("Type"). '&nbsp;&nbsp;</th>
					</tr>';
		$count = 0;
		foreach ($languagecodes as $languagecode) {
			if ($messagegroup->hasMessage('email', $subtype, $languagecode)) {
				$html .= '
					<tr '. (($count % 2)?'class="listAlt"':''). '>
						<td>'. Language::getName($languagecode).'</td>
						<td>'. escapehtml(_L("Email/%s", $subtype)). '</td>
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
		
		$helpsteps = array (_L("These older messages will be overwritten with the messages you've just created in the wizard. Clicking Next will replace the old messages with the new ones."));
		
		return new Form("submitConfirm",$formdata,$helpsteps);
		
	}
	
	//returns true if this step is enabled
	function isEnabled($postdata, $step) {
		global $USER;
		
		// if we are on the finish step, its only important to see if it actually overwrote anything
		if (isset($_SESSION['wizard_message']['finish'])) {
			if (isset($_SESSION['wizard_message']['didOverwrite']))
				return true;
			else
				return false;
		}
		
		if (isset($_SESSION['wizard_message_subtype']))
			$subtype = $_SESSION['wizard_message_subtype'];
		else
			return false;
		
		// if the user has multilingual but hasn't selected a language
		if ($USER->authorize('sendmulti') && !$this->parent->dataHelper('/create/language:language'))
			return false;
		
		// only show the confirm step if the creation of this message will overwrite an existing message
		$languagecode = $this->parent->dataHelper('/create/language:language',false,Language::getDefaultLanguageCode());
		
		$args = array();
		
		// if it's an auto translate, we have to look up each of the trasnlated languages and english
		if ($languagecode == "autotranslate") {
			// autotranslate always overwrites the english message
			$args[] = "en";
			// need the translated step's session data to get the enabled languages
			foreach ($this->parent->dataHelper('/create/translatepreview',false,array()) as $langcode => $enabled) {
				if ($enabled === "true")
					$args[] = $langcode;
			}
		} else {
			// not auto translate so...
			// query the messages to see if a message exists already for this language code
			$args[] = $languagecode;
		}
		
		// need a list of ? for each language code we are going to look up to put in the query
		$langqueryargs = repeatWithSeparator("?",",",count($args));
		
		// add additional query arguments
		$args[] = $_SESSION['wizard_message_mgid'];
		$args[] = $subtype;
		
		// query for any messages matching one of these language codes
		$hasmessage = QuickQuery(
			"select 1 from message 
			where languagecode in (".$langqueryargs.") 
			and messagegroupid = ? 
			and type = 'email' 
			and subtype = ? 
			and autotranslate in ('none', 'translated', 'overridden')", false, $args);
		
		if ($hasmessage)
			return true;
		
		return false;
	}
}

class FinishMessageWizard extends WizFinish {
	function finish ($postdata) {
		global $USER;
		
		$_SESSION['wizard_message']['finish'] = true;
		
		// start a transaction
		QuickQuery("BEGIN");
		
		// is the messagegroup id still valid?
		$messagegroup = new MessageGroup($_SESSION['wizard_message_mgid']);
		if (!userOwns("messagegroup", $messagegroup->id) || $messagegroup->deleted)
			return;
		
		$messagegroup->modified = date("Y-m-d H:i:s", time());
		$messagegroup->update(array("modified"));
		
		// get the language code from postdata
		$langcode = $this->parent->dataHelper("/create/language:language",false,Language::getDefaultLanguageCode());
		
		// auto translations and english need the source stored as english. everything else stores as an overridden autotranslation 
		if ($langcode == "autotranslate" || $langcode == "en") {
			$sourcelangcode = "en";
			$autotrans = 'none';
		} else {
			$sourcelangcode = $langcode;
			$autotrans = 'overridden';
		}
		
		// #################################################################
		// Text based messages
		
		// keep track of the message data we are going to create messages for
		$messages = array();
		
		$subtype = $_SESSION['wizard_message_subtype'];
		
		// email message
		if (MsgWiz_emailText::isEnabled($postdata, false)) {
			$messages[$sourcelangcode][$autotrans]['text'] = $postdata["/create/email"]["message"];
			$messages[$sourcelangcode][$autotrans]["fromname"] = $postdata["/create/email"]["fromname"];
			$messages[$sourcelangcode][$autotrans]["from"] = $postdata["/create/email"]["from"];
			$messages[$sourcelangcode][$autotrans]["subject"] = $postdata["/create/email"]["subject"];
			$messages[$sourcelangcode][$autotrans]['attachments'] = $this->parent->dataHelper("/create/email:attachments",true);
			if ($messages[$sourcelangcode][$autotrans]['attachments'] == null) 
				$messages[$sourcelangcode][$autotrans]['attachments'] = array();
			
			// check for and retrieve translations
			if (MsgWiz_translatePreview::isEnabled($postdata, false) && $langcode == "autotranslate") {
				$translationselections = $this->parent->dataHelper("/create/translatepreview",false,array());
				$translations = translate_fromenglish(makeTranslatableString($messages['en']['none']["text"]),array_keys($translationselections));
				$translationsindex = 0;
				foreach ($translationselections as $translatedlangcode => $enabled) {
					if ($enabled) {
						$messages[$translatedlangcode]['source'] = $messages['en']['none'];
						$messages[$translatedlangcode]['translated'] = $messages['en']['none'];
						if ($translations[$translationsindex] !== false)
							$messages[$translatedlangcode]['translated']['text'] = $translations[$translationsindex];
					}
					$translationsindex++;
				}
			}
		}
		
		// #################################################################
		// create a message for each one
		
		// for each language code
		foreach ($messages as $langcode => $autotranslatevalues) {
			// for each autotranslate value
			foreach ($autotranslatevalues as $autotranslate => $data) {
				
				// check for an existing message with this language code for this message group 
				$message = DBFind("Message", 
					"from message 
					where messagegroupid = ? 
					and type = 'email' 
					and subtype = ? 
					and languagecode = ? 
					and autotranslate = ?", false, array($messagegroup->id, $subtype, $langcode, $autotranslate));
			
				if (!$message) {
					// no message, create a new one!
					$message = new Message();
				} else {
					$_SESSION['wizard_message']['didOverwrite'] = true;
				}
				
				$message->messagegroupid = $messagegroup->id;
				$message->type = 'email';
				$message->subtype = $subtype;
				$message->autotranslate = $autotranslate;
				$message->name = $messagegroup->name;
				$message->description = Language::getName($langcode);
				$message->userid = $USER->id;
				$message->modifydate = date("Y-m-d H:i:s");
				$message->languagecode = $langcode;
				
				$message->subject = $data["subject"];
				$message->fromname = $data["fromname"];
				$message->fromemail = $data["from"];
				
				$message->stuffHeaders();
				if (!$message->id)
					$message->create();
				else
					$message->update();
				
				// create the message parts
				$message->recreateParts($data['text'], null, isset($data['gender'])?$data['gender']:false);
				
				// check for existing attachments
				$existingattachments = QuickQueryList("select contentid, id from messageattachment where messageid = ?", true, false, array($message->id));
				
				// if there are message attachments, attach them
				$existingattachmentstokeep = array();
				if (isset($data['attachments']) && $data['attachments']) {
					foreach ($data['attachments'] as $cid => $details) {
						// check if this is already attached.
						if (isset($existingattachments[$cid])) {
							$existingattachmentstokeep[$existingattachments[$cid]] = true;
							continue;
						} else {
							$msgattachment = new MessageAttachment();
							$msgattachment->messageid = $message->id;
							$msgattachment->contentid = $cid;
							$msgattachment->filename = $details->name;
							$msgattachment->size = $details->size;
							$msgattachment->create();
						}
					}
				}
				// remove attachments that are no longer attached
				foreach ($existingattachments as $cid => $attachmentid) {
					if (!isset($existingattachmentstokeep[$attachmentid])) {
						$attachment = new MessageAttachment($attachmentid);
						if ($attachment)
							QuickUpdate("delete from messageattachment where id = ?", false, array($attachment->id));
					}
				}
				
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
					and type = 'email'
					and subtype = ?
					$autotranslateclause
					and languagecode = ?
					and id != ?", false, array($messagegroup->id, $subtype, $langcode, $message->id));
			}
		}
		
		$messagegroup->updateDefaultLanguageCode();
		
		// end the transaction
		QuickQuery("COMMIT");
	}
	
	function getFinishPage ($postdata) {
		$html = '<h3>Success! Your message has been saved</h3>';
		return $html;
	}
}

$wizdata = array(
	"create" => new WizSection ("Create",array(
		"language" => new MsgWiz_language(_L("Language")),
		"email" => new MsgWiz_emailText(_L("Compose Email")),
		"translatepreview" => new MsgWiz_translatePreview(_L("Translations")),
	)),
	"submit" => new WizSection ("Confirm",array(
		"confirm" => new MsgWiz_submitConfirm(_L("To be Overwritten"))
	))
);

$wizard = new Wizard("wizard_message",$wizdata, new FinishMessageWizard("Finish"));
$wizard->doneurl = "mgeditor.php?id=".$_SESSION['wizard_message_mgid'];
$wizard->handlerequest();

// if the message group id or subtype isn't set in session data, redirect to unauth
if (!isset($_SESSION['wizard_message_mgid']))
	redirect('unauthorized.php');
if (!isset($_SESSION['wizard_message_subtype']))
	redirect('unauthorized.php');

PreviewModal::HandleRequestWithEmailText();

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$PAGE = _L("notifications").":"._L("messages");
$TITLE = false;

require_once("nav.inc.php");

?>
<script type="text/javascript">
<?	Validator::load_validators(array("ValMessageBody", "ValEmailAttach","ValTranslationLength"));?>
</script>
<?
PreviewModal::includePreviewScript();


startWindow(_L("Add Email Message Wizard"));
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