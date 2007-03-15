<?
$SETTINGS = parse_ini_file("inc/settings.ini.php",true);
$IS_COMMSUITE = $SETTINGS['feature']['is_commsuite'];

require_once("inc/db.inc.php");
require_once("inc/DBMappedObject.php");
require_once("inc/DBRelationMap.php");
require_once("inc/ftpfile.inc.php");
require_once("obj/User.obj.php");
require_once("obj/UserRule.obj.php");
require_once("obj/Access.obj.php");
require_once("obj/Person.obj.php");
require_once("obj/Permission.obj.php");
require_once("obj/Rule.obj.php");
require_once("obj/PersonData.obj.php");
require_once("obj/FieldMap.obj.php");
require_once("obj/Phone.obj.php");
require_once("obj/Email.obj.php");
require_once("obj/Address.obj.php");
require_once("obj/Import.obj.php");
require_once("obj/ImportField.obj.php");
require_once("obj/ListEntry.obj.php");
require_once("obj/ImportJob.obj.php");


/*
insert into importfield (importid, mapto, mapfrom) values
(3,'f01',0),
(3,'f02',1),
(3,'user',2),
(3,'p0',3),
(3,'acc',4),
(3,'vf05',5)
*/
/*

field mappings

Person mappings
key = person.pkey
f01-f20 = persondata.f01-f20
p0-p3 = phone.phone with seq=0-3
e0-e1 = email.email with seq=0-1
a1 = address.addr1
a2 = address.addr2
a3 = address.city
a4 = address.state
a5 = address.zip
a6 = address.addressee

User mappings
user = user.login
pass = user.password
f01 = user.firstname
f02 = user.lastname
p0 = user.phone
acc = access.name
vf01-vf20 = userrules with rules "and fXX in ('A','B','C')" where the value is "A|B|C"
code = user.accesscode
pin = user.pin


*/


function wlog ($str) {
	global $logfp, $debug;
	if ($debug) echo $str . "\r\n";
	fwrite($logfp, date("Y-m-d h:i:s") . " - $str\r\n");
}

$logfp = fopen($SETTINGS['feature']['log_dir']."import_log.txt","a") or $logfp = fopen($SETTINGS['feature']['log_dir']."import_log2.txt","a");
if (!$logfp) {
	exit(-1);
}

set_time_limit (0);



if (count($argv) < 2) {
	echo "usage: import.php -import=<importid>";
	wlog("bad usage");
	exit(-1);
}


if (strpos($argv[1], "-import=") !== false) {
	list($dummy,$importid) = explode("=",$argv[1]);
	$importid = DBSafe($importid);
} else {
	wlog("missing parameters");
	exit(-1);
}

if (strpos($argc[2],"-debug") !== false) {
	$debug = true;
} else {
	$debug = false;
}

wlog("start id=$importid");

//get the import from the DB
$import = new Import($importid);

$temp = DBFindMany("ImportField", "from importfield where importid='$importid'");

$importfields = array();
foreach ($temp as $importfield) {
	$importfields[$importfield->mapto] = $importfield;
}

$dotrimoldrecords = $import->updatemethod == "full";
$doinsertnewrecords = $import->updatemethod != "updateonly";
$dosetimportid = ($import->updatemethod == "full") || ($import->ownertype == "user");
$setuserid = NULL;
$setpersontype = "system";
$doupdatepdvalues = true;

if ($import->ownertype == "user") {
	$doupdatepdvalues = false; //no list value fields allowed in user import
	$setuserid = $import->userid;
	if ($import->listid != NULL) {
		//import to list, don't add to address book (aka "manualadd")
		$setpersontype = "upload";
	} else {
		//import to address book
		$setpersontype = "addressbook";
	}
}

$custid = $import->customerid;

$defaultareacode = QuickQuery("select value from setting where customerid='$custid' and name='defaultareacode'");

$timezone = QuickQuery("select timezone from customer where id=$custid");
date_default_timezone_set($timezone);
QuickUpdate("set time_zone='" . $timezone . "'");
$now = QuickQuery("select now()");

//update the status and lastrun info now that timezone is set
$import->status="running";
$import->lastrun = $now;
$import->update(array("status","lastrun"));


if ($SETTINGS['import']['type'] == "ftp")
	$importfile = getImportFileURL($import->customerid,$import->id);
else if ($SETTINGS['import']['type'] == "file")
	$importfile = $SETTINGS['import']['filedir'] . "/" . $import->path;


$fp = fopen($importfile, "r");
if (!$fp) {
	$import->status = "error";
	$import->update(array("status"));
	wlog("No input file: $importfile");
	exit(-1);
}

