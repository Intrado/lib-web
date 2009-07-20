<?
////////////////////////////////////////////////////////////////////////////////
// Custom Form Item Definitions
////////////////////////////////////////////////////////////////////////////////

class TextAreaPhone extends FormItem {
	function render ($value) {
		$n = $this->form->name."_".$this->name;
		if (!$value)
			$value = '{"gender": "female", "text": ""}';
		$vals = json_decode($value);
		$rows = isset($this->args['rows']) ? 'rows="'.$this->args['rows'].'"' : "";
		$str = '<input id="'.$n.'" name="'.$n.'" type="hidden" value="'.escapehtml($value).'"/>
			<textarea id="'.$n.'-textarea" style="clear:both; width:'.$this->args['width'].'" name="'.$n.'-textarea" '.$rows.'/>'.escapehtml($vals->text).'</textarea>
			<div>
				<input id="'.$n.'-female" name="'.$n.'-gender" type="radio" value="female" '.($vals->gender == "female"?"checked":"").'/><label for="'.$n.'-female">'._L('Female').'</label><br />
				<input id="'.$n.'-male" name="'.$n.'-gender" type="radio" value="male" '.($vals->gender == "male"?"checked":"").'/><label for="'.$n.'-male">'._L('Male').'</label><br />
			</div>
			<div>'.icon_button(_L("Play"),"play",null,null,"id=\"".$n."-play\"").'</div>
			<script type="text/javascript">
				$("'.$n.'-play").observe("click", function(e) {
					var val = $("'.$n.'-textarea").value;
					if(val.length > 4000) {
						alert("The preview will only render audio from the first 4000 characters.");
						val = val.substr(0,4000);
					}
					var gender = ($("'.$n.'-female").checked?"female":"male");
					if (val) {
						var encodedtext = encodeURIComponent(val);
						popup(\'previewmessage.php?text=\' + encodedtext + \'&language='.urlencode($this->args['language']).'&gender=\'+ gender, 400, 400);
					}
				});
				$("'.$n.'-textarea").observe("blur", '.$n.'_storedata);
				$("'.$n.'-textarea").observe("keyup", '.$n.'_storedata);
				$("'.$n.'-female").observe("click", '.$n.'_storedata);
				$("'.$n.'-male").observe("click", '.$n.'_storedata);
				
				function '.$n.'_storedata(event) {
					var form = event.findElement("form");
					var formvars = document.formvars[form.name];
					var e = event.element();
					var formitem = $("'.$n.'");
					if (formvars.keyuptimer) {
						if (formvars.keyupelement == e)
							window.clearTimeout(formvars.keyuptimer);
					}
					formvars.keyupelement = e;
					formvars.keyuptimer = window.setTimeout(function () {
							var val = formitem.value.evalJSON();
							val.text = $("'.$n.'-textarea").value;
							val.gender = ($("'.$n.'-female").checked?"female":"male");
							formitem.value = Object.toJSON(val);
							form_do_validation(form, formitem);
							formvars.keyuptimer = null;
						},
						event.type == "keyup" ? 1000 : 200
					);
				}
			</script>
		';
		return $str;
	}
}

class CallMe extends FormItem {
	function render ($value) {
		$n = $this->form->name."_".$this->name;
		if (isset($this->args['language']) && $this->args['language'])
			$language = $this->args['language'];
		else
			$language = array("English (Default)");
		
		if (!$value)
			$value = '{"'.$language[0].'": ""}';
		// Hidden input item to store values in
		$str = '<input id="'.$n.'" name="'.$n.'" type="hidden" value="'.escapehtml($value).'" />
		<table class="msgdetails" width="100%">
		<tr><td class="msglabel">'._L("Language").':</td><td>';
		if (count($language) <= 1) {
			$str .= '<div id='.$n.'select style="background: white; border: 1px solid; padding: 2px; margin-right: 5px; margin-top: 2px;" value="'.$language[0].'">'.$language[0].'</div>';
		} else {
			$str .= '<select id='.$n.'select >';
			foreach ($language as $langname) 
				$str .= '<option id='.$n.'option_'.$langname.' value="'.escapehtml($langname).'" >'.escapehtml($langname).'</option>';
			$str .= '</select>';
		}
		$str .= '</td></tr>
		<tr><td class="msglabel">'._L("Phone").':</td><td><input style="float: left; margin-top: 3px" type="text" id='.$n.'phone value="'.$this->args['phone'].'" /></td></tr>
		<tr><td></td><td><img id="'.$n.'progress_img" style="float:left" src="img/pixel.gif"/><div id="'.$n.'progress" /></td></tr>
		<tr><td></td><td>'.icon_button(_L("Call Me To Record"),"/diagona/16/151","new Easycall('".$this->form->name."','".$n."','".$language[0]."','jobwizard','".$this->args['min']."','".$this->args['max']."').start();",null,'id="'.$n.'recordbutton"').'</td></tr>
		<tr><td class="msglabel">'._L("Messages").':</td>
		<td><table id="'.$n.'messages" style="border: 1px solid gray; width: 80%">
		<tr class="listHeader" align="left"><th colspan=2>'._L("Message Language").'</th><th width="30%">'._L("Actions").'</th></tr>
		
		</table></td></tr>
		</table>';
		// include the easycall javascript object and set up the localized version of the text it will use. then load existing values.
		$str .= '<script type="text/javascript" src="script/easycall.js.php"></script>
				<script type="text/javascript">
					new Easycall("'.$this->form->name.'","'.$n.'","'.$language[0].'","jobwizard").load();
				</script>';
		return $str;
	}
}

////////////////////////////////////////////////////////////////////////////////
// Validators
////////////////////////////////////////////////////////////////////////////////
class ValHasMessage extends Validator {
	var $onlyserverside = true;
	
	function validate ($value, $args) {
		global $USER;
		if ($value == 'pick') {
			$msgcount = (QuickQuery("select count(id) from message where userid=" . $USER->id ." and not deleted and type='".$args['type']."'"));
			if (!$msgcount)
				return "$this->label doesnt appear to exist for this user. Select another option or go create a message.";
		}
		return true;
	}
}

class ValEasycall extends Validator {
	var $onlyserverside = true;
	function validate ($value, $args) {
		global $USER;
		if (!$USER->authorize("starteasy"))
			return "$this->label is not allowed for this user account.";
		$values = json_decode($value);
		//return var_dump($values);
		foreach ($values as $lang => $message)
			if (!$message)
				return "$this->label has messages that are not recorded.";
		return true;
	}
}

class ValLists extends Validator {
	var $onlyserverside = true;
	
	function validate ($value, $args) {
		global $USER;
		
		if (strpos($value, 'choosingList') !== false)
			return _L('You are in the middle of choosing a list!');
		else if (strpos($value, 'buildingList') !== false)
			return _L('You are in the middle of building a list!');
			
		$listids = json_decode($value);
		if (empty($listids))
			return _L("Please add a list");
		$allempty = true;
		foreach ($listids as $listid) {
			if (!userOwns('list', $listid))
				return _L('You have specified an invalid list!');
			$list = new PeopleList($listid + 0);
			$renderedlist = new RenderedList($list);
			$renderedlist->calcStats();
			if ($renderedlist->total >= 1)
				$allempty = false;
		}
		if ($allempty)
			return _L('All these lists are empty!');
		return true;
	}
}

class ValTextAreaPhone extends Validator {
	function validate ($value, $args) {
		$msgdata = json_decode($value);
		$textlength = mb_strlen($msgdata->text);
		if (!$textlength)
			return $this->label." "._L("cannot be blank.");
		else if ($textlength > 4000) {
			return $this->label." "._L("cannot be more than 4000 characters.");		
		}		
		return true;
	}
	
