<?php
////////////////////////////////////////////////////////////////////////////////
// Custom Utility Functions
////////////////////////////////////////////////////////////////////////////////
function makeTranslationItem($type, $subtype, $languagecode, $languagename, $sourcetext, $messagetext, $translationlanguages, $override, $allowoverride = true, $hidetranslationcheckbox = false, $enabled = true, $disabledinfo = "", $datafields = null) {
	$control = array("TranslationItem",
		"phone" => $type == 'phone',
		"language" => $languagecode, // TODO: Update TranslationItem to take languagecode; note: the languagename must be the one used by translate.php
		"englishText" => $sourcetext,
		"multilingual" => $type != 'sms',
		"subtype" => $subtype,
		"reload" => true,
		"allowoverride" => $allowoverride,
		"usehtmleditor" => $subtype == 'html',
		"hidetranslationcheckbox" => $hidetranslationcheckbox,
		"hidetranslationlock" => true,
		"disabledinfo" => $disabledinfo,
		"showhr" => false
	);
	
	if (is_array($datafields))
		$control["fields"] = $datafields;
	
	return array(
		"label" => ucfirst($languagename),
		"value" => json_encode(array(
			"enabled" => $enabled,
			"text" => $messagetext,
			"override" => $override,
			"gender" => 'female' // TODO: This needs to take preferredvoice.
		)),
		"validators" => array(),
		"control" => $control,
		"renderoptions" => array("icon" => false, "label" => false, "errormessage" => true),
		"transient" => false,
		"helpstep" => 2
	);
}

function makeFormHtml($html) {
	return array(
		"label" => "",
		"value" => "",
		"validators" => array(),
		"control" => array("FormHtml","html" => $html),
		"renderoptions" => array("icon" => false, "label" => false, "errormessage" => true),
		"helpstep" => 1
	);
}

function makeMessageBody($type, $label, $messagetext, $datafields = null, $usehtmleditor = false, $hideplaybutton = false, $hidden = false) {
	$control = array("MessageBody",
		"playbutton" => $type == 'phone' && !$hideplaybutton,
		"usehtmleditor" => $usehtmleditor,
		"hidden" => $hidden
	);
	
	if (is_array($datafields))
		$control["fields"] = $datafields;
	
	return array(
		"label" => $label,
		"value" => $messagetext,
		"validators" => array(),
		"control" => $control,
		"transient" => false,
		"renderoptions" => array("icon" => false, "label" => false, "errormessage" => true),
		"helpstep" => 2
	);
}
			
function makeAccordionSplitter($type, $subtype, $languagecode, $permanent, $preferredgender, $inautotranslator = false, $emailattachments = null, $allowtranslation = false, $existingmessagegroupid = 0) {
	global $USER;
	
	$existingmessagegroupid = !empty($existingmessagegroupid) ? $existingmessagegroupid + 0 : 0;
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
			$accordionsplitterchildren[] = array(
				"title" => _L("Audio"),
				"formdata" =>  array(
					"callme" => array(
						"label" => _L('Voice Recording'),
						"value" => "",
						"validators" => array(
							array('ValCallMeMessage')
						),
						"control" => array(
							"CallMe",
							"phone" => Phone::format($USER->phone),
							"max" => getSystemSetting('easycallmax',10),
							"min" => getSystemSetting('easycallmin',10)
						),
						"renderoptions" => array(
							"icon" => false,
							"label" => false,
							"errormessage" => true
						),
						"helpstep" => 1
					),
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
								var audiolibrarywidget = new AudioLibraryWidget('audiolibrarycontainer', $existingmessagegroupid);
								
								var audiouploadformitem = $('{$type}-{$subtype}-{$languagecode}_audioupload');
								audiouploadformitem.observe('AudioUpload:AudioUploaded', function(event) {
									hideHtmlEditor();
									console.info('audio uploaded');
									var audiofile = event.memo;
									console.info(audiofile);
									textInsert('{{' + audiofile.name + '}}', $('{$type}-{$subtype}-{$languagecode}_messagebody'));
									this.reload();
								}.bindAsEventListener(audiolibrarywidget));
								
								audiolibrarywidget.container.observe('AudioLibraryWidget:ClickName', function(event) {
									hideHtmlEditor();
									var audiofile = event.memo.audiofile;
									textInsert('{{' + audiofile.name + '}}', $('{$type}-{$subtype}-{$languagecode}_messagebody'));
								}.bindAsEventListener(audiolibrarywidget));
								
								var callmeformitem = $('{$type}-{$subtype}-{$languagecode}_callme');
								callmeformitem.observe('Easycall:RecordingDone', function(event) {
									hideHtmlEditor();
									//textInsert('{{' + audiofile.name + '}}', $('{$type}-{$subtype}-{$languagecode}_messagebody'));
									this.reload();
								}.bindAsEventListener(audiolibrarywidget));
							})();
						</script>
					")
				)
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
							for (var i = 0; i < datafieldstable.length; i++) {
								container.insert(datafieldstable[i]);
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
						<table><tbody><tr id='accordiontranslationtr'></tr></tbody></table>
						<script type='text/javascript'>
							(function() {
								var tr = $('accordiontranslationtr');
								var tds = $$('.TranslationItemCheckboxTD');
								for (var i = 0; i < tds.length; i++) {
									tr.insert(tds[i]);
								}
							})();
						</script>
					")
				)
			);
		}
	}
	
	$advancedoptionsformdata = array(
		"autoexpire" => array(
			"label" => _L('Auto Expire'),
			"value" => $permanent,
			"validators" => array(
				// TODO: ValInArray().
			),
			"control" => array("RadioButton", "values" => array(0 => "Yes (Keep for ". getSystemSetting('softdeletemonths', "6") ." months)",1 => "No (Keep forever)")),
			"helpstep" => 1
		)
	);
	
	if ($type == 'phone') {
		$advancedoptionsformdata['preferredgender'] = array(
			"label" => _L('Preferred Voice'),
			"fieldhelp" => _L('Choose the gender of the text-to-speech voice.'),
			"value" => ucfirst($preferredgender),
			"validators" => array(),
			"control" => array("RadioButton","values" => array ("Female" => "Female","Male" => "Male")),
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
			<div id="'.$n.'_messages" style="padding: 6px; white-space:nowrap"></div>
			<div id="'.$n.'_altlangs" style="clear: both; padding: 5px; display: none"></div>
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
			(function () {
				var msgs = '.$value.';
				// Load default. it is a special case
				new Easycall(
					"'.$this->form->name.'",
					"'.$n.'",
					"Default",
					"'.((isset($this->args['min']) && $this->args['min'])?$this->args['min']:"10").'",
					"'.((isset($this->args['max']) && $this->args['max'])?$this->args['max']:"10").'",
					"'.$defaultphone.'",
					"'.$nophone.'",
					"CallMe",
					"easycall"
				).load();
			})();
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
			return "$this->label "._L("is not allowed for this user account");
		$values = json_decode($value);
		return true;/*
		if ($value == "{}")
			return "$this->label "._L("has messages that are not recorded");
		if (!$values->Default)
			return "$this->label "._L("has messages that are not recorded");
		$msg = new Message($values->Default +0);
		if ($msg->userid !== $USER->id)
			return "$this->label "._L("has invalid message values");*/
		return true;
	}
}

?>