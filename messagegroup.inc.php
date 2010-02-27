<?php
////////////////////////////////////////////////////////////////////////////////
// Custom Utility Functions
////////////////////////////////////////////////////////////////////////////////

// Returns an array structure accepted by FormSplitter's constructor for a child form.
function makeSummaryTab($destinations, $customerlanguages, $systemdefaultlanguagecode, $existingmessagegroup = null, $readonly = false) {
	// Table Headers
	$summaryheaders = '<th></th>';
	$summarylanguagerows = "";
	foreach ($destinations as $type => $destination) {
		foreach ($destination['subtypes'] as $subtype) {
			$summaryheaders .= "<th class='Destination'>" . ($type == 'sms' ? "SMS" : ucfirst($type)) . (count($destination['subtypes']) > 1 ? (" (" . ($subtype == 'html' ? "HTML" : ucfirst($subtype)) . ") ") : "") . "</th>";
		}
	}

	// Table Rows
	foreach ($customerlanguages as $languagecode => $languagename) {
		$summarylanguagerows .= "<tr><th class='Language'>" . ucfirst($languagename) . "</th>";
		foreach ($destinations as $type => $destination) {
			foreach ($destination['subtypes'] as $subtype) {
				if ($type == 'sms' && $languagecode != $systemdefaultlanguagecode) {
					$summarylanguagerows .= "<td>" . _L("N/A") . "</td>";
				} else {
					$hasmessage = !is_null($existingmessagegroup) && $existingmessagegroup->hasMessage($type, $subtype, $languagecode);
					$icon = $hasmessage ? 'img/icons/accept.gif' : 'img/icons/diagona/16/160.gif';
					$alt = $hasmessage ? escapehtml(_L("Message found.")) : escapehtml(_L("Message not found."));
					$title = _L("Click to jump to this message");
					$summarylanguagerows .= "<td class='StatusIcon'><img ".((!$readonly || $hasmessage) ? "class='StatusIcon'" : "")." id='{$type}-{$subtype}-{$languagecode}-summaryicon' title='$title' alt='$alt' src='$icon'/></td>";
				}
			}
		}
		$summarylanguagerows .= "</tr>";
	}

	// Returns an array structure accepted by FormSplitter's constructor for a child form.
	return array(
		"name" => "summary",
		"title" => "Summary",
		"icon" => "img/icons/application_view_columns.gif",
		"formdata" => array(
			'summary' => makeFormHtml(null, null,"<table>{$summaryheaders}{$summarylanguagerows}</table>")
		)
	);
}

