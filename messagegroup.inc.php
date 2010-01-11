<?php
////////////////////////////////////////////////////////////////////////////////
// Custom Utility Functions
////////////////////////////////////////////////////////////////////////////////
function makeTranslationItem($required, $type, $subtype, $languagecode, $languagename, $sourcetext, $messagetext, $translationcheckboxlabel, $override, $allowoverride = true, $hidetranslationcheckbox = false, $enabled = true, $disabledinfo = "", $datafields = null, $inautotranslator = false, $maximages = 3) {
	$control = array("TranslationItem",
		"phone" => $type == 'phone',
		"language" => $languagecode,
		"englishText" => $sourcetext,
		"multilingual" => $type != 'sms',
		"subtype" => $subtype,
		"reload" => true,
		"allowoverride" => $allowoverride,
		"usehtmleditor" => $subtype == 'html',
		"hidetranslationcheckbox" => $hidetranslationcheckbox,
		"hidetranslationlock" => true,
		"disabledinfo" => $disabledinfo,
		"translationcheckboxlabel" => $translationcheckboxlabel,
		"translationcheckboxnewline" => true,
		"editenglishtext" => !$inautotranslator,
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
	}
	
	$validators[] = array("ValMessageBody", "type" => $type, "subtype" => $subtype, "languagecode" => $languagecode, "maximages" => $maximages);
	
	if ($required)
		$validators[] = array("ValRequired");

	return array(
		"label" => ucfirst($languagename),
		"value" => json_encode(array(
			"enabled" => $enabled,
			"text" => $messagetext,
			"englishText" => $sourcetext,
			"override" => $override,
			"gender" => 'female' // TODO: This needs to take preferredvoice.
		)),
		"validators" => $validators,
		"control" => $control,
		"renderoptions" => array("icon" => false, "label" => false, "errormessage" => true),
		"transient" => false,
		"helpstep" => 2
	);
}

function makeFormHtml($html) {
	return array(
		"label" => "",
		"control" => array("FormHtml","html" => $html),
		"renderoptions" => array("icon" => false, "label" => false, "errormessage" => true),
		"helpstep" => 1
	);
}

function makeMessageBody($required, $type, $subtype, $languagecode, $label, $messagetext, $datafields = null, $usehtmleditor = false, $hideplaybutton = false, $hidden = false, $maximages = 3) {
	$control = array("MessageBody",
		"playbutton" => $type == 'phone' && !$hideplaybutton,
		"usehtmleditor" => $usehtmleditor,
		"hidden" => $hidden,
		"hidedatafieldsonload" => true
	);

	if (is_array($datafields))
		$control["fields"] = $datafields;

	$validators = array(array("ValMessageBody", "type" => $type, "subtype" => $subtype, "languagecode" => $languagecode, "maximages" => $maximages));

	if ($type == 'phone') {
		$validators[] = array("ValLength","max" => 4000);
	}
	if ($type == 'email') {
		$validators[] = array("ValEmailMessageBody");
	}
	
	if ($required)
		$validators[] = array("ValRequired");

	return array(
		"label" => $label,
		"value" => $messagetext,
		"validators" => $validators,
		"control" => $control,
		"transient" => false,
		"renderoptions" => array("icon" => false, "label" => false, "errormessage" => true),
		"helpstep" => 2
	);
}

