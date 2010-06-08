<?
////////////////////////////////////////////////////////////////////////////////
// global wizard functions
////////////////////////////////////////////////////////////////////////////////

// Check the whole of the wizard post data and include user authorization to figure out if this wizard has an assigned message group
function wizHasMessageGroup($postdata) {
	global $USER;

	// user has to be able to send some kind of message
	if (!$USER->authorize("sendphone") && !$USER->authorize("sendemail") && !($USER->authorize("sendsms") && getSystemSetting("_hassms")))
		return false;

	$package = isset($postdata["/start"]["package"])?$postdata["/start"]["package"]:false;
	$messageoptions = isset($postdata["/message/options"]["options"])?$postdata["/message/options"]["options"]:false;

	// if it's custom and pick message and there is a message group selected
	if($package == "custom" && $messageoptions == "pick" && isset($postdata["/message/pickmessage"]["messagegroup"]))
		return true;

	return false;
}

// Check the whole of the wizard post data and include user authorization to figure out if this wizard has an assigned phone message
function wizHasPhone($postdata) {
	global $USER;

	if (!$USER->authorize("sendphone"))
		return false;

	$package = isset($postdata["/start"]["package"])?$postdata["/start"]["package"]:false;
	$callme = isset($postdata["/message/phone/callme"]["message"])?json_decode($postdata["/message/phone/callme"]["message"]):false;

	// if it's an easycall and message has been recorded
	if ($package == 'easycall' && $callme)
		return true;

	$phonetext = isset($postdata["/message/phone/text"]["message"])?$postdata["/message/phone/text"]["message"]:false;

	// if it's express and message text entered
	if ($package == 'express' && $phonetext)
		return true;

	// if it's personalized and message recorded
	if ($package == 'personalized' && $callme)
		return true;

	$messageoptions = isset($postdata["/message/options"]["options"])?$postdata["/message/options"]["options"]:false;
	$messagepick = isset($postdata["/message/pick"]["type"])?$postdata["/message/pick"]["type"]:array();
	$messageselectphone = isset($postdata["/message/select"]["phone"])?$postdata["/message/select"]["phone"]:false;

	// if custom and create message and phone selected and record requested and message recorded
	if ($package == 'custom' && $messageoptions == 'create' && in_array('phone', $messagepick) && $messageselectphone == 'record' && $callme)
		return true;

	// if custom and create message and phone selected and text requested and message text entered
	if ($package == 'custom' && $messageoptions == 'create' && in_array('phone', $messagepick) && $messageselectphone == 'text' && $phonetext)
		return true;

	$messagegroupid = isset($postdata["/message/pickmessage"]["messagegroup"])?$postdata["/message/pickmessage"]["messagegroup"]:false;
	$messagegroup = DBFind("MessageGroup", "from messagegroup where id = ?", false, array($messagegroupid));

	// if custom and select saved and selected message group has a phone message
	if ($package == 'custom' && $messageoptions == 'pick' && $messagegroup && $messagegroup->hasMessage('phone'))
		return true;

	return false;
}

// Check the whole of the wizard post data and include user authorization to figure out if this wizard has an assigned email message
function wizHasEmail($postdata) {
	global $USER;

	if (!$USER->authorize("sendemail"))
		return false;

	$package = isset($postdata["/start"]["package"])?$postdata["/start"]["package"]:false;

	// easycall never attaches an email message
	if ($package == 'easycall')
		return false;

	$emailtext = isset($postdata["/message/email/text"]["message"])?$postdata["/message/email/text"]["message"]:false;

	// express and email text entered
	if ($package == 'express' && $emailtext)
		return true;

	// personalized and email text entered
	if ($package == 'personalized' && $emailtext)
		return true;

	$messageoptions = isset($postdata["/message/options"]["options"])?$postdata["/message/options"]["options"]:false;
	$messagepick = isset($postdata["/message/pick"]["type"])?$postdata["/message/pick"]["type"]:array();

	// if custom and create message and email selected and email text entered
	if ($package == 'custom' && $messageoptions == 'create' && in_array('email', $messagepick) && $emailtext)
		return true;

	$messagegroupid = isset($postdata["/message/pickmessage"]["messagegroup"])?$postdata["/message/pickmessage"]["messagegroup"]:false;
	$messagegroup = DBFind("MessageGroup", "from messagegroup where id = ?", false, array($messagegroupid));

	// if custom and select saved and selected message group has an email message
	if ($package == 'custom' && $messageoptions == 'pick' && $messagegroup && $messagegroup->hasMessage('email'))
		return true;

	return false;
}

// Check the whole of the wizard post data and include user authorization to figure out if this wizard has an assigned sms message
function wizHasSms($postdata) {
	global $USER;

	if (!$USER->authorize("sendsms") || !getSystemSetting("_hassms"))
		return false;

	$package = isset($postdata["/start"]["package"])?$postdata["/start"]["package"]:false;

	// easycall never attaches an sms message
	if ($package == 'easycall')
		return false;

	$smstext = isset($postdata["/message/sms/text"]["message"])?$postdata["/message/sms/text"]["message"]:false;

	// express and sms text entered
	if ($package == 'express' && $smstext)
		return true;

	// personalized and sms text entered
	if ($package == 'personalized' && $smstext)
		return true;

	$messageoptions = isset($postdata["/message/options"]["options"])?$postdata["/message/options"]["options"]:false;
	$messagepick = isset($postdata["/message/pick"]["type"])?$postdata["/message/pick"]["type"]:array();

	// if custom and create message and sms selected and sms text entered
	if ($package == 'custom' && $messageoptions == 'create' && in_array('sms', $messagepick) && $smstext)
		return true;

	$messagegroupid = isset($postdata["/message/pickmessage"]["messagegroup"])?$postdata["/message/pickmessage"]["messagegroup"]:false;
	$messagegroup = DBFind("MessageGroup", "from messagegroup where id = ?", false, array($messagegroupid));

	// if custom and select saved and selected message group has an sms message
	if ($package == 'custom' && $messageoptions == 'pick' && $messagegroup && $messagegroup->hasMessage('sms'))
		return true;

	return false;
}

