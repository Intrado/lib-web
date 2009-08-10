<?
require_once("common.inc.php");
require_once("../inc/html.inc.php");
require_once("../inc/table.inc.php");
require_once("../obj/Person.obj.php");
require_once("../obj/Phone.obj.php");
require_once("../obj/Email.obj.php");
require_once("../obj/Sms.obj.php");
require_once("../obj/SubscriberPending.obj.php");
require_once("../obj/JobType.obj.php");
require_once("../obj/FieldMap.obj.php");
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

$person = new Person($_SESSION['personid']);

$firstnameField = FieldMap::getFirstNameField();
$lastnameField = FieldMap::getLastNameField();
$languageField = FieldMap::getLanguageField();
$subscribeFields = FieldMap::getSubscribeMapNames();

$subscribeFieldValues = array();
foreach ($subscribeFields as $fieldnum => $name) {
	if ('f' == substr($fieldnum, 0, 1)) {
		$subscribeFieldValues[$fieldnum] = QuickQueryList("select value, value from persondatavalues where fieldnum=? and editlock=1", true, false, array($fieldnum));
	} else {
		$gfield = substr($fieldnum, 1, 3);
		$subscribeFieldValues[$fieldnum] = QuickQueryList("select value, value from groupdata where fieldnum=? and personid=0 and importid=0", true, false, array($gfield));
	}
}

$fieldmaps = DBFindMany("FieldMap", "from fieldmap where options like '%subscribe%' order by fieldnum");

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
	$dest->type = _L("SMS Text");
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
		$dest->type = _L("SMS Text");
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
$values = QuickQueryList("select cp.jobtypeid from contactpref cp join jobtype jt on (cp.jobtypeid=jt.id) where cp.personid=? and cp.type='email' and cp.sequence=0 and cp.enabled=1 and jt.systempriority != 1", false, false, array($pid));

$formdata["jobtypes"] = array(
	"label" => "",
	"value" => $values,
	"validators" => array(
		array("ValInArray", 'values'=>array_keys($jtvalues))
	),
	"control" => array("MultiCheckbox","values" => $jtvalues),
	"helpstep" => 1
);

