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

require_once("../obj/Job.obj.php");
require_once("../obj/JobLanguage.obj.php");
require_once("../obj/MessageGroup.obj.php");
require_once("../obj/Message.obj.php");
require_once("../obj/MessagePart.obj.php");
require_once("../obj/AudioFile.obj.php");



function upgrade_7_5 ($rev, $shardid, $customerid, $db) {
	
	apply_sql("upgrades/db_7-5_pre.sql",$customerid,$db, $rev);
	
	switch($rev) {
		default:
		case 0:
			//no code needed, fall through		
		case 1:
			// upgrade from rev1 to rev 2
			echo "|";
	
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
			$jobs = DBFindMany("Job","from job",false,false,$db);

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
			foreach ($messagegroupjobs as $messagecsv => $joblist) {
				$job = $jobs[$joblist[0]];

				//name = default message name in order: phone, email,sms
				$defaultmessageid = first($job->phonemessageid,$job->emailmessageid,$job->smsmessageid);
				$msg = new Message($defaultmessageid);
						
				$mg = new MessageGroup();
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
						$newmsg = Job::copyMessage($msgid);
						$msgids[$k] = $newmsg->id;
					}
				}
				
				//recreate csv with new ids
				$messagecsv = implode(",",$msgids);
				
				Quickupdate("update message set messagegroupid=$mg->id where id in ($messagecsv)",$db);
				QuickUpdate("update job set messagegroupid=$mg->id where id in (" . implode(",",$joblist) . ")",$db);
				
				if (++$count % 1000 == 0)
					echo ".";
			}
			
			Query("drop table jobmessages", $db);
		
	}
	
	return true;
}


?>