function makeAccordionSplitter($type, $subtype, $languagecode, $permanent, $preferredgender, $inautotranslator = false, $emailattachments = null, $allowtranslation = false) {
	global $USER;

	$accordionsplitterchildren = array();

	if ($type == 'email') {
		$accordionsplitterchildren[] = array(
			"title" => _L("Attachments"),
			"formdata" =>  array(
				"attachments" => array(
					"label" => _L('Attachments'),
					"fieldhelp" => "You may attach up to three files that are up to 2048kB each. Note: Some recipients may have different size restrictions on incoming mail which can cause them to not receive your message if you have attached large files.",
					"value" => $emailattachments ? $emailattachments : '',
					"validators" => array(array("ValEmailAttach")),
					"control" => array("EmailAttach","size" => 30, "maxlength" => 51),
					"renderoptions" => array("icon" => false, "label" => false, "errormessage" => true),
					"helpstep" => 3
				)
			)
		);
	} else if ($type == 'phone') {
		if (!$inautotranslator) {
			$callmeformdata = !$USER->authorize('starteasy') ? array() : array(
				"callme" => array(
					"label" => _L('Voice Recording'),
					"value" => "",
					"validators" => array(
						array('ValCallMeMessage')
					),
					"control" => array(
						"CallMe",
						"phone" => Phone::format($USER->phone),
						"langcode" => $languagecode
					),
					"renderoptions" => array(
						"icon" => false,
						"label" => false,
						"errormessage" => true
					),
					"helpstep" => 1
				)
			);
			
			$accordionsplitterchildren[] = array(
				"title" => _L("Audio"),
				"formdata" =>  array_merge($callmeformdata, array(
					"audioupload" => array(
						"label" => _L('Audio Upload'),
						"fieldhelp" => "You may attach up to three files that are up to 2048kB each. Note: Some recipients may have different size restrictions on incoming mail which can cause them to not receive your message if you have attached large files.",
						"value" => '',
						"validators" => array(),
						"control" => array("AudioUpload", "size" => 30, "maxlength" => 51),
						"renderoptions" => array("icon" => false, "label" => false, "errormessage" => true),
						"helpstep" => 3
					),
					"audiolibrary" => makeFormHtml("
						<div id='audiolibrarycontainer'></div>
						<script type='text/javascript'>
							(function () {
								var audiolibrarywidget = new AudioLibraryWidget('audiolibrarycontainer', {$_SESSION['messagegroupid']});
								var audiotextarea = '" . ($allowtranslation ? "{$type}-{$subtype}-{$languagecode}_translationitemtext" : "{$type}-{$subtype}-{$languagecode}_nonemessagebody") . "';

								var audiouploadformitem = $('{$type}-{$subtype}-{$languagecode}_audioupload');
								audiouploadformitem.observe('AudioUpload:AudioUploaded', function(event) {
									hideHtmlEditor();
									var audiofile = event.memo;
									textInsert('{{' + audiofile.name + '}}', $(audiotextarea));
									this.reload();
								}.bindAsEventListener(audiolibrarywidget, audiotextarea));

								audiolibrarywidget.container.observe('AudioLibraryWidget:ClickName', function(event) {
									hideHtmlEditor();
									var audiofile = event.memo.audiofile;
									textInsert('{{' + audiofile.name + '}}', $(audiotextarea));
								}.bindAsEventListener(audiolibrarywidget, audiotextarea));

								// observe the callme container element for EasyCall events
								$('{$type}-{$subtype}-{$languagecode}_callme_content').observe('EasyCall:update', function(event, audiotextarea) {
									new Ajax.Request('ajaxmessagegroup.php', {
										'method': 'post',
										'parameters': {
											'action': 'assignaudiofile',
											'messagegroupid': {$_SESSION['messagegroupid']},
											'audiofileid': event.memo.audiofileid
										},
										'onSuccess': function(transport, audiofilename, audiotextarea) {
											// if success
											if (transport.responseJSON) {
												textInsert('{{' + audiofilename + '}}', $(audiotextarea));
												this.reload();

											// if failed the action but success on the ajax request
											} else {
												alert('" . escapehtml(_L('An error occured while trying to save your audio.\nPlease try again.')) . "');
											}

											// create a new EasyCall to record another audio file if desired
											newEasyCall();

										}.bindAsEventListener(this, event.memo.audiofilename, audiotextarea),
										'onFailure': function() {
											alert('" . escapehtml(_L('An error occured while trying to save your audio.\nPlease try again.')) . "');
											newEasyCall();
										}.bindAsEventListener(this)
									});
								}.bindAsEventListener(audiolibrarywidget, audiotextarea));

							})();
						</script>
					")
				))
			);
		}
	} else if ($type == 'sms') {

	} else {
		return null;
	}

	if ($type == 'email' || $type == 'phone') {
		$accordionsplitterchildren[] = array(
			"title" => _L("Data Fields"),
			"formdata" =>  array(
				"datafields" => makeFormHtml("
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
				"formdata" =>  array(
					"translation" => makeFormHtml("
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
	$advancedoptionsformdata = array(
		"autoexpire" => array(
			"label" => _L('Auto Expire'),
			"value" => $permanent,
			"validators" => array(
				array("ValInArray", "values" => array_keys($autoexpirevalues))
			),
			"control" => array("RadioButton", "values" => $autoexpirevalues),
			"helpstep" => 1
		)
	);

	if ($type == 'phone') {
		$gendervalues = array ("Female" => "Female","Male" => "Male");
		$advancedoptionsformdata['preferredgender'] = array(
			"label" => _L('Preferred Voice'),
			"fieldhelp" => _L('Choose the gender of the text-to-speech voice.'),
			"value" => ucfirst($preferredgender),
			"validators" => array(
				array("ValInArray", "values" => array_keys($gendervalues))
			),
			"control" => array("RadioButton","values" => $gendervalues),
			"helpstep" => 2
		);
	}

	$accordionsplitterchildren[] = array("title" => _L("Advanced Options"), "formdata" => $advancedoptionsformdata);
	$accordionsplitter = new FormSplitter("", "", null, "accordion", array(), $accordionsplitterchildren);

	return $accordionsplitter;
}

////////////////////////////////////////////////////////////////////////////////
// Custom Form Items
////////////////////////////////////////////////////////////////////////////////
class CallMe extends FormItem {
	function render ($value) {
		$n = $this->form->name."_".$this->name;
		$nophone = _L("Phone Number");
		$defaultphone = escapehtml((isset($this->args['phone']) && $this->args['phone'])?Phone::format($this->args['phone']):$nophone);
		if (!$value)
			$value = '{}';
		// Hidden input item to store values in
		$str = '<input id="'.$n.'" name="'.$n.'" type="hidden" value="'.escapehtml($value).'" />
		<div>
			<div id="'.$n.'_content" style="padding: 6px; white-space:nowrap"></div>
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

		$nophone = _L("Phone Number");
		$defaultphone = escapehtml((isset($this->args['phone']) && $this->args['phone'])?Phone::format($this->args['phone']):$nophone);
		if (!$value)
			$value = '{}';

		return '
			function newEasyCall() {
				// remove any existing content
				$("'.$n.'_content").update();
				// Create an EasyCall!
				new EasyCall(
					"'.$this->form->name.'",
					"'.$n.'",
					"'.$n.'_content'.'",
					"'.$defaultphone.'",
					"'.Language::getName($this->args['langcode']).' - " + curDate()
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

?>
