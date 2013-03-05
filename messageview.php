<? 
require_once("inc/common.inc.php");
require_once("inc/securityhelper.inc.php");
require_once("obj/Message.obj.php");
require_once("obj/MessageGroup.obj.php");
require_once("obj/MessagePart.obj.php");
require_once("obj/Language.obj.php");
require_once("obj/FieldMap.obj.php");
require_once("obj/Voice.obj.php");


?>
<!DOCTYPE html>
<html>
	<body>
<?
if (isset($_GET['id'])) {
	$message = DBFind("Message", "from message where id = ?", false, array($_GET['id']));
	
	if (!$message)
		return;
	
	
	$jobpriority = 3;
	if (isset($_REQUEST["jobtypeid"])) {
		$jobtype = DBFind("JobType","from jobtype where id=?",false,array($_REQUEST["jobtypeid"]));
		if ($jobtype) {
			$jobpriority = $jobtype->systempriority;
		}
	}
	
	$imageparts = DBFindMany('MessagePart', "from messagepart where messageid=? and type='I'", false, array($message->id));
	foreach($imageparts as $part) {
		permitContent($part->imagecontentid);
	}
	
	$email = messagePreviewForPriority($message->id, $jobpriority); // returns commsuite_EmailMessageView object
	$modal->text = $modal->formatEmailHeader($email);
	
	$stationery = new MessageGroup($_GET['stationery']);
	if ($stationery->type == "stationery" &&
		$emailstationery = $stationery->getMessage("email", "html", "en")) {
		
		$emailstationeryparts = DBFindMany("MessagePart", "from messagepart where messageid = ? order by sequence", false, array($emailstationery->id));
			
		echo Message::format($emailstationeryparts);
	}	
}	
?>

	</body>
</html>