foreach ($fieldmaps as $fieldmap) {
	$fieldnum = $fieldmap->fieldnum;
	if ($fieldnum == 'f01' || $fieldnum == 'f02')
		continue; // first and lastname set on account page
		
	if ('f' == substr($fieldnum, 0, 1)) {
		if ($fieldmap->isOptionEnabled("static")) {
			// static
			
			if ($fieldmap->isOptionEnabled("text")) {
				// static text
				
			} else {
				// static multi, subscriber must select one
				
				if ($fieldnum == $languageField) {
				
					// map locale to customer language
					$value = "en_US";
					if ($person->$fieldnum == "Spanish")
						$value = "es_US";
				
					$formdata['locale'] = array (
   	    				"label" => _L("Language"),
       					"value" => $value,
       					"validators" => array(
       						array("ValRequired"),
       						array("ValInArray", 'values'=>array_keys($LOCALES))
       					),
       					"control" => array("RadioButton","values" => $LOCALES),
       					"helpstep" => 1
					);
				
				} else {
					$values = QuickQueryList("select value, value from persondatavalues where fieldnum=? and editlock=1", true, false, array($fieldnum));
					if (count($values) > 0) {
						$v = $person->$fieldnum;
						if (count($values) == 1) {
							$a = array_values($values);
							$v = $a[0];
						}
						$formdata[$fieldnum] = array (
    	    				"label" => $fieldmap->name,
        					"value" => $v,
        					"validators" => array(
        						array("ValRequired"),
        						array("ValInArray", 'values'=>array_keys($values))
        					),
        					"control" => array("RadioButton","values" => $values),
        					"helpstep" => 1
						);
					}
				}
			}
		} else {
			// dynamic
			
			if ($fieldmap->isOptionEnabled("text")) {
				// dynamic text

				$max = 255;
				if ($fieldnum == $firstnameField || $fieldnum == $lastnameField)
					$max = 50;
				
				$formdata[$fieldnum] = array (
        			"label" => $fieldmap->name,
        			"value" => $person->$fieldnum,
        			"validators" => array(
	            		array("ValRequired"),
            			array("ValLength","min" => 1,"max" => $max)
        			),
        			"control" => array("TextField","maxlength" => $max),
        			"helpstep" => 1
    			);
			} else {
				// dynamic multi, subscriber must select one (data from imports)
			
				$values = QuickQueryList("select value, value from persondatavalues where fieldnum=? and editlock=0", true, false, array($fieldnum));
				if (count($values) > 0)
					$formdata[$fieldnum] = array (
    	    			"label" => $fieldmap->name,
        				"value" => $person->$fieldnum,
        				"validators" => array(
        					array("ValRequired"),
        					array("ValInArray", 'values'=>array_keys($values))
        				),
        				"control" => array("RadioButton","values" => $values),
        				"helpstep" => 1
					);
			}
		}
	} else { // Gfield
		if ($fieldmap->isOptionEnabled("static")) {
				// static multi, subscriber must select one
				
				$values = QuickQueryList("select value, value from persondatavalues where fieldnum=? and editlock=1", true, false, array($fieldnum));
		} else {
				// dynamic multi, subscriber must select one (data from imports)
			
				$values = QuickQueryList("select value, value from persondatavalues where fieldnum=? and editlock=0", true, false, array($fieldnum));
		}
		$gfield = substr($fieldnum, 1, 3);
		$arr = QuickQueryList("select value, value from groupdata where personid=? and fieldnum=?", false, false, array($person->id, $gfield));
				if (count($values) > 0)
					$formdata[$fieldnum] = array (
    	    			"label" => _L($fieldmap->name),
        				"value" => $arr,
        				"validators" => array(
        					array("ValInArray", 'values'=>array_keys($values))
        				),
        				"control" => array("MultiCheckbox","values" => $values),
        				"helpstep" => 1
					);
	}
}


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
        $nonemergencyjtid = QuickQueryList("select id from jobtype where systempriority != 1 and deleted = 0");
        
        //save data here

		// new contactpref rows
		$query = "insert into contactpref (personid, jobtypeid, type, sequence, enabled) values ";
		$values = array();
		
		foreach ($phoneList as $phone) {
			if ($phone->phone == '') continue;
			// add opt-in for these job types
			foreach ($postdata["jobtypes"] as $jtid) {
				$query .= "(?, ?, 'phone', ?, 1),";
				$values[] = $pid;
				$values[] = $jtid;
				$values[] = $phone->sequence;
			}
			// always opt-in for emergency job types
			foreach ($emergencyjtid as $jtid) {
				$query .= "(?, ?, 'phone', ?, 1),";
				$values[] = $pid;
				$values[] = $jtid;
				$values[] = $phone->sequence;
			}
			// add opt-out for these job types
			foreach ($nonemergencyjtid as $jtid) {
				if (in_array($jtid, $postdata['jobtypes'])) continue;
				$query .= "(?, ?, 'phone', ?, 0),";
				$values[] = $pid;
				$values[] = $jtid;
				$values[] = $phone->sequence;
			}
		}
		// TODO be sure emaillist has e0 on it...
		foreach ($emailList as $email) {
			// email sequence 0 is special case, must always set because we read from it to load initial values
			if ($email->sequence != 0 && $email->email == '') continue;
			foreach ($postdata["jobtypes"] as $jtid) {
				$query .= "(?, ?, 'email', ?, 1),";
				$values[] = $pid;
				$values[] = $jtid;
				$values[] = $email->sequence;
			}
			foreach ($emergencyjtid as $jtid) {
				$query .= "(?, ?, 'email', ?, 1),";
				$values[] = $pid;
				$values[] = $jtid;
				$values[] = $email->sequence;
			}
			// add opt-out for these job types
			foreach ($nonemergencyjtid as $jtid) {
				if (in_array($jtid, $postdata['jobtypes'])) continue;
				$query .= "(?, ?, 'email', ?, 0),";
				$values[] = $pid;
				$values[] = $jtid;
				$values[] = $email->sequence;
			}
		}
		foreach ($smsList as $sms) {
			if ($sms->sms == '') continue;
			foreach ($postdata["jobtypes"] as $jtid) {
				$query .= "(?, ?, 'sms', ?, 1),";
				$values[] = $pid;
				$values[] = $jtid;
				$values[] = $sms->sequence;
			}
			foreach ($emergencyjtid as $jtid) {
				$query .= "(?, ?, 'sms', ?, 1),";
				$values[] = $pid;
				$values[] = $jtid;
				$values[] = $sms->sequence;
			}	
			// add opt-out for these job types
			foreach ($nonemergencyjtid as $jtid) {
				if (in_array($jtid, $postdata['jobtypes'])) continue;
				$query .= "(?, ?, 'sms', ?, 0),";
				$values[] = $pid;
				$values[] = $jtid;
				$values[] = $sms->sequence;
			}
		}
		QuickUpdate("Begin");
		QuickUpdate("delete from contactpref where personid=?", false, array($pid));
		$query = substr($query, 0, strlen($query)-1); // remove trailing comma
		if (count($values))
			QuickUpdate($query, false, $values);
        QuickUpdate("Commit");


		// delete all groupdata for this person, rebuild from current selections
		QuickUpdate("delete from groupdata where personid=?", false, array($person->id));
		
		// add all static text fields to this person
		$staticList = QuickQueryList("select fieldnum from fieldmap where options like '%text%' and options like '%subscribe%' and options like '%static%'"); 
		foreach ($staticList as $fieldnum) {
			$value = QuickQuery("select value from persondatavalues where fieldnum=? and editlock=1", false, array($fieldnum));
			if ($value) {
				$person->$fieldnum = $value;
			}
		}
        
		foreach ($fieldmaps as $fieldmap) {
			$fieldnum = $fieldmap->fieldnum;
			if (!isset($postdata[$fieldnum])) continue; // some had no data to display
			
			$val = $postdata[$fieldnum];
			if ($val == null)
				$val = array();

			if ('f' == substr($fieldnum, 0, 1)) {
				$person->$fieldnum = $val;
			} else { // 'g'
				$gfield = substr($fieldnum, 1, 3);
				//QuickUpdate("delete from groupdata where fieldnum=".$gfield." and personid=".$person->id);
				
				if (count($val) > 0) {
					$query = "insert into groupdata (personid, fieldnum, value, importid) values ";
					$args = array();
					foreach ($val as $v) {
						$query .= "(?, ?, ?, 0), ";
						$args[] = $person->id;
						$args[] = $gfield;
						$args[] = $v;
					}
					$query = substr($query, 0, strlen($query)-2); // remove trailing comma and space
					QuickUpdate($query, false, $args);
				}
			}
		}

        $preferences = array();
        $preferences['_locale'] = $postdata['locale'];
        $prefs = json_encode($preferences);

		QuickUpdate("update subscriber set preferences=? where id=?", false, array($prefs, $_SESSION['subscriberid']));
		$_SESSION['_locale'] = $postdata['locale'];        

		$person->$languageField = "English";
		if ($postdata['locale'] == "es_US")
			$person->$languageField = "Spanish";
        
        $person->update();
        $_SESSION['subscriber.firstname'] = $person->$firstnameField;
        $_SESSION['subscriber.lastname'] = $person->$lastnameField;

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

startWindow(_L('Contacts'));

// table of destinations
showObjects($destinations, $titles, array("name"=>"fmt_name", "status"=>"fmt_status", "action" => "fmt_actions"));

// find remaining phone/email/sms available (some already active and pending)
$available = findAvailableDestinationTypes();
if (count($available) > 0)
	buttons(button(_L("Add More"),null,"destinationwizard.php?new"));
else {
?>
<div style="margin: 5px;">
	<img src="img/bug_lightbulb.gif" ><?=_L("All available contacts have been added.  Delete one of the above contacts before you add more.")?>
</div>
<?
}
endWindow();
echo "<br>";

// form data
startWindow(_L('Interests'));

echo '<table cellpadding="3"><tr><td>&nbsp;&nbsp;<img src="img/bug_lightbulb.gif" >&nbsp;&nbsp;' . _L("In addition to Emergency notifications, I would like to receive the following types of announcements:") . '</td></tr></table>';
echo $form->render();

endWindow();

require_once("navbottom.inc.php");
?>