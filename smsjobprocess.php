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

$starttime = time();

//while (time() - $starttime < 60) {
while (true) {
	if ($smsjobid = QuickQuery("select id from smsjob where status='queued' order by id desc limit 1")) {
		echo date("r") . "sending job $smsjobid\n";
		sendSmsJob($smsjobid);
	} else {
		echo date("r") . " sleeping\n";
		sleep(1);
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
