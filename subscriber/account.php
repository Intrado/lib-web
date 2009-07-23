<?
require_once("common.inc.php");

require_once("../obj/Validator.obj.php");
require_once("../obj/Form.obj.php");
require_once("../obj/FormItem.obj.php");

require_once("../inc/table.inc.php");
require_once("../inc/html.inc.php");
require_once("../inc/form.inc.php");
require_once("../obj/FieldMap.obj.php");
require_once("../obj/Person.obj.php");
require_once("../obj/Phone.obj.php");
require_once("../obj/Email.obj.php");
require_once("../obj/Sms.obj.php");
require_once("../obj/JobType.obj.php");
require_once("subscriberutils.inc.php");


////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

$pid = $_SESSION['personid'];
$person = new Person($_SESSION['personid']);

$firstnameField = FieldMap::getFirstNameField();
$lastnameField = FieldMap::getLastNameField();
$languageField = FieldMap::getLanguageField();
$subscribeFields = FieldMap::getSubscribeMapNames();

$subscribeFieldValues = array();
foreach ($subscribeFields as $fieldnum => $name) {
	if ('f' == substr($fieldnum, 0, 1)) {
		$subscribeFieldValues[$fieldnum] = QuickQueryList("select value, value from persondatavalues where fieldnum='".$fieldnum."' and editlock=1", true);
	} else {
		$gfield = substr($fieldnum, 1, 3);
		$subscribeFieldValues[$fieldnum] = QuickQueryList("select value, value from groupdata where fieldnum='".$gfield."' and personid=0 and importid=0", true);
	}
}


$fieldmaps = DBFindMany("FieldMap", "from fieldmap where options like '%subscribe%' order by fieldnum");

$formhtmlemail = escapehtml($_SESSION['subscriber.username']) . '<br>' .
				'<a href="changeemail.php">Change Account Email</a><br>';

$formhtmlpass = '<a href="changepass.php">Change Password</a><br>';

$formhtmlclose = '<a href="closeaccount.php">Permanently close my account</a><br>';

$formdata = array();

$formdata["changeemail"] = array(
   	"label" => "Account Email",
   	"control" => array("FormHtml","html" => $formhtmlemail),
	"helpstep" => 1
);
$formdata["changepass"] = array(
   	"label" => "Account Password",
   	"control" => array("FormHtml","html" => $formhtmlpass),
	"helpstep" => 1
);
$formdata["closeaccount"] = array(
   	"label" => "Account Status",
   	"control" => array("FormHtml","html" => $formhtmlclose),
	"helpstep" => 1
);

foreach ($fieldmaps as $fieldmap) {
	$fieldnum = $fieldmap->fieldnum;
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
					if ($person->$fieldnum == "French")
						$value = "fr_CA";
				
					$formdata['locale'] = array (
   	    				"label" => _L($fieldmap->name),
       					"value" => $value,
       					"validators" => array(
       						array("ValRequired")
       					),
       					"control" => array("RadioButton","values" => $LOCALES),
       					"helpstep" => 1
					);
				
				} else {
					$values = QuickQueryList("select value, value from persondatavalues where fieldnum='".$fieldnum."' and editlock=1", true);
					if (count($values) > 0) {
						$v = $person->$fieldnum;
						if (count($values) == 1) {
							$a = array_values($values);
							$v = $a[0];
						}
						$formdata[$fieldnum] = array (
    	    				"label" => _L($fieldmap->name),
        					"value" => $v,
        					"validators" => array(
        						array("ValRequired")
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
        			"label" => _L($fieldmap->name),
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
			
				$values = QuickQueryList("select value, value from persondatavalues where fieldnum='".$fieldnum."' and editlock=0", true);
				if (count($values) > 0)
					$formdata[$fieldnum] = array (
    	    			"label" => _L($fieldmap->name),
        				"value" => $person->$fieldnum,
        				"validators" => array(
        					array("ValRequired")
        				),
        				"control" => array("RadioButton","values" => $values),
        				"helpstep" => 1
					);
			}
		}
	} else { // Gfield
		if ($fieldmap->isOptionEnabled("static")) {
				// static multi, subscriber must select one
				
				$values = QuickQueryList("select value, value from persondatavalues where fieldnum='".$fieldnum."' and editlock=1", true);
		} else {
				// dynamic multi, subscriber must select one (data from imports)
			
				$values = QuickQueryList("select value, value from persondatavalues where fieldnum='".$fieldnum."' and editlock=0", true);
		}
		$gfield = substr($fieldnum, 1, 3);
		$arr = QuickQueryList("select value, value from groupdata where personid=".$person->id." and fieldnum=".$gfield);
				if (count($values) > 0)
					$formdata[$fieldnum] = array (
    	    			"label" => _L($fieldmap->name),
        				"value" => $arr,
        				"validators" => array(),
        				"control" => array("MultiCheckbox","values" => $values),
        				"helpstep" => 1
					);
	}
}


$buttons = array(submit_button(_L("Save"),"save","tick"),
                icon_button(_L("Cancel"),"cross",null,"account.php?cancel"));

$formname = "contactinfo";                
$_REQUEST['form'] = $formname;                
$form = new Form($formname,$formdata,null,$buttons);
$form->ajaxsubmit = true;

//check and handle an ajax request (will exit early)
//or merge in related post data
$form->handleRequest();

//check for form submission
if ($button = $form->getSubmit()) { //checks for submit and merges in post data
    if (($errors = $form->validate()) === false) { //checks all of the items in this form
        $postdata = $form->getData(); //gets assoc array of all values {name:value,...}
        $ajax = $form->isAjaxSubmit(); //whether or not this requires an ajax response        
        
        //save data here

		// delete all groupdata for this person, rebuild from current selections
		QuickUpdate("delete from groupdata where personid=".$person->id);
		
		// add all static text fields to this person
		$staticList = QuickQueryList("select fieldnum from fieldmap where options like '%text%subscribe%static%'"); //TODO FIXME this breaks if the order of the options changes. 
		foreach ($staticList as $fieldnum) {
			$value = QuickQuery("select value from persondatavalues where fieldnum='".$fieldnum."' and editlock=1");
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
					$query = substr($query, 0, strlen($query)-2); // remove trailing comma
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
		if ($postdata['locale'] == "fr_CA")
			$person->$languageField = "French";
        
        $person->update();
        $_SESSION['subscriber.firstname'] = $person->$firstnameField;
        $_SESSION['subscriber.lastname'] = $person->$lastnameField;
        
        if ($ajax)
            $form->sendTo("account.php");
        else
            redirect("account.php");
    }
}


$PAGE = "account:account";
$TITLE = _L("Account Information") . ": " . escapehtml($_SESSION['subscriber.firstname']) . " " . escapehtml($_SESSION['subscriber.lastname']);
require_once("nav.inc.php");

startWindow(_L('User Information'));
echo $form->render();
endWindow();

require_once("navbottom.inc.php");
?>