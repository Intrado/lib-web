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
require_once("obj/FormBrandTheme.obj.php");

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
$helpstepnum = 0;
/* Version 7.0 will not support UI translation
$helpsteps = array(_L("Select the default language for the user interface."));
$formdata["locale"] = array(
	"label" => _L("Default Language"),
	"fieldhelp" => _L("This is the default language for the user interface."),
	"value" => getSystemSetting('_locale'),
	"validators" => array(
	),
	"control" => array("SelectMenu","values"=>$LOCALES),
	"helpstep" => $helpstepnum
);*/

$helpsteps[$helpstepnum++] = _L("Choose a theme for the user interface.<br><br>Additionally, you can select a color which will be blended into the grey parts of certain interface components. The amount of tint is determined by the shader ratio.<br><br> Setting the theme will reset the color and ratio options to the theme defaults.");
$formdata["brandtheme"] = array(
	"label" => _L("Default Theme"),
	"fieldhelp" => _L("Use this to select a different theme for the user interface. Themes can be customized with alternate primary colors (in hex) and primary to background color ratio settings."),
	"value" => json_encode(array(
		"theme"=>getSystemSetting('_brandtheme'),
		"color"=>getSystemSetting('_brandprimary'),
		"ratio"=>getSystemSetting('_brandratio'),
		"customize"=>true
		)),
	"validators" => array(
		array("ValRequired"),
		array("ValBrandTheme", "values" => array_keys($COLORSCHEMES))),
	"control" => array("BrandTheme","values"=>$COLORSCHEMES,"toggle"=>false),
	"helpstep" => $helpstepnum
);

// system setting 'organizationfieldname'
$helpsteps[$helpstepnum++] = _L("A display name used on labels and rule selections for the organization field.  A common example is 'School', 'Site' or 'Organization'.");
$formdata["organizationfieldname"] = array(
	"label" => _L("Organization Display Name"),
	"fieldhelp" => _L("Name displayed for your organization field.  May be 'School' or 'Organization' or your preference."),
	"value" => getSystemSetting('organizationfieldname', 'School'),
	"validators" => array(
		array("ValRequired"),
		array("ValLength","max" => 50)),
	"control" => array("TextField","size" => 37, "maxlength" => 50),
	"helpstep" => $helpstepnum
);


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
		/* Version 7.0 does not support UI translation
		setSystemSetting('_locale', $postdata['locale']);*/
		
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
		
		setSystemSetting('organizationfieldname', $postdata['organizationfieldname']);
		
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
$TITLE = _L('Systemwide Display');

require_once("nav.inc.php");
?>
<script type="text/javascript">
<? if ($datachange) { ?>
	alert("<?=_L("The data on this form has changed. You're changes cannot be saved.")?>")";
	window.location = '<?= addcslashes($_SERVER['REQUEST_URI']) ?>';
<? } ?>
<? Validator::load_validators(array("ValBrandTheme"));?>
</script>
<?

startWindow(_L("Settings"));
echo $form->render();
endWindow();

require_once("navbottom.inc.php");
?>

