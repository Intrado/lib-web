<?
$SETTINGS = parse_ini_file("inc/settings.ini.php",true);
$IS_COMMSUITE = $SETTINGS['feature']['is_commsuite'];

require_once("inc/db.inc.php");
require_once("inc/DBMappedObject.php");
require_once("inc/DBRelationMap.php");
require_once("inc/utils.inc.php");
require_once("inc/date.inc.php");
require_once("obj/User.obj.php");
require_once("obj/Access.obj.php");
require_once("obj/Permission.obj.php");

require_once("obj/SmsJob.obj.php");

require_once("inc/nusoap.php");
require_once("inc/instaconnect.php");


set_time_limit(0);


//auth server connection info
$authdbhost = "10.25.25.56";
$authdbuser = "root";
$authdbpass = "";
$authdbdb = "authserver";

$authdb = mysql_connect($authdbhost, $authdbuser, $authdbpass, true);
mysql_select_db($authdbdb, $authdb);

//get a list of all shards
//and get a connection to each shard
$query = "select id, name, description, dbhost, dbusername, dbpassword from shard";
$shards = array();
$res = Query($query,$authdb);
while ($row = DBGetRow($res)) {
	echo "connecting to " . $row[1] . "\n";
	$shards[$row[0]] = mysql_connect($row[3], $row[4], $row[5], true);
}


$starttime = time();

//while (time() - $starttime < 60) {
while (true) {

	$somejobs = false;
	foreach ($shards as $shardid => $sharddb) {
		//get a list of customer dbs
		$customers = QuickQueryList("select id,shardid from customer",true,$authdb);

		//go through each customer and change the global DB to the shard and check for sms jobs
		foreach ($customers as $customerid => $shardid) {
			$_dbcon = $shards[$shardid];
			mysql_select_db("c_" . $customerid, $_dbcon);

			if ($smsjobid = QuickQuery("select id from smsjob where status='queued' order by id desc limit 1")) {
				echo date("r") . "sending smsjob. shard: $shardid, customer: $customerid, smsjobid: $smsjobid\n";
				sendSmsJob($smsjobid);
				sleep(5);
				$somejobs = true;
			}
		}
	}

	if (!$somejobs) {
		echo date("r") . " sleeping\n";
		sleep(5);
	}
}

function sendSmsJob($smsjobid) {

	if (!QuickUpdate("update smsjob set status='sent' where status='queued' and id=$smsjobid"))
		return;//already sent!

	$smsjob = new SmsJob($smsjobid);
	$user = new User($smsjob->userid);

	//load unique phone numbers for this smsjob
	$query = "select distinct s.phone from smsmsg s where s.smsjobid=$smsjobid";
	$phones = QuickQueryList($query);

	foreach($phones as $index => $value) {
		if (strlen($value) != 10 ||
			$value[0] < 2 ||
			$value[3] < 2)
			unset($phones[$index]);
	}
	$phones = array_values($phones);

	var_dump($phones);

	if (count($phones) > 0) {
		$connectObj = new instaconnect();
		$res = $connectObj->Send_Bulk_SMS($phones, $smsjob->txt);
		var_dump($res);
		if ($res['responsecode'] != 1) {
			echo date("r") . "Problem sending SMS!!! " . $res['responsetext'];
//			$smsjob->status="error";
//			$smsjob->update();
			return;
		} else {
			echo date("r") . " job $smsjobid sent successfully\n";
		}
	} else {
		echo date("r") . " job $smsjobid was empty\n";
	}
}

?>
