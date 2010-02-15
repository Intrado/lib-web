<?php
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("inc/common.inc.php");
require_once("inc/table.inc.php");
require_once("inc/securityhelper.inc.php");
require_once("inc/html.inc.php");
require_once("inc/utils.inc.php");
require_once("inc/date.inc.php");
require_once("inc/translate.inc.php");
require_once("obj/Form.obj.php");
require_once("obj/FormTabber.obj.php");
require_once("obj/FormSplitter.obj.php");
require_once("obj/FormItem.obj.php");
require_once("obj/Phone.obj.php");
require_once("obj/Message.obj.php");
require_once("obj/Voice.obj.php");
require_once("obj/FieldMap.obj.php");
require_once("obj/AudioFile.obj.php");
require_once("obj/MessageBody.fi.php");
require_once("obj/MessagePart.obj.php");
require_once("obj/Content.obj.php");
require_once("obj/Validator.obj.php");
require_once("obj/ValDuplicateNameCheck.val.php");
require_once("obj/ValMessageBody.val.php");
require_once("obj/MessageGroup.obj.php");
require_once("obj/MessageAttachment.obj.php");
require_once("obj/traslationitem.obj.php");
require_once("obj/AudioUpload.fi.php");
require_once("obj/EmailAttach.fi.php");
require_once("obj/EmailAttach.val.php");
require_once("obj/Language.obj.php");
require_once("messagegroup.inc.php");

///////////////////////////////////////////////////////////////////////////////
// Authorization:
///////////////////////////////////////////////////////////////////////////////
$cansendphone = $USER->authorize('sendphone');
$cansendemail = $USER->authorize('sendemail');
$cansendsms = getSystemSetting('_hassms', false) && $USER->authorize('sendsms');
$cansendmultilingual = $USER->authorize('sendmulti');

// Only kick the user out if he does not have permission to create any message at all (neither phone, email, nor sms).
if (!$cansendphone && !$cansendemail && !$cansendsms) {
	redirect('unauthorized.php');
}

///////////////////////////////////////////////////////////////////////////////
// Defaults.
///////////////////////////////////////////////////////////////////////////////
$defaultautotranslate = 'none';
$defaultpermanent = 0;
$defaultmessagegroupname = 'Please enter a name';
$defaultemailheaders = array(
	'subject' => '',
	'fromname' => $USER->firstname . " " . $USER->lastname,
	'fromemail' => reset(explode(";", $USER->email))
);

///////////////////////////////////////////////////////////////////////////////
// Request processing:
///////////////////////////////////////////////////////////////////////////////
if (isset($_GET['id'])) {
	if (!isset($_SESSION['messagegroupid']) || $_SESSION['messagegroupid'] != $_GET['id']) {
		unset($_SESSION['emailheaders']);
		unset($_SESSION['emailattachments']);
		unset($_SESSION['autotranslatesourcetext']);
	}
	setCurrentMessageGroup($_GET['id']);

	if ($_GET['id'] === 'new') {
		// For a new messagegroup, it is first created in the database as deleted
		// in case the user does not submit the form. Once the form is submitted, the
		// messagegroup is set as not deleted; the permanent flag is toggled by the user.
		$newmessagegroup = new MessageGroup();
		$newmessagegroup->userid = $USER->id;
		$newmessagegroup->name = $defaultmessagegroupname;
		$newmessagegroup->defaultlanguagecode = Language::getDefaultLanguageCode();
		$newmessagegroup->description = '';
		$newmessagegroup->modified =  makeDateTime(time());
		$newmessagegroup->deleted = 1; // Set to deleted in case the user does not submit the form.
		$newmessagegroup->permanent = $defaultpermanent;

		if ($newmessagegroup->create())
			$_SESSION['messagegroupid'] = $newmessagegroup->id;
			
		redirect();
	}
}

if (!isset($_SESSION['messagegroupid'])) {
	redirect('unauthorized.php');
}

if (!isset($_SESSION['autotranslatesourcetext']))
	$_SESSION['autotranslatesourcetext'] = array();

$messagegroup = new MessageGroup(getCurrentMessageGroup());

///////////////////////////////////////////////////////////////////////////////
// Data Gathering:
///////////////////////////////////////////////////////////////////////////////
// Make an array of just the default language, for use in SMS and when the user cannot send multilingual messages.
$deflanguagecode = Language::getDefaultLanguageCode();
$deflanguage = array($deflanguagecode => Language::getName($deflanguagecode));

//if the user can send multi-lingual notifications use all languages, otherwise use an array of just the default.
$customerlanguages = $cansendmultilingual ? Language::getLanguageMap() : $deflanguage;

$ttslanguages = $cansendmultilingual ? Voice::getTTSLanguageMap() : array();
unset($ttslanguages[Language::getDefaultLanguageCode()]);
if ($cansendmultilingual)
	$allowtranslation = isset($SETTINGS['translation']['disableAutoTranslate']) ? (!$SETTINGS['translation']['disableAutoTranslate']) : true;
else
	$allowtranslation = false;
// NOTE: The customer may have a custom name for a particular language code, different from that of Google's.
$translationlanguages = $allowtranslation ? getTranslationLanguages() : array();
unset($translationlanguages[Language::getDefaultLanguageCode()]);
$customeremailtranslationlanguages = array_intersect_key($customerlanguages, $translationlanguages);
$customerphonetranslationlanguages = array_intersect_key($ttslanguages, $translationlanguages); // NOTE: $ttslanguages is already an a subset of $customerlanguages.

$datafields = FieldMap::getAuthorizedMapNames();
$messagegroup->readHeaders();

$permanent = $messagegroup->permanent;

if (!isset($_SESSION['emailheaders'])) {
	$_SESSION['emailheaders'] = $messagegroup->getGlobalEmailHeaders($defaultemailheaders);
}

// $emailattachments is a map indexed by contentid, containing size and name of each attachment, passed as data to the EmailAttach formitem.
$emailattachments = array();
foreach ($messagegroup->getGlobalEmailAttachments() as $attachment) {
	$emailattachments[$attachment->contentid] = array("size" => $attachment->size, "name" => $attachment->filename);
}
if (empty($emailattachments) && isset($_SESSION['emailattachments']))
	$emailattachments = $_SESSION['emailattachments'];

// $destinations is a tree that is populated according to the user's permissions; it contains destination types, subtypes, and languages.
$destinations = array();
if ($cansendphone) {
	$destinations['phone'] = array(
		'subtypes' => array('voice'),
		'languages' => $customerlanguages
	);
}
if ($cansendemail) {
	$destinations['email'] = array(
		'subtypes' => array('html', 'plain'),
		'languages' => $customerlanguages
	);
}
if ($cansendsms) {
	$destinations['sms'] = array(
		'subtypes' => array('plain'),
		'languages' => $deflanguage
	);
}

