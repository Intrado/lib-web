<?

require_once("inc/common.inc.php");
require_once("inc/securityhelper.inc.php");
require_once("inc/table.inc.php");
require_once("inc/html.inc.php");
require_once("inc/utils.inc.php");
require_once("obj/Validator.obj.php");
require_once("obj/Form.obj.php");
require_once("obj/FormItem.obj.php");
require_once("obj/MessageGroup.obj.php");
require_once("obj/Language.obj.php");
require_once("obj/ValDuplicateNameCheck.val.php");
require_once("obj/Message.obj.php");
require_once("obj/Voice.obj.php");


require_once("obj/MessagePart.obj.php");
require_once("obj/FieldMap.obj.php");
require_once("inc/previewfields.inc.php");
require_once("obj/PreviewModal.obj.php");
require_once("inc/appserver.inc.php");

///////////////////////////////////////////////////////////////////////////////
// Authorization:/
//////////////////////////////////////////////////////////////////////////////
$cansendemail = $USER->authorize('sendemail');

if (!$cansendemail) {
	redirect('unauthorized.php');
} 


////////////////////////////////////////////////////////////////////////////////
// Form Data
////////////////////////////////////////////////////////////////////////////////
class LayoutSelector extends FormItem {
	function render ($value) {
		$n = $this->form->name."_".$this->name;
		// is the radioname data html formatted?
		$ishtml = false;
		if (isset($this->args['ishtml']) && $this->args['ishtml'])
			$ishtml = true;
		$str = '<div id='.$n.' class="radiobox stationeryselector">';
		$hoverdata = array();
		$counter = 1;
		$autoselect = count($this->args['values']) == 1; //if there is only one value, autoselect it
		foreach ($this->args['values'] as $radiovalue => $radioname) {
			if ($radioname == "#-#") {
				$str .= "<hr />\n";
			} else {
				$id = $n.'-'.$counter;
				$str .= '<input id="'.$id.'" name="'.$n.'" class="stationeryselector" type="radio" value="'.escapehtml($radiovalue).'" '.($value == $radiovalue || $autoselect ? 'checked' : '').' /><label id="'.$id.'-label" for="'.$id.'">'.($ishtml?$radioname:escapehtml($radioname)).'</label><br />';
				if (isset($this->args['hover'])) {
					$hoverdata[$id] = $this->args['hover'][$radiovalue];
					$hoverdata[$id.'-label'] = $this->args['hover'][$radiovalue];
				}
				$counter++;
			}
		}
		$str .= "</div>
		<div class=\"stationerypreview\">
		<iframe id=\"stationerypreview\"  src=\"blank.html\"></iframe>
		</div>
		<script type=\"text/javascript\">
			$('{$this->form->name}').on('change', 'input.stationeryselector', function(event) {	
				$('stationerypreview').src = 'mglayoutpreview.php?layout=' + event.element().value;
			});
		";
		if (isset($this->args['hover']))
			$str .= 'form_do_hover(' . json_encode($hoverdata) .');';
		$str .= '</script>';
		
		
		return $str;
	}
}




$helpsteps = array();
$formdata = array();
$helpstepnum = 1;
$helpsteps[] = _L("Enter a name for your Stationery. " .
					"Using a descriptive name that indicates the stationery content will make it easier to find the message later. " .
					"You may also optionally enter a description of the stationery.");
$formdata["name"] = array(
	"label" => _L('Stationery Name'),
	"fieldhelp" => _L('Enter a name for your Stationery.'),
	"value" => "",
	"validators" => array(
		array("ValRequired"),
		array("ValDuplicateNameCheck","type" => "messagegroup"),
		array("ValLength","max" => 50)
	),
	"control" => array("TextField","size" => 30, "maxlength" => 50),
	"helpstep" => 1
);
$formdata["description"] = array(
	"label" => _L('Description'),
	"fieldhelp" => _L('Enter a description of the stationery. This is optional, but can help identify the stationery later.'),
	"value" => "",
	"validators" => array(
		array("ValLength","min" => 0,"max" => 50)
	),
	"control" => array("TextField","size" => 30, "maxlength" => 50),
	"helpstep" => $helpstepnum++
);

$layouts = array(
	"onecolumn" => _L("One Column"),
	"twocolumns" => _L("Two Columns"),
	"threecolumns" => _L("Three Columns"),
	"1:2:3" => _L("1:2:3"),
	"2:3:2" => _L("2:3:2")
);

$formdata["layout"] = array(
		"label" => _L("Layout"),
		"fieldhelp" => _L("Select the layout that is similar to the final design that is desired for the stationery. "),
		"value" => "",
		"validators" => array(
				array("ValRequired"),
				array("ValInArray", "values" => array_keys($layouts))
		),
		"control" => array("LayoutSelector", "values" => $layouts),
		"helpstep" => $helpstepnum++
);


$buttons = array(submit_button(_L('Next'),"submit","arrow_right"));

$form = new Form("mglayoutselector",$formdata,$helpsteps,$buttons);

////////////////////////////////////////////////////////////////////////////////
// Form Data Handling
////////////////////////////////////////////////////////////////////////////////

//check and handle an ajax request (will exit early)
//or merge in related post data
$form->handleRequest();
//check for form submission
if ($button = $form->getSubmit()) { //checks for submit and merges in post data
	$ajax = $form->isAjaxSubmit(); //whether or not this requires an ajax response

	if ($form->checkForDataChange()) {
		$datachange = true;
	} else if (($errors = $form->validate()) === false) { //checks all of the items in this form
		$postdata = $form->getData(); //gets assoc array of all values {name:value,...}
		Query("BEGIN");
		$messagegroup = new MessageGroup();
		$messagegroup->name = removeIllegalXmlChars($postdata['name']);
		$messagegroup->description = $postdata['description'];
		$messagegroup->type = "stationery";
		$messagegroup->userid = $USER->id;
		$messagegroup->modified = date("Y-m-d H:i:s", time());
		$messagegroup->permanent = 1;
		$messagegroup->create();
		
		$message = new Message();
		$message->messagegroupid = $messagegroup->id;
		$message->type = "email";
		$message->subtype = "html";
		$message->autotranslate = 'none';
		$message->name = $messagegroup->name;
		$message->description = "Created from stationary layout: " . $postdata['layout'];
		$message->userid = $USER->id;
		$message->modifydate = date("Y-m-d H:i:s");
		$message->languagecode = "en";
		$message->create();
		
		// create the message parts
		$message->recreateParts(file_get_contents("layouts/{$postdata['layout']}.html"), null, false);
		
		Query("COMMIT");
		if ($ajax)
			$form->sendTo("mgeditor.php?id=" . $messagegroup->id);
		else
			redirect("mgeditor.php?id=" . $messagegroup->id);
	}
}
////////////////////////////////////////////////////////////////////////////////
// Display Functions
////////////////////////////////////////////////////////////////////////////////

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$PAGE = "notifications:messages";
$TITLE = _L('Stationery Editor');

include_once("nav.inc.php");
?>
<script type="text/javascript">
<? Validator::load_validators(array("ValDuplicateNameCheck"));?>
</script>
<?

startWindow(_L('Stationery Layouts'));
echo $form->render();
endWindow();

include_once("navbottom.inc.php");
?>