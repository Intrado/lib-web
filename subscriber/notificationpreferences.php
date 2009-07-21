<?
require_once("common.inc.php");
require_once("../inc/html.inc.php");
require_once("../inc/table.inc.php");
require_once("../obj/Phone.obj.php");
require_once("../obj/Email.obj.php");
require_once("../obj/Sms.obj.php");
require_once("../obj/SubscriberPending.obj.php");
require_once("../obj/JobType.obj.php");
require_once("subscriberutils.inc.php");

$STATUS_ACTIVE = "ACTIVE";
$STATUS_PENDING = "PENDING";

class Destination {
	var $id; // sequence for phone, email, sms ------ subscriberpending id
	var $tablename;
	var $name;
	var $type; // phone, email, sms
	var $status; // active or pending
	var $nodelete;
}

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

$pid = $_SESSION['personid'];

if (isset($_GET['delete']) && isset($_GET['tbl'])) {
	$seq = $_GET['delete'] +0;
	$tablename = $_GET['tbl'];
	if ($tablename == 'subscriberpending') {
		QuickUpdate("delete from subscriberpending where id=?", false, array($seq));
	} else if ($tablename == 'phone' ||
		$tablename == 'email' ||
		$tablename == 'sms') {
	 	$query = "update ".$tablename." set ".$tablename."='' where personid=? and sequence=?";
		QuickUpdate($query, false, array($pid, $seq));
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
	$dest->id = $email->sequence;
	$dest->tablename = "email";
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
	$dest->id = $phone->sequence;
	$dest->tablename = "phone";
	$dest->name = Phone::format($phone->phone);
	$dest->type = _L("Phone");
	$dest->status = $STATUS_ACTIVE;
	$dest->nodelete = false;
	$destinations[] = $dest;
}
foreach ($smsList as $sms) {
	if ($sms->sms == '') continue;
	$dest = new Destination();
	$dest->id = $sms->sequence;
	$dest->tablename = "sms";
	$dest->name = Phone::format($sms->sms);
	$dest->type = _L("Text");
	$dest->status = $STATUS_ACTIVE;
	$dest->nodelete = false;
	$destinations[] = $dest;
}
foreach ($pendingList as $p) {
	$dest = new Destination();
	$dest->id = $p->id;
	$dest->tablename = "subscriberpending";
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
			"name" => _L("Contact Information"),
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
	
	$action_links = action_link(_L("Delete"), "cross", "notificationpreferences.php?delete=$obj->id&tbl=$obj->tablename", "return confirmDelete();");
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

// NOTE: what if email sequence0 is deleted? could have set up account, added email, changed account to use email1 then removed e0
// OK because e0 is never deleted, it is only set to empty email field
$values = QuickQueryList("select jobtypeid from contactpref where personid=? and type='email' and sequence=0 and enabled=1", false, false, array($pid));

$formdata["jobtypes"] = array(
	"label" => _L(""),
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
		// TODO be sure emaillist has e0 on it...
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
// TODO need to insert enabled=0 contactpref for those unselected jobtypes, or uses admin default jobtypepref
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

startWindow(_L('Interests'));
echo '<table cellpadding="3"><tr><td>&nbsp;&nbsp;<img src="img/bug_lightbulb.gif" >&nbsp;&nbsp;' . _L("In addition to Emergency notifications, I would like to receive the following types of announcements:") . '</td></tr></table>';
echo $form->render();
showObjects($destinations, $titles, array("name"=>"fmt_name", "status"=>"fmt_status", "action" => "fmt_actions"));

// find remaining phone/email/sms available (some already active and pending)
$available = findAvailableDestinationTypes();
if (count($available) > 0)
	buttons(icon_button("Add More",null,null,"destinationwizard.php"));
else {
?>
<div style="margin: 5px;">
	<img src="img/bug_lightbulb.gif" > All available contacts have been added.  Delete one of the above contacts before you add more.
</div>
<?
}

endWindow();

require_once("navbottom.inc.php");
?>