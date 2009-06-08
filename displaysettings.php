<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("inc/common.inc.php");
require_once("inc/table.inc.php");
require_once("inc/html.inc.php");
require_once("inc/themes.inc.php");
require_once("obj/Validator.obj.php");
require_once("obj/Form.obj.php");
require_once("obj/FormItem.obj.php");

////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('managesystem')) {
	redirect('unauthorized.php');
}

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

$formdata = array();
$helpsteps = array(_L("Global default display settings."));

$helpstepnum = 1;

$formdata["locale"] = array(
	"label" => _L("Default Language"),
	"value" => getSystemSetting('_locale'),
	"validators" => array(
	),
	"control" => array("SelectMenu","values"=>$LOCALES),
	"helpstep" => $helpstepnum
);
$helpsteps[$helpstepnum++] = _L("User interface language and localization selection.");

$formdata["brandtheme"] = array(
	"label" => _L("Default Theme"),
	"value" => json_encode(array(
		"theme"=>getSystemSetting('_brandtheme'),
		"color"=>getSystemSetting('_brandprimary'),
		"ratio"=>getSystemSetting('_brandratio'),
		"customize"=>true
		)),
	"validators" => array(array("ValTheme")),
	"control" => array("BrandTheme","values"=>$COLORSCHEMES,"toggle"=>false),
	"helpstep" => $helpstepnum
);
$helpsteps[$helpstepnum++] = _L("The Color Theme controls the systemwide color palette.");

$buttons = array(submit_button(_L("Done"),"submit","accept"),
				icon_button(_L("Cancel"),"cross",null,"settings.php"));

$form = new Form("customerinfo", $formdata, $helpsteps, $buttons);
$form->ajaxsubmit = true;

//check and handle an ajax request (will exit early)
//or merge in related post data
$form->handleRequest();

$datachange = false;
$errors = false;

if ($button = $form->getSubmit()) { //checks for submit and merges in post data
	$ajax = $form->isAjaxSubmit(); //whether or not this requires an ajax response    

	if ($form->checkForDataChange()) {
		$datachange = true;
	} else if (($errors = $form->validate()) === false) { //checks all of the items in this form
		$postdata = $form->getData(); //gets assoc array of all values {name:value,...}
		
		//save data here
		setSystemSetting('_locale', $postdata['locale']);
		
		$newTheme = json_decode($postdata['brandtheme']);
		setSystemSetting('_brandtheme', $newTheme->theme);
		setSystemSetting('_brandprimary', $newTheme->color);
		setSystemSetting('_brandratio', $newTheme->ratio);
		setSystemSetting('_brandtheme1', $COLORSCHEMES[$newTheme->theme]["_brandtheme1"]);
		setSystemSetting('_brandtheme2', $COLORSCHEMES[$newTheme->theme]["_brandtheme2"]);
			
		if(!QuickQuery("select value from usersetting where name = '_brandtheme' and userid='" . $USER->id . "'")){
			$_SESSION['colorscheme']['_brandtheme'] = $newTheme->theme;
			$_SESSION['colorscheme']['_brandprimary'] = $newTheme->color;
			$_SESSION['colorscheme']['_brandratio'] = $newTheme->ratio;
			$_SESSION['colorscheme']['_brandtheme1'] = $COLORSCHEMES[$newTheme->theme]["_brandtheme1"];
			$_SESSION['colorscheme']['_brandtheme2'] = $COLORSCHEMES[$newTheme->theme]["_brandtheme2"];
		}
		
		if ($ajax) {
			$form->sendTo("settings.php");
		} else
			redirect("settings.php");
    }
}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$PAGE = "admin:settings";
$TITLE = _L('Systemwide Security');

require_once("nav.inc.php");
?>
<script>
<? if ($datachange) { ?>
	alert("<?=_L("The data on this form has changed. You're changes cannot be saved.")?>")";
	window.location = '<?= addcslashes($_SERVER['REQUEST_URI']) ?>';
<? } ?>
</script>
<?

startWindow(_L("Settings"));
echo $form->render();
endWindow();

require_once("navbottom.inc.php");
?>

