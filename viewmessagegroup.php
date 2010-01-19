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
require_once("obj/Form.obj.php");
require_once("obj/FormTabber.obj.php");
require_once("obj/FormSplitter.obj.php");
require_once("obj/FormItem.obj.php");
require_once("obj/Phone.obj.php");
require_once("obj/Message.obj.php");
require_once("obj/Voice.obj.php");
require_once("obj/FieldMap.obj.php");
require_once("obj/AudioFile.obj.php");
require_once("obj/MessagePart.obj.php");
require_once("obj/Content.obj.php");
require_once("obj/Validator.obj.php");
require_once("obj/MessageGroup.obj.php");
require_once("obj/MessageAttachment.obj.php");
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
if (isset($_GET['id']) || isset($_SESSION['messagegroupid'])) {
	$existingmessagegroupid = isset($_GET['id']) ? $_GET['id'] + 0 : $_SESSION['messagegroupid'] + 0;
	if (userOwns('messagegroup', $existingmessagegroupid)) {
		$_SESSION['messagegroupid'] = $existingmessagegroupid;
		$existingmessagegroup = new MessageGroup($existingmessagegroupid);
	} else {
		unset($_SESSION['messagegroupid']);
		redirect('unauthorized.php');
	}
} else {
	redirect('messages.php');
}

///////////////////////////////////////////////////////////////////////////////
// Data Gathering:
///////////////////////////////////////////////////////////////////////////////
$systemdefaultlanguagecode = 'en';
$customerlanguages = $cansendmultilingual ? QuickQueryList("select code, name from language", true) : QuickQueryList("select code, name from language where code=?", true, false, array($systemdefaultlanguagecode));
if ($cansendmultilingual)
	$allowtranslation = isset($SETTINGS['translation']['disableAutoTranslate']) ? (!$SETTINGS['translation']['disableAutoTranslate']) : true;
else
	$allowtranslation = false;

