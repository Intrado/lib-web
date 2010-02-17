<?

/*
 * the sql is split into 2 halves, before and after this script.
 * ideally, this script would use a transaction around all of the data migration bits
 * to keep it an atomic operation. some schema before to set up new fields, tables
 * and after to clean up.
 * 
 * note: use a customer setting to indicate progress here, to facilitate automatic parallelization
 * so perhaps we could fire up multiple copies without spliting customer ids first
 * maybe lock instance to shard, run 2-4 per shard. each flags setting to indicate it's handling the customer
 * 
 * would also be nice to start solving the upgrade version problem.
 * auto detect what version schema, perhaps from setting, and upgrade from there
 * would also help in dev since there are lots of small changes in between major versions
 */

//require_once("../obj/MessageGroup.obj.php");
//require_once("../obj/Message.obj.php");
//require_once("../obj/MessagePart.obj.php");
//require_once("../obj/AudioFile.obj.php");
require_once("../obj/FieldMap.obj.php");
//require_once("../obj/Rule.obj.php");
//require_once("../obj/ListEntry.obj.php");

//some old or transitional objects used here
require_once("upgrades/db_7-5_oldcode.php");

function upgrade_7_5 ($rev, $shardid, $customerid, $db) {
	
	
	
	switch($rev) {
		default:
		case 0:
			apply_sql("upgrades/db_7-5_pre.sql",$customerid,$db, 1);
			//no code needed, fall through
		case 1:
			// upgrade from rev 1 to rev 2
			echo "|";
			apply_sql("upgrades/db_7-5_pre.sql",$customerid,$db, 2);
	
		case 2:
			// upgrade from rev 2 to rev 3
			echo "|";
			apply_sql("upgrades/db_7-5_pre.sql",$customerid,$db, 3);
			
			//moved from rev1 to fix bug
			$query = "create temporary table jobmessages (
				jobid int not null,
				messageid int not null,
				primary key (jobid,messageid)
			)";
			
			if (!Query($query, $db))
				return false;

			if (!Query("insert ignore into jobmessages select id,emailmessageid from job where emailmessageid is not null", $db))
				return false;
			if (!Query("insert ignore into jobmessages select id,phonemessageid from job where phonemessageid is not null", $db))
				return false;
			if (!Query("insert ignore into jobmessages select id,smsmessageid from job where smsmessageid is not null", $db))
				return false;
			if (!Query("insert ignore into jobmessages select jobid,messageid from joblanguage", $db))
				return false;

			echo ".";

			$messagegroupjobs = array();  //messageid csv -> jobid array
			$jobdata = array(); //jobid->null (set)
			$res = Query("select jobid, group_concat(convert(messageid,char) order by messageid separator ',') as messagelist from jobmessages group by jobid", $db);
			if (!$res)
				return false;
			while ($row = DBGetRow($res)) {
				if (!isset($jobmessages[$row[0]]))
					$jobmessages[$row[0]] = array();
				$messagegroupjobs[$row[1]][] = $row[0];
				$jobids[$row[0]] = null;
			}

			echo ".";
			
			//load all jobs
			$jobs = DBFindMany("Job_7_5_r2","from job",false,false,$db);
			
			echo ".";
			
			//load all joblanguage data
			$query = "select jobid, type, language, messageid from joblanguage";
			$res = Query($query,$db);
			while ($row = DBGetRow($res,true)) {
				$jobs[$row['jobid']]->joblanguages[$row['type']][$row['language']] = $row['messageid'];
			}

			echo "|";
			
			//make the messagegroups
			$count = 0;
			$jobmessagegroup = array();
			$messageoriginals = array();
			foreach ($messagegroupjobs as $messagecsv => $joblist) {
				$job = $jobs[$joblist[0]];
				if (isset($job->messagegroupid) && $job->messagegroupid)
					continue; //skip already converted jobs in case this is re-run on already partially upgraded version

				//name = default message name in order: phone, email,sms
				$defaultmessageid = first($job->phonemessageid,$job->emailmessageid,$job->smsmessageid);
				$msg = new Message_7_5_r2($defaultmessageid);
						
				$mg = new MessageGroup_7_5_r2();
				$mg->name = $msg->name;
				$mg->description = SmartTruncate("Created from $job->name", 50);
				$mg->userid = $job->userid;
				$mg->lastused = $msg->lastused == null ? $job->finishdate : $msg->lastused;
				$mg->modified = $msg->modifydate == null ? date('Y-m-d H:i:s') : $msg->modifydate;
				
				$mg->create();
				
				//some messages may already be associated with another message group
				//in this case we need to clone them.
				$msgids = explode(",",$messagecsv);
				$duplicatemsgids = QuickQueryList("select id from message where messagegroupid != 0 and id in ($messagecsv)",false,$db);
				
				foreach ($msgids as $k => $msgid) {
					if (in_array($msgid,$duplicatemsgids)) {
						$newmsg = Job_7_5_r2::copyMessage($msgid);
						$msgids[$k] = $newmsg->id;
						$messageoriginals[$newmsg->id] = $msgid;
					}
				}
		
				//recreate csv with new ids
				$messagecsv = implode(",",$msgids);
				
				Quickupdate("update message set messagegroupid=$mg->id where id in ($messagecsv)",$db);
	//MISSING message.language
				QuickUpdate("update job set messagegroupid=$mg->id where id in (" . implode(",",$joblist) . ")",$db);
				
				if (++$count % 1000 == 0)
					echo ".";
			}
			
			Query("drop table jobmessages", $db);
			
			
			//non survey orphaned messages
			echo "|";
			
			$query = "create temporary table surveymessages (
				messageid int not null,
				primary key (messageid)
			)";			
			if (!Query($query, $db))
				return false;
			
			if (!Query("insert ignore into surveymessages select machinemessageid from surveyquestionnaire", $db))
				return false;
			if (!Query("insert ignore into surveymessages select emailmessageid from surveyquestionnaire", $db))
				return false;
			if (!Query("insert ignore into surveymessages select intromessageid from surveyquestionnaire", $db))
				return false;
			if (!Query("insert ignore into surveymessages select exitmessageid from surveyquestionnaire", $db))
				return false;
			if (!Query("insert ignore into surveymessages select phonemessageid from surveyquestion", $db))
				return false;
			
			$orphanedmessages = DBFindMany("Message_7_5_r2","from message m where m.messagegroupid is null and not exists (select * from surveymessages sm where sm.messageid=m.id)",false,false,$db);
			$count = 0;
			foreach ($orphanedmessages as $msg) {
				$mg = new MessageGroup_7_5_r2();
				$mg->name = $msg->name;
				$mg->description = $msg->description;
				$mg->userid = $msg->userid;
				$mg->lastused = $msg->lastused;
				$mg->modified = $msg->modifydate == null ? date('Y-m-d H:i:s') : $msg->modifydate;
				
				$mg->create();
				
				$msg->messagegroupid = $mg->id;
				$msg->update(array("messagegroupid"));
				
				if (++$count % 1000 == 0)
					echo ".";
			}
			
			//drop temp table
			Query("drop table surveymessages", $db);
			
			//HANDLE copied messages losing joblangue reference
			//$messageoriginals[$newmsg->id] = $msgid;
			foreach ($messageoriginals as $newmsgid => $origmsgid) {
				QuickUpdate("update message set originalid=$origmsgid where id=$newmsgid");
			}
			
			
			$schoolfieldnum = QuickQuery("select fieldnum from fieldmap where options like '%school%'");
			if ($schoolfieldnum) {
				$num = substr($schoolfieldnum,1) + 0;
				
				//create orgs for each school field
				if ($schoolfieldnum[0] == "g")
					$query = "select distinct value from groupdata where fieldnum=$num";
				else
					$query = "select distinct $schoolfieldnum from person";
				QuickUpdate("insert ignore into organization (orgkey) $query");

				//create person associations
				if ($schoolfieldnum[0] == "g") {
					$query = "insert into personassociation (personid,type,organizationid)
					select g.personid,'organization',o.id from groupdata g inner join organization o on (o.orgkey=g.value and g.fieldnum=$num)
					group by g.personid, o.id";
				} else {
					$query = "insert into personassociation (personid,type,organizationid)
					select p.id,'organization',o.id from person p inner join organization o on (o.orgkey=p.$schoolfieldnum)
					group by p.id, o.id";
				}
				QuickUpdate($query);
				
				//delete f field values or gfield group entries
				if ($schoolfieldnum[0] == "g") {
					$query = "delete from groupdata where fieldnum=$num";
				} else {
					$query = "update person set $schoolfieldnum = null";					
				}
				QuickUpdate($query);
				
				QuickUpdate("update fieldmap set fieldnum='o01' where fieldnum='$schoolfieldnum'"); //TODO delete this fieldmap instead?
				
				
				//create orgs for rules, in case rules are set up for nonexisting schools
				//then can merge broken orgs later in ui
				
				$rulevals = QuickQueryList("select val from rule where fieldnum='$schoolfieldnum'");
				$orgs = array();
				foreach ($rulevals as $ruleval) {
					foreach (explode("|",$ruleval) as $org) {
						$orgs[$org] = true;
					}
				}
				if (count($orgs) > 0) {
					//add to list of orgs
					$query = "insert ignore into organization (orgkey) values " . repeatWithSeparator("(?)",",",count($orgs));
					QuickUpdate($query,$db, array_keys($orgs));				
				}
				
				//load all org ids
				$orgs = QuickQueryList("select orgkey,id from organization",true);

				//create userassociations
				$query = "select ua.userid,r.val from userassociation ua inner join rule r on (r.id=ua.ruleid) where r.fieldnum='$schoolfieldnum'";
				$userorgs = array();
				foreach (QuickQueryList($query,true) as $userid => $ruleval) {
					foreach (explode("|",$ruleval) as $org) {
						$userorgs[] = "($userid,'organization'," . $orgs[$org] . ")"; 
					}
				}
				if (count($userorgs) > 0) {
					$query = "insert into userassociation (userid,type,organizationid) values " . implode(",",$userorgs);
					QuickUpdate($query);
				}
				//delete old rule user associations
				QuickUpdate("delete r, ua from rule r inner join userassociation ua on (ua.ruleid=r.id and r.fieldnum='$schoolfieldnum')");
				
				
				//create list entries for orgs
				$query = "select le.listid, r.val from listentry le inner join rule r on (r.id=le.ruleid) where r.fieldnum='$schoolfieldnum'";
				$listorgs = array();
				foreach (QuickQueryList($query,true) as $listid => $ruleval) {
					foreach (explode("|",$ruleval) as $org) {
						$listorgs[] = "($listid,'organization'," . $orgs[$org] . ")"; 
					}
				}
				if (count($listorgs) > 0) {
					$query = "insert into listentry (listid,type,organizationid) values " . implode(",",$listorgs);
					QuickUpdate($query);
				}
				//delete old rule listentries
				QuickUpdate("delete r, le from rule r inner join listentry le on (le.ruleid=r.id and r.fieldnum='$schoolfieldnum')");
			} //end if schoolfieldnum
			
			
			
			apply_sql("upgrades/db_7-5_post.sql",$customerid,$db, 3);
		case 3:
			// upgrade from rev 3 to rev 4
			echo "|";
			apply_sql("upgrades/db_7-5_pre.sql",$customerid,$db, 4);
			
			$f03 = DBFind("FieldMap","from fieldmap where fieldnum='f03'",false,false,$db);
			$f03->updatePersonDataValues();
		case 4:
			// upgrade from rev 4 to rev 5
			echo "|";
			apply_sql("upgrades/db_7-5_pre.sql",$customerid,$db, 5);
			
		case 5:
			// upgrade from rev 5 to rev 6
			echo "|";
			apply_sql("upgrades/db_7-5_pre.sql",$customerid,$db, 6);
			
		case 6:
			// upgrade from rev 6 to rev 7
			echo "|";
			apply_sql("upgrades/db_7-5_pre.sql",$customerid,$db, 7);
	}
	
	//do these always
	//apply_sql("../db/targetedmessages.sql",$customerid,$db);
	apply_sql("../db/update_SMAdmin_access.sql",$customerid,$db);
	
	
	return true;
}


?>
