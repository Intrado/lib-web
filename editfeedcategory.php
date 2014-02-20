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
require_once("inc/appserver.inc.php");


////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!getSystemSetting("_hasfeed") || !$USER->authorize('managesystem')) {
	redirect('unauthorized.php');
}

////////////////////////////////////////////////////////////////////////////////
// Optional Form Items And Validators
////////////////////////////////////////////////////////////////////////////////
class ValFeedName extends Validator {
	var $onlyserverside = true;
	function validate ($value, $args) {
		$dupename = QuickQuery("select 1 from feedcategory where not deleted and name = ? and id != ? limit 1", false, array($value, $args['id']));
		if ($dupename)
			return $this->label." "._L("already exists. Duplicate category names are not allowed.");
		return true;
	}
}

////////////////////////////////////////////////////////////////////////////////
// Form Data
////////////////////////////////////////////////////////////////////////////////

// get the current non-deleted categories from the db
$categories = DBFindMany("FeedCategory", "from feedcategory where not deleted order by id");

// TODO: get feed types when available
$feedTypes = array(
	"rss" => "RSS",
	"desktop" => "Desktop Alerts",
	"push" => "Push Notifications" 
);
$feedTypeKeys = array_keys($feedTypes);
$feedTypeLabels = array_values($feedTypes);
 
$formdata = array();
foreach ($categories as $category) {
	$formdata[] = _L('Category: %s', $category->name);
	$formdata["feedcategoryname-".$category->id] = array(
		"label" => _L('Name'),
		"fieldhelp" => _L('This is the name of the feed category.'),
		"value" => $category->name,
		"validators" => array(
			array("ValRequired"),
			array("ValFeedName", "id" => $category->id),
			array("ValLength","min" => 1,"max" => 50)
		),
		"control" => array("TextField","size" => 30, "maxlength" => 50),
		"helpstep" => 1
	);
	$formdata["feedcategorydesc-".$category->id] = array(
		"label" => _L('Description'),
		"fieldhelp" => _L('This is a short description, describing the content appropriate to this feed category.'),
		"value" => $category->description,
		"validators" => array(
			array("ValLength","min" => 0,"max" => 255)
		),
		"control" => array("TextArea","size" => 30, "cols" => 34),
		"helpstep" => 1
	);
	
	$types = QuickQueryList("select type from feedcategorytype where feedcategoryid=?", false, false, array($category->id));
	$values = array();
	for($i = 0; $i < count($feedTypeKeys); ++ $i) {
		if (in_array($feedTypeKeys[$i], $types)) {
			$values [] = $i;
		}
	}
	
	$formdata["feedcategorytypes-".$category->id] = array(
		"label" => _L('Feed Type(s)'),
		"fieldhelp" => _L(''),
		"value" => $values,
		"validators" => array(
			array("ValInArray", "values" => array_keys($feedTypeKeys))
		),
		"control" => array("MultiCheckBox", "values" => $feedTypeLabels),
		"helpstep" => 1
	);

	$formdata["feedcategorydelete-".$category->id] = array(
		"label" => "",
		"value" => "",
		"validators" => array(),
		"control" => array("InpageSubmitButton",
			"submitvalue" => "delete-".$category->id, 
			"name" => _L("Remove %s",$category->name), 
			"icon" => "cross",
			"confirm" => _L("Are you sure you want to delete feed category: %s?",$category->name)),
		"helpstep" => 1
	);

	if (intval(getCustomerSystemSetting('_cmaappid') > 0)) {
		$formdata["feedcategorymapping-".$category->id] = array(
			"label" => "",
			"value" => "",
			"validators" => array(),
			"control" => array("InpageSubmitButton",
				"submitvalue" => "cmamap-".$category->id,
				"name" => _L("Map to CMA Category"),
				"icon" => "pictos/p1/16/28"),
			"helpstep" => 1
		);
	}
}