	function getJSValidator () {
		return 
			'function (name, label, value, args) {
				var msgdata = value.evalJSON();
				var textlength = msgdata.text.length;
				if (!textlength)
					return label + " '.addslashes(_L('cannot be blank.')).'";
				else if(textlength > 4000)
					return label + " '.addslashes(_L('cannot be more than 4000 characters.')).'";			
				return true;
			}';
	}
}


////////////////////////////////////////////////////////////////////////////////
// Form Items
////////////////////////////////////////////////////////////////////////////////
class JobWiz_start extends WizStep {
	function getForm($postdata, $curstep) {
		global $USER;
		$userjobtypes = JobType::getUserJobTypes();
		$jobtypes = array();
		$jobtips = array();
		foreach ($userjobtypes as $id => $jobtype) {
			if (!$jobtype->issurvey) {
				$jobtypes[$id] = $jobtype->name;
				$jobtips[$id] = $jobtype->info;
			}
		}
		
		$formdata = array(
			$this->title,
			"name" => array(
				"label" => _L("Job Name"),
				"fieldhelp" => _L("Name is used for the job's email subject and to create reports."),
				"value" => "",
				"validators" => array(
					array("ValRequired"),
					array("ValLength","max" => 50)
				),
				"control" => array("TextField","maxlength" => 50, "size" => 50),
				"helpstep" => 1
			),
			"jobtype" => array(
				"label" => _L("Type/Category"),
				"fieldhelp" => _L("These options determine how your message will be received."),
				"value" => "",
				"validators" => array(
					array("ValRequired")
				),
				"control" => array("RadioButton", "values" => $jobtypes, "hover" => $jobtips),
				"helpstep" => 2
			),
			"package" => array(
				"label" => _L("Notification Method"),
				"fieldhelp" => _L("These are commonly used notification packages. For other options, select Custom."),
				"validators" => array(
					array("ValRequired")
				),
				"value" => "",
				"control" => array("RadioButton", 
					"values" => array(
						"easycall" => _L("EasyCall"),
						"express" => _L("ExpressText"),
						"personalized" => _L("Personalized"),
						"custom" => _L("Custom")
					),
					"hover" => array(
						"easycall" => _L("Record your voice via Phone. Message is delivered via phone, automatically generated Email with link to recording and automatically generated Text Message to cell phones."),
						"express" => _L("Text-to-Speach message. Automatically translated into altnernate languages. Text is delivered by Phone, Email and Text Message."),
						"personalized" => _L("Record your voice via Phone. Enter text for an Email and Text Message."),
						"custom" => _L("Choose any combination of options to customize your message delivery.")
					)
				),
				"helpstep" => 3
			)
		);
		$helpsteps = array (
			_L("Welcome to the Job Wizard. This is a guided 5 step process. <br><br>Enter your Job's name. Job names are used for email subjects and reporting, so they should be descriptive.").'<br>'._L("Good examples include 'Standardized testing reminder', or 'Early dismissal'."),
			_L("Job Types are used to determine which phones or emails will be contacted. Choosing the correct job type is important for effective communication.").'<br><br> <i><b>'._L("Note").':</b>'._L("Emergency jobs include a notification that the message is regarding an emergency.").'</i><br><br>',
			_L("Choose a notification method. The first three options are preconfigured to ask you to fill out specific steps. Custom will allow you to choose from all available notification options.")
		);
		return new Form("start",$formdata,$helpsteps);
	}
}

class JobWiz_listChoose extends WizStep {
	function getForm($postdata, $curstep) {
		return new ListForm("listChoose");
	}
	function isEnabled($postdata, $step) {
		return true;
	}
}

class JobWiz_messageType extends WizStep {
	function getForm($postdata, $curstep) {
		// Form Fields.
		$values = array();
		global $USER;
		$deliverytypes = array(
			'phone'=>array('sendphone', _L("Phone Call")),
			'email'=>array('sendemail', _L("EMail")),
			'sms'=>array('sendsms', _L("Text Message")),
			'print'=>array('sendmessage', _L("Print To Mail Document")));
		foreach ($deliverytypes as $checkvalue => $checkname)
			if ($USER->authorize($checkname[0]))
				$values[$checkvalue] = $checkname[1];
				
		$formdata[] = $this->title;
		$helpsteps = array(_L("Select a method or methods for message delivery."));
		$formdata["type"] = array(
			"label" => _L("Message Type"),
			"value" => "",
			"validators" => array(
				array("ValRequired")
			),
			"control" => array("MultiCheckBox", "values"=>$values),
			"helpstep" => 1
		);
		return new Form("messageSelect",$formdata,$helpsteps);
	}
	
	//returns true if this step is enabled
	function isEnabled($postdata, $step) {
		if (isset($postdata['/start']['package']) && 
			$postdata['/start']['package'] == "custom") {
			return true;
		} else {
			return false;
		}
	}
}

class JobWiz_messageSelect extends WizStep {
	function getForm($postdata, $curstep) {
		// Form Fields.
		$formdata = array();
		global $USER;
		$values = array();
		if ($USER->authorize("starteasy") && $USER->authorize("sendphone") && in_array('phone', $postdata['/message/pick']['type']))
			$values["record"] = _L("Call Me to Record");
		$values["text"] =_L("Type A Message");
		$values["pick"] =_L("Select Saved Message");

		$formdata[] = $this->title;
		$helpsteps = array(_L("Select the desired content source for the message delivery options listed."));
		if ($USER->authorize("sendphone") && in_array('phone', $postdata['/message/pick']['type'])) {
			$formdata["phone"] = array(
				"label" => _L("Phone Message"),
				"value" => "",
				"validators" => array(
					array("ValRequired"),
					array("ValHasMessage","type"=>"phone")
				),
				"control" => array("RadioButton","values"=>$values),
				"helpstep" => 1
			);
		}
		
		if (isset($values["record"]))
			$values["record"] = _L("Attach Recorded Message");
		
		if ($USER->authorize("sendemail") && in_array('email',$postdata['/message/pick']['type'])) {
			$formdata["email"] = array(
				"label" => _L("Email Message"),
				"value" => "",
				"validators" => array(
					array("ValRequired"),
					array("ValHasMessage","type"=>"email")
				),
				"control" => array("RadioButton","values"=>$values),
				"helpstep" => 1
			);
		}
		
		if (isset($values["record"]))
			$values["record"] = _L("Notify of Recorded Message");

		if ($USER->authorize("sendsms") && in_array('sms',$postdata['/message/pick']['type'])) {
			$formdata["sms"] = array(
				"label" => _L("SMS Message"),
				"value" => "",
				"validators" => array(
					array("ValRequired"),
					array("ValHasMessage","type"=>"sms")
				),
				"control" => array("RadioButton","values"=>$values),
				"helpstep" => 1
			);
		}

		if ($USER->authorize("sendmessage") && in_array('print',$postdata['/message/pick']['type'])) {
			$formdata["sms"] = array(
				"label" => _L("SMS Message"),
				"value" => "",
				"validators" => array(
					array("ValRequired"),
					array("ValHasMessage","type"=>"print")
				),
				"control" => array("RadioButton","values"=>array(
					"text"=>_L("Type A Message"), 
					"pick"=>_L("Select Saved Message"))),
				"helpstep" => 1
			);
		}
		
		return new Form("messageSelect",$formdata,$helpsteps);
	}
	
