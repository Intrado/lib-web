<?

///////////////////////////////////////////////////////////////////////////////
// Authorization:
///////////////////////////////////////////////////////////////////////////////
// no messagegroup id

if (!isset($_GET['id']))
	redirect('unauthorized.php');
	
$messagegroup = new MessageGroup($_GET['id'] + 0);

// check publication and subscription restrictions on this message (or original if it is a copy)
// if the user owns this message, they can preview it. Otherwise, check the original message group id
if (!userOwns("messagegroup", $messagegroup->id) && $messagegroup->originalmessagegroupid) {
	if(!userOwns('messagegroup',$messagegroup->originalmessagegroupid) && 
			!isSubscribed("messagegroup",$messagegroup->originalmessagegroupid)) {
		redirect('unauthorized.php');
	}
} else if (!userOwns('messagegroup',$_GET['id'] + 0) && 
		!isPublished('messagegroup', $_GET['id']) && 
		!userCanSubscribe('messagegroup', $_GET['id'])) {
	redirect('unauthorized.php');
}
 
///////////////////////////////////////////////////////////////////////////////
// Request processing:
///////////////////////////////////////////////////////////////////////////////

if($messagegroup->type != 'notification') {
	redirect('unauthorized.php');
}

$cansendphone = $messagegroup->hasMessage('phone','voice');
$cansendemail = $messagegroup->hasMessage('email','plain') || $messagegroup->hasMessage('email','html');
$cansendsms = getSystemSetting('_hassms', false) && $messagegroup->hasMessage('sms','plain');
$cansendmultilingual = QuickQuery("select 1 from `message` where messagegroupid = ? and languagecode != 'en' limit 1", false, array($messagegroup->id));

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
			"formdata" => array(makeFormHtml(null, null,
				$attachmentshtml != "" ? $attachmentshtml : _L("There are no attachments.")
			))
		);
	}
	
	//////////////////////
	// Subtypes
	//////////////////////
	$subtypelayoutforms = array();

	foreach ($destination['subtypes'] as $index => $subtype) {
		if (!$messagegroup->hasMessage($type, $subtype)) {
			unset($destinations[$type]['subtypes'][$index]);
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
			
			$parts = DBFindMany('MessagePart', 'from messagepart where messageid=? order by sequence', false, array($message->id));

			$messageformdata = array();
			
			if ($type == 'sms') {
				$messagetext = Message::renderSmsParts($parts);
				$messageformdata["header"] = makeFormHtml(null, null,"<div class='MessageBodyHeader'>" . _L("SMS Message") . "</div>");
				$messageformdata["message"] = makeFormHtml(null, null,"<div class='MessageTextReadonly'>$messagetext</div>");
			} else {
				if ($type == 'email') {
					if ($subtype == 'html') {
						//$messagetext = str_replace('<<', '&lt;&lt;', $message->format($parts));
						//$messagetext = str_replace('>>', '&gt;&gt;', $messagetext);
						$messagetext = $message->renderEmailWithTemplate();
					} else {
						//$messagetext = escapehtml($message->format($parts));
						$messagetext = escapehtml($message->renderEmailWithTemplate());
					}
				} 

				$messageformdata["header"] = makeFormHtml(null, null,"<div class='MessageBodyHeader'>" . _L("%s Message", $languagename) . "</div>");
				$messagehtml = "";
				if ($type != 'phone') {
					$messagehtml .= "<div class='MessageTextReadonly'>$messagetext</div>";
				} else { // phone
					list($fields,$fielddata,$fielddefaults) = getpreviewfieldmapdata($message->id);
					$messageformdata += getpreviewformdata($fields,$fielddata,$fielddefaults,"phone");
					$hasdata = count($messageformdata) > 1;
					$messageformdata[] = array(
						"label" => "",
						"control" => array("FormHtml","html" =>
							($hasdata?submit_button(_L('Play with Field(s)'),"submit","fugue/control"):'') . '
							<div id="messageresultdiv" name="messageresultdiv"></div>
							<div id="messagepreviewdiv" name="messagepreviewdiv">
								<div align="center" style="clear:left">
									<div id="player"></div>' .
									($hasdata?'':'<script language="JavaScript" type="text/javascript">
														embedPlayer("preview.wav.php/embed_preview.wav?id=' . $message->id . '","player");
													</script>') .
									'<div id="download">' . ($hasdata?'':'<a href="preview.wav.php/download_preview.wav?id=' . $message->id . '&download=true" onclick="sessiondata=false;">' . _L("Click here to download") . '</a>') .
									'</div>
								</div>
							</div>

						'),
						"fieldhelp" => "",
						"renderoptions" => array("icon" => false, "label" => false, "errormessage" => true),
						"helpstep" => 1
					);

					//$messagetext = nl2br(escapehtml(Message::format($parts)));
				}
				$messageformdata["message"] = makeFormHtml(null, null,$messagehtml);
				
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
	} else if ($type == 'phone' || $type == 'sms') { // Phone, Sms.
		$subtypelayoutforms[0]->title = $type == 'sms' ? 'SMS' : ucfirst($type);
		
		$destinationlayoutforms[] = $subtypelayoutforms[0];
	}
}



//////////////////////////////////////////////////////////
// Finalize the formsplitter.
//////////////////////////////////////////////////////////
$buttons = ($popup) ? array() : array(icon_button(_L("Done"),"tick", null, "messages.php"));

$countdestinations = count($destinations);
if ($countdestinations > 0) {
	$destinationlayoutforms[] = makeSummaryTab($destinations, $customerlanguages, Language::getDefaultLanguageCode(), $messagegroup, true);
	$messagegroupsplitter = new FormSplitter("messagegroupbasics", "", null, "horizontalsplit", $buttons, array(
		array("title" => "", "formdata" => array(
			'name' => array(
				"label" => _L('Message Name'),
				"control" => array("FormHtml","html" => $messagegroup->name),
				"helpstep" => 1
			),
			'desc' => array(
				"label" => _L('Description'),
				"control" => array("FormHtml","html" => $messagegroup->description),
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

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

$button = $messagegroupsplitter->getSubmit();

// If loadtab is set, handleRequest will exit causing the submit not to be handled.
// Therefore unset loadtab when button was pressed even if within the tab
if ($button)
	unset($_REQUEST['loadtab']);

$messagegroupsplitter->handleRequest();

///////////////////////////////////////////////////////////////////////////////
// Submit
///////////////////////////////////////////////////////////////////////////////

if ($button) {
	$form = $messagegroupsplitter->getSubmittedForm();
	if ($form) {
		$ajax = $form->isAjaxSubmit();
		if (!$form->checkForDataChange() && $form->validate() === false) {
			$postdata = $form->getData();
			$previewdata = "";
			foreach ($postdata as $field => $value) {
				$previewdata .= "&$field=" . urlencode($value);
			}
			list($formdestinationtype, $formdestinationsubtype, $formdestinationlanguagecode) = explode('-', $form->name);
			if ($formdestinationtype == 'phone') {
				if (!$message = $messagegroup->getMessage('phone','voice',$formdestinationlanguagecode, 'overridden')) {
					if (!$message = $messagegroup->getMessage('phone','voice',$formdestinationlanguagecode, 'translated')) {
						if (!$message = $messagegroup->getMessage('phone','voice',$formdestinationlanguagecode, 'none')) {
						}
					}
				}
				$request = ($message)?"id=$message->id":"blank=true";
				$form->modifyElement("messageresultdiv", '
						<script language="JavaScript" type="text/javascript">
							embedPlayer("preview.wav.php/embed_preview.wav?' . $request . $previewdata. '","player");
							$("download").update(\'<a href="preview.wav.php/download_preview.wav?'  . $request .  $previewdata . '&download=true" onclick="sessiondata=false;">' . _L("Click here to download") . '</a>\');
						</script>');
			}
			return;
		}
	}
}

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
<script type="text/javascript" language="javascript" src="script/niftyplayer.js.php"></script>
<link href="css/messagegroup.css" type="text/css" rel="stylesheet">
<style type='text/css'>
#messageemptyspan {
	padding-top: 6px;
	display: block;
	clear: both;
	color: rgb(130,130,130);
}
</style>
<?
startWindow(_L('Message Viewer'));

if ($countdestinations > 0) {
	$preferreddestinationtype = isset($_GET['type']) && isset($destinations[$_GET['type']]) ? $_GET['type'] : reset(array_keys($destinations));
	$preferreddestinationsubtype = isset($_GET['subtype']) && in_array($_GET['subtype'], $destinations[$preferreddestinationtype]['subtypes']) ? $_GET['subtype'] : reset($destinations[$preferreddestinationtype]['subtypes']);
	if (isset($_GET['languagecode']) && isset($destinations[$preferreddestinationtype]['languages'][$_GET['languagecode']])) {
		$preferredlanguagecode = $_GET['languagecode'];
	} else {
		$preferredlanguagecode = $messagegroup->defaultlanguagecode;
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
				'countphonelanguages': <?=isset($destinations['phone']) ? count($destinations['phone']['languages']) : 0?>,
				'currentdestinationtype': '<?=$preferreddestinationtype?>',
				'currentsubtype': '<?=$preferreddestinationsubtype?>',
				'currentlanguagecode': '<?=$preferredlanguagecode?>',
				'defaultlanguagecode': '<?=$messagegroup->defaultlanguagecode?>',
				'messagegroupsummary': <?=json_encode(MessageGroup::getSummary($messagegroup->id))?>
			};
		
			var formswitchercontainer = $('messagegroupformcontainer');

			formswitchercontainer.observe('FormSplitter:BeforeTabLoad',
				messagegroupHandleBeforeTabLoad.bindAsEventListener(formswitchercontainer, state)
			);

			// When a tab is loaded, update the status icon of the previous tab.
			formswitchercontainer.observe('FormSplitter:TabLoaded',
				messagegroupHandleTabLoaded.bindAsEventListener(formswitchercontainer, state, '<?=$messagegroup->id?>', null, true)
			);
		
			form_init_splitter(formswitchercontainer, <?=json_encode($preferredtabs)?>, true);
		
			messagegroupStyleLayouts(true);
		})();

	</script>
	<?
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