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
require_once("obj/Wizard.obj.php");
require_once("obj/ColorPicker.fi.php");
require_once("obj/InpageSubmitButton.fi.php");

////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!getSystemSetting("_hasfeed", false) || !$USER->authorize('managesystem')) {
	redirect('unauthorized.php');
}

////////////////////////////////////////////////////////////////////////////////
// Action/Request Processing
////////////////////////////////////////////////////////////////////////////////
if (isset($_GET['debug']))
	$_SESSION['wizard_feedurl']['debug'] = true;

////////////////////////////////////////////////////////////////////////////////
// Optional Form Items And Validators
////////////////////////////////////////////////////////////////////////////////

////////////////////////////////////////////////////////////////////////////////
// Form Data
////////////////////////////////////////////////////////////////////////////////

class FeedUrlWiz_feedoptions extends WizStep {
	function getForm($postdata, $curstep) {
		// browse back to the first page? remove the generated feed url from session data
		unset($_SESSION['wizard_feedurl']["data"]["/feedurl"]["feedurl"]);
		
		global $USER;
		$userfeedcategories = QuickQueryList("select id, name from feedcategory where id in (select feedcategoryid from userfeedcategory where userid=?) and not deleted", true, false, array($USER->id));
		$args = array();
		$query = "select id, name from feedcategory where not deleted";
		if (count($userfeedcategories)) {
			$query .= " and id not in (";
			$count = 0;
			foreach ($userfeedcategories as $id => $name) {
				if ($count++ > 0)
					$query .= ",";
				$query .= "?";
				$args[] = $id;
			}
			$query .= ")";
		}
		$otherfeedcategories = QuickQueryList($query, true, false, $args);
		
		$formdata = array();
		$formdata[] = _L('Feed Settings');
		// User's associated feed categories
		if (count($userfeedcategories)) {
			$formdata["userfeedcategories"] = array(
				"label" => _L("My feed categories"),
				"fieldhelp" => _L('TODO: help me!'),
				"value" => array_keys($userfeedcategories),
				"validators" => array(
					array("ValInArray", "values" => array_keys($userfeedcategories))),
				"control" => array("MultiCheckBox", "values"=>$userfeedcategories),
				"helpstep" => 2
			);
			$otherfeedslabel = _L("Other feed categories");
		} else {
			$otherfeedslabel = _L("Feed categories");
		}
		// other feed categories that arn't associated with the user
		if (count($otherfeedcategories)) {
			$formdata["otherfeedcategories"] = array(
				"label" => $otherfeedslabel,
				"fieldhelp" => _L('TODO: help me!'),
				"value" => "",
				"validators" => array(
					array("ValInArray", "values" => array_keys($otherfeedcategories))),
				"control" => array("MultiCheckBox", "values"=>$otherfeedcategories),
				"helpstep" => 2
			);
		}
		$formdata["itemcount"] = array(
			"label" => _L('Items to display'),
			"fieldhelp" => _L('TODO: help me!'),
			"value" => "10",
			"validators" => array(
				array("ValRequired"),
				array("ValNumber", "min" => 1, "max" => 100)),
			"control" => array("TextField", "size" => 5),
			"helpstep" => 1
		);
		$formdata["maxage"] = array(
			"label" => _L('Max age (days)'),
			"fieldhelp" => _L('TODO: help me!'),
			"value" => "",
			"validators" => array(
				array("ValNumber", "min" => 0, "max" => 365)),
			"control" => array("TextField", "size" => 5),
			"helpstep" => 1
		);
		
		$helpsteps = array(_L("TODO: help me!"));
		
		return new Form("feedurlwiz-feedoptions",$formdata,$helpsteps);
	}
	function isEnabled($postdata, $step) {
		return true;
	}
}