	//returns true if this step is enabled
	function isEnabled($postdata, $step) {
		if (isset($postdata['/start']['package']) && 
			$postdata['/start']['package'] == "custom") {
			return true;
		} else {
			return false;
		}
	}
}

class JobWiz_messagePhoneChoose extends WizStep {
	function getForm($postdata, $curstep) {
		// Form Fields.
		$formdata = array();
		$phonemessage = array(array("name"=>"--- "._L('Select One')." ---"));
		$values = array();
		global $USER;
		
		$messagelist = DBFindMany("Message","from message where userid=" . $USER->id ." and deleted=0 and type='phone' order by name");
		foreach ($messagelist as $id => $message) {
			$phonemessage[$id]['name'] = $message->name;
			$values[] = $id;
		}

		$formdata[] = $this->title;
		$formdata["message"] = array(
			"label" => "Select A Message",
			"value" => "",
			"validators" => array(
				array("ValRequired"),
				array("ValInArray","values"=>$values)
			),
			"control" => array("SelectMessage", "type"=>"phone", "width"=>"80%", "values"=>$phonemessage),
			"helpstep" => 1
		);
		$helpsteps = array(_L("Select from list of existing messages. If you do not find an appropriate message, you may click the Message Source link from the navigation on the left and choose to create a new message."));

		return new Form("messagePhoneChoose",$formdata,$helpsteps);
	}
	
	//returns true if this step is enabled
	function isEnabled($postdata, $step) {
		global $USER;
		if (!$USER->authorize("sendphone"))
			return false;
		if (isset($postdata['/start']['package']) && $postdata['/start']['package'] == "custom" &&
			isset($postdata['/message/select']['phone']) && $postdata['/message/select']['phone'] == "pick") {
			return true;
		} else {
			return false;
		}
	}
}

class JobWiz_messagePhoneText extends WizStep {
	function getForm($postdata, $curstep) {
		global $USER;
		// Form Fields.
		$helpsteps = array(_L("Enter your message text in the provided text area. Be sure to introduce yourself and give detailed information, including call back information if appropriate."));
		$formdata = array(
			$this->title,
			"message" => array(
				"label" => _L("Phone Message"),
				"fieldhelp" => _L("This text will be converted to a voice and read over the phone."),
				"value" => "",
				"validators" => array(
					array("ValRequired"),
					array("ValTextAreaPhone")
				),
				"control" => array("TextAreaPhone","width"=>"80%","rows"=>10,"language"=>"english","voice"=>"female"),
				"helpstep" => 1
			)
		);
		
		if ($USER->authorize('sendmulti')) {
			$helpsteps[] = _L("Automatically translate into alternate languages.");
			$formdata["translate"] = array(
				"label" => _L("Translate"),
				"value" => ($postdata['/start']['package'] == "express")?true:false,
				"validators" => array(),
				"control" => array("CheckBox"),
				"helpstep" => 2
			);
		}
		
		return new Form("messagePhoneText",$formdata,$helpsteps);
	}
	
	//returns true if this step is enabled
	function isEnabled($postdata, $step) {
		global $USER;
		if (!$USER->authorize("sendphone"))
			return false;
		if ((isset($postdata['/start']['package']) && $postdata['/start']['package'] == "express") ||
			((isset($postdata['/start']['package']) && $postdata['/start']['package'] == "custom") &&
				(isset($postdata['/message/select']['phone']) && $postdata['/message/select']['phone'] == "text"))
		) {
			return true;
		} else {
			return false;
		}
	}
}

class JobWiz_messagePhoneTranslate extends WizStep {
	function getTranslationDataArray($language, $text, $gender = "female", $transient = true, $englishText = false) {
		return array(
			"label" => ucfirst($language),
			"value" => json_encode(array(
				"enabled" => true,
				"text" => $text,
				"override" => false,
				"gender" => $gender
			)),
			"validators" => array(array("ValTranslation")),
			"control" => array("TranslationItem",
				"phone" => true,
				"language" => strtolower($language),
				"englishText" => $englishText
			),
			"transient" => $transient,
			"helpstep" => 2
		);
	}
	
	function isTransient ($postdata, $language) {
		if (isset($postdata["/message/phone/translate"][$language])) {
			$postmsgdata = json_decode($postdata["/message/phone/translate"][$language]);
			if ($postmsgdata)
				return !(!$postmsgdata->enabled || $postmsgdata->override);
		}
		return true;
	}
	
	function getForm($postdata, $curstep) {
		static $translations = false;
		static $translationlanguages = false;
		
		$msgdata = isset($postdata['/message/phone/text']['message'])?json_decode($postdata['/message/phone/text']['message']):json_decode('{"gender": "female", "text": ""}');
		
		$warning = "";
		if(mb_strlen($msgdata->text) > 4000) {
			$warning = _L('Warning. Only the first 4000 characters are translated.');
		}

		//Get available languages
		$translationlanguages = Voice::getTTSLanguages();
		$englishkey = array_search('English', $translationlanguages);
		if($englishkey !== false)
			unset($translationlanguages[$englishkey]);
			
		$translations = translate_fromenglish($msgdata->text,$translationlanguages);
		$voices = Voice::getTTSVoices();

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
			"control" => array("FormHtml","html"=>'<div style="font-size: medium;">'.escapehtml($msgdata->text).'</div><br>'),
			"helpstep" => 1
		);
		
		//$translations = false; // Debug output when no translation is available
		if(!$translations) {
			$formdata["Translationinfo"] = array(
				"label" => _L("Info"),
				"control" => array("FormHtml","html"=>'<div style="font-size: medium;">'._L('No Translations Available').'</div><br>'),
				"helpstep" => 2
			);
		} else {
			$i = 1;
			if(is_array($translations)){
				foreach($translations as $obj){
					if(!isset($voices[strtolower($translationlanguages[$i]).":".$msgdata->gender]))
						$gender = ($msgdata->gender == "male")?"female":"male";
					else
						$gender = $msgdata->gender;
					$transient = $this->isTransient($postdata, $translationlanguages[$i]);
					$formdata[$translationlanguages[$i]] = $this->getTranslationDataArray($translationlanguages[$i], $obj->responseData->translatedText, $gender, $transient, ($transient?"":$msgdata->text));
					$i++;
				}
			} else {
				$transient = $this->isTransient($postdata, $translationlanguages[$i]);
				$formdata[$translationlanguages[$i]] = $this->getTranslationDataArray($translationlanguages[$i], $translations->translatedText, $msgdata->gender, $transient, ($transient?"":$msgdata->text));
			}
		}
		if(!isset($formdata["Translationinfo"])) {
			$formdata["Translationinfo"] = array(
				"label" => " ",
				"control" => array("FormHtml","html"=>'
					<div id="branding">
						<div style="color: rgb(103, 103, 103);float: right;" class="gBranding"><span style="vertical-align: middle; font-family: arial,sans-serif; font-size: 11px;" class="gBrandingText">'._L('Translation powered by').'<img style="padding-left: 1px; vertical-align: middle;" alt="Google" src="http://www.google.com/uds/css/small-logo.png"></span></div>
					</div>
				'),
				"helpstep" => 2
			);
		}
		
		$helpsteps = array(
			_L("This is the message that all contacts will recieve if they do not have any other language message specified"),
			_L("This is an automated translation. Remember that the translation may not be 100% accurate so make sure to review the translations by translating back using the reverse translation feature. ")
		);
		
		
		return new Form("messagePhoneTranslate",$formdata,$helpsteps);
	}
	