function makeTranslationItem($messagegroup, $required, $type, $subtype, $languagecode, $languagename, $preferredgender, $sourcetext, $messagetext, $overrideplaintext, $translationcheckboxlabel, $override, $allowoverride = true, $hidetranslationcheckbox = false, $enabled = true, $disabledinfo = "", $datafields = null, $inautotranslator = false, $maximages = 10) {
	
	$control = array("TranslationItem",
		"phone" => $type == 'phone',
		"language" => $languagecode,
		"subtype" => $subtype,
		"reload" => true,
		"allowoverride" => $allowoverride,
		"usehtmleditor" => !$inautotranslator && $subtype == 'html',
		"escapehtml" => $subtype != 'html',
		"hidetranslationcheckbox" => $hidetranslationcheckbox,
		"hidetranslationlock" => true,
		"disabledinfo" => $disabledinfo,
		"translationcheckboxlabel" => $translationcheckboxlabel,
		"translationcheckboxnewline" => true,
		"editenglishtext" => !$inautotranslator,
		"editwhendisabled" => !$inautotranslator,
		"preferredgenderformitem" => "{$type}-{$subtype}-{$languagecode}_preferredgender",
		"showhr" => false
	);

	if (is_array($datafields))
		$control["fields"] = $datafields;

	$validators = array();

	if ($type == 'phone') {
		$validators[] = array("ValLength","max" => 4000);
	}
	if ($type == 'email' && !$inautotranslator) {
		$validators[] = array("ValEmailMessageBody");
		
		if ($subtype == 'plain') {
			$control["overrideplaintext"] = $overrideplaintext;
			$control["plaintextmessage"] = $messagetext;
		}
	}
	
	if (!$inautotranslator) {
		$validators[] = array("ValMessageBody",
			"translationitem" => true,
			"type" => $type,
			"subtype" => $subtype,
			"maximages" => $maximages,
		);
		
		$validators[] = array("ValDefaultMessage",
			"messagegroup" => $messagegroup,
			"languagecode" => $languagecode,
			"requesteddefaultlanguagecode" => $_SESSION["requesteddefaultlanguagecode"],
			"type" => $type,
			"subtype" => $subtype
		);
	} else {
		$control["transienttext"] = $messagetext;
	}
	
	$validators[] = array("ValTranslationItem", "required" => $required);

	if ($required)
		$validators[] = array("ValRequired");

	if (!$inautotranslator &&
		(($enabled && !$override && empty($sourcetext)) ||
			((!$enabled || $override) && empty($messagetext)))
	) {
		$value = "";
	} else {
		$value = json_encode(array(
			"enabled" => $enabled,
			"text" => $inautotranslator ? '' : $messagetext,
			"englishText" => $sourcetext,
			"override" => $override,
			"gender" => $preferredgender,
			"language" => $languagecode
		));
	}
	
	return array(
		"label" => _L('%s Message', ucfirst($languagename)),
		"value" => (!$inautotranslator && $type == 'email' && $subtype == 'plain' && !$overrideplaintext) ? "" : $value,
		"validators" => $validators,
		"control" => $control,
		"renderoptions" => array("icon" => false, "label" => false, "errormessage" => true),
		"transient" => false,
		"helpstep" => 2
	);
}

// $verticallabel and $fieldhelp are optional.
function makeFormHtml($verticallabel, $fieldhelp, $html, $for = null) {
	if ($verticallabel && $verticallabel !== "") {
		$html = '<label class="formlabel" for="' . $for . '">' . $verticallabel . '</label>' . $html;
	}
	
	return array(
		"label" => $verticallabel,
		"control" => array("FormHtml","html" => $html),
		"fieldhelp" => $fieldhelp,
		"renderoptions" => array("icon" => false, "label" => false, "errormessage" => true),
		"helpstep" => 1
	);
}

