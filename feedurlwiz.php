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
require_once("obj/ColorPicker.val.php");
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
		$feedcategories = QuickQueryList("select id, name from feedcategory where id in (select feedcategoryid from userfeedcategory where userid=?) and not deleted order by name", true, false, array($USER->id));
		$args = array();
		$query = "select id, name from feedcategory where not deleted";
		if (count($feedcategories)) {
			$query .= " and id not in (";
			$count = 0;
			foreach ($feedcategories as $id => $name) {
				if ($count++ > 0)
					$query .= ",";
				$query .= "?";
				$args[] = $id;
			}
			$query .= ")";
			$feedcategories[] = "#-#";
		}
		$query .= " order by name";
		$otherfeedcategories = QuickQueryList($query, true, false, $args);
		
		foreach ($otherfeedcategories as $id => $name)
			$feedcategories[$id] = $name;
		
		$formdata = array(_L('Feed Settings'),
			"feedcategories" => array(
				"label" => _L("Feed categories"),
				"fieldhelp" => _L('Select which categories you wish to include in this feed.'),
				"value" => "",
				"validators" => array(
					array("ValRequired"),
					array("ValInArray", "values" => array_keys($feedcategories))),
				"control" => array("MultiCheckBox", "values"=>$feedcategories),
				"helpstep" => 1
			),
			"itemcount" => array(
				"label" => _L('Items to display'),
				"fieldhelp" => _L('Select the maximum number of items this feed should display.'),
				"value" => "10",
				"validators" => array(
					array("ValRequired"),
					array("ValNumber", "min" => 1, "max" => 100)),
				"control" => array("TextField", "size" => 5),
				"helpstep" => 1
			),
			"maxage" => array(
				"label" => _L('Max age (days)'),
				"fieldhelp" => _L('Choose the maximum age (in days) of messages displayed on this feed.'),
				"value" => "",
				"validators" => array(
					array("ValNumber", "min" => 0, "max" => 365)),
				"control" => array("TextField", "size" => 5),
				"helpstep" => 1
			)
		);
		
		$helpsteps = array(_L("Select the appropriate options for the feed you wish to generate:<p>Categories: These are the categories of messages which will be displayed as content in the feed. The top selections are your associated feed categories. On the bottom are other available categories.</p><p>Items: The maximum number of items the feed will display.</p><p>Max age: How old the oldest displayed message can be.</p>"));
		
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
		$feedurl .= "&cat=".implode(",", $this->parent->dataHelper("/feedoptions:feedcategories", false, array()));
		$feedurl .= "&items=".$this->parent->dataHelper("/feedoptions:itemcount","10");
		if ($this->parent->dataHelper("/feedoptions:maxage"))
			$feedurl .= "&age=".$this->parent->dataHelper("/feedoptions:maxage");
		
		// TODO: text explaining that it's fine to cancel at this step, or continue and configure the widget
		$formdata = array(
				_L('Feed URL'),
				"helptext" => array(
					"label" => "",
					"control" => array("FormHtml", "html" => _L("<p>Use and share this URL with anyone who wishes to follow the feed selections on the previous page. If this is all you need, feel free to cancel now.</p><p>If you wish to generate a feed widget to include in a web page, click on the Next button.</p>")),
					"helpstep" => 1
				),
				"feedurl" => array(
				"label" => _L('URL'),
				"fieldhelp" => _L('This is the URL you should use in your feed agregation software.'),
				"value" => $feedurl,
				"validators" => array(
					array("ValRequired")),
				"control" => array("TextField", "size" => "60"),
				"helpstep" => 1
			)
		);

		$helpsteps = array(_L("<p>Use and share this URL with anyone who wishes to follow the feed selections on the previous page. If this is all you need, feel free to cancel now.</p><p>If you wish to generate a feed widget to include in a web page, click on the Next button.</p>"));

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
			_L('Container settings'),
			"fontfamily" => array(
				"label" => _L('Font Family'),
				"fieldhelp" => _L('Display font to use when displaying the feed.'),
				"value" => "default",
				"validators" => array(
					array("ValRequired"),
					array("ValInArray", "values" => array_keys($fontfamilies))),
				"control" => array("SelectMenu", "values" => $fontfamilies),
				"helpstep" => 1
			),
			"iframeheight" => array(
				"label" => _L('Iframe height'),
				"fieldhelp" => _L('Height of the containing iframe.'),
				"value" => 480,
				"validators" => array(
					array("ValRequired"),
					array("ValNumber", "min" => 200, "max" => 2200)),
				"control" => array("TextField", "size" => 5),
				"helpstep" => 1
			),
			"iframewidth" => array(
				"label" => _L('Iframe width'),
				"fieldhelp" => _L('Width of the containing iframe.'),
				"value" => 300,
				"validators" => array(
					array("ValRequired"),
					array("ValNumber", "min" => 150, "max" => 800)),
				"control" => array("TextField", "size" => 5),
				"helpstep" => 1
			),
			"borderstyle" => array(
				"label" => _L('Border style'),
				"fieldhelp" => _L('Border style of the container.'),
				"value" => "solid",
				"validators" => array(
					array("ValRequired"),
					array("ValInArray", "values" => array_keys($borderstyles))),
				"control" => array("SelectMenu", "values" => $borderstyles),
				"helpstep" => 1
			),
			"bordersize" => array(
				"label" => _L('Border size'),
				"fieldhelp" => _L('Size of the border on the container.'),
				"value" => "1",
				"validators" => array(
					array("ValRequired"),
					array("ValInArray", "values" => array_keys($pxsizes))),
				"control" => array("SelectMenu", "values" => $pxsizes),
				"helpstep" => 1
			),
			"bordercolor" => array(
				"label" => _L('Border color'),
				"fieldhelp" => _L('Color of the border on the container.'),
				"value" => $_SESSION['colorscheme']['_brandtheme2'],
				"validators" => array(
					array("ValRequired"),
					array("ValColorPicker")),
				"control" => array("ColorPicker", "size" => 7),
				"helpstep" => 1
			),
			_L('Header'),
			"headersize" => array(
				"label" => _L('Text size'),
				"fieldhelp" => _L('Size of the font used when displaying the header.'),
				"value" => "18",
				"validators" => array(
					array("ValRequired"),
					array("ValInArray", "values" => array_keys($pxsizes))),
				"control" => array("SelectMenu", "values" => $pxsizes),
				"helpstep" => 2
			),
			"titlecolor" => array(
				"label" => _L('Header color'),
				"fieldhelp" => _L('Color of the font used when displaying the header.'),
				"value" => $_SESSION['colorscheme']['_brandprimary'],
				"validators" => array(
					array("ValRequired"),
					array("ValColorPicker")),
				"control" => array("ColorPicker", "size" => 7),
				"helpstep" => 2
			),
			_L('Item List'),
			"liststyle" => array(
				"label" => _L('List style'),
				"fieldhelp" => _L('List style to use when displaying the feed items.'),
				"value" => "circle",
				"validators" => array(
					array("ValRequired"),
					array("ValInArray", "values" => array_keys($liststyletypes))),
				"control" => array("SelectMenu", "values" => $liststyletypes),
				"helpstep" => 3
			),
			"listposition" => array(
				"label" => _L('List position'),
				"fieldhelp" => _L('Position of the list style elements relative to the feed item data.'),
				"value" => "outside",
				"validators" => array(
					array("ValRequired"),
					array("ValInArray", "values" => array_keys($listpositions))),
				"control" => array("SelectMenu", "values" => $listpositions),
				"helpstep" => 3
			),
			"listpadding" => array(
				"label" => _L('Left padding'),
				"fieldhelp" => _L('Left padding of the list elements.'),
				"value" => "25",
				"validators" => array(
					array("ValNumber", "min" => 0, "max" => 60)),
				"control" => array("TextField", "size" => 5),
				"helpstep" => 3
			),
			"labelsize" => array(
				"label" => _L('Label size'),
				"fieldhelp" => _L('Size of the font used when displaying the item labels.'),
				"value" => "16",
				"validators" => array(
					array("ValRequired"),
					array("ValInArray", "values" => array_keys($pxsizes))),
				"control" => array("SelectMenu", "values" => $pxsizes),
				"helpstep" => 3
			),
			"labelcolor" => array(
				"label" => _L('Label color'),
				"fieldhelp" => _L('Color of the font used when displaying the item labels.'),
				"value" => $_SESSION['colorscheme']['_brandtheme1'],
				"validators" => array(
					array("ValRequired"),
					array("ValColorPicker")),
				"control" => array("ColorPicker", "size" => 7),
				"helpstep" => 3
			),
			"descriptionsize" => array(
				"label" => _L('Description size'),
				"fieldhelp" => _L('Size of the font used when displaying the item description.'),
				"value" => "14",
				"validators" => array(
					array("ValRequired"),
					array("ValInArray", "values" => array_keys($pxsizes))),
				"control" => array("SelectMenu", "values" => $pxsizes),
				"helpstep" => 3
			),
			"descriptioncolor" => array(
				"label" => _L('Description color'),
				"fieldhelp" => _L('Color of the font used when displaying the item description.'),
				"value" => $_SESSION['colorscheme']['_brandtheme2'],
				"validators" => array(
					array("ValRequired"),
					array("ValColorPicker")),
				"control" => array("ColorPicker", "size" => 7),
				"helpstep" => 3
			),
			"descriptionpadding" => array(
				"label" => _L('Description padding'),
				"fieldhelp" => _L('Additional left padding to use when displaying the item description.'),
				"value" => "4",
				"validators" => array(
					array("ValRequired"),
					array("ValInArray", "values" => array_keys($pxsizes))),
				"control" => array("SelectMenu", "values" => $pxsizes),
				"helpstep" => 3
			),
			_L('Generate Preview'),
			"preview" => array(
				"label" => _L('Preview'),
				"fieldhelp" => _L('Click here to generate a preview of the feed which would be generated useing the above options.'),
				"value" => "",
				"validators" => array(),
				"control" => array("InpageSubmitButton", "submitvalue" => "preview", "name" => _L("Generate Preview"), "icon" => "tick"),
				"helpstep" => 4
			)
		);
		
		$helpsteps = array(
			_L("<p>Use these options to control the visual appearance of the container displaying your feed data.</p>"),
			_L("<p>Use these options to control the visual appearance of the main feed header.</p>"),
			_L("<p>Use these options to control the visual appearance of the news items displayed in your feed.</p>"),
			_L("<p>Generating a preview will cause the right side pane to update with the settings you have provided above. This will let you see what the widget should look like once you embed it in your website.</p>")
		);
		
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
		$html = '
		<h2 style="padding:8px;color:#'.$_SESSION['colorscheme']['_brandprimary'].';">'._L("Your feed selections are complete!").'</h2>
		<ul style="color:#'.$_SESSION['colorscheme']['_brandprimary'].';">
			<li>
				<h2 style="font-size:14px;color:black;">'._L("Use the following url in a feed agregator or other feed display application. Share it with anyone who is interested in the information displayed on this feed.").'</h2>
				<input type="text" readonly value="'.escapehtml($this->parent->dataHelper("/feedurl:feedurl")).'" style="background-color:#ffffff;cursor:text;width:99%;"/>
			</li>
			<li><h2 style="font-size:14px;color:black;">'._L("The following javascript snippet can be included in your web page to display the feed information described in the previous steps. Simply copy and paste it into your document where-ever you wish the feed to be displayed.").'</h2>
				<textarea readonly wrap="off" spellcheck="false" style="background-color:#ffffff;cursor:text;width:99%;height:12em;">'.escapehtml($_SESSION['wizard_feedurl']['feedwidgetjs']).'</textarea>
			</li>
		</ul>';
		
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
</script>";
	
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
<?	Validator::load_validators(array("ValColorPicker"));?>
	// observe events fired in the feedwidgetstyle form on submit
	document.observe("dom:loaded", function() {
		$('feedurlwiz-feedwidgetstyle').observe('Form:Submitted',function(e){
			var data = e.memo.evalJSON(true);

			// update the width of the preview area
			var feedjswidth = parseInt(data.preview_width) - 5;
			$('feedjs').setStyle({"width":feedjswidth+"px", "height":"12em"});
			
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
<table width="100%">
	<tr>
		<td valign="top" width="99%">
<?
}
startWindow($wizard->getStepData()->title);

echo $wizard->render();

endWindow();
if ($wizard->curstep == "/feedwidgetstyle") {
?>
		</td>
		<td valign="top">
<?
	startWindow(_L('Feed Widget Preview'));
?>
			<div style="padding-bottom:5px;padding-top:5px">
				<textarea id="feedjs" wrap="off" spellcheck="false" style="width:<?=($postdata["iframewidth"]-5)?>px;height:12em;"><?=escapehtml($feedwidgetjs)?></textarea>
			</div>
			<div id="feedpreview">
<?=$feedwidgetjs?>
			</div>
<?
	endWindow();
?>
		</td>
	</tr>
</table>
</div>
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