$formdata[] = _L('New Feed Category');
$formdata["feedcategoryname-new"] = array(
	"label" => _L('Name'),
	"fieldhelp" => _L('This is the name of the feed category.'),
	"value" => "",
	"validators" => array(
		array("ValFeedName", "id" => "new"),
		array("ValLength","min" => 1,"max" => 50)
	),
	"control" => array("TextField","size" => 30, "maxlength" => 50),
	"helpstep" => 1
);
$formdata["feedcategorydesc-new"] = array(
	"label" => _L('Description'),
	"fieldhelp" => _L('This is a short description, describing the content appropriate to this feed category.'),
	"value" => "",
	"validators" => array(
		array("ValLength","min" => 0,"max" => 255)
	),
	"control" => array("TextArea","size" => 30, "cols" => 34),
	"requires" => array("feedcategoryname-new"),
	"helpstep" => 1
);
$formdata["feedcategorytypes-new"] = array(
    "label" => _L('Feed Type(s)'),
    "fieldhelp" => _L(''),
    "value" => array(),
    "validators" => array(
        array("ValInArray", "values" => array_keys($feedTypeKeys))
    ),
    "control" => array("MultiCheckBox", "values" => $feedTypeLabels),
    "helpstep" => 1
);
$formdata["feedcategoryadd-new"] = array(
	"label" => "",
	"value" => "",
	"validators" => array(),
	"control" => array("InpageSubmitButton", "submitvalue" => "newcategory", "name" => _L("Add New Category"), "icon" => "add"),
	"helpstep" => 1
);

$buttons = array(submit_button(_L('Save'),"submit","tick","settings.php"),
				icon_button(_L('Cancel'),"cross",null,"settings.php"));
$form = new Form("editfeedcategory",$formdata,null,$buttons);

function createCategoryTypes($categoryId, $types) {
	global $feedTypeKeys;
	foreach ($types as $t) {
		QuickUpdate("insert into feedcategorytype values(?,?)", false, array($categoryId, $feedTypeKeys[$t]));
	}
}

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

		if ($postdata['feedcategoryname-new']) {
			$nfc = new FeedCategory();
			$nfc->name = $postdata['feedcategoryname-new'];
			$nfc->description = $postdata['feedcategorydesc-new'];
			$nfc->create();
			$types = $postdata['feedcategorytypes-new'];
			createCategoryTypes($nfc->id, $types);			
			notice(_L("New category %s created.", $nfc->name));
		}
		
		$categoryids = array();
		foreach ($categories as $category) {
			// if the delete button was clicked for this one. set it to deleted.
			if ($button == "delete-".$category->id) {
				$category->deleted = 1;

				// Delete any CMA categories mapped to this feed category (if there are any)
				Query("DELETE FROM `cmafeedcategory` WHERE `feedcategoryid` = ?;", false, array($category->id));

				notice(_L("Feed category %s has been deleted.", $category->name));
			} else {
				$category->name = $postdata['feedcategoryname-'.$category->id];
				$category->description = $postdata['feedcategorydesc-'.$category->id];
				QuickUpdate("delete from feedcategorytype where feedcategoryid=?", false, array($category->id));
				$types = $postdata['feedcategorytypes-' . $category->id];
				createCategoryTypes($category->id, $types);
			}
			$category->update();
			// TODO: maybe only invalidate feed categories that changed?
			$categoryids[] = $category->id;
		}
		
		Query("COMMIT");

		// appserver to expire feed cache
		if (count($categoryids) > 0)
			expireFeedCategories($CUSTOMERURL, $categoryids);
		
		if (substr($button,0,7) == 'delete-' || $button == "newcategory") {
			$redirectLocation = "editfeedcategory.php";
		} else if(substr($button,0,7) == 'cmamap-') {
			$redirectLocation = "feedcategorymapping.php?id=". substr($button, 7);
		} else {
			notice(_L("Feed category changes are now saved."));
			$redirectLocation = "settings.php";
		}

		if ($ajax)
			$form->sendTo($redirectLocation);
		else
			redirect($redirectLocation);
	}
}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$PAGE = "admin:settings";
$TITLE = _L('Edit Feed Category');

include_once("nav.inc.php");

?>
<script type="text/javascript">
<?	Validator::load_validators(array("ValFeedName"));?>
</script>
<?

startWindow(_L('Feed Categories'));
echo $form->render();
endWindow();
include_once("navbottom.inc.php");
?>