///////////////////////////////////////////////////////////////////////////////
// Formdata
///////////////////////////////////////////////////////////////////////////////
$destinationlayoutforms = array();
$clearmessageconfirmtext = _L("Are you sure you want to clear this message?");
foreach ($destinations as $type => $destination) {
	$countlanguages = count($destination['languages']);
	$subtypelayoutforms = array();
	foreach ($destination['subtypes'] as $subtype) {
		$messageformsplitters = array();

		// Autotranslator.
		if ($countlanguages > 1) {
			$autotranslatorformdata = array();

			if (empty($_SESSION['autotranslatesourcetext']["{$type}{$subtype}"]))
				$_SESSION['autotranslatesourcetext']["{$type}{$subtype}"] = $messagegroup->getMessageText($type,$subtype,Language::getDefaultLanguageCode(), 'none');

			if ($type == 'phone' || $type == 'email') {
				$autotranslatorformdata["header"] = makeFormHtml("<div class='MessageBodyHeader'>" . _L("Automatic Translation") . "</div>" . icon_button(_L("Clear"),"delete", null, null, 'id="clearmessagebutton"') . "<span id='messageemptyspan'></span>");
				
				// NOTE: The plain-text autotranslator sets overrideplaintext = true because the user wouldn't be there unless he wanted to autotranslate a custom plain text message.
				$autotranslatorformdata["sourcemessagebody"] = makeMessageBody(false, $type, $subtype, 'autotranslator', _L('Automatic Translation'), $_SESSION['autotranslatesourcetext']["{$type}{$subtype}"], $datafields, $subtype == 'html', $subtype == 'plain', true);
				$autotranslatorformdata["extrajavascript"] = makeFormHtml("
					<script type='text/javascript'>
						(function() {
							var itemname = '{$type}-{$subtype}-autotranslator_sourcemessagebody';
							if (!$(itemname))
								return;
								
							var form = $(itemname).up('form');
							var clearmessagebutton = $('clearmessagebutton');
							var keytimer = null;
							var validateHtmlEditor = function(event, checknow) {
								var formelement;
								var htmleditorobject;
								
								window.clearTimeout(keytimer);
								if (!checknow) {
									keytimer = window.setTimeout(function() {
										validateHtmlEditor(null, true);
									}, 500);
									return;
								}
								
								formelement = $(itemname);
								htmleditorobject = getHtmlEditorObject();
								if (htmleditorobject) {
									saveHtmlEditorContent(htmleditorobject);
									if (htmleditorobject.currenttextarea && htmleditorobject.currenttextarea.id.include(itemname)) {
										form_do_validation(form, formelement);
									}
								}
							};
								
							// Clear any existing click-observers on this element, then make a new one.
							clearmessagebutton.stopObserving('click').observe('click', function() {
								if (!confirm('".addslashes($clearmessageconfirmtext)."')) {
									saveHtmlEditorContent();
									return;
								}
								
								$(itemname).value = '';
								
								clearHtmlEditorContent();
							});
							
							registerHtmlEditorKeyListener(validateHtmlEditor);
						})();
					</script>
				");
					
				$autotranslatorformdata["refreshtranslations"] = makeFormHtml(icon_button(_L("Refresh Translations"),"fugue/arrow_circle_double_135", null, null, 'id="autotranslatorrefreshtranslationbutton"') . "<div style='margin-top:35px;clear:both'></div>");

				$translationitems = array();
				foreach ($destination['languages'] as $languagecode => $languagename) {
					if ($type == 'phone' && !isset($customerphonetranslationlanguages[$languagecode]))
						continue;
					else if ($type == 'email' && !isset($customeremailtranslationlanguages[$languagecode]))
						continue;

					$translationitems[] = "{$languagecode}-translationitem";
					$autotranslatorformdata["{$languagecode}-translationitem"] = makeTranslationItem(false, $type, $subtype, $languagecode, $languagename, $messagegroup->preferredgender, $_SESSION['autotranslatesourcetext']["{$type}{$subtype}"], "", 0, $languagename, false, false, false, !$messagegroup->hasMessage($type, $subtype, $languagecode), '', null, true);
				}
				$autotranslatorformdata["sourcemessagebody"]["requires"] = $translationitems;
				
				$autotranslatorformdata["branding"] = makeBrandingFormHtml();
			}

			$accordionsplitter = makeAccordionSplitter($type, $subtype, 'autotranslator', $permanent, $messagegroup->preferredgender, true, $type == 'email' ? $emailattachments : null, false);

			$messageformsplitters[] = new FormSplitter("{$type}-{$subtype}-autotranslator", _L("Automatic Translation"), "img/icons/world.gif", "verticalsplit", array(), array(array("title" => "", "formdata" => $autotranslatorformdata), $accordionsplitter));
		}

		// Individual Message (type-subtype-language).
		foreach ($destination['languages'] as $languagecode => $languagename) {
			$blankmessagewarning = $countlanguages > 1 ? _L("If the %s message is blank, these contacts will receive messages in the default language.", $languagename) : '';
			$messageformname = "{$type}-{$subtype}-{$languagecode}";
			
			if ($subtype == 'html' && $languagecode == 'fr') {
				$sourcemessagetext = $messagegroup->getMessageText($type,$subtype,$languagecode, 'source');
			}
			$messagetexts = array(
				'source' => $messagegroup->getMessageText($type,$subtype,$languagecode, 'source'),
				'translated' => $messagegroup->getMessageText($type,$subtype,$languagecode, 'translated'),
				'overridden' => $messagegroup->getMessageText($type,$subtype,$languagecode, 'overridden'),
				'none' => $messagegroup->getMessageText($type,$subtype,$languagecode, 'none')
			);

			$formdata = array();
			
			$required = ($messagegroup->defaultlanguagecode == $languagecode) && $messagegroup->hasMessage($type);
			
			if (($type == 'phone' && isset($customerphonetranslationlanguages[$languagecode])) ||
				($type == 'email' && isset($customeremailtranslationlanguages[$languagecode]))
			) {
				$translationenabled = empty($messagetexts['none']);
				
				// Translation formitem.
				if (!empty($messagetexts['overridden'])) {
					$messagetext = $messagetexts['overridden'];
				} else if (!empty($messagetexts['translated'])) {
					$messagetext = $messagetexts['translated'];
				} else {
					$messagetext = $messagetexts['none'];
				}
				$formdata["header"] = makeFormHtml("<div class='MessageBodyHeader'>" . escapehtml(_L("%s Message", $languagename)) . "</div>" . icon_button(_L("Clear"),"delete", null, null, 'id="clearmessagebutton"') . "
					<span id='messageemptyspan'>".escapehtml($blankmessagewarning)."</span>
				");
				
				$emailplain = $type == 'email' && $subtype == 'plain';
				
				if ($emailplain) {
					$plainmessage = null;
					foreach ($messagegroup->getMessages() as $message) {
						if ($message->type == $type &&
							$message->subtype == 'plain' &&
							$message->languagecode == $languagecode
						) {
							$plainmessage = $message;
							break;
						}
					}
					
					if ($plainmessage) {
						$plainmessage->readHeaders();
					}
					
					foreach ($messagegroup->getMessages() as $message) {
						if ($message->type == $type &&
							$message->subtype == 'html' &&
							$message->languagecode == $languagecode
						) {
							$required = false;
							break;
						}
					}
				}
				
				$overridingplaintext = isset($plainmessage) && $plainmessage && $plainmessage->overrideplaintext;
				
				$formdata["translationitem"] = makeTranslationItem($required, $type, $subtype, $languagecode, $languagename, $messagegroup->preferredgender, $messagetexts['source'], $messagetext,  $overridingplaintext ? 1 : 0, _L("Enable Translation"), !empty($messagetexts['overridden']), true, false, $translationenabled, "", $datafields);
				
				if ($emailplain) {
					$formdata["overrideplaintext"] = array(
						"label" => _L("Override Plain Text"),
						"value" => $overridingplaintext,
						"fieldhelp" => _L("A plain-text message will be generated from the html message, however you may type a custom plain-text message by overriding it."),
						"control" => array("CheckBox"),
						"validators" => array(
							array('ValInArray', 'values' => array('true', 'false')),
							array('ValOverridePlainText', 'field' => 'translationitem')
						),
						"requires" => array("translationitem"),
						"helpstep" => 1
					);
				}
				
				$formdata["extrajavascript"] = makeFormHtml("
					<script type='text/javascript'>
						(function() {
							var itemname = '{$type}-{$subtype}-{$languagecode}_translationitem';
							if (!$(itemname))
								return;
								
							var form = $(itemname).up('form');
							var clearmessagebutton = $('clearmessagebutton');
							var messageemptyspan = $('messageemptyspan');
							
							var keytimer = null;
							var warnIfMessageTextEmpty = function(event, checknow) {
								var translationvalueobject;
								var messagetext;
								var formelement;
								var htmleditorobject;
								
								window.clearTimeout(keytimer);
								if (!checknow) {
									keytimer = window.setTimeout(function() {
										warnIfMessageTextEmpty(null, true);
									}, 500);
									return;
								}
								
								formelement = $(itemname);
								htmleditorobject = getHtmlEditorObject();
								if (htmleditorobject) {
									saveHtmlEditorContent(htmleditorobject);
									if (htmleditorobject.currenttextarea && htmleditorobject.currenttextarea.id.include(itemname)) {
										form_do_validation(form, formelement);
									}
								}
								
								translationvalueobject = setTranslationValue(itemname);
								
								if (translationvalueobject.enabled)
									messagetext = translationvalueobject.override ? translationvalueobject.text : translationvalueobject.englishText;
								else
									messagetext = translationvalueobject.text;
								
								messageemptyspan.style.visibility = (messagetext.strip() == '') ? 'visible' : 'hidden';
							};
							
							// Clear any existing click-observers on this element, then make a new one which will call warnIfMessageTextEmpty().
							clearmessagebutton.stopObserving('click').observe('click', function() {
								var translationvalueobject;
								
								if (!confirm('".addslashes($clearmessageconfirmtext)."')) {
									saveHtmlEditorContent();
									return;
								}
								
								translationvalueobject = setTranslationValue(itemname);
								
								if (translationvalueobject.enabled) {
									if (translationvalueobject.override) {
										$(itemname+'text').value = '';
									} else {
										$(itemname+'englishText').value = '';
									}
								} else {
									$(itemname+'text').value = '';
								}
								
								warnIfMessageTextEmpty();
								clearHtmlEditorContent();
							});
							
							warnIfMessageTextEmpty();
							
							// Clear any existing keyup-observers for this element, then make a new one which will call warnIfMessageTextEmpty().
							form.stopObserving('keyup').observe('keyup', warnIfMessageTextEmpty);
							
							registerHtmlEditorKeyListener(warnIfMessageTextEmpty);
							
							var overrideplaintext = " . ($emailplain ? "$('{$type}-{$subtype}-{$languagecode}_overrideplaintext')" : "null") . ";
							if (overrideplaintext) {
								overrideplaintext.observe('click', function() {
									form.fire('PlainEmailCheckbox:OverridePlainText', {
										override: overrideplaintext.checked
									});
								});
							}
						})();
					</script>
				");
				
				$formdata["branding"] = makeBrandingFormHtml();
			} else {
				if ($type == 'sms') {
					$formdata["header"] = makeFormHtml("<div class='MessageBodyHeader'>" . _L("SMS Message") . "</div>" . icon_button(_L("Clear"),"delete", null, null, 'id="clearmessagebutton"') . "<span id='messageemptyspan'></span>");
					$formdata['nonemessagebody'] = array(
						"label" => _L("SMS Message"),
						"value" => $messagetexts['none'],
						"fieldhelp" => _L("Short text message that can be sent to mobile phones. These messages cannot be longer than 160 characters."),
						"validators" => array(
							array("ValLength","max"=>160),
							array("ValRegExp","pattern" => getSmsRegExp())
						),
						"control" => array("TextArea","cols" => 50, "rows"=>10,"counter"=>160),
						"renderoptions" => array("label" => false, "icon" => false, "error" => true),
						"helpstep" => 2
					);
					$formdata["extrajavascript"] = makeFormHtml("
						<script type='text/javascript'>
							(function() {
								var itemname = '{$type}-{$subtype}-{$languagecode}_nonemessagebody';
								if (!$(itemname))
									return;
								
								var clearmessagebutton = $('clearmessagebutton');
								
								// Clear any existing click-observers on this element, then make a new one.
								clearmessagebutton.stopObserving('click').observe('click', function() {
									if (!confirm('".addslashes($clearmessageconfirmtext)."'))
										return;
									
									$(itemname).value = '';
								});
							})();
						</script>
					");
				} else {
					$formdata["header"] = makeFormHtml("<div class='MessageBodyHeader'>" . _L("%s Message", $languagename) . "</div>" . icon_button(_L("Clear"),"delete", null, null, 'id="clearmessagebutton"') . "
						<span id='messageemptyspan'>".escapehtml($blankmessagewarning)."</span>
					");
					
					$emailplain = $type == 'email' && $subtype == 'plain';
					
					if ($emailplain) {
						$plainmessage = $messagegroup->getMessage($type, $subtype, $languagecode, 'none');
						
						if ($plainmessage) {
							$plainmessage->readHeaders();
							foreach ($messagegroup->getMessages() as $message) {
								if ($message->type == $type &&
									$message->subtype == 'html' &&
									$message->languagecode == $languagecode
								) {
									$required = false;
									break;
								}
							}
						}
					}
					
					$overridingplaintext = isset($plainmessage) && $plainmessage && $plainmessage->overrideplaintext;
					
					$formdata['nonemessagebody'] = makeMessageBody($required, $type, $subtype, $languagecode, _L("%s Message", $languagename), $messagetexts['none'], $datafields, $subtype == 'html', $overridingplaintext ? 1 : 0);
					
					if ($emailplain) {
						$formdata["overrideplaintext"] = array(
							"label" => _L("Override Plain Text"),
							"value" => $overridingplaintext,
							"fieldhelp" => _L("A plain-text message will be generated from the html message, however you may type a custom plain-text message by overriding it."),
							"control" => array("CheckBox"),
							"validators" => array(
								array('ValInArray', 'values' => array('true', 'false')),
								array('ValOverridePlainText', 'field' => 'nonemessagebody')
							),
							"requires" => array("nonemessagebody"),
							"helpstep" => 1
						);
					}
					
					$formdata["extrajavascript"] = makeFormHtml("
						<script type='text/javascript'>
							(function() {
								var itemname = '{$type}-{$subtype}-{$languagecode}_nonemessagebody';
								if (!$(itemname))
									return;
									
								var form = $(itemname).up('form');
								var clearmessagebutton = $('clearmessagebutton');
								var messageemptyspan = $('messageemptyspan');
								
								var keytimer = null;
								var warnIfMessageTextEmpty = function(event, checknow) {
									var messagetext;
									var formelement;
									var htmleditorobject;
									
									window.clearTimeout(keytimer);
									if (!checknow) {
										keytimer = window.setTimeout(function() {
											warnIfMessageTextEmpty(null, true);
										}, 500);
										return;
									}
									
									formelement = $(itemname);
									htmleditorobject = getHtmlEditorObject();
									if (htmleditorobject) {
										saveHtmlEditorContent(htmleditorobject);
										if (htmleditorobject.currenttextarea && htmleditorobject.currenttextarea.id.include(itemname)) {
											form_do_validation(form, formelement);
										}
									}
									
									messagetext = formelement.value;
									
									messageemptyspan.style.visibility = (messagetext.strip() == '') ? 'visible' : 'hidden';
								};
								
								// Clear any existing click-observers on this element, then make a new one which will call warnIfMessageTextEmpty().
								clearmessagebutton.stopObserving('click').observe('click', function() {
									if (!confirm('".addslashes($clearmessageconfirmtext)."')) {
										saveHtmlEditorContent();
										return;
									}
									
									$(itemname).value = '';
									
									warnIfMessageTextEmpty();
									clearHtmlEditorContent();
								});
								
								warnIfMessageTextEmpty();
								
								// Clear any existing keyup-observers for this element, then make a new one which will call warnIfMessageTextEmpty().
								form.stopObserving('keyup').observe('keyup', warnIfMessageTextEmpty);
								
								registerHtmlEditorKeyListener(warnIfMessageTextEmpty);
								
								var overrideplaintext = " . ($emailplain ? "$('{$type}-{$subtype}-{$languagecode}_overrideplaintext')" : "null") . ";
								if (overrideplaintext) {
									overrideplaintext.observe('click', function() {
										form.fire('PlainEmailCheckbox:OverridePlainText', {
											override: overrideplaintext.checked
										});
									});
								}
							})();
						</script>
					");
				}
			}
			
			$accordionsplitter = makeAccordionSplitter($type, $subtype, $languagecode, $permanent, $messagegroup->preferredgender, false, $type == 'email' ? $emailattachments : null, isset($formdata['translationitem']) ? true : false, $messagegroup, $countlanguages > 1);

			$messageformsplitters[] = new FormSplitter($messageformname, $languagename, $messagegroup->hasMessage($type, $subtype, $languagecode) ? "img/icons/accept.gif" : "img/icons/diagona/16/160.gif", "verticalsplit", array(), array(
			array("title" => "", "formdata" => $formdata),
			$accordionsplitter));
		}

		if ($countlanguages > 1) {
			$subtypelayoutforms[] = new FormTabber("{$type}-{$subtype}", $subtype == 'html' ? 'HTML' : ucfirst($subtype), $messagegroup->hasMessage($type, $subtype) ? "img/icons/accept.gif" : "img/icons/diagona/16/160.gif", "verticaltabs", $messageformsplitters);
		} else if ($countlanguages == 1) {
			$messageformsplitters[0]->title = ucfirst($subtype);
			$subtypelayoutforms[] = $messageformsplitters[0];
		}
	}

	if (count($destination['subtypes']) > 1) {
		if ($type == 'email') {
			$additionalvalidators = $messagegroup->getFirstMessageOfType('email') ? array(array("ValRequired")) : array();

			$emailheadersformdata = array();
			$emailheadersformdata['subject'] = array(
				"label" => _L('Subject'),
				"value" => $_SESSION['emailheaders']['subject'],
				"validators" => array_merge($additionalvalidators, array(
					array("ValLength","max" => 50)
				)),
				"control" => array("TextField","size" => 30, "maxlength" => 50),
				"helpstep" => 1
			);
			$emailheadersformdata['fromname'] = array(
				"label" => _L('From Name'),
				"value" => $_SESSION['emailheaders']['fromname'],
				"validators" => array_merge($additionalvalidators, array(
					array("ValLength","max" => 50)
				)),
				"control" => array("TextField","size" => 30, "maxlength" => 50),
				"helpstep" => 1
			);
			$emailheadersformdata['fromemail'] = array(
				"label" => _L('From Email'),
				"value" => $_SESSION['emailheaders']['fromemail'],
				"validators" => array_merge($additionalvalidators, array(
					array("ValLength","max" => 255),
					array("ValEmail", "domain" => getSystemSetting('emaildomain'))
				)),
				"control" => array("TextField","size" => 30, "maxlength" => 255),
				"helpstep" => 1
			);

			$destinationlayoutforms[] = new FormSplitter("emailheaders", ucfirst($type), $messagegroup->hasMessage($type) ? "img/icons/accept.gif" : "img/icons/diagona/16/160.gif", "horizontalsplit", array(), array(
				array("title" => "", "formdata" => $emailheadersformdata),
				new FormTabber("", "", null, "horizontaltabs", $subtypelayoutforms)
			));
		}
	} else if (count($subtypelayoutforms) == 1) { // Phone, Sms.
		if ($type == 'sms')
			$subtypelayoutforms[0]->title = 'SMS';
		else
			$subtypelayoutforms[0]->title = ucfirst($type);
		$destinationlayoutforms[] = $subtypelayoutforms[0];
	}
}

$destinationlayoutforms[] = makeSummaryTab($destinations, $customerlanguages, Language::getDefaultLanguageCode(), $messagegroup);

//////////////////////////////////////////////////////////
// Finalize the formsplitter.
//////////////////////////////////////////////////////////
$buttons = array(icon_button(_L("Done"),"tick", "form_submit_all(null, 'done', $('formswitchercontainer'));", null), icon_button(_L("Cancel"),"cross",null,"messages.php"));

$messagegroupsplitter = new FormSplitter("messagegroupbasics", "", null, "horizontalsplit", $buttons, array(
	array("title" => "", "formdata" => array(
		'name' => array(
			"label" => _L('Message Name'),
			// If the user hasn't changed the message group's default name, then just show blank so that the user is forced to make a better one.
			"value" => $messagegroup->name == $defaultmessagegroupname ? '' : $messagegroup->name,
			"validators" => array(
				array("ValDuplicateNameCheck", "type" => "messagegroup"),
				array("ValRequired"),
				array("ValLength","max" => 50)
			),
			"control" => array("TextField","size" => 30, "maxlength" => 50),
			"helpstep" => 1
		),
		'defaultlanguagecode' => array(
			"label" => _L('Default Language'),
			"value" => $messagegroup->defaultlanguagecode,
			"validators" => array(
				array("ValRequired"),
				array("ValInArray","values" => array_keys($customerlanguages)),
				array("ValDefaultLanguageCode")
			),
			// NOTE: It is not necessary to capitalize the language names in $customerlanguages because it should already be so in the database.
			"control" => array("SelectMenu","values" => $customerlanguages),
			"helpstep" => 1
		)
	)),
	new FormTabber("destinationstabber", "", null, "horizontaltabs", $destinationlayoutforms)
));

///////////////////////////////////////////////////////////////////////////////
// Ajax
///////////////////////////////////////////////////////////////////////////////
$messagegroupsplitter->handleRequest();

///////////////////////////////////////////////////////////////////////////////
// Submit
///////////////////////////////////////////////////////////////////////////////
if ($button = $messagegroupsplitter->getSubmit()) {
	$form = $messagegroupsplitter->getSubmittedForm();

	if ($form) {
		$ajax = $form->isAjaxSubmit();
		if (!$form->checkForDataChange() && $form->validate() === false) {
			$postdata = $form->getData();

			switch($button) {
				case 'tab':
				case 'done': {
					QuickQuery('BEGIN');

					if ($form->name == 'messagegroupbasics') {
						$messagegroup->name = trim($postdata['name']);
						$messagegroup->defaultlanguagecode = $postdata['defaultlanguagecode'];
					} else if ($form->name == 'emailheaders') {
						$_SESSION['emailheaders']['subject'] = trim($postdata['subject']);
						$_SESSION['emailheaders']['fromname'] = trim($postdata['fromname']);
						$_SESSION['emailheaders']['fromemail'] = trim($postdata['fromemail']);

						foreach ($messagegroup->getMessages() as $message) {
							if ($message->type == 'email') {
								$emaildata = $_SESSION['emailheaders'];
								if ($message->subtype == 'plain') {
									$message->readHeaders();
									$emaildata['overrideplaintext'] = $message->overrideplaintext;
								}
								$message->data = makeUrlDataString($emaildata);
								$message->update();
							}
						}
					} else if ($form->name != 'summary') {
						list($formdestinationtype, $formdestinationsubtype, $formdestinationlanguagecode) = explode('-', $form->name);

						$destination = isset($destinations[$formdestinationtype]) ? $destinations[$formdestinationtype] : null;

						if (in_array($formdestinationsubtype, $destination['subtypes']) && ($formdestinationlanguagecode == 'autotranslator' || isset($destination['languages'][$formdestinationlanguagecode]))) {
							$messagegroup->permanent = $postdata['autoexpire'] + 0;
							$messagegroup->deleted = 0;
							$messagegroup->modified = makeDateTime(time());

							// Update audio files' permanent flag; the user does not have to be editing a phone message to change the permanent flag.
							QuickUpdate('update audiofile set permanent=? where messagegroupid=?', false, array($messagegroup->permanent, $messagegroup->id));

							if ($formdestinationtype == 'phone') {
								$messagegroup->preferredgender = $postdata['preferredgender'];
								$messagegroup->stuffHeaders();
								foreach ($messagegroup->getMessages() as $message) {
									if ($message->type == 'phone') {
										$message->updatePreferredVoice($messagegroup->preferredgender);
									}
								}
							} else if ($formdestinationtype == 'email') {
								// Email Attachments.
								if (!is_array($emailattachments = json_decode($postdata["attachments"],true)))
									$emailattachments = array();
								$_SESSION['emailattachments'] = $emailattachments;

								// First delete all message attachments for this messagegroup, then create new ones.
								QuickUpdate("delete a from messageattachment a join message m on a.messageid = m.id where m.messagegroupid=?",false,array($messagegroup->id));
								foreach ($messagegroup->getMessages() as $message) {
									if ($message->type == 'email') {
										$message->createMessageAttachments($emailattachments);
									}
								}

								// Email Data.
								$emaildata = $_SESSION['emailheaders'];
								
								if ($formdestinationsubtype == 'plain') {
									if ($formdestinationlanguagecode == 'autotranslator') {
										$emaildata['overrideplaintext'] = 1;
									} else {
										$emaildata['overrideplaintext'] = $postdata['overrideplaintext'] === "true" ? 1 : 0;
									}
								}
								
								$emaildatastring = makeUrlDataString($emaildata);
							}

							if ($formdestinationlanguagecode == 'autotranslator') {
								$autotranslatorlanguages = array(); // [$languagecode] = $translationlanguagename
								$trimmedautotranslatorsourcetext = trim($postdata['sourcemessagebody']);
								if (!empty($trimmedautotranslatorsourcetext)) {
									$_SESSION['autotranslatesourcetext']["{$formdestinationtype}{$formdestinationsubtype}"] = $trimmedautotranslatorsourcetext;
									// Determine the set of languages to autotranslate so that we can make a batch translation call.
									foreach ($destination['languages'] as $languagecode => $languagename) {
										if (($formdestinationtype == 'phone' && !isset($customerphonetranslationlanguages[$languagecode])) || ($formdestinationtype == 'email' && !isset($customeremailtranslationlanguages[$languagecode])))
											continue;

										if (isset($postdata["{$languagecode}-translationitem"])) {
											$translationitemdata = json_decode($postdata["{$languagecode}-translationitem"]);

											// Add this language code into $autotranslatortranslations so that it can be batched for translation.
											if ($translationitemdata->enabled)
												$autotranslatorlanguages[$languagecode] = $translationlanguages[$languagecode];
										}
									}
									
									if (!empty($autotranslatorlanguages)) {
										// Batch translation.
										$sourcemessagetext = $_SESSION['autotranslatesourcetext']["{$formdestinationtype}{$formdestinationsubtype}"];
										
										if ($autotranslatortranslations = translate_fromenglish(makeTranslatableString($sourcemessagetext), array_keys($autotranslatorlanguages))) {
											// NOTE: Reuse the same set of message parts for each language's source message; Message::recreateParts() calls DBMappedObject::create(), which will result in the message parts getting new IDs.
											$sourcemessageparts = false;
											if ($formdestinationtype == 'email' && $formdestinationsubtype == 'html')
												$plainsourcemessageparts = false;
											
											// Increment an index because translate_fromenglish() does not return an associative array.
											$autotranslationlanguageindex = 0;
											
											foreach ($autotranslatorlanguages as $languagecode => $translationlanguagename) {
												// Delete any existing messages of the same type-subtype-languagecode that are not relevent for autotranslate.
												QuickUpdate('update message set deleted=1 where autotranslate not in ("source", "translated") and messagegroupid=? and type=? and subtype=? and languagecode=?', false, array($messagegroup->id, $formdestinationtype, $formdestinationsubtype, $languagecode));
												
												if ($formdestinationtype == 'email' && $formdestinationsubtype == 'html') {
													// Use this flag to determine whether or not to generate a plain text message.
													$plaintextoverriden = false;
													
													// Delete any plain email messages that do not have overrideplaintext and are not relevent for autotranslate.
													foreach ($messagegroup->getMessages() as $message) {
														if ($message->type == 'email' &&
															$message->subtype == 'plain' &&
															$message->languagecode == $languagecode &&
															!in_array($message->autotranslate, array('source', 'translated'))
														) {
															$message->readHeaders();
															
															if ($message->overrideplaintext) {
																$plaintextoverriden = true;
																break;
															} else {
																$message->deleted = 1;
																$message->update();
															}
														}
													}
												}
												
												if (!($sourcemessage = $messagegroup->getMessage($formdestinationtype, $formdestinationsubtype, $languagecode, 'source'))) {
													$sourcemessage = new Message();
													$sourcemessage->userid = $USER->id;
													$sourcemessage->messagegroupid = $messagegroup->id;
													$sourcemessage->name = $messagegroup->name;
													$sourcemessage->type = $formdestinationtype;
													$sourcemessage->subtype = $formdestinationsubtype;
													$sourcemessage->languagecode = $languagecode;
													$sourcemessage->autotranslate = 'source';
													$sourcemessage->modifydate = makeDateTime(time());
													$sourcemessage->deleted = 0;
													if ($formdestinationtype == 'email') {
														$sourcemessage->data = $emaildatastring;
														$sourcemessage->description = SmartTruncate(($formdestinationsubtype == 'html' ? 'HTML' : ucfirst($formdestinationsubtype)) . ' ' . Language::getName($languagecode), 50);
													} else {
														$sourcemessage->description = SmartTruncate(Language::getName($languagecode), 50);
													}
													$sourcemessage->create();
													
													if ($formdestinationtype == 'email') {
														$sourcemessage->createMessageAttachments($emailattachments);
													}
												} else {
													$sourcemessage->data = $emaildatastring;
													$sourcemessage->readHeaders();
													$sourcemessage->update();
												}

												if ($sourcemessageparts === false)
													$sourcemessageparts = $sourcemessage->parse($sourcemessagetext);
												
												$sourcemessage->recreateParts(null, $sourcemessageparts, $formdestinationtype == 'phone' ? $messagegroup->preferredgender : null);
												
												if (isset($plaintextoverriden) && !$plaintextoverriden) {
													if ($plainsourcemessage = $messagegroup->getMessage($formdestinationtype, 'plain', $languagecode, 'source')) {
														$plainsourcemessage->readHeaders();
													} else {
														$plainsourcemessage = new Message();
														$plainsourcemessage->userid = $USER->id;
														$plainsourcemessage->messagegroupid = $messagegroup->id;
														$plainsourcemessage->name = $messagegroup->name;
														$plainsourcemessage->type = 'email';
														$plainsourcemessage->subtype = 'plain';
														$plainsourcemessage->languagecode = $languagecode;
														$plainsourcemessage->autotranslate = 'source';
														$plainsourcemessage->modifydate = makeDateTime(time());
														$plainsourcemessage->data = $emaildatastring;
														$plainsourcemessage->description = SmartTruncate('Plain ' . Language::getName($languagecode), 50);
														$plainsourcemessage->deleted = 0;
														$plainsourcemessage->create();
														$plainsourcemessage->createMessageAttachments($emailattachments);
													}
													if (!$plainsourcemessage->overrideplaintext) {
														if ($plainsourcemessageparts === false)
															$plainsourcemessageparts = $plainsourcemessage->parse(html_to_plain($sourcemessagetext));
														
														$plainsourcemessage->recreateParts(null, $plainsourcemessageparts, null);
													}
												}
												
												if (is_array($autotranslatortranslations)) {
													$translationtext = $autotranslatortranslations[$autotranslationlanguageindex]->responseData->translatedText;
												} else {
													$translationtext = $autotranslatortranslations->translatedText;
												}
												
												if (!($translatedmessage = $messagegroup->getMessage($formdestinationtype, $formdestinationsubtype, $languagecode, 'translated'))) {
													$translatedmessage = new Message();
													$translatedmessage->userid = $USER->id;
													$translatedmessage->messagegroupid = $messagegroup->id;
													$translatedmessage->name = $messagegroup->name;
													$translatedmessage->type = $formdestinationtype;
													$translatedmessage->subtype = $formdestinationsubtype;
													$translatedmessage->languagecode = $languagecode;
													$translatedmessage->autotranslate = 'translated';
													$translatedmessage->modifydate = makeDateTime(time());
													$translatedmessage->deleted = 0;
													if ($formdestinationtype == 'email') {
														$translatedmessage->data = $emaildatastring;
														$translatedmessage->description = SmartTruncate(($formdestinationsubtype == 'html' ? 'HTML' : ucfirst($formdestinationsubtype)) . ' ' . Language::getName($languagecode), 50);
													} else {
														$translatedmessage->description = SmartTruncate(Language::getName($languagecode), 50);
													}
													$translatedmessage->create();
													
													if ($formdestinationtype == 'email')
														$translatedmessage->createMessageAttachments($emailattachments);
												} else {
													$translatedmessage->data = $emaildatastring;
													$translatedmessage->readHeaders();
													$translatedmessage->update();
												}
												
												$translatedmessage->recreateParts($translationtext, null, $formdestinationtype == 'phone' ? $messagegroup->preferredgender : null);
												
												if (isset($plaintextoverriden) && !$plaintextoverriden) {
													if ($plaintranslatedmessage = $messagegroup->getMessage('email', 'plain', $languagecode, 'translated')) {
														$plaintranslatedmessage->readHeaders();
													} else {
														$plaintranslatedmessage = new Message();
														$plaintranslatedmessage->userid = $USER->id;
														$plaintranslatedmessage->messagegroupid = $messagegroup->id;
														$plaintranslatedmessage->name = $messagegroup->name;
														$plaintranslatedmessage->type = 'email';
														$plaintranslatedmessage->subtype = 'plain';
														$plaintranslatedmessage->languagecode = $languagecode;
														$plaintranslatedmessage->autotranslate = 'translated';
														$plaintranslatedmessage->modifydate = makeDateTime(time());
														$plaintranslatedmessage->data = $emaildatastring;
														$plaintranslatedmessage->description = SmartTruncate('Plain ' . Language::getName($languagecode), 50);
														$plaintranslatedmessage->deleted = 0;
														$plaintranslatedmessage->create();
														$plaintranslatedmessage->createMessageAttachments($emailattachments);
													}
													
													if (!$plaintranslatedmessage->overrideplaintext) {
														$plaintranslatedmessage->recreateParts(html_to_plain($translationtext), null, null);
													}
												}
												
												$autotranslationlanguageindex++;
											}
										} else {
											unset($autotranslatortranslations);
										}
									}
								}
							} else {
								// If overrideplaintext is false, just soft-delete existing messages without creating new ones.
								// Otherwise, either update existing messages or soft-delete them, depending on the user inputs for the sourcemessagebody/translationitem/messagebody.
								if (isset($emaildata) && isset($emaildata['overrideplaintext']) && !$emaildata['overrideplaintext']) {
									$newmessagesneeded = $messagesneeded = array();
								} else if (count($destination['languages']) > 1 &&
									(($formdestinationtype == 'phone' && isset($customerphonetranslationlanguages[$formdestinationlanguagecode])) ||
										($formdestinationtype == 'email' && isset($customeremailtranslationlanguages[$formdestinationlanguagecode]))
									)
								) {
									if (isset($postdata['translationitem'])) {
										$translationitemdata = json_decode($postdata['translationitem']);
									}

									$newmessagesneeded = $messagesneeded = array(
										'source' => isset($translationitemdata) && $translationitemdata->enabled,
										'translated' => isset($translationitemdata) && $translationitemdata->enabled && !$translationitemdata->override,
										'overridden' => isset($translationitemdata) && $translationitemdata->enabled && $translationitemdata->override,
										'none' => !isset($translationitemdata) || !$translationitemdata->enabled
									);
								} else {
									$newmessagesneeded = $messagesneeded = array('none' => true);
								}
								
								$trimmedsourcetext = isset($translationitemdata->englishText) ? trim($translationitemdata->englishText) : '';
								if (!empty($messagesneeded['translated']) && !empty($trimmedsourcetext)) {
									if (!$translation = translate_fromenglish(makeTranslatableString($trimmedsourcetext), array($formdestinationlanguagecode))) {
										unset($translation);
									}
								}

								if (isset($postdata['nonemessagebody'])) {
									$nonemessagebody = trim($postdata['nonemessagebody']);
								} else if (isset($translationitemdata)) {
									$nonemessagebody = trim($translationitemdata->text);
								} else {
									$nonemessagebody = '';
								}
								
								$messagebodies = array(
									'source' => !empty($messagesneeded['source']) ? $trimmedsourcetext : '',
									'translated' => isset($translation) ? $translation->translatedText : '',
									'overridden' => !empty($messagesneeded['overridden']) ? $translationitemdata->text : '',
									'none' => !empty($messagesneeded['none']) ? $nonemessagebody : ''
								);
								
								// Use this flag to indicate whether or not to generate/update a plain-text message.
								$plaintextoverriden = false;
								
								// Delete any existing messages that are no longer relevent, update relevent existing messages, and figure out what new messages are needed.
								foreach ($messagegroup->getMessages() as $existingmessage) {
									if ($existingmessage->type != $formdestinationtype ||
										$existingmessage->languagecode != $formdestinationlanguagecode) {
										continue;
									}
									
									if ($formdestinationsubtype == 'html' && $existingmessage->subtype == 'plain') {
										$existingmessage->readHeaders();
										if ($existingmessage->overrideplaintext) {
											$plaintextoverriden = true;
											continue;
										}
									} else if ($existingmessage->subtype != $formdestinationsubtype) {
										continue;
									}
									
									$newmessagesneeded[$existingmessage->autotranslate] = false;

									if (!isset($messagesneeded[$existingmessage->autotranslate]) || !$messagesneeded[$existingmessage->autotranslate] || $messagebodies[$existingmessage->autotranslate] == "") {
										$existingmessage->readHeaders();
										
										if ($formdestinationtype == 'email' && $formdestinationsubtype == 'plain') {
											if (!$existingmessage->overrideplaintext) {
												continue;
											} else {
												// Indicate that we should revert to using generated plain-text message from existing html messages because previously overridden plain-text messages are being deleted.
												$generateplaintext = true;
											}
										}
										
										$existingmessage->deleted = 1;
										$existingmessage->update();
										
										// NOTE: Don't bother updating parts and attachments for deleted messages.
										continue;
									}
									
									if ($formdestinationtype == 'email' && $formdestinationsubtype == 'html' && $existingmessage->subtype == 'plain') {
										// Update existing plain messages that are not overridden.
										$existingmessage->recreateParts(html_to_plain($messagebodies[$existingmessage->autotranslate]), null, null);
									} else {
										if ($formdestinationtype == 'email' && $formdestinationsubtype == 'plain') {
											$existingmessage->data = $emaildatastring;
											$existingmessage->readHeaders();
											$existingmessage->update();
										}
										
										$existingmessage->recreateParts($messagebodies[$existingmessage->autotranslate], null, $formdestinationtype == 'phone' ? $messagegroup->preferredgender : null);
									}
								}
								
								if ($formdestinationsubtype == 'html' && !$plaintextoverriden) {
									// Create any missing plain-text messages for existing html messages.
									foreach ($messagesneeded as $autotranslate => $needed) {
										if (!$needed ||
											$messagebodies[$autotranslate] == '' ||
											$newmessagesneeded[$autotranslate]
										) {
											continue;
										}
										
										if (!$messagegroup->getMessage('email', 'plain', $formdestinationlanguagecode, $autotranslate)) {
											$newplainmessage = new Message();
											$newplainmessage->userid = $USER->id;
											$newplainmessage->messagegroupid = $messagegroup->id;
											$newplainmessage->name = $messagegroup->name;
											$newplainmessage->type = 'email';
											$newplainmessage->subtype = 'plain';
											$newplainmessage->languagecode = $formdestinationlanguagecode;
											$newplainmessage->autotranslate = $autotranslate;
											$newplainmessage->modifydate = makeDateTime(time());
											$newplainmessage->deleted = 0;
											$newplainmessage->data = $emaildatastring;
											$newplainmessage->description = SmartTruncate('Plain ' . Language::getName($formdestinationlanguagecode), 50);
											$newplainmessage->create();
											$newplainmessage->createMessageAttachments($emailattachments);
											$newplainmessage->recreateParts(html_to_plain($messagebodies[$autotranslate]), null, null);
										}
									}
								}
								
								foreach ($newmessagesneeded as $autotranslate => $needed) {
									if (!$needed || $messagebodies[$autotranslate] == "")
										continue;
									$newmessage = new Message();
									$newmessage->userid = $USER->id;
									$newmessage->messagegroupid = $messagegroup->id;
									$newmessage->name = $messagegroup->name;
									$newmessage->type = $formdestinationtype;
									$newmessage->subtype = $formdestinationsubtype;
									$newmessage->languagecode = $formdestinationlanguagecode;
									$newmessage->autotranslate = $autotranslate;
									$newmessage->modifydate = makeDateTime(time());
									$newmessage->deleted = 0;
									if ($formdestinationtype == 'email') {
										$newmessage->data = $emaildatastring;
										$newmessage->description = SmartTruncate(($formdestinationsubtype == 'html' ? 'HTML' : ucfirst($formdestinationsubtype)) . ' ' . Language::getName($formdestinationlanguagecode), 50);
										
										// Create any new plain messages that are needed for these new html messages.
										if ((!$plaintextoverriden && $formdestinationsubtype == 'html') &&
											!$messagegroup->hasMessage('email', 'plain', $formdestinationlanguagecode, $autotranslate)
										) {
											$newplainmessage = new Message();
											$newplainmessage->userid = $USER->id;
											$newplainmessage->messagegroupid = $messagegroup->id;
											$newplainmessage->name = $messagegroup->name;
											$newplainmessage->type = 'email';
											$newplainmessage->subtype = 'plain';
											$newplainmessage->languagecode = $formdestinationlanguagecode;
											$newplainmessage->autotranslate = $autotranslate;
											$newplainmessage->modifydate = makeDateTime(time());
											$newplainmessage->deleted = 0;
											$newplainmessage->data = $emaildatastring;
											$newplainmessage->description = SmartTruncate('Plain ' . Language::getName($formdestinationlanguagecode), 50);
											$newplainmessage->create();
											$newplainmessage->createMessageAttachments($emailattachments);
											$newplainmessage->recreateParts(html_to_plain($messagebodies[$autotranslate]), null, null);
										}
									} else if ($formdestinationtype != 'sms') {
										$newmessage->description = SmartTruncate(Language::getName($formdestinationlanguagecode), 50);
									} else {
										$newmessage->description = '';
									}
									$newmessage->create();
									
									if ($formdestinationtype == 'email')
										$newmessage->createMessageAttachments($emailattachments);

									$newmessage->recreateParts($messagebodies[$autotranslate], null, $formdestinationtype == 'phone' ? $messagegroup->preferredgender : null);
								}
								
								// Previously overridden plain-text messages have been deleted, so we need to generate new ones from any existing html messages. This time, we do not want to override plain-text.
								if (isset($generateplaintext) && $generateplaintext) {
									foreach ($messagegroup->getMessages() as $message) {
										if ($message->type != 'email' ||
											$message->subtype != 'html' ||
											$message->languagecode != $formdestinationlanguagecode
										) {
											continue;
										}
										
										$newplainmessage = new Message();
										$newplainmessage->userid = $USER->id;
										$newplainmessage->messagegroupid = $messagegroup->id;
										$newplainmessage->name = $messagegroup->name;
										$newplainmessage->type = 'email';
										$newplainmessage->subtype = 'plain';
										$newplainmessage->languagecode = $formdestinationlanguagecode;
										$newplainmessage->autotranslate = $message->autotranslate;
										$newplainmessage->modifydate = makeDateTime(time());
										$newplainmessage->deleted = 0;
										$newplainmessage->data = $emaildatastring;
										$newplainmessage->description = SmartTruncate('Plain ' . Language::getName($formdestinationlanguagecode), 50);
										$newplainmessage->create();
										$newplainmessage->createMessageAttachments($emailattachments);
										$newplainmessage->recreateParts(html_to_plain($messagegroup->getMessageText($message->type, $message->subtype, $message->languagecode, $message->autotranslate)), null, null);
									}
								}
							}
						}
					}

					$messagegroup->update();

					QuickQuery('COMMIT');

					if ($ajax && $button == 'done')
						$form->sendTo('messages.php');
					else if ($ajax && $button == 'tab')
						$form->sendTo('');
				} break;
			}
		}
	}
}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$PAGE = "notifications:messages";
$TITLE = _L('Message Editor');

include_once('nav.inc.php');
?>

<script src="script/ckeditor/ckeditor_basic.js" type="text/javascript"></script>
<script src="script/htmleditor.js" type="text/javascript"></script>
<script src="script/accordion.js" type="text/javascript"></script>
<script src="script/messagegroup.js.php" type="text/javascript"></script>
<script src="script/audiolibrarywidget.js.php" type="text/javascript"></script>
<script type="text/javascript">
	<?php Validator::load_validators(array("ValOverridePlainText", "ValDefaultLanguageCode", "ValTranslationItem", "ValDuplicateNameCheck", "ValCallMeMessage", "ValMessageBody", "ValEmailMessageBody", "ValLength", "ValRegExp", "ValEmailAttach")); ?>
</script>
<style type='text/css'>
#messageemptyspan {
	padding-top: 6px;
	display: block;
	clear: both;
	color: rgb(130,130,130);
	visibility: hidden;
}
</style>
<?php
startWindow(_L('Message Editor'));

$firstdestinationtype = reset(array_keys($destinations));
$firstdestinationsubtype = reset($destinations[$firstdestinationtype]['subtypes']);
$defaultsections = array("{$firstdestinationtype}-{$firstdestinationsubtype}", "{$firstdestinationtype}-{$firstdestinationsubtype}-" . Language::getDefaultLanguageCode());
if ($firstdestinationtype == 'email')
	$defaultsections[] = "emailheaders";
echo '<div id="messagegroupformcontainer">' . $messagegroupsplitter->render($defaultsections) . '</div>';

?>

<script type="text/javascript">
	(function() {
		// Use an object to store state information.
		var state = {
			'currentdestinationtype': '<?=$firstdestinationtype?>',
			'currentsubtype': '<?=$firstdestinationsubtype?>',
			'currentlanguagecode': '<?=Language::getDefaultLanguageCode()?>',
			'messagegroupsummary': <?=json_encode(MessageGroup::getSummary($_SESSION['messagegroupid']))?>
		};

		var formswitchercontainer = $('messagegroupformcontainer');
		form_init_splitter(formswitchercontainer, <?=json_encode($defaultsections)?>);

		var confirmAutotranslator = function(clickevent, tabevent, state) {
			saveHtmlEditorContent(); // If this is email html, the autotranslator also uses the html editor for its source message body.
			
			var sourcetextarea = $(state.currentdestinationtype + '-' + state.currentsubtype + '-autotranslator_sourcemessagebody');
			var sourcetext = sourcetextarea.value;
			if (sourcetext.strip() == '') {
				if (clickevent) {
					alert('<?= addslashes(_L("Please enter a message to translate.")) ?>');
					return null;
				} else {
					return {};
				}
			}

			var translationcheckboxes = $$('.TranslationItemCheckboxTD');
			// Loop over list of languages to translate (languages that the user has checked), adding to the translationlanguagecodes array.
			var translationlanguagecodes = []; // List of language names to be sent via ajax to translate.php.

			var willoverwrite = false; // Indicates if any messages will get overwritten.
			var messagegroupsummary = state.messagegroupsummary;
			for (var i = 0, count = translationcheckboxes.length; i < count; i++) {
				var checkbox = translationcheckboxes[i].down('input[type="checkbox"]');
				if (checkbox.checked) {
					var checkboxidpieces = checkbox.identify().split('-');
					var languagecode = (checkboxidpieces[2]).split('_').pop(); // checkboxidpieces[2]: "autotranslator_{$languagecode}"
					translationlanguagecodes.push(languagecode);

					// Detect if any message will get overwritten.
					for (var j = 0, jcount = messagegroupsummary.length; j < jcount; j++) {
						var messageinfo = messagegroupsummary[j];
						if (messageinfo.type == state.currentdestinationtype && messageinfo.subtype == state.currentsubtype && messageinfo.languagecode == languagecode) {
							willoverwrite = true;
							break;
						}
					}
				}
			}

			if (translationlanguagecodes.length < 1) {
				if (clickevent) {
					alert('<?=addslashes(_L("Please select a language to translate.")) ?>');
					return null;
				} else {
					return {};
				}
			}

			if (willoverwrite && !confirm('<?= _L("Some messages will get overwritten, do you want to continue? If not, please clear the translation message.") ?>')) { // WORDSMITH: Better message.
				return null;
			}

			return {'sourcetext': sourcetext, 'translationlanguagecodes': translationlanguagecodes};
		};
		var updateTranslationItem = function(form, languagecode, sourcetext, translatedtext) {
			var formitemname = form.name + '_' + languagecode + '-translationitem';
			$(formitemname+'text').value = translatedtext;
			$(formitemname+"englishText").value = sourcetext;
			$(formitemname+'textdiv').update(translatedtext.replace(/<</g, "&lt;&lt;").replace(/>>/g, "&gt;&gt;"));
			setTranslationValue(formitemname);
		};

		formswitchercontainer.observe('FormSplitter:BeforeSubmitAll', function(event, state) {
			if ($('autotranslatorrefreshtranslationbutton') && !confirmAutotranslator(null, event, state))
				event.stop();
		}.bindAsEventListener(formswitchercontainer, state));

		formswitchercontainer.observe('FormSplitter:BeforeTabLoad',
			messagegroupHandleBeforeTabLoad.bindAsEventListener(formswitchercontainer, state, '<?=Language::getDefaultLanguageCode()?>')
		);

		var autotranslatorupdator = function (autotranslatorbutton, state) {
			autotranslatorbutton.stopObserving('click');
			autotranslatorbutton.observe('click', function(event, state) {
				var autotranslateobject = confirmAutotranslator(event, null, state);
				if (!autotranslateobject)
					return;

				var sourcetext = autotranslateobject.sourcetext;
				var translationlanguagecodes = autotranslateobject.translationlanguagecodes;

				// Show ajax loaders for the translating languages, and clear the retranslation text.
				for (var i = 0, count = translationlanguagecodes.length; i < count; i++) {
					var formitemname = this.name + '_' + translationlanguagecodes[i] + '-translationitem';

					$(formitemname + 'textdiv').update('<img src=\"img/ajax-loader.gif\" />');
					$(formitemname + 'retranslationtext').update();
				}

				var errortext = '<?=addslashes(_L('Sorry, an error occurred during translation. Please try again.'))?>';
				new Ajax.Request('translate.php', {
					'method':'post',
					'parameters': {'english': makeTranslatableString(sourcetext), 'languages': translationlanguagecodes.join(';')},
					'onSuccess': function(transport, translationlanguagecodes, errortext) {
						var data = transport.responseJSON;

						if (!data || !data.responseData || !data.responseStatus || data.responseStatus != 200 ||
							(translationlanguagecodes.length > 1 && translationlanguagecodes.length != data.responseData.length)) {
							alert(errortext);
							return;
						}

						var dataResponseData = data.responseData;
						
						var count = translationlanguagecodes.length;

						// If there is a single language, the response is not an array.
						if (count == 1) {
							updateTranslationItem(this, languagecode, sourcetext, dataResponseData.translatedText);
							return;
						}

						// Use a flag to indicate if any language has an error;
						// we want to wait until after the for-loop to show an alert() otherwise the user could get multiple alerts.
						var haserror = false;

						for (var i = 0; i < count; i++) {
							var languagecode = translationlanguagecodes[i];

							var response = dataResponseData[i];
							var responseData = response.responseData;

							// If there is an error for a particular language, clear its contents, and flag haserror.
							if (response.responseStatus != 200 || !responseData) {
								updateTranslationItem(this, languagecode, '', '');
								haserror = true;
								continue;
							} else {
								updateTranslationItem(this, languagecode, sourcetext, responseData.translatedText);
							}
						}

						if (haserror)
							alert(errortext);
					}.bindAsEventListener(this, translationlanguagecodes, errortext),
					
					'onFailure': function(transport, errortext) {
						alert(errortext);
					}.bindAsEventListener(this, errortext)
				});
			}.bindAsEventListener(autotranslatorbutton.up('form'), state));
		};
		
		// When a tab is loaded, update the status icon of the previous tab.
		formswitchercontainer.observe('FormSplitter:TabLoaded',
			messagegroupHandleTabLoaded.bindAsEventListener(formswitchercontainer, state, '<?=$_SESSION['messagegroupid']?>', '<?=Language::getDefaultLanguageCode()?>', autotranslatorupdator, false)
		);
		
		messagegroupStyleLayouts();
	})();

</script>

<?php
endWindow();
include_once('navbottom.inc.php');
?>
