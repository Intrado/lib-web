<?

function getPhoneReview($phone, $code) {
	$inboundnumber = getCustomerSystemSetting("inboundnumber", "");
	
	$formhtml = '<div style="height: 200px; overflow:auto;">' . 
		_L("You must follow these steps within <b>24 hours</b> to complete this addition to your account.") . '<br><br>' .
		button(_L("Print this page now"), "window.print()") . '<br><br><br>' .
		_L("Step 1: You must call from the phone ") . '<b>' . Phone::format($phone) . '</b>' . _L(" in order to verify your caller ID with our records.") . '<br>' .
		'<img src="img/bug_lightbulb.gif" >&nbsp;&nbsp;' . _L("If your phone service has caller identification blocked, you must first dial *82 to unblock it for this call.") . '<br>' .
		_L("Step 2: Call ") . '<b>' . Phone::format($inboundnumber) . '</b><br>' . 
		'Step 3: When prompted, select option 2.<br>' .
		'Step 4: When prompted, enter this activation code <span style="font-weight:bold; font-size: 140%;">' . $code . '</span><br>' .
		'Step 5: When the call is complete, log back into your account to edit your notification preferences.<br>' .
		'</div>';
	return $formhtml;
}

function getEmailReview($email) {
	$formhtml = '<div style="height: 200px; overflow:auto;">' . 
		_L("You must follow these steps within <b>24 hours</b> to complete this addition to your account.") . '<br><br>' .
		_L("Step 1: Check your email account") . ' <b>' . $email . '</b><br>' .
		_L("Step 2: Click the activation link") . '<br>' . 
		'</div>';
	return $formhtml;
}


function loadSubscriberDisplaySettings() {
	$subscriberID = $_SESSION['subscriberid'];
	
	$_SESSION['personid'] = $pid = QuickQuery("select personid from subscriber where id=?", false, array($subscriberID));
	$_SESSION['custname'] = QuickQuery("select value from setting where name='displayname'");		
	$_SESSION['productname'] = QuickQuery("select value from setting where name='_productname'");
		
	$firstnameField = FieldMap::getFirstNameField();
	$lastnameField = FieldMap::getLastNameField();
	
	$_SESSION['subscriber.username'] = QuickQuery("select username from subscriber where id=?", false, array($subscriberID));
	$_SESSION['subscriber.firstname'] = QuickQuery("select ".$firstnameField." from person where id=?", false, array($pid));
	$_SESSION['subscriber.lastname'] = QuickQuery("select ".$lastnameField." from person where id=?", false, array($pid));

	$theme = QuickQuery("select value from setting where name = '_brandtheme'");
	if ($theme === false)
		$theme = "3dblue";
	$theme1 = QuickQuery("select value from setting where name = '_brandtheme1'");
	if ($theme1 === false)
		$theme1 = "89A3CE";
	$theme2 = QuickQuery("select value from setting where name = '_brandtheme2'");
	if ($theme2 === false)
		$theme2 = "89A3CE";
	$primary = QuickQuery("select value from setting where name = '_brandprimary'");
	if ($primary === false)
		$primary = "26477D";
	$ratio = QuickQuery("select value from setting where name = '_brandratio'");
	if ($ratio === false)
		$ratio = ".3";
	$_SESSION['colorscheme']['_brandtheme']   = $theme;
	$_SESSION['colorscheme']['_brandtheme1']  = $theme1;
	$_SESSION['colorscheme']['_brandtheme2']  = $theme2;
	$_SESSION['colorscheme']['_brandprimary'] = $primary;
	$_SESSION['colorscheme']['_brandratio']   = $ratio;

	$prefs = QuickQuery("select preferences from subscriber where id=?", false, array($subscriberID));
	$preferences = json_decode($prefs, true);
	if (isset($preferences['_locale']))
		$_SESSION['_locale'] = $preferences['_locale'];
	else
		$_SESSION['_locale'] = "en_US"; // US English
}

function getContactIDs($subscriberid) {
	$firstnameField = FieldMap::getFirstNameField();
	return QuickQueryList("select personid from subscriber where id = '$subscriberid'");
}

