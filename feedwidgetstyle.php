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
require_once("obj/ColorPicker.fi.php");
////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('managesystem')) {
	redirect('unauthorized.php');
}


////////////////////////////////////////////////////////////////////////////////
// Action/Request Processing
////////////////////////////////////////////////////////////////////////////////


////////////////////////////////////////////////////////////////////////////////
// Optional Form Items And Validators
////////////////////////////////////////////////////////////////////////////////


////////////////////////////////////////////////////////////////////////////////
// Form Data
////////////////////////////////////////////////////////////////////////////////

$fontfamilies = array(
	'default' => "Browser default",
	'"Times New Roman", Times, serif' => "Times New Roman, Times, serif",
	'Arial, Helvetica, sans-serif' => "Arial, Helvetica, sans-serif"
);
$pxsizes = array(
	"0" => "0px",
	"1" => "1px",
	"2" => "2px",
	"3" => "3px",
	"4" => "4px",
	"5" => "5px",
	"6" => "6px",
	"7" => "7px",
	"8" => "8px",
	"10" => "10px",
	"12" => "12px",
	"14" => "14px",
	"16" => "16px",
	"18" => "18px",
	"20" => "20px",
	"22" => "22px",
	"24" => "24px"
);
$borderstyles = array(
	"none" => "None",
	"solid" => "Solid",
	"dotted" => "Dotted",
	"dashed" => "Dashed",
	"double" => "Double",
	"groove" => "Groove",
	"ridge" => "Ridge",
	"inset" => "Inset",
	"outset" => "Outset"
);
$liststyletypes = array(
	"circle" => "Circle",
	"disc" => "Disc",
	"square" => "Square",
	"decimal" => "Decimal",
	"upper-alpha" => "Alpha",
	"upper-roman" => "Roman",
	"none" => "None"
);
$listpositions = array(
	"outside" => "Outside",
	"inside" => "Inside"
);
$formdata = array(
	_L('Global settings'),
	"fontfamily" => array(
		"label" => _L('Font Family'),
		"value" => "default",
		"validators" => array(
			array("ValRequired"),
			array("ValInArray", "values" => array_keys($fontfamilies))),
		"control" => array("SelectMenu", "values" => $fontfamilies),
		"helpstep" => 1
	),
	"iframeheight" => array(
		"label" => _L('Iframe height'),
		"value" => 480,
		"validators" => array(
			array("ValRequired"),
			array("ValNumber", "min" => 200, "max" => 2200)),
		"control" => array("TextField", "size" => 5),
		"helpstep" => 1
	),
	"iframewidth" => array(
		"label" => _L('Iframe width'),
		"value" => 300,
		"validators" => array(
			array("ValRequired"),
			array("ValNumber", "min" => 150, "max" => 800)),
		"control" => array("TextField", "size" => 5),
		"helpstep" => 1
	),
	"borderstyle" => array(
		"label" => _L('Border style'),
		"value" => "solid",
		"validators" => array(
			array("ValRequired"),
			array("ValInArray", "values" => array_keys($borderstyles))),
		"control" => array("SelectMenu", "values" => $borderstyles),
		"helpstep" => 1
	),
	"bordersize" => array(
		"label" => _L('Border size'),
		"value" => "1",
		"validators" => array(
			array("ValRequired"),
			array("ValInArray", "values" => array_keys($pxsizes))),
		"control" => array("SelectMenu", "values" => $pxsizes),
		"helpstep" => 1
	),
	"bordercolor" => array(
		"label" => _L('Border color'),
		"value" => $_SESSION['colorscheme']['_brandtheme2'],
		"validators" => array(
			array("ValRequired")),
		"control" => array("ColorPicker", "size" => 7),
		"helpstep" => 1
	),
	_L('Header'),
	"headersize" => array(
		"label" => _L('Text size'),
		"value" => "18",
		"validators" => array(
			array("ValRequired"),
			array("ValInArray", "values" => array_keys($pxsizes))),
		"control" => array("SelectMenu", "values" => $pxsizes),
		"helpstep" => 1
	),
	"titlecolor" => array(
		"label" => _L('Header color'),
		"value" => $_SESSION['colorscheme']['_brandprimary'],
		"validators" => array(
			array("ValRequired")),
		"control" => array("ColorPicker", "size" => 7),
		"helpstep" => 1
	),
	_L('Item List'),
	"itemstodisplay" => array(
		"label" => _L('Items to display'),
		"value" => 10,
		"validators" => array(
			array("ValRequired"),
			array("ValNumber", "min" => 5, "max" => 100)),
		"control" => array("TextField", "size" => 5),
		"helpstep" => 1
	),
	"liststyle" => array(
		"label" => _L('List style'),
		"value" => "circle",
		"validators" => array(
			array("ValRequired"),
			array("ValInArray", "values" => array_keys($liststyletypes))),
		"control" => array("SelectMenu", "values" => $liststyletypes),
		"helpstep" => 1
	),
	"listposition" => array(
		"label" => _L('List position'),
		"value" => "outside",
		"validators" => array(
			array("ValRequired"),
			array("ValInArray", "values" => array_keys($listpositions))),
		"control" => array("SelectMenu", "values" => $listpositions),
		"helpstep" => 1
	),
	"listpadding" => array(
		"label" => _L('Left padding'),
		"value" => "25",
		"validators" => array(
			array("ValNumber", "min" => 0, "max" => 60)),
		"control" => array("TextField", "size" => 5),
		"helpstep" => 1
	),
	"labelsize" => array(
		"label" => _L('Label size'),
		"value" => "16",
		"validators" => array(
			array("ValRequired"),
			array("ValInArray", "values" => array_keys($pxsizes))),
		"control" => array("SelectMenu", "values" => $pxsizes),
		"helpstep" => 1
	),
	"labelcolor" => array(
		"label" => _L('Label color'),
		"value" => $_SESSION['colorscheme']['_brandtheme1'],
		"validators" => array(
			array("ValRequired")),
		"control" => array("ColorPicker", "size" => 7),
		"helpstep" => 1
	),
	"descriptionsize" => array(
		"label" => _L('Description size'),
		"value" => "14",
		"validators" => array(
			array("ValRequired"),
			array("ValInArray", "values" => array_keys($pxsizes))),
		"control" => array("SelectMenu", "values" => $pxsizes),
		"helpstep" => 1
	),
	"descriptioncolor" => array(
		"label" => _L('Description color'),
		"value" => $_SESSION['colorscheme']['_brandtheme2'],
		"validators" => array(
			array("ValRequired")),
		"control" => array("ColorPicker", "size" => 7),
		"helpstep" => 1
	),
	"descriptionpadding" => array(
		"label" => _L('Description padding'),
		"value" => "4",
		"validators" => array(
			array("ValRequired"),
			array("ValInArray", "values" => array_keys($pxsizes))),
		"control" => array("SelectMenu", "values" => $pxsizes),
		"helpstep" => 1
	),
);

