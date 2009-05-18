<?
require_once("common.inc.php");
require_once("../inc/html.inc.php");
require_once("../inc/table.inc.php");
require_once("../obj/Phone.obj.php");
require_once("../obj/Email.obj.php");
require_once("../obj/Sms.obj.php");


////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

$pid = $_SESSION['personid'];
$jobtypes = QuickQueryList("select id, name from jobtype where deleted=0", true);
// TODO, should we localize the job type names? Emergency, Attendance, General...
// TODO remove survey if not supported
// TODO do we need the info field for more detail display

$phoneList = DBFindMany("Phone", "from phone where personid=?", false, array($pid));
$emailList = DBFindMany("Email", "from email where personid=?", false, array($pid));
$smsList = DBFindMany("Sms", "from sms where personid=?", false, array($pid));

$formdata = array();

foreach ($phoneList as $phone) {
	if ($phone->phone == '') continue;
	
	$values = QuickQueryList("select jobtypeid from contactpref where personid=? and type='phone' and sequence=? and enabled=1", false, false, array($pid, $phone->sequence));

	$formdata["phone".$phone->sequence] = array(
        "label" => Phone::format($phone->phone),
        "value" => $values,
        "validators" => array(),
        "control" => array("MultiCheckbox","values" => $jobtypes),
        "helpstep" => 1
    );
}

foreach ($emailList as $email) {
	if ($email->email == '') continue;
	
	$values = QuickQueryList("select jobtypeid from contactpref where personid=? and type='email' and sequence=? and enabled=1", false, false, array($pid, $email->sequence));

	$formdata["email".$email->sequence] = array(
        "label" => $email->email,
        "value" => $values,
        "validators" => array(),
        "control" => array("MultiCheckbox","values" => $jobtypes),
        "helpstep" => 1
    );
}

foreach ($smsList as $sms) {
	if ($sms->sms == '') continue;
	
	$values = QuickQueryList("select jobtypeid from contactpref where personid=? and type='sms' and sequence=? and enabled=1", false, false, array($pid, $sms->sequence));

	$formdata["sms".$sms->sequence] = array(
        "label" => $sms->sms,
        "value" => $values,
        "validators" => array(),
        "control" => array("MultiCheckbox","values" => $jobtypes),
        "helpstep" => 1
    );
}


$helpsteps = array (
    _L("Welcome to the Guide system. You can use this guide to walk through the form, or access it as needed by clicking to the right of a section"),
	"blah blah blah..."
);

$buttons = array(submit_button(_L("Save"),"submit","tick"),
                icon_button(_L("Cancel"),"cross",null,"notificationpreferences.php"));
                
$form = new Form("notifyprefs",$formdata,$helpsteps,$buttons);
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
            
        
        //save data here
        
		// new contactpref rows
		$values = array();
		
		foreach ($jobtypes as $jtid => $jtname) {
			foreach ($phoneList as $phone) {
				if ($phone->phone == '') continue;
				$data = $postdata['phone'.$phone->sequence];
				$enabled = 0;
				if (array_search($jtid, $data) !== false)
					$enabled = 1;
				$values[] = "(" . $pid . "," . $jtid . ",'phone'," . $phone->sequence . "," . $enabled . ")";
			}
			foreach ($emailList as $email) {
				if ($email->email == '') continue;
				$data = $postdata['email'.$email->sequence];
				$enabled = 0;
				if (array_search($jtid, $data) !== false)
					$enabled = 1;
				$values[] = "(" . $pid . "," . $jtid . ",'email'," . $email->sequence . "," . $enabled . ")";
			}
			foreach ($smsList as $sms) {
				if ($sms->sms == '') continue;
				$data = $postdata['sms'.$sms->sequence];
				$enabled = 0;
				if (array_search($jtid, $data) !== false)
					$enabled = 1;
				$values[] = "(" . $pid . "," . $jtid . ",'sms'," . $sms->sequence . "," . $enabled . ")";
			}
		}
		
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

startWindow(_L('Preferences'));
echo $form->render();
endWindow();
require_once("navbottom.inc.php");
?>