// checks postdata to see if any auto translations are requested
function wizHasTranslation($postdata) {
	if (isset($postdata["/start"]["package"])) {
		$package = $postdata["/start"]["package"];
		if(isset($postdata['/message/email/text']['translate']) && $postdata['/message/email/text']['translate']) {
			if($package == 'express' || $package == 'personalized') {
				return true;
			}
			if($package == 'custom' &&
			   isset($postdata["/message/options"]["options"])  &&
			   $postdata["/message/options"]["options"] == "create" && // Need to take create Path
			   isset($postdata['/message/pick']['type']) &&
			   in_array('email', $postdata['/message/pick']['type'])
			   ) {
				return true;
			}
		}
		if(isset($postdata['/message/phone/text']['translate']) && $postdata['/message/phone/text']['translate']) {
			if($package == 'express' || $package == 'personalized') {
				return true;
			}
			if($package == 'custom' &&
			   isset($postdata["/message/options"]["options"])  &&
			   $postdata["/message/options"]["options"] == "create" && // Need to take create Path
			   isset($postdata['/message/pick']['type']) &&
			   in_array('phone', $postdata['/message/pick']['type'])
			   ) {
				return true;
			}
		}
		if($package == "custom" && isset($postdata['/message/options']["options"]) &&
			$postdata["/message/options"]["options"] == "pick" &&
			isset($postdata['/message/pickmessage']["messagegroup"]) &&
				QuickQuery("select 1 from message where messagegroupid = ? and autotranslate = 'translated' limit 1", false, array($postdata['/message/pickmessage']["messagegroup"]))) {
				return true;
		}
	}
	return false;
}