$imported = 0;
$ignored = 0;
$count = 0;
$anydata = false;
while (($row = fgetcsv($fp,4096)) !== FALSE) {
	$anydata = true;
	//skip blank lines
	if (count($row) == 1 and trim($row[0]) === "")
		continue;
	$count++;
	//try to use mapped key
	if (isset($importfields['key']) && strlen($row[$importfields['key']->mapfrom]) > 0) {
		$key = trim($row[$importfields['key']->mapfrom]);
	} else {
		$key = false;
	}

	if (isset($importfields['user']) &&
		strlen(trim($row[$importfields['user']->mapfrom])) > 0) {
		$username = trim($row[$importfields['user']->mapfrom]);
	} else {
		$username = false;
	}

	if ($key === false && $username === false && $import->ownertype != "user") {
		wlog("ignoring row $count, no key or user found");
		$ignored++;
		continue;
	}


	//handle person stuff
	if ($key !== false || $import->ownertype == "user") {

		$personid = NULL;
		$persondataid = NULL;
		$addressid = NULL;

		$persondata = false;
		$persondatafields = array("personid");
		$address = false;
		$phones = array();
		$emails = array();


		if ($key !== false) {
			//try to find the person in question
			//and all of their data

			$query = "select p.id, pd.id, ph.id, ph.sequence, e.id, e.sequence, a.id, ph.phone, e.email
						from person p
						left join persondata pd on (p.id=pd.personid)
						left join phone ph on (p.id=ph.personid)
						left join email e on (p.id=e.personid)
						left join address a on (p.id=a.personid)
						where p.pkey='" . DBSafe($key) . "'
						and p.customerid='" . $custid . "'
						";

			$result = Query($query);
			while ($personrow = DBGetRow($result)) {
				$personid = $personrow[0];
				$persondataid = $personrow[1];
				$addressid = $personrow[6];

				if ($personrow[3] !== NULL && $personrow[2] !== NULL) {
					$phone = new Phone();
					$phone->id = $personrow[2];
					$phone->personid = $personid;
					$phone->phone = $personrow[7];
					$phone->sequence = $personrow[3];

					$phones[$personrow[3]] = $phone;
				}

				if ($personrow[5] !== NULL && $personrow[4] !== NULL) {
					$email = new Email();
					$email->id = $personrow[4];
					$email->personid = $personid;
					$email->email = $personrow[8];
					$email->sequence = $personrow[5];

					$emails[$personrow[5]] = $email;
				}
			}
		} //end if key !== false

		if ($personid === NULL && !$doinsertnewrecords) {
			wlog("set to updateonly, not importing persond key:$key");
			$ignored++;
			continue;
		}

		if ($personid === NULL) {
			$person = new Person();
			$person->userid = $setuserid;
			$person->deleted = 0;
			$person->pkey = $import->ownertype == "user" ? NULL : $key;
			$person->customerid = $custid;
			$person->type = $setpersontype;
			$person->create();
			$personid = $person->id;

			//should we add this person to a list?
			if ($import->listid != NULL) {
				$le = new ListEntry();
				$le->listid = $import->listid;
				$le->type="A";
				$le->personid = $personid;
				$le->create();
			}
		}

		foreach ($importfields as $to => $fieldmap) {

			//if a row doesnt exist in the import, assume it is an empty string.
			if (!isset($row[$fieldmap->mapfrom]))
				$row[$fieldmap->mapfrom] = "";

			$fieldtype = substr($to,0,1);
			switch ($fieldtype) {
				case "f":
					if (!$persondata) {
						$persondata = new PersonData($persondataid);
						$persondata->personid = $personid;
					}
					$persondatafields[] = $to;
					$persondata->$to = $row[$fieldmap->mapfrom];
					break;
				case "a":
					$part = substr($to,1);

					if (!$address) {
						$address = new Address($addressid);
						$address->personid = $personid;
						$address->addr1 = NULL;
						$address->addr2 = NULL;
						$address->city = NULL;
						$address->state = NULL;
						$address->zip = NULL;
						$address->addressee = NULL;
					}

					$value = $row[$fieldmap->mapfrom] != "" ? $row[$fieldmap->mapfrom] : NULL;
					switch ($part) {
						case 1:
							$address->addr1 = $value;
							break;
						case 2:
							$address->addr2 = $value;
							break;
						case 3:
							$address->city = $value;
							break;
						case 4:
							$address->state = $value;
							break;
						case 5:
							$address->zip = $value;
							break;
						case 6:
							$address->addressee = $value;
							break;
					}

					break;
				case "p":
					$seq = substr($to,1) ;
					$parsedphone = Phone::parse($row[$fieldmap->mapfrom]);
					if ($defaultareacode && strlen($parsedphone) == 7)
						$parsedphone = Phone::parse($defaultareacode . $parsedphone);

					if (strlen($parsedphone) == 10) {
						if (isset($phones[$seq])) {
							$phones[$seq]->phone = $parsedphone;
						} else {
							$phone = new Phone();
							$phone->personid = $personid;
							$phone->sequence = $seq ;
							$phone->phone = $parsedphone;
							$phones[$seq] = $phone;
						}
					} else if (isset($phones[$seq])) {
						if (isset($phones[$seq])) {
							$phones[$seq]->phone = "";
						} else {
							$phone = new Phone();
							$phone->personid = $personid;
							$phone->sequence = $seq ;
							$phone->phone = "";
							$phones[$seq] = $phone;
						}
					}

					if (strlen($row[$fieldmap->mapfrom]) < 10 &&
						strlen($row[$fieldmap->mapfrom]) > 0) {
						wlog("$key - bad phone number " . $row[$fieldmap->mapfrom]);
					}
					break;

				case "e":
					$seq = substr($to,1) ;
					if (strlen($row[$fieldmap->mapfrom]) > 0) {
						if (isset($emails[$seq])) {
							$emails[$seq]->email = $row[$fieldmap->mapfrom];
						} else {
							$email = new Email();
							$email->personid = $personid;
							$email->sequence = $seq;
							$email->email = $row[$fieldmap->mapfrom];
							$emails[$seq] = $email;
						}
					} else if (isset($emails[$seq])) {
						if (isset($emails[$seq])) {
							$emails[$seq]->email = "";
						} else {
							$email = new Email();
							$email->personid = $personid;
							$email->sequence = $seq ;
							$email->email = "";
							$emails[$seq] = $email;
						}
					}

					break;
			}
		}

		//fill in all missing phones with blanks, and save them all

		$maxphoneseq = -1;
		foreach ($phones as $phone)
			$maxphoneseq = max($maxphoneseq,$phone->sequence);
		for ($x = 0; $x <= $maxphoneseq; $x++) {
			if (!isset($phones[$x])) {
				echo "max $maxphoneseq, phone not there, making new one for $personid\n";
				$phone = new Phone();
				$phone->sequence = $x;
				$phone->personid = $personid;
				$phone->phone = "";
				$phones[$x] = $phone;
			}
			$phones[$x]->update();
		}

		//same for email
		$maxemailseq = -1;
		foreach ($emails as $email)
			$maxemailseq  = max($maxemailseq,$email->sequence);
		for ($x = 0; $x <= $maxemailseq; $x++) {
			if (!isset($emails[$x])) {
				$email = new Email();
				$email->sequence = $x;
				$email->personid = $personid;
				$email->email = "";
				$emails[$x] = $email;
			}
			$emails[$x]->update();
		}


		if ($persondata)
			$persondata->update($persondatafields);
		if ($address)
			$address->update();


		if ($dosetimportid) {
			QuickUpdate("update person set importid='$importid', lastimport='$now' where id='$personid'");
		} else {
			QuickUpdate("update person set lastimport='$now' where id='$personid'");
		}
	} //end person stuff

	if ($username !== false) {

		$user = DBFind("User", "from user where login='" . DBSafe($username) . "' and customerid=$custid");
		if (!$user) {
			$user = new User();
			$user->login = $username;
			$user->customerid=$custid;
			$user->enabled = 0;
			$user->email = "";
			$newuser = true;
		} else {
			$newuser = false;
		}

		//update the user fields and access profile
		$setpincode = false;
		$setpassword = false;
		foreach ($importfields as $to => $fieldmap) {

			//first name
			if ($to == FieldMap::getFirstNameField()) {
				$user->firstname = $row[$fieldmap->mapfrom];
			//last name
			} else if ($to == FieldMap::getLastNameField()) {
				$user->lastname = $row[$fieldmap->mapfrom];
			//primary phone
			} else if ($to == "p0") {
				$user->phone = Phone::parse($row[$fieldmap->mapfrom]);
			//primary email
			} else if ($to == "e0") {
				$user->email = $row[$fieldmap->mapfrom];
			//access profile
			} else if ($to == "acc") {
				$access = DBFind("Access", "from access where name='" . DBSafe($row[$fieldmap->mapfrom]) . "' and customerid=$custid");
				if (!$access) {
					$access = new Access();
					$access->name = $row[$fieldmap->mapfrom];
					$access->customerid = $custid;
					$access->moduserid = $import->userid;
					$access->description = "";
					$access->modified = date("Y-m-d H:i:s");
					$access->created = date("Y-m-d H:i:s");
					$access->create();
				}

				$user->accessid = $access->id;
			} else if ($to == "code") {
				$user->accesscode = $row[$fieldmap->mapfrom];
			} else if ($to == "pin") {
				$setpincode = $row[$fieldmap->mapfrom];
			} else if ($to == "pass") {
				$setpassword = $row[$fieldmap->mapfrom];
			}
		}
		//save changes before doing other related imports
		$user->update();

		//set default password to same as user account
		if ($newuser) {
			if ($setpassword !== false)
				$user->setPassword($setpassword);
			if ($setpincode !== false)
				$user->setPincode($setpincode);
		}

		//find any userrules
		$hasuserrules = QuickQuery("select count(*) from userrule where userid=$user->id");
		$newrules = array();

		foreach ($importfields as $to => $fieldmap) {

			//import the userrules for a dataview
			if (substr($to,0,1) == "v") {
				//only import if the user has no rules or the mode is set to full import
				if ($import->updatemethod == "full" || !$hasuserrules) {
					$fieldnum = substr($to,1);
					$rule = new Rule();
					$rule->logical = "and";
					$rule->fieldnum = $fieldnum;
					$rule->op = "in";
					$rule->val = $row[$fieldmap->mapfrom];

					$newrules[] = $rule;
				}
			}
		}

		//see if we need to switch to the new rules
		//TODO compare the rules, but for now just delete and make new ones for each import
		if (count($newrules) > 0) {
			//delete the old ones
			QuickUpdate("delete from rule where id in (select ruleid from userrule where userid=$user->id)");
			QuickUpdate("delete from userrule where userid=$user->id");
			//make new ones
			foreach ($newrules as $rule) {
				$rule->create();
				$userrule = new UserRule();
				$userrule->userid = $user->id;
				$userrule->ruleid = $rule->id;
				$userrule->create();
			}
		}

	}

	if ($imported%1000 == 0) {
		if ($debug) echo date("Y-m-d h:i:s") . " imported so far: $imported\n";
	}

	$imported++;

}//while people to import

