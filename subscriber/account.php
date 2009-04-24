<?
require_once("common.inc.php");
require_once("../inc/html.inc.php");
require_once("../inc/form.inc.php");
require_once("../inc/table.inc.php");
require_once("../obj/Phone.obj.php");
require_once("../obj/Person.obj.php");
require_once("../obj/FieldMap.obj.php");


$person = new Person($_SESSION['personid']);

$firstnameField = FieldMap::getFirstNameField();
$lastnameField = FieldMap::getLastNameField();
$subscribeFields = FieldMap::getSubscribeMapNames();

$subscribeFieldValues = array();
foreach ($subscribeFields as $fieldnum => $name) {
	$subscribeFieldValues[$fieldnum] = QuickQueryList("select value from persondatavalues where fieldnum='".$fieldnum."' and editlock=1");
}


$formdata = array(
    "locale" => array(
        "label" => _L("Display Language"),
        "value" => $_SESSION['_locale'],
        "validators" => array(    
            array("ValRequired")
        ),
        "control" => array("RadioButton","values" => $LOCALES),
        "helpstep" => 1
    )
);

$helpsteps = array (
    "Welcome to the Guide system. You can use this guide to walk through the form, or access it as needed by clicking to the right of a section",
	"Select a language"
);

$buttons = array(submit_button("Save","submit","tick"),
                icon_button("Cancel","cross",null,"account.php"));

$form = new Form("accountform",$formdata,$helpsteps,$buttons);
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

        $preferences = array();
        $preferences['_locale'] = $postdata['locale'];
        $prefs = json_encode($preferences);

		QuickUpdate("update subscriber set preferences=? where id=?", false, array($prefs, $_SESSION['subscriberid']));
		$_SESSION['_locale'] = $postdata['locale'];        

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