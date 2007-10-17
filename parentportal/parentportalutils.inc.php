<?

function getContactIDs($portaluserid){
	return QuickQueryList("select personid from portalperson where portaluserid = '$portaluserid' order by personid");
}

function getContacts($portaluserid) {
	$contactList = getContactIDs($portaluserid);
	return DBFindMany("Person", "from person where id in ('" . implode("','", $contactList) . "') and not deleted order by id");
}

//put form data for contact details
function putContactPrefFormData($f, $s, $contactprefs, $defaultcontactprefs, $phones, $emails, $smses, $jobtypes){
	foreach($emails as $email){
		PutFormData($f, $s, "email" . $email->sequence, $email->email, "email", 0, 100);
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
		PutFormData($f, $s, "phone" . $phone->sequence, Phone::format($phone->phone), "phone", 10);
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
			PutFormData($f, $s, "sms" . $sms->sequence, Phone::format($sms->sms), "phone", 0, 10);
			foreach($jobtypes as $jobtype){
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

//displays checkbox for each jobtype 
function displayJobtypeForm($f, $s, $type, $sequence, $jobtypes){
	foreach($jobtypes as $jobtype){
		?><div style="float:left"><?=jobtype_info($jobtype)?><? NewFormItem($f, $s, $type . $sequence . "jobtype" . $jobtype->id, "checkbox", 0, 1); ?></div><?
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
	return implode(",",$enabled);
}

//copies contact details and preferences of main person to all persons in otherpids
function copyContactData($mainpid, $otherpids = array(), $accessiblePhones){
	$mainphones = QuickQueryList("select sequence, phone from phone where personid = '" . $mainpid . "'", true);
	$mainemails = QuickQueryList("select sequence, email from email where personid = '" . $mainpid . "'", true);
	if(getSystemSetting("_hassms")){
		$mainsmses = QuickQueryList("select sequence, sms from sms where personid = '" . $mainpid . "'", true);
	}
	$mainContactPrefs = getContactPrefs($mainpid);
	
	foreach($otherpids as $pid){
		$phones = DBFindMany("Phone", "from phone where personid = '" . $pid . "'");
		$emails = DBFindMany("Email", "from email where personid = '" . $pid . "'");
		if(getSystemSetting("_hassms")){
			$smses = DBFindMany("Sms", "from sms where personid = '" . $pid . "'");
		}
		foreach($phones as $phone){
			if($accessiblePhones[$phone->sequence]){
				$phone->phone = $mainphones[$phone->sequence];
				$phone->editlock = 1;
				$phone->update();
			}
		}
		foreach($emails as $email){
			$email->email = $mainemails[$email->sequence];
			$email->editlock = 1;
			$email->update();
		}
		if(getSystemSetting("_hassms")){
			foreach($smses as $sms){
				$sms->sms = $mainseses[$sms->sequence];
				$sms->editlock = 1;
				$sms->update();
			}
		}
		foreach($mainContactPrefs as $type => $sequencePrefs){
			foreach($sequencePrefs as $sequence => $jobtypePrefs){
				foreach($jobtypePrefs as $jobtypeid => $enabled){
					QuickUpdate("insert into contactpref (personid, jobtypeid, type, sequence, enabled)
									values ('" . $pid . "','" . $jobtypeid . "','" . $type . "','" . $sequence . "','" 
									. $enabled . "') 
									on duplicate key update
									personid = '" . $pid . "',
									jobtypeid = '" . $jobtypeid . "',
									type = '" . $type . "',
									sequence = '" . $sequence . "',
									enabled = '" . $enabled . "'");
				}
			}
		}
	}
}

//Gets form data and updates contact details
function getsetContactFormData($f, $s, $PERSONID, $phones, $emails, $smses, $jobtypes){
	global $accessiblePhones;
	foreach($phones as $phone){
		if($accessiblePhones[$phone->sequence]){
			$phone->phone = Phone::parse(GetFormData($f, $s, "phone" . $phone->sequence));
			$phone->editlock = 1;
			$phone->update();
			foreach($jobtypes as $jobtype){
				QuickUpdate("insert into contactpref (personid, jobtypeid, type, sequence, enabled)
							values ('" . $PERSONID . "','" . $jobtype->id . "','phone','" . $phone->sequence . "','" 
							. DBSafe(GetFormData($f, $s, "phone" . $phone->sequence . "jobtype" . $jobtype->id)) . "') 
							on duplicate key update
							personid = '" . $PERSONID . "',
							jobtypeid = '" . $jobtype->id . "',
							type = 'phone',
							sequence = '" . $phone->sequence . "',
							enabled = '" . DBSafe(GetFormData($f, $s, "phone" . $phone->sequence . "jobtype" . $jobtype->id)) . "'");
			}
		}
	}
	foreach($emails as $email){
		$email->email = GetFormData($f, $s, "email" . $email->sequence);
		$email->editlock = 1;
		$email->update();
		foreach($jobtypes as $jobtype){
			QuickUpdate("insert into contactpref (personid, jobtypeid, type, sequence, enabled)
						values ('" . $PERSONID . "','" . $jobtype->id . "','email','" . $phone->sequence . "','" 
						. DBSafe(GetFormData($f, $s, "email" . $email->sequence . "jobtype" . $jobtype->id)) . "') 
						on duplicate key update
						personid = '" . $PERSONID . "',
						jobtypeid = '" . $jobtype->id . "',
						type = 'email',
						sequence = '" . $email->sequence . "',
						enabled = '" . DBSafe(GetFormData($f, $s, "email" . $email->sequence . "jobtype" . $jobtype->id)) . "'");
		}
	}
	if(getSystemSetting("_hassms")){
		foreach($smses as $sms){
			$sms->sms = Phone::parse(GetFormData($f, $s, "sms" . $sms->sequence));
			$sms->editlock = 1;
			$sms->update();
			foreach($jobtypes as $jobtype){
				QuickUpdate("insert into contactpref (personid, jobtypeid, type, sequence, enabled)
							values ('" . $PERSONID . "','" . $jobtype->id . "','sms','" . $sms->sequence . "','" 
							. DBSafe(GetFormData($f, $s, "sms" . $sms->sequence . "jobtype" . $jobtype->id)) . "') 
							on duplicate key update
							personid = '" . $PERSONID . "',
							jobtypeid = '" . $jobtype->id . "',
							type = 'sms',
							sequence = '" . $sms->sequence . "',
							enabled = '" . DBSafe(GetFormData($f, $s, "sms" . $sms->sequence . "jobtype" . $jobtype->id)) . "'");
			}
		}
	}
}


//displays jobtype name and on hover, displays infoforparents
function jobtype_info($jobtype, $extrahtml = NULL, $style = NULL) {
	$contents = nl2br($jobtype->infoforparents);
	if($contents == ""){
		$contents = "<br/>";
	}

	$hover = '<span ' . $extrahtml . '>';
	$hover .= '<div';
	$hover .= ' onmouseover="this.nextSibling.style.display = \'block\'; setIFrame(this.nextSibling);"';
	$hover .= ' onmouseout="this.nextSibling.style.display = \'none\'; setIFrame(null);"';
	$hover .= '>&nbsp;' . $jobtype->name . '&nbsp;</div><div class="hoverhelp">' . $contents . '</div></span>';

	return $hover;
}
?>