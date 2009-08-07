<?
////////////////////////////////////////////////////////////////////////////////
// global wizard functions
////////////////////////////////////////////////////////////////////////////////

// Check the whole of the wizard post data and include user authorization to figure out if this wizard has an assigned valid phone message
function wizHasPhone($postdata) {
	global $USER;
	if ($USER->authorize("sendphone") && (
		(isset($postdata["/start"]["package"]) && $postdata["/start"]["package"] == "easycall" && isset($postdata["/message/phone/callme"]["message"]) && strlen($postdata["/message/phone/callme"]["message"]) > 2) ||
		(isset($postdata["/start"]["package"]) && $postdata["/start"]["package"] == "express" && isset($postdata["/message/phone/text"]["message"]) && strlen($postdata["/message/phone/text"]["message"]) > 2) ||
		(isset($postdata["/start"]["package"]) && $postdata["/start"]["package"] == "personalized" && isset($postdata["/message/phone/callme"]["message"]) && strlen($postdata["/message/phone/callme"]["message"]) > 2) ||
		(isset($postdata["/start"]["package"]) && $postdata["/start"]["package"] == "custom" && isset($postdata["/message/pick"]["type"]) && in_array('phone', $postdata["/message/pick"]["type"]) && (
			(isset($postdata["/message/select"]["phone"]) && $postdata["/message/select"]["phone"] == "record" && isset($postdata["/message/phone/callme"]["message"]) && strlen($postdata["/message/phone/callme"]["message"]) > 2) ||
			(isset($postdata["/message/select"]["phone"]) && $postdata["/message/select"]["phone"] == "text" && isset($postdata["/message/phone/text"]["message"]) && strlen($postdata["/message/phone/text"]["message"]) > 2) || 
			(isset($postdata["/message/select"]["phone"]) && $postdata["/message/select"]["phone"] == "pick" && isset($postdata["/message/phone/pick"]["Default"]) && $postdata["/message/phone/pick"]["Default"])
		))))
		return true;
	return false;
}

// Check the whole of the wizard post data and include user authorization to figure out if this wizard has an assigned valid email message
function wizHasEmail($postdata) {
	global $USER;
	if ($USER->authorize("sendemail") && (
		(isset($postdata["/start"]["package"]) && $postdata["/start"]["package"] == "easycall" && isset($postdata["/message/phone/callme"]["message"]) && strlen($postdata["/message/phone/callme"]["message"]) > 2 && $USER->authorize("sendphone")) ||
		(isset($postdata["/start"]["package"]) && $postdata["/start"]["package"] == "express" && isset($postdata["/message/email/text"]["message"]) && $postdata["/message/email/text"]["message"]) ||
		(isset($postdata["/start"]["package"]) && $postdata["/start"]["package"] == "personalized" &&isset($postdata["/message/email/text"]["message"]) && $postdata["/message/email/text"]["message"]) ||
		(isset($postdata["/start"]["package"]) && $postdata["/start"]["package"] == "custom" && isset($postdata["/message/pick"]["type"]) && in_array('email', $postdata["/message/pick"]["type"]) && (
			(isset($postdata["/message/select"]["email"]) && $postdata["/message/select"]["email"] == "record" && $USER->authorize("sendphone") && (
				(isset($postdata["/message/select"]["phone"]) && $postdata["/message/select"]["phone"] == "record" && isset($postdata["/message/phone/callme"]["message"]) && strlen($postdata["/message/phone/callme"]["message"]) > 2) ||
				(isset($postdata["/message/select"]["phone"]) && $postdata["/message/select"]["phone"] == "text" && isset($postdata["/message/phone/text"]["message"]) && strlen($postdata["/message/phone/text"]["message"]) > 2) || 
				(isset($postdata["/message/select"]["phone"]) && $postdata["/message/select"]["phone"] == "pick" && isset($postdata["/message/phone/pick"]["message"]) && $postdata["/message/phone/pick"]["message"])
			)) ||
			(isset($postdata["/message/select"]["email"]) && $postdata["/message/select"]["email"] == "text" && isset($postdata["/message/email/text"]["message"]) && $postdata["/message/email/text"]["message"]) || 
			(isset($postdata["/message/select"]["email"]) && $postdata["/message/select"]["email"] == "pick" && isset($postdata["/message/email/pick"]["Default"]) && $postdata["/message/email/pick"]["Default"])
		))))
		return true;
	return false;
}

// Check the whole of the wizard post data and include user authorization to figure out if this wizard has an assigned valid sms message
function wizHasSms($postdata) {
	global $USER;
	if ($USER->authorize("sendsms") && getSystemSetting("_hassms") && (
		(isset($postdata["/start"]["package"]) && $postdata["/start"]["package"] == "easycall" && isset($postdata["/message/phone/callme"]["message"]) && strlen($postdata["/message/phone/callme"]["message"]) > 2 && $USER->authorize("sendphone")) ||
		(isset($postdata["/start"]["package"]) && $postdata["/start"]["package"] == "express" && isset($postdata["/message/sms/text"]["message"]) && $postdata["/message/sms/text"]["message"]) ||
		(isset($postdata["/start"]["package"]) && $postdata["/start"]["package"] == "personalized" &&isset($postdata["/message/sms/text"]["message"]) && $postdata["/message/sms/text"]["message"]) ||
		(isset($postdata["/start"]["package"]) && $postdata["/start"]["package"] == "custom" && isset($postdata["/message/pick"]["type"]) && in_array('sms', $postdata["/message/pick"]["type"]) && (
			(isset($postdata["/message/select"]["sms"]) && $postdata["/message/select"]["sms"] == "record" && $USER->authorize("sendphone") && (
				(isset($postdata["/message/select"]["phone"]) && $postdata["/message/select"]["phone"] == "record" && isset($postdata["/message/phone/callme"]["message"]) && strlen($postdata["/message/phone/callme"]["message"]) > 2) ||
				(isset($postdata["/message/select"]["phone"]) && $postdata["/message/select"]["phone"] == "text" && isset($postdata["/message/phone/text"]["message"]) && strlen($postdata["/message/phone/text"]["message"]) > 2) || 
				(isset($postdata["/message/select"]["phone"]) && $postdata["/message/select"]["phone"] == "pick" && isset($postdata["/message/phone/pick"]["message"]) && $postdata["/message/phone/pick"]["message"])
			)) ||
			(isset($postdata["/message/select"]["sms"]) && $postdata["/message/select"]["sms"] == "text" && isset($postdata["/message/sms/text"]["message"]) && $postdata["/message/sms/text"]["message"]) || 
			(isset($postdata["/message/select"]["sms"]) && $postdata["/message/select"]["sms"] == "pick" && isset($postdata["/message/sms/pick"]["message"]) && $postdata["/message/sms/pick"]["message"])
		))))
		return true;
	return false;
}

