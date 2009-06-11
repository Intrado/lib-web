<?
require_once("common.inc.php");
require_once("../inc/html.inc.php");
require_once("../inc/table.inc.php");
require_once("../obj/Phone.obj.php");
require_once("../obj/Email.obj.php");
require_once("../obj/Sms.obj.php");
require_once("../obj/SubscriberPending.obj.php");
require_once("../obj/JobType.obj.php");

$STATUS_ACTIVE = "ACTIVE";
$STATUS_PENDING = "PENDING";

class Destination {
	var $id;
	var $name;
	var $type; // phone, email, sms
	var $status; // active or pending
	var $nodelete;
}

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

$pid = $_SESSION['personid'];

if (isset($_GET['delete'])) {
	$id = $_GET['delete'];
	$temp = substr($id, 0, 7);
	if ($temp == 'pending') {
		$temp = substr($id, 7);
		QuickUpdate("delete from subscriberpending where id=?", false, array($temp));
	} else {
		$sequence = substr($id, strlen($id)-1);
		$type = substr($id, 0, strlen($id)-1);
	 
		QuickUpdate("update ".$type." set ".$type."='' where personid=? and sequence=?", false, array($pid, $sequence));
	}
	redirect();
}

$subscriberid = $_SESSION['subscriberid'];
$pendingList = DBFindMany("SubscriberPending", "from subscriberpending where subscriberid=?", false, array($subscriberid));

$phoneList = DBFindMany("Phone", "from phone where personid=?", false, array($pid));
$emailList = DBFindMany("Email", "from email where personid=?", false, array($pid));
$smsList = DBFindMany("Sms", "from sms where personid=?", false, array($pid));


$destinations = array();

foreach ($emailList as $email) {
	if ($email->email == '') continue;
	$dest = new Destination();
	$dest->id = 'email'.$email->sequence;
	$dest->name = $email->email;
	$dest->type = _L("Email");
	$dest->status = $STATUS_ACTIVE;
	if ($_SESSION['subscriber.username'] == $email->email)
		$dest->nodelete = true;
	else
		$dest->nodelete = false;
	$destinations[] = $dest;
}
foreach ($phoneList as $phone) {
	if ($phone->phone == '') continue;
	$dest = new Destination();
	$dest->id = 'phone'.$phone->sequence;
	$dest->name = Phone::format($phone->phone);
	$dest->type = _L("Phone");
	$dest->status = $STATUS_ACTIVE;
	$dest->nodelete = false;
	$destinations[] = $dest;
}
foreach ($smsList as $sms) {
	if ($sms->sms == '') continue;
	$dest = new Destination();
	$dest->id = 'sms'.$sms->sequence;
	$dest->name = Phone::format($sms->sms);
	$dest->type = _L("Text");
	$dest->status = $STATUS_ACTIVE;
	$dest->nodelete = false;
	$destinations[] = $dest;
}
foreach ($pendingList as $p) {
	$dest = new Destination();
	$dest->id = 'pending'.$p->id;
	if ($p->type == 'phone') {
		$dest->name = Phone::format($p->value);
		$dest->type = _L("Phone");
	} else if ($p->type == 'email') {
		$dest->name = $p->value;
		$dest->type = _L("Email");
	} else if ($p->type == 'sms') {
		$dest->name = Phone::format($p->value);
		$dest->type = _L("Text");
	} else {
		error_log("bad subscriberpending record, id ".$p->id);
	}
	$dest->status = $STATUS_PENDING;
	$dest->nodelete = false;
	$destinations[] = $dest;
}

$titles = array(
			"name" => _L("Destination"),
			"type" => _L("Type"),
			"status" => _L("Status"),
			"action" => _L("Actions")
			);

function fmt_name ($obj, $name) {
	global $STATUS_PENDING;
	if ($obj->status == $STATUS_PENDING)
		return "<i>".$obj->name."</i>";
	else return $obj->name;
}

function fmt_status ($obj, $name) {
	global $STATUS_PENDING;
	if ($obj->status == $STATUS_PENDING)
		return "<b>"._L("Pending")."</b>";
	else return _L("Active");
}

function fmt_actions ($obj, $name) {
	global $STATUS_PENDING;
	if ($obj->nodelete)
		return _L("Account Email cannot be removed");
	
	$action_links = action_link(_L("Delete"), "cross", "notificationpreferences?delete=$obj->id", "return confirmDelete();");
	if ($obj->status == $STATUS_PENDING)
		$action_links .= '&nbsp|&nbsp' . action_link(_L("Activation Info"), "pencil", "viewpending.php?id=$obj->id");		
	return $action_links;
}