class FeedUrlWiz_feedurl extends WizStep {
	function getForm($postdata, $curstep) {
		// construct feed url from form data on previous step
		$feedurl = "http://".getSystemSetting("tinydomain", "alrt4.me")."/feed.php?cust=".getSystemSetting("urlcomponent");
		$feedurl .= "&cat=".implode(",", array_merge($this->parent->dataHelper("/feedoptions:userfeedcategories", false, array()), $this->parent->dataHelper("/feedoptions:otherfeedcategories", false, array())));
		$feedurl .= "&items=".$this->parent->dataHelper("/feedoptions:itemcount","10");
		if ($this->parent->dataHelper("/feedoptions:maxage",false, false) !== false)
			$feedurl .= "&age=".$this->parent->dataHelper("/feedoptions:maxage");
		
		// TODO: text explaining that it's fine to cancel at this step, or continue and configure the widget
		$formdata = array(
				_L('Feed URL'),
				"feedurl" => array(
				"label" => _L('URL'),
				"fieldhelp" => _L('TODO: help me!'),
				"value" => $feedurl,
				"validators" => array(
					array("ValRequired")),
				"control" => array("TextField", "size" => 50),
				"helpstep" => 1
			)
		);

		$helpsteps = array(_L("TODO: help me!"));

		return new Form("feedurlwiz-feedurl",$formdata,$helpsteps);
	}
	function isEnabled($postdata, $step) {
		return true;
	}
}

class FeedUrlWiz_feedwidgetstyle extends WizStep {
	function getForm($postdata, $curstep) {
		$fontfamilies = array(
			'default' => "Browser default",
			'\'Times New Roman\', Times, serif' => "Times New Roman, Times, serif",
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
				"fieldhelp" => _L('TODO: help me!'),
				"value" => "default",
				"validators" => array(
					array("ValRequired"),
					array("ValInArray", "values" => array_keys($fontfamilies))),
				"control" => array("SelectMenu", "values" => $fontfamilies),
				"helpstep" => 1
			),
			"iframeheight" => array(
				"label" => _L('Iframe height'),
				"fieldhelp" => _L('TODO: help me!'),
				"value" => 480,
				"validators" => array(
					array("ValRequired"),
					array("ValNumber", "min" => 200, "max" => 2200)),
				"control" => array("TextField", "size" => 5),
				"helpstep" => 1
			),
			"iframewidth" => array(
				"label" => _L('Iframe width'),
				"fieldhelp" => _L('TODO: help me!'),
				"value" => 300,
				"validators" => array(
					array("ValRequired"),
					array("ValNumber", "min" => 150, "max" => 800)),
				"control" => array("TextField", "size" => 5),
				"helpstep" => 1
			),
			"borderstyle" => array(
				"label" => _L('Border style'),
				"fieldhelp" => _L('TODO: help me!'),
				"value" => "solid",
				"validators" => array(
					array("ValRequired"),
					array("ValInArray", "values" => array_keys($borderstyles))),
				"control" => array("SelectMenu", "values" => $borderstyles),
				"helpstep" => 1
			),
			"bordersize" => array(
				"label" => _L('Border size'),
				"fieldhelp" => _L('TODO: help me!'),
				"value" => "1",
				"validators" => array(
					array("ValRequired"),
					array("ValInArray", "values" => array_keys($pxsizes))),
				"control" => array("SelectMenu", "values" => $pxsizes),
				"helpstep" => 1
			),
			"bordercolor" => array(
				"label" => _L('Border color'),
				"fieldhelp" => _L('TODO: help me!'),
				"value" => $_SESSION['colorscheme']['_brandtheme2'],
				"validators" => array(
					array("ValRequired")),
				"control" => array("ColorPicker", "size" => 7),
				"helpstep" => 1
			),
			_L('Header'),
			"headersize" => array(
				"label" => _L('Text size'),
				"fieldhelp" => _L('TODO: help me!'),
				"value" => "18",
				"validators" => array(
					array("ValRequired"),
					array("ValInArray", "values" => array_keys($pxsizes))),
				"control" => array("SelectMenu", "values" => $pxsizes),
				"helpstep" => 1
			),
			"titlecolor" => array(
				"label" => _L('Header color'),
				"fieldhelp" => _L('TODO: help me!'),
				"value" => $_SESSION['colorscheme']['_brandprimary'],
				"validators" => array(
					array("ValRequired")),
				"control" => array("ColorPicker", "size" => 7),
				"helpstep" => 1
			),
			_L('Item List'),
			"liststyle" => array(
				"label" => _L('List style'),
				"fieldhelp" => _L('TODO: help me!'),
				"value" => "circle",
				"validators" => array(
					array("ValRequired"),
					array("ValInArray", "values" => array_keys($liststyletypes))),
				"control" => array("SelectMenu", "values" => $liststyletypes),
				"helpstep" => 1
			),
			"listposition" => array(
				"label" => _L('List position'),
				"fieldhelp" => _L('TODO: help me!'),
				"value" => "outside",
				"validators" => array(
					array("ValRequired"),
					array("ValInArray", "values" => array_keys($listpositions))),
				"control" => array("SelectMenu", "values" => $listpositions),
				"helpstep" => 1
			),
			"listpadding" => array(
				"label" => _L('Left padding'),
				"fieldhelp" => _L('TODO: help me!'),
				"value" => "25",
				"validators" => array(
					array("ValNumber", "min" => 0, "max" => 60)),
				"control" => array("TextField", "size" => 5),
				"helpstep" => 1
			),
			"labelsize" => array(
				"label" => _L('Label size'),
				"fieldhelp" => _L('TODO: help me!'),
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
				"fieldhelp" => _L('TODO: help me!'),
				"value" => "14",
				"validators" => array(
					array("ValRequired"),
					array("ValInArray", "values" => array_keys($pxsizes))),
				"control" => array("SelectMenu", "values" => $pxsizes),
				"helpstep" => 1
			),
			"descriptioncolor" => array(
				"label" => _L('Description color'),
				"fieldhelp" => _L('TODO: help me!'),
				"value" => $_SESSION['colorscheme']['_brandtheme2'],
				"validators" => array(
					array("ValRequired")),
				"control" => array("ColorPicker", "size" => 7),
				"helpstep" => 1
			),
			"descriptionpadding" => array(
				"label" => _L('Description padding'),
				"fieldhelp" => _L('TODO: help me!'),
				"value" => "4",
				"validators" => array(
					array("ValRequired"),
					array("ValInArray", "values" => array_keys($pxsizes))),
				"control" => array("SelectMenu", "values" => $pxsizes),
				"helpstep" => 1
			),
			"preview" => array(
				"label" => _L('Preview'),
				"fieldhelp" => _L('TODO: help me!'),
				"value" => "",
				"validators" => array(),
				"control" => array("InpageSubmitButton", "submitvalue" => "preview", "name" => _L("Generate Preview"), "icon" => "tick"),
				"helpstep" => 1
			)
		);
		