	//returns true if this step is enabled
	function isEnabled($postdata, $step) {
		global $USER;
		if (!$USER->authorize("sendphone" || !$USER->authorize("sendmulti")))
			return false;
		if ((isset($postdata["/start"]["package"]) && $postdata["/start"]["package"] == 'express' && isset($postdata['/message/phone/text']['translate']) && $postdata['/message/phone/text']['translate']) ||
			(isset($postdata["/start"]["package"]) && $postdata["/start"]["package"] == 'custom' && isset($postdata['/message/pick']['type']) && in_array('phone', $postdata['/message/pick']['type']) && 
				isset($postdata["/message/select"]['phone']) && $postdata["/message/select"]['phone'] == 'text' && isset($postdata['/message/phone/text']['translate']) && $postdata['/message/phone/text']['translate'])
		) 
			return true;
		else
			return false;
	}
}

class JobWiz_messagePhoneCallMe extends WizStep {
	function getForm($postdata, $curstep) {
		// Form Fields.
		global $USER;
		$langs = array("English (Default)");
		if ($USER->authorize("sendmulti")) {
			$syslangs = DBFindMany("Language","from language order by name");
			foreach ($syslangs as $langid => $language)
				if ($syslangs[$langid]->name !== "English")
					$langs[] = $syslangs[$langid]->name;
		}
		$formdata = array($this->title);
		$formdata["message"] = array(
			"label" => _L("Messages"),
			"fieldhelp" => _L("Use the Language box to select the recorded language you wish to add to your notification. Enter a phone number and hit the Call Me To Record button to receive a phone call that will guide you through the process of attaching the selected language"),
			"value" => "",
			"validators" => array(
				array("ValRequired"),
				array("ValEasycall")
			),
			"control" => array(
				"CallMe", 
				"phone"=>$USER->phone, 
				"language"=>$langs,
				"max" => getSystemSetting('easycallmax',10), 
				"min" => getSystemSetting('easycallmin',10)
			),
			"helpstep" => 1
		);
		$helpsteps[] = _L("<p>Call Me sessions will ring the phone number you specify to record a selected language.</p><p>The 'Default' message is always required and will be delivered to any contacts who do not have a language specified, or who's prefered language is not recorded.</p><p>When you are ready...</p><p>Select the desired language you wish to record (Or Default)</p><p>Enter the phone number you are reachable at</p><p>Click the 'Call Me To Record' button</p><p>Listen carefully to the prompts when you receive the call.</p><p>You may record as many different languages as you need.</p>");

		return new Form("messagePhoneCallMe",$formdata,$helpsteps);
	}
	
	//returns true if this step is enabled
	function isEnabled($postdata, $step) {
		global $USER;
		if (!$USER->authorize("sendphone"))
			return false;
		if ((isset($postdata['/start']['package']) && 
			($postdata['/start']['package'] == "easycall" ||
				$postdata['/start']['package'] == "personalized") ||
			(isset($postdata['/message/select']['phone']) &&
				$postdata['/message/select']['phone'] == 'record'))
		) {
			return true;
		} else {
			return false;
		}
	}
}

class JobWiz_messageEmailChoose extends WizStep {
	function getForm($postdata, $curstep) {
		$messages = array(array("name"=>"--- "._L('Select One')." ---"));
		$values = array();
		global $USER;
		
		$messagelist = DBFindMany("Message","from message where userid=" . $USER->id ." and deleted=0 and type='email' order by name");
		foreach ($messagelist as $id => $message) {
			$messages[$id]['name'] = $message->name;
			$values[] = $id;
		}
		
		// Form Fields.
		$formdata = array($this->title);
		$helpsteps = array(_L("Select from list of existing messages. If you do not find an appropriate message, you may click the Message Source link from the navigation on the left and choose to create a new message."));
		$formdata["message"] = array(
			"label" => "Select A Message",
			"validators" => array(
				array("ValRequired"),
				array("ValInArray", "values"=>$values)
			),
			"value" => "",
			"control" => array("SelectMessage","type"=>"email", "width"=>"80%", "values"=>$messages),
			"helpstep" => 1
		);
		
		return new Form("messageEmailChoose",$formdata,$helpsteps);
	}
	
	//returns true if this step is enabled
	function isEnabled($postdata, $step) {
		global $USER;
		if (!$USER->authorize("sendemail"))
			return false;
		if (isset($postdata['/start']['package']) && $postdata['/start']['package'] == "custom" &&
			isset($postdata['/message/select']['email']) && $postdata['/message/select']['email'] == "pick") {
			return true;
		} else {
			return false;
		}
	}
}

class JobWiz_messageEmailText extends WizStep {
	function getForm($postdata, $curstep) {
		global $USER;
		$msgdata = isset($postdata['/message/phone/text']['message'])?json_decode($postdata['/message/phone/text']['message']):'{"text": ""}';
		// Form Fields.
		$formdata = array($this->title);
		$helpsteps = array(_L("Enter the address to which replies should be sent."));
		$formdata["from"] = array(
			"label" => _L("From"),
			"value" => $USER->email,
			"validators" => array(
				array("ValRequired"),
				array("ValLength","min" => 3,"max" => 255),
				array("ValEmail")
				),
			"control" => array("TextField","max"=>255,"min"=>3,"size"=>35),
			"helpstep" => 1
		);
		
		$helpsteps[] = _L("Email Subject.");
		$formdata["subject"] = array(
			"label" => _L("Subject"),
			"value" => $postdata['/start']['name'],
			"validators" => array(
				array("ValRequired"),
				array("ValLength","min" => 3,"max" => 255)
			),
			"control" => array("TextField","max"=>255,"min"=>3,"size"=>45),
			"helpstep" => 2
		);

		$helpsteps[] = '<ul><li>' . _L('Attach files up to 2 MB') . '<li>' . _L('Mention the attachments in the Message body') . '</ul>';
		$formdata["attachments"] = array(
			"label" => _L('Attachments'),
			"fieldhelp" => _L("You may attach up to three files that are up to 2048kB each. For greater security, certain file types are not permitted."),
			"value" => "",
			"validators" => array(array("ValEmailAttach")),
			"control" => array("EmailAttach","size" => 30, "maxlength" => 51),
			"helpstep" => 3
		);
		
		$helpsteps[] = _L("Email message body text goes here. Be sure to introduce yourself and give detailed information. Include a reply address or phone number if applicable.");
		$formdata["message"] = array(
			"label" => _L("Email Message"),
			"value" => $msgdata->text,
			"validators" => array(
				array("ValRequired"),
				array("ValLength","max" => 30000)
			),
			"control" => array("TextArea","rows"=>10,"cols"=>45),
			"helpstep" => 4
		);

		if ($USER->authorize('sendmulti')) {
			$helpsteps[] = _L("Automatically translate into alternate languages.");
			$formdata["translate"] = array(
				"label" => _L("Translate"),
				"value" => ($postdata['/start']['package'] == "express")?true:false,
				"validators" => array(),
				"control" => array("CheckBox"),
				"helpstep" => 5
			);
		}
		
		return new Form("messageEmailText",$formdata,$helpsteps);
	}
	
