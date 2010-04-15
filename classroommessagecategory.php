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
require_once("obj/TargetedMessageCategory.obj.php");


////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////

if (!getSystemSetting('_hastargetedmessage', false) || !$USER->authorize('manageclassroommessaging')) {
	redirect('unauthorized.php');
}

$formradiovalues = array();
foreach($classroomcategoryicons as $key => $image) {
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
	redirect("classroommessagecategory.php");
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

$category = new TargetedMessageCategory($id);

$formdata = array(
	"name" => array(
		"label" => _L('Name'),
		"fieldhelp" => _L('Enter a name for the message category that will appear on the tab.'),
		"value" => $category->name,
		"validators" => array(
			array("ValRequired"),
			array("ValLength","min" => 0,"max" => 50),
			array("ValDuplicateNameCheck","type" => "targetedmessagecategory")
		),
		"control" => array("TextField","size" => 30, "maxlength" => 50),
		"helpstep" => 1
	),
	"image" => array(
		"label" => _L('Icon'),
		"fieldhelp" => _L('Select an icon to display on the message category tab.'),
		"value" => $category->image,
		"validators" => array(
			array("ValRequired"),
			array("ValInArray", "values" => array_keys($formradiovalues)),
		),
		"control" => array("ImgRadioButton","values" => $formradiovalues),
		"helpstep" => 2
	),
);

$helpsteps = array (
	_L('Enter a name for your new message category. The name of the category will appear on the category tab and should reflect the content of its messages.'),
	_L('The category icon adds a visual cue for the category and will appear next to the category name on its tab.')
	);

$buttons = array(submit_button(_L('Save'),"submit","tick"),
				icon_button(_L('Cancel'),"cross",null,"classroommessagemanager.php"));
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

		$category->name = $postdata["name"];
		$category->image = $postdata["image"];
		$category->update();

		if ($ajax)
			$form->sendTo("classroommessagemanager.php");
		else
			redirect("classroommessagemanager.php");
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