$helpsteps = array (
	_L('TODO: help me')
);

$buttons = array(submit_button(_L('Generate'),"submit","tick"), icon_button(_L('Cancel'),"cross",null,"start.php"));
$form = new Form("feedwidgetstyle",$formdata,$helpsteps,$buttons);

////////////////////////////////////////////////////////////////////////////////
// Form Data Handling
////////////////////////////////////////////////////////////////////////////////

//check and handle an ajax request (will exit early)
//or merge in related post data
$form->handleRequest();

$vars = array(
	"head" => 'color:$TITLECOLOR;font-size:$HEADERSIZE;padding-left:4px;',
	"list" => 'list-style:$LISTSTYLE $LISTPOSITION;$LISTPADDING;color:$LABELCOLOR;font-size:$LABELSIZE;',
	"box" => '$FONTFAMILYborder:$BORDERSIZE $BORDERSTYLE $BORDERCOLOR;height:$IFRAMEHEIGHT;overflow:auto;',
	"desc" => 'color:$DESCRIPTIONCOLOR;font-size:$DESCRIPTIONSIZE;padding-left:$DESCRIPTIONPADDING;padding-bottom:4px;',
	"audio" => 'font-size:$DESCRIPTIONSIZE;padding-left:$DESCRIPTIONPADDING;cursor:pointer;color:blue;text-decoration:underline;'
);
$categories = "1,2,3,4";
$iframe = '<iframe height=$IFRAMEHEIGHT width=$IFRAMEWIDTH frameborder=0 marginwidth=0 marginheight=0 src="$TINYURL/feedwidget.html?cust=$CUSTURL&i=$ITEMSTODISPLAY&c=$SMWIDGETCATEGORIES&v=$SMWIDGETVARS"></iframe>';