		$helpsteps = array(_L("TODO: help me!"));
		
		return new Form("feedurlwiz-feedwidgetstyle",$formdata,$helpsteps);
	}
	function isEnabled($postdata, $step) {
		return true;
	}
}

class FinishFeedUrlWiz extends WizFinish {

	function finish ($postdata) {
		// nothing to do
	}
	
	function getFinishPage ($postdata) {
		// TODO: instructions for use
		$html = '<input type="text" value="'.escapehtml($this->parent->dataHelper("/feedurl:feedurl")).'" style="width:100%;"/>
		<textarea id="feedjs" wrap="off" spellcheck="false" style="width:100%;height:145px;">'.escapehtml($_SESSION['wizard_feedurl']['feedwidgetjs']).'</textarea>';
		
		return $html;
	}
}

/**************************** wizard setup ****************************/
$wizdata = array(
	"feedoptions" => new FeedUrlWiz_feedoptions(_L("Feed Options")),
	"feedurl" => new FeedUrlWiz_feedurl(_L("Feed URL")),
	"feedwidgetstyle" => new FeedUrlWiz_feedwidgetstyle(_L("Feed Widget"))
);

$wizard = new Wizard("wizard_feedurl", $wizdata, new FinishFeedUrlWiz(_L("Finish")));
$wizard->doneurl = "start.php";
$wizard->handleRequest();