$formdata = array();

// remove survey if not supported
$survey = "";
if (!getSystemSetting("_hassurvey","0"))
	$survey = " and issurvey=0";
$jobtypes = DBFindMany("JobType", "from jobtype where systempriority != 1 and deleted = 0".$survey);
$jtvalues = array();
foreach ($jobtypes as $jt) {
	$jtvalues[$jt->id] = $jt->name . " (" . $jt->info . ")";
}

// TODO what if email sequence0 is deleted? could have set up account, added email, changed account to use email1 then removed e0
$values = QuickQueryList("select jobtypeid from contactpref where personid=? and type='email' and sequence=0 and enabled=1", false, false, array($pid));

$formdata["jobtypes"] = array(
	"label" => _L("Communication Interests"),
	"value" => $values,
	"validators" => array(),
	"control" => array("MultiCheckbox","values" => $jtvalues),
	"helpstep" => 1
);


$buttons = array(submit_button(_L("Save"),"submit","tick"),
                icon_button(_L("Cancel"),"cross",null,"notificationpreferences.php"));
                
$form = new Form("notifyprefs",$formdata,null,$buttons);
$form->ajaxsubmit = true;

//check and handle an ajax request (will exit early)
//or merge in related post data
$form->handleRequest();

$datachange = false;

//check for form submission
if ($button = $form->getSubmit()) { //checks for submit and merges in post data
    $ajax = $form->isAjaxSubmit(); //whether or not this requires an ajax response    
    
    if ($form->checkForDataChange()) {
        $datachange = true;
    } else if (($errors = $form->validate()) === false) { //checks all of the items in this form
        $postdata = $form->getData(); //gets assoc array of all values {name:value,...}
            
        $emergencyjtid = QuickQueryList("select id from jobtype where systempriority = 1 and deleted = 0");
        
        //save data here
        
		// new contactpref rows
		$values = array();
		
		foreach ($phoneList as $phone) {
			if ($phone->phone == '') continue;
			foreach ($postdata["jobtypes"] as $jtid) {
				$values[] = "(" . $pid . "," . $jtid . ",'phone'," . $phone->sequence . ", 1)";
			}
			foreach ($emergencyjtid as $jtid) {
				$values[] = "(" . $pid . "," . $jtid . ",'phone'," . $phone->sequence . ", 1)";
			}
		}
		foreach ($emailList as $email) {
			// email sequence 0 is special case, must always set because we read from it to load initial values
			if ($email->sequence != 0 && $email->email == '') continue;
			foreach ($postdata["jobtypes"] as $jtid) {
				$values[] = "(" . $pid . "," . $jtid . ",'email'," . $email->sequence . ", 1)";
			}
			foreach ($emergencyjtid as $jtid) {
				$values[] = "(" . $pid . "," . $jtid . ",'email'," . $email->sequence . ", 1)";
			}
		}
		foreach ($smsList as $sms) {
			if ($sms->sms == '') continue;
			foreach ($postdata["jobtypes"] as $jtid) {
				$values[] = "(" . $pid . "," . $jtid . ",'sms'," . $sms->sequence . ", 1)";
			}
			foreach ($emergencyjtid as $jtid) {
				$values[] = "(" . $pid . "," . $jtid . ",'sms'," . $sms->sequence . ", 1)";
			}	
		}
// TODO may need to insert enabled=0 contactpref for those unselected jobtypes, or uses admin default jobtypepref
		QuickUpdate("Begin");
		QuickUpdate("delete from contactpref where personid=?", false, array($pid));
		if (count($values))
			QuickUpdate("insert into contactpref (personid, jobtypeid, type, sequence, enabled)
							values " . implode(",",$values));
        QuickUpdate("Commit");

        if ($ajax)
            $form->sendTo("notificationpreferences.php");
        else
            redirect("notificationpreferences.php");
    }
}


////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$PAGE = "contacts:notificationprefs";
$TITLE = _L("Notification Preferences");

require_once("nav.inc.php");

startWindow(_L('Destinations'));
echo '<table cellpadding="3"><tr><td>&nbsp;&nbsp;<img src="img/bug_lightbulb.gif" >&nbsp;&nbsp;' . _L("In addition to Emergency, I would like to receive information about the following:") . '</td></tr></table>';
echo $form->render();
showObjects($destinations, $titles, array("name"=>"fmt_name", "status"=>"fmt_status", "action" => "fmt_actions"));

// TODO if room for another phone/email/sms
	buttons(icon_button("Add Another",null,null,"destinationwizard.php"));

endWindow();
require_once("navbottom.inc.php");
?>