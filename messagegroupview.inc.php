<?php

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
// Request processing:
///////////////////////////////////////////////////////////////////////////////
if (!isset($_GET['id']) || !userOwns('messagegroup',$_GET['id'] + 0))
	redirect('unauthorized.php');

$messagegroup = new MessageGroup($_GET['id'] + 0);

///////////////////////////////////////////////////////////////////////////////
// Data Gathering:
///////////////////////////////////////////////////////////////////////////////

// Make an array of just the default language, for use in SMS and when the user cannot send multilingual messages.
$deflanguagecode = Language::getDefaultLanguageCode();
$deflanguage = array($deflanguagecode => Language::getName($deflanguagecode));

//if the user can send multi-lingual notifications use all languages, otherwise use an array of just the default.
$customerlanguages = $cansendmultilingual ? Language::getLanguageMap() : $deflanguage;

$emailheaders = $messagegroup->getGlobalEmailHeaders(
	array(
		'subject' => '',
		'fromname' => '',
		'fromemail' => ''
	)
);

$emailattachments = $messagegroup->getGlobalEmailAttachments();

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
	if (!$messagegroup->hasMessage($type)) {
		unset($destinations[$type]);
		continue;
	}
		
	/////////////////////////////////
	// Accordion Sections
	// The accordion sections for viewmessagegroup.php do not need javascript interaction with any formitems,
	// so we can define the entire accordion at the destination-type level.
	/////////////////////////////////
	$accordionsplitterchildren = array();

	if ($type == 'email' && count($emailattachments) > 0) {
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
	
	//////////////////////
	// Subtypes
	//////////////////////
	$subtypelayoutforms = array();

	foreach ($destination['subtypes'] as $subtype) {
		if (!$messagegroup->hasMessage($type, $subtype)) {
			continue;
		}
		
		$messageformsplitters = array();

		// Individual Message (type-subtype-language).
		foreach ($destination['languages'] as $languagecode => $languagename) {	
			if (!$message = $messagegroup->getMessage($type,$subtype,$languagecode, 'overridden')) {
				if (!$message = $messagegroup->getMessage($type,$subtype,$languagecode, 'translated')) {
					if (!$message = $messagegroup->getMessage($type,$subtype,$languagecode, 'none')) {
						// NOTE: We want to use the original array $destinations because
						// when loading a specific tab, we only want to jump to a language
						// that has a tab.
						unset($destinations[$type]['languages'][$languagecode]);
						continue;
					}
				}
			}
			
			$messageformdata = array();
			
			$parts = DBFindMany('MessagePart', 'from messagepart where messageid=?', false, array($message->id));
			if ($subtype == 'html') {
				$messagetext = str_replace('<<', '&lt;&lt;', $message->format($parts));
				$messagetext = str_replace('>>', '&gt;&gt;', $messagetext);
			} else {
				$messagetext = escapehtml($message->format($parts));
			}
			
			if ($type == 'sms') {
				$messageformdata["header"] = makeFormHtml("<div class='MessageBodyHeader'>" . _L("SMS Message") . "</div>");
				$messageformdata["message"] = makeFormHtml("<div class='MessageTextReadonly'>$messagetext</div>");
			} else {
				$messageformdata["header"] = makeFormHtml("<div class='MessageBodyHeader'>" . _L("%s Message", $languagename) . "</div>");
				$messagehtml = "<div class='MessageTextReadonly'>$messagetext</div>";
					
				if ($type == 'phone') {
					$messagehtml .= icon_button(_L("Play"), "fugue/control",
							"popup('previewmessage.php?id={$message->id}', 400, 400,'preview');"
						) .
						"<div style='clear:both'></div>";
				}
				
				$messageformdata["message"] = makeFormHtml($messagehtml);
				
				if ($message->type == 'translated')
					$messageformdata["branding"] = makeBrandingFormHtml();
			}
			
			$formsplitterchildren = array(
				array("title" => "", "formdata" => $messageformdata)
			);
			
			if (count($accordionsplitterchildren) > 0) {
				$formsplitterchildren[] = new FormSplitter("", "", null, "accordion", array(), $accordionsplitterchildren);
			}
			
			$messageformsplitters[] = new FormSplitter("{$type}-{$subtype}-{$languagecode}", $languagename,
				$messagegroup->hasMessage($type, $subtype, $languagecode) ? "img/icons/accept.gif" : "img/icons/diagona/16/160.gif",
				"verticalsplit",
				array(),
				$formsplitterchildren
			);
		}

		// NOTE: We want to use the original array $destinations because
		// the count of languages may have changed due to unset($destinations[$type]['languages'][$languagecode]).
		if (count($destinations[$type]['languages']) > 1) {
			$subtypelayoutforms[] = new FormTabber("{$type}-{$subtype}", $subtype == 'html' ? 'HTML' : ucfirst($subtype), $messagegroup->hasMessage($type, $subtype) ? "img/icons/accept.gif" : "img/icons/diagona/16/160.gif", "verticaltabs", $messageformsplitters);
		} else {
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
				$messagegroup->hasMessage($type) ? "img/icons/accept.gif" : "img/icons/diagona/16/160.gif",
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



//////////////////////////////////////////////////////////
// Finalize the formsplitter.
//////////////////////////////////////////////////////////
$buttons = ($popup) ? array() : array(icon_button(_L("Done"),"tick", null, "messages.php"));

$countdestinations = count($destinations);
if ($countdestinations > 0) {
	$destinationlayoutforms[] = makeSummaryTab($destinations, $customerlanguages, Language::getDefaultLanguageCode(), $messagegroup);
	$messagegroupsplitter = new FormSplitter("messagegroupbasics", "", null, "horizontalsplit", $buttons, array(
		array("title" => "", "formdata" => array(
			'name' => array(
				"label" => _L('Message Name'),
				"control" => array("FormHtml","html" => $messagegroup->name),
				"helpstep" => 1
			),
			'defaultlanguagecode' => array(
				"label" => _L('Default Language'),
				// NOTE: It is not necessary to capitalize the language names in $customerlanguages because it should already be so in the database.
				"control" => array("FormHtml","html" => $customerlanguages[$messagegroup->defaultlanguagecode]),
				"helpstep" => 1
			)
		)),
		new FormTabber("destinationstabber", "", null, "horizontaltabs", $destinationlayoutforms)
	));
}

///////////////////////////////////////////////////////////////////////////////
// Ajax
///////////////////////////////////////////////////////////////////////////////
if ($countdestinations > 0)
	$messagegroupsplitter->handleRequest(); // Handles $_REQUEST["loadtab"]

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$PAGE = "notifications:messages";
$TITLE = _L('Message Viewer');

if ($popup) {
	include_once('popup.inc.php');
	button_bar(button('Done', 'window.close()'));
	echo '<br/>';
} else {
	include_once('nav.inc.php');
}
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

if ($countdestinations > 0) {
	$preferreddestinationtype = isset($_GET['type']) && isset($destinations[$_GET['type']]) ? $_GET['type'] : reset(array_keys($destinations));
	$preferreddestinationsubtype = isset($_GET['subtype']) && in_array($_GET['subtype'], $destinations[$preferreddestinationtype]['subtypes']) ? $_GET['subtype'] : reset($destinations[$preferreddestinationtype]['subtypes']);
	if (isset($_GET['languagecode']) && isset($destinations[$preferreddestinationtype]['languages'][$_GET['languagecode']])) {
		$preferredlanguagecode = $_GET['languagecode'];
	} else if (isset($destinations[$preferreddestinationtype]['languages'][Language::getDefaultLanguageCode()])) {
		$preferredlanguagecode = Language::getDefaultLanguageCode();
	} else {
		$preferredlanguagecode = reset(array_keys($destinations[$preferreddestinationtype]['languages']));
	}
	$preferredtabs = array("{$preferreddestinationtype}-{$preferreddestinationsubtype}", "{$preferreddestinationtype}-{$preferreddestinationsubtype}-{$preferredlanguagecode}");
	if ($preferreddestinationtype == 'email')
		$preferredtabs[] = "emailheaders";

	echo '<div id="messagegroupformcontainer">' . $messagegroupsplitter->render($preferredtabs) . '</div>';
	?>
	<script type="text/javascript">
		(function() {
			// Use an object to store state information.
			var state = {
				'currentdestinationtype': '<?=$preferreddestinationtype?>',
				'currentsubtype': '<?=$preferreddestinationsubtype?>',
				'currentlanguagecode': '<?=$preferredlanguagecode?>',
				'messagegroupsummary': <?=json_encode(MessageGroup::getSummary($messagegroup->id))?>
			};
		
			var formswitchercontainer = $('messagegroupformcontainer');

			formswitchercontainer.observe('FormSplitter:BeforeTabLoad',
				messagegroupHandleBeforeTabLoad.bindAsEventListener(formswitchercontainer, state, '<?=$preferredlanguagecode?>')
			);

			// When a tab is loaded, update the status icon of the previous tab.
			formswitchercontainer.observe('FormSplitter:TabLoaded',
				messagegroupHandleTabLoaded.bindAsEventListener(formswitchercontainer, state, '<?=$messagegroup->id?>', '<?=$preferredlanguagecode?>', null, true)
			);
		
			form_init_splitter(formswitchercontainer, <?=json_encode($preferredtabs)?>, true);
		
			messagegroupStyleLayouts(true);
		})();

	</script>
	<?php
} else {
	echo '<br/>' . _L('This message is empty.') . '<br/><br/>' . implode('', $buttons) . '<div style="clear:both"></div>';
}

endWindow();

if ($popup) {
	include_once("popupbottom.inc.php");
} else {
	include_once('navbottom.inc.php');
}
?>