// if we are working with the widget style form, do some work to get the form data and return it to the browser for previewing
if ($wizard->curstep == "/feedwidgetstyle") {
	// default js variables
	$vars = array(
		"iframe" => '<iframe height=$IFRAMEHEIGHT width=$IFRAMEWIDTH frameborder=0 marginwidth=0 marginheight=0 src="$TINYURL/feedwidget.html?feedurl=$SMFEEDURL&head=$SMWIDGETHEAD&list=$SMWIDGETLIST&box=$SMWIDGETBOX&desc=$SMWIDGETDESC&audio=$SMWIDGETAUDIO"></iframe>',
		"head" => 'color:$TITLECOLOR;font-size:$HEADERSIZE;padding-left:4px;',
		"list" => 'list-style:$LISTSTYLE $LISTPOSITION;$LISTPADDING;color:$LABELCOLOR;font-size:$LABELSIZE;',
		"box" => '$FONTFAMILYborder:$BORDERSIZE $BORDERSTYLE $BORDERCOLOR;height:$BOXHEIGHT;overflow:auto;',
		"desc" => 'color:$DESCRIPTIONCOLOR;font-size:$DESCRIPTIONSIZE;padding-left:$DESCRIPTIONPADDING;padding-bottom:4px;',
		"audio" => 'font-size:$DESCRIPTIONSIZE;padding-left:$DESCRIPTIONPADDING;cursor:pointer;color:blue;text-decoration:underline;'
	);
	$feedurl = $wizard->dataHelper("/feedurl:feedurl");
	
	$postdata = $wizard->getForm()->getData();
	// replace any placeholders in the js with the form values
	$vars = str_replace('$IFRAMEHEIGHT', $postdata["iframeheight"], $vars);
	$vars = str_replace('$IFRAMEWIDTH', $postdata["iframewidth"], $vars);
	$vars = str_replace('$TINYURL', "http://".getSystemSetting("tinydomain","alrt4.me"), $vars);
	$vars = str_replace('$FONTFAMILY', (($postdata["fontfamily"] == "default")?"":"font-family:".$postdata["fontfamily"].";"), $vars);
	$vars = str_replace('$TITLECOLOR', "#".$postdata["titlecolor"], $vars);
	$vars = str_replace('$BORDERSTYLE', $postdata["borderstyle"], $vars);
	$vars = str_replace('$BORDERSIZE', $postdata["bordersize"]."px", $vars);
	$vars = str_replace('$BORDERCOLOR', "#".$postdata["bordercolor"], $vars);
	$vars = str_replace('$BOXHEIGHT', ($postdata["iframeheight"]-($postdata["bordersize"]*2))."px", $vars);
	$vars = str_replace('$HEADERSIZE', $postdata["headersize"]."px", $vars);
	$vars = str_replace('$LISTSTYLE', $postdata["liststyle"], $vars);
	$vars = str_replace('$LISTPOSITION', $postdata["listposition"], $vars);
	$vars = str_replace('$LISTPADDING', ($postdata["listpadding"] !== "")?"padding-left:".$postdata["listpadding"]."px":"", $vars);
	$vars = str_replace('$LABELSIZE', $postdata["labelsize"]."px", $vars);
	$vars = str_replace('$LABELCOLOR', "#".$postdata["labelcolor"], $vars);
	$vars = str_replace('$DESCRIPTIONSIZE', $postdata["descriptionsize"]."px", $vars);
	$vars = str_replace('$DESCRIPTIONCOLOR', "#".$postdata["descriptioncolor"], $vars);
	$vars = str_replace('$DESCRIPTIONPADDING', $postdata["descriptionpadding"]."px", $vars);
	
	//data to use when preview creates an event
	$onsubmitdata = array(
		"head" => $vars['head'],
		"list" => $vars['list'],
		"box" => $vars['box'],
		"desc" => $vars['desc'],
		"audio" => $vars['audio'],
		"feedurl" => $feedurl,
		"iframe" => $vars['iframe'],
		"preview_width" => $postdata["iframewidth"]
	);
	
	// create the widget js and stuff it in session data to use on the finish step
	$vars = str_replace('$SMWIDGETHEAD', "'+encodeURIComponent(smwidgethead)+'", $vars);
	$vars = str_replace('$SMWIDGETLIST', "'+encodeURIComponent(smwidgetlist)+'", $vars);
	$vars = str_replace('$SMWIDGETBOX', "'+encodeURIComponent(smwidgetbox)+'", $vars);
	$vars = str_replace('$SMWIDGETDESC', "'+encodeURIComponent(smwidgetdesc)+'", $vars);
	$vars = str_replace('$SMWIDGETAUDIO', "'+encodeURIComponent(smwidgetaudio)+'", $vars);
	$vars = str_replace('$SMFEEDURL', "'+encodeURIComponent(smfeedurl)+'", $vars);
	
	$feedwidgetjs = "
<script type=\"text/javascript\">
	var smwidgethead = \"".$vars['head']."\";
	var smwidgetlist = \"".$vars['list']."\";
	var smwidgetbox = \"".$vars['box']."\";
	var smwidgetdesc = \"".$vars['desc']."\";
	var smwidgetaudio = \"".$vars['audio']."\";
	var smfeedurl = \"".$feedurl."\";
	document.write('".$vars['iframe']."');
</script>
";
	
	$_SESSION['wizard_feedurl']['feedwidgetjs'] = $feedwidgetjs;
	
	//check for form submission
	if ($button = $wizard->getForm()->getSubmit() && $wizard->getForm()->isAjaxSubmit()) {
		$wizard->getForm()->fireEvent(json_encode($onsubmitdata));
	}
}
////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$PAGE = "admin:settings";
$TITLE = "Feed URL Generator";