function wizHasTranslation($postdata) {
	if (((isset($postdata["/start"]["package"]) && $postdata["/start"]["package"] == 'express' && isset($postdata['/message/email/text']['translate']) && $postdata['/message/email/text']['translate']) ||
		(isset($postdata["/start"]["package"]) && $postdata["/start"]["package"] == 'personalized' && isset($postdata['/message/email/text']['translate']) && $postdata['/message/email/text']['translate']) ||
		(isset($postdata["/start"]["package"]) && $postdata["/start"]["package"] == 'custom' && isset($postdata['/message/pick']['type']) && in_array('email', $postdata['/message/pick']['type']) && 
			isset($postdata["/message/select"]['email']) && $postdata["/message/select"]['email'] == 'text' && isset($postdata['/message/email/text']['translate']) && $postdata['/message/email/text']['translate'])
		) || ((isset($postdata["/start"]["package"]) && $postdata["/start"]["package"] == 'express' && isset($postdata['/message/phone/text']['translate']) && $postdata['/message/phone/text']['translate']) ||
		(isset($postdata["/start"]["package"]) && $postdata["/start"]["package"] == 'custom' && isset($postdata['/message/pick']['type']) && in_array('phone', $postdata['/message/pick']['type']) && 
			isset($postdata["/message/select"]['phone']) && $postdata["/message/select"]['phone'] == 'text' && isset($postdata['/message/phone/text']['translate']) && $postdata['/message/phone/text']['translate'])
		)
	)
		return true;
	return false;
}

////////////////////////////////////////////////////////////////////////////////
// Custom Form Item Definitions
////////////////////////////////////////////////////////////////////////////////

class HtmlRadioButtonBigCheck extends FormItem {
	function render ($value) {
		$n = $this->form->name."_".$this->name;
		$str = '<input id="'.$n.'" name="'.$n.'" type="hidden" value="'.escapehtml($value).'"/>
			<div id="'.$n.'-container" class="htmlradiobuttonbigcheck"><table>';
		$count = 0;
		foreach ($this->args['values'] as $val => $html)  {
			$id = $n.'-'.$count++;
			$str .= '<tr>
				<td><img id="'.$id.'" name="'.$id.'" class="htmlRadioButtonBigCheck_checkImg" src="'.(($value == $val)?'img/bigradiobutton_checked.gif':'img/bigradiobutton.gif').'" onclick="htmlRadioButtonBigCheck_doCheck(\''.$this->form->name.'\', \''.$n.'\',  \''.$id.'\', \''.$n.'-container\', \''.$val.'\')" /></td>
				<td><label for="'.$id.'"><button type="button" style=" width: 100%;" onclick="htmlRadioButtonBigCheck_doCheck(\''.$this->form->name.'\', \''.$n.'\',  \''.$id.'\', \''.$n.'-container\', \''.$val.'\')">'.($html).'</button></label></td></tr>
				';
		}
		$str .= '</table>
			<script>
				function htmlRadioButtonBigCheck_doCheck(form, formitem, checkimg, container, value) {
					var form = $(form);
					var formitem = $(formitem);
					var checkimg = $(checkimg);
					var container =  $(container);
					formitem.value = value;
					container.select(\'[class="htmlRadioButtonBigCheck_checkImg"]\').each( function(i) {
						$(i).src = "img/bigradiobutton.gif";
					});
					checkimg.src = "img/bigradiobutton_checked.gif";
					// set helper step and validate
					var formvars = document.formvars[form.name];
					var fieldset = formitem.up("fieldset");
					var step = fieldset.id.substring(fieldset.id.lastIndexOf("_")+1)-1;
					form_go_step(form,null,step);
					form_do_validation(form, formitem);
				}
			</script>
		';
		return $str;
	}
}

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
			<div>'.icon_button(_L("Play"),"fugue/control",null,null,"id=\"".$n."-play\"").'</div>
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
				
				$("'.$n.'-textarea").observe("change", textAreaPhone_storedata.curry("'.$n.'"));
				$("'.$n.'-textarea").observe("blur", textAreaPhone_storedata.curry("'.$n.'"));
				$("'.$n.'-textarea").observe("keyup", textAreaPhone_storedata.curry("'.$n.'"));
				$("'.$n.'-textarea").observe("focus", textAreaPhone_storedata.curry("'.$n.'"));
				$("'.$n.'-textarea").observe("click", textAreaPhone_storedata.curry("'.$n.'"));
				$("'.$n.'-female").observe("click", textAreaPhone_storedata.curry("'.$n.'"));
				$("'.$n.'-male").observe("click", textAreaPhone_storedata.curry("'.$n.'"));
				
				var textAreaPhone_keyupTimer = null;
				function textAreaPhone_storedata(formitem, event) {
					var form = event.findElement("form");
					if (textAreaPhone_keyupTimer) {
						window.clearTimeout(textAreaPhone_keyupTimer);
					}
					textAreaPhone_keyupTimer = window.setTimeout(function () {
							var val = $(formitem).value.evalJSON();
							val.text = $(formitem+"-textarea").value;
							val.gender = ($(formitem+"-female").checked?"female":"male");
							$(formitem).value = Object.toJSON(val);
							form_do_validation(form, $(formitem));
						},
						event.type == "keyup" ? 500 : 100
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
			$language = array();
		
		$nophone = _L("Phone Number");
		$defaultphone = escapehtml((isset($this->args['phone']) && $this->args['phone'])?Phone::format($this->args['phone']):$nophone);
		if (!$value)
			$value = '{}';
		// Hidden input item to store values in
		$str = '<input id="'.$n.'" name="'.$n.'" type="hidden" value="'.escapehtml($value).'" />
		<div>
			<div id="'.$n.'_messages" style="padding: 6px; white-space:nowrap">
			</div>
			<div id="'.$n.'_altlangs" style="clear: both; padding: 5px; display: none">';
		if (count($language)) {
			$str .= '
				<div style="margin-bottom: 3px;">'._L("Add an alternate language?").'</div>
				<select id="'.$n.'_select" ><option value="0">-- '._L("Select One").' --</option>';
			foreach ($language as $langname) 
				$str .= '<option id="'.$n.'_select_'.$langname.'" value="'.escapehtml($langname).'" >'.escapehtml($langname).'</option>';
			$str .= '</select>';
		}
		$str .= '
			</div>
		</div>
		';

		// include the easycall javascript object. then load existing values.
		$str .= '<script type="text/javascript" src="script/easycall.js.php"></script>
			<script type="text/javascript">
				var msgs = '.$value.';
				// Load default. it is a special case
				new Easycall(
					"'.$this->form->name.'",
					"'.$n.'",
					"Default",
					"'.((isset($this->args['min']) && $this->args['min'])?$this->args['min']:"10").'",
					"'.((isset($this->args['max']) && $this->args['max'])?$this->args['max']:"10").'",
					"'.$defaultphone.'",
					"'.$nophone.'"
				).load();
				easycallRecordings++;
				Object.keys(msgs).each(function(lang) {
					new Easycall(
						"'.$this->form->name.'",
						"'.$n.'",
						lang,
						"'.((isset($this->args['min']) && $this->args['min'])?$this->args['min']:"10").'",
						"'.((isset($this->args['max']) && $this->args['max'])?$this->args['max']:"10").'",
						"'.$defaultphone.'",
						"'.$nophone.'"
					).load();
					easycallRecordings++;
				});
				if ($("'.$n.'_select")) {
					$("'.$n.'_select").observe("change", function (event) {
						e = event.element();
						if (e.value == 0)
							return;
						new Easycall(
							"'.$this->form->name.'",
							"'.$n.'",
							$("'.$n.'_select").value,
							"'.((isset($this->args['min']) && $this->args['min'])?$this->args['min']:"10").'",
							"'.((isset($this->args['max']) && $this->args['max'])?$this->args['max']:"10").'",
							"'.$defaultphone.'",
							"'.$nophone.'"
						).setupRecord();
					});
				}
			</script>';
		return $str;
	}
}

