<?
////////////////////////////////////////////////////////////////////////////////
// Custom Form Item Definitions
////////////////////////////////////////////////////////////////////////////////


// Select message (phone, email, or sms)
class SelectMessage extends FormItem {
	function render ($value) {
		$n = $this->form->name."_".$this->name;
		$str = '<select id="'.$n.'" name="'.$n.'" onchange="'.$n.'messageselect.getMessage();" >';
		foreach ($this->args['values'] as $selectid => $selectvals) {
			$checked = $value == $selectid;
			$str .= '<option value="'.escapehtml($selectid).'" '.($checked ? 'selected' : '').' >'.escapehtml($selectvals['name']).'</option>';
		}
		$str .= '</select>
		<table id="'.$n.'details" class="msgdetails" width="'.$this->args['width'].'">
		<tr><td class="msglabel">'._L("Last Used").':</td><td><span id="'.$n.'lastused" class="msginfo">...</span></td></tr>
		<tr><td class="msglabel">'._L("Description").':</td><td><span id="'.$n.'description" class="msginfo">...</span></td></tr>';
		if ($this->args['type'] == 'email') {
			$str .= '<tr><td class="msglabel">'._L("From").':</td><td><span id="'.$n.'from" class="msginfo">...</span></td></tr>
			<tr><td class="msglabel">'._L("Subject").':</td><td><span id="'.$n.'subject" class="msginfo">...</span></td></tr>
			<tr><td class="msglabel">'._L("Attachment").'t:</td><td><span id="'.$n.'attachment" class="msgattachment">...</span></td></tr>';
		}
		if ($this->args['type'] == 'phone') {
			$str .= '<tr><td class="msglabel">'._L("Preview").':</td><td>'.icon_button("Play","play",null,null,'id="'.$n.'play"').'</td></tr>';
		}
		$str .= '<tr><td class="msglabel">'._L("Body").':</td><td><textarea style="width:100%" rows="15" readonly id="'.$n.'body" >...</textarea></td></tr>
		</table>
		<script type="text/javascript" src="script/messageselect.js"></script>
			<script type="text/javascript">
			var '.$n.'messageselect = new MessageSelect("'.$n.'","'.$this->args['type'].'");
		</script>';
		return $str;
	}
}