fclose($fp);

if (!$anydata) {
	$import->status = "error";
	$import->update(array("status"));
	wlog("Input file was totally empty, refusing to coninue processing: $importfile");
	exit(-1);
}


wlog("imported total: $imported of $count");
if ($debug) echo date("Y-m-d h:i:s") . " imported total: $imported\n";

if ($dotrimoldrecords) {

	$count = QuickUpdate("update person p set p.customerid=customerid * -1 where p.lastimport != '$now' and p.customerid='$custid' and p.importid=$importid");
	wlog("deactivated $count old people records");


	//remove all deactivated people from list additions
	QuickUpdate("update listentry le left join person p on (le.personid=p.id) set le.personid=-1 where le.type='A' and p.customerid < 0");
	QuickUpdate("delete from listentry where personid=-1");

}

if ($doupdatepdvalues) {
	$fieldmaps = DBFindMany("FieldMap","from fieldmap where customerid='$custid'");

	//now update the persondatavalues
	foreach ($fieldmaps as $fieldmap) {

		//ignore fields that were part of the import
		if (!isset($importfields[$fieldmap->fieldnum]))
			continue;

		if ($fieldmap->isOptionEnabled("searchable") &&
			$fieldmap->isOptionEnabled("multisearch")) {

			$field = $importfields[$fieldmap->fieldnum]->mapto;
			if ($debug) echo "updating $field\n";

			$query = "delete from persondatavalues where customerid=$custid and fieldnum='$field'";
			QuickUpdate($query);

			$count = QuickUpdate("insert into persondatavalues (customerid,fieldnum,value,refcount)
			select $custid as customerid,
					'$field' as fieldnum,
					pd.$field as value,
					count(*)
					from persondata pd, person p use index (ownership)
					where p.customerid=$custid and p.id=pd.personid and p.userid is null
					group by value");
			wlog("there are $count $fieldmap->name pd values");
		}
	}
}

$associatedjobs = DBFindMany("ImportJob", "from importjob where importid = '$importid'");
if($associatedjobs){
	foreach($associatedjobs as $job) {
		$jobid = $job->jobid;
		exec("php jobprocess.php $jobid");
	}
}

wlog("done");
$import->status="idle";
$import->update(array("status"));
if ($debug) echo "done\n";

fclose($logfp);

exit(0);
?>