	//returns true if this step is enabled
	function isEnabled($postdata, $step) {
		global $USER;
		if (!$USER->authorize("sendemail"))
			return false;
		if ((isset($postdata['/start']['package']) && ($postdata['/start']['package'] == "express" || $postdata['/start']['package'] == "personalized")) ||
			(isset($postdata['/start']['package']) && $postdata['/start']['package'] == "custom" && isset($postdata['/message/select']['email']) && $postdata['/message/select']['email'] == "text")
		) {
			return true;
		} else {
			return false;
		}
	}
}

class JobWiz_messageEmailTranslate extends WizStep {
	function getTranslationDataArray($language, $text, $gender = "female", $transient = true, $englishText = false) {
		return array(
			"label" => ucfirst($language),
			"value" => json_encode(array(
				"enabled" => true,
				"text" => $text,
				"override" => false,
				"gender" => false
			)),
			"validators" => array(array("ValTranslation")),
			"control" => array("TranslationItem",
				"email" => true,
				"language" => strtolower($language),
				"englishText" => $englishText
			),
			"transient" => $transient,
			"helpstep" => 2
		);
	}
	
	function isTransient ($postdata, $language) {
		if (isset($postdata["/message/email/translate"][$language])) {
			$postmsgdata = json_decode($postdata["/message/email/translate"][$language]);
			if ($postmsgdata)
				return !(!$postmsgdata->enabled || $postmsgdata->override);
		}
		return true;
	}

	function getForm($postdata, $curstep) {
		static $translations = false;
		static $translationlanguages = false;
		
		$englishtext = isset($postdata['/message/email/text']['message'])?$postdata['/message/email/text']['message']:"";
		
		$warning = "";
		if(mb_strlen($englishtext) > 4000) {
			$warning = _L('Warning. Only the first 4000 characters are translated.');
		}
			
		if(!$translations) {
			//Get available languages
			$alllanguages = QuickQueryList("select name from language");
			$translationlanguages = array_intersect($alllanguages,array("Arabic", "Bulgarian", "Catalan", "Chinese", "Croatian", "Czech", "Danish", "Dutch", "Finnish", "French", "German", "Greek", "Hebrew", "Hindi", "Indonesian", "Italian", "Japanese", "Korean", "Latvian", "Lithuanian", "Norwegian", "Polish", "Portuguese", "Romanian", "Russian", "Serbian", "Slovak", "Slovenian", "Spanish", "Swedish", "Ukrainian", "Vietnamese"));
			$translations = translate_fromenglish($englishtext,$translationlanguages);
		}
		// Form Fields.
		$formdata = array($this->title);
		
		if ($warning)
			$formdata["warning"] = array(
				"label" => _L("Warning"),
				"control" => array("FormHtml","html"=>'<div style="font-size: medium; color: red">'.escapehtml($warning).'</div><br>'),
				"helpstep" => 1
			);

		$formdata["Englishtext"] = array(
			"label" => _L("English"),
			"control" => array("FormHtml","html"=>'<div style="font-size: medium;">'.escapehtml($englishtext).'</div><br>'),
			"helpstep" => 1
		);
		
		//$translations = false; // Debug output when no translation is available
		if(!$translations) {
			$formdata["Translationinfo"] = array(
				"label" => _L("Info"),
				"control" => array("FormHtml","html"=>'<div style="font-size: medium;">'._L('No Translations Available').'</div><br>'),
				"helpstep" => 2
			);
		} else {
			$i = 1;
			if(is_array($translations)) {
				foreach($translations as $obj){
					$transient = $this->isTransient($postdata, $translationlanguages[$i]);
					$formdata[$translationlanguages[$i]] = $this->getTranslationDataArray($translationlanguages[$i],$obj->responseData->translatedText, false, $transient, ($transient?"":$englishtext));
					$i++;
				}
			} else {
				$transient = $this->isTransient($postdata, $translationlanguages[$i]);
				$formdata[$translationlanguages[$i]] = $this->getTranslationDataArray($translationlanguages[$i],$translations->translatedText, false, $transient, ($transient?"":$englishtext));
			}
		}
		
		if(!isset($formdata["Translationinfo"])) {
			$formdata["Translationinfo"] = array(
				"label" => " ",
				"control" => array("FormHtml","html"=>'
					<div id="branding">
						<div style="color: rgb(103, 103, 103);float: right;" class="gBranding"><span style="vertical-align: middle; font-family: arial,sans-serif; font-size: 11px;" class="gBrandingText">'._L('Translation powered by').'<img style="padding-left: 1px; vertical-align: middle;" alt="Google" src="http://www.google.com/uds/css/small-logo.png"></span></div>
					</div>
				'),
				"helpstep" => 2
			);
		}
		
		$helpsteps = array(
			_L("This is the message that all contacts will recieve if they do not have any other language message specified"),
			_L("This is an automated translation. Remember that the translation may not be 100% accurate so make sure to review the translations by translating back using the reverse translation feature. ")
		);

		return new Form("messageEmailTranslate",$formdata,$helpsteps);
	}
	
	//returns true if this step is enabled
	function isEnabled($postdata, $step) {
		global $USER;
		if (!$USER->authorize("sendmulti") || !$USER->authorize("sendemail"))
			return false;
		if ((isset($postdata["/start"]["package"]) && $postdata["/start"]["package"] == 'express' && isset($postdata['/message/email/text']['translate']) && $postdata['/message/email/text']['translate']) ||
			(isset($postdata["/start"]["package"]) && $postdata["/start"]["package"] == 'personalized' && isset($postdata['/message/email/text']['translate']) && $postdata['/message/email/text']['translate']) ||
			(isset($postdata["/start"]["package"]) && $postdata["/start"]["package"] == 'custom' && isset($postdata['/message/pick']['type']) && in_array('email', $postdata['/message/pick']['type']) && 
				isset($postdata["/message/select"]['email']) && $postdata["/message/select"]['email'] == 'text' && isset($postdata['/message/email/text']['translate']) && $postdata['/message/email/text']['translate'])
		)
			return true;
		else
			return false;
	}
}

class JobWiz_messageSmsChoose extends WizStep {
	function getForm($postdata, $curstep) {
		// Form Fields.
		$formdata = array();
		$messages = array(array("name"=>"--- "._L('Select One')." ---"));
		$values = array();
		global $USER;
		
		$messagelist = DBFindMany("Message","from message where userid=" . $USER->id ." and deleted=0 and type='sms' order by name");
		foreach ($messagelist as $id => $message) {
			$messages[$id]['name'] = $message->name;
			$values[] = $id;
		}

		$formdata[] = $this->title;
		$formdata["message"] = array(
			"label" => "Select A Message",
			"validators" => array(
				array("ValRequired"),
				array("ValInArray", "values"=>$values)
			),
			"value" => "",
			"control" => array("SelectMessage", "type"=>"sms", "width"=>"80%", "values"=>$messages),
			"helpstep" => 1
		);
		$helpsteps = array(_L("Select from list of existing messages. If you do not find an appropriate message, you may click the Message Source link from the navigation on the left and choose to create a new message."));

		return new Form("messageSmsChoose",$formdata,$helpsteps);
	}
	