////////////////////////////////////////////////////////////////////////////////
// Validators
////////////////////////////////////////////////////////////////////////////////
class ValJobName extends Validator {
	var $onlyserverside = true;
	
	function validate ($value, $args) {
		global $USER;
		$jobcount = QuickQuery("select count(id) from job where not deleted and userid=? and name=? and status in ('new','scheduled','processing','procactive','active')", false, array($USER->id, $value));
		if ($jobcount)
			return "$this->label: ". _L('There is already an active notification with this name. Please choose another.');
		return true;
	}
}

class ValHasMessage extends Validator {
	var $onlyserverside = true;
	
	function validate ($value, $args) {
		global $USER;
		if ($value == 'pick') {
			$msgcount = (QuickQuery("select count(id) from message where userid=? and not deleted and type=?", false, array($USER->id, $args['type'])));
			if (!$msgcount)
				return "$this->label: ". _L('There are no saved messages of this type.');
		}
		return true;
	}
}

class ValEasycall extends Validator {
	var $onlyserverside = true;
	function validate ($value, $args) {
		global $USER;
		if (!$USER->authorize("starteasy"))
			return "$this->label "._L("is not allowed for this user account");
		$values = json_decode($value);
		if ($value == "{}")
			return "$this->label "._L("has messages that are not recorded");
		foreach ($values as $lang => $message)
			$msg = new Message($message+0);
			if ($msg->userid !== $USER->id)
				return "$this->label "._L("has invalid message values");
			if (!$message)
				return "$this->label "._L("has messages that are not recorded");
		return true;
	}
}

class ValLists extends Validator {
	var $onlyserverside = true;
	
