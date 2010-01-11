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
require_once('messagegroup.inc.php');

///////////////////////////////////////////////////////////////////////////////
// Authorization:
///////////////////////////////////////////////////////////////////////////////
$cansendphone = $USER->authorize('sendphone');
$cansendemail = $USER->authorize('sendemail');
$cansendsms = getSystemSetting('_hassms', false) && $USER->authorize('sendsms');
$cansendmultilingual = $USER->authorize('sendmulti');

// Only kick the user out if he does not have permission to create any message at all (neither phone, email, nor sms).
if (!$cansendphone && !$cansendemail && !$cansendsms) {
	unset($_SESSION['messagegroupid']);
	redirect('unauthorized.php');
}

///////////////////////////////////////////////////////////////////////////////
// Request processing:
///////////////////////////////////////////////////////////////////////////////
// Only unset the session variable if navigating to messagegroup.php via a link, which we can detect when there is only ?messagegroupid and no postdata.
// A form's ajax call keeps the url components intact so that ?messagegroupid=new will remain even after we've created a messagegroup.
// If we do not make this strict check, the session variable will get unset and a new messagegroup would be created each time a form makes an ajax call.
if (isset($_GET['id']) && empty($_POST) && count($_GET) == 1) {
	unset($_SESSION['messagegroupid']);
	if ($existingmessagegroupid = $_GET['id'] + 0) {
		if (userOwns('messagegroup', $existingmessagegroupid))
			$_SESSION['messagegroupid'] = $existingmessagegroupid;
		else
			redirect('unauthorized.php');
	} else { // URL: ?messagegroupid=new
		// Continue below, where a new messagegroup is created because the session variable is not set.
	}
}



///////////////////////////////////////////////////////////////////////////////
// Defaults.
///////////////////////////////////////////////////////////////////////////////
$readonly = false;

$defaultpreferredgender = 'Female'; // TODO: Maybe this should be lowercase..
$defaultautotranslate = 'none';
$systemdefaultlanguagecode = 'en';
$defaultpermanent = 0;
$defaultmessagegroupname = 'Please enter a name';
$defaultemailheaders = array(
	'subject' => '',
	'fromname' => $USER->firstname . " " . $USER->lastname,
	'fromemail' => array_shift(explode(";", $USER->email))
);

///////////////////////////////////////////////////////////////////////////////
// Data Gathering:
///////////////////////////////////////////////////////////////////////////////
if (isset($_SESSION['messagegroupid'])) {
	$existingmessagegroup = new MessageGroup($_SESSION['messagegroupid']);
} else {
	// For a new messagegroup, it is first created in the database as deleted
	// in case the user does not submit the form. Once the form is submitted, the
	// messagegroup is set as not deleted; the permanent flag is toggled by the user.
	$newmessagegroup = new MessageGroup();
	$newmessagegroup->userid = $USER->id;
	$newmessagegroup->name = $defaultmessagegroupname;
	$newmessagegroup->defaultlanguagecode = $systemdefaultlanguagecode;
	$newmessagegroup->description = '';
	$newmessagegroup->modified =  makeDateTime(time());
	$newmessagegroup->deleted = 1; // Set to deleted in case the user does not submit the form.
	$newmessagegroup->permanent = $defaultpermanent;

	if (!$readonly) {
		if ($newmessagegroup->create()) {
			$_SESSION['messagegroupid'] = $newmessagegroup->id;
		} else {
			redirect('unauthorized.php'); // TODO: Something went wrong.. redirect somewhere?
		}
	}
}

$customerlanguages = $cansendmultilingual ? QuickQueryList("select code, name from language", true) : QuickQueryList("select code, name from language where code=?", true, false, array($systemdefaultlanguagecode));
$ttslanguages = $cansendmultilingual ? Voice::getTTSLanguageMap() : array();
unset($ttslanguages[$systemdefaultlanguagecode]);
if ($cansendmultilingual)
	$allowtranslation = isset($SETTINGS['translation']['disableAutoTranslate']) ? (!$SETTINGS['translation']['disableAutoTranslate']) : true;
else
	$allowtranslation = false;
// NOTE: The customer may have a custom name for a particular language code, different from that of Google's.
$translationlanguages = $allowtranslation ? getTranslationLanguages() : array();
unset($translationlanguages[$systemdefaultlanguagecode]);
$customeremailtranslationlanguages = array_intersect_key($customerlanguages, $translationlanguages);
$customerphonetranslationlanguages = array_intersect_key($customerlanguages, $translationlanguages, $ttslanguages);

$datafields = FieldMap::getAuthorizedMapNames();
$preferredgender = /*TODO, BUGGY:isset($existingmessagegroup) ? $existingmessagegroup->getGlobalPreferredGender($defaultpreferredgender) :*/ $defaultpreferredgender;
$permanent = isset($existingmessagegroup) ? $existingmessagegroup->permanent + 0 : $defaultpermanent;

if (!isset($existingmessagegroup))
	unset($_SESSION['emailheaders']);
if (!isset($_SESSION['emailheaders'])) {
	$_SESSION['emailheaders'] = isset($existingmessagegroup) ? $existingmessagegroup->getGlobalEmailHeaders($defaultemailheaders) : $defaultemailheaders;
}