	//returns true if this step is enabled
	function isEnabled($postdata, $step) {
		global $USER;
		if (!$USER->authorize("sendsms"))
			return false;
		if (isset($postdata['/start']['package']) && $postdata['/start']['package'] == "custom" &&
			isset($postdata['/message/select']['sms']) && $postdata['/message/select']['sms'] == "pick") {
			return true;
		} else {
			return false;
		}
	}
}

class JobWiz_messageSmsText extends WizStep {
	function getForm($postdata, $curstep) {
		if (isset($postdata['/message/phone/text'])) {
			if (isset($postdata['/message/phone/text']['message'])) {
				$msgdata = json_decode($postdata['/message/phone/text']['message']);
				$text = $msgdata->text;
			}
		} else if (isset($postdata['/message/email/text'])) {
			if (isset($postdata['/message/email/text']['message']))
				$text = $postdata['/message/email/text']['message'];
		} else 
			$text = "";

		// Form Fields.
		$formdata = array($this->title);
		$helpsteps = array(_L("Enter the message you wish to deliver via Text Message."));
		$formdata["message"] = array(
			"label" => _L("Text Message"),
			"value" => $text,
			"validators" => array(
				array("ValRequired"),
				array("ValLength","max"=>160)
			),
			"control" => array("TextArea","rows"=>5,"cols"=>35,"counter"=>160),
			"helpstep" => 1
		);

		return new Form("messageSmsText",$formdata,$helpsteps);
	}
	
	//returns true if this step is enabled
	function isEnabled($postdata, $step) {
		global $USER;
		if (!$USER->authorize("sendsms"))
			return false;
		if ((isset($postdata['/start']['package']) && ($postdata['/start']['package'] == "express" || $postdata['/start']['package'] == "personalized")) ||
			(isset($postdata['/start']['package']) && $postdata['/start']['package'] == "custom" && isset($postdata['/message/select']['sms']) && $postdata['/message/select']['sms'] == "text")
		) {
			return true;
		} else {
			return false;
		}
	}
}

class JobWiz_scheduleOptions extends WizStep {
	function getForm($postdata, $curstep) {
		global $USER;
		global $ACCESS;
		// Form Fields.
		$formdata = array($this->title);
		$helpsteps = array(_L("Select when to send this message."));
		$formdata["schedule"] = array(
			"label" => _L("Delivery Schedule"),
			"fieldhelp" => _L("Select when to send this message."),
			"value" => "",
			"validators" => array(
				array("ValRequired")
			),
			"control" => array("RadioButton","values"=>array(
				"schedule"=>_L("Schedule and Send"),
				"template"=>_L("Save for Later")
			)),
			"helpstep" => 1
		);
		return new Form("scheduleOptions",$formdata,$helpsteps);
	}
}

class JobWiz_scheduleDate extends WizStep {
	function getForm($postdata, $curstep) {
		global $USER;
		global $ACCESS;
		// Form Fields.
		$formdata = array($this->title);
		if ($postdata['/schedule/options']['schedule'] == "schedule") {
			$helpsteps = array(_L("Choose a date for this notification to be delivered."));
			$formdata["date"] = array(
				"label" => _L("Start Date"),
				"fieldhelp" => _L("Notification will begin on the selected date."),
				"value" => "today",
				"validators" => array(),
				"control" => array("TextDate", "size"=>12, "nodatesbefore" => 0),
				"helpstep" => 1
			);
		}  else {
			$helpsteps = array();
		}
		// Only for phone calls
		if ((isset($postdata['/start']['package']) && $postdata['/start']['package'] == "easycall") ||
			(isset($postdata['/start']['package']) && $postdata['/start']['package'] == "express") ||
			(isset($postdata['/start']['package']) && $postdata['/start']['package'] == "personalized") ||
			(isset($postdata['/start']['package']) && $postdata['/start']['package'] == "custom" && isset($postdata["/message/pick"]["type"]) && in_array('phone', $postdata["/message/pick"]["type"]))
		) {
			$maxjobdays = $ACCESS->getValue("maxjobdays");
			$helpsteps[] = _L("The number of days your job will run for if it is unable to complete before the end of it's delivery window.");
			$formdata["days"] = array(
				"label" => _L("Days to run"),
				"fieldhelp" => ("Number of total days this notification may run for."),
				"value" => $USER->getDefaultAccessPref("maxjobdays", 1),
				"validators" => array(),
				"control" => array("SelectMenu", "values" => array_combine(range(1,$maxjobdays),range(1,$maxjobdays))),
				"helpstep" => 2
			);
		}
		
		$helpsteps[] = _L("The Delivery Window designates the earliest call time and the latest call time allowed for notification delivery.");
		$startvalues = newform_time_select(NULL, $ACCESS->getValue('callearly'), $ACCESS->getValue('calllate'), $USER->getCallEarly());
		$formdata["callearly"] = array(
			"label" => _L("Start Time"),
			"fieldhelp" => ("This is the earliest time to send calls. This is also determined by your security profile."),
			"value" => $USER->getCallEarly(),
			"validators" => array(
			),
			"control" => array("SelectMenu", "values"=>$startvalues),
			"helpstep" => 3
		);
		$endvalues = newform_time_select(NULL, $ACCESS->getValue('callearly'), $ACCESS->getValue('calllate'), $USER->getCallLate());
		$formdata["calllate"] = array(
			"label" => _L("End Time"),
			"fieldhelp" => ("This is the latest time to send calls. This is also determined by your security profile."),
			"value" => $USER->getCallLate(),
			"validators" => array(
			),
			"control" => array("SelectMenu", "values"=>$endvalues),
			"helpstep" => 3
		);

		return new Form("scheduleDate",$formdata,$helpsteps);
	}
	
	//returns true if this step is enabled
	function isEnabled($postdata, $step) {
		if (isset($postdata['/schedule/options']['schedule']) && 
			$postdata['/schedule/options']['schedule'] !== "template"
		) {
			return true;
		} else {
			return false;
		}
	}
}

class JobWiz_submitConfirm extends WizStep {
	function phoneRecordedMessage($msgdata) {
		$retval = array();
		foreach ($msgdata as $lang => $data)
			$retval[$lang] = array(
				"id" => $data,
				"text" => "",
				"gender" => "",
				"language" => ($lang == "English (Default)")?"english":strtolower($lang),
				"override" => true
			);
		return $retval;
	}
	
