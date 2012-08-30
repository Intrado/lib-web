<?
require_once("common.inc.php");
require_once("../inc/table.inc.php");
require_once("../obj/User.obj.php");
require_once("../obj/Validator.obj.php");
require_once("../obj/Form.obj.php");
require_once("../obj/FormItem.obj.php");
require_once("../inc/html.inc.php");
require_once("dbmo/tai/Thread.obj.php");
require_once("dbmo/tai/Message.obj.php");
require_once("dbmo/tai/UserMessage.obj.php");


if (!isset($_GET['customerid']) && !isset($_GET['threadid'])) {
	redirect("taiinbox.php");
}


////////////////////////////////////////////////////////////////////////////////
// formatters
////////////////////////////////////////////////////////////////////////////////
function fmt_user($row, $index) {
	global $thread,$threadusers;
	if ($row[$index] == 1)
		return "School Messenger";
	if (isset($threadusers[$row[$index]])) {
		$user = $threadusers[$row[$index]];
		if ($thread->wassentanonymously && $thread->originatinguserid == $row[$index])
			return "Anonymous " . action_link(_L('View User'),"magnifier",null,"alert('ID: {$user->id} Name: {$user->firstname} {$user->lastname}');return false;");
		return $user->firstname . " " .  $user->lastname;
	} else
		return "&nbsp;";
}


function fmt_timestamp($row, $index) {
	return date("Y-m-d G:i:s",$row[$index]);;
}

loadManagerConnectionData();
$custdb = getPooledCustomerConnection($_GET['customerid'],true);

$thread = DBFind("Thread", "from tai_thread where id=?",false,array($_GET['threadid']),$custdb);
if ($thread === null) {
	redirectToReferrer();
}



$formdata = array();
$formdata["reply"] = array(
	"label" => _L('Reply to Originator'),
	"value" => "",
	"validators" => array(
		array("ValRequired")
	),
	"control" => array("TextArea", "rows" => 3, "cols" => 40),
	"helpstep" => 1
);

$windowdescription = "";
switch($thread->threadtype) {
	case "identityreveal":
		$PAGE = "tai:requests";
		$windowdescription = _L('Identity Request on Customer: %s',$_GET['customerid']);
		$backbutton = icon_button(_L('Back'),"	fugue/arrow_180",null,"tairevealrequests.php");
		break;
	case "comment":
		$PAGE = "tai:inbox";
		$windowdescription = _L('Comment on Customer: %s',$_GET['customerid']);
		$backbutton = icon_button(_L('Back'),"	fugue/arrow_180",null,"taiinbox.php");

		break;
	default:
		$PAGE = "tai:inbox";
		$backbutton = icon_button(_L('Back'),"	fugue/arrow_180",null,isset($_SERVER['HTTP_REFERER'])?$_SERVER['HTTP_REFERER']:"taiinbox.php");
		$windowdescription = _L('Thread: %s on Customer: %s',$_GET['threadid'],$_GET['customerid']);
	break;
}

$buttons = array($backbutton,submit_button(_L('Reply To Originator'),"submit","tick"));

$threadusers = DBFindMany("User", "from user u inner join tai_thread t on (u.id = t.originatinguserid or u.id = t.recipientuserid) where u.id != 1 and t.id=? ","u",array($_GET['threadid']),$custdb);


////////////////////////////////////////////////////////////////////////////////
// Form Data Handling
////////////////////////////////////////////////////////////////////////////////


if ($thread->originatinguserid == 1 || $thread->recipientuserid ==1) {
	$form = new Form("threadreply",$formdata,null,$buttons);
	
	//check and handle an ajax request (will exit early)
	//or merge in related post data
	$form->handleRequest();
	
	$datachange = false;
	$errors = false;
	//check for form submission
	if ($button = $form->getSubmit()) {
		//checks for submit and merges in post data
		$ajax = $form->isAjaxSubmit(); //whether or not this requires an ajax response
	
		if ($form->checkForDataChange()) {
			$datachange = true;
		} else if (($errors = $form->validate()) === false) {
			//checks all of the items in this form
			$postdata = $form->getData(); //gets assoc array of all values {name:value,...}
			
			// set global to customer db, restore after this section
			global $_dbcon;
			$savedbcon = $_dbcon;
			$_dbcon = $custdb;
			
			Query("BEGIN");
			
			// Can only reply to messages schoolmessenger is originator or recipient to
			if ($thread->originatinguserid == 1 || $thread->recipientuserid ==1) {
				$message = new Message();
				$message->body = $postdata["reply"];
				$message->senderuserid = 1;
				$message->recipientuserid = $thread->originatinguserid;
				$message->modifiedtimestamp = time();
				$message->threadid = $_GET['threadid'];
				$message->create();
				
				$usermessage = new UserMessage();
				$usermessage->messageid = $message->id;
				$usermessage->userid = $thread->originatinguserid;
				$usermessage->create();
				
				$usermessage = new UserMessage();	
				$usermessage->messageid = $message->id;
				$usermessage->userid = 1;
				$usermessage->isread = 1;
				$usermessage->create();
			}
			
			
			Query("COMMIT");
			
			// restore global db connection
			$_dbcon = $savedbcon;
			if ($ajax)
				$form->sendTo("taithread.php?customerid=" . $_GET['customerid'] . "&threadid=" . $_GET['threadid']);
			else
				redirect("taithread.php?customerid=" . $_GET['customerid'] . "&threadid=" . $_GET['threadid']);
		}
	}
}


$TITLE = "Talk About It Thread";




include_once("nav.inc.php");
startWindow($windowdescription);





$query = "SELECT threadid,senderuserid,recipientuserid,modifiedtimestamp,body FROM `tai_message` m WHERE exists (select * from tai_usermessage um where um.userid=1 and um.isdeleted=0) and threadid=? order by modifiedtimestamp desc";
$messages = QuickQueryMultiRow($query,true,$custdb,array($_GET['threadid']));

$titles = array(
	"senderuserid" => "From",
	"recipientuserid" => "To",
	"body" => "Message",
	"modifiedtimestamp" => "sent");
$formatters = array(
	"senderuserid" => "fmt_user",
	"recipientuserid" => "fmt_user",
	"modifiedtimestamp" => "fmt_timestamp"
);

if ($thread->threadtype == "thread") {
	$topic = QuickQuery("select name from tai_topic where id=?",$custdb,array($thread->topicid));
	echo "<b>Thread Topic:</b>$topic<br/>";
}
echo  "<b>Last Modified Date:</b> " . date("Y-m-d G:i:s",$thread->modifiedtimestamp) . "<hr/>";

echo '<table id="taimessages" class="list sortable">';

showTable($messages,$titles,$formatters);

echo '</table>';
echo "<hr/>" . (isset($form)?$form->render():$backbutton);
endWindow();
include_once("navbottom.inc.php");
?>