function makeBrandingFormHtml() {
	return makeFormHtml('', '', '
		<div id="branding" style="margin-top:20px">
			<div style="color: rgb(103, 103, 103);" class="gBranding"><span style="vertical-align: middle; font-family: arial,sans-serif; font-size: 11px;" class="gBrandingText">Translation powered by<img style="padding-left: 1px; vertical-align: middle;" alt="Google" src="' . (isset($_SERVER['HTTPS'])?"https":"http") . '://www.google.com/uds/css/small-logo.png"></span></div>
		</div>
	');
}

function makeMessageBody($messagegroup, $translationlanguages, $required, $type, $subtype, $languagecode, $label, $messagetext, $datafields = null, $usehtmleditor = false, $overrideplaintext = 0, $hideplaybutton = false, $hidden = false, $maximages = 10) {
	$control = array("MessageBody",
		"playbutton" => $type == 'phone' && !$hideplaybutton,
		"usehtmleditor" => $usehtmleditor,
		"hidden" => $hidden,
		"hidedatafieldsonload" => true,
		"language" => $languagecode,
		"preferredgenderformitem" => "{$type}-{$subtype}-{$languagecode}_preferredgender"
	);

	if (is_array($datafields))
		$control["fields"] = $datafields;

	$validators = array(
		array("ValMessageBody",
			"type" => $type,
			"subtype" => $subtype,
			"maximages" => $maximages
		),
		array ("ValDefaultMessage",
			"messagegroup" => $messagegroup,
			"languagecode" => $languagecode,
			"autotranslator" => $languagecode == "autotranslator",
			"requesteddefaultlanguagecode" => $_SESSION["requesteddefaultlanguagecode"],
			"translationlanguages" => $translationlanguages,
			"type" => $type,
			"subtype" => $subtype
		)
	);

	if ($type == 'phone') {
		$validators[] = array("ValLength","max" => 4000);
	}
	if ($type == 'email') {
		$validators[] = array("ValEmailMessageBody");
		
		if ($subtype == 'plain') {
			$control["overrideplaintext"] = $overrideplaintext;
			$control["plaintextmessage"] = $messagetext;
		}
	}
	
	if ($required)
		$validators[] = array("ValRequired");

	return array(
		"label" => $label,
		"value" => ($type == 'email' && $subtype == 'plain' && !$overrideplaintext) ? "" : $messagetext,
		"validators" => $validators,
		"control" => $control,
		"transient" => false,
		"renderoptions" => array("icon" => false, "label" => false, "errormessage" => true),
		"helpstep" => 2
	);
}

function makeAccordionSplitter($type, $subtype, $languagecode, $permanent, $preferredgender, $inautotranslator = false, $emailattachments = null, $allowtranslation = false, $messagegroup = null, $multilingual = false) {
	global $USER;

	$formname = "{$type}-{$subtype}-{$languagecode}";
	
	$accordionsplitterchildren = array();
	
	if ($type == 'email') {
		$accordionsplitterchildren[] = array(
			"title" => _L("Attachments"),
			"icon" => "img/icons/diagona/16/190.gif",
			"formdata" =>  array(
				"attachmentsheader" => makeFormHtml(_L("Attachments"), null, '', "{$formname}_attachments"),
				"attachments" => array(
					"label" => _L('Attachments'),
					"fieldhelp" => "You may attach up to three files that are up to 2048kB each. Note: Some recipients may have different size restrictions on incoming mail which can cause them to not receive your message if you have attached large files.",
					"value" => $emailattachments ? $emailattachments : '',
					"validators" => array(array("ValEmailAttach")),
					"control" => array("EmailAttach"),
					"renderoptions" => array("icon" => false, "label" => false, "errormessage" => true),
					"helpstep" => 3
				),
				"attachmentsjavascript" => makeFormHtml(null, null,"
					<script type='text/javascript'>
						(function () {
							var attachmentsformitemname = '{$type}-{$subtype}-{$languagecode}_attachments';
							var attachmentsformitem = $(attachmentsformitemname);
							var form = attachmentsformitem.up('form');

							attachmentsformitem.observe('Form:ValidationDisplayed', function(event) {
								var formvars = document.formvars[this.identify()];

								if (event.memo.style == 'error')
									formvars.preventAccordionClosing = true;
								else
									formvars.preventAccordionClosing = false;
							}.bindAsEventListener(form));

							form.observe('Accordion:ClickTitle', function(event) {
								var formvars = document.formvars[this.identify()];
								var currentSection = event.memo.currentSection;
								var sectionobject = event.memo.widget.sections[currentSection];
								if (!sectionobject)
									return;
								var contentDiv = sectionobject.contentDiv;
								if (contentDiv && contentDiv.down('#' + attachmentsformitemname)) {
									if (formvars.preventAccordionClosing && !confirm('".addslashes(_L('There are errors on this form, are you sure you want to continue?'))."'))
										event.stop();
								}
							}.bindAsEventListener(form));
						})();
					</script>
				")
			)
		);
	} else if ($type == 'phone') {
		if (!$inautotranslator) {
			if ($messagegroup && !$messagegroup->deleted) {
				$preferredaudiofilename = $messagegroup->name;
			} else {
				$preferredaudiofilename = 'Call Me to Record';
			}
			
			// If there are multiple languages for this destination type, append the language name.					
			if ($multilingual) {
				$preferredaudiofilename .= ' - ' . Language::getName($languagecode);
			}
			
			$callmelabeltext = _L('Voice Recording');
			
			if ($USER->authorize('starteasy')) {
				$callmeformdata = array(
					"callmelabel" => makeFormHtml($callmelabeltext, null,'', "{$type}-{$subtype}-{$languagecode}_callme"),
					"callme" => array(
						"label" => $callmelabeltext,
						"value" => "",
						"fieldhelp" => _L("Enter your phone number and press Call Me to Record. The recorded audio file will appear in the Audio Library below."),
						"validators" => array(
							array('ValCallMeMessage')
						),
						"control" => array(
							"CallMe",
							"phone" => Phone::format($USER->phone),
							"preferredaudiofilename" => $preferredaudiofilename
						),
						"renderoptions" => array(
							"icon" => false,
							"label" => false,
							"errormessage" => true
						),
						"helpstep" => 1
					)
				);
				
				// observe the callme container element for EasyCall events
				$callmeobserver = "$('{$type}-{$subtype}-{$languagecode}_callme_content').observe('EasyCall:update', function(event) {
						new Ajax.Request('ajaxmessagegroup.php', {
							'method': 'post',
							'parameters': {
								'action': 'assignaudiofile',
								'messagegroupid': {$_SESSION['messagegroupid']},
								'audiofileid': event.memo.audiofileid
							},
							'onSuccess': function(transport) {
								var audiofilename = transport.responseJSON;
								
								// if success
								if (audiofilename) {
									textInsert('{{' + audiofilename + '}}', getAudioTextarea());
									this.reload();
									
								// if failed the action but success on the ajax request
								} else {
									alert('" . addslashes(_L('An error occured while trying to save your audio.\nPlease try again.')) . "');
								}
								
								// create a new EasyCall to record another audio file if desired
								newEasyCall();
							}.bindAsEventListener(this),
							'onFailure': function() {
								alert('" . addslashes(_L('An error occured while trying to save your audio.\nPlease try again.')) . "');
								newEasyCall();
							}.bindAsEventListener(this)
						});
					}.bindAsEventListener(audiolibrarywidget));
				";
			} else {
				$callmeformdata = array();
				$callmeobserver = "";
			}
			
			$audiouploadlabeltext = _L('Audio Upload');
			
			$accordionsplitterchildren[] = array(
				"title" => _L("Audio"),
				"icon" => 'img/icons/fugue/microphone.gif',
				"formdata" =>  array_merge($callmeformdata, array(
					"audiouploadlabel" => makeFormHtml(_L($audiouploadlabeltext), null, '', "{$type}-{$subtype}-{$languagecode}_audioupload"),
					"audioupload" => array(
						"label" => $audiouploadlabeltext,
						'fieldhelp' => _L('Upload an audio file. It will appear in the Audio Library below.'),
						"value" => '',
						"validators" => array(), // uploadaudio.php does custom validation, and messagepart will validate audio inserts.
						"control" => array("AudioUpload"),
						"renderoptions" => array("icon" => false, "label" => false, "errormessage" => true),
						"helpstep" => 3
					),
					"audiolibrary" => makeFormHtml(_L("Audio Library"), _L("fieldhelp"),"
						<div id=\"audiolibrarycontainer\" style=\"border:1px dotted gray; padding-bottom: 10px\"></div>
						<script type='text/javascript'>
							(function () {
								var audiolibrarywidget = new AudioLibraryWidget('audiolibrarycontainer', {$_SESSION['messagegroupid']});

								var audiouploadformitem = $('{$type}-{$subtype}-{$languagecode}_audioupload');

								var getAudioTextarea = function () {
									var audiotextareaid = '" . ($allowtranslation ? "{$type}-{$subtype}-{$languagecode}_translationitem" : "{$type}-{$subtype}-{$languagecode}_nonemessagebody") . "';
									
									var sourcetextarea = $(audiotextareaid + 'englishText');
									if (sourcetextarea) {
										if (sourcetextarea.up('.MessageBodyContainer').visible())
											return sourcetextarea;
										else
											return $(audiotextareaid + 'text');
									} else {
										return $(audiotextareaid);
									}
								};

								var translationcheckbox = $('{$type}-{$subtype}-{$languagecode}_translationitem' + 'translatecheck');
								
								var observetranslationaccordion = function(accordion, translationcheckbox) {
									var audiosection = accordion.get_section_containing('audiolibrarycontainer');
									if (audiosection) {
										if (translationcheckbox) {
											translationcheckbox.observe('click', function(event, accordion, audiosection) {
												if (event.element().checked)
													accordion.lock_section(audiosection);
												else
													accordion.unlock_section(audiosection);
											}.bindAsEventListener(translationcheckbox, accordion, audiosection));

											if (translationcheckbox.checked)
												accordion.lock_section(audiosection);
											else
												accordion.show_section(audiosection, true);
										} else {
											accordion.show_section(audiosection, true);
										}
									}
								};

								var form = audiouploadformitem.up('form');

								form.observe('FormSplitter:AccordionLoaded', function(event, translationcheckbox) {
									var formvars = document.formvars[this.name];

									observetranslationaccordion(formvars.accordion, translationcheckbox);
								}.bindAsEventListener(form, translationcheckbox));

								// If the accordion was loaded before the form got a chance to observe, then go ahead and do the same thing as what the FormSplitter:AccordionLoaded handler would've done.
								if (document.formvars && document.formvars[form.name] && document.formvars[form.name].accordion) {
									var formvars = document.formvars[form.name];
									observetranslationaccordion(formvars.accordion, translationcheckbox);
								}
								
								audiouploadformitem.observe('AudioUpload:AudioUploaded', function(event) {
									hideHtmlEditor();
									var audiofile = event.memo;

									textInsert('{{' + audiofile.name + '}}', getAudioTextarea());
									this.reload();
								}.bindAsEventListener(audiolibrarywidget));

								audiolibrarywidget.container.observe('AudioLibraryWidget:ClickInsert', function(event) {
									hideHtmlEditor();
									var audiofile = event.memo.audiofile;

									textInsert('{{' + audiofile.name + '}}', getAudioTextarea());
								}.bindAsEventListener(audiolibrarywidget));

								$callmeobserver
							})();
						</script>
					", "{$type}-{$subtype}-{$languagecode}_audiolibrary")
				))
			);
		}
	} else if ($type != 'sms') {
		return null;
	}
	
	if ($type == 'email' || $type == 'phone') {
		$accordionsplitterchildren[] = array(
			"title" => _L("Data Fields"),
			"icon" => 'img/icons/fugue/arrow_turn_180.gif',
			"formdata" =>  array(
				"datafields" => makeFormHtml(_L("Data Fields"), _L("fieldhelp"),"
					<div id='accordiondatafieldscontainer'></div>
					<script type='text/javascript'>
						(function() {
							var container = $('accordiondatafieldscontainer');
							var datafieldstable = $$('.DataFieldsTable');
							for (var i = 0, count = datafieldstable.length; i < count; i++) {
								container.insert(datafieldstable[i]);
								if (count == 1)
									datafieldstable[i].show();
							}
						})();
					</script>
				")
			)
		);

		if ($allowtranslation && !$inautotranslator) {
			$accordionsplitterchildren[] = array(
				"title" => _L("Translation"),
				"icon" => "img/icons/world.gif",
				"formdata" =>  array(
					"translation" => makeFormHtml(_L("Translation"), _L("fieldhelp"),"
						<table style='width:100%'><tbody><tr id='accordiontranslationtr'></tr></tbody></table>
						<script type='text/javascript'>
							(function() {
								var tr = $('accordiontranslationtr');
								var tds = $$('.TranslationItemCheckboxTD');
								for (var i = 0; i < tds.length; i++) {
									tr.insert(tds[i].show());
								}
							})();
						</script>
					")
				)
			);
		}
	}

	$autoexpirevalues = array(0 => "Yes (Keep for ". getSystemSetting('softdeletemonths', "6") ." months)",1 => "No (Keep forever)");
	
	$autoexpirelabeltext = _L('Auto Expire');
	$advancedoptionsformdata = array(
		"autoexpirelabel" => makeFormHtml(_L("Auto Expire"), null,'', "{$type}-{$subtype}-{$languagecode}_autoexpire"),
		"autoexpire" => array(
			"label" => $autoexpirelabeltext,
			"value" => $permanent,
			"validators" => array(
				array("ValInArray", "values" => array_keys($autoexpirevalues))
			),
			"fieldhelp" => _L('Selecting Yes will allow the system to delete this message after %1$s months if it is not associated with any active jobs.', getSystemSetting('softdeletemonths', "6")),
			"control" => array("RadioButton", "values" => $autoexpirevalues),
			"renderoptions" => array("label" => false, "icon" => false, "errormessage" => true),
			"helpstep" => 1
		)
	);

	if ($type == 'phone') {
		$gendervalues = array ("female" => "Female","male" => "Male");
		$preferredgenderlabeltext = _L('Preferred Voice');
		$advancedoptionsformdata['preferredgenderlabel'] = makeFormHtml($preferredgenderlabeltext, _L("fieldhelp"),'', "{$type}-{$subtype}-{$languagecode}_preferredgender");
		$advancedoptionsformdata['preferredgender'] = array(
			"label" => $preferredgenderlabeltext,
			"fieldhelp" => _L('Choose the gender of the text-to-speech voice.'),
			"value" => $preferredgender,
			"validators" => array(
				array("ValInArray", "values" => array_keys($gendervalues))
			),
			"control" => array("RadioButton","values" => $gendervalues),
			"renderoptions" => array("label" => false, "icon" => false, "errormessage" => true),
			"helpstep" => 2
		);
	}

	$accordionsplitterchildren[] = array("title" => _L("Advanced Options"), "icon" => "img/icons/diagona/16/041.gif", "formdata" => $advancedoptionsformdata);
	$accordionsplitter = new FormSplitter("", "", null, "vertical", array(),
		array(
			array(
				'title' => '',
				'formdata' => array(makeFormHtml(null, null,'
					<div style="float:right">'
					. button(_L('Show Tools'), NULL, NULL, ' id="showaccordiontools" style="display:none" ')
					. button(_L('Hide Tools'), NULL, NULL, ' id="hideaccordiontools" ')
					. '<div style="clear:both"></div>
					</div>
					
					<div style="clear:both"></div>
					
					<script type="text/javascript">
						(function () {
							var formname = "'.$formname.'";
							var form = $(formname);
							
							// Helper function for getting the accordion container.
							function showAccordionContainer() {
								var accordionsplitpane = form.down("td.SplitPane", 1);
								
								if (accordionsplitpane) {
									accordionsplitpane.down(".accordion").show();
									accordionsplitpane.style.width = "45%";
								}
								
								document.stopObserving("FormSplitter:AllLayoutLoaded");
							}
							
							// Observe each button for a click; when a button is clicked, hide itself and show the other button. Then set the visibility of the accordion container appropriately.
							
							$("hideaccordiontools").observe("click", function(event) {
								event.stop(); // In Safari, event.element() is a TD within the button.
								
								$("hideaccordiontools").hide();
								$("showaccordiontools").show();
								
								var accordionsplitpane = form.down("td.SplitPane", 1);
								
								if (accordionsplitpane) {
									accordionsplitpane.down(".accordion").hide();
									accordionsplitpane.style.width = "110px";
								}
							});
							
							$("showaccordiontools").observe("click", function(event) {
								event.stop(); // In Safari, event.element() is a TD within the button.
								
								$("hideaccordiontools").show();
								$("showaccordiontools").hide();
								
								showAccordionContainer();
							});
							
							// In case "FormSplitter:AllLayoutLoaded" had already fired.
							showAccordionContainer();
							
							// In case "FormSplitter:AllLayoutLoaded" has yet to fire.
							document.observe("FormSplitter:AllLayoutLoaded", showAccordionContainer);
						})();
					</script>
				'))
			),
			new FormSplitter("", "", null, "accordion", array(), $accordionsplitterchildren)
		)
	);

	return $accordionsplitter;
}

////////////////////////////////////////////////////////////////////////////////
// Custom Form Items
////////////////////////////////////////////////////////////////////////////////
class CallMe extends FormItem {
	function render ($value) {
		$n = $this->form->name."_".$this->name;

		// Hidden input item to store values in
		$str = '<input id="'.$n.'" name="'.$n.'" type="hidden" value="{}" />';

		// set up easycall stylesheet
		$str .= '
		<style type="text/css">
		.easycallcallprogress {
			float:left;
		}
		.easycallunderline {
			padding-top: 3px;
			margin-bottom: 5px;
			border-bottom:
			1px solid gray;
			clear: both;
		}
		.easycallphoneinput {
			margin-bottom: 5px;
			border: 1px solid gray;
		}

		.messagegroupcontent {
			padding: 6px;
			white-space:nowrap
		}
		</style>';

		$str .= '
		<div>
			<div id="'.$n.'_content" class="messagegroupcontent"></div>
		</div>
		';

		return $str;
	}

	function renderJavascriptLibraries() {
		// include the easycall javascript object and setup to record
		$str = '<script type="text/javascript" src="script/easycall.js.php"></script>';
		return $str;
	}

	function renderJavascript($value) {
		$n = $this->form->name."_".$this->name;

		$defaultphone = escapehtml(Phone::format($this->args['phone']));
		
		return '
			function newEasyCall() {
				// remove any existing content
				$("'.$n.'_content").update();
				// Create an EasyCall!
				new EasyCall(
					"'.$n.'",
					"'.$n.'_content'.'",
					"'.$defaultphone.'",
					"'.addslashes($this->args['preferredaudiofilename']).'"
				);
			};
			newEasyCall();
		';
	}
}

////////////////////////////////////////////////////////////////////////////////
// Custom Validators
////////////////////////////////////////////////////////////////////////////////
class ValCallMeMessage extends Validator {
	var $onlyserverside = true;
	function validate ($value, $args) {
		global $USER;
		if (!$USER->authorize("starteasy"))
			return _L("%s is not allowed for this user account", $this->label);
		$values = json_decode($value);
		return true;
	}
}

class ValEmailMessageBody extends Validator {
	var $onlyserverside = true;
	function validate ($value, $args) {
		if (!isset($_SESSION['emailheaders']))
			return false;
		// It is an error if any of the headers are blank.
		foreach ($_SESSION['emailheaders'] as $headervalue) {
			$headervalue = trim($headervalue);
			
			if (empty($headervalue))
				return _L('Email headers are incomplete. Please fill in the Subject, From Name, and From Email.');
		}
		return true;
	}
}

class ValTranslationItem extends Validator {
	var $onlyserverside = true;
	function validate ($value, $args) {
		$msgdata = json_decode($value);

		if (!empty($args['required'])) {
			if (($msgdata->enabled && !$msgdata->override && empty($msgdata->englishText)) || ((!$msgdata->enabled || $msgdata->override) && empty($msgdata->text))) {
				return _L('%s is required.', $this->label);
			}
		}
		return true;
	}
}

?>