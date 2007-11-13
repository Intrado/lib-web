<?

function getContactIDs($portaluserid){
	return QuickQueryList("select personid from portalperson where portaluserid = '$portaluserid' order by personid");
}

function getContacts($portaluserid) {
	$contactList = getContactIDs($portaluserid);
	return DBFindMany("Person", "from person where id in ('" . implode("','", $contactList) . "') and not deleted order by id");
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
function getsetContactFormData($f, $s, $PERSONID, $phones, $emails, $smses, $jobtypes, $locked){
	$lockedphones = $locked['phones'];
	$lockedemails = $locked['emails'];
	$lockedsms = $locked['sms'];
	foreach($phones as $phone){
		if(!$lockedphones[$phone->sequence]){
			$phone->phone = Phone::parse(GetFormData($f, $s, "phone" . $phone->sequence));
			$phone->editlock = 1;
			$phone->update();
		}
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
	foreach($emails as $email){
		if(!$lockedemails[$email->sequence]){
			$email->email = GetFormData($f, $s, "email" . $email->sequence);
			$email->editlock = 1;
			$email->update();
		}
		foreach($jobtypes as $jobtype){
			QuickUpdate("insert into contactpref (personid, jobtypeid, type, sequence, enabled)
						values ('" . $PERSONID . "','" . $jobtype->id . "','email','" . $email->sequence . "','" 
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
			if(!$lockedsms[$sms->sequence]){
				$sms->sms = Phone::parse(GetFormData($f, $s, "sms" . $sms->sequence));
				$sms->editlock = 1;
				$sms->update();
			}
			foreach($jobtypes as $jobtype){
				if(!$jobtype->issurvey){
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
}

function checkEmergencyPhone($f, $s, $phones){
	$hasemergency = false;
	$emergencyjobtypeid = QuickQuery("select id from jobtype where systempriority = '1'");
	$maxphones = getSystemSetting("maxphones");
	$lockedphones = array();
	for($i=0; $i < $maxphones; $i++){
		$lockedphones[$i] = getSystemSetting("lockedphone" . $i, 0);
	}
	for($i=0; $i < $maxphones; $i++){
		if(!$lockedphones[$i] && GetFormData($f, $s, "phone" . $i . "jobtype" . $emergencyjobtypeid) && GetFormData($f, $s, "phone" . $i) !== "") {
			$hasemergency=true;
			break;
		} else if ($lockedphones[$i] && GetFormData($f, $s, "phone" . $i . "jobtype" . $emergencyjobtypeid) && $phones[$i]->phone){
			$hasemergency=true;
			break;
		}
	}
	return $hasemergency;
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