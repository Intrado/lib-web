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
require_once("obj/ValDuplicateNameCheck.val.php");


////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////

if (!$USER->authorize('managesystem')) {
	redirect('unauthorized.php');
}

////////////////////////////////////////////////////////////////////////////////
// Image path array of valid images
////////////////////////////////////////////////////////////////////////////////

$validimages = array(
	"gold star" => "award_star_gold_2",
	"lightning" => "lightning",
	"information" => "information",
	"red dot" => "diagona/16/151",
	"green dot" => "diagona/16/152",
	"blue dot" => "diagona/16/153",
	"yellow dot" => "diagona/16/154",
	"pink dot" => "diagona/16/155",
	"orange dot" => "diagona/16/156",
	"purple dot" => "diagona/16/157",
	"black dot" => "diagona/16/158",
	"gray dot" => "diagona/16/159",
);

$formradiovalues = array();
foreach($validimages as $key => $image) {
	$formradiovalues[$key] = "<img src='img/icons/$image.gif' style='border:0px' />";
}

////////////////////////////////////////////////////////////////////////////////
// Custom Form Items
////////////////////////////////////////////////////////////////////////////////
class ImgRadioButton extends FormItem {
	function render ($value) {
		$n = $this->form->name."_".$this->name;
		$str = '<div id='.$n.' class="radiobox">';
		$counter = 1;
		foreach ($this->args['values'] as $radiovalue => $radiohtml) {
			$id = $n.'-'.$counter;
			$str .= '<div style="float:left;margin:10px;"><input id="'.$id.'" name="'.$n.'" type="radio" style="float:left" value="'.escapehtml($radiovalue).'" '.($value == $radiovalue ? 'checked' : '').' /><label for="'.$id.'"><button type="button" class="regbutton" style="border: 0px; background-color: white; color: black; margin-left: 0px;" onclick="$(\''.$id.'\').click();">'.($radiohtml).'</button></label>
				</div><div style="clear:both;"></div>';
			$counter++;
		}

		$str .= '<div style="clear:both;"></div></div>';
		return $str;
	}
}

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

//get the message to edit from the request params or session
if (isset($_GET['id'])) {
	if($_GET['id'] == "new") {
		$_SESSION["targetedmessagecategoryid"] = null;
	} else {
		$_SESSION["targetedmessagecategoryid"] = $_GET['id'] + 0;
	}
	redirect("targetedmessagecategory.php");
}

$id = $_SESSION["targetedmessagecategoryid"];
////////////////////////////////////////////////////////////////////////////////
// Action/Request Processing
////////////////////////////////////////////////////////////////////////////////

if (isset($_GET['deleteid'])) {
	
}

////////////////////////////////////////////////////////////////////////////////
// Form Data
////////////////////////////////////////////////////////////////////////////////
$row = false;

if($id != null) {
	$row = QuickQueryRow("select name, image from targetedmessagecategory where id = ?", true,false, array($id));
}



if($row != false) {
	$name = $row["name"];
	$image = isset($row["image"])?$row["image"]:"";
} else {
	$name = "";
	$image = "";
}


$formdata = array(
	"name" => array(
		"label" => _L('Name'),
		"value" => $name,
		"validators" => array(
			array("ValRequired"),
			array("ValLength","min" => 3,"max" => 50),
			array("ValDuplicateNameCheck","type" => "targetedmessagecategory")
		),
		"control" => array("TextField","size" => 30, "maxlength" => 51),
		"helpstep" => 1
	),
	"image" => array(
		"label" => _L('Image'),
		"value" => $image,
		"validators" => array(
			array("ValRequired"),
			array("ValInArray", "values" => array_keys($formradiovalues)),
		),
		"control" => array("ImgRadioButton","values" => $formradiovalues),
		"helpstep" => 2
	),
);

$helpsteps = array (
	_L('The name of the category should reflect the content of its messages.'),
	_L('The category image is a visual aid that can help the user find the appropriate category.')
	);

$buttons = array(submit_button(_L('Save'),"submit","tick"),
				icon_button(_L('Cancel'),"cross",null,"targetedmessageedit.php"));
$form = new Form("targetedmessagecategory",$formdata,$helpsteps,$buttons);

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
		if($id != null) {
			QuickUpdate("update targetedmessagecategory set name=?, image=? where id=?", false, array($postdata["name"],$postdata["image"],$id));
		} else {
			QuickUpdate("insert into targetedmessagecategory (name,image) values (?,?)",false,array($postdata["name"],$postdata["image"]));
		}
		if ($ajax)
			$form->sendTo("targetedmessageedit.php");
		else
			redirect("targetedmessageedit.php");
	}
}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$PAGE = "admin:settings";
$TITLE = $id==null?_L("Create New Targeted Message Category"):_L("Rename Targeted Message Category");

include_once("nav.inc.php");
?>
<script type="text/javascript">
<? Validator::load_validators(array("ValDuplicateNameCheck")); ?>
</script>
<?
startWindow(_L('Targeted Message Category'));
echo $form->render();
endWindow();
include_once("navbottom.inc.php");
?>