function getContacts($subscriberid) {
	$firstnameField = FieldMap::getFirstNameField();
	$contactList = getContactIDs($subscriberid);
	return resequence(DBFindMany("Person", "from person where id in ('" . implode("','", $contactList) . "') and not deleted order by $firstnameField"), "pkey");
}

//put form data for contact details
function putContactPrefFormData($f, $s, $contactprefs, $defaultcontactprefs, $phones, $emails, $smses, $jobtypes, $locked){
	$lockedphones = $locked['phones'];
	$lockedemails = $locked['emails'];
	$lockedsms = $locked['sms'];
	foreach($emails as $email){
		if(!$lockedemails[$email->sequence]){
			PutFormData($f, $s, "email" . $email->sequence, $email->email, "email", 0, 100);
		}
		foreach($jobtypes as $jobtype){
			$contactpref = 0;
			if(isset($contactprefs["email"][$email->sequence][$jobtype->id]))
				$contactpref = $contactprefs["email"][$email->sequence][$jobtype->id];
			else if(isset($defaultcontactprefs["email"][$email->sequence][$jobtype->id]))
				$contactpref = $defaultcontactprefs["email"][$email->sequence][$jobtype->id];
			PutFormData($f, $s, "email" . $email->sequence . "jobtype" . $jobtype->id, $contactpref, "bool", 0, 1);
		}
	}
	foreach($phones as $phone){
		if(!$lockedphones[$phone->sequence]){
			PutFormData($f, $s, "phone" . $phone->sequence, Phone::format($phone->phone), "phone", 10);
		}
		foreach($jobtypes as $jobtype){
			$contactpref = 0;
			if(isset($contactprefs["phone"][$phone->sequence][$jobtype->id]))
				$contactpref = $contactprefs["phone"][$phone->sequence][$jobtype->id];
			else if(isset($defaultcontactprefs["phone"][$phone->sequence][$jobtype->id]))
				$contactpref = $defaultcontactprefs["phone"][$phone->sequence][$jobtype->id];
			PutFormData($f, $s, "phone" . $phone->sequence . "jobtype" . $jobtype->id, $contactpref, "bool", 0, 1);
		}
	}
	if(getSystemSetting("_hassms")){
		foreach($smses as $sms){
			if(!$lockedsms[$sms->sequence]){
				PutFormData($f, $s, "sms" . $sms->sequence, Phone::format($sms->sms), "phone", 0, 10);
			}
			foreach($jobtypes as $jobtype){
				if(!$jobtype->issurvey){
					$contactpref = 0;
					if(isset($contactprefs["sms"][$sms->sequence][$jobtype->id]))
						$contactpref = $contactprefs["sms"][$sms->sequence][$jobtype->id];
					else if(isset($defaultcontactprefs["sms"][$sms->sequence][$jobtype->id]))
						$contactpref = $defaultcontactprefs["sms"][$sms->sequence][$jobtype->id];
					PutFormData($f, $s, "sms" . $sms->sequence . "jobtype" . $jobtype->id, $contactpref, "bool", 0, 1);
				}
			}
		}
	}

}

//displays checkbox for each jobtype
function displayJobtypeForm($f, $s, $type, $sequence, $jobtypes){
	foreach($jobtypes as $jobtype){
		?><td class="bottomBorder" align="center"><?
		if($type!="sms" || ($type=="sms" && !$jobtype->issurvey)){
			echo NewFormItem($f, $s, $type . $sequence . "jobtype" . $jobtype->id, "checkbox", 0, 1);
		} else {
			echo "&nbsp;";
		}
		?></td><?
	}
}

//returns jobtype names of enabled preferences
function displayEnabledJobtypes($contactprefs, $defaultcontactprefs, $type, $sequence, $jobtypes){
	$enabled = array();
	foreach($jobtypes as $jobtype){
		if(isset($contactprefs[$type][$sequence][$jobtype->id])){
			if($contactprefs[$type][$sequence][$jobtype->id]){
				$enabled[] = $jobtype->name;
			}
		} else if(isset($defaultcontactprefs[$type][$sequence][$jobtype->id])){
			if($defaultcontactprefs[$type][$sequence][$jobtype->id]){
				$enabled[] = $jobtype->name;
			}
		}
	}
	return implode(", ",$enabled);
}