// get the user requested schedule out of postdata
function getSchedule($postdata) {
	global $ACCESS;
	global $USER;
	$schedule = array();

	$scheduleoptions = isset($postdata["/schedule/options"]["schedule"])?$postdata["/schedule/options"]["schedule"]:false;
	switch ($scheduleoptions) {
		case "now":
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

			$schedule = array(
				"maxjobdays" => isset($postdata["/schedule/advanced"]["maxjobdays"])?$postdata["/schedule/advanced"]["maxjobdays"]:1,
				"date" => date('m/d/Y'),
				"callearly" => $callearly,
				"calllate" => $calllate
			);
			break;
		case "schedule":
			$schedule = array(
				"maxjobdays" => isset($postdata["/schedule/advanced"]["maxjobdays"])?$postdata["/schedule/advanced"]["maxjobdays"]:1,
				"date" => date('m/d/Y', strtotime($postdata["/schedule/date"]["date"])),
				"callearly" => $postdata["/schedule/date"]["callearly"],
				"calllate" => $postdata["/schedule/date"]["calllate"]
			);
			break;
		case "template":
			$schedule = array(
				"maxjobdays" => isset($postdata["/schedule/advanced"]["maxjobdays"])?$postdata["/schedule/advanced"]["maxjobdays"]:1,
				"date" => false,
				"callearly" => false,
				"calllate" => false
			);
			break;
		default:
			break;
	}
	return $schedule;
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
					var gender = ($("'.$n.'-female").checked?"female":"male");
					if (val) {
						popup(\'previewmessage.php?parentfield='.$n.'-textarea&language='.urlencode($this->args['language']).'&gender=\'+ gender, 400, 400,\'preview\');
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

class HtmlTextArea extends FormItem {
	function render ($value) {
		$n = $this->form->name."_".$this->name;
		if (!$value)
			$value = '';
		$rows = isset($this->args['rows']) ? 'rows="'.$this->args['rows'].'"' : "";
		$str = '<textarea id="'.$n.'" name="'.$n.'" '.$rows.'/>'.escapehtml($value).'</textarea>
			<div id ="'.$n.'htmleditor"></div>
			<script type="text/javascript" src="script/ckeditor/ckeditor_basic.js"></script>
			<script type="text/javascript" src="script/htmleditor.js"></script>
			<script type="text/javascript">
				document.observe("dom:loaded",
					function() {
						// add the ckeditor to the textarea
						applyHtmlEditor($("'.$n.'"),true,"'.$n.'htmleditor");

						// set up a keytimer to save content and validate
						var htmlTextArea_keytimer = null;
						registerHtmlEditorKeyListener(function (event) {
							window.clearTimeout(htmlTextArea_keytimer);
							var htmleditor = getHtmlEditorObject();
							htmlTextArea_keytimer = window.setTimeout(function() {
								saveHtmlEditorContent(htmleditor);
								form_do_validation(htmleditor.currenttextarea.up("form"), htmleditor.currenttextarea);
							}, 500);
						});
					});
			</script>
		';
		return $str;
	}
}

class CheckBoxWithHtmlPreview extends FormItem {
	function render ($value) {
		$n = $this->form->name."_".$this->name;
		$str = '<input id="'.$n.'" name="'.$n.'" type="checkbox" value="true" '. ($value ? 'checked' : '').'
				onclick="showhidepreview(\''. $n .'\')"/>
			<div id="'.$n.'-checked" name="'.$n.'" style="border: 1px solid gray; overflow: auto; padding: 4px; max-height: 150px; display: '. ($value ? 'block' : 'none') .'">
				'. $this->args['checkedhtml'] .'
			</div>
			<div id="'.$n.'-unchecked" name="'.$n.'" style="border: 1px solid gray; overflow: auto; padding: 4px; max-height: 150px; color: gray; display: '. ($value ? 'none' : 'block') .'">
				'. $this->args['uncheckedhtml'] .'
			</div>
			<script type="text/javascript">
				function showhidepreview(e) {
					e = $(e);
					if (e.checked) {
						$(e.id + "-checked").show();
						$(e.id + "-unchecked").hide();
					} else {
						$(e.id + "-checked").hide();
						$(e.id + "-unchecked").show();
					}
				}
			</script>
		';
		return $str;
	}
}

class EasyCall extends FormItem {
	function render ($value) {
		$n = $this->form->name."_".$this->name;
		if (isset($this->args['languages']) && $this->args['languages'])
			$languages = $this->args['languages'];
		else
			$languages = array();

		$defaultphone = escapehtml(Phone::format($this->args['phone']));
		if (!$value)
			$value = '{}';
		// Hidden input item to store values in
		$str = '<input id="'.$n.'" name="'.$n.'" type="hidden" value="'.escapehtml($value).'" />';

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

		.wizeasycallcontainer {
			padding: 0px;
			margin: 0px;
			white-space:nowrap;
		}
		.wizeasycallaction {
			width: 80%;
			float: right;
			margin-bottom: 5px;
		}
		.wizeasycalllanguage {
			font-size: large;
			float: left;
		}
		.wizeasycallbutton {
			float: left;
		}
		.wizeasycallmaincontainer {
			padding-bottom: 6px;
		}
		.wizeasycallcontent {
			padding-bottom: 6px;
			padding-left: 6px;
			padding-top: 0px;
			margin: 0px;
			white-space:nowrap;
		}
		.wizeasycallaltlangs {
			clear: both;
			padding: 5px;
		}
		</style>';

		$str .='
		<div class="wizeasycallmaincontainer">
			<div id="'.$n.'_content" class="wizeasycallcontent"></div>
			<div id="'.$n.'_altlangs" class="wizeasycallaltlangs" style="display: none">';
		if (count($languages)) {
			$str .= '
				<div style="margin-bottom: 3px;">'._L("Add an alternate language?").'</div>
				<select id="'.$n.'_select" ><option value="0">-- '._L("Select One").' --</option>';
			foreach ($languages as $langcode => $langname)
				$str .= '<option id="'.$n.'_select_'.$langcode.'" value="'.$langcode.'" >'.escapehtml($langname).'</option>';
			$str .= '</select>';
		}
		$str .= '
			</div>
		</div>
		';

		// include the easycall javascript object, extend it's functionality, then load existing values.
		$str .= '<script type="text/javascript" src="script/easycall.js.php"></script>
			<script type="text/javascript" src="script/wizeasycall.js.php"></script>
			<script type="text/javascript">
				// get the current audiofiles from the form data
				var audiofiles = '.$value.';

				// if en (Default) is not set, set it so it must be recorded
				if (typeof(audiofiles["en"]) == "undefined")
					audiofiles["en"] = null;

				// store the language code to name map in a json object, we need this in WizEasyCall
				languages = '.json_encode($languages).';
				languages["en"] = "Default";

				// save default phone into msgphone, this variable tracks changes the user makes to desired call me number
				msgphone = "'.$defaultphone.'";

				// load up all the audiofiles from form data
				Object.keys(audiofiles).each(function(langcode) {

					// create a new wizard easycall
					insertNewWizEasyCall( "'.$n.'", "'.$n.'_content", "'.$n.'_select", langcode );
				});
				
				// listen for selections from the _select element
				if ($("'.$n.'_select")) {
					$("'.$n.'_select").observe("change", function (event) {
						e = event.element();
						if (e.value == 0)
							return;

						var langcode = $("'.$n.'_select").value;

						// create a new wizard easycall
						insertNewWizEasyCall( "'.$n.'", "'.$n.'_content", "'.$n.'_select", langcode );

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
			// find if there are any message groups the user owns or subscribes to
			$hasowned = QuickQuery("
				select 1
				from messagegroup mg
				where not mg.deleted and mg.userid = ?
				limit 1", false, array($USER->id));
			$hassubscribed = QuickQuery("
				select 1
				from publish p
				where p.userid = ? and action = 'subscribe' and type = 'messagegroup'
				limit 1", false, array($USER->id));
			if (!$hasowned && !$hassubscribed)
				return "$this->label: ". _L('You have no saved or subscribed messages.');
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
		if ($values == json_decode("{}"))
			return "$this->label "._L("has messages that are not recorded");
		foreach ($values as $langcode => $afid) {
			$audiofile = DBFind("AudioFile", "from audiofile where id = ? and userid = ?", false, array($afid, $USER->id));
			if (!$audiofile)
				return "$this->label "._L("has invalid or missing messages");
		}
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
				0 => _L('Use Your Saved Messages'),
				1 => _L('Customize Message Combination Options'),
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
		$canstarteasy = $USER->authorize("starteasy");

		// All delivery types allowed
		if ($deliverytypes['sendphone'] && $deliverytypes['sendemail'] && $deliverytypes['sendsms']) {
			$packageDetails["easycall"]["enabled"] = $canstarteasy;
			$packageDetails["express"]["enabled"] = true;
			$packageDetails["personalized"]["enabled"] = $canstarteasy;
		// Only phone
		} elseif ($deliverytypes['sendphone'] && !$deliverytypes['sendemail'] && !$deliverytypes['sendsms']) {
			$packageDetails["easycall"][2] = "";
			$packageDetails["easycall"]["enabled"] = $canstarteasy;
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
			$packageDetails["easycall"]["enabled"] = $canstarteasy;
			$packageDetails["express"]["enabled"] = true;
			$packageDetails["personalized"][1] = _L('Type Email Message');
			$packageDetails["personalized"]["enabled"] = $canstarteasy;
		// Phone and SMS
		} elseif ($deliverytypes['sendphone'] && !$deliverytypes['sendemail'] && $deliverytypes['sendsms']) {
			$packageDetails["easycall"][2] = _L('Auto SMS Text Alerts');
			$packageDetails["easycall"]["enabled"] = $canstarteasy;
			$packageDetails["express"][2] = "";
			$packageDetails["express"]["enabled"] = true;
			$packageDetails["personalized"][1] = _L('Type SMS Text');
			$packageDetails["personalized"][2] = "";
			$packageDetails["personalized"]["enabled"] = $canstarteasy;
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
			"control" => array("TextField","maxlength" => 30, "size" => 30),
			"helpstep" => 1
		);
		$formdata["jobtype"] = array(
			"label" => _L("Type/Category"),
			"fieldhelp" => _L("Select the option that best describes the type of notification you are sending."),
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
			"fieldhelp" => _L("Choose a notification method. <br><br><ul>
			<li><b>Record</b> - Record a phone message in your voice. In addition to the phone call the system will automatically send email and SMS text message alerts.
			<li><b>Write</b> - Type your phone, email and SMS text messages. The phone message text will be automatically converted to a call using text-to-speech. Auto-translation is optional.
			<li><b>Record and Write</b> - Record a phone message in your voice. Type your email and SMS text messages.
			<li><b>Customize</b> - Use the Customize option to choose a message that you've already saved or to manually select any combination of message options you require.
			</ul>
			<i><b>Note:</b> Email and SMS text messaging are optional features and may not be enabled.</i>"),
			"validators" => array(
				array("ValRequired"),
				array("ValInArray", "values" => array('easycall', 'express', 'personalized', 'custom'))
			),
			"value" => "",
			"control" => array("HtmlRadioButtonBigCheck", "values" => $packages),
			"helpstep" => 3
		);
		$helpsteps = array (
			_L("Job names are used for email subjects and reporting, so they should be descriptive.<br><br><b>Note:</b> Before you send your first job, you should make a test list by selecting New List in the Shortcuts menu."),
			_L("Job Types are used to determine which phones or emails will be contacted. Choosing the correct job type is important for effective communication.<br><br><b>Note:</b> Emergency jobs include a notification that the message is regarding an emergency."),
			_L("Choose a notification method. The first three options are preconfigured to ask you to fill out specific steps. <br><br><ul>
			<li><b>Record</b> - Record a phone message in your voice. In addition to the phone call the system will automatically send email and SMS text message alerts to those recipients with the appropriate email and SMS contact information and preference settings.
			<li><b>Write</b> - Type your phone, email, and SMS text messages. The phone message text will be automatically converted to a call using text-to-speech. Both the phone and email messages can also be automatically translated into the other languages defined in your account.
			<li><b>Record and Write</b> - Record a phone message in your voice. Type your email and SMS text messages.
			<li><b>Customize</b> - Use the Customize option to choose a previously saved message or to manually select any combination of message options you require. 			</ul>
			<b>Note:</b> Email and SMS text messaging are optional features and may not be enabled for some user accounts.")
		);
		return new Form("start",$formdata,$helpsteps);
	}
}

class JobWiz_listChoose extends WizStep {
	function getForm($postdata, $curstep) {
		return new ListForm("listChoose");
	}
}



//get here at the Message step after clicking Custom. Let's you choose to create a message or pick a message
class JobWiz_messageOptions extends WizStep {
	function getForm($postdata, $curstep) {
		// Form Fields.
		global $USER;

		$values = array();
		$values["create"] =_L("Create A Message");
		$values["pick"] =_L("Select Saved Message");

		$formdata[] = $this->title;
		$helpsteps = array(_L("Select to either create a new message or choose an existing message you've already saved or subscribed to."));
		$formdata["options"] = array(
			"label" => _L("Message Options"),
			"fieldhelp" => _L("Choose whether you would like to create a new message or use one you have already saved or subscribed to."),
			"value" => "",
			"validators" => array(
				array("ValRequired")
			),
			"control" => array("RadioButton","values"=>$values),
			"helpstep" => 1
		);

		return new Form("messageCreateOrPick",$formdata,$helpsteps);
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
class JobWiz_messageGroupChoose extends WizStep {
	function getForm($postdata, $curstep) {
		global $USER;
		// get this user's owned and subscribed messages
		$messages = QuickQueryList(
			"(select mg.id,
				mg.name as name,
				(mg.name +0) as digitsfirst
			from messagegroup mg
			where mg.userid=?
				and mg.type = 'notification'
				and not mg.deleted)
			UNION
			(select mg.id,
				mg.name as name,
				(mg.name +0) as digitsfirst
			from publish p
			inner join messagegroup mg on
				(p.messagegroupid = mg.id)
			where p.userid=?
				and p.action = 'subscribe'
				and p.type = 'messagegroup'
				and not mg.deleted)
			order by digitsfirst, name",
			true,false,array($USER->id, $USER->id));
		$messages = ($messages === false)?array("" =>_L("-- Select a Message --")):(array("" =>_L("-- Select a Message --")) + $messages);

		$formdata = array();

		$formdata[] = $this->title;
		$helpsteps = array(_L("Select from list of existing messages. After selecting a message, the message components will display below. Clicking the icon for any component will open a preview window."));
		$formdata["messagegroup"] = array(
			"label" => _L("Select a Message"),
			"fieldhelp" => _L("Choose a saved message from the menu."),
			"value" => "",
			"validators" => array(
				array("ValRequired"),
				array("ValInArray","values"=>array_keys($messages)),
				array("ValNonEmptyMessage")
			),
			"control" => array("MessageGroupSelectMenu","width"=>"80%", "values"=>$messages),
			"helpstep" => 1
		);
		return new Form("messageGroupChoose",$formdata,$helpsteps);
	}

	//returns true if this step is enabled
	function isEnabled($postdata, $step) {
		global $USER;

		if (!$USER->authorize("sendphone") && !$USER->authorize("sendemail") && !($USER->authorize("sendsms") && getSystemSetting("_hassms")))
			return false;

		$package = isset($postdata["/start"]["package"])?$postdata["/start"]["package"]:false;
		$messageoptions = isset($postdata["/message/options"]["options"])?$postdata["/message/options"]["options"]:false;

		// if custom and pick message selected
		if ($package == 'custom' && $messageoptions == "pick")
			return true;

		return false;
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
		$helpsteps = array(_L("Choose how you you like your message to be delivered."));
		$formdata["type"] = array(
			"label" => _L("Message Type"),
			"fieldhelp" => _L("Choose the message options you would like to configure."),
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
		if (isset($postdata['/start']['package']) && $postdata['/start']['package'] == "custom"
			&& isset($postdata['/message/options']['options']) && $postdata['/message/options']['options'] == "create") {
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
		//$values["pick"] =_L("Select Saved Message");

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

		return new Form("messageSelect",$formdata,$helpsteps);
	}

	//returns true if this step is enabled
	function isEnabled($postdata, $step) {
		if (isset($postdata['/start']['package']) && $postdata['/start']['package'] == "custom" &&
			isset($postdata['/message/options']['options']) && $postdata['/message/options']['options'] == "create" &&
			isset($postdata['/message/pick']['type']) && in_array('phone',$postdata['/message/pick']['type'])) {
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
				"control" => array("TextAreaPhone","width"=>"80%","rows"=>10,"language"=>"en","voice"=>"female"),
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

		$package = false;
		if (isset($postdata['/start']['package']))
			$package = $postdata['/start']['package'];

		// if its express, you have to enter phone text
		if ($package == "express")
			return true;

		$messageoption = false;
		if (isset($postdata['/message/options']["options"]))
			$messageoption = $postdata['/message/options']["options"];

		$messagepick = array();
		if (isset($postdata['/message/pick']['type']))
			$messagepick = $postdata['/message/pick']['type'];

		$messageselectphone = false;
		if (isset($postdata["/message/select"]["phone"]))
			$messageselectphone = $postdata["/message/select"]["phone"];

		// if it's custom and type create and phone is selected and you chose text, you must enter phone text
		if ($package == 'custom' && $messageoption == 'create' && in_array('phone',$messagepick) && $messageselectphone == 'text')
			return true;

		return false;
	}
}

//Displays the different message translations.
class JobWiz_messagePhoneTranslate extends WizStep {
	function getTranslationDataArray($label, $languagecode, $text, $gender = "female", $transient = true, $englishText = false) {
		return array(
			"label" => ucfirst($label),
			"value" => json_encode(array(
				"enabled" => true,
				"text" => $text,
				"override" => false,
				"gender" => $gender,
				"englishText" => $englishText
			)),
			"validators" => array(array("ValTranslation")),
			"control" => array("TranslationItem",
				"phone" => true,
				"language" => $languagecode
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
		$translationlanguages = Voice::getTTSLanguageMap();
		unset($translationlanguages['en']);
		$translationlanguagecodes = array_keys($translationlanguages);
		$translations = translate_fromenglish($msgdata->text,$translationlanguagecodes);
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
			if(is_array($translations)){
				foreach($translations as $obj){
					$languagecode = array_shift($translationlanguagecodes);

					if(!isset($voices[$languagecode.":".$msgdata->gender]))
						$gender = ($msgdata->gender == "male")?"female":"male";
					else
						$gender = $msgdata->gender;
					$transient = $this->isTransient($postdata, $languagecode);

					$formdata[$languagecode] = $this->getTranslationDataArray($translationlanguages[$languagecode], $languagecode, $obj->responseData->translatedText, $gender, $transient, ($transient?"":$msgdata->text));
				}
			} else {
				$languagecode = reset($translationlanguagecodes);
				$transient = $this->isTransient($postdata, $languagecode);
				$formdata[$languagecode] = $this->getTranslationDataArray($translationlanguages[$languagecode], $languagecode, $translations->translatedText, $msgdata->gender, $transient, ($transient?"":$msgdata->text));
			}
		}
		if(!isset($formdata["Translationinfo"])) {
			$formdata["Translationinfo"] = array(
				"label" => " ",
				"control" => array("FormHtml","html"=>'
					<div id="branding">
						<div style="color: rgb(103, 103, 103);float: right;" class="gBranding"><span style="vertical-align: middle; font-family: arial,sans-serif; font-size: 11px;" class="gBrandingText">'._L('Translation powered by').'<img style="padding-left: 1px; vertical-align: middle;" alt="Google" src="' . (isset($_SERVER['HTTPS'])?"https":"http") . '://www.google.com/uds/css/small-logo.png"></span></div>
					</div>
				'),
				"helpstep" => 2
			);
		}

		$helpsteps = array(
			_L("This is the message that all contacts will receive if they do not have any other language message specified"),
			_L("This is an automated translation powered by Google Translate. Please note that although machine translation is always improving, it is not perfect yet. You can try reverse translation for an idea of how well your message was translated.")
		);


		return new Form("messagePhoneTranslate",$formdata,$helpsteps);
	}

	//returns true if this step is enabled
	function isEnabled($postdata, $step) {
		global $USER;
		if (!$USER->authorize("sendphone") || !$USER->authorize("sendmulti"))
			return false;

		$package = false;
		if (isset($postdata['/start']['package']))
			$package = $postdata['/start']['package'];

		$translate = (isset($postdata["/message/phone/text"]["translate"])?$postdata["/message/phone/text"]["translate"]:false);

		// if its express and phone translation requested
		if ($package == "express" && $translate)
			return true;

		$messageoption = false;
		if (isset($postdata['/message/options']["options"]))
			$messageoption = $postdata['/message/options']["options"];

		$messagepick = array();
		if (isset($postdata['/message/pick']['type']))
			$messagepick = $postdata['/message/pick']['type'];

		$messageselectphone = false;
		if (isset($postdata["/message/select"]["phone"]))
			$messageselectphone = $postdata["/message/select"]["phone"];

		// if it's custom and type create and phone is selected and you chose text and translation requested
		if ($package == 'custom' && $messageoption == 'create' && in_array('phone',$messagepick) && $messageselectphone == 'text' && $translate)
			return true;

		return false;
	}
}

//Call me to Record
class JobWiz_messagePhoneEasyCall extends WizStep {
	function getForm($postdata, $curstep) {
		// Form Fields.
		global $USER;
		$langs = array();
		if ($USER->authorize("sendmulti")) {
			$syslangs = Language::getLanguageMap();
			foreach ($syslangs as $langid => $langname)
				if (strtolower($langname) !== "english")
					$langs[$langid] = $langname;
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
				"EasyCall",
				"phone"=>$USER->phone,
				"languages"=>$langs
			),
			"helpstep" => 1
		);
		$helpsteps[] = _L("The system will call you at the number you enter in this form and guide you through a series of prompts to record your message. The default message is always required and will be sent to any recipients who do not have a language specified.<br><br>
		Choose which language you will be recording in and enter the phone number where the system can reach you. Then click \"Call Me to Record\" to get started. Listen carefully to the prompts when you receive the call. You may record as many different langauges as you need.
		");

		return new Form("messagePhoneEasyCall",$formdata,$helpsteps);
	}

	//returns true if this step is enabled
	function isEnabled($postdata, $step) {
		global $USER;
		if (!$USER->authorize("sendphone"))
			return false;

		$package = false;
		if (isset($postdata['/start']['package']))
			$package = $postdata['/start']['package'];

		// if its easycall or personalized, you have to record
		if ($package == "easycall" || $package == "personalized")
			return true;

		$messageoption = false;
		if (isset($postdata['/message/options']["options"]))
			$messageoption = $postdata['/message/options']["options"];

		$messagepick = array();
		if (isset($postdata['/message/pick']['type']))
			$messagepick = $postdata['/message/pick']['type'];

		$messageselectphone = false;
		if (isset($postdata["/message/select"]["phone"]))
			$messageselectphone = $postdata["/message/select"]["phone"];

		// if it's custom and type create and phone is selected and you chose record, you must record
		if ($package == 'custom' && $messageoption == 'create' && in_array('phone',$messagepick) && $messageselectphone == 'record')
			return true;

		return false;
	}
}


class JobWiz_messageEmailText extends WizStep {
	function getForm($postdata, $curstep) {
		global $USER;
		$msgdata = isset($postdata['/message/phone/text']['message'])?json_decode($postdata['/message/phone/text']['message']):json_decode('{"text": ""}');
		// Form Fields.
		$formdata = array($this->title);

		$helpsteps = array(_L("Enter the name for the email acount."));
		$formdata["fromname"] = array(
			"label" => _L('From Name'),
			"fieldhelp" => _L('Recipients will see this name as the sender of the email.'),
			"value" => $USER->firstname . " " . $USER->lastname,
			"validators" => array(
					array("ValRequired"),
					array("ValLength","max" => 50)
					),
			"control" => array("TextField","size" => 25, "maxlength" => 50),
			"helpstep" => 1
		);

		$helpsteps[] = array(_L("Enter the address where you would like to receive replies."));
		$formdata["from"] = array(
			"label" => _L("From Email"),
			"fieldhelp" => _L('This is the address the email is coming from. Recipients will also be able to reply to this address.'),
			"value" => $USER->email,
			"validators" => array(
				array("ValRequired"),
				array("ValLength","max" => 255),
				array("ValEmail", "domain" => getSystemSetting('emaildomain'))
				),
			"control" => array("TextField","max"=>255,"min"=>3,"size"=>35),
			"helpstep" => 2
		);

		$helpsteps[] = _L("Enter the subject of the email here.");
		$formdata["subject"] = array(
			"label" => _L("Subject"),
			"fieldhelp" => _L('The Subject will appear as the subject line of the email.'),
			"value" => $postdata['/start']['name'],
			"validators" => array(
				array("ValRequired"),
				array("ValLength","max" => 255)
			),
			"control" => array("TextField","max"=>255,"min"=>3,"size"=>45),
			"helpstep" => 3
		);

		$helpsteps[] = _L("You may attach up to three files that are up to 2MB each. For greater security, only certain types of files are accepted.<br><br><b>Note:</b> Some email accounts may not accept attachments above a certain size and may reject your message.");
		$formdata["attachments"] = array(
			"label" => _L('Attachments'),
			"fieldhelp" => _L("You may attach up to three files that are up to 2MB each. For greater security, certain file types are not permitted. Be aware that some email accounts may not accept attachments above a certain size and may reject your message."),
			"value" => "",
			"validators" => array(array("ValEmailAttach")),
			"control" => array("EmailAttach"),
			"helpstep" => 4
		);

		$helpsteps[] = _L("Email message body text goes here. Be sure to introduce yourself and give detailed information. For helpful message tips and ideas, click the Help link in the upper right corner of the screen.");
		$formdata["message"] = array(
			"label" => _L("Email Message"),
			"fieldhelp" => _L('Enter the message you would like to send. Helpful tips for successful messages can be found at the Help link in the upper right corner.'),
			"value" => $msgdata->text,
			"validators" => array(
				array("ValRequired"),
				array("ValMessageBody")
			),
			"control" => array("HtmlTextArea","rows"=>10),
			"helpstep" => 5
		);

		if ($USER->authorize('sendmulti')) {
			$helpsteps[] = _L("Automatically translate into alternate languages. Please note that automatic translation is always improving, but is not perfect yet. Try reverse translating your message for a preview of how well it translated.");
			$formdata["translate"] = array(
				"label" => _L("Translate"),
				"fieldhelp" => _L('Check this box to automatically translate your message using Google Translate.'),
				"value" => ($postdata['/start']['package'] == "express")?true:false,
				"validators" => array(),
				"control" => array("CheckBox"),
				"helpstep" => 6
			);
		}

		return new Form("messageEmailText",$formdata,$helpsteps);
	}

	//returns true if this step is enabled
	function isEnabled($postdata, $step) {
		global $USER;
		if (!$USER->authorize("sendemail"))
			return false;

		$package = false;
		if (isset($postdata['/start']['package']))
			$package = $postdata['/start']['package'];

		// if its express or personalized, you have to enter email text
		if ($package == "express" || $package == "personalized")
			return true;

		$messageoption = false;
		if (isset($postdata['/message/options']["options"]))
			$messageoption = $postdata['/message/options']["options"];

		$messagepick = array();
		if (isset($postdata['/message/pick']['type']))
			$messagepick = $postdata['/message/pick']['type'];

		// if it's custom and type create and email is selected, you must enter email text
		if ($package == 'custom' && $messageoption == 'create' && in_array('email',$messagepick))
			return true;

		return false;
	}
}

class JobWiz_messageEmailTranslate extends WizStep {

	function getForm($postdata, $curstep) {
		global $TRANSLATIONLANGUAGECODES;

		static $translations = false;
		static $translationlanguages = false;

		$englishtext = isset($postdata['/message/email/text']['message'])?$postdata['/message/email/text']['message']:"";

		$warning = "";
		if(mb_strlen($englishtext) > 4000) {
			$warning = _L('Warning. Only the first 4000 characters are translated.');
		}

		if(!$translations) {
			//Get available languages
			$alllanguages = QuickQueryList("select code, name from language", true);
			$translationlanguages = array_intersect_key($alllanguages, array_flip($TRANSLATIONLANGUAGECODES));
			unset($translationlanguages['en']);
			$translationlanguagecodes = array_keys($translationlanguages);
			$translations = translate_fromenglish($englishtext,$translationlanguagecodes);
		} else {
			$translationlanguagecodes = array_keys($translationlanguages);
		}

		// Form Fields.

		if ($warning)
			$formdata["warning"] = array(
				"label" => _L("Warning"),
				"control" => array("FormHtml","html"=>'<div style="font-size: medium; color: red">'.escapehtml($warning).'</div><br>'),
				"helpstep" => 1
			);

		//$translations = false; // Debug output when no translation is available
		if(!$translations) {
			$formdata["Translationinfo"] = array(
				"label" => _L("Info"),
				"control" => array("FormHtml","html"=>'<div style="font-size: medium;">'._L('No Translations Available').'</div><br>'),
				"helpstep" => 1
			);
		} else {
			$formdata[] = Language::getName('en');
			
			$formdata["englishversion"] = array(
				"label" => _L("Default"),
				"control" => array("FormHtml","html" => '<div style="border: 1px solid gray; overflow: auto; padding: 4px; max-height: 150px;">'. $englishtext .'</div>'),
				"helpstep" => 1
			);
			
			if(is_array($translations)) {
				foreach($translations as $obj){
					$languagecode = array_shift($translationlanguagecodes);
					$formdata[] = Language::getName($languagecode);
					$formdata[$languagecode] = array(
						"label" => _L("Enabled"),
						"fieldhelp" => _L('Check this box to automatically translate your message using Google Translate.'),
						"value" => 1,
						"validators" => array(),
						"control" => array("CheckBoxWithHtmlPreview", "checkedhtml" => $obj->responseData->translatedText, "uncheckedhtml" => addslashes(_L("People tagged with this language will receive the English version."))),
						"helpstep" => 1
					);
				}
			} else {
				$languagecode = reset($translationlanguagecodes);
				$formdata[] = Language::getName($languagecode);
				$formdata[$languagecode] = array(
					"label" => _L("Enabled"),
					"fieldhelp" => _L('Check this box to automatically translate your message using Google Translate.'),
					"value" => 1,
					"validators" => array(),
					"control" => array("CheckBoxWithHtmlPreview", "checkedhtml" => $translations->translatedText, "uncheckedhtml" => addslashes(_L("People tagged with this language will receive the English version."))),
					"helpstep" => 1
				);
			}
		}

		if(!isset($formdata["Translationinfo"])) {
			$formdata["Translationinfo"] = array(
				"label" => " ",
				"control" => array("FormHtml","html"=>'
					<div id="branding">
						<div style="color: rgb(103, 103, 103);float: right;" class="gBranding"><span style="vertical-align: middle; font-family: arial,sans-serif; font-size: 11px;" class="gBrandingText">'._L('Translation powered by').'<img style="padding-left: 1px; vertical-align: middle;" alt="Google" src="' . (isset($_SERVER['HTTPS'])?"https":"http") . '://www.google.com/uds/css/small-logo.png"></span></div>
					</div>
				'),
				"helpstep" => 1
			);
		}

		$helpsteps = array(
			_L("This translation was automatically generated. Please note that automatic translation is always improving, but is not perfect yet.")
		);

		return new Form("messageEmailTranslate",$formdata,$helpsteps);
	}

	//returns true if this step is enabled
	function isEnabled($postdata, $step) {
		global $USER;
		if (!$USER->authorize("sendemail") || !$USER->authorize("sendmulti"))
			return false;

		$package = false;
		if (isset($postdata['/start']['package']))
			$package = $postdata['/start']['package'];

		$translate = (isset($postdata["/message/email/text"]["translate"])?$postdata["/message/email/text"]["translate"]:false);

		// if its express or personalized and email translation requested
		if (($package == "express" || $package == "personalized") && $translate)
			return true;

		$messageoption = false;
		if (isset($postdata['/message/options']["options"]))
			$messageoption = $postdata['/message/options']["options"];

		$messagepick = array();
		if (isset($postdata['/message/pick']['type']))
			$messagepick = $postdata['/message/pick']['type'];

		// if it's custom and type create and email is selected and translation requested
		if ($package == 'custom' && $messageoption == 'create' && in_array('email',$messagepick) && $translate)
			return true;

		return false;
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
				$text = html_to_plain($postdata['/message/email/text']['message']);
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
				array("ValRegExp","pattern" => getSmsRegExp())
			),
			"control" => array("TextArea","rows"=>5,"cols"=>35,"counter"=>160),
			"helpstep" => 1
		);

		return new Form("messageSmsText",$formdata,$helpsteps);
	}

	//returns true if this step is enabled
	function isEnabled($postdata, $step) {
		global $USER;
		
		// if the customer doesn't have sms
		if (!getSystemSetting('_hassms'))
			return false;
		
		if (!$USER->authorize("sendsms"))
			return false;

		$package = false;
		if (isset($postdata['/start']['package']))
			$package = $postdata['/start']['package'];

		// if its express or personalized, you have to enter sms text
		if ($package == "express" || $package == "personalized")
			return true;

		$messageoption = false;
		if (isset($postdata['/message/options']["options"]))
			$messageoption = $postdata['/message/options']["options"];

		$messagepick = array();
		if (isset($postdata['/message/pick']['type']))
			$messagepick = $postdata['/message/pick']['type'];

		// if it's custom and type create and sms is selected, you must enter sms text
		if ($package == 'custom' && $messageoption == 'create' && in_array('sms',$messagepick))
			return true;

		return false;
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
		$callearlysec = strtotime($callearly) ;
		$calllatesec = strtotime($calllate);
		$accessCallearlysec = strtotime($accessCallearly);
		$accessCalllatesec = strtotime($accessCalllate);

		// Check that the NOW call early time is less than the NOW call late time
		// and it's greater than or equal to the access profile call early time
		// and the call late time is less than or equal to the access call late time
		// and the proposed job window would be half an hour or more
		if (($callearlysec < $calllatesec) && ($callearlysec >= $accessCallearlysec) && ($calllatesec <= $accessCalllatesec) && (($calllatesec - $callearlysec) >= 1800))
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
				array("ValRequired"),
				array("ValInArray", "values" => array_keys($menu))
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

		// Make sure it's 30 minutes or more before the end of their allowed access call window
		$dayoffset = (strtotime("now") > (strtotime(($ACCESS->getValue("calllate")?$ACCESS->getValue("calllate"):"11:59 pm")) - 1800))?1:0;
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
			"requires" => array("callearly", "date"),
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

		$helpsteps = array();
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
						array("ValPhone")
					),
					"control" => array("TextField","maxlength" => 20, "size" => 15),
					"helpstep" => $helpstepnum++
				);
			}
			$helpsteps[] = _L("Specify the number of days for which you would like your job to run before it stops.");
			$formdata["maxjobdays"] = array(
				"label" => _L("Days to Run"),
				"fieldhelp" => ("Use this menu to set the default number of days your jobs should run."),
				"value" => $maxjobdays,
				"validators" => array(
					array("ValRequired"),
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
		$wizHasMessageGroup= wizHasMessageGroup($postdata);

		// if something is missing from post data send to unauthorized... NOTE THE NOT SYMBOL !!!
		if (!(($wizHasPhoneMsg ||$wizHasEmailMsg || $wizHasSmsMsg || $wizHasMessageGroup) &&
			isset($postdata["/schedule/options"]["schedule"]) &&
			($postdata["/schedule/options"]["schedule"] == "now"  ||
				$postdata["/schedule/options"]["schedule"] == "template" ||
				($postdata["/schedule/options"]["schedule"] == "schedule" && isset($postdata["/schedule/date"]["date"]))
			))
		)
			redirect('unauthorized.php');

		// Built/Existing Lists
		$lists = json_decode($postdata["/list"]["listids"]);
		unset($lists['addme']);
		$calctotal = $postdata["/list"]["addme"] ? 1 : 0;
		foreach ($lists as $id) {
			if (!userOwns('list', $id) && !isSubscribed("list", $id))
				continue;
			$list = new PeopleList($id+0);
			$renderedlist = new RenderedList2();
			$renderedlist->pagelimit = 0;
			$renderedlist->initWithList($list);
			$calctotal = $calctotal + $renderedlist->getTotal();
		}

		$formdata = array($this->title);

		$schedule = getSchedule($postdata);
		if ($schedule && $schedule['maxjobdays'] == 1 && $schedule['callearly'] && $schedule['calllate'] && ((strtotime($schedule['calllate']) - 3600) < strtotime($schedule['callearly']))) {
			$html = '<div style="font-size: medium; color: red">' . escapehtml(_L('Your call window for this job appears to be less than one hour. It may not be able to retry all undelivered messages.'));
			$formdata["warning"] = array(
				"label" => '<div style="color: red;">'. _L("Warning"). '</div>',
				"control" => array("FormHtml", "html" => $html),
				"helpstep" => 1
			);
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
			"transient" => true,
			"helpstep" => 1
		);

		return new Form("confirm",$formdata,array());
	}
}
?>