require_once("nav.inc.php");

if ($wizard->curstep == "/feedwidgetstyle") {
?>
<script type="text/javascript">
	// observe events fired in the feedwidgetstyle form on submit
	document.observe("dom:loaded", function() {
		$('feedurlwiz-feedwidgetstyle').observe('Form:Submitted',function(e){
			var data = JSON.parse(e.memo);

			// update the width of the preview area
			var preview_width = parseInt(data.preview_width) + 26;
			$('widget_preview').setStyle({"float":"right","position":"relative","width":preview_width+"px"});
			$('wizard_form').setStyle({"marginRight":preview_width+"px"});
			
			// update the script box
			var iframehtml = data.iframe;
			iframehtml = iframehtml.replace("$SMWIDGETHEAD","'+encodeURIComponent(smwidgethead)+'");
			iframehtml = iframehtml.replace("$SMWIDGETLIST","'+encodeURIComponent(smwidgetlist)+'");
			iframehtml = iframehtml.replace("$SMWIDGETBOX","'+encodeURIComponent(smwidgetbox)+'");
			iframehtml = iframehtml.replace("$SMWIDGETDESC","'+encodeURIComponent(smwidgetdesc)+'");
			iframehtml = iframehtml.replace("$SMWIDGETAUDIO","'+encodeURIComponent(smwidgetaudio)+'");
			iframehtml = iframehtml.replace("$SMFEEDURL","'+encodeURIComponent(smfeedurl)+'");
			var feedjs = "<script type=\"text/javascript\">\n\tvar smwidgethead = \""+data.head+"\";\n\tvar smwidgetlist = \""+data.list+"\";\n\tvar smwidgetbox = \""+data.box+"\";\n\tvar smwidgetdesc = \""+data.desc+"\";\n\tvar smwidgetaudio = \""+data.audio+"\";\n\tvar smfeedurl = \""+data.feedurl+"\";\n\tdocument.write('"+iframehtml+"');\n<\/script>";
			$('feedjs').value = feedjs;

			// update the preview window
			iframehtml = data.iframe;
			iframehtml = iframehtml.replace("$SMWIDGETHEAD",encodeURIComponent(data.head));
			iframehtml = iframehtml.replace("$SMWIDGETLIST",encodeURIComponent(data.list));
			iframehtml = iframehtml.replace("$SMWIDGETBOX",encodeURIComponent(data.box));
			iframehtml = iframehtml.replace("$SMWIDGETDESC",encodeURIComponent(data.desc));
			iframehtml = iframehtml.replace("$SMWIDGETAUDIO",encodeURIComponent(data.audio));
			iframehtml = iframehtml.replace("$SMFEEDURL",encodeURIComponent(data.feedurl));
			$('feedpreview').innerHTML = iframehtml;
			
		});
	});
</script>
<div>
	<div id="widget_preview" style="float:right;width:324px;position:relative;">
<?
	startWindow(_L('Feed Widget Preview'));
?>
		<div style="width:99%;">
			<textarea id="feedjs" wrap="off" spellcheck="false" style="width:100%;height:145px;"><?=escapehtml($feedwidgetjs)?></textarea>
		</div>
		<div id="feedpreview">
<?=$feedwidgetjs?>
		</div>
<?
	endWindow();
?>
	</div>
	<div id="wizard_form" style="margin-right:324px;">
<?
}
startWindow($wizard->getStepData()->title);

echo $wizard->render();

endWindow();
if ($wizard->curstep == "/feedwidgetstyle") {
?>
	</div>
</div>
<div style="clear:both;"></div>
<?
}

if (isset($_SESSION['wizard_feedurl']['debug']) && $_SESSION['wizard_feedurl']['debug']) {
	startWindow("Feed Data");
	echo "<pre>";
	var_dump($_SESSION['wizard_feedurl']);
	//var_dump($_SERVER);
	echo "</pre>";
	endWindow();
}

require_once("navbottom.inc.php");
?>