	function validate ($value, $args) {
		global $USER;
		
		
		if (strpos($value, 'pending') !== false)
			return _L('Please finish adding this rule, or unselect the field');
			
		$listids = json_decode($value);
		if (empty($listids))
			return _L("Please add a list");
		$allempty = true;
		foreach ($listids as $listid) {
			if (!userOwns('list', $listid))
				return _L('You have specified an invalid list');
			$list = new PeopleList($listid + 0);
			$renderedlist = new RenderedList($list);
			$renderedlist->calcStats();
			if ($renderedlist->total >= 1)
				$allempty = false;
		}
		if ($allempty)
			return _L('All of these lists are empty');
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

class ValTimeWindowCallEarly extends Validator {
	var $onlyserverside = true;
	function validate ($value, $args, $requiredvalues) {
		if ((strtotime($value) + 3600) > strtotime($requiredvalues['calllate']))
			return $this->label. " ". _L('There must be a minimum of one hour between start and end time');
		return true;
	}
}

class ValTimeWindowCallLate extends Validator {
	var $onlyserverside = true;
	function validate ($value, $args, $requiredvalues) {
		if ((strtotime($value) - 3600) < strtotime($requiredvalues['callearly']))
			return $this->label. " ". _L('There must be a minimum of one hour between start and end time');
		return true;
	}
}

class ValDate extends Validator {
	var $onlyserverside = true;
	function validate ($value, $args) {
		if (strtotime($value) < strtotime($args['min']))
			return $this->label. " ". _L('cannot be a date earlier than %s', $args['min']);
		if (isset($args['max']))
			if (strtotime($value) > strtotime($args['max']))
				return $this->label. " ". _L('cannot be a date later than %s', $args['max']);
			
		return true;
	}
}


////////////////////////////////////////////////////////////////////////////////
// Form Items
////////////////////////////////////////////////////////////////////////////////
class JobWiz_start extends WizStep {
	function getForm($postdata, $curstep) {
		global $USER;
		
		$wizJobType = isset($_SESSION['wizard_job']['jobtype'])?$_SESSION['wizard_job']['jobtype']:"all";
		$userjobtypes = JobType::getUserJobTypes();
		$jobtypes = array();
		$jobtips = array();
		foreach ($userjobtypes as $id => $jobtype) {
			switch ($wizJobType) {
				case "emergency":
					if ($jobtype->systempriority == 1)
						$jobtypes[$id] = $jobtype->name;
					break;
				case "normal":
					if ($jobtype->systempriority > 1)
						$jobtypes[$id] = $jobtype->name;
					break;
				default:
					$jobtypes[$id] = $jobtype->name;
					break;
			}
			$jobtips[$id] = escapehtml($jobtype->info);
		}
		
		$deliverytypes = array();
		foreach (array('sendphone', 'sendemail', 'sendsms') as $deliverytype) {
			if ($USER->authorize($deliverytype))
				$deliverytypes[$deliverytype] = true;
			else
				$deliverytypes[$deliverytype] = false;
		}

		if ($deliverytypes['sendsms'] && !getSystemSetting('_hassms'))
			$deliverytypes['sendsms'] = false;
		
		$packageDetails = array(
			"easycall" => array(
				0 => _L('EasyCall'),
				1 => _L('Record Phone Message'),
				2 => _L('Auto Email and SMS Text Alerts'),
				"icon" => "img/record.gif",
				"label" => _L("Record"),
				"enabled" => false
			),
			"express" => array(
				0 => _L('Type  All Messages'),
				1 => _L('Text-to-Speech Phone'),
				2 => _L('Automatic Translation'),
				"icon" => "img/write.gif",
				"label" => _L("Write"), "enabled" => false
			),
			"personalized" => array(
				0 => _L('Record Phone Message'),
				1 => _L('Type Email, and SMS Text'),
				2 => _L('Automatic Translation'),
				"icon" => "img/recordandwrite.gif",
				"label" => _L("Record & Write"), "enabled" => false
			),
			"custom" => array(
				0 => "",
				1 => "",
				2 => "",
				"icon" => "img/customize.gif",
				"label" => _L("Customize"), "enabled" => true
			)
		);
		// if the user isn't authorized to send multi lingual messages then don't tell them they can
		if (!$USER->authorize("sendmulti")) {
			$packageDetails["express"][2] = "";
			$packageDetails["personalized"][2] = "";
		}
		
		// All delivery types allowed
		if ($deliverytypes['sendphone'] && $deliverytypes['sendemail'] && $deliverytypes['sendsms']) {
			$packageDetails["easycall"]["enabled"] = true;
			$packageDetails["express"]["enabled"] = true;
			$packageDetails["personalized"]["enabled"] = true;
		// Only phone
		} elseif ($deliverytypes['sendphone'] && !$deliverytypes['sendemail'] && !$deliverytypes['sendsms']) {
			$packageDetails["easycall"][2] = "";
			$packageDetails["easycall"]["enabled"] = true;
		// Only email
		} elseif ($deliverytypes['sendemail'] && !$deliverytypes['sendphone'] && !$deliverytypes['sendsms']) {
			$packageDetails["express"][0] = _L('Type Email message');
			$packageDetails["express"]["enabled"] = true;
		// Only SMS
		} elseif ($deliverytypes['sendsms'] && !$deliverytypes['sendphone'] && !$deliverytypes['sendemail']) {
			$packageDetails["express"][0] = _L('Type SMS Text');
			$packageDetails["express"]["enabled"] = true;
		// Phone and Email
		} elseif ($deliverytypes['sendphone'] && $deliverytypes['sendemail'] && !$deliverytypes['sendsms']) {
			$packageDetails["easycall"][2] = _L('Auto Email Alerts');
			$packageDetails["easycall"]["enabled"] = true;
			$packageDetails["express"]["enabled"] = true;
			$packageDetails["personalized"][1] = _L('Type Email Message');
			$packageDetails["personalized"]["enabled"] = true;
		// Phone and SMS
		} elseif ($deliverytypes['sendphone'] && !$deliverytypes['sendemail'] && $deliverytypes['sendsms']) {
			$packageDetails["easycall"][2] = _L('Auto SMS Text Alerts');
			$packageDetails["easycall"]["enabled"] = true;
			$packageDetails["express"][2] = "";
			$packageDetails["express"]["enabled"] = true;
			$packageDetails["personalized"][1] = _L('Type SMS Text');
			$packageDetails["personalized"][2] = "";
			$packageDetails["personalized"]["enabled"] = true;
		// Email and SMS
		} elseif (!$deliverytypes['sendphone'] && $deliverytypes['sendemail'] && $deliverytypes['sendsms']) {
			$packageDetails["express"][0] = _L('Type Email and SMS Text');
			$packageDetails["express"]["enabled"] = true;
		}
		//<img style="float:left" src="img/icons/bullet_blue.gif"/>&nbsp;
		$packages = array();
		foreach ($packageDetails as $package => $details) {
			if ($details['enabled'])
				$packages[$package] = '
					<table align="left" style="border: 0px; margin: 0px; padding: 0px">
						<tr>
							<td style="border: 0px; margin: 0px; padding: 0px" align="center" valign="center"><div style="width: 94px; height: 88px; background: url('.$details['icon'].') no-repeat;"><div style="position: relative; top: 67px; width: 100%; font-size: 10px">'.escapehtml($details['label']).'</div></div></td>
							<td style="border: 0px; margin: 0px; padding: 0px;" align="left" valign="center">
								<ol>
									'.(($details[0])?'<li style="list-style-type: circle; list-style-image: url(img/icons/bullet_blue.gif); list-style-position: outside;">'.escapehtml($details[0]).'</li>':'').'
									'.(($details[1])?'<li style="list-style-type: circle; list-style-image: url(img/icons/bullet_blue.gif); list-style-position: outside;">'.escapehtml($details[1]).'</li>':'').'
									'.(($details[2])?'<li style="list-style-type: circle; list-style-image: url(img/icons/bullet_blue.gif); list-style-position: outside;">'.escapehtml($details[2]).'</li>':'').'
								</ol>
							</td>
						</tr>
					</table>';
		}
		
		$formdata = array($this->title);
		$formdata["name"] = array(
			"label" => _L("Job Name"),
			"fieldhelp" => _L("The name of your job will also become the subject line for generated emails. The best names are brief, but indicate the message content."),
			"value" => "",
			"validators" => array(
				array("ValRequired"),
				array("ValJobName"),
				array("ValLength","max" => 30)
			),
			"control" => array("TextField","maxlength" => 50, "size" => 50),
			"helpstep" => 1
		);
		$formdata["jobtype"] = array(
			"label" => _L("Type/Category"),
			"fieldhelp" => _L("These options determine how your message will be received."),
			"value" => "",
			"validators" => array(
				array("ValRequired"),
				array("ValInArray", "values" => array_keys($jobtypes))
			),
			"control" => array("RadioButton", "values" => $jobtypes, "hover" => $jobtips),
			"helpstep" => 2
		);
		
		$formdata["package"] = array(
			"label" => _L("Notification Method"),
			"fieldhelp" => _L("These are commonly used notification packages. For other options, select Custom."),
			"validators" => array(
				array("ValRequired"),
				array("ValInArray", "values" => array('easycall', 'express', 'personalized', 'custom'))
			),
			"value" => "",
			"control" => array("HtmlRadioButtonBigCheck", "values" => $packages),
			"helpstep" => 3
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
}

//get here at the Message step after clicking Custom. Let's you pick the types of messages for the job.
class JobWiz_messageType extends WizStep {
	function getForm($postdata, $curstep) {
		// Form Fields.
		$values = array();
		global $USER;
		$deliverytypes = array(
			'phone'=>array('sendphone', _L("Phone Call")),
			'email'=>array('sendemail', _L("Email")),
			'sms'=>array('sendsms', _L("SMS Text")));
		foreach ($deliverytypes as $checkvalue => $checkname)
			if ($USER->authorize($checkname[0]))
				$values[$checkvalue] = $checkname[1];

		if (isset($values['sms']) && !getSystemSetting('_hassms'))
			unset($values['sms']);
		
		$formdata[] = $this->title;
		$helpsteps = array(_L("Select a method or methods for message delivery."));
		$formdata["type"] = array(
			"label" => _L("Message Type"),
			"fieldhelp" => _L("Choose the types of messages you would like to send."),
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

//Custom notification package Step: Message>MessageSource Let's you choose how you want to create messages in a custom package.
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
				"fieldhelp" => _L("Contains the different ways you can create or reuse a phone message."),
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
			$values["record"] = _L("Automatic Email Alert");
		
		if ($USER->authorize("sendemail") && in_array('email',$postdata['/message/pick']['type'])) {
			$formdata["email"] = array(
				"label" => _L("Email Message"),
				"fieldhelp" => _L("Contains the different ways you can create or reuse an email message."),
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
			$values["record"] = _L("Automatic SMS Text Alert");

		if ($USER->authorize("sendsms") && in_array('sms',$postdata['/message/pick']['type'])) {
			$formdata["sms"] = array(
				"label" => _L("SMS Text"),
				"fieldhelp" => _L("Contains the different ways you can create or reuse an SMS Text."),
				"value" => "",
				"validators" => array(
					array("ValRequired"),
					array("ValHasMessage","type"=>"sms")
				),
				"control" => array("RadioButton","values"=>$values),
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

//This is for selecting a saved phone message.
class JobWiz_messagePhoneChoose extends WizStep {
	function getForm($postdata, $curstep) {
		global $USER;
		$phonemessage = array();
		$values = array();
		$langs = array();
		if ($USER->authorize("sendmulti")) {
			$syslangs = DBFindMany("Language","from language order by name");
			foreach ($syslangs as $langid => $language)
				if ($syslangs[$langid]->name !== "English")
					$langs[] = $syslangs[$langid]->name;
		}
		
		$messagelist = DBFindMany("Message","from message where userid=" . $USER->id ." and deleted=0 and type='phone' order by name");
		foreach ($messagelist as $id => $message) {
			$phonemessage[$id]['name'] = $message->name;
			$values[] = $id;
		}
		$formdata = array();

		$formdata[] = $this->title;
		$helpsteps = array(_L("Select from list of existing messages. If you do not find an appropriate message, you may click the Message Source link from the navigation on the left and choose to create a new message."));
		$formdata["Default"] = array(
			"label" => _L("Select a Message"),
			"value" => "",
			"validators" => array(
				array("ValRequired"),
				array("ValInArray","values"=>$values)
			),
			"control" => array("SelectMessage", "type"=>"phone", "width"=>"80%", "values"=>$phonemessage),
			"helpstep" => 1
		);
		if (count($langs)) $formdata[] = _L("Optional additional languages");
		foreach ($langs as $lang) {
			$helpsteps = array(_L("Select from list of existing messages. If you do not find an appropriate message, you may click the Message Source link from the navigation on the left and choose to create a new message."));
			$formdata[$lang] = array(
				"label" => $lang,
				"value" => "",
				"validators" => array(
					array("ValInArray","values"=>$values)
				),
				"control" => array("SelectMessage", "type"=>"phone", "width"=>"80%", "values"=>$phonemessage),
				"helpstep" => 1
			);
		}
		
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

//This is for typing in a phone message.
class JobWiz_messagePhoneText extends WizStep {
	function getForm($postdata, $curstep) {
		global $USER;
		// Form Fields.
		$helpsteps = array(_L("Enter your message text in the provided text area. Be sure to introduce yourself and give detailed information, including call back information if appropriate."));
		$formdata = array(
			$this->title,
			"message" => array(
				"label" => _L("Phone Message"),
				"fieldhelp" => _L('Enter the message you would like to send. Helpful tips for successful messages can be found at the Help link in the upper right corner.'),
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
			$helpsteps[] = _L("Automatically translate into alternate languages powered by Google Translate.");
			$formdata["translate"] = array(
				"label" => _L("Translate"),
				"fieldhelp" => _L('Check here if you would like to use automatic translation. Remember automatic translation is improving all the time, but it\'s not perfect yet. Be sure to preview and try reverse translation in the next screen.'),
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

//Displays the different message translations.
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
			_L("This is an automated translation powered by Google Translate. Please note that although machine translation is always improving, it is not perfect yet. You can try reverse translation for an idea of how well your message was translated.")
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

//Call me to Record
class JobWiz_messagePhoneCallMe extends WizStep {
	function getForm($postdata, $curstep) {
		// Form Fields.
		global $USER;
		$langs = array();
		if ($USER->authorize("sendmulti")) {
			$syslangs = DBFindMany("Language","from language order by name");
			foreach ($syslangs as $langid => $language)
				if ($syslangs[$langid]->name !== "English")
					$langs[] = $syslangs[$langid]->name;
		}
		$formdata = array($this->title);
		$formdata['tips'] = array(
			"label" => _L('Message Tips'),
			"control" => array("FormHtml", "html" => '
				<ul>
				<li style="list-style: url(img/icons/bullet_blue.gif)">'.escapehtml(_L('Introduce yourself')).'</li>
				<li style="list-style: url(img/icons/bullet_blue.gif)">'.escapehtml(_L('Clearly state the reason for the call')).'</li>
				<li style="list-style: url(img/icons/bullet_blue.gif)">'.escapehtml(_L('Repeat important information')).'</li>
				<li style="list-style: url(img/icons/bullet_blue.gif)">'.escapehtml(_L('Instruct recipients what to do should they have questions ')).'</li>
				</ul>
				'),
				"helpstep" => 1
			);
		$formdata["message"] = array(
			"label" => _L("Voice Recordings"),
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
		$helpsteps[] = _L("The system will call you at the number you enter in this form and guide you through a series of prompts to record your message. The default message is always required and will be sent to any recipients who do not have a language specified.<br><br>
		Choose which language you will be recording in and enter the phone number where the system can reach you. Then click \"Call Me to Record\" to get started. Listen carefully to the prompts when you receive the call. You may record as many different langauges as you need.
		");

		return new Form("messagePhoneCallMe",$formdata,$helpsteps);
	}
	
	//returns true if this step is enabled
	function isEnabled($postdata, $step) {
		global $USER;
		if (!$USER->authorize("sendphone"))
			return false;
		if ($USER->authorize("sendphone") && (
			(isset($postdata['/start']['package']) && (
				($postdata['/start']['package']== "easycall" || $postdata['/start']['package'] == "personalized") ||
				($postdata['/start']['package'] == "custom" && isset($postdata['/message/select']['phone']) && $postdata['/message/select']['phone'] == 'record')
			))
		)) {
			return true;
		} else {
			return false;
		}
	}
}

class JobWiz_messageEmailChoose extends WizStep {
	function getForm($postdata, $curstep) {
		$messages = array();
		$values = array();
		global $USER;
		$langs = array();
		if ($USER->authorize("sendmulti")) {
			$syslangs = DBFindMany("Language","from language order by name");
			foreach ($syslangs as $langid => $language)
				if ($syslangs[$langid]->name !== "English")
					$langs[] = $syslangs[$langid]->name;
		}
		$messagelist = DBFindMany("Message","from message where userid=" . $USER->id ." and deleted=0 and type='email' order by name");
		foreach ($messagelist as $id => $message) {
			$messages[$id]['name'] = $message->name;
			$values[] = $id;
		}
		
		// Form Fields.
		$formdata = array($this->title);
		$helpsteps = array(_L("Select from list of existing messages. If you do not find an appropriate message, you may click the Message Source link from the navigation on the left and choose to create a new message."));
		$formdata["Default"] = array(
			"label" => _L("Select a Message"),
			"validators" => array(
				array("ValRequired"),
				array("ValInArray", "values"=>$values)
			),
			"value" => "",
			"control" => array("SelectMessage","type"=>"email", "width"=>"80%", "values"=>$messages),
			"helpstep" => 1
		);

		if (count($langs)) $formdata[] = _L("Optional additional languages");
		foreach ($langs as $lang) {
			$helpsteps = array(_L("Select from list of existing messages. If you do not find an appropriate message, you may click the Message Source link from the navigation on the left and choose to create a new message."));
			$formdata[$lang] = array(
				"label" => $lang,
				"value" => "",
				"validators" => array(
					array("ValInArray","values"=>$values)
				),
				"control" => array("SelectMessage", "type"=>"phone", "width"=>"80%", "values"=>$messages),
				"helpstep" => 1
			);
		}

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
		$msgdata = isset($postdata['/message/phone/text']['message'])?json_decode($postdata['/message/phone/text']['message']):json_decode('{"text": ""}');
		// Form Fields.
		$formdata = array($this->title);
		$helpsteps = array(_L("Enter the address where you would like to receive replies."));
		$formdata["from"] = array(
			"label" => _L("From"),
			"fieldhelp" => _L('This is the address the email is coming from. Recipients will also be able to reply to this address.'),
			"value" => $USER->email,
			"validators" => array(
				array("ValRequired"),
				array("ValLength","min" => 3,"max" => 255),
				array("ValEmail")
				),
			"control" => array("TextField","max"=>255,"min"=>3,"size"=>35),
			"helpstep" => 1
		);
		
		$helpsteps[] = _L("Enter the subject of the email here.");
		$formdata["subject"] = array(
			"label" => _L("Subject"),
			"fieldhelp" => _L('The Subject will appear as the subject line of the email.'),
			"value" => $postdata['/start']['name'],
			"validators" => array(
				array("ValRequired"),
				array("ValLength","min" => 3,"max" => 255)
			),
			"control" => array("TextField","max"=>255,"min"=>3,"size"=>45),
			"helpstep" => 2
		);

		$helpsteps[] = _L("You may attach up to three files that are up to 2MB each. For greater security, only certain types of files are accepted.<br><br><b>Note:</b> Some email accounts may not accept attachments above a certain size and may reject your message.");
		$formdata["attachments"] = array(
			"label" => _L('Attachments'),
			"fieldhelp" => _L("You may attach up to three files that are up to 2MB each. For greater security, certain file types are not permitted. Be aware that some email accounts may not accept attachments above a certain size and may reject your message."),
			"value" => "",
			"validators" => array(array("ValEmailAttach")),
			"control" => array("EmailAttach","size" => 30, "maxlength" => 51),
			"helpstep" => 3
		);
		
		$helpsteps[] = _L("Email message body text goes here. Be sure to introduce yourself and give detailed information. For helpful message tips and ideas, click the Help link in the upper right corner of the screen.");
		$formdata["message"] = array(
			"label" => _L("Email Message"),
			"fieldhelp" => _L('Enter the message you would like to send. Helpful tips for successful messages can be found at the Help link in the upper right corner.'),
			"value" => $msgdata->text,
			"validators" => array(
				array("ValRequired"),
				array("ValLength","max" => 30000)
			),
			"control" => array("TextArea","rows"=>10,"cols"=>45),
			"helpstep" => 4
		);

		if ($USER->authorize('sendmulti')) {
			$helpsteps[] = _L("Automatically translate into alternate languages. Please note that automatic translation is always improving, but is not perfect yet. Try reverse translating your message for a preview of how well it translated.");
			$formdata["translate"] = array(
				"label" => _L("Translate"),
				"fieldhelp" => _L('Check this box to automatically translate your message using Google Translate.'),
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
			_L("This translation was automatically generated. Please note that automatic translation is always improving, but is not perfect yet. Try reverse translating your message for a preview of how well it translated.")
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
		$messages = array();
		$values = array();
		global $USER;
		
		$messagelist = DBFindMany("Message","from message where userid=" . $USER->id ." and deleted=0 and type='sms' order by name");
		foreach ($messagelist as $id => $message) {
			$messages[$id]['name'] = $message->name;
			$values[] = $id;
		}

		$formdata[] = $this->title;
		$formdata["message"] = array(
			"label" => "Select a Message",
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
		if (!$USER->authorize("sendsms") || !getSystemSetting("_hassms"))
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
		$helpsteps = array(_L("Enter the message you wish to deliver via SMS Text."));
		$formdata["message"] = array(
			"label" => _L("SMS Text"),
			"value" => $text,
			"validators" => array(
				array("ValRequired"),
				array("ValLength","max"=>160),
				array("ValRegExp","pattern" => "^[a-zA-Z0-9\x20\x09\x0a\x0b\x0C\x0d\x2a\x5e\<\>\?\,\.\/\{\}\|\~\!\@\#\$\%\&\(\)\_\+\']*$")
			),
			"control" => array("TextArea","rows"=>5,"cols"=>35,"counter"=>160),
			"helpstep" => 1
		);

		return new Form("messageSmsText",$formdata,$helpsteps);
	}
	
	//returns true if this step is enabled
	function isEnabled($postdata, $step) {
		global $USER;
		if (!$USER->authorize("sendsms") || !getSystemSetting("_hassms"))
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
		$wizHasPhoneMsg = wizHasPhone($postdata);
		$wizHasEmailMsg= wizHasEmail($postdata);

		$callearly = date("g:i a");
		$accessCallearly = $ACCESS->getValue("callearly");
		if (!$accessCallearly)
			$accessCallearly = "12:00 am";
		$calllate = $USER->getCallLate();
		if ((strtotime($callearly) + 3600) > strtotime($calllate))
			$calllate = date("g:i a", strtotime($callearly) + 3600);
		$accessCalllate = $ACCESS->getValue("calllate");
		if (!$accessCalllate)
			$accessCalllate = "11:59 pm";
		if (strtotime($calllate)  > strtotime($accessCalllate))
			$calllate = $accessCalllate;

		$menu = array();
		if (!((strtotime($callearly) >= strtotime($calllate)) || (strtotime($callearly) <= strtotime($accessCallearly)) || (strtotime($calllate) >= strtotime($accessCalllate))))
			$menu["now"] = _L("Now"). " ($callearly - $calllate)";
		$menu["schedule"] = _L("Schedule and Send");
		$menu["template"] = _L("Save for Later");
		
		$formdata = array($this->title);
		$helpsteps = array(_L("Select when to send this message."));
		$formdata["schedule"] = array(
			"label" => _L("Delivery Schedule"),
			"fieldhelp" => _L("Select when to send this message."),
			"value" => "",
			"validators" => array(
				array("ValRequired")
			),
			"control" => array("RadioButton","values"=>$menu),
			"helpstep" => 1
		);
		
		if ($wizHasEmailMsg || $wizHasPhoneMsg) {
			$helpsteps[] = _L("Set advanced options such as duplicate removal and number of days to run.");
			$formdata["advanced"] = array(
				"label" => _L("Advanced Options"),
				"fieldhelp" => _L('Check here if you would like to set additional options for this notification such as duplicate removal and number of days to run.'),
				"value" => "",
				"validators" => array(),
				"control" => array("CheckBox"),
				"helpstep" => 2
			);
		}
		
		return new Form("scheduleOptions",$formdata,$helpsteps);
	}
}

class JobWiz_scheduleDate extends WizStep {
	function getForm($postdata, $curstep) {
		global $USER;
		global $ACCESS;
		
		// Check to see if translation is used anywhere in the wizard. If it is, the job cannot be scheduled out more than 7 days.
		$translated = wizHasTranslation($postdata);
			
		// Form Fields.
		$formdata = array($this->title);
		
		$dayoffset = (strtotime("now") > strtotime($ACCESS->getValue("calllate")))?1:0;
		$helpsteps = array(_L("Choose a date for this notification to be delivered."));
		$formdata["date"] = array(
			"label" => _L("Start Date"),
			"fieldhelp" => _L("Notification will begin on the selected date."),
			"value" => "now + $dayoffset days",
			"validators" => array(
				array("ValRequired"),
				array("ValDate", "min" => date("m/d/Y", strtotime("now + $dayoffset days")))
			),
			"control" => array("TextDate", "size"=>12, "nodatesbefore" => $dayoffset),
			"helpstep" => 1
		);
		// If translation is used. don't show any dates in the calendar past 7 days from now.
		if ($translated) {
			$formdata["date"]["control"]["nodatesafter"] = 7;
			$formdata["date"]["validators"][1]["max"] = date("m/d/Y", strtotime("+ 7 days"));
		}

		$helpsteps[] = _L("The Delivery Window designates the earliest call time and the latest call time allowed for notification delivery.");
		$startvalues = newform_time_select(NULL, $ACCESS->getValue('callearly'), $ACCESS->getValue('calllate'), $USER->getCallEarly());
		$formdata["callearly"] = array(
			"label" => _L("Start Time"),
			"fieldhelp" => ("This is the earliest time to send calls. This is also determined by your security profile."),
			"value" => $USER->getCallEarly(),
			"validators" => array(
				array("ValRequired"),
				array("ValTimeCheck", "min" => $ACCESS->getValue('callearly'), "max" => $ACCESS->getValue('calllate')),
				array("ValTimeWindowCallEarly")
			),
			"requires" => array("calllate"),
			"control" => array("SelectMenu", "values"=>$startvalues),
			"helpstep" => 2
		);
		$endvalues = newform_time_select(NULL, $ACCESS->getValue('callearly'), $ACCESS->getValue('calllate'), $USER->getCallLate());
		$formdata["calllate"] = array(
			"label" => _L("End Time"),
			"fieldhelp" => ("This is the latest time to send calls. This is also determined by your security profile."),
			"value" => $USER->getCallLate(),
			"validators" => array(
				array("ValRequired"),
				array("ValTimeCheck", "min" => $ACCESS->getValue('callearly'), "max" => $ACCESS->getValue('calllate')),
				array("ValTimeWindowCallLate")
			),
			"requires" => array("callearly"),
			"control" => array("SelectMenu", "values"=>$endvalues),
			"helpstep" => 2
		);

		return new Form("scheduleDate",$formdata,$helpsteps);
	}
	
	//returns true if this step is enabled
	function isEnabled($postdata, $step) {
		if (isset($postdata['/schedule/options']['schedule']) && 
			$postdata['/schedule/options']['schedule'] == "schedule"
		) {
			return true;
		} else {
			return false;
		}
	}
}
class JobWiz_scheduleAdvanced extends WizStep {
	function getForm($postdata, $curstep) {
		global $USER;
		global $ACCESS;
		$wizHasPhoneMsg = wizHasPhone($postdata);
		$wizHasEmailMsg= wizHasEmail($postdata);
		$helpstepnum = 1;
		
		$helpsteps = array(_L("Specify the number of days for which you would like your job to run before it stops."));
		$maxjobdays = $USER->getSetting("maxjobdays", $ACCESS->getValue('maxjobdays'));
		$maxdays = $ACCESS->getValue('maxjobdays', 7);
		
		$formdata = array($this->title);
		if ($wizHasPhoneMsg) {
			if ($ACCESS->getPermission('setcallerid') && !getSystemSetting('_hascallback')) {
				$helpsteps[] = _L("This option will set the number displayed on the recipient's home or cellular phone.");
				$formdata["callerid"] = array(
					"label" => _L("Caller ID"),
					"fieldhelp" => _L('This option will set the Caller ID when the person is called.'),
					"value" => Phone::format($USER->getSetting("callerid", getSystemSetting("callerid"))),
					"validators" => array(
						array("ValLength","min" => 3,"max" => 20),
						array("ValPhone")
					),
					"control" => array("TextField","maxlength" => 20, "size" => 15),
					"helpstep" => $helpstepnum++
				);
			}
			$formdata["maxjobdays"] = array(
				"label" => _L("Days to Run"),
				"fieldhelp" => ("Use this menu to set the default number of days your jobs should run."),
				"value" => $maxjobdays,
				"validators" => array(
					array("ValInArray", "values" => range(1,$maxdays))
				),
				"control" => array("SelectMenu", "values"=>array_combine(range(1,$maxdays),range(1,$maxdays))),
				"helpstep" => $helpstepnum++
			);
			if ($ACCESS->getPermission('leavemessage')) {
				$helpsteps[] = _L("Enable the Voice Response feature to allow recipients to leave you a message. Make sure to include instructions in your message.");
				$formdata["leavemessage"] = array(
					"label" => _L("Voice Response"),
					"fieldhelp" => _L('Allow call recipients to leave a message.'),
					"value" => $USER->getSetting("leavemessage", true),
					"validators" => array(),
					"control" => array("CheckBox"),
					"helpstep" => $helpstepnum++
				);
			}
			
			if ($ACCESS->getPermission('messageconfirmation')) {
				$helpsteps[] = _L("This option allows recipients to respond to your phone message with a yes by pressing 1 or a no by pressing 2.");
				$formdata["messageconfirmation"] = array(
					"label" => _L("Confirmation"),
					"fieldhelp" => _L('Allow message confirmation by recipients.'),
					"value" => $USER->getSetting("messageconfirmation", false),
					"validators" => array(),
					"control" => array("CheckBox"),
					"helpstep" => $helpstepnum++
				);
			}
			$helpsteps[] = _L("Indicates that duplicate phone numbers in the list should receive only one notification.");
			$formdata["skipduplicates"] = array(
				"label" => _L("Skip Duplicate Phones"),
				"fieldhelp" => _L('Indicates that duplicate phone numbers in the list should receive only one call.'),
				"value" => $USER->getSetting("skipduplicates", true),
				"validators" => array(),
				"control" => array("CheckBox"),
				"helpstep" => $helpstepnum++
			);
		}
		if ($wizHasEmailMsg) {
			$helpsteps[] = _L("Indicates that duplicate Emails in the list should receive only one notification.");
			$formdata["skipemailduplicates"] = array(
				"label" => _L("Skip Duplicate Emails"),
				"fieldhelp" => _L('Indicates that duplicate Emails in the list should receive only one message.'),
				"value" => $USER->getSetting("skipemailduplicates", true),
				"validators" => array(),
				"control" => array("CheckBox"),
				"helpstep" => $helpstepnum++
			);
		}
		
		return new Form("scheduleAdvanced",$formdata,$helpsteps);
	}
	
	//returns true if this step is enabled
	function isEnabled($postdata, $step) {
		$wizHasPhoneMsg = wizHasPhone($postdata);
		$wizHasEmailMsg= wizHasEmail($postdata);
		if (isset($postdata['/schedule/options']['advanced']) && $postdata['/schedule/options']['advanced'] && ($wizHasEmailMsg || $wizHasPhoneMsg))
			return true;
		return false;
	}
}

class JobWiz_submitConfirm extends WizStep {
	function getForm($postdata, $curstep) {
		$wizHasPhoneMsg = wizHasPhone($postdata);
		$wizHasEmailMsg= wizHasEmail($postdata);
		$wizHasSmsMsg= wizHasSms($postdata);

		// if something is missing from post data send to unauthorized... NOTE THE NOT SYMBOL !!!
		if (!(($wizHasPhoneMsg ||$wizHasEmailMsg || $wizHasSmsMsg) &&
			isset($postdata["/schedule/options"]["schedule"]) &&
			($postdata["/schedule/options"]["schedule"] == "now"  ||
				$postdata["/schedule/options"]["schedule"] == "template" ||
				($postdata["/schedule/options"]["schedule"] == "schedule" && isset($postdata["/schedule/date"]["date"]))
			))
		)
			redirect('unauthorized.php');
		
		$lists = json_decode($postdata["/list"]["listids"]);
		$calctotal = 0;
		foreach ($lists as $id) {
			$list = new PeopleList($id+0);
			$renderedlist = new RenderedList($list);
			$renderedlist->calcStats();
			$calctotal = $calctotal + $renderedlist->total;
		}
		
		$html = '<div style="font-size: medium">';
		if ($postdata['/schedule/options']['schedule'] == 'template')
			$html .= escapehtml(_L('You are about to save a notification to be used at a later date.')). "<br>". escapehtml(_L('Confirm and click Next to save this notification.'));
		else {
			if ($calctotal == 1)
				$html = escapehtml(_L('Confirm and click Next to send this notification to the 1 person you selected'));
			else
				$html = escapehtml(_L('Confirm and click Next to send this notification to the %1$s people you selected.', $calctotal, count($lists)));
		}
		$html .= '</div>';
		$formdata = array($this->title);
		$formdata["jobinfo"] = array(
			"label" => _L("Job Info"),
			"control" => array("FormHtml", "html" => $html),
			"helpstep" => 1
		);
		$formdata["jobconfirm"] = array(
			"label" => _L("Confirm"),
			"value" => "",
			"validators" => array(
				array("ValRequired")
			),
			"control" => array("CheckBox"),
			"helpstep" => 1
		);

		return new Form("confirm",$formdata,array());
	}
}
?>
