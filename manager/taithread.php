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
	global $threaduser;	
	if ($row[$index] == 1)
		return "School Messenger";
	if ($row[$index] === $threaduser->id)
		return $threaduser->firstname . " " .  $threaduser->lastname;
	else
		return "&nbsp;";
}



$formdata = array();
$formdata["reply"] = array(
	"label" => _L('Reply'),
	"value" => "",
	"validators" => array(),
	"control" => array("TextArea", "rows" => 3, "cols" => 40),
	"helpstep" => 1
);

$buttons = array(submit_button(_L('Reply'),"submit","tick"));

$form = new Form("threadreply",$formdata,null,$buttons);


////////////////////////////////////////////////////////////////////////////////
// Form Data Handling
////////////////////////////////////////////////////////////////////////////////

loadManagerConnectionData();
$custdb = getPooledCustomerConnection($_GET['customerid'],true);
$threaduser = DBFind("User", "from user u inner join tai_thread t on (u.id = t.originatinguserid or u.id = t.recipientuserid) where u.id != 1 and t.id=? ","u",array($_GET['threadid']),$custdb);


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
		$thread = DBFind("Thread", "from tai_thread where id=? ",false,array($_GET['threadid']));

		if ($thread !== null) {
			$message = new Message();
			$message->body = $postdata["reply"];
			$message->senderuserid = 1;
			$message->recipientuserid = $threaduser->id;
			$message->modifiedtimestamp = time();
			$message->threadid = $_GET['threadid'];
			$message->create();
			
			$usermessage = new UserMessage();
			$usermessage->messageid = $message->id;
			$usermessage->userid = $threaduser->id;
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



$TITLE = "Talk About It Thread";
$PAGE = "tai:inbox";


include_once("nav.inc.php");

buttons(icon_button(_L('Back to Inbox'),"arrow_left",null,"taithread.php"));
startWindow(_L('Thread: %s on Customer: %s',$_GET['threadid'],$_GET['customerid']));





$query = "SELECT threadid,senderuserid,recipientuserid,modifiedtimestamp,body FROM `tai_message` m WHERE exists (select * from tai_usermessage um where um.userid=1 and um.isdeleted=0) and threadid=? order by modifiedtimestamp desc";
$messages = QuickQueryMultiRow($query,true,$custdb,array($_GET['threadid']));

$titles = array(
	"senderuserid" => "From",
	"recipientuserid" => "To",
	"body" => "Message");
$formatters = array(
	"senderuserid" => "fmt_user",
	"recipientuserid" => "fmt_user"
);

echo '<table id="taimessages" class="list sortable">';

showTable($messages,$titles,$formatters);

echo '</table>';


echo $form->render();



endWindow();
buttons();
include_once("navbottom.inc.php");
?>
