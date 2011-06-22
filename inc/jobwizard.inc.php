<?
////////////////////////////////////////////////////////////////////////////////
// global wizard functions
////////////////////////////////////////////////////////////////////////////////

// Check the whole of the wizard post data and include user authorization to figure out if this wizard has an assigned message group
function wizHasMessageGroup($wiz) {
	global $USER;
	if ($wiz->dataHelper("/message/pickmessage:messagegroup"))
		return true;
	return false;
}

// Check the wizard to figure out if this wizard has an assigned message
function wizHasMessage($wiz, $messagetype) {
	if ($messagetype == "phone") {
		if ($wiz->dataHelper("/message/phone/callme:message"))
			return true;
	}
	if ($wiz->dataHelper("/message/$messagetype/text:message"))
		return true;
	if ($wiz->dataHelper("/message/pickmessage:messagegroup")) {
		$mg = new MessageGroup($wiz->dataHelper("/message/pickmessage:messagegroup"));
		return $mg->hasMessage($messagetype);
	}
	return false;
	
}

// checks postdata to see if any auto translations are requested
function wizHasTranslation($wiz) {
	if ($wiz->dataHelper('/message/email/text:translate'))
		return true;
	if ($wiz->dataHelper('/message/phone/text:translate'))
		return true;
	if ($wiz->dataHelper('/message/pickmessage:messagegroup')) {
		if (QuickQuery("select 1 from message where messagegroupid = ? 
						and autotranslate = 'translated' limit 1", 
						false, array($wiz->dataHelper('/message/pickmessage:messagegroup')))) {
			return true;
		}
	}
	return false;
}