//copies contact details and preferences of main person to all persons in otherpids
function copyContactData($mainpid, $otherpids = array(), $locked){

	$lockedphones = $locked['phones'];
	$lockedemails = $locked['emails'];
	$mainphones = QuickQueryList("select sequence, phone from phone where personid = '" . $mainpid . "'", true);
	$mainemails = QuickQueryList("select sequence, email from email where personid = '" . $mainpid . "'", true);
	if(getSystemSetting("_hassms", false)){
		$mainsmses = QuickQueryList("select sequence, sms from sms where personid = '" . $mainpid . "'", true);
		$lockedsms = $locked['sms'];
	}
	$mainContactPrefs = getContactPrefs($mainpid);

	foreach($otherpids as $pid){
		$phones = DBFindMany("Phone", "from phone where personid = '" . $pid . "'");
		$temp = array();
		foreach($phones as $phone){
			$temp[$phone->sequence] = $phone;
		}
		$phones = $temp;

		$temp = array();
		$emails = DBFindMany("Email", "from email where personid = '" . $pid . "'");
		foreach($emails as $email){
			$temp[$email->sequence] = $email;
		}
		$emails = $temp;

		if(getSystemSetting("_hassms", false)){
			$temp = array();
			$smses = DBFindMany("Sms", "from sms where personid = '" . $pid . "'");
			foreach($smses as $sms){
				$temp[$sms->sequence] = $sms;
			}
			$smses = $temp;
		}


		foreach($mainphones as $sequence => $mainphone){
			if(!$lockedphones[$sequence]){
				if(!isset($phones[$sequence])){
					$phone = new Phone();
					$phone->phone = $mainphone;
					$phone->editlock = 1;
					$phone->personid = $pid;
					$phone->sequence=$sequence;
					$phone->create();
				} else {
					$phones[$sequence]->phone = $mainphone;
					$phones[$sequence]->editlock = 1;
					$phones[$sequence]->update();
				}
			}
		}
		foreach($mainemails as $sequence => $mainemail){
			if(!$lockedemails[$sequence]){
				if(!isset($emails[$sequence])){
					$email = new Email();
					$email->email = $mainemail;
					$email->editlock = 1;
					$email->personid = $pid;
					$email->sequence=$sequence;
					$email->create();
				} else {
					$emails[$sequence]->email = $mainemail;
					$emails[$sequence]->editlock = 1;
					$emails[$sequence]->update();
				}
			}
		}
		if(getSystemSetting("_hassms", false)){
			foreach($mainsmses as $sequence => $mainsms){
				if(!$lockedsms[$sequence]){
					if(!isset($smses[$sequence])){
						$sms = new Sms();
						$sms->sms = $mainsms;
						$sms->editlock = 1;
						$sms->personid = $pid;
						$sms->sequence=$sequence;
						$sms->create();
					} else {
						$smses[$sequence]->sms = $mainsms;
						$smses[$sequence]->editlock = 1;
						$smses[$sequence]->update();
					}
				}
			}
		}
		QuickUpdate("Begin");
		QuickUpdate("delete from contactpref where personid = '" . $pid . "'");
		$values = array();
		foreach($mainContactPrefs as $type => $sequencePrefs){
			foreach($sequencePrefs as $sequence => $jobtypePrefs){
				foreach($jobtypePrefs as $jobtypeid => $enabled){
					$values[] = "('" . $pid . "','" . $jobtypeid . "','" . $type . "','" . $sequence . "','"
									. $enabled . "')";
				}
			}
		}
		QuickUpdate("insert into contactpref (personid, jobtypeid, type, sequence, enabled)
									values " . implode(",",$values));
		QuickUpdate("Commit");
	}
}

//Gets form data and updates contact details
// returns false if any phone or email validation fails
function getsetContactFormData($f, $s, $PERSONID, $phones, $emails, $smses, $jobtypes, $locked){
	$lockedphones = $locked['phones'];
	$lockedemails = $locked['emails'];
	$lockedsms = $locked['sms'];
	QuickUpdate("Begin");
	QuickUpdate("delete from contactpref where personid = '" . $PERSONID . "'");
	$values = array();
	foreach($phones as $phone){
		if(!$lockedphones[$phone->sequence]){
			$p = GetFormData($f, $s, "phone" . $phone->sequence);
			$phone->phone = Phone::parse($p);
			$phone->editlock = 1;
			$phone->update();
		}
		foreach($jobtypes as $jobtype){
			$values[] = "('" . $PERSONID . "','" . $jobtype->id . "','phone','" . $phone->sequence . "','"
						. DBSafe(GetFormData($f, $s, "phone" . $phone->sequence . "jobtype" . $jobtype->id)) . "')";
		}
	}
	foreach($emails as $email){
		if(!$lockedemails[$email->sequence]){
			$email->email = GetFormData($f, $s, "email" . $email->sequence);
			$email->editlock = 1;
			$email->update();
		}
		foreach($jobtypes as $jobtype){
			$values[] = "('" . $PERSONID . "','" . $jobtype->id . "','email','" . $email->sequence . "','"
						. DBSafe(GetFormData($f, $s, "email" . $email->sequence . "jobtype" . $jobtype->id)) . "')";
		}
	}
	if(getSystemSetting("_hassms")){
		foreach($smses as $sms){
			if(!$lockedsms[$sms->sequence]){
				$sms->sms = Phone::parse(GetFormData($f, $s, "sms" . $sms->sequence));
				$sms->editlock = 1;
				$sms->update();
			}
			foreach($jobtypes as $jobtype){
				if(!$jobtype->issurvey){
					$values[] = "('" . $PERSONID . "','" . $jobtype->id . "','sms','" . $sms->sequence . "','"
								. DBSafe(GetFormData($f, $s, "sms" . $sms->sequence . "jobtype" . $jobtype->id)) . "')";
				}
			}

		}
	}
	QuickUpdate("insert into contactpref (personid, jobtypeid, type, sequence, enabled)
							values " . implode(",",$values));
	QuickUpdate("Commit");
}

function checkPriorityPhone($f, $s, $phones){
	$hasemergency = false;
	$jobtypenames = QuickQueryList("select id, name from jobtype where systempriority in ('1', '2') and not deleted", true);
	$jobtypelist = $jobtypenames;
	$maxphones = getSystemSetting("maxphones", 3);
	$lockedphones = array();
	for($i=0; $i < $maxphones; $i++){
		$lockedphones[$i] = getSystemSetting("lockedphone" . $i, 0);
	}
	foreach($jobtypenames as $jobtypeid => $jobtypename){
		for($i=0; $i < $maxphones; $i++){
			if(!$lockedphones[$i] && GetFormData($f, $s, "phone" . $i . "jobtype" . $jobtypeid) && GetFormData($f, $s, "phone" . $i) !== "") {
				unset($jobtypelist[$jobtypeid]);
				break;
			} else if ($lockedphones[$i] && GetFormData($f, $s, "phone" . $i . "jobtype" . $jobtypeid) && $phones[$i]->phone){
				unset($jobtypelist[$jobtypeid]);
				break;
			}
		}
	}

	return $jobtypelist;
}

function getLockedDestinations($maxphones, $maxemails, $maxsms){
	$lockedphones= array();
	for($i=0; $i < $maxphones; $i++){
		$lockedphones[$i] = getSystemSetting("lockedphone" . $i, 0);
	}
	$lockedemails= array();
	for($i=0; $i < $maxemails; $i++){
		$lockedemails[$i] = getSystemSetting("lockedemail" . $i, 0);
	}
	$lockedsmses= array();
	for($i=0; $i < $maxsms; $i++){
		$lockedsmses[$i] = getSystemSetting("lockedsms" . $i, 0);
	}
	return array("phones" => $lockedphones,
				"emails" => $lockedemails,
				"sms" => $lockedsmses);
}
?>