	function phoneTextMessage($msgdata) {
		return array("English (Default)" => array(
			"id" => "",
			"text" => $msgdata->text,
			"gender" => $msgdata->gender,
			"language" => 'english',
			"override" => true
		));
	}
	
	function phoneTextTranslation($msgdata) {
		$retval = array();
		foreach ($msgdata as $lang => $data) {
			$newmsgdata = json_decode($data);
			if ($newmsgdata->enabled)
				$retval[$lang] = array(
					"id" => "",
					"text" => $newmsgdata->text,
					"gender" => $newmsgdata->gender,
					"language" => strtolower($lang),
					"override" => $newmsgdata->override
				);
		}
		return $retval;
	}
	
	function emailSavedMessage($msgdata) {
		$retval = array();
		foreach ($msgdata as $lang => $data)
			$retval[$lang] = array(
				"id" => $data,
				"from" => "",
				"fromname" => "",
				"subject" => "",
				"attachments" => "",
				"text" => "",
				"language" => ($lang == "English (Default)")?"english":strtolower($lang),
				"override" => true
			);
		return $retval;
	}

	function emailTextMessage($msgdata) {
		return array("English (Default)" => array(
			"id" => "",
			"fromname" => "",
			"from" => $msgdata["from"],
			"subject" => $msgdata["subject"],
			"attachments" => json_decode($msgdata["attachments"]),
			"text" => $msgdata["message"],
			"language" => "english",
			"override" => true
		));
	}

	function emailTextTranslation($msgdata, $translationdata) {
		$retval = array();
		foreach ($translationdata as $lang => $data) {
			$newmsgdata = json_decode($data);
			if ($newmsgdata->enabled)
				$retval[$lang] = array(
					"id" => "",
					"from" => $msgdata["from"],
					"fromname" => "",
					"subject" => $msgdata["subject"],
					"attachments" => json_decode($msgdata["attachments"]),
					"text" => $newmsgdata->text,
					"language" => strtolower($lang),
					"override" => $newmsgdata->override
				);
		}
		return $retval;
	}
	
