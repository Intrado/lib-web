<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("inc/common.inc.php");
require_once("inc/securityhelper.inc.php");
require_once("inc/table.inc.php");
require_once("inc/html.inc.php");
require_once("inc/utils.inc.php");
require_once("obj/Validator.obj.php");
require_once("obj/Form.obj.php");
require_once("obj/FormItem.obj.php");
require_once("obj/FeedCategory.obj.php");
require_once("obj/InpageSubmitButton.fi.php");


////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!getSystemSetting("_hasfeed") || !$USER->authorize('managesystem')) {
	redirect('unauthorized.php');
}

////////////////////////////////////////////////////////////////////////////////
// Form Data
////////////////////////////////////////////////////////////////////////////////

// get the current non-deleted categories from the db
$categories = DBFindMany("FeedCategory", "from feedcategory where not deleted order by id");

$formdata = array();
foreach ($categories as $category) {
	$formdata[] = _L('Category: %s', $category->name);
	$formdata["feedcategoryname-".$category->id] = array(
		"label" => _L('Name'),
		"value" => $category->name,
		"validators" => array(
			array("ValRequired"),
			array("ValLength","min" => 1,"max" => 50)
		),
		"control" => array("TextField","size" => 30, "maxlength" => 50),
		"helpstep" => 1
	);
	$formdata["feedcategorydesc-".$category->id] = array(
		"label" => _L('Description'),
		"value" => $category->description,
		"validators" => array(
			array("ValLength","min" => 0,"max" => 255)
		),
		"control" => array("TextArea","size" => 30, "cols" => 34),
		"helpstep" => 1
	);
	$formdata["feedcategorydelete-".$category->id] = array(
		"label" => _L('Delete'),
		"value" => "",
		"validators" => array(),
		"control" => array("InpageSubmitButton", "submitvalue" => "delete-".$category->id, "name" => _L("Remove %s",$category->name), "icon" => "cross"),
		"helpstep" => 1
	);
}
$formdata[] = _L('New Feed Category');
$formdata["feedcategoryname-new"] = array(
	"label" => _L('Name'),
	"value" => "",
	"validators" => array(
		array("ValLength","min" => 1,"max" => 50)
	),
	"control" => array("TextField","size" => 30, "maxlength" => 50),
	"helpstep" => 1
);
$formdata["feedcategorydesc-new"] = array(
	"label" => _L('Description'),
	"value" => "",
	"validators" => array(
		array("ValLength","min" => 0,"max" => 255)
	),
	"control" => array("TextArea","size" => 30, "cols" => 34),
	"requires" => array("feedcategoryname-new"),
	"helpstep" => 1
);
$formdata["feedcategoryadd-new"] = array(
	"label" => _L('Add'),
	"value" => "",
	"validators" => array(),
	"control" => array("InpageSubmitButton", "submitvalue" => "newcategory", "name" => _L("Add New Category"), "icon" => "add"),
	"helpstep" => 1
);

$helpsteps = array (
	_L('TODO: help me!')
);

$buttons = array(submit_button(_L('Save'),"submit","tick"),
				icon_button(_L('Cancel'),"cross",null,"start.php"));
$form = new Form("templateform",$formdata,$helpsteps,$buttons);

////////////////////////////////////////////////////////////////////////////////
// Form Data Handling
////////////////////////////////////////////////////////////////////////////////

//check and handle an ajax request (will exit early)
//or merge in related post data
$form->handleRequest();

$datachange = false;
$errors = false;
//check for form submission
if ($button = $form->getSubmit()) { //checks for submit and merges in post data
	$ajax = $form->isAjaxSubmit(); //whether or not this requires an ajax response	
	
	if ($form->checkForDataChange()) {
		$datachange = true;
	} else if (($errors = $form->validate()) === false) { //checks all of the items in this form
		$postdata = $form->getData(); //gets assoc array of all values {name:value,...}
		
		Query("BEGIN");
		// create a new feed category if the "new" one is filled out
		if ($postdata['feedcategoryname-new']) {
			$nfc = new FeedCategory();
			$nfc->name = $postdata['feedcategoryname-new'];
			$nfc->description = $postdata['feedcategorydesc-new'];
			$nfc->create();
		}
		
		foreach ($categories as $category) {
			// if the delete button was clicked for this one. set it to deleted.
			if ($button == "delete-".$category->id) {
				$category->deleted = 1;
				notice(_L("Feed category %s has been deleted.", $category->name));
				// TODO call appserver expireFeedCategories()
			} else {
				$category->name = $postdata['feedcategoryname-'.$category->id];
				$category->description = $postdata['feedcategorydesc-'.$category->id];
			}
			$category->update();
		}
		Query("COMMIT");
		
		if ($button == "newcategory")
			notice(_L("New category %s created.", $nfc->name));
		
		if (substr($button,0,7) == 'delete-' || $button == "newcategory") {
			if ($ajax)
				$form->sendTo("editfeedcategory.php");
			else
				redirect("editfeedcategory.php");
		} else {
			notice(_L("Feed category changes are now saved."));

			if ($ajax)
				$form->sendTo("start.php");
			else
				redirect("start.php");
		}
	}
}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$PAGE = "admin:settings";
$TITLE = _L('Edit Feed Category');

include_once("nav.inc.php");

startWindow(_L('Feed Categories'));
echo $form->render();
endWindow();
include_once("navbottom.inc.php");
?>