// get the user requested schedule out of postdata
function getSchedule($wiz) {
	global $ACCESS;
	global $USER;
	$schedule = array();

	$scheduleoptions = $wiz->dataHelper("/schedule/options:schedule");
	$maxjobdays = $wiz->dataHelper("/schedule/advanced:maxjobdays");
	if (!$maxjobdays)
		$maxjobdays = 1;
	switch ($scheduleoptions) {
		case "now":

			//get the callearly and calllate defaults
			$callearly = date("g:i a");
			$calllate = $USER->getCallLate();
			
			//get access profile settings
			$accessCallearly = $ACCESS->getValue("callearly");
			if (!$accessCallearly)
				$accessCallearly = "12:00 am";
			$accessCalllate = $ACCESS->getValue("calllate");
			if (!$accessCalllate)
				$accessCalllate = "11:59 pm";
			
			//convert everything to timestamps for comparisons
			$callearlysec = strtotime($callearly);
			$calllatesec = strtotime($calllate);
			$accessCallearlysec = strtotime($accessCallearly);
			$accessCalllatesec = strtotime($accessCalllate);
			
			//get calllate first from user pref, try to ensure it is at least an hour after start, up to access restriction
			if ($callearlysec + 3600 > $calllatesec)
				$calllatesec = $callearlysec + 3600;
			
			//make sure the calculated calllate is not past access profile
			if ($calllatesec  > $accessCalllatesec)
				$calllatesec = $accessCalllatesec;
			
			$calllate = date("g:i a", $calllatesec);
			
			$schedule = array(
				"maxjobdays" => $maxjobdays,
				"date" => date('m/d/Y'),
				"callearly" => $callearly,
				"calllate" => $calllate
			);
			break;
		case "schedule":
			$schedule = array(
				"maxjobdays" => $maxjobdays,
				"date" => date('m/d/Y', strtotime($wiz->dataHelper("/schedule/date:date"))),
				"callearly" => $wiz->dataHelper("/schedule/date:callearly"),
				"calllate" => $wiz->dataHelper("/schedule/date:calllate")
			);
			break;
		case "template":
			$schedule = array(
				"maxjobdays" => $maxjobdays,
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

function parseLists ($wiz) {
	// get the list or lists
	$joblists = $wiz->dataHelper("/list:listids", true);
	// Remove temporary 'addme' token from listids. (not a valid listid, obviously)
	if (($i = array_search('addme', $joblists)) !== false)
		unset($joblists[$i]);
	
	return array_values($joblists); //have to re-key array, pdo explodes if gap in keys
}

//returns true if some of the lists were created in the wizard
function someListsAreNew ($wiz) {
	$joblists = parseLists($wiz);
	if (count($joblists) == 0)
		return false;
	//see if any of the lists are softdeleted, which means they must have been created in the wizard (can't select them otherwise)
	$query = "select 1 from list where deleted and id in (" . DBParamListString(count($joblists)). ") limit 1";
	
	return QuickQuery($query, false, $joblists); 
}

function facebookAuthorized($wiz) {
	global $USER;
	if (getSystemSetting("_hasfacebook") && $USER->authorize("facebookpost")) {
		// this user's accesstoken validity
		$isvalidtoken = (isset($_SESSION['wiz_facebookauth']) && $_SESSION['wiz_facebookauth']);
		// if we started with a valid token or added one later
		if ($isvalidtoken || $wiz->dataHelper('/message/post/facebookauth:facebookauth'))
			return true;
	}
	return false;
}

function twitterAuthorized($wiz) {
	global $USER;
	if (getSystemSetting("_hastwitter") && $USER->authorize("twitterpost")) {
		// this user's accesstoken validity
		$isvalidtoken = (isset($_SESSION['wiz_twitterauth']) && $_SESSION['wiz_twitterauth']);
		// if we started with a valid token or added one later
		if ($isvalidtoken || $wiz->dataHelper('/message/post/twitterauth:twitterauth'))
			return true;
	}
	return false;
}

////////////////////////////////////////////////////////////////////////////////
// Custom Form Item Definitions
////////////////////////////////////////////////////////////////////////////////

class EasyCall extends FormItem {
	function render ($value) {
		$n = $this->form->name."_".$this->name;
		if (isset($this->args['languages']) && $this->args['languages'])
			$languages = $this->args['languages'];
		else
			$languages = array();

		$defaultphone = "";
		if (isset($this->args['phone']))
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

class TimeSelectMenu extends FormItem {
	function render ($value) {
		global $SETTINGS;
		global $ACCESS;
		global $USER;
		
		$values = newform_time_select(NULL, $ACCESS->getValue('callearly'), $ACCESS->getValue('calllate'), $value);
		
		$n = $this->form->name."_".$this->name;
		$size = isset($this->args['size']) ? 'size="'.$this->args['size'].'"' : "";
		$str = '<select style="float:left" id='.$n.' name="'.$n.'" '.$size .' onchange="changeTimeSelectNote(\''.$n.'\', this.value)">';
		foreach ($values as $selectvalue => $selectname) {
			$checked = $value == $selectvalue;
			$str .= '<option value="'.escapehtml($selectvalue).'" '.($checked ? 'selected' : '').' >'.escapehtml($selectname).'</option>
				';
		}
		
		// choose icon based on time of day
		$warnearly = $SETTINGS['feature']['warn_earliest'] ? $SETTINGS['feature']['warn_earliest'] : "7:00 am";
		$warnlate = $SETTINGS['feature']['warn_latest'] ? $SETTINGS['feature']['warn_latest'] : "9:00 pm";
		
		if (strtotime($value) < strtotime($warnearly) || strtotime($value) > strtotime($warnlate)) {
			$icon = "img/icons/moon_16.gif";
			$timeNote = stripslashes(_L("WARNING: Outside typical calling hours."));
		} else {
			$icon = "img/icons/weather_sun.gif";
			$timeNote = "";
		}
		
		$str .= '</select><img style="padding-left: 5px; float:left" id="'.$n.'-timenoteimg" src="'. $icon. '" /><div style="padding-left: 5px; padding-top: 2px; float:left; color:red" id="'.$n.'-timenote" >'. $timeNote. '</div>
			<script type="text/javascript">
				function changeTimeSelectNote(element, value) {
					var earlyTime = new Date("1/1/2010 '. $warnearly. '");
					var lateTime = new Date("1/1/2010 '. $warnlate. '");
					var thisTime = new Date("1/1/2010 " + value); 
					
					if (thisTime < earlyTime || thisTime > lateTime) {
						$(element + "-timenoteimg").src = "img/icons/moon_16.gif";
						$(element + "-timenote").update("'.stripslashes(_L("WARNING: Outside typical calling hours.")).'");
					} else {
						$(element + "-timenoteimg").src = "img/icons/weather_sun.gif";
						$(element + "-timenote").update();
					}
				}
			</script>';
		return $str;
	}

}

////////////////////////////////////////////////////////////////////////////////
// Validators
////////////////////////////////////////////////////////////////////////////////

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

class ValMessageTypeSelect extends Validator {

	function validate ($value, $args) {
		// MUST contain one of phone, email or sms
		if (!array_intersect(array('phone','email','sms'), $value))
			return "$this->label ". _L('requires one message of type Phone, Email or SMS Text.');
		return true;
	}

	function getJSValidator () {
		return '
			function (name, label, value, args) {
				var isvalid = false;
				$A(value).each(function (val) {
					if (val == "phone" || val == "email" || val == "sms")
						isvalid = true;
				});
				if (!isvalid)
					return label + " '. _L("requires one message of type Phone, Email or SMS Text.") .'";
				return true;
			}
		';
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

		$helpstepnum = 1;
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
			"helpstep" => $helpstepnum++
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
			"helpstep" => $helpstepnum++
		);

		$formdata["package"] = array(
			"label" => _L("Notification Method"),
			"fieldhelp" => _L("Choose a notification method. Click the \"Guide\" button to the right for a detailed description of each method.<br><br>
			<i><b>Note:</b> Email and SMS text messaging are optional features and may not be enabled.</i>"),
			"validators" => array(
				array("ValRequired"),
				array("ValInArray", "values" => array('easycall', 'express', 'personalized', 'custom'))
			),
			"value" => "",
			"control" => array("HtmlRadioButtonBigCheck", "values" => $packages),
			"helpstep" => $helpstepnum++
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
		
		// cache user facebook and twitter auth status so we don't have to check it on every single page load
		if (getSystemSetting("_hasfacebook") && $USER->authorize("facebookpost"))
			$_SESSION['wiz_facebookauth'] = fb_hasValidAccessToken();
		if (getSystemSetting("_hastwitter") && $USER->authorize("twitterpost")) {
			$tw = new Twitter($USER->getSetting("tw_access_token", false));
			$_SESSION['wiz_twitterauth'] = $tw->hasValidAccessToken();
		}
		
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
		$values["create"] =_L("Create a Message");
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
		if ($this->parent->dataHelper('/start:package') == "custom")
			return true;
		return false;
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

		// used to preview email message with correct template (emergency, or notification)
		$wizJobType = isset($_SESSION['wizard_job']['jobtype'])?$_SESSION['wizard_job']['jobtype']:"all";
		// TODO fix hack, should really get the jobtype.systempriority value
		if ($wizJobType == "emergency")
			$jobpriority = 1;
		else
			$jobpriority = 3;
		
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
				array("ValMessageGroup")
			),
			"control" => array("MessageGroupSelectMenu","width"=>"80%", "values"=>$messages, "jobpriority"=>$jobpriority),
			"helpstep" => 1
		);
		return new Form("messageGroupChoose",$formdata,$helpsteps);
	}

	//returns true if this step is enabled
	function isEnabled($postdata, $step) {
		global $USER;
		if (!$USER->authorize("sendphone") && !$USER->authorize("sendemail") && !($USER->authorize("sendsms") && getSystemSetting("_hassms")))
			return false;
		// if custom and pick message selected
		if ($this->parent->dataHelper("/message/options:options") == "pick")
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
		
		if ($USER->authorize('sendphone'))
			$values['phone'] = _L("Phone Call");
		if ($USER->authorize('sendemail'))
			$values['email'] = _L("Email");
		if (getSystemSetting('_hassms') && $USER->authorize('sendsms'))
			$values['sms'] = _L("SMS Text");
		if ((getSystemSetting('_hasfacebook', false) && $USER->authorize('facebookpost')) ||
				(getSystemSetting('_hastwitter', false) && $USER->authorize('twitterpost')))
			$values['post'] = _L("Social Media/Page post");

		$formdata[] = $this->title;
		$helpsteps = array(_L("Choose how you you like your message to be delivered."));
		$formdata["type"] = array(
			"label" => _L("Message Type"),
			"fieldhelp" => _L("Choose the message options you would like to configure."),
			"value" => "",
			"validators" => array(
				array("ValRequired"),
				array("ValMessageTypeSelect")
			),
			"control" => array("MultiCheckBox", "values"=>$values),
			"helpstep" => 1
		);
		return new Form("messageSelect",$formdata,$helpsteps);
	}

	//returns true if this step is enabled
	function isEnabled($postdata, $step) {
		if ($this->parent->dataHelper("/message/options:options") == "create")
			return true;
		return false;
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
		$values["text"] =_L("Type a Message");
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
		if (in_array('phone',$this->parent->dataHelper('/message/pick:type', false, array())))
			return true;
		return false;
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
				"value" => ($this->parent->dataHelper('/start:package') == "express")?true:false,
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

		// if its express, you have to enter phone text
		if ($this->parent->dataHelper('/start:package') == "express")
			return true;

		// if it's custom and type create and phone is selected and you chose text, you must enter phone text
		if ($this->parent->dataHelper('/message/select:phone') == "text")
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

		$msgdata = $this->parent->dataHelper("/message/phone/text:message", true, '{"gender": "female", "text": ""}');

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

		// if phone translation requested
		if ($this->parent->dataHelper("/message/phone/text:translate"))
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

		// if its easycall or personalized, you have to record
		$package = $this->parent->dataHelper('/start:package');
		if ($package == "easycall" || $package == "personalized")
			return true;
			
		// if it's custom and type create and phone is selected and you chose record, you must record
		if ($this->parent->dataHelper("/message/select:phone") == 'record')
			return true;

		return false;
	}
}


class JobWiz_messageEmailText extends WizStep {
	function getForm($postdata, $curstep) {
		global $USER;
		$msgdata = $this->parent->dataHelper("/message/phone/text:message", true, '{"gender": "female", "text": ""}');
		
		// Form Fields.
		$formdata = array($this->title);

		$helpsteps = array(_L("Enter the name for the email account."));
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
			"value" => "",
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

		// if its express or personalized, you have to enter email text
		$package = $this->parent->dataHelper('/start:package');
		if ($package == "express" || $package == "personalized")
			return true;
		
		// if it's custom and type create and email is selected, you must enter email text
		if (in_array('email',$this->parent->dataHelper('/message/pick:type', false, array())))
			return true;

		return false;
	}
}

class JobWiz_messageEmailTranslate extends WizStep {

	function getForm($postdata, $curstep) {
		global $TRANSLATIONLANGUAGECODES;

		static $translations = false;
		static $translationlanguages = false;

		$englishtext = $this->parent->dataHelper('/message/email/text:message', false, "");

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

		// if email translation requested
		if ($this->parent->dataHelper("/message/email/text:translate"))
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
		
		// if the customer/user doesn't have sms
		if (!getSystemSetting('_hassms') || !$USER->authorize("sendsms"))
			return false;

		// if its express or personalized, you have to enter email text
		$package = $this->parent->dataHelper('/start:package');
		if ($package == "express" || $package == "personalized")
			return true;
		
		// if it's custom and type create and sms is selected, you must enter email text
		if (in_array('sms',$this->parent->dataHelper('/message/pick:type', false, array())))
			return true;
		
		return false;
	}
}

class JobWiz_facebookAuth extends WizStep {
	function getForm($postdata, $curstep) {
	
		// FB auth note
		$html = "<div>". escapehtml(_L("If you would like to create a message on Facebook, connect to a facebook account now.")). "</div>";
		
		// Form Fields.
		$formdata = array(
			$this->title,
			"facebooknote" => array(
				"label" => _L("Note"),
				"control" => array("FormHtml","html" => $html),
				"helpstep" => 1
			),
			"facebookauth" => array(
				"label" => _L('Add Facebook Account'),
				"fieldhelp" => _L("Authorize this application to post to your Facebook account. If you want to authorize a different account, be sure to log out of Facebook first."),
				"value" => false,
				"validators" => array(),
				"control" => array("FacebookAuth"),
				"helpstep" => 1
			)
		);
		$helpsteps = array(_L("Before you can post your message to Facebook, you must authorize this application. Click the Connect to Facebook button to get started. <br><br>If you are logged into Facebook already, make sure you are logged into the account you plan to use for messaging. If you authorize the wrong account, you can disconnect from Facebook on your Account page, accessible from the Account link in the upper right corner. Then log out of Facebook using Facebook's web site. When you attempt to connect to Facebook again, you will be able to log into the correct account.<br><br><b>Note:</b> Disconnecting from Facebook from within this application will not automatically remove authorization from your Facebook account. You will need to do that from your Facebook account."));
		return new Form("facebookauth",$formdata,$helpsteps);
	}

	//returns true if this step is enabled
	function isEnabled($postdata, $step) {
		global $USER;
		
		// if they are allowed to post
		if (!(getSystemSetting("_hasfacebook") && $USER->authorize("facebookpost")))
			return false;
		
		// this user's accesstoken validity. if their token is good, we don't need this step
		if (isset($_SESSION['wiz_facebookauth']) && $_SESSION['wiz_facebookauth'])
			return false;
		
		// if it's custom and type create and post is selected, you can authorize facebook
		if ($this->parent->dataHelper('/start:package') == "custom") {
			// selected post type
			if (in_array('post',$this->parent->dataHelper('/message/pick:type', false, array())))
				return true;
			
			// if type is pick and the message group has facebook content
			$mgid = $this->parent->dataHelper('/message/pickmessage:messagegroup');
			if ($mgid)
				$mg = new MessageGroup($mgid);
			else
				$mg = new MessageGroup();
			
			if ($mg->hasMessage("post","facebook"))
				return true;
		} else {
			// any other package, it's enabled.
			return true;
		}
		return false;
	}
}

class JobWiz_twitterAuth extends WizStep {
	function getForm($postdata, $curstep) {
		global $USER;
		
		// Twitter auth note
		$html = "<div>". escapehtml(_L("If you would like to create a message on Twitter, connect to a facebook account now.")). "</div>";
		
		// Form Fields.
		$formdata = array(
			$this->title,
			"twitternote" => array(
				"label" => _L("Note"),
				"control" => array("FormHtml","html" => $html),
				"helpstep" => 1
			),
			"twitterauth" => array(
				"label" => _L('Add Twitter Account'),
				"fieldhelp" => _L("Authorize this application to post to your Twitter account. If you want to authorize a different account, be sure to log out of Twitter first."),
				"value" => false,
				"validators" => array(),
				"control" => array("TwitterAuth"),
				"helpstep" => 1
			)
		);
		$helpsteps = array(_L("Before you can post messages to Twitter, you must authorize this application. Clicking on the Connect to Twitter button will redirect you to Twitter. After you authorize this application, you will return to this page.<br><br> If you need to authorize a different account, make sure to log out of Twitter. Clicking the Connect to Twitter button will then allow you to select a different Twitter account."));
		return new Form("twitterauth",$formdata,$helpsteps);
	}

	//returns true if this step is enabled
	function isEnabled($postdata, $step) {
		global $USER;
		
		// if they are allowed to post
		if (!(getSystemSetting("_hastwitter") && $USER->authorize("twitterpost")))
			return false;
		
		// this user's accesstoken validity. if their token is good, we don't need this step
		if (isset($_SESSION['wiz_twitterauth']) && $_SESSION['wiz_twitterauth'])
			return false;
		
		// if it's custom and type create and post is selected, you can authorize twitter
		if ($this->parent->dataHelper('/start:package') == "custom") {
			// selected post type
			if (in_array('post',$this->parent->dataHelper('/message/pick:type', false, array())))
				return true;
			
			// if type is pick and the message group has twitter content
			$mgid = $this->parent->dataHelper('/message/pickmessage:messagegroup');
			if ($mgid)
				$mg = new MessageGroup($mgid);
			else
				$mg = new MessageGroup();
			
			if ($mg->hasMessage("post","twitter"))
				return true;
		} else {
			// any other package, it's enabled.
			return true;
		}
		return false;
	}
}

class JobWiz_socialMedia extends WizStep {
	function getForm($postdata, $curstep) {
		global $USER;
		
		// only enabled by default on custom
		$smEnable = false;
		$package = isset($postdata['/start']['package'])?$postdata['/start']['package']:false;
		$customtype = isset($postdata['/message/options']['options'])?$postdata['/message/options']['options']:false;
		if ($package == "custom" && $customtype == "create")
			$smEnable = true;
		
		$defaulttext = _L("We have sent out a new message, you can preview it here.");
		
		// for facebook text, check email, then phone, then sms
		$fbtext = html_to_plain($this->parent->dataHelper('/message/email/text:message'));
		if (!$fbtext )
			$fbtext = $this->parent->dataHelper('/message/phone/text:message', true, '{"gender": "female", "text": ""}')->text;
		if (!$fbtext)
			$fbtext = $this->parent->dataHelper('/message/sms/text:message');
		if (!$fbtext)
			$fbtext = $defaulttext;
		
		// for twitter text, check sms, then email, then phone
		$twtext = $this->parent->dataHelper('/message/sms/text:message');
		if (!$twtext)
			$twtext = html_to_plain($this->parent->dataHelper('/message/email/text:message'));
		if (!$twtext)
			$twtext = $this->parent->dataHelper('/message/phone/text:message', true, '{"gender": "female", "text": ""}')->text;
		if (!$twtext)
			$twtext = $defaulttext;
		
		$formdata = array($this->title);
		$helpstepnum = 1;
		$helpsteps = array();
		
		// Facebook
		if (facebookAuthorized($this->parent)) {
			$helpsteps[] = _L("Enter the message you wish to deliver via Facebook.");
			$formdata["fbdata"] = array(
				"label" => _L('Facebook'),
				"fieldhelp" => _L("Create your Facebook posting text here."),
				"value" => ($smEnable?$fbtext:""),
				"validators" => array(
					array("ValLength","max"=>420)),
				"control" => array("TextAreaWithEnableCheckbox", "defaultvalue" => $fbtext, "rows"=>10,"cols"=>50,"counter"=>420),
				"helpstep" => $helpstepnum++
			);
		}
		
		// Twitter
		if (twitterAuthorized($this->parent)) {
			// need to reserve some characters for the link url and the six byte code. (http://smalldomain.com/<code>)
			$reservedchars = mb_strlen(" http://". getSystemSetting("tinydomain"). "/") + 6;
			$helpsteps[] = _L("Enter the message you wish to deliver via Twitter.");
			$formdata["twdata"] = array(
				"label" => _L("Twitter"),
				"fieldhelp" => _L("Select what text to use as a status update."),
				"value" => ($smEnable?$twtext:""),
				"validators" => array(
					array("ValLength","max"=>(140 - $reservedchars))),
				"control" => array("TextAreaWithEnableCheckbox", "defaultvalue" => $twtext, "rows"=>5,"cols"=>50,"counter"=>(140 - $reservedchars)),
				"helpstep" => $helpstepnum++
			
			);
		}
		
		return new Form("socialMedia",$formdata,$helpsteps);
	}
	
	//returns true if this step is enabled
	function isEnabled($postdata, $step) {
		
		if (facebookAuthorized($this->parent) || twitterAuthorized($this->parent)) {
			// everything but custom enables this step outright
			if ($this->parent->dataHelper('/start:package') !== "custom")
				return true;
			// if it's a create message and has post type
			$msgtypes = $this->parent->dataHelper('/message/pick:type', false, array());
			if ($msgtypes && in_array("post", $msgtypes))
				return true;
		}
		return false;
	}
}

class JobWiz_facebookPage extends WizStep {
	function getForm($postdata, $curstep) {
		global $USER;
		
		$formdata = array (
			"fbpage" => array(
				"label" => _L('Facebook Page(s)'),
				"fieldhelp" => _L("Select which pages to post to."),
				"value" => "",
				"validators" => array(
					array("ValRequired"),
					array("ValFacebookPage", "authpages" => getFbAuthorizedPages(), "authwall" => getSystemSetting("fbauthorizewall"))),
				"control" => array("FacebookPage", "access_token" => $USER->getSetting("fb_access_token", false)),
				"helpstep" => 1)
		);
		
		$helpsteps = array(_L("TODO: help"));
		
		return new Form("facebookPage",$formdata,$helpsteps);
	}
	
	//returns true if this step is enabled
	function isEnabled($postdata, $step) {
		// if type is pick and the message group has facebook content
		$mgid = $this->parent->dataHelper('/message/pickmessage:messagegroup');
		if ($mgid)
			$mg = new MessageGroup($mgid);
		else
			$mg = new MessageGroup();
		
		if ($mg->hasMessage("post","facebook") && facebookAuthorized($this->parent))
			return true;
		
		// or, there is a facebook message created
		if ($this->parent->dataHelper('/message/post/socialmedia:fbdata') && facebookAuthorized($this->parent))
			return true;
		
		return false;
	}
}

class JobWiz_scheduleOptions extends WizStep {
	function getForm($postdata, $curstep) {
		global $USER;
		global $ACCESS;
		$wizHasPhoneMsg = wizHasMessage($this->parent, "phone");
		$wizHasEmailMsg= wizHasMessage($this->parent, "email");
		
		$helpstepnum = 1;
		
		//get the callearly and calllate defaults
		$callearly = date("g:i a");
		$calllate = $USER->getCallLate();
		
		//get access profile settings
		$accessCallearly = $ACCESS->getValue("callearly");
		if (!$accessCallearly)
			$accessCallearly = "12:00 am";
		$accessCalllate = $ACCESS->getValue("calllate");
		if (!$accessCalllate)
			$accessCalllate = "11:59 pm";
		
		//convert everything to timestamps for comparisons
		$callearlysec = strtotime($callearly);
		$calllatesec = strtotime($calllate);
		$accessCallearlysec = strtotime($accessCallearly);
		$accessCalllatesec = strtotime($accessCalllate);		
				
		//get calllate first from user pref, try to ensure it is at least an hour after start, up to access restriction
		if ($callearlysec + 3600 > $calllatesec)
			$calllatesec = $callearlysec + 3600;
		
		//make sure the calculated calllate is not past access profile
		if ($calllatesec  > $accessCalllatesec)
			$calllatesec = $accessCalllatesec;
		
		$calllate = date("g:i a", $calllatesec);
			
		$menu = array();

		$isStartBeforeEnd = $callearlysec < $calllatesec; // Check that the NOW call early time is less than the NOW call late time
		$isStartAllowed = $callearlysec >= $accessCallearlysec; // and it's greater than or equal to the access profile call early time
		$isEndAllowed = $calllatesec <= $accessCalllatesec; // and the call late time is less than or equal to the access call late time
		if ($isStartBeforeEnd && $isStartAllowed && $isEndAllowed)
			$menu["now"] = _L("Now"). " ($callearly - $calllate)";
		$menu["schedule"] = _L("Schedule and Send");
		$menu["template"] = _L("Save for Later");

		$formdata = array($this->title);
		
		$helpsteps = array(_L("Select when to send this message or you may choose to save this job for later."));
		$formdata["schedule"] = array(
			"label" => _L("Delivery Schedule"),
			"fieldhelp" => _L("Select when to send this message or you may choose to save this job for later. Please note that Social Media posts may not be saved for later."),
			"value" => "",
			"validators" => array(
				array("ValRequired"),
				array("ValInArray", "values" => array_keys($menu))
			),
			"control" => array("RadioButton","values"=>$menu),
			"helpstep" => $helpstepnum
		);

		if ($wizHasEmailMsg || $wizHasPhoneMsg) {
			$helpsteps[] = _L("Set advanced options such as duplicate removal and number of days to run.");
			$formdata["advanced"] = array(
				"label" => _L("Advanced Options"),
				"fieldhelp" => _L('Check here if you would like to set additional options for this notification such as duplicate removal and number of days to run.'),
				"value" => "",
				"validators" => array(),
				"control" => array("CheckBox"),
				"helpstep" => ++$helpstepnum
			);
		}
		
		//add checkbox for reviewing and saving lists if some lists were created in the wizard
		if (someListsAreNew($this->parent)) {
			
			$joblists = parseLists($this->parent);
			$lists = DBFindMany("PeopleList", "from list where deleted and id in (" . DBParamListString(count($joblists)). ")", false, $joblists);
			
			
			
			foreach ($lists as $list) {
			$renderedlist = new RenderedList2();
			$renderedlist->pagelimit = 0;
			$renderedlist->initWithList($list);
			$total = $renderedlist->getTotal() + 0;
			
			$values[$list->id] = $list->name;
			$hover[$list->id] = '
				<table>
					<tr><th>Name:</th><td>'.escapehtml($list->name).'</td></tr>
					<tr><th>Total:</th><td>'.$total.'</td></tr>
				</table>
				';
			}
					
			$helpsteps[] = _L("You may save the list you've created in MessageSender so that it is available for future jobs. Your list will be viewable in the List Builder, found under the Notifications tab.");
			$formdata["savelists"] = array(
				"label" => _L("Save Lists"),
				"fieldhelp" => _L('Check each list that you would like to save for future jobs.'),
				"value" => array(),
				"validators" => array(),
				"control" => array("MultiCheckBox", "values" => $values, "hover" => $hover),
				"helpstep" => ++$helpstepnum
			);
		}
		
		//add checkbox for reviewing and saving the message, only show message checkbox if they created the message
		if (!wizHasMessageGroup($this->parent)) {
			$helpsteps[] = _L("You may save the message you've created in MessageSender so that it is available for future jobs. Your message will be viewable in the Message Builder, found under the Notifications tab.");
			$formdata["savemessage"] = array(
				"label" => _L("Save Message"),
				"fieldhelp" => _L('Select this option if you would like to save your message for future jobs.'),
				"value" => false,
				"validators" => array(),
				"control" => array("CheckBox"),
				"helpstep" => ++$helpstepnum
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
		$translated = wizHasTranslation($this->parent);

		// Form Fields.
		$formdata = array($this->title);

		// Make sure it's 30 minutes or more before the end of their allowed access call window
		$dayoffset = (strtotime("now") > (strtotime(($ACCESS->getValue("calllate")?$ACCESS->getValue("calllate"):"11:59 pm"))))?1:0;
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
			"control" => array("TimeSelectMenu"),
			"helpstep" => 2
		);
		
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
			"control" => array("TimeSelectMenu"),
			"helpstep" => 2
		);

		return new Form("scheduleDate",$formdata,$helpsteps);
	}

	//returns true if this step is enabled
	function isEnabled($postdata, $step) {
		if ($this->parent->dataHelper('/schedule/options:schedule') == "schedule")
			return true;
		return false;
	}
}
class JobWiz_scheduleAdvanced extends WizStep {
	function getForm($postdata, $curstep) {
		global $USER;
		global $ACCESS;
		$wizHasPhoneMsg = wizHasMessage($this->parent, "phone");
		$wizHasEmailMsg= wizHasMessage($this->parent, "email");
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
		if ($this->parent->dataHelper('/schedule/options:advanced'))
			return true;
		return false;
	}
}

class JobWiz_submitConfirm extends WizStep {
	function getForm($postdata, $curstep) {
		$wizHasPhoneMsg = wizHasMessage($this->parent, "phone");
		$wizHasEmailMsg= wizHasMessage($this->parent, "email");
		$wizHasSmsMsg= wizHasMessage($this->parent, "sms");
		$wizHasMessageGroup= wizHasMessageGroup($this->parent);

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
		$lists = $this->parent->dataHelper("/list:listids", true);
		unset($lists['addme']);
		$calctotal = $this->parent->dataHelper("/list:addme")? 1 : 0;
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

		$schedule = getSchedule($this->parent);
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
				$html .= escapehtml(_L('Confirm and click Next to send this notification to the 1 person you selected'));
			else
				$html .= escapehtml(_L('Confirm and click Next to send this notification to the %1$s people you selected.', $calctotal, count($lists)));
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
