<?

function getContactIDs($portaluserid){
	return QuickQueryList("select personid from portalperson where portaluserid = '$portaluserid' order by personid");
}

function getContacts($portaluserid) {
	$contactList = getContactIDs($portaluserid);
	return resequence(DBFindMany("Person", "from person where id in ('" . implode("','", $contactList) . "') and not deleted order by id"), "pkey");
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
function getsetContactFormData($f, $s, $PERSONID, $phones, $emails, $smses, $jobtypes, $locked){
	$lockedphones = $locked['phones'];
	$lockedemails = $locked['emails'];
	$lockedsms = $locked['sms'];
	QuickUpdate("Begin");
	QuickUpdate("delete from contactpref where personid = '" . $PERSONID . "'");
	$values = array();
	foreach($phones as $phone){
		if(!$lockedphones[$phone->sequence]){
			$phone->phone = Phone::parse(GetFormData($f, $s, "phone" . $phone->sequence));
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

function validEmail($email){
		#
	    # RFC822 Email Parser
	    #
	    # By Cal Henderson <cal@iamcal.com>
	    # This code is licensed under a Creative Commons Attribution-ShareAlike 2.5 License
	    # http://creativecommons.org/licenses/by-sa/2.5/
	    #
	    # $Revision: 1.21 $
	    # http://www.iamcal.com/publish/articles/php/parsing_email/
	
	    ##################################################################################
	
	    #
		# Email Parser Start
		#
		
        $qtext = '[^\\x0d\\x22\\x5c\\x80-\\xff]';

        $dtext = '[^\\x0d\\x5b-\\x5d\\x80-\\xff]';

        $atom = '[^\\x00-\\x20\\x22\\x28\\x29\\x2c\\x2e\\x3a-\\x3c'.
            '\\x3e\\x40\\x5b-\\x5d\\x7f-\\xff]+';

        $quoted_pair = '\\x5c[\\x00-\\x7f]';

        $domain_literal = "\\x5b($dtext|$quoted_pair)*\\x5d";

        $quoted_string = "\\x22($qtext|$quoted_pair)*\\x22";

        $domain_ref = $atom;

        $sub_domain = "($domain_ref|$domain_literal)";

        $word = "($atom|$quoted_string)";
		// original code allows a domain to only contain a single sub_domain.  Code has been
		// changed to require 2 domain parts ex.  "example.com"  instead of just "example"
        $domain = "$sub_domain\\x2e$sub_domain(\\x2e$sub_domain)*";

        $local_part = "$word(\\x2e$word)*";

        $addr_spec = "$local_part\\x40$domain";

		#
		# Email Parser Start
		#
		
        if(!preg_match("!^$addr_spec$!", $email)){
        	return false;
        }
        return true;

}
?>