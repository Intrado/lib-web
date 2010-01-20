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
	unset($_SESSION['viewmessagegroupid']);
	redirect('unauthorized.php');
}

///////////////////////////////////////////////////////////////////////////////
// Request processing:
///////////////////////////////////////////////////////////////////////////////

if (isset($_GET['id'])) {
	$id = $_GET['id'] + 0;
	if (userOwns('messagegroup',$id))
		$_SESSION['viewmessagegroupid'] = $id;
	redirect('viewmessagegroup.php');
}

if (!isset($_SESSION['viewmessagegroupid'])) {
	redirect('unauthorized.php');
}

$existingmessagegroup = new MessageGroup($_SESSION['viewmessagegroupid']);


///////////////////////////////////////////////////////////////////////////////
// Data Gathering:
///////////////////////////////////////////////////////////////////////////////

// Make an array of just the default language, for use in SMS and when the user cannot send multilingual messages.
$deflanguagecode = Language::getDefaultLanguageCode();
$deflanguage = array($deflanguagecode => Language::getName($deflanguagecode));

//if the user can send multi-lingual notifications use all languages, otherwise use an array of just the default.
$customerlanguages = $cansendmultilingual ? Language::getLanguageMap() : $deflanguage;

$emailheaders = $existingmessagegroup->getGlobalEmailHeaders(
	array(
		'subject' => '',
		'fromname' => '',
		'fromemail' => ''
	)
);

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
		'languages' => $deflanguage
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
			$messageformdata = array();
			
			if (!$message = $existingmessagegroup->getMessage($type,$subtype,$languagecode, 'overridden')) {
				if (!$message = $existingmessagegroup->getMessage($type,$subtype,$languagecode, 'translated'))
					$message = $existingmessagegroup->getMessage($type,$subtype,$languagecode, 'none');
			}
			
			if ($message) {
				$parts = DBFindMany('MessagePart', 'from messagepart where messageid=?', false, array($message->id));
				$messagetext = $subtype != 'html' ? escapehtml($message->format($parts)) : $message->format($parts);
			}
			
			if ($type == 'sms') {
				$messageformdata["header"] = makeFormHtml("<div class='MessageBodyHeader'>" . _L("SMS Message") . "</div>");
				if ($message)
					$messageformdata["message"] = makeFormHtml("<div class='MessageTextReadonly'>$messagetext</div>");
				// Don't show a blank-message warning for SMS.
			} else {
				$messageformdata["header"] = makeFormHtml("<div class='MessageBodyHeader'>" . _L("%s Message", $languagename) . "</div>");
				
				if ($message) {
					$messagehtml = "<div class='MessageTextReadonly'>$messagetext</div>";
					
					if ($type == 'phone') {
						$messagehtml .= icon_button(_L("Play"), "fugue/control",
								"popup('previewmessage.php?id={$message->id}', 400, 400,'preview');"
							) .
							"<div style='clear:both'></div>";
					}
				// Don't show a blank-message warning if either subtype has a message.
				} else if ($subtype == 'html' && $existingmessagegroup->hasMessage($type, 'plain', $languagecode)) {
					$messagehtml = '';
				} else if ($subtype == 'plain' && $existingmessagegroup->hasMessage($type, 'html', $languagecode)) {
					$messagehtml = '';
				} else if ($countlanguages > 1) {
					$messagehtml = '<span id="messageemptyspan">' .
						_L("The %s message is blank, so these contacts will receive messages in the default language.", $languagename) .
						'</span>';
				} else {
					$messagehtml = '';
				}
				
				$messageformdata["message"] = makeFormHtml($messagehtml);
				
				if ($message && $message->type == 'translated')
					$messageformdata["branding"] = makeBrandingFormHtml();
			}
			
			/////////////////////////////////
			// Accordion Sections
			/////////////////////////////////
			$accordionsplitterchildren = array();
			$advancedoptionsformdata = array();
			
			// Accordion sections for phone and email.
			if ($type == 'phone') {
				$gendervalues = array ("female" => "Female","male" => "Male");
				$advancedoptionsformdata['preferredgender'] = array(
					"label" => _L('Preferred Voice'),
					"control" => array("FormHtml", "html" => $gendervalues[$existingmessagegroup->getGlobalPreferredGender()]),
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
				
				$accordionsplitterchildren[] = array("title" => _L("Attachments"),
					"icon" => "img/icons/diagona/16/190.gif",
					"formdata" => array(makeFormHtml(
						$attachmentshtml != "" ? $attachmentshtml : _L("There are no attachments.")
					))
				);
			}
			
			// Accordion sections common to all destination types.	
			$autoexpirevalues = array(
				"Yes (Keep for ". getSystemSetting('softdeletemonths', "6") ." months)",
				"No (Keep forever)"
			);
			$advancedoptionsformdata['autoexpire'] = array(
				"label" => _L('Auto Expire'),
				"control" => array("FormHtml","html" => $autoexpirevalues[$existingmessagegroup->permanent]),
				"helpstep" => 1
			);
			
			$accordionsplitterchildren[] = array("title" => _L("Advanced Options"), "icon" => "img/icons/diagona/16/041.gif", "formdata" => $advancedoptionsformdata);
			$messageformsplitters[] = new FormSplitter("{$type}-{$subtype}-{$languagecode}", $languagename,
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
			$emailheadersformdata = array(
				'subject' => array(
					"label" => _L('Subject'),
					"control" => array("FormHtml","html" => $emailheaders['subject']),
					"helpstep" => 1
				),
				'fromname' => array(
					"label" => _L('From Name'),
					"control" => array("FormHtml","html" => $emailheaders['fromname']),
					"helpstep" => 1
				),
				'fromemail' => array(
					"label" => _L('From Email'),
					"control" => array("FormHtml","html" => $emailheaders['fromemail']),
					"helpstep" => 1
				)
			);

			$destinationlayoutforms[] = new FormSplitter("emailheaders", ucfirst($type),
				// Icon.
				$existingmessagegroup->hasMessage($type) ? "img/icons/accept.gif" : "img/icons/diagona/16/160.gif",
				// Layout-type.
				"horizontalsplit",
				// Buttons.
				array(),
				// Children.
				array(
					array("title" => "", "formdata" => $emailheadersformdata),
					new FormTabber("", "", null, "horizontaltabs", $subtypelayoutforms)
				)
			);
		}
	} else if (count($subtypelayoutforms) == 1) { // Phone, Sms.
		if ($type == 'sms')
			$subtypelayoutforms[0]->title = 'SMS';
		else
			$subtypelayoutforms[0]->title = ucfirst($type);
		
		$destinationlayoutforms[] = $subtypelayoutforms[0];
	}
}

$destinationlayoutforms[] = makeSummaryTab($destinations, $customerlanguages, Language::getDefaultLanguageCode(), $existingmessagegroup);

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
			'messagegroupsummary': <?=json_encode(MessageGroup::getSummary($existingmessagegroup->id))?>
		};
		
		var formswitchercontainer = $('messagegroupformcontainer');

		formswitchercontainer.observe('FormSplitter:BeforeTabLoad',
			messagegroupHandleBeforeTabLoad.bindAsEventListener(formswitchercontainer, state, '<?=Language::getDefaultLanguageCode()?>')
		);

		// When a tab is loaded, update the status icon of the previous tab.
		formswitchercontainer.observe('FormSplitter:TabLoaded',
			messagegroupHandleTabLoaded.bindAsEventListener(formswitchercontainer, state, '<?=$existingmessagegroup->id?>', '<?=Language::getDefaultLanguageCode()?>', null, true)
		);
		
		form_init_splitter(formswitchercontainer, <?=json_encode($defaultsections)?>, true);
		
		messagegroupStyleLayouts(true);
	})();

</script>

<?php
endWindow();
include_once('navbottom.inc.php');
?>