<? 
require_once("inc/common.inc.php");
require_once("inc/securityhelper.inc.php");
require_once("obj/Message.obj.php");
require_once("obj/MessageGroup.obj.php");
require_once("obj/MessagePart.obj.php");
require_once("obj/Language.obj.php");
require_once("obj/FieldMap.obj.php");
require_once("obj/Voice.obj.php");


require_once("inc/appserver.inc.php");

function displayMessage() {
	if (!isset($_GET['messageid']) || !userCanSee("message", $_GET['messageid'])) {
		error_log("Id is not set for message view");
		return;
	}
	
	$message = DBFind("Message", "from message where id = ?", false, array($_GET['messageid']));
	if (!$message) {
		error_log("message is not set for message view" . json_encode($message));
		return;
	}
	

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
	echo $email->emailbody;
}


?>
<!DOCTYPE html>
<html>
    <script type="text/javascript" src="script/jquery.1.7.2.min.js"></script>
    <script type="text/javascript">
   		$().ready(function() {
   			if (typeof(parent.messagePrevewLoaded) != 'undefined') 
   	   			parent.messagePrevewLoaded($('body'))
    	});
	</script>
	<body>
<?
	displayMessage();
?>

	</body>
</html>