$postdata = $form->getData();
// replace any placeholders in the js with the form values
$vars = str_replace('$FONTFAMILY', "font-family:".$postdata["fontfamily"].";", $vars);
$vars = str_replace('$TITLECOLOR', "#".$postdata["titlecolor"], $vars);
$vars = str_replace('$BORDERSTYLE', $postdata["borderstyle"], $vars);
$vars = str_replace('$BORDERSIZE', $postdata["bordersize"]."px", $vars);
$vars = str_replace('$BORDERCOLOR', "#".$postdata["bordercolor"], $vars);
$vars = str_replace('$IFRAMEHEIGHT', ($postdata["iframeheight"]-($postdata["bordersize"]*2))."px", $vars);
$vars = str_replace('$HEADERSIZE', $postdata["headersize"]."px", $vars);
$vars = str_replace('$LISTSTYLE', $postdata["liststyle"], $vars);
$vars = str_replace('$LISTPOSITION', $postdata["listposition"], $vars);
$vars = str_replace('$LISTPADDING', ($postdata["listpadding"] !== "")?"padding-left:".$postdata["listpadding"]."px":"", $vars);
$vars = str_replace('$LABELSIZE', $postdata["labelsize"]."px", $vars);
$vars = str_replace('$LABELCOLOR', "#".$postdata["labelcolor"], $vars);
$vars = str_replace('$DESCRIPTIONSIZE', $postdata["descriptionsize"]."px", $vars);
$vars = str_replace('$DESCRIPTIONCOLOR', "#".$postdata["descriptioncolor"], $vars);
$vars = str_replace('$DESCRIPTIONPADDING', $postdata["descriptionpadding"]."px", $vars);

$iframe = str_replace('$IFRAMEHEIGHT', $postdata["iframeheight"], $iframe);
$iframe = str_replace('$IFRAMEWIDTH', $postdata["iframewidth"], $iframe);
$iframe = str_replace('$CUSTURL', getSystemSetting("urlcomponent"), $iframe);
$iframe = str_replace('$ITEMSTODISPLAY', $postdata["itemstodisplay"], $iframe);
$iframe = str_replace('$TINYURL', "http://".getSystemSetting("tinydomain","alrt4.me"), $iframe);

//check for form submission
if ($button = $form->getSubmit() && $form->isAjaxSubmit())
	$form->fireEvent(json_encode(array("vars" => $vars, "categories" => $categories, "iframe" => $iframe)));

// create the initial js
$iframe = str_replace('$SMWIDGETCATEGORIES', "'+smcategories+'", $iframe);
$iframe = str_replace('$SMWIDGETVARS', "'+encodeURIComponent(JSON.stringify(smwidgetvars))+'", $iframe);

$feedwidgetjs = "
<script type=\"text/javascript\">
	var smwidgetvars = ".json_encode($vars).";
	var smcategories = \"$categories\";
	document.write('$iframe');
</script>
";
////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$PAGE = "admin:settings";
$TITLE = _L('Feed Widget Display');

include_once("nav.inc.php");

// Optional Load Custom Form Validators
?>
<script type="text/javascript" src="script/json2.js"></script>
<script type="text/javascript">
<? Validator::load_validators(array()); ?>

// observe events fired in the form on submit
document.observe("dom:loaded", function() {
	$('feedwidgetstyle').observe('Form:Submitted',function(e){
		var data = JSON.parse(e.memo);

		// update the script box
		var iframehtml = data.iframe;
		iframehtml = iframehtml.replace("$SMWIDGETCATEGORIES","'+smcategories+'");
		iframehtml = iframehtml.replace("$SMWIDGETVARS","'+encodeURIComponent(JSON.stringify(smwidgetvars))+'");
		var feedjs = "<script type=\"text/javascript\">\n\tvar smwidgetvars = "+JSON.stringify(data.vars)+";\n\tvar smcategories = \""+data.categories+"\";\n\tdocument.write('"+iframehtml+"');\n<\/script>";
		$('feedjs').value = feedjs;

		// update the preview window
		iframehtml = data.iframe;
		iframehtml = iframehtml.replace("$SMWIDGETCATEGORIES",data.categories);
		iframehtml = iframehtml.replace("$SMWIDGETVARS",encodeURIComponent(JSON.stringify(data.vars)));
		$('feedpreview').innerHTML = iframehtml;
		
	});
});
</script>
<div>
	<div style="float:left;width:68%;">
<?

startWindow(_L('Feed Widget Display'));
echo $form->render();
endWindow();
?>
	</div>
	<div style="float:right;width:32%;">
<?
startWindow(_L('Feed Widget Preview'));
?>
		<div style="width:99%;">
			<textarea id="feedjs" wrap="off" style="width:100%;height:100px;"><?=escapehtml($feedwidgetjs)?></textarea>
		</div>
		<div id="feedpreview">
<?=$feedwidgetjs?>
		</div>
<?
endWindow();
?>
	</div>
</div>
<div style="clear:both;"></div>
<?
include_once("navbottom.inc.php");
?>