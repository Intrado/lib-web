<?
////////////////////////////////////////////////////////////////////////////////
// Custom Form Item Definitions
////////////////////////////////////////////////////////////////////////////////
// Select message (phone, email, or sms)
class SelectMessage extends FormItem {
	function render ($value) {
		$n = $this->form->name."_".$this->name;
		$str = '<script>'.
			(($this->args['type'] == 'phone')?'
				function '. $n .'Play() {
					var val = $("'.$n.'").value;
					if (val > 0)
						$("'.$n.'_iframe").innerHTML = \'<iframe height="200px" width="'.$this->args['width'].'" frameborder=0 marginwidth=0 marginheight=0 src="previewmessage.php?embed&noplay&close=1&id=\' + val + \'">\';
				}
				':'').'
			function updateDetails() {
				$("'.$n.'_lastused").innerHTML = \'<blink>Loading...</blink>\';
				$("'.$n.'_description").innerHTML = \'<blink>Loading...</blink>\';'.
				(($this->args['type'] == 'email')?'
					$("'.$n.'_from").innerHTML = \'<blink>Loading...</blink>\';
					$("'.$n.'_subject").innerHTML = \'<blink>Loading...</blink>\';
					$("'.$n.'_attachment").innerHTML = \'<blink>Loading...</blink>\';
					':'').
				(($this->args['type'] == 'email' || $this->args['type'] == 'sms')?'
					$("'.$n.'_body").value = \'Loading...\';
					':'').'
				var val = $("'.$n.'").value;
				if (parseInt(val) > 0) {
					$("'.$n.'_details").show();'.
					(($this->args['type'] == 'phone')?'
						$("'.$n.'_iframe").show();
						$("'.$n.'_iframe").innerHTML = \'<blink>Loading...</blink>\';
					':'').'
				} else {
					$("'.$n.'_details").hide();'.
					(($this->args['type'] == 'phone')?'
						$("'.$n.'_iframe").hide();
						$("'.$n.'_iframe").innerHTML = \'\';
					':'').'
					return false;
				}
				var fieldmap = null;
			

				new Ajax.Request(\'ajax.php?ajax&type=previewmessage&id=\'+val, {
					method:\'get\',
					onSuccess: function(transport){
						var response = transport.responseJSON;
						var text = "";
						if (response) {
							
							$("'.$n.'_lastused").innerHTML = response[\'lastused\'] || \'Never\';
							$("'.$n.'_description").innerHTML = response[\'description\'] || \'\';'.
							(($this->args['type'] == 'email')?'
								$("'.$n.'_from").innerHTML = response[\'fromemail\'] || \'\';
								$("'.$n.'_subject").innerHTML = response[\'subject\'] || \'\';
								if (response[\'attachment\'])						
									for (id in response[\'attachment\'])
										text = text + \'<a href="messageattachmentdownload.php?id=\' + id + \'">\' + response[\'attachment\'][id][\'filename\'] + \'&nbsp;&nbsp;(\' + parseInt(parseInt(response[\'attachment\'][id][\'size\']) / 1024) + \'K)</a><br>\';
								$("'.$n.'_attachment").innerHTML = text;
								text = "";
								':'').
							(($this->args['type'] == 'email' || $this->args['type'] == 'sms')?'
								$("'.$n.'_body").value = response[\'body\'];
								':'').'
						} else {
							$("'.$n.'_lastused").innerHTML = \'...\';
							$("'.$n.'_description").innerHTML = \'...\';'.
							(($this->args['type'] == 'email')?'
								$("'.$n.'_from").innerHTML = \'...\';
								$("'.$n.'_subject").innerHTML = \'...\';
								$("'.$n.'_attachment").innerHTML = \'...\';
								':'').
							(($this->args['type'] == 'email' || $this->args['type'] == 'sms')?'
								$("'.$n.'_body").value = \'...\';
								':'').'
						}
					},
					onFailure: function() {
							$("'.$n.'_lastused").innerHTML = \'Error...\';
							$("'.$n.'_description").innerHTML = \'Error...\';'.
							(($this->args['type'] == 'email')?'
								$("'.$n.'_from").innerHTML = \'Error...\';
								$("'.$n.'_subject").innerHTML = \'Error...\';			
								$("'.$n.'_attachment").innerHTML = \'Error...\';
								':'').
							(($this->args['type'] == 'email' || $this->args['type'] == 'sms')?'
								$("'.$n.'_body").value = \'Error...\';
								':'').'
					}
				});'.
				(($this->args['type'] == 'phone')?'
					'. $n .'Play();
					':'').'
			}
			</script>';
		$str .= '<select id="'.$n.'" name="'.$n.'" onchange="updateDetails();" >';
		foreach ($this->args['values'] as $selectid => $selectvals) {
			$checked = $value == $selectid;
			$str .= '<option value="'.escapehtml($selectid).'" '.($checked ? 'selected' : '').' >'.escapehtml($selectvals['name']).'</option>';
		}
		$str .= '</select>';
		$str .= '<table id="'.$n.'_details" class="msgdetails" width="'.$this->args['width'].'">';
		$str .= '<tr><td class="msglabel">Last Used:</td><td><span id="'.$n.'_lastused" class="msginfo">...</span></td></tr>';
		$str .= '<tr><td class="msglabel">Description:</td><td><span id="'.$n.'_description" class="msginfo">...</span></td></tr>';
		if ($this->args['type'] == 'email') {
			$str .= '<tr><td class="msglabel">From:</td><td><span id="'.$n.'_from" class="msginfo">...</span></td></tr>';
			$str .= '<tr><td class="msglabel">Subject:</td><td><span id="'.$n.'_subject" class="msginfo">...</span></td></tr>';
			$str .= '<tr><td class="msglabel">Attachment:</td><td><span id="'.$n.'_attachment" class="msgattachment">...</span></td></tr>';
		}
		if ($this->args['type'] == 'email' || $this->args['type'] == 'sms')
			$str .= '<tr><td class="msglabel">Body:</td><td><textarea style="width:100%" rows="20" readonly id="'.$n.'_body" >...</textarea></td></tr>';
		$str .= '</table>';
		if ($this->args['type'] == 'phone')
			$str .= '<div style="width:100%" id="'.$n.'_iframe"></div>';
		$str .= '<script>updateDetails()</script>';
		
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
				</script>';
		$str .= '<textarea id="'.$n.'" style="width:'.$this->args['width'].'" name="'.$n.'" '.$rows.'/>'.escapehtml($value).'</textarea>';
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
			$language = array("Default");
		
		if (!$value)
			$value = '{"'.$language[0].'": ""}';
		$str = '<input id="'.$n.'" name="'.$n.'" type="hidden" value="'.escapehtml($value).'" />';
		$str .= '<table class="msgdetails" width="80%">';
		$str .= '<tr><td class="msglabel">'._L("Language").':</td><td>';
		if (count($language) <= 1) {
			$str .= '<div id='.$n.'select style="background: white; border: 1px solid; padding: 2px; margin-right: 5px; margin-top: 2px;" value="'.$language[0].'">'.$language[0].'</div>';
		} else {
			$str .= '<select id='.$n.'select >';
			foreach ($language as $langname) 
				$str .= '<option id='.$n.'option_'.$langname.' value="'.escapehtml($langname).'" >'.escapehtml($langname).'</option>';
			$str .= '</select>';
		}
		$str .= '</td></tr>';
		$str .= '<tr><td class="msglabel">'._L("Phone").':</td><td><input style="float: left; margin-top: 3px" type="text" id='.$n.'phone value="'.$this->args['phone'].'" />';
		$str .= icon_button("Call To Record","/diagona/16/151","new Easycall('".$n."','".$language[0]."').start();",null,'id="'.$n.'recordbutton"').'<div style="padding-top:4px; margin-left:5px" id='.$n.'progress /></td></tr>';
		$str .= '<tr><td class="msglabel">'._L("Messages").':</td>';
		$str .= '<td><table id="'.$n.'messages" style="border: 1px solid gray; width: 80%">';
		$str .= '<tr><th colspan=2 class="windowRowHeader">'._L("Message Language").'</th><th class="windowRowHeader" width="30%">'._L("Actions").'</th></tr>';
		
		$str .= '</table></td></tr>';
		$str .= '</table>';
		$str .= '<script type="text/javascript" src="script/easycall.js"></script><script>
				new Easycall("'.$n.'","'.$language[0].'").load();
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
				return "$this->label doesnt appear to exist for this uer. Select another option or go create a message.";
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

class ValCallMePhone extends Validator {
	var $onlyserverside = true;
	function validate ($value, $args) {
		global $USER;
		if (!$USER->authorize("starteasy"))
			return "$this->label is not allowed for this user account.";
		$max = getSystemSetting('easycallmax',10);
		$min = getSystemSetting('easycallmin',10);
		$value = ereg_replace("[^0-9]*","",$value);
		if (!ereg("^-?[0-9]*\.?[0-9]+$",$value))
			return "$this->label must be a number";
		if ($min == $max && $min == 10 && $err = Phone::validate($value)) {
			$errmsg = "$this->label appers incorrect.  ";
			foreach ($err as $e) {
				$errmsg .= $e . " ";
			}
			return $errmsg;
		}
		if (strlen($value) < $min)
			return "$this->label cannot be less than $min digits";
		if (strlen($value) > $max)
			return "$this->label cannot be greater than $max digits";
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
		$listids = json_decode($value);
		if (empty($listids))
			return "Please specify a list";
		// foreach ($listids as $id) {
			// $list = new PeopleList($id);
			// $renderedlist = new RenderedList($list);
			// $renderedlist->calcStats();
			// if ($renderedlist->total == 0)
				// return "Sorry, lists are empty";
		// }
		return true;
	}
}

////////////////////////////////////////////////////////////////////////////////
// Form Items
////////////////////////////////////////////////////////////////////////////////
class JobWiz_basic extends WizStep {
	function getForm($postdata, $curstep) {
		global $USER;
		$userjobtypes = JobType::getUserJobTypes();
		$jobtypes = array();
		foreach ($userjobtypes as $id => $jobtype)
			if (!$jobtype->issurvey)
				$jobtypes[$id] = $jobtype->info;
			
		$formdata = array(
			"name" => array(
				"label" => "Name",
				"value" => "",
				"validators" => array(
					array("ValRequired"),
					array("ValLength","max" => 50)
				),
				"control" => array("TextField","maxlength" => 50),
				"helpstep" => 1
			),
			"jobtype" => array(
				"label" => "Type/Category",
				"value" => "",
				"validators" => array(
					array("ValRequired")
				),
				"control" => array("RadioButton", "values" => $jobtypes),
				"helpstep" => 2
			),
			"listmethod" => array(
				"label" => "Contact List",
				"validators" => array(
					array("ValRequired"),
					array("ValContactListMethod")
				),
				"value" => "",
				"control" => array("RadioButton", "values" => array(
					"pick" => "Match contacts using rules, Choose an existing list",
					"search" => "Upload, Manually Enter, Choose from Address Book, or Search for individual contacts"
				)),
				"helpstep" => 3
			),
			"package" => array(
				"label" => "Notification Method",
				"validators" => array(
					array("ValRequired")
				),
				"value" => "",
				"control" => array("RadioButton", "values" => array(
					"easycall" => "EasyCall (Record via Phone -> Automatic Email Recording -> Automatic SMS Message)",
					"express" => "ExpressText (Text-to-speech Phone with Auto Translate -> Email -> SMS) ",
					"personalized" => "Personalized (Record via Phone / Type Email -> SMS)",
					"custom" => "Custom (Pick your own detailed options)"
				)),
				"helpstep" => 4
			),
		);
		$helpsteps = array (
			"Welcome to the Job Wizard. This is a guided 5 step process.",
			"This is your Job's name and Type. Job names are important, and should be descriptive. 
			A good example is 'Standardized testing reminder', or 'Early dismissal'.",
			"Job Types are used to determine which phones or emails we should notify. Choosing the appropriate Job Type is important for effective communication.",
			"Adding contacts based on rules allows you to specify rules like 'Everyone from school XYZ'.<br><br>You may also have predefined Lists, and use them here.<br><br>If you need to use the list upload feature, manually enter contacts, or create a custom list of contacts, you will need to use the List Builder.",
			"blah blah blah",
		);
		return new Form("basic",$formdata,$helpsteps);
	}
}

class JobWiz_listChoose extends WizStep {
	function getForm($postdata, $curstep) {
		return new ListForm("listChoose");
	}
}

class JobWiz_messageType extends WizStep {
	function getForm($postdata, $curstep) {
		// Form Fields.
		$formdata = array();
		$helpsteps = array(_L("Select messages."));
		$values = array();
		global $USER;
		$deliverytypes = array(
			'phone'=>array('sendphone',"Phone Call"),
			'email'=>array('sendemail',"E-Mail"),
			'sms'=>array('sendsms',"SMS to Mobile Phone"),
			'print'=>array('sendmessage',"Print To Mail Document"));
		foreach ($deliverytypes as $checkvalue => $checkname)
			if ($USER->authorize($checkname[0]))
				$values[$checkvalue] = $checkname[1];
				
		$helpstepnum = 1;
		
		$formdata["type"] = array(
			"label" => _L("Message Type"),
			"value" => "",
			"validators" => array(
				array("ValRequired")
			),
			"control" => array("MultiCheckBox", "values"=>$values),
			"helpstep" => $helpstepnum
		);
		$helpsteps[$helpstepnum++] = _L("Select a method or methods for message delivery.");

		return new Form("messageSelect",$formdata,$helpsteps);
	}
	
	//returns true if this step is enabled
	function isEnabled($postdata, $step) {
		if (isset($postdata['/basic']['package']) && 
			$postdata['/basic']['package'] == "custom") {
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
		$helpsteps = array(_L("Select messages."));
		global $USER;
		$values = array();
		if ($USER->authorize("starteasy") && $USER->authorize("sendphone"))
			$values["record"] = _L("Call Me to Record");
		$values["text"] =_L("Type A Message");
		$values["pick"] =_L("Select Saved Message");
		
		$helpstepnum = 1;
		
		if ($USER->authorize("sendphone") && in_array('phone',$postdata['/message/pick']['type'])) {
			$formdata["phone"] = array(
				"label" => _L("Phone Message"),
				"value" => "",
				"validators" => array(
					array("ValRequired"),
					array("ValHasMessage","type"=>"phone")
				),
				"control" => array("RadioButton","values"=>$values),
				"helpstep" => $helpstepnum
			);
			$helpsteps[$helpstepnum++] = _L("Select a method for message delivery via telephone.");
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
				"helpstep" => $helpstepnum
			);
			$helpsteps[$helpstepnum++] = _L("Select a method for message delivery via e-mail.");
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
				"helpstep" => $helpstepnum
			);
			$helpsteps[$helpstepnum++] = _L("Select a method for message delivery via SMS.");
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
				"helpstep" => $helpstepnum
			);
			$helpsteps[$helpstepnum++] = _L("Select a method for message delivery via SMS.");
		}
		
		return new Form("messageSelect",$formdata,$helpsteps);
	}
	
	//returns true if this step is enabled
	function isEnabled($postdata, $step) {
		if (isset($postdata['/basic']['package']) && 
			$postdata['/basic']['package'] == "custom") {
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
		$helpsteps = array(_L("description."));
		$helpstepnum = 1;
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
			
		$formdata["message"] = array(
			"label" => "Select A Message",
			"value" => "",
			"validators" => array(
				array("ValRequired"),
				array("ValInArray","values"=>$values)
			),
			"control" => array("SelectMessage", "type"=>"phone", "width"=>"80%", "values"=>$phonemessage),
			"helpstep" => $helpstepnum
		);
		$helpsteps[$helpstepnum++] = _L("Select from list of existing messages");

		return new Form("messagePhoneChoose",$formdata,$helpsteps);
	}
	
	//returns true if this step is enabled
	function isEnabled($postdata, $step) {
		if (isset($postdata['/message/select']['phone']) && 
			$postdata['/message/select']['phone'] == "pick") {
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
		
		$formdata["message"] = array(
			"label" => _L("Phone Message"),
			"value" => "",
			"validators" => array(
				array("ValRequired")
			),
			"control" => array("TextAreaPhone","width"=>"80%","rows"=>10,"language"=>"english","voice"=>"female"),
			"helpstep" => $helpstepnum
		);

		$formdata["translate"] = array(
			"label" => _L("Translate"),
			"value" => ($postdata['/basic']['package'] == "express")?true:false,
			"validators" => array(),
			"control" => array("CheckBox"),
			"helpstep" => $helpstepnum
		);
		$helpsteps[$helpstepnum++] = _L("Automatically translate into alternate languages.");
		
		return new Form("messagePhoneText",$formdata,$helpsteps);
	}
	
	//returns true if this step is enabled
	function isEnabled($postdata, $step) {
		if ((isset($postdata['/basic']['package']) && 
			$postdata['/basic']['package'] == "express") ||
			(isset($postdata['/message/select']['phone']) && 
			$postdata['/message/select']['phone'] == "text")
		) {
			return true;
		} else {
			return false;
		}
	}
}

class JobWiz_messagePhoneTranslate extends WizStep {
	function getForm($postdata, $curstep) {
		// Form Fields.
		$formdata = array();
		$helpsteps = array(_L("description."));
		
		$helpstepnum = 1;
		
		$formdata["a"] = array(
			"label" => _L("Review Translations"),
			"control" => array("FormHtml","html"=>"<h1>Wicked Awesome Translation Review Widget</h1>"),
			"helpstep" => $helpstepnum
		);
		$helpsteps[$helpstepnum++] = _L("c");

		// TODO: Need translation review page

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
		$formdata = array();
		$helpsteps = array(_L("description."));
		global $USER;
		$helpstepnum = 1;
		$syslangs = DBFindMany("Language","from language order by name");
		$langs = array("Default");
		foreach ($syslangs as $langid => $language)
			$langs[] = $syslangs[$langid]->name;
				
		$formdata["callme"] = array(
			"label" => _L("Call Me"),
			"value" => "",
			"validators" => array(
				array("ValRequired"),
				array("ValEasycall")
			),
			"control" => array("CallMe", "width"=>"80%", "phone"=>$USER->phone, "language"=>$langs),
			"helpstep" => $helpstepnum
		);
		$helpsteps[$helpstepnum++] = _L("c");

		return new Form("messagePhoneCallMe",$formdata,$helpsteps);
	}
	
	//returns true if this step is enabled
	function isEnabled($postdata, $step) {
		if ((isset($postdata['/basic']['package']) && 
			($postdata['/basic']['package'] == "easycall" ||
				$postdata['/basic']['package'] == "personalized") ||
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
		$helpsteps = array(_L("description."));
		$helpstepnum = 1;
		$messages = array(array("name"=>"--- Select One ---"));
		$values = array();
		global $USER;
		
		$messagelist = DBFindMany("Message","from message where userid=" . $USER->id ." and deleted=0 and type='email' order by name");
		foreach ($messagelist as $id => $message) {
			$messages[$id]['name'] = $message->name;
			$values[] = $id;
		}
			
		$formdata["phoneSelect"] = array(
			"label" => "Select A Message",
			"validators" => array(
				array("ValRequired"),
				array("ValInArray", "values"=>$values)
			),
			"value" => "",
			"control" => array("SelectMessage","type"=>"email", "width"=>"80%", "values"=>$messages),
			"helpstep" => $helpstepnum
		);
		$helpsteps[$helpstepnum++] = _L("Select from list of existing messages");
		
		// TODO: Need to be able to review the email message in the form
		
		return new Form("messageEmailChoose",$formdata,$helpsteps);
	}
	
	//returns true if this step is enabled
	function isEnabled($postdata, $step) {
		if (isset($postdata['/message/select']['email']) && 
			$postdata['/message/select']['email'] == "pick") {
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
			"value" => $postdata['/basic']['name'],
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
			"value" => ($postdata['/basic']['package'] == "express")?true:false,
			"validators" => array(),
			"control" => array("CheckBox"),
			"helpstep" => $helpstepnum
		);
		$helpsteps[$helpstepnum++] = _L("Automatically translate into alternate languages.");

		$formdata["attachment"] = array(
			"label" => _L("Attach File"),
			"value" => "",
			"validators" => array(),
			"control" => array("CheckBox"),
			"helpstep" => $helpstepnum
		);
		$helpsteps[$helpstepnum++] = _L("Include file attachment.");
		
		return new Form("messageEmailText",$formdata,$helpsteps);
	}
	
	//returns true if this step is enabled
	function isEnabled($postdata, $step) {
		if ((isset($postdata['/basic']['package']) && 
			($postdata['/basic']['package'] == "express" ||
			$postdata['/basic']['package'] == "personalized")) ||
			(isset($postdata['/message/select']['email']) && 
			$postdata['/message/select']['email'] == "text")
		) {
			return true;
		} else {
			return false;
		}
	}
}

class JobWiz_messageEmailTranslate extends WizStep {
	function getForm($postdata, $curstep) {
		// Form Fields.
		$formdata = array();
		$helpsteps = array(_L("description."));
		
		$helpstepnum = 1;
		
		$formdata["translation"] = array(
				"label" => "Review Translations",
				"control" => array("FormHtml","html"=>"<h1>Wicked Awesome Translation Review Widget</h1>"),
				"helpstep" => $helpstepnum
		);
		$helpsteps[$helpstepnum++] = _L("c");
		
		// TODO: Need translation review page

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
		$helpsteps = array(_L("description."));
		$helpstepnum = 1;
		$messages = array(array("name"=>"--- Select One ---"));
		$values = array();
		global $USER;
		
		$messagelist = DBFindMany("Message","from message where userid=" . $USER->id ." and deleted=0 and type='sms' order by name");
		foreach ($messagelist as $id => $message) {
			$messages[$id]['name'] = $message->name;
			$values[] = $id;
		}

		$formdata["phoneSelect"] = array(
			"label" => "Select A Message",
			"validators" => array(
				array("ValRequired"),
				array("ValInArray", "values"=>$values)
			),
			"value" => "",
			"control" => array("SelectMessage", "type"=>"sms", "width"=>"80%", "values"=>$messages),
			"helpstep" => $helpstepnum
		);
		$helpsteps[$helpstepnum++] = _L("Select from list of existing messages");
		
		// TODO: Need message preview

		return new Form("messageSmsChoose",$formdata,$helpsteps);
	}
	
	//returns true if this step is enabled
	function isEnabled($postdata, $step) {
		if (isset($postdata['/message/select']['sms']) && 
			$postdata['/message/select']['sms'] == "pick") {
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
		if ((isset($postdata['/basic']['package']) && 
			($postdata['/basic']['package'] == "express" ||
			$postdata['/basic']['package'] == "personalized")) ||
			(isset($postdata['/message/select']['sms']) && 
			$postdata['/message/select']['sms'] == "text")
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
		$formdata = array();
		$helpsteps = array(_L("description."));
		
		$helpstepnum = 1;
		
		$formdata["schedule"] = array(
			"label" => _L("Delivery Schedule"),
			"value" => "",
			"validators" => array(
				array("ValRequired")
			),
			"control" => array("RadioButton","values"=>array(
				"now"=>_L("Now!"),
				"schedule"=>_L("Later"),
				"template"=>_L("Save as template")
			)),
			"helpstep" => $helpstepnum
		);
		$helpsteps[$helpstepnum++] = _L("Select when to send this message.");

		$formdata["test"] = array(
			"label" => _L("Send Test"),
			"value" => "",
			"validators" => array(),
			"control" => array("CheckBox"),
			"helpstep" => $helpstepnum
		);
		$helpsteps[$helpstepnum++] = _L("Send a test to your phone/email/sms.");

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