	function getForm($postdata, $curstep) {
		global $USER;
		$jobtype = DBFind("JobType", "from jobtype where id=?", false, array($postdata["/start"]["jobtype"]));
		$jobname = $postdata["/start"]["name"];

		$phoneMsg = array();
		$emailMsg = array();
		$smsMsg = array();
		switch ($postdata["/start"]["package"]) {
			//If package is Easycall
			case "easycall":
				$phoneMsg = $this->phoneRecordedMessage(json_decode($postdata["/message/phone/callme"]["message"]));
				$emailMsg = array("English (Default)" => array(
					"id" => "",
					"from" => $USER->email,
					"fromname" => "",
					"subject" => $postdata["/start"]["name"],
					"attachments" => array(),
					"text" => "// TODO: Insert link to customer page with job message preview? Maybe we want to attach the audio file, but that feels like a bad idea.",
					"language" => "english",
					"override" => true
				));
				$smsMsg = array("Default" => array(
					"id" => false,
					"text" => "// TODO: Put call back number to customer perhaps?",
					"language" => "english"
				));
				break;
			//Express Text
			case "express":
				$phoneMsg = $this->phoneTextMessage(json_decode($postdata["/message/phone/text"]["message"]));
				if ($postdata["/message/phone/text"]["translate"] == 'true')
					$phoneMsg = array_merge($phoneMsg, $this->phoneTextTranslation($postdata["/message/phone/translate"]));
				$emailMsg = $this->emailTextMessage($postdata["/message/email/text"]);
				if ($postdata["/message/email/text"]["translate"] == 'true')
					$emailMsg = array_merge($emailMsg, $this->emailTextTranslation($postdata["/message/email/text"], $postdata["/message/email/translate"]));
				$smsMsg = array("Default" => array(
					"id" => false,
					"text" => $postdata["/message/sms/text"]["message"],
					"language" => "english"
				));
				break;
			//Personalized
			case "personalized":
				$phoneMsg = $this->phoneRecordedMessage(json_decode($postdata["/message/phone/callme"]["message"]));
				$emailMsg = $this->emailTextMessage($postdata["/message/email/text"]);
				if ($postdata["/message/email/text"]["translate"] == 'true')
					$emailMsg = array_merge($emailMsg, $this->emailTextTranslation($postdata["/message/email/text"], $postdata["/message/email/translate"]));
				$smsMsg = array("Default" => array(
					"id" => false,
					"text" => $postdata["/message/sms/text"]["message"],
					"language" => "english"
				));
				break;
			//Custom
			case "custom":
				if (in_array('phone', $postdata["/message/pick"]["type"])) {
					switch ($postdata["/message/select"]["phone"]) {
						case "record":
							$phoneMsg = $this->phoneRecordedMessage(json_decode($postdata["/message/phone/callme"]["message"]));
							break;
						case "text":
							if ($postdata["/message/select"]["phone"] == "text") {
								$phoneMsg = $this->phoneTextMessage(json_decode($postdata["/message/phone/text"]["message"]));
								if ($postdata["/message/phone/text"]["translate"] == 'true')
									$phoneMsg = array_merge($phoneMsg, $this->phoneTextTranslation($postdata["/message/phone/translate"]));
							}
							break;
						case "pick":
							$phoneMsg = $this->phoneRecordedMessage(array("English (Default)" => $postdata["/message/phone/pick"]["message"]));
							break;
						default:
							error_log($postdata["/message/select"]["phone"] . " is an unknown value for ['/message/select']['phone']");
					}
				}
				if (in_array('email', $postdata["/message/pick"]["type"])) {
					switch ($postdata["/message/select"]["email"]) {
						case "record":
							$emailMsg = array("English (Default)" => array(
								"id" => "",
								"from" => $USER->email,
								"fromname" => "",
								"subject" => $postdata["/start"]["name"],
								"attachments" => array(),
								"text" => "// TODO: Insert link to customer page with job message preview? Maybe we want to attach the audio file, but that feels like a bad idea.",
								"language" => "english",
								"override" => true
							));
							break;
						case "text":
							$emailMsg = $this->emailTextMessage($postdata["/message/email/text"]);
							if ($postdata["/message/email/text"]["translate"] == 'true')
								$emailMsg = array_merge($emailMsg, $this->emailTextTranslation($postdata["/message/email/text"], $postdata["/message/email/translate"]));
							break;
						case "pick":
							$emailMsg = $this->emailSavedMessage(array("English (Default)" => $postdata["/message/email/pick"]["message"]));
							break;
						default:
							error_log($postdata["/message/select"]["email"] . " is an unknown value for ['/message/select']['email']");
					}
				}
				if (in_array('sms', $postdata["/message/pick"]["type"])) {
					switch ($postdata["/message/select"]["sms"]) {
						case "record":
							$smsMsg = array("Default" => array(
								"id" => false,
								"text" => "// TODO: Insert link to customer page with job message preview? Maybe we want to attach the audio file, but that feels like a bad idea.",
								"language" => "english"
							));
							break;
						case "text":
							$smsMsg = array("Default" => array(
								"id" => false,
								"text" => $postdata["/message/sms/text"]["message"],
								"language" => "english"
							));
							break;
						case "pick":
							$smsMsg = array("Default" => array(
								"id" => $postdata["/message/sms/pick"]["message"],
								"text" => false,
								"language" => "english"
							));
							break;
						default:
							error_log($postdata["/message/select"]["sms"] . " is an unknown value for ['/message/select']['sms']");
					}
				}
				break;
			
			default:
				error_log($postdata["/start"]["package"] . "is an unknown value for 'package'");
		}
		
		$schedule = array();
		switch ($postdata["/schedule/options"]["schedule"]) {
			case "schedule":
				$schedule = array(
					"date" => date('m/d/Y', strtotime($postdata["/schedule/date"]["date"])),
					"callearly" => $postdata["/schedule/date"]["callearly"],
					"calllate" => $postdata["/schedule/date"]["calllate"],
					"days" => isset($postdata["/schedule/date"]["days"])?$postdata["/schedule/date"]["days"]:false
				);
				break;
			case "template": 
				$schedule = array(
					"date" => false,
					"callearly" => false,
					"calllate" => false,
					"days" => false
				);
				break;
			default:
				break;
		}

		$formdata = array($this->title);
		
		$html = '<table style="border: 1px solid gray; width: 100%">
			<tr class="listHeader" align="left"><th width="50%">'._L("Job Name").'</th><th width="50%">'._L("Type").'</th></tr>
			<tr><td>
				<div style="font-size: large"><img src="img/icons/comment.gif"/>&nbsp;'.$jobname.'</div>
			</td>
			<td>
				<div id="jobtype"><img src="img/icons/cog.gif"/>&nbsp;'.$jobtype->name.'</div>
			</td></tr>
			</table>
			<script>
				var hover = {};
				hover["jobtype"] = "'.$jobtype->info.'";
				form_do_hover(hover);
			</script>
		';
		
		$formdata["jobtype"] = array(
			"label" => _L('Job Type'),
			"control" => array("FormHtml", "html" => $html),
			"helpstep" => 1,
		);

		$lists = json_decode($postdata["/list"]["listids"]);
		$calctotal = 0;
		$html = '<table style="border: 1px solid gray; width: 100%">
			<tr class="listHeader" align="left"><th width="50%">'._L("List Name").'</th><th width="50%">'._L("People").'</th></tr>
		';
		
		foreach ($lists as $id) {
			$list = new PeopleList($id+0);
			$renderedlist = new RenderedList($list);
			$renderedlist->calcStats();
			$calctotal = $calctotal + $renderedlist->total;
			$html .='<tr><td><img src="img/icons/group_add.gif"/>&nbsp;'.$list->name.'</td><td>'.$renderedlist->total.'</td></tr>
			';
		}
		$html .='<tr><td style="border-top: 2px solid gray; font-weight:900"><img src="img/icons/group_go.gif"/>&nbsp;'._L('Total').'</td><td style="border-top: 2px solid gray; font-weight:900">'.$calctotal.'</td></tr>
			</table>
		';
		$formdata["list"] = array(
			"label" => _L('List'),
			"control" => array("FormHtml", "html" => $html),
			"helpstep" => 1,
		);
		
		// Message Preview
		$html = '<table style="border: 1px solid gray; width: 100%">
			<tr class="listHeader" align="left"><th colspan=2>'._L("Message Languages").'</th></tr>
		';
		// Phone Message Preview
		if ($phoneMsg) {
			$html .= '<tr><td width="10%"><img src="img/icons/group.gif"/>&nbsp;'._L("Phone").'</td><td>
			';
			foreach ($phoneMsg as $label => $data)
				if ($data['id'])
					$html .= icon_button($label, "play", "popup('previewmessage.php?id=".$data['id']."', 400, 500)");
				else
					$html .= icon_button($label, "play", "popup('previewmessage.php?language=".$data['language']."&gender=".$data['gender']."&text=".$data['text']."', 400, 500)");
			$html .= '
				</td></tr>
			';
		}
		// Email Message Preview
		if ($emailMsg) {
			$html .= '<tr><td><img src="img/icons/email.gif"/>&nbsp;'._L("Email").'</td><td>
			';
			$langsText = '';
			foreach ($emailMsg as $label => $data) {
				if ($data['id'])
					$html .= icon_button($label, "email", "popup('previewmessage.php?close=1&id=".$data['id']."', 600, 500)");
				else
					$langsText .= $label . ", ";
			}
			if ($langsText)
				$langsText = substr($langsText, 0, -2);
			$html .= $langsText. '
				</td></tr>
			';
		}
		// SMS Message Preview
		if ($smsMsg) {
			$html .= '<tr><td><img src="img/icons/phone.gif"/>&nbsp;'._L("Text").'</td><td>
			';
			$langsText = '';
			foreach ($smsMsg as $label => $data) {
				if ($data['id'])
					$html .= icon_button($label, "phone", "popup('previewmessage.php?close=1&id=".$data['id']."', 600, 500)");
				else
					$langsText .= $label . ", ";
			}
			if ($langsText)
				$langsText = substr($langsText, 0, -2);
			$html .= $langsText. '
				</td></tr>
			';
		}
		$html .= '</table>
		';
		$formdata["message"] = array(
			"label" => _L('Message'),
			"control" => array("FormHtml", "html" => $html),
			"helpstep" => 1,
		);
		
		$html = '<table style="border: 1px solid gray; width: 100%">
			<tr class="listHeader" align="left"><th width="33%">'._L("Start Date").'</th><th width="33%">'._L("Delivery Window").'</th><th width="33%">'._L("Days to Run").'</th></tr>
			<tr><td><img src="img/icons/calendar.gif"/>&nbsp;'. ($schedule["date"]?$schedule["date"]:_L('Not Scheduled')). '</td><td><img src="img/icons/clock.gif"/>&nbsp;'. $schedule["callearly"]. ' -- '. $schedule["calllate"]. '</td><td><img src="img/icons/calendar_add.gif"/>&nbsp;'. ($schedule["days"]?$schedule["days"]:_L('N/A')). '</td></tr>
			</table>
		';
		$formdata["schedule"] = array(
			"label" => _L('Schedule'),
			"control" => array("FormHtml", "html" => $html),
			"helpstep" => 1,
		);
		
		$formdata["note"] = array(
			"label" => _L('Note'),
			"control" => array("FormHtml", "html" => '<div style="font-size: medium">'._L('Clicking Next will submit this job and schedule your notification to begin.').'</div>'),
			"helpstep" => 1,
		);

		if (isset($_SESSION['confirmedJobWizard']))
			unset($_SESSION['confirmedJobWizard']);
		
		$_SESSION['confirmedJobWizard'] = array(
			"jobtype" => $jobtype->id,
			"jobname" => $jobname,
			"lists" => $lists,
			"phone" => $phoneMsg,
			"email" => $emailMsg,
			"sms" => $smsMsg,
			"print" => array(),
			"schedule" => $schedule
		);
		
		return new Form("confirm",$formdata,array());
	}
}
?>