// $emailattachments is a map indexed by contentid, containing size and name of each attachment.
if (isset($existingmessagegroup)) {
	$emailattachments = array();
	$attachments = $existingmessagegroup->getGlobalEmailAttachments();
	foreach ($attachments as $attachment) {
		$emailattachments[$attachment->contentid] = array("size" => $attachment->size, "name" => $attachment->filename);
	}
	if (empty($emailattachments)) {
		if (isset($_SESSION['emailattachments']))
			$emailattachments = $_SESSION['emailattachments'];
	}
} else {
	unset($_SESSION['emailattachments']);
	$emailattachments = array();
}

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
		'languages' => array($systemdefaultlanguagecode => $customerlanguages[$systemdefaultlanguagecode])
	);
}

///////////////////////////////////////////////////////////////////////////////
// Formdata
// TODO: If $readonly, don't use actual formitems, just use FormHtml.
///////////////////////////////////////////////////////////////////////////////
$destinationlayoutforms = array();
foreach ($destinations as $type => $destination) {

	$subtypelayoutforms = array();
	foreach ($destination['subtypes'] as $subtype) {

		$messageformsplitters = array();

		// Autotranslator.
		if (!$readonly && count($destination['languages']) > 1) {
			$autotranslatorformdata = array();

			if (!isset($_SESSION['autotranslatesourcetext']))
				$_SESSION['autotranslatesourcetext'] = isset($existingmessagegroup) ? $existingmessagegroup->getMessageText($type,$subtype,$systemdefaultlanguagecode, 'none') : '';

			if ($type == 'phone' || $type == 'email') {
				$autotranslatorformdata["header"] = makeFormHtml("<div class='MessageBodyHeader'>" . _L("Autotranslate") . "</div");
				$autotranslatorformdata["sourcemessagebody"] = makeMessageBody(false, $type, $subtype, null, 'autotranslator', $_SESSION['autotranslatesourcetext'], $datafields, $subtype == 'html', true);
				$autotranslatorformdata["refreshtranslations"] = makeFormHtml(icon_button(_L("Refresh Translations"),"tick", null, null, 'id="autotranslatorrefreshtranslationbutton"') . "<div style='margin-top:35px;clear:both'></div>");

				foreach ($destination['languages'] as $languagecode => $languagename) {
					if ($type == 'phone' && !isset($customerphonetranslationlanguages[$languagecode]))
						continue;
					else if ($type == 'email' && !isset($customeremailtranslationlanguages[$languagecode]))
						continue;

					$autotranslatorformdata["{$languagecode}-translationitem"] = makeTranslationItem(false, $type, $subtype, $languagecode, $languagename, $_SESSION['autotranslatesourcetext'], "", ucfirst($languagename), false, false, false, !(isset($existingmessagegroup) && $existingmessagegroup->hasMessage($type, $subtype, $languagecode)), '', null, true);
				}
			}

			$accordionsplitter = makeAccordionSplitter($type, $subtype, $languagecode, $permanent, $preferredgender, true, $type == 'email' ? $emailattachments : null, false);

			$messageformsplitters[] = new FormSplitter("{$type}-{$subtype}-autotranslator", _L("Autotranslate"), null, "verticalsplit", array(), array(
			array("title" => "", "formdata" => $autotranslatorformdata), // TODO: Change the wording for this title.
			$accordionsplitter));
		}

		// Individual Message (type-subtype-language).
		foreach ($destination['languages'] as $languagecode => $languagename) {
			$messageformname = "{$type}-{$subtype}-{$languagecode}";

			$messagetexts = array(
				'source' => isset($existingmessagegroup) ? $existingmessagegroup->getMessageText($type,$subtype,$languagecode, 'source') : '',
				'translated' => isset($existingmessagegroup) ? $existingmessagegroup->getMessageText($type,$subtype,$languagecode, 'translated') : '',
				'overridden' => isset($existingmessagegroup) ? $existingmessagegroup->getMessageText($type,$subtype,$languagecode, 'overridden') : '',
				'none' => isset($existingmessagegroup) ? $existingmessagegroup->getMessageText($type,$subtype,$languagecode, 'none') : ''
			);

			$formdata = array();
			if (isset($existingmessagegroup)) {
				$required = $existingmessagegroup->defaultlanguagecode == $languagecode;
			} else {
				$required = $languagecode == $systemdefaultlanguagecode;
			}
			if (($type == 'phone' && isset($customerphonetranslationlanguages[$languagecode])) || ($type == 'email' && isset($customeremailtranslationlanguages[$languagecode]))) {
				$translationenabled = empty($messagetexts['none']);
				
				// Translation formitem.
				if (!empty($messagetexts['overridden'])) {
					$messagetext = $messagetexts['overridden'];
				} else if (!empty($messagetexts['translated'])) {
					$messagetext = $messagetexts['translated'];
				} else {
					$messagetext = $messagetexts['none'];
				}
				$formdata["translationitem"] = makeTranslationItem($required, $type, $subtype, $languagecode, $languagename, $messagetexts['source'], $messagetext, _L("Enable Translation"), !empty($messagetexts['overridden']), true, false, $translationenabled, "", $datafields);

				// Javascript to detect when user enables/disables translation.
				$usehtmleditor = $subtype == 'html' ? 'true' : 'false';
				$translationitemid = $messageformname . '_translationitem';
				$sourcemessagebodyid = $translationitemid . 'englishText';
				$messagebodyid = $translationitemid . 'text';
				$translationenabledstr = $translationenabled ? 'true' : 'false';
				$overriddenstr = !empty($messagetexts['overridden']) ? 'true' : 'false';
				$formdata["toggletranslation"] = makeFormHtml("
					<script type='text/javascript'>
						if ($translationenabledstr) {
							if ($overriddenstr) {
								$$('.MessageBodyHeader').invoke('show');
								$$('.SourceMessageBodyHeader').invoke('hide');

								$('refreshtranslationbutton').hide();
							} else {
								$$('.MessageBodyHeader').invoke('show');
								$$('.SourceMessageBodyHeader').invoke('hide');
							}
						} else {

							$$('.MessageBodyHeader').invoke('show');
							$$('.SourceMessageBodyHeader').invoke('hide');

							$('refreshtranslationbutton').hide();
						}
					</script>
				");
			} else {
				if ($type == 'sms') {
					$formdata["header"] = makeFormHtml("<div class='MessageBodyHeader'>" . _L("SMS") . "</div");
					$formdata['nonemessagebody'] = array(
						"label" => _L("SMS Message"),
						"value" => $messagetexts['none'],
						"fieldhelp" => _L("Short text message that can be sent to mobile phones. These messages cannot be longer than 160 characters."),
						"validators" => array(
							array("ValLength","max"=>160),
							array("ValRegExp","pattern" => getSmsRegExp())
						),
						"control" => array("TextArea","rows"=>10,"counter"=>160),
						"renderoptions" => array("label" => false, "icon" => false, "error" => true),
						"helpstep" => 2
					);
				} else {
					$formdata["header"] = makeFormHtml("<div class='MessageBodyHeader'>" . ucfirst($languagename) . "</div");
					$formdata['nonemessagebody'] = makeMessageBody($required, $type, $subtype, $languagecode, ucfirst($languagename), $messagetexts['none'], $datafields, $subtype == 'html');
				}
			}

			$accordionsplitter = makeAccordionSplitter($type, $subtype, $languagecode, $permanent, $preferredgender, false, $type == 'email' ? $emailattachments : null, isset($formdata['translationitem']) ? true : false);

			$messageformsplitters[] = new FormSplitter($messageformname, $languagename, isset($existingmessagegroup) && $existingmessagegroup->hasMessage($type, $subtype, $languagecode) ? "img/icons/accept.gif" : "img/icons/diagona/16/160.gif", "verticalsplit", array(), array(
			array("title" => "", "formdata" => $formdata), // TODO: Change the wording for this title.
			$accordionsplitter));
		}

		if (count($destination['languages']) > 1) {
			$subtypelayoutforms[] = new FormTabber("{$type}-{$subtype}", ucfirst($subtype), isset($existingmessagegroup) && $existingmessagegroup->hasMessage($type, $subtype) ? "img/icons/accept.gif" : "img/icons/diagona/16/160.gif", "verticaltabs", $messageformsplitters);
		} else if (count($destination['languages']) == 1) {
			$messageformsplitters[0]->title = ucfirst($subtype);
			$subtypelayoutforms[] = $messageformsplitters[0];
		}
	}

	if (count($destination['subtypes']) > 1) {
		if ($type == 'email') {
			$additionalvalidators = isset($existingmessagegroup) && $existingmessagegroup->getOneEnabledMessage('email') ? array(array("ValRequired")) : array();

			$emailheadersformdata = array();
			$emailheadersformdata['subject'] = array(
				"label" => _L('Subject'),
				"value" => $_SESSION['emailheaders']['subject'],
				"validators" => array_merge($additionalvalidators, array(
					array("ValLength","min" => 3,"max" => 50)
				)),
				"control" => array("TextField","size" => 30, "maxlength" => 51),
				"helpstep" => 1
			);
			$emailheadersformdata['fromname'] = array(
				"label" => _L('From Name'),
				"value" => $_SESSION['emailheaders']['fromname'],
				"validators" => array_merge($additionalvalidators, array(
					array("ValLength","min" => 3,"max" => 50)
				)),
				"control" => array("TextField","size" => 30, "maxlength" => 51),
				"helpstep" => 1
			);
			$emailheadersformdata['fromemail'] = array(
				"label" => _L('From Email'),
				"value" => $_SESSION['emailheaders']['fromemail'],
				"validators" => array_merge($additionalvalidators, array(
					array("ValEmail")
				)),
				"control" => array("TextField","size" => 30, "maxlength" => 51),
				"helpstep" => 1
			);

			$destinationlayoutforms[] = new FormSplitter("emailheaders", ucfirst($type), isset($existingmessagegroup) && $existingmessagegroup->hasMessage($type) ? "img/icons/accept.gif" : "img/icons/diagona/16/160.gif", "horizontalsplit", array(), array(
				array("title" => "", "formdata" => $emailheadersformdata),
				new FormTabber("", "", null, "horizontaltabs", $subtypelayoutforms)
			));
		}
	} else if (count($subtypelayoutforms) == 1) { // Phone, Sms.
		$subtypelayoutforms[0]->title = ucfirst($type);
		$destinationlayoutforms[] = $subtypelayoutforms[0];
	}
}

// Summary Tab.
$summaryheaders = '<th></th>';
$summarylanguagerows = "";
foreach ($destinations as $type => $destination) {
	foreach ($destination['subtypes'] as $subtype) {
		$summaryheaders .= "<th class='Destination'>" . ucfirst($type) . (count($destination['subtypes']) > 1 ? (" (" . ucfirst($subtype) . ") ") : "") . "</th>";
	}
}
foreach ($customerlanguages as $languagecode => $languagename) {
	$summarylanguagerows .= "<tr><th class='Language'>" . ucfirst($languagename) . "</th>";
	foreach ($destinations as $type => $destination) {
		foreach ($destination['subtypes'] as $subtype) {
			if ($type == 'sms' && $languagecode != $systemdefaultlanguagecode) {
				$summarylanguagerows .= "<td></td>";
			} else {
				$icon = (isset($existingmessagegroup) && $existingmessagegroup->hasMessage($type, $subtype, $languagecode)) ? 'img/icons/accept.gif' : 'img/icons/diagona/16/160.gif';
				$summarylanguagerows .= "<td class='StatusIcon'><img class='StatusIcon' id='{$type}-{$subtype}-{$languagecode}-summaryicon' src='$icon'/></td>";
			}
		}
	}
	$summarylanguagerows .= "</tr>";
}
$destinationlayoutforms[] = array(
	"name" => "summary",
	"title" => "Summary",
	"formdata" => array(
		'summary' => array(
			"label" => _L('Summary!'),
			"value" => "wassup",
			"validators" => array(),
			"control" => array("FormHtml","html" => "<table>{$summaryheaders}{$summarylanguagerows}</table>"),
			"renderoptions" => array("icon" => false, "label" => false, "errormessage" => false),
			"helpstep" => 1
		)
	)
);

//////////////////////////////////////////////////////////
// Finalize the formsplitter.
//////////////////////////////////////////////////////////
if (!$readonly) {
	$buttons = array(icon_button(_L("Done"),"tick", "form_submit_all(null, 'done', $('formswitchercontainer'));", null), icon_button(_L("Cancel"),"cross",null,"start.php"));
} else {
	$buttons = array(); // TODO: Maybe readonly needs a done button?
}

$messagegroupname = isset($existingmessagegroup) ? $existingmessagegroup->name : $newmessagegroup->name;
$messagegroupsplitter = new FormSplitter("messagegroupbasics", "", null, "horizontalsplit", $buttons, array(
	array("title" => "", "formdata" => array(
		'name' => array(
			"label" => _L('Message Name'),
			// If the user hasn't changed the message group's default name, then just show blank so that the user is forced to make a better one.
			"value" => $messagegroupname == $defaultmessagegroupname ? '' : $messagegroupname,
			"validators" => array(
				array("ValDuplicateNameCheck", "type" => "messagegroup"),
				array("ValRequired","ValLength","min" => 3,"max" => 50)
			),
			"control" => array("TextField","size" => 30, "maxlength" => 50),
			"helpstep" => 1
		),
		'defaultlanguagecode' => array(
			"label" => _L('Default Language'),
			"value" => isset($existingmessagegroup) ? $existingmessagegroup->defaultlanguagecode : $systemdefaultlanguagecode,
			"validators" => array(
				array("ValRequired"),
				array("ValInArray","values" => array_keys($customerlanguages))
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
if (($button = $messagegroupsplitter->getSubmit()) && !$readonly) {
	$form = $messagegroupsplitter->getSubmittedForm();

	$messagegroup = isset($existingmessagegroup) ? $existingmessagegroup : $newmessagegroup;

	if ($form) {
		$ajax = $form->isAjaxSubmit();
		$postdata = $form->getData();

		switch($button) {
			case 'tab':
			case 'done': {
				QuickQuery('BEGIN');

				/////////////////////////////
				// Global Settings.
				/////////////////////////////

				// Preferred Gender:
				// $preferredgender is previously defined, but it can be overwritten by $postdata.
				if (isset($postdata['preferredgender']))
					$preferredgender = $postdata['preferredgender'];
				$phonemessages = DBFindMany('Message', 'from message where not deleted and type="phone" and messagegroupid=?', false, array($messagegroup->id));
				foreach ($phonemessages as $phonemessage) {
					$phonemessage->updatePreferredVoice($preferredgender);
				}

				// Email Headers:
				if (isset($postdata['subject']))
					$_SESSION['emailheaders']['subject'] = trim($postdata['subject']);
				if (isset($postdata['fromname']))
					$_SESSION['emailheaders']['fromname'] = trim($postdata['fromname']);
				if (isset($postdata['fromemail']))
					$_SESSION['emailheaders']['fromemail'] = trim($postdata['fromemail']);
				// Use a single query to update all email message headers in this message group.
				$emailheaderdatastring = Message::makeHeaderDataString($_SESSION['emailheaders']);
				QuickUpdate('update message set data=? where not deleted and type="email" and messagegroupid=?', false, array($emailheaderdatastring, $messagegroup->id));

				// Email Attachments:
				// $emailattachments is previously defined, but it can be overwritten by $postdata.
				if (isset($postdata["attachments"])) {
					if (!is_array($emailattachments = json_decode($postdata["attachments"],true)))
						$emailattachments = array();
					$_SESSION['emailattachments'] = $emailattachments;

					// First delete all message attachments for this messagegroup, then create new ones.
					QuickUpdate("delete a from messageattachment a join message m on a.messageid = m.id where m.messagegroupid=?",false,array($messagegroup->id));
					$emailmessages = DBFindMany('Message', 'from message where not deleted and type="email" and messagegroupid=?', false, array($messagegroup->id));
					foreach ($emailmessages as $emailmessage) {
						$emailmessage->createMessageAttachments($emailattachments);
					}
				}

				/////////////////////////////
				// Specific Forms
				/////////////////////////////

				if ($form->name == 'messagegroupbasics') {
					$messagegroup->name = trim($postdata['name']);
					$messagegroup->defaultlanguagecode = $postdata['defaultlanguagecode'];
				} else if ($form->name == 'emailheaders') {
					// Email headers are updated further down, where global settings are applied.
				} else if ($form->name == 'summary') {
				} else {
					list($formdestinationtype, $formdestinationsubtype, $formdestinationlanguagecode) = explode('-', $form->name);

					$destination = isset($destinations[$formdestinationtype]) ? $destinations[$formdestinationtype] : null;

					if (in_array($formdestinationsubtype, $destination['subtypes']) && ($formdestinationlanguagecode == 'autotranslator' || isset($destination['languages'][$formdestinationlanguagecode]))) {
						$messagegroup->permanent = $postdata['autoexpire'] + 0;
						QuickUpdate('update audiofile set permanent=? where messagegroupid=?', false, array($messagegroup->permanent, $messagegroup->id));

						if ($formdestinationlanguagecode == 'autotranslator') {
							$autotranslatorlanguages = array(); // [$languagecode] = $translationlanguagename
							$trimmedautotranslatorsourcetext = trim($postdata['sourcemessagebody']);
							if (!empty($trimmedautotranslatorsourcetext)) {
								$_SESSION['autotranslatorsourcetext'] = $trimmedautotranslatorsourcetext;
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
									$sourcemessageparts = Message::parse($_SESSION['autotranslatorsourcetext']);
									if ($autotranslatortranslations = translate_fromenglish(Message::format($sourcemessageparts, true), $autotranslatorlanguages)) {
										// Increment an index because translate_fromenglish() does not return an associative array.
										$autotranslationlanguageindex = 0;
										foreach ($autotranslatorlanguages as $languagecode => $translationlanguagename) {
											// TODO, Optimize: Use a single QuickUpdate() for all languages, instead of per language? Would need to query "where languagecode in (...)".
											// Delete any existing messages that are not relevent for autotranslate.
											QuickUpdate('update message set deleted=1 where autotranslate not in ("source", "translated") and messagegroupid=? and type=? and subtype=? and languagecode=?', false, array($messagegroup->id, $formdestinationtype, $formdestinationsubtype, $languagecode));
											if (!($sourcemessage = DBFind('Message', 'from message where not deleted and autotranslate="source" and messagegroupid=? and type=? and subtype=? and languagecode=?', false, array($messagegroup->id, $formdestinationtype, $formdestinationsubtype, $languagecode)))) {
												$sourcemessage = new Message();
												$sourcemessage->updateMessageForCurrentUser($messagegroup->id, "Message for {$formdestinationtype} {$formdestinationsubtype} {$languagecode}", "", $formdestinationtype, $formdestinationsubtype, $languagecode, 'source', $formdestinationtype == 'email' ? $emailheaderdatastring : '');
												if ($formdestinationtype == 'email')
													$sourcemessage->createMessageAttachments($emailattachments);
											}

											// NOTE: Reuse the same set of message parts for each language's source message; Message::recreateParts() calls DBMappedObject::create(), which will result in the message parts getting new IDs.
											// TestCase: Verify $sourcemessage's parts have unique IDs; they should be different than $sourcemessageparts; they should also be different from the other languages.
											$sourcemessage->recreateParts(null, $sourcemessageparts, $formdestinationtype == 'phone' ? $preferredgender : null);

											if (!($translatedmessage = DBFind('Message', 'from message where not deleted and autotranslate="translated" and messagegroupid=? and type=? and subtype=? and languagecode=?', false, array($messagegroup->id, $formdestinationtype, $formdestinationsubtype, $languagecode)))) {
												$translatedmessage = new Message();
												$translatedmessage->updateMessageForCurrentUser($messagegroup->id, "Message for {$formdestinationtype} {$formdestinationsubtype} {$languagecode}", "", $formdestinationtype, $formdestinationsubtype, $languagecode, 'translated', $formdestinationtype == 'email' ? $emailheaderdatastring : '');
												if ($formdestinationtype == 'email')
													$translatedmessage->createMessageAttachments($emailattachments);
											}

											$translationtext = is_array($autotranslatortranslations) ? $autotranslatortranslations[$autotranslationlanguageindex]->responseData->translatedText : $autotranslatortranslations->translatedText;
											$translatedmessage->recreateParts($translationtext, null, $formdestinationtype == 'phone' ? $preferredgender : null);
											$autotranslationlanguageindex++;
										}
									} else {
										unset($autotranslatortranslations);
									}
								}
							}
						} else {
							// Either update existing messages or soft-delete them, depending on the user inputs for the sourcemessagebody/translationitem/messagebody.

							if (count($destination['languages']) > 1 && (($formdestinationtype == 'phone' && isset($customerphonetranslationlanguages[$formdestinationlanguagecode])) || ($formdestinationtype == 'email' && isset($customeremailtranslationlanguages[$formdestinationlanguagecode])))) {
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
								if (!$translation = translate_fromenglish(Message::format(Message::parse($trimmedsourcetext),true), array($translationlanguages[$formdestinationlanguagecode]))) {
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
							$existingmessages = DBFindMany('Message', 'from message where not deleted and messagegroupid=? and type=? and subtype=? and languagecode=?', false, array($messagegroup->id, $formdestinationtype, $formdestinationsubtype, $formdestinationlanguagecode));
							// Delete any existing messages that are no longer relevent, and figure out what new messages are needed.
							foreach ($existingmessages as $existingmessage) {
								$newmessagesneeded[$existingmessage->autotranslate] = false;

								if (!$messagesneeded[$existingmessage->autotranslate] || $messagebodies[$existingmessage->autotranslate] == "") {
									$existingmessage->deleted = 1;
									$existingmessage->update();

									// NOTE: Don't bother updating parts and attachments for deleted messages.
									continue;
								}

								$existingmessage->recreateParts($messagebodies[$existingmessage->autotranslate], null, $formdestinationtype == 'phone' ? $preferredgender : null);
							}
							foreach ($newmessagesneeded as $autotranslate => $needed) {
								if (!$needed || $messagebodies[$autotranslate] == "")
									continue;

								$newmessage = new Message();
								$newmessage->updateMessageForCurrentUser($messagegroup->id, "Message for {$formdestinationtype} {$formdestinationsubtype} {$formdestinationlanguagecode}", "", $formdestinationtype, $formdestinationsubtype, $formdestinationlanguagecode, $autotranslate, $formdestinationtype == 'email' ? $emailheaderdatastring : '');

								if ($formdestinationtype == 'email')
									$newmessage->createMessageAttachments($emailattachments);

								$newmessage->recreateParts($messagebodies[$autotranslate], null, $formdestinationtype == 'phone' ? $preferredgender : null);
							}
						}
					} else {
						break;
					}
				}

				$messagegroup->deleted = 0;
				$messagegroup->update();

				QuickQuery('COMMIT');

				if ($ajax && $button == 'done')
					$form->sendTo('start.php');
				else if ($ajax && $button == 'tab')
					$form->sendTo('');
			} break;

			case 'cancel': {
			} break;
		}
	} else {
	}
}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$PAGE = "notifications:messages";
$TITLE = _L('Message Editor');

include_once('nav.inc.php');
?>


<style type='text/css'>
form {
	margin: 0;
	padding: 0;
}
#messagegroupbasics_name_fieldarea .formtableheader {
	width: 150px;
}
td.verticaltabstabspane {
	width: 10%;
	white-space: nowrap;
}
td.MessageGroupAudioFile {

}
td.GlobalAudioFile {
}
iframe.UploadIFrame {
	overflow: hidden;
	width: 100%;
	margin: 0;
	margin-top: 10px;
	padding: 0;
	height: 60px;
}
#cke_reusableckeditor {
	border: 0;
	margin: 0;
	padding: 0;
}
div.MessageBodyHeader, div.SourceMessageBodyHeader {
	font-weight: bold;
	margin-left: 2px;
}
</style>

<script src="script/ckeditor/ckeditor_basic.js" type="text/javascript"></script>
<script src="script/accordion.js" type="text/javascript"></script>
<script src="script/audiolibrarywidget.js.php" type="text/javascript"></script>
<script type="text/javascript">
	<?php Validator::load_validators(array("ValDuplicateNameCheck", "ValCallMeMessage", "ValMessageBody", "ValEmailMessageBody", "ValLength", "ValRegExp", "ValEmailAttach")); ?>
</script>

<?php
startWindow(_L('Message Editor'));

$firstdestinationtype = array_shift(array_keys($destinations));
$firstdestinationsubtype = array_shift($destinations[$firstdestinationtype]['subtypes']);
$defaultsections = array("{$firstdestinationtype}-{$firstdestinationsubtype}", "{$firstdestinationtype}-{$firstdestinationsubtype}-{$systemdefaultlanguagecode}");
if ($firstdestinationtype == 'email')
	$defaultsections[] = "emailheaders";
echo '<div id="formswitchercontainer">' . $messagegroupsplitter->render($defaultsections) . '</div>';

?>

<div style='display:none'>
	<textarea id='preloadhtmleditor'></textarea>
</div>

<script type="text/javascript">
	(function() {
		// Use an object to store state information.
		var state = {
			'currentdestinationtype': '<?=$firstdestinationtype?>',
			'currentsubtype': '<?=$firstdestinationsubtype?>',
			'currentlanguagecode': '<?=$systemdefaultlanguagecode?>',
			'messagegroupsummary': <?=json_encode(QuickQueryMultiRow("select distinct type,subtype,languagecode from message where userid=? and messagegroupid=? and not deleted order by type,subtype,languagecode", true, false, array($USER->id, $_SESSION['messagegroupid'])))?>
		};

		var formswitchercontainer = $('formswitchercontainer');
		form_init_splitter(formswitchercontainer, <?=json_encode($defaultsections)?>);

		var styleLayouts = function() {
			$$('div.accordion').each(function(div) {
				if (div.match('.FormSwitcherLayoutSection')) {
					var td = div.up('td.SplitPane');
					if (td) {
						td.style.width = '30%';
					}

					var formtableheaders = div.select('.formtableheader');
					formtableheaders.each(function(th) {
						th.style.width = '100px';
					});
				}
			});

			var verticaltabs = $$('div.verticaltabstitlediv');
			if (verticaltabs.length > 0)
				verticaltabs[0].setStyle({'marginBottom':'20px'});
		};

		var confirmAutotranslator = function(clickevent, tabevent, state) {
			saveHtmlEditorContent(); // If this is email html, the autotranslator also uses the html editor for its source message body.

			var sourcetextarea = $(state.currentdestinationtype + '-' + state.currentsubtype + '-autotranslator_sourcemessagebody');
			var sourcetext = sourcetextarea.value;
			if (sourcetext.strip() == '') {
				if (clickevent) {
					alert('<?= _L("You have not typed a translation message.") ?>');
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
					for (var i = 0; i < messagegroupsummary.length; i++) {
						var messageinfo = messagegroupsummary[i];
						if (messageinfo.type == state.currentdestinationtype && messageinfo.subtype == state.currentsubtype && messageinfo.languagecode == languagecode) {
							willoverwrite = true;
							break;
						}
					}
				}
			}

			if (translationlanguagecodes.length < 1) {
				if (clickevent) {
					alert('<?= _L("You have not selected any languages to translate.") ?>'); // WORDSMITH: Better message.
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
		var updateTranslationItem = function(form, languagecode, translatedtext) {
			var formitemname = form.name + '_' + languagecode + '-translationitem';
			$(formitemname+'text').value = translatedtext;
			$(formitemname+'textdiv').update(translatedtext.replace(/<</, "&lt;&lt;").replace(/>>/, "&gt;&gt;"));
		};

		formswitchercontainer.observe('FormSplitter:BeforeSubmitAll', function(event, state) {
			if ($('autotranslatorrefreshtranslationbutton') && !confirmAutotranslator(null, event, state))
				event.stop();
		}.bindAsEventListener(formswitchercontainer, state));

		formswitchercontainer.observe('FormSplitter:BeforeTabLoad', function(event, state) {
			var nexttab = event.memo.nexttab;
			var nexttabpieces = nexttab.split('-');
			if (nexttabpieces.length == 2 && nexttabpieces[0] == 'email') {
				// If the user is tabbing between subtypes, make sure the language stays consistent.
				event.memo.specificsections = [event.memo.nexttab + '-' + state.currentlanguagecode];
			} else if (nexttab == 'emailheaders') {
				event.memo.specificsections = ['emailheaders', 'email-html', 'email-html-<?=$systemdefaultlanguagecode?>'];
			} else if (nexttab == 'phone-voice') {
				event.memo.specificsections = ['phone-voice', 'phone-voice-<?=$systemdefaultlanguagecode?>'];
			}
		}.bindAsEventListener(formswitchercontainer, state));

		// When a tab is loaded, update the status icon of the previous tab.
		formswitchercontainer.observe('FormSplitter:TabLoaded', function(event, state, styleLayouts) {
			styleLayouts();

			var memo = event.memo;
			var previoustabpieces = memo.previoustab.split('-');
			var tabloadedpieces = memo.tabloaded.split('-');

			// Keep track of the current destination type, subtype, and languagecode.
			if (tabloadedpieces.length == 3) {
				state.curerntdestinationtype = tabloadedpieces[0];
				state.currentsubtype = tabloadedpieces[1];
				state.currentlanguagecode = tabloadedpieces[2];
			} else if (tabloadedpieces.length == 1 || (tabloadedpieces.length == 2 && tabloadedpieces[0] == 'phone')) {
				state.currentdestinationtype = tabloadedpieces[0] != 'summary' ? tabloadedpieces[0] : '';
				if (state.currentdestinationtype == 'emailheaders')
					state.currentdestinationtype = 'email';
				state.currentsubtype = tabloadedpieces.length == 2 ? tabloadedpieces[1] : '';
				state.currentlanguagecode = '<?=$systemdefaultlanguagecode?>';
			}

			// Event Handlers for specific tabs like summary, autotranslator, individual languages.
			if (memo.tabloaded == 'summary') {
				memo.widget.container.observe('click', function(event, widget, state) {
					var element = event.element();
					if (element.match('.StatusIcon')) {
						var pieces = element.identify().split('-');
						var specificsections = [
							pieces[0] + '-' + pieces[1] + '-' + pieces[2]
						];
						if (pieces[0] != 'sms')
							specificsections.push(pieces[0] + '-' + pieces[1]);
						if (pieces[0] == 'email') {
							specificsections.push('emailheaders');
							//state.currentlanguagecode = pieces[2];
						}

						var nexttab;
						if (pieces[0] == 'sms') {
							nexttab = pieces[0] + '-' + pieces[1] + '-' + pieces[2];
						} else {
							nexttab = pieces[0] == 'email' ? 'emailheaders' : (pieces[0] + '-' + pieces[1]);
						}

						form_load_tab(this, widget, nexttab, specificsections, true);
					}
				}.bindAsEventListener(memo.form, memo.widget, state));
			} else if (tabloadedpieces.length == 3 && tabloadedpieces[2] == 'autotranslator') {
				var autotranslator =  $('autotranslatorrefreshtranslationbutton');
				if (autotranslator) {
					autotranslator.observe('click', function(event, state) {
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

						var errortext = '<?=escapehtml(_L('Sorry an error occurred during translation. Please try again.'))?>';
						new Ajax.Request('translate.php', {
							'method':'post',
							'parameters': {'english': makeTranslatableString(sourcetext), 'languages': translationlanguagecodes.join(';')},
							'onSuccess': function(transport, translationlanguagecodes, errortext) {
								var data = transport.responseJSON;

								if (!data || !data.responseData || !data.responseStatus || data.responseStatus != 200 || (translationlanguagecodes.length > 1 && translationlanguagecodes.length != data.responseData.length)) {
									alert(errortext);
									return;
								}

								var dataResponseData = data.responseData;

								for (var i = 0, count = translationlanguagecodes.length; i < count; i++) {
									var languagecode = translationlanguagecodes[i];
									if (count == 1) {
										updateTranslationItem(this, languagecode, dataResponseData.translatedText);
										break;
									}

									var response = dataResponseData[i];
									var responseData = response.responseData;
									i++;

									if (response.responseStatus != 200 || !responseData) {
										alert(errortext);
										continue;
									}

									updateTranslationItem(this, languagecode, responseData.translatedText);
								}
							}.bindAsEventListener(this, translationlanguagecodes, errortext),
							
							'onFailure': function(transport, errortext) {
								alert(errortext);
							}.bindAsEventListener(this, errortext)
						});
					}.bindAsEventListener(autotranslator.up('form'), state));
				}
			} else if (tabloadedpieces.length == 3) {
				// Individual message translation.
				/*var refreshtranslationbutton = $('refreshtranslationbutton');
				if (refreshtranslationbutton) {
					refreshtranslationbutton.observe('click', function(event, state) {
						saveHtmlEditorContent();

						var formname = this.name;
						var idpieces = formname.split('-');
						if (idpieces.length == 3) {
							getTranslation(formname + '_translationitem', idpieces[2], idpieces[1] == 'html');
						}
					}.bindAsEventListener(refreshtranslationbutton.up('form'), state));
				}*/
			}

			// Update the status icons on tabs.
			new Ajax.Request('ajax.php', {
				'method': 'get',
				'parameters': {
					'type': 'messagegroupsummary',
					'messagegroupid': <?=$_SESSION['messagegroupid']?>
				},
				'onSuccess': function(transport, memo, state) {
					var previoustabpieces = memo.previoustab.split('-');
					var tabloadedpieces = memo.tabloaded.split('-');

					var results = transport.responseJSON;
					state.messagegroupsummary = results || [];
					if (results) {
						for (var i = 0; i < results.length; i++) {
							var result = results[i];

							var updateprevioustab = (result.type == 'email' && memo.previoustab == 'emailheaders') || result.type == previoustabpieces[0];
							var updatetabloaded = (result.type == 'email' && memo.tabloaded == 'emailheaders') || result.type == tabloadedpieces[0];
							if (updateprevioustab || updatetabloaded) {
								if (result.type == 'email' && $('emailheadersicon')) {
									$('emailheadersicon').src = "img/icons/accept.gif";
								}

								if ($(result.type + '-' + result.subtype + 'icon')) {
									$(result.type + '-' + result.subtype + 'icon').src = "img/icons/accept.gif";
								}

								if ($(result.type + '-' + result.subtype + '-' + result.languagecode + 'icon')) {
									$(result.type + '-' + result.subtype + '-' + result.languagecode + 'icon').src = "img/icons/accept.gif";
								}
							}
						}
					}
				}.bindAsEventListener(this, memo, state)
				
				// NOTE: No need to alert on failure because this ajax request is only to update the status icons.
			});
		}.bindAsEventListener(formswitchercontainer, state, styleLayouts));

		styleLayouts();
	})();

</script>

<?php
endWindow();
include_once('navbottom.inc.php');
?>