$emailheaders = $existingmessagegroup->getGlobalEmailHeaders(array());
$emailattachments = $existingmessagegroup->getGlobalEmailAttachments();

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
///////////////////////////////////////////////////////////////////////////////
$destinationlayoutforms = array();
foreach ($destinations as $type => $destination) {
	$countlanguages = count($destination['languages']);
	$subtypelayoutforms = array();
	foreach ($destination['subtypes'] as $subtype) {
		$messageformsplitters = array();

		// Individual Message (type-subtype-language).
		foreach ($destination['languages'] as $languagecode => $languagename) {
			$blankmessagewarning = $countlanguages > 1 ? '<span id="messageemptyspan">' . _L("The %s message is blank, so these contacts will receive messages in the default language.", ucfirst($languagename)) . '</span>' : '';
			$messageformname = "{$type}-{$subtype}-{$languagecode}";

			$messagetexts = array(
				'source' => $existingmessagegroup->getMessageText($type,$subtype,$languagecode, 'source'),
				'translated' => $existingmessagegroup->getMessageText($type,$subtype,$languagecode, 'translated'),
				'overridden' => $existingmessagegroup->getMessageText($type,$subtype,$languagecode, 'overridden'),
				'none' => $existingmessagegroup->getMessageText($type,$subtype,$languagecode, 'none')
			);
			
			if (!empty($messagetexts['overridden'])) {
				$messagetext = $messagetexts['overridden'];
				$message = $existingmessagegroup->getMessage($type,$subtype,$languagecode, 'overridden');
			} else if (!empty($messagetexts['translated'])) {
				$messagetext = $messagetexts['translated'];
				$message = $existingmessagegroup->getMessage($type,$subtype,$languagecode, 'translated');
			} else {
				$messagetext = $messagetexts['none'];
				$message = $existingmessagegroup->getMessage($type,$subtype,$languagecode, 'none');
			}
			
			if ($subtype != 'html')
				$messagetext = escapehtml($messagetext);

			$messageformdata = array();
			$accordionsplitterchildren = array();
			$advancedoptionsformdata = array();
			$autoexpirevalues = array(
				"Yes (Keep for ". getSystemSetting('softdeletemonths', "6") ." months)",
				"No (Keep forever)"
			);
			$advancedoptionsformdata['autoexpire'] = array(
				"label" => _L('Auto Expire'),
				"control" => array("FormHtml","html" => $autoexpirevalues[$existingmessagegroup->permanent]),
				"helpstep" => 1
			);
			
			if ($type == 'sms') {
				$messageformdata["header"] = makeFormHtml("<div class='MessageBodyHeader'>" . _L("SMS Message") . "</div>");
				$messageformdata["message"] = makeFormHtml("<div class='MessageTextReadonly'>$messagetext</div>");
			} else {
				$messageformdata["header"] = makeFormHtml("<div class='MessageBodyHeader'>" . _L("%s Message", ucfirst($languagename)) . "</div>");
				
				if (!empty($messagetext)) {
					$messagehtml = "<div class='MessageTextReadonly'>$messagetext</div>";
					
					if ($type == 'phone') {
						$playbutton = icon_button(_L("Play"),"fugue/control",
							"popup('previewmessage.php?id={$message->id}', 400, 400,'preview');"
						);
						$messagehtml .= "$playbutton<div style='clear:both'></div>";
					}
				} else {
					$messagehtml = $blankmessagewarning;
				}
				
				$messageformdata["message"] = makeFormHtml($messagehtml);
				if (!empty($messagetexts['translated']))
					$messageformdata["branding"] = makeBrandingFormHtml();
					
				if ($type == 'phone') {
					$gendervalues = array ("female" => "Female","male" => "Male");
					$advancedoptionsformdata['preferredgender'] = array(
						"label" => _L('Preferred Voice'),
						"control" => array("FormHtml", "html" => $gendervalues[$existingmessagegroup->getGlobalPreferredGender('female')]),
						"helpstep" => 1
					);
				} else if ($type == 'email') {
					$attachmentshtml = "";
					
					foreach ($emailattachments as $emailattachment) {
						$urifilename = urlencode($emailattachment->filename);
						$filename = escapehtml($emailattachment->filename);
						$filesize = (int)($emailattachment->size / 1024);
						$attachmentshtml .= "<a href='emailattachment.php?id={$emailattachment->contentid}&name={$urifilename}'>$filename</a>";
						$attachmentshtml .= "&nbsp;(Size: {$filesize}k)&nbsp;";
					}
					
					if ($attachmentshtml == "")
						$attachmentshtml = _L("There are no attachments.");
					$accordionsplitterchildren[] = array("title" => _L("Attachments"), "icon" => "img/icons/diagona/16/190.gif", "formdata" => array(makeFormHtml($attachmentshtml)));
				}
			}
			
			$accordionsplitterchildren[] = array("title" => _L("Advanced Options"), "icon" => "img/icons/diagona/16/041.gif", "formdata" => $advancedoptionsformdata);
			$messageformsplitters[] = new FormSplitter($messageformname, $languagename,
				$existingmessagegroup->hasMessage($type, $subtype, $languagecode) ? "img/icons/accept.gif" : "img/icons/diagona/16/160.gif",
				"verticalsplit",
				array(),
				array(
					array("title" => "", "formdata" => $messageformdata),
					new FormSplitter("", "", null, "accordion", array(), $accordionsplitterchildren)
				)
			);
		}

		if ($countlanguages > 1) {
			$subtypelayoutforms[] = new FormTabber("{$type}-{$subtype}", $subtype == 'html' ? 'HTML' : ucfirst($subtype), $existingmessagegroup->hasMessage($type, $subtype) ? "img/icons/accept.gif" : "img/icons/diagona/16/160.gif", "verticaltabs", $messageformsplitters);
		} else if ($countlanguages == 1) {
			$messageformsplitters[0]->title = ucfirst($subtype);
			$subtypelayoutforms[] = $messageformsplitters[0];
		}
	}

	if (count($destination['subtypes']) > 1) {
		if ($type == 'email') {
			$emailheadersformdata = array();
			$emailheadersformdata['subject'] = array(
				"label" => _L('Subject'),
				"control" => array("FormHtml","html" => $emailheaders['subject']),
				"helpstep" => 1
			);
			$emailheadersformdata['fromname'] = array(
				"label" => _L('From Name'),
				"control" => array("FormHtml","html" => $emailheaders['fromname']),
				"helpstep" => 1
			);
			$emailheadersformdata['fromemail'] = array(
				"label" => _L('From Email'),
				"control" => array("FormHtml","html" => $emailheaders['fromemail']),
				"helpstep" => 1
			);

			$destinationlayoutforms[] = new FormSplitter("emailheaders", ucfirst($type), $existingmessagegroup->hasMessage($type) ? "img/icons/accept.gif" : "img/icons/diagona/16/160.gif", "horizontalsplit", array(), array(
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

$destinationlayoutforms[] = makeSummaryTab($destinations, $customerlanguages, $systemdefaultlanguagecode, $existingmessagegroup);

//////////////////////////////////////////////////////////
// Finalize the formsplitter.
//////////////////////////////////////////////////////////
$buttons = array(icon_button(_L("Done"),"tick", null, "messages.php"));

$messagegroupsplitter = new FormSplitter("messagegroupbasics", "", null, "horizontalsplit", $buttons, array(
	array("title" => "", "formdata" => array(
		'name' => array(
			"label" => _L('Message Name'),
			"control" => array("FormHtml","html" => $existingmessagegroup->name),
			"helpstep" => 1
		),
		'defaultlanguagecode' => array(
			"label" => _L('Default Language'),
			// NOTE: It is not necessary to capitalize the language names in $customerlanguages because it should already be so in the database.
			"control" => array("FormHtml","html" => $customerlanguages[$existingmessagegroup->defaultlanguagecode]),
			"helpstep" => 1
		)
	)),
	new FormTabber("destinationstabber", "", null, "horizontaltabs", $destinationlayoutforms)
));

///////////////////////////////////////////////////////////////////////////////
// Ajax
///////////////////////////////////////////////////////////////////////////////
$messagegroupsplitter->handleRequest(); // Handles $_REQUEST["loadtab"]

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$PAGE = "notifications:messages";
$TITLE = _L('Message Viewer');

include_once('nav.inc.php');
?>

<script src="script/accordion.js" type="text/javascript"></script>
<script src="script/messagegroup.js.php" type="text/javascript"></script>
<style type='text/css'>
#messageemptyspan {
	padding-top: 6px;
	display: block;
	clear: both;
	color: rgb(130,130,130);
}
</style>
<?php
startWindow(_L('Message Viewer'));

$firstdestinationtype = array_shift(array_keys($destinations));
$firstdestinationsubtype = array_shift($destinations[$firstdestinationtype]['subtypes']);
$defaultsections = array("{$firstdestinationtype}-{$firstdestinationsubtype}", "{$firstdestinationtype}-{$firstdestinationsubtype}-{$systemdefaultlanguagecode}");
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
			'currentlanguagecode': '<?=$systemdefaultlanguagecode?>',
			'messagegroupsummary': <?=json_encode(QuickQueryMultiRow("select distinct type,subtype,languagecode from message where userid=? and messagegroupid=? and not deleted order by type,subtype,languagecode", true, false, array($USER->id, $existingmessagegroup->id)))?>
		};

		var formswitchercontainer = $('messagegroupformcontainer');

		formswitchercontainer.observe('FormSplitter:BeforeTabLoad',
			messagegroupHandleBeforeTabLoad.bindAsEventListener(formswitchercontainer, state, '<?=$systemdefaultlanguagecode?>')
		);

		// When a tab is loaded, update the status icon of the previous tab.
		formswitchercontainer.observe('FormSplitter:TabLoaded',
			messagegroupHandleTabLoaded.bindAsEventListener(formswitchercontainer, state, '<?=$existingmessagegroup->id?>', '<?=$systemdefaultlanguagecode?>', true)
		);
		
		form_init_splitter(formswitchercontainer, <?=json_encode($defaultsections)?>, true);
		
		messagegroupStyleLayouts(true);
	})();

</script>

<?php
endWindow();
include_once('navbottom.inc.php');
?>