class TextAreaPhone extends FormItem {
	function render ($value) {
		$n = $this->form->name."_".$this->name;
		$rows = isset($this->args['rows']) ? 'rows="'.$this->args['rows'].'"' : "";
		$str = '<script>
				function '. $n .'Play() {
					var val = $("'.$n.'").value;
					if (val) {
						var encodedtext=encodeURIComponent(val);
						popup(\'previewmessage.php?text=\' + encodedtext + \'&language='.urlencode($this->args['language']).'&gender='.urlencode($this->args['voice']).'\', 400, 400);
					}
				}
				</script>
		<textarea id="'.$n.'" style="width:'.$this->args['width'].'" name="'.$n.'" '.$rows.'/>'.escapehtml($value).'</textarea>';
		$str .= icon_button(_L("Play"),"play",$n."Play();");
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
		<tr><td></td><td><img id="'.$n.'progress_img" style="float:left" /><div id="'.$n.'progress" /></td></tr>
		<tr><td></td><td>'.icon_button(_L("Call Me To Record"),"/diagona/16/151","new Easycall('".$this->form->name."','".$n."','".$language[0]."','jobwizard','".$this->args['min']."','".$this->args['max']."').start();",null,'id="'.$n.'recordbutton"').'</td></tr>
		<tr><td class="msglabel">'._L("Messages").':</td>
		<td><table id="'.$n.'messages" style="border: 1px solid gray; width: 80%">
		<tr><th colspan=2 class="windowRowHeader">'._L("Message Language").'</th><th class="windowRowHeader" width="30%">'._L("Actions").'</th></tr>
		
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

class ValContactListMethod extends Validator {
	function validate ($value, $args) {
		if ($value == 'pick')
			return true;
		return "$this->label cannot be of a complex type for the wizard. If you would like to create one go to Notifications > Lists and then return here.";		
	}
	
	function getJSValidator () {
		return 
			'function (name, label, value, args) {
				if (value == "pick")
						return true;
				
				return label + " cannot be of a complex type for the wizard. If you would like to create one go to Notifications > Lists and then return here.";
			}';
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
			_L('Welcome to the Job Wizard'),
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
				
		$formdata[] = 'Delivery Methods';
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
		if ($USER->authorize("starteasy") && $USER->authorize("sendphone"))
			$values["record"] = _L("Call Me to Record");
		$values["text"] =_L("Type A Message");
		$values["pick"] =_L("Select Saved Message");

		$formdata[] = 'Message Source';
		$helpsteps = array(_L("Select the desired content source for the message delivery options listed."));
		if ($USER->authorize("sendphone") && in_array('phone',$postdata['/message/pick']['type'])) {
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
		$phonemessage = array(array("name"=>"--- Select One ---","lastused"=>"","description"=>""));
		$values = array();
		global $USER;
		
		$messagelist = DBFindMany("Message","from message where userid=" . $USER->id ." and deleted=0 and type='phone' order by name");
		foreach ($messagelist as $id => $message) {
			$phonemessage[$id]['name'] = $message->name;
			$phonemessage[$id]['lastused'] = ($message->lastused)?$message->lastused:"Never";
			$phonemessage[$id]['description'] = $message->description;
			$values[] = $id;
		}

		$formdata[] = _L('Phone: Existing Message');
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
		// Form Fields.
		$formdata = array();
		$helpsteps = array(_L("description."));
		
		$helpstepnum = 1;
		
		$formdata = array(
				"message" => array(
					"label" => _L("Phone Message"),
					"value" => "",
					"validators" => array(
						array("ValRequired")
					),
					"control" => array("TextAreaPhone","width"=>"80%","rows"=>10,"language"=>"english","voice"=>"female"),
					"helpstep" => $helpstepnum
				),
				"voice" => array(
					"label" => _L("Voice"),
					"value" => "Female",
					"validators" => array(array("ValRequired")),
					"control" => array("RadioButton","values" => array("Female" => "Female","Male" => "Male")),
					"helpstep" => $helpstepnum
				),
				"translate" => array(
					"label" => _L("Translate"),
					"value" => ($postdata['/start']['package'] == "express")?true:false,
					"validators" => array(),
					"control" => array("CheckBox"),
					"helpstep" => $helpstepnum
				)
		);
		$helpsteps[$helpstepnum++] = _L("Automatically translate into alternate languages.");
		
		return new Form("messagePhoneText",$formdata,$helpsteps);
	}
	
	//returns true if this step is enabled
	function isEnabled($postdata, $step) {
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
	function getTranslationDataArray($language, $text, $voice = "Female") {
		return array(
				"label" => $language,
				"value" => array(
							"enabled" => true,
							"text" => $text,
							"override" => false
							),
				"validators" => array(array("ValTranslation")),
				"control" => array("TranslationItem","phone" => true,"voice" => $voice),
				"transient" => true,
				"helpstep" => 2
				);	
	}
	
	function getForm($postdata, $curstep) {
		static $translations = false;
		static $translationlanguages = false;
		static $voicearray = false;
		
		$englishtext = isset($postdata['/message/phone/text']['message'])?$postdata['/message/phone/text']['message']:"";
		
		if(!$translations) {
			//Get available languages
			$translationlanguages = Voice::getTTSLanguages();
			$englishkey = array_search('English', $translationlanguages);
			if($englishkey !== false)
				unset($translationlanguages[$englishkey]);
			$translations = translate_fromenglish($englishtext,$translationlanguages);
			$voices = DBFindMany("Voice","from ttsvoice");
			foreach ($voices as $voice) {
				$voicearray[ucfirst($voice->language)][ucfirst($voice->gender)] = true;
			}
		}

		// Form Fields.
		$formdata = array("Default Phone Message");
		$formdata["Englishtext"] = array(
			"label" => _L("English:"),
			"control" => array("FormHtml","html"=>"<h3>$englishtext</h3><br />"),
			"helpstep" => 1
		);
		$formdata[] = "Translations";
		
		//$translations = false; // Debug output when no translation is available
		if(!$translations) {
			$formdata["Translationinfo"] = array(
					"label" => _L("Info") . ": ",
					"control" => array("FormHtml","html"=>"<h3>No Translations Available</h3><br />"),
					"helpstep" => 2
			);
		} else {
			$preferredvoice = isset($postdata["/message/phone/text"]["voice"])?$postdata["/message/phone/text"]["voice"]:"Female";
			$i = 1;
			if(is_array($translations)){
				foreach($translations as $obj){
					$displaylanguage = ucfirst($translationlanguages[$i]);
					if(isset($voicearray[$displaylanguage][$preferredvoice])) {
						$voice = $preferredvoice;
					}else {
						if($preferredvoice == "Male")
							$voice = "Female";
						else
							$voice = "Male";
					}
					
					$formdata["$displaylanguage"] = $this->getTranslationDataArray($displaylanguage,$obj->responseData->translatedText,$voice);
					$i++;
				}
			} else {
				$displaylanguage = ucfirst($translationlanguages[$i]);	
				$formdata["$displaylanguage"] = $this->getTranslationDataArray($displaylanguage,$translations->translatedText,$voice);
			}
		}
		if(!isset($formdata["Translationinfo"])) {
			$formdata["Translationinfo"] = array(
					"label" => " ",
					"control" => array("FormHtml","html"=>'
						<div id="branding">
							<div style="color: rgb(103, 103, 103);float: right;" class="gBranding"><span style="vertical-align: middle; font-family: arial,sans-serif; font-size: 11px;" class="gBrandingText">Translation powered by<img style="padding-left: 1px; vertical-align: middle;" alt="Google" src="http://www.google.com/uds/css/small-logo.png"></span></div>
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
		if (isset($postdata['/message/phone/text']['translate']) && $postdata['/message/phone/text']['translate'])
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
		$syslangs = DBFindMany("Language","from language order by name");
		foreach ($syslangs as $langid => $language)
			if ($syslangs[$langid]->name !== "English")
				$langs[] = $syslangs[$langid]->name;
		
		$formdata = array(_L("Record"));
		$formdata["callme"] = array(
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
		// Form Fields.
		$formdata = array();
		$messages = array(array("name"=>"--- Select One ---"));
		$values = array();
		global $USER;
		
		$messagelist = DBFindMany("Message","from message where userid=" . $USER->id ." and deleted=0 and type='email' order by name");
		foreach ($messagelist as $id => $message) {
			$messages[$id]['name'] = $message->name;
			$values[] = $id;
		}

		$formdata[] = 'Email: Existing Message';
		$formdata["phoneSelect"] = array(
			"label" => "Select A Message",
			"validators" => array(
				array("ValRequired"),
				array("ValInArray", "values"=>$values)
			),
			"value" => "",
			"control" => array("SelectMessage","type"=>"email", "width"=>"80%", "values"=>$messages),
			"helpstep" => 1
		);
		$helpsteps = array(_L("Select from list of existing messages. If you do not find an appropriate message, you may click the Message Source link from the navigation on the left and choose to create a new message."));
		
		return new Form("messageEmailChoose",$formdata,$helpsteps);
	}
	
	//returns true if this step is enabled
	function isEnabled($postdata, $step) {
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
		// Form Fields.
		$formdata = array();
		$helpsteps = array(_L("description."));
		global $USER;
		$helpstepnum = 1;
		
		$formdata["from"] = array(
			"label" => _L("From"),
			"value" => $USER->email,
			"validators" => array(
				array("ValRequired"),
				array("ValLength","min" => 3,"max" => 255),
				array("ValEmail")
				),
			"control" => array("TextField","max"=>255,"min"=>3),
			"helpstep" => $helpstepnum
		);
		$helpsteps[$helpstepnum++] = _L("Enter the address to which replies should be sent.");
		
		$formdata["subject"] = array(
			"label" => _L("Subject"),
			"value" => $postdata['/start']['name'],
			"validators" => array(
				array("ValRequired"),
				array("ValLength","min" => 3,"max" => 255)
			),
			"control" => array("TextField","max"=>255,"min"=>3),
			"helpstep" => $helpstepnum
		);
		$helpsteps[$helpstepnum++] = _L("Email Subject.");

		$formdata["message"] = array(
			"label" => _L("Email Message"),
			"value" => isset($postdata['/message/phone/text']['message'])?$postdata['/message/phone/text']['message']:"",
			"validators" => array(
				array("ValRequired")
			),
			"control" => array("TextArea","rows"=>15),
			"helpstep" => $helpstepnum
		);
		$helpsteps[$helpstepnum++] = _L("Enter your message text here.");

		$formdata["translate"] = array(
			"label" => _L("Translate"),
			"value" => ($postdata['/start']['package'] == "express")?true:false,
			"validators" => array(),
			"control" => array("CheckBox"),
			"helpstep" => $helpstepnum
		);
		$helpsteps[$helpstepnum++] = _L("Automatically translate into alternate languages.");
		
		return new Form("messageEmailText",$formdata,$helpsteps);
	}
	
	//returns true if this step is enabled
	function isEnabled($postdata, $step) {
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
	function getTranslationDataArray($language, $text) {
		return array(
				"label" => $language,
				"value" => array(
							"enabled" => true,
							"text" => $text,
							"override" => false
							),
				"validators" => array(array("ValTranslation")),
				"control" => array("TranslationItem","email" => true),
				"transient" => true,
				"helpstep" => 2
				);	
	}
	
	function getForm($postdata, $curstep) {
		static $translations = false;
		static $translationlanguages = false;
		
		$englishtext = isset($postdata['/message/email/text']['message'])?$postdata['/message/email/text']['message']:"";
		
		if(!$translations) {
			//Get available languages
			$alllanguages = QuickQueryList("select name from language");
			$translationlanguages = array_intersect($alllanguages,array("Arabic", "Bulgarian", "Catalan", "Chinese", "Croatian", "Czech", "Danish", "Dutch", "Finnish", "French", "German", "Greek", "Hebrew", "Hindi", "Indonesian", "Italian", "Japanese", "Korean", "Latvian", "Lithuanian", "Norwegian", "Polish", "Portuguese", "Romanian", "Russian", "Serbian", "Slovak", "Slovenian", "Spanish", "Swedish", "Ukrainian", "Vietnamese"));
			$translations = translate_fromenglish($englishtext,$translationlanguages);
		}
		// Form Fields.
		$formdata = array("Default Email Message");						
		$formdata["Englishtext"] = array(
			"label" => _L("English:"),
			"control" => array("FormHtml","html"=>"<h3>$englishtext</h3><br />"),
			"helpstep" => 1
		);
		$formdata[] = "Translations";
		
		$emaillanguages = $translationlanguages; // since translation language is static
		//$translations = false; // Debug output when no translation is available
		if(!$translations) {
			$formdata["Translationinfo"] = array(
					"label" => _L("Info") . ": ",
					"control" => array("FormHtml","html"=>"<h3>No Translations Available</h3><br />"),
					"helpstep" => 2
			);
		} else {
			if(is_array($translations)){
				foreach($translations as $obj){
					$displaylanguage = ucfirst(array_shift($emaillanguages));
					$formdata["$displaylanguage"] = $this->getTranslationDataArray($displaylanguage,$obj->responseData->translatedText);
				}
			} else {
				$displaylanguage = ucfirst(array_shift($emaillanguages));
				$formdata["$displaylanguage"] = $this->getTranslationDataArray($displaylanguage,$translations->translatedText);
			}
		}
		if(!isset($formdata["Translationinfo"])) {
			$formdata["Translationinfo"] = array(
					"label" => " ",
					"control" => array("FormHtml","html"=>'
						<div id="branding">
							<div style="color: rgb(103, 103, 103);float: right;" class="gBranding"><span style="vertical-align: middle; font-family: arial,sans-serif; font-size: 11px;" class="gBrandingText">Translation powered by<img style="padding-left: 1px; vertical-align: middle;" alt="Google" src="http://www.google.com/uds/css/small-logo.png"></span></div>
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
		if (isset($postdata['/message/email/text']['translate']) && $postdata['/message/email/text']['translate']) {
			return true;
		} else {
			return false;
		}
	}
}

class JobWiz_messageEmailAttachment extends WizStep {
	function getForm($postdata, $curstep) {
		// Form Fields.
		$formdata = array();
		$helpsteps = array(_L("description."));
		
		$helpstepnum = 1;
		
		$formdata["a"] = array(
				"label" => "Attach Files",
				"control" => array("FormHtml","html"=>"<h1>Wicked Awesome Email Attachment Widget</h1>"),
				"helpstep" => $helpstepnum
		);
		$helpsteps[$helpstepnum++] = _L("c");

		// TODO: Need email attachment formItem

		return new Form("messageEmailAttachment",$formdata,$helpsteps);
	}
	
	//returns true if this step is enabled
	function isEnabled($postdata, $step) {
		if (isset($postdata['/message/email/emailText']['attachFile']) && 
			$postdata['/message/email/emailText']['attachFile'] == "true") {
			return true;
		} else {
			return false;
		}
	}
}

class JobWiz_messageSmsChoose extends WizStep {
	function getForm($postdata, $curstep) {
		// Form Fields.
		$formdata = array();
		$messages = array(array("name"=>"--- Select One ---"));
		$values = array();
		global $USER;
		
		$messagelist = DBFindMany("Message","from message where userid=" . $USER->id ." and deleted=0 and type='sms' order by name");
		foreach ($messagelist as $id => $message) {
			$messages[$id]['name'] = $message->name;
			$values[] = $id;
		}

		$formdata[] = 'Txt: Existing Message';
		$formdata["phoneSelect"] = array(
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
		// Form Fields.
		$formdata = array();
		$helpsteps = array(_L("description."));
		$text = "";
		$helpstepnum = 1;
		if (isset($postdata['/message/phone/text'])) {
			if (isset($postdata['/message/phone/text']['message']))
				$text = $postdata['/message/phone/text']['message'];
		} else if (isset($postdata['/message/email/text'])) {
			if (isset($postdata['/message/email/text']['message']))
				$text = $postdata['/message/email/text']['message'];
		}
		$formdata["message"] = array(
			"label" => _L("SMS Message"),
			"value" => $text,
			"validators" => array(
				array("ValRequired"),
				array("ValLength","max"=>160)
			),
			"control" => array("TextArea","rows"=>10),
			"helpstep" => $helpstepnum
		);
		$helpsteps[$helpstepnum++] = _L("Enter your message text here.");

		// TODO: Need custom formItem for SMS message with character counter

		return new Form("messageSmsText",$formdata,$helpsteps);
	}
	
	//returns true if this step is enabled
	function isEnabled($postdata, $step) {
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
		// Form Fields.
		$formdata = array('Schedule Options');
		$formdata["schedule"] = array(
			"label" => _L("Delivery Schedule"),
			"value" => "",
			"validators" => array(
				array("ValRequired")
			),
			"control" => array("RadioButton","values"=>array(
				"now"=>_L("Now"),
				"schedule"=>_L("Later"),
				"template"=>_L("Save as template")
			)),
			"helpstep" => 1
		);
		$helpsteps = array(_L("Select when to send this message. Earliest and latest delivery times may be restricted by system policy."));

		return new Form("scheduleOptions",$formdata,$helpsteps);
	}
}

class JobWiz_scheduleDate extends WizStep {
	function getForm($postdata, $curstep) {
		// Form Fields.
		$formdata = array();
		$helpsteps = array(_L("description."));
		
		$helpstepnum = 1;
		
		$formdata["a"] = array(
			"label" => _L("b"),
			"value" => "",
			"validators" => array(
			),
			"control" => array("TextField","max"=>100),
			"helpstep" => $helpstepnum
		);
		$helpsteps[$helpstepnum++] = _L("c");


		return new Form("scheduleDate",$formdata,$helpsteps);
	}
	
	//returns true if this step is enabled
	function isEnabled($postdata, $step) {
		if (isset($postdata['/schedule/options']['schedule']) && 
			$postdata['/schedule/options']['schedule'] == "later"
		) {
			return true;
		} else {
			return false;
		}
	}
}

class JobWiz_scheduleTemplate extends WizStep {
	function getForm($postdata, $curstep) {
		// Form Fields.
		$formdata = array();
		$helpsteps = array(_L("description."));
		global $USER;
		global $ACCESS;
		$helpstepnum = 1;
		
		$formdata["daysofweek"] = array(
			"label" => _L("Days of the Week"),
			"value" => "",
			"validators" => array(),
			"control" => array("MultiCheckBox","values"=>array(
				"0"=>"Sunday",
				"1"=>"Monday",
				"2"=>"Tuesday",
				"3"=>"Wednesday",
				"4"=>"Thursday",
				"5"=>"Friday",
				"6"=>"Saturday"
			)),
			"helpstep" => $helpstepnum
		);
		$helpsteps[$helpstepnum++] = _L("Days of the week.");

		$startvalues = newform_time_select(NULL, $ACCESS->getValue('callearly'), $ACCESS->getValue('calllate'), $USER->getCallEarly());
		$formdata["calltime"] = array(
			"label" => "Default Delivery Window (Earliest)",
			"value" => $USER->getCallEarly(),
			"validators" => array(),
			"control" => array("SelectMenu", "values"=>$startvalues),
			"helpstep" => $helpstepnum
		);
		$helpsteps[$helpstepnum++] = _L("Time to start.");

		return new Form("scheduleTemplate",$formdata,$helpsteps);
	}
	
	//returns true if this step is enabled
	function isEnabled($postdata, $step) {
		if (isset($postdata['/schedule/options']['schedule']) && 
			$postdata['/schedule/options']['schedule'] == "template"
		) {
			return true;
		} else {
			return false;
		}
	}
}

class JobWiz_submitTest extends WizStep {
	function getForm($postdata, $curstep) {
		// Form Fields.
		$formdata = array();
		$helpsteps = array(_L("description."));
		
		$helpstepnum = 1;
		
		$formdata["a"] = array(
				"label" => "Submit Test",
				"control" => array("FormHtml","html"=>"<h1>Wicked Awesome Test Notification Widget</h1>"),
				"helpstep" => $helpstepnum
		);
		$helpsteps[$helpstepnum++] = _L("c");


		return new Form("submitTest",$formdata,$helpsteps);
	}
	
	//returns true if this step is enabled
	function isEnabled($postdata, $step) {
		if (isset($postdata['/schedule/options']['test']) && 
			$postdata['/schedule/options']['test']
		) {
			return true;
		} else {
			return false;
		}
	}
}

class JobWiz_submitConfirm extends WizStep {
	function getForm($postdata, $curstep) {
		// Form Fields.
		$formdata = array();
		$helpsteps = array(_L("description."));
		
		$helpstepnum = 1;
		
		$formdata["a"] = array(
				"label" => "Confirm Settings",
				"control" => array("FormHtml","html"=>"<h1>Wicked Awesome Confirm Settings Widget</h1>"),
				"helpstep" => $helpstepnum
		);
		$helpsteps[$helpstepnum++] = _L("c");


		return new Form("submitConfirm",$formdata,$helpsteps);
	}
}
?>
