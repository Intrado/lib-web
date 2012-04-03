<?

// hack for pages not logged in (no session)
if (!isset($_SESSION['colorscheme'])) {
	// TODO these should come from customer display data (still brand the login pages)
	$theme = "classroom";
	$primary = "484848";
	$theme1 = "000000";
	$theme2 = "444444";
	$globalratio = ".2";
} else {
	$theme = $_SESSION['colorscheme']['_brandtheme'];
	$primary = $_SESSION['colorscheme']['_brandprimary'];
	$theme1 = $_SESSION['colorscheme']['_brandtheme1'];
	$theme2 = $_SESSION['colorscheme']['_brandtheme2'];
	$globalratio = $_SESSION['colorscheme']['_brandratio'];
}

$fade1 = "E5E5E5";
$fade2 = "b9b9b9";
$fade3 = "848484";

$newfade1 = fadecolor($primary, $fade1, $globalratio);
$newfade2 = fadecolor($primary, $fade2, $globalratio);
$newfade3 = fadecolor($primary, $fade3, $globalratio);

$topbg = fadecolor($theme2, "FFFFFF", $globalratio/2);

$primary = "#" . $primary;
$theme1 = "#" . $theme1;
$theme2 = "#" . $theme2;

//Takes 2 hex color strings and 1 ratio to apply to to the primary:original
function fadecolor($primary, $fade, $ratio){
	$primaryarray = array(substr($primary, 0, 2), substr($primary, 2, 2), substr($primary, 4, 2));
	$fadearray = array(substr($fade, 0, 2), substr($fade, 2, 2), substr($fade, 4, 2));
	$newcolorarray = array();
	for($i = 0; $i<3; $i++){
		$newcolorarray[$i] = dechex(round(hexdec($primaryarray[$i]) * $ratio + hexdec($fadearray[$i])*(1-$ratio)));
	}
	$newcolor = "#" . implode("", $newcolorarray);
	return $newcolor;
}

?>

/*----- Normalize - this resets default styles applied by browsers -----*/

article, aside, details, figcaption, figure, footer, header, hgroup, nav, section { display: block; }
audio, canvas, video { display: inline-block; *display: inline; *zoom: 1; }
audio:not([controls]) { display: none; }
[hidden] { display: none; }

html { font-size: 100%; overflow-y: scroll; -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
body { margin: 0; font-size: 0.75em; line-height: 1.231; }
body, input, select, textarea, button { font-family: Verdana, "Helvetica Neue", helvetica,  Arial, sans-serif; color: #444 /* <?=$primary?> */ ; }

a { color: <?=$primary?>; text-decoration: none; }
a:visited { color: <?=$primary?>; }
a:hover { color: <?=$theme1?>; }
a:focus { outline: thin dotted; }
a:hover, a:active { outline: 0; }

b, strong { font-weight: bold; }
blockquote { margin: 1em 40px; }
dfn { font-style: italic; }
hr { display: block; height: 1px; border: 0; border-top: 1px solid #ccc; margin: 1em 0; padding: 0; }
ins { background: #ff9; color: #000; text-decoration: none; }
mark { background: #ff0; color: #000; font-style: italic; font-weight: bold; }
pre, code, kbd, samp { font-family: monospace, monospace; _font-family: 'courier new', monospace; font-size: 1em; }
pre { white-space: pre; white-space: pre-wrap; word-wrap: break-word; }
q { quotes: none; }
q:before, q:after { content: ""; content: none; }
small { font-size: 85%; }
sub, sup { font-size: 75%; line-height: 0; position: relative; vertical-align: baseline; }
sup { top: -0.5em; }
sub { bottom: -0.25em; }
ul, ol { margin: 1em 0; padding: 0 0 0 40px; }
dd { margin: 0 0 0 40px; }
nav ul, nav ol { list-style: none; list-style-image: none; margin: 0; padding: 0; }
img { border: 0; -ms-interpolation-mode: bicubic; vertical-align: middle; }
svg:not(:root) { overflow: hidden; }
figure { margin: 0; }

form { margin: 0; }
fieldset { border: 0; margin: 0; padding: 0; }
label { cursor: pointer; }
legend { border: 0; *margin-left: -7px; padding: 0; }
button, input, select, textarea { font-size: 100%; margin: 0; vertical-align: baseline; *vertical-align: middle;}
button, input { line-height: normal; *overflow: visible; }
table button, table input { *overflow: auto; }
button, input[type="button"], input[type="reset"], input[type="submit"] { cursor: pointer; -webkit-appearance: button; }
input[type="checkbox"], input[type="radio"] { box-sizing: border-box; }
input[type="search"] { -webkit-appearance: textfield; -moz-box-sizing: content-box; -webkit-box-sizing: content-box; box-sizing: content-box; }
input[type="search"]::-webkit-search-decoration { -webkit-appearance: none; }
button::-moz-focus-inner, input::-moz-focus-inner { border: 0; padding: 0; }
textarea { overflow: auto; vertical-align: top; resize: vertical; }
/*
input:valid, textarea:valid {  }
input:invalid, textarea:invalid { background-color: #f0dddd; }
*/
table { border-collapse: collapse; border-spacing: 0; }
td, th { vertical-align: top; padding: .5em; }


/*----- Basic stylesheet for all themes, each theme has it's own specific styles in the theme folder. See example.css for guide and ideas -----*/

/*----- Banner -----*/

/*-----
banner_logo is always floated left across all themes
banner_links_wrap is always floated right in each theme 
banner_custname is positioned absolutely so it can be positioned for each theme
link styles and active states for links are in the individual theme stylesheet
-----*/

.banner_logo { display: inline; float: left; }
.banner_logo h1 { display: none; }
.banner_logo a { display: block; }
.banner_links_wrap { display: inline; float: right; }
.banner_custname { position: absolute; }

.banner_links { list-style: none; margin: 0; padding: 0; } 
.banner_links li { display: inline; float: left; }
		
.banner_links a:hover { text-decoration: underline; } 
.ie6 .banner { height: 44px; }
.ie6 .banner_links { height: 20px; }
.ie6 .banner_logo_wrap { width: 360px; } 
.ie6 .banner_links_wrap { width: 272px; } 
.ie6 .banner_logo { height: 44px; } 


/*----- Navigation -----*/

/*-----
primary_nav given basic default colour, this is overridden within specific theme folder
navtabs are floated left and have had a reset of padding, margin and list-style-type for all themes
navshortcut is floated right and has default styles for all themes, links inside have default styles across all themes
-----*/

.primary_nav { background: #ccc; }
.navtabs { float: left; display: inline; list-style-type: none; margin: 0px; padding: 0px; }
.navtabs li { float: left; display: inline; cursor: pointer; }

.navshortcut { float: right; display: inline;  }
.shortcuts { font-size: 12px; background: #fff; }
.shortcuttitle { background: #d9d9d9; color: #212121; font-size: 13px; margin: 0 1px; padding: 5px 10px; border-bottom: 1px solid #fff; }
.shortcuts a, .shortcuts a:visited { background: #f1f1f1; margin: 0 1px; padding: 5px 10px; display: block; color: #333; border-bottom: 1px solid #fff; }
.shortcuts a:hover { background: #b3b3b3; color: #333; text-decoration: none; }

/*-----
subnavtabs have had a reset of padding, margin and list-style-type for all themes
subnavtabs li and a have been set up to list horizontal for all themes with basic styling
further colours for subnav links are in the specific theme folder
-----*/

.subnavtabs { width: 100%; list-style-type: none; margin: 0px; padding: 0px; }
.subnavtabs li { float: left; display: inline; font-size: 10px; margin-left: 10px; }
.subnavtabs a { display: block; text-decoration: none; }
.subnavtabs a:hover { text-decoration: underline; }

.applinks { padding-top:2px; padding-left: 5px; padding-right: 5px; font-size: 10px; white-space: nowrap; }


/*----- global styles -----*/

.hoverlinks a { text-decoration: none; }
.hoverlinks a:hover { text-decoration: none; }
.destlabel { color: <?=$primary?>; }
.custname { font-size: 12px; color:<?=$primary?>; white-space: nowrap; text-align: right; margin: 0px; padding: 0px; margin-right: 5px; }

/*----- manager styles -----*/

.manager_logo { margin: 10px 0; }
.maincontent { margin: 0 1%; }
form#search { margin: 10px 0; }

table.imagelink td { text-align: center; font-size: 10px; }
table.imagelink td a { display: block; }


/*----- Content sections -----*/

/*-----
content_wrap has a default padding to give it space across all themes
csec is a default style for all content sections so that they can be floated to fit different theme layout
secwindow is container for windows and table content set to fit screen width for fluid layout
-----*/

.content_wrap { margin: 10px 1%; }
.csec { float: left; display: inline; }
.secwindow { width: 100%; }

.pagetitle { margin-bottom: .5em; font-size: 21px; }
.pagetitlesubtext { margin-left: 1em; margin-bottom: .5em; font-size: 1em; font-style: italic; color: <?=$primary?>; }

.crumbs { float: right; font-size: 10px; margin-top: 5px; margin-right: 15px; }
.crumbs img { vertical-align: bottom; }

/*-----
big_button_wrap is simple container for the two new job buttons
further styles for buttons and links are defined in each specific theme within the relevant theme folder
-----*/

.big_button_wrap img { display: block; }
.big_button_wrap img:hover { cursor: pointer; }
.newjob a, .emrjob a { display: block; }

.regbutton				{ text-decoration: none;	float: left;	background-color: transparent;	border: 0px;	width: auto;	overflow: visible;	vertical-align: middle;	padding: 1px; }
.htmlradiobutton	{	width: 100%; border: 2px outset; background-color: #fff; color: #000; margin-left: 0px; }

.menucollapse { float: right; margin-top: 4px; margin-right: 5px; border: 2px outset white; width: 10px; height: 10px; }

.window {	background-color: #ffffff; border: 1px solid #999; margin: 0 0 15px 0; }
.window_title_wrap 	{ padding: 0; position: relative;} 
.window_title_l, .window_title_r 	{ display: none; }
			
.window_body_wrap { position: relative; margin: 0; padding: 20px 1%; }
.window_body_l, .window_body_r { display: none; } /* hide from newer browsers... */
.window_body { position: relative; }
table.window_body { width: 100%; }
.window_foot_wrap { display: none; } /* hide from newer browsers... */

.window_aside { width: 18%; margin: 0 2% 0 0; }
.window_main { width: 78%; margin: 0 0 0 2%; }

.feedfilter {	margin: 0; padding: 0; list-style: none; }
.feedfilter li { line-height: 20px; padding-top: 5px; }
.feedfilter li a img { margin-top: -5px; margin-right: 5px; }


/*----- Buttons, for buttons within the windows and tables, further styling is in the specific theme file for each theme -----*/
   
button { color: <?=$primary?>; }
.btn { margin: 2px 5px; padding: 0; border: none; background: transparent; font-weight: bold; cursor: pointer; }
.btn_wrap { white-space: nowrap; position: relative; } 
.btn_left, 
.btn_right, 
.btn_middle { padding-top: 4px; display: block; height: 19px; }
.btn_left, 
.btn_right { position: absolute; top: 0; }
.btn_left { left: 0; background: url(themes/<?=$theme?>/button_left.gif) no-repeat center center; width: 9px; }
.btn_right { right: 0; background: url(themes/<?=$theme?>/button_right.gif) repeat-x center center; width: 9px;}
.btn_middle { margin: 0 9px; padding-right: 2px;  background: url(themes/<?=$theme?>/button_mid.gif) repeat-x center center;}
			
.btn_middle img { margin-top: -3px; padding: 0 3px 0 0; }

.btn:hover .btn_left { background: url(themes/<?=$theme?>/button_left_over.gif) no-repeat center center; }
.btn:hover .btn_middle { background: url(themes/<?=$theme?>/button_mid_over.gif) repeat-x center center; }
.btn:hover .btn_right { background: url(themes/<?=$theme?>/button_right_over.gif) no-repeat center center; }

.btn_hide { display: none !important; visibility: hidden; } /* for hidden buttons! */

.import_file_data .btn { font-size: 10px; line-height: 15px; margin: 0 0 8px 6px; padding: 0; }
.import_file_data .btn_middle { padding: 3px 5px 0 5px; height: 20px; }


/*----- Start page -----*/

.content_col, .content_col_side, .content_col_main { display: block; float: left; }
.content_col_side { width: 15%; min-width: 180px;  }
.content_col_main { width: 85%; }


/*----- Timeline, effects the timeline on the homepage to try and keep it constant for all themes -----*/

/*-----
timeline_table is the container that will stretch to fit space available
table_middle contains the timeline details set to 84% so it looks ok at 1024 width and will stretch for larger screens
table_left and table_right have the arrow controls, set to 8% width for 1024 screens and will still look good on larger screens 
-----*/

.timeline_table { width: 100%; margin-bottom: 3em; }
.timeline_table_middle { width: 84%; }
.timeline_table_left, .timeline_table_right { width: 8%; }
#_backward, #_forward { display: block; width: 51px; height: 53px; margin: 9px 0 0; }
#_backward { background: url('img/timelinearrowleft.gif') no-repeat; }
#_forward { background: url('img/timelinearrowright.gif') no-repeat; }

/*----- actionlinks control the tooltip links found on recent activity next to the message -----*/

.actionlink_tools { width: 100px; position: absolute; top: .5em; right: 0; }
.actionlink_tools:hover { cursor: pointer; }

.content_feed_notification { margin: 10px 10px 0 0; }
.content_feed_left, .content_feed_right { display: block; float: left; }
.content_feed_left { overflow: hidden; margin-right: 10px; }

#t_refresh { float: left; display: inline; width: 25%; }
#t_zoom { float: left; display: inline; width: 46%; margin: 0 2%; padding: 10px 0 0; text-align: center; }
#t_legend { float: right; display: inline; width: 25%; padding: 7px 0 0; text-align: right; }


/* +----------------------------------------------------------------+
	 | window content                                                 |
   +----------------------------------------------------------------+ */


/* === feed / left panel === */

.content_side {  margin: 0 1% .5% 0; padding: 0 1% .5% 0; width: 15%;  min-width: 180px; border-right: 1px dotted <?=$theme2?>; }
.content_main { width: 80%; padding: 0 1% 0 1%; }
.content_row { display: block; width: 100%; min-width: 600px; position: relative; margin: 0 auto; margin-bottom: 1em; }
#feeditems .content_row { border-bottom: 1px solid #ccc; padding: 0 0 1em 0; }
.content_col_1 { width: 16%; }
.content_col_2 { width: 32%; }
.content_col_3 { width: 48%; }
.content_col_4 { width: 64%; }
.content_col_5 { width: 80%; }
.content_col_6 { width: 96%; }

.start_page, db_fl { display: block; float: left; }

.content_recordcount_top, 
.content_recordcount_btm { display: block; padding-bottom: .5em; }
.content_recordcount_top {  }
.content_recordcount_btm { padding-top: .5em; }
.content_recordcount { text-align: right; margin: 0;}

.content_feed { position: relative; width: 100%; color: #444; }
.content_feed { /*margin-bottom: 35px;*/  }
.content_feed img {  } 
.content_feed span, .feed_detail { display: block; padding: 0 0 5px 0; width: 100%; }
.content_feed .msg_icon { float: left; display: inline; margin: 0 10px 0 0; }
.content_feed .feed_wrap { float: left; display: inline; margin: 0; }
.content_feed .actionlinks { display: block; float: right; }
	
.feed, .feedtitle, .feed_title, .feedtitle a { color: #444; /* <?=$primary?> */ }
.feedtitle, .feed_title { font-size: 14px; font-weight: bold;  }
.feed_icon { width: 48px; vertical-align: top; }
.feed_actions { width: 100px; }
.feedtitle a:hover, .feed_title:hover { text-decoration: underline; }	
.feed_btn_wrap { 	border-bottom: 1px dashed #ccc;	margin: 0 0 10px 0;	padding: 0 0 10px 0; }
.feed_item { border-bottom: 1px solid #ccc; padding: 1em 0.5em; }
.feed_item td { padding: 1em 0.5em; }



/* +----------------------------------------------------------------+
	 | general styles                                                 |
   +----------------------------------------------------------------+ */

input.text, input , select, textarea, table.form  {
	/*border: <?=$theme1?> 1px solid;*/
}

.windowRowHeader { background-color: #d4d4d4; color: <?=$newfade3?>; width: 85px; }

.chop { text-align: left; width: 100%; white-space:nowrap; overflow: hidden; color: #666666; border: 1px solid <?=$theme1?>; }
div.gBranding { display:inline; }

/* Scrolling window style settings */
div.scrollTableContainer {
	height: 220px; /* Set scrolling window size */
	overflow: auto; /* Turn on scrolling */
}
/* End of scrolling window style settings */



/*----- action links - edit, copy etc used througout site -----*/

ul.actionlinks { padding: 0; margin: 0; list-style-type: none; }
ul.actionlinks li { float: left; display: inline; padding: 0 4px 0 0; border-right: 1px solid #aaa; }
ul.actionlinks li:last-child { border: none; }
ul.actionlinks li a { display: block; font-size: 11px; line-height: 18px; border: 0px; cursor: pointer; color: #484848; }
ul.actionlinks li a:hover { color: #000; }
ul.actionlinks li img { padding: 0 4px; }

/*----- items need to appear in vertical list on homepage -----*/

.tooltip ul.actionlinks li { float: none; display: block; border: none; }

/*----- removed the padding and background colour set elsewhere -----*/

table.list ul.actionlinks li { margin: 0 0 4px 0; padding: 0 4px 0 0; }
table.list ul.actionlinks li:nth-child(2n) { background: transparent; }


/* +----------------------------------------------------------------+
	 | lists / tables                                                 |
   +----------------------------------------------------------------+ */

.listAlt { background-color: <?=$newfade1?>; }

.topBorder { border-top: 1px solid <?=$theme2?>; }
.bottomBorder { border-bottom: 1px solid <?=$theme2?>; }
.border { border-top: 1px solid #ccc; }

#activeUsersContainer { margin: 10px 0 0; }
.usersearch { float: left; display: inline; }
.pagenavinfo { float:right; }
.pagenavselect { float: right; height:16px; }
.scrolltable { float: left; display: inline; width: 100%; margin: 10px 0 0; }

.tableprogressbar { float:right; width:16px; height:16px; margin-right: 5px }
.togglers {  } /* think this is for search criteria */

.table_controls { margin: 0 0 10px 0; }
.table_controls .sortby { float: left; display: inline; }
.table_controls .sortby h3 { margin: 0 0 5px 0; }
.table_controls .pagenavinfo { float: right; display: inline; padding: 20px 0 0; }
.table_controls .fieldvis { float: left; display: inline; padding: 24px 0 0 10px; }
 
/* +---------------------------------+
	 | table overrides for list styles |
   +---------------------------------+ */

table.list { width: 100%; border-left: 1px solid #ccc; border-bottom: 1px solid #ccc; border-top: 1px solid #ccc; }
table.list tr { background-color: #fbfbfb; }
table.list tr.listHeader { background-color: #d4d4d4; vertical-align: top; }
table.list tr.listAlt { background-color: #f1f1f1; }
table.list th, table.list td { text-align: left; border-right: 1px solid #ccc; color: #484848; }
table.list td { padding: 5px; }
table.list ul { list-style: none; padding: 0; margin: 0; }
table.list ul li { padding: 6px 8px; }

table.usagelist { width: 100%; margin: 0; border: 1px solid #ccc; background-color: #f8f8f8;  }
table.usagelist tr:nth-child(even) { background-color: #f1f1f1; }

table > .listHeader > th 	{ text-align: left;  color: white; }
.listHeader > .label { padding: 2px; font-weight: normal; }

.form_table { width: 100%; }

.tcol10		{ width: 10%; }
.tcol20		{ width: 20%; }
.tcol30		{ width: 30%; }
.tcol40		{ width: 40%; }
.tcol50		{ width: 50%; }
.tcol60		{ width: 60%; }
.tcol70		{ width: 70%; }
.tcol80		{ width: 80%; }
.tcol90		{ width: 90%; }
.tcol100	{ width: 100%; }

.hoverhelpicon { margin: 0 5px;	}

.hovertitle { font-weight: bold; }

#footer { padding: 15px 0; margin: 0 1%; }
#logininfo { float: left; display: inline; text-align: left; font-size: 9px; color: gray; }
#termsinfo { float: right; display: inline; text-align: right; font-size: 9px; color: gray; }

.alertmessage {
	margin-left: 25%;
	width: 50%;
	text-align: center;
	border: 5px double red;
}

.confirmnoticecontainer { margin: 5px 0 10px 25%; width: 50%; }
.confirmnoticecontent { background: <?=$topbg?>; margin: 0px; padding: 10px; text-align: center; font-size: 1.2em; 
-webkit-border-radius: 5px; -moz-border-radius: 5px; border-radius: 5px;
-moz-background-clip: padding; -webkit-background-clip: padding-box; background-clip: padding-box; 
-webkit-box-shadow: 0px 1px 2px 0px #999; -moz-box-shadow: 0px 1px 2px 0px #999; box-shadow: 0px 1px 2px 0px #999; }

.confirmnoticecontent hr {
	border: solid 1px <?=$theme2?>;
}


.horizontaltabstabspane {
	margin: 0;
	border: 0;
	padding: 0;
}
.horizontaltabstitlediv {
	padding:0;
	cursor: pointer;
	color: <?=$primary?>;
	float: left;
	margin:0;
}
.horizontaltabstitlediv table {
}
.horizontaltabstitlediv td {
	padding: 0;
	margin: 0;
	text-align: left;
	white-space: nowrap;
	font-size: 90%;
}

.horizontaltabstitlediv td.middle {
	padding-right: 3px;
	padding-left: 0;
	background: <?=$theme1?> url('img/horizontaltab_middle.gif') repeat-x;
}
.horizontaltabstitlediv td.right {
	background: <?=$theme1?> url('img/horizontaltab_right.gif') no-repeat;
	width: 14px;
	height: 26px;
}
.horizontaltabstitlediv td.left {
	background: <?=$theme1?> url('img/horizontaltab_left.gif') no-repeat;
	width: 14px;
	height: 26px;
}

.horizontaltabstitledivexpanded {
	margin-bottom: -1px;
	border-bottom: solid 1px white;
}

.horizontaltabstitledivexpanded td.middle {
	background: <?=$theme2?> url('img/horizontaltab_middle.gif') repeat-x;
	font-weight: bold;
	color: <?=$primary?>;
	font-size: 110%;
}
.horizontaltabstitledivexpanded td.left {
	background: <?=$theme2?> url('img/horizontaltab_left.gif') repeat-x;
}
.horizontaltabstitledivexpanded td.right {
	background: <?=$theme2?> url('img/horizontaltab_right.gif') repeat-x;
}
.horizontaltabstitledivcollapsed {
	color: <?=$theme1?>;
}
.horizontaltabstitleicon {
	margin-left: 5px;
	margin-right: 5px;
	vertical-align: middle;
}
.horizontaltabscontentdiv {
	clear: both;
}
.horizontaltabspanelspane {
	border: 1px solid <?=$theme2?>;
	margin:2px;
	margin-top: 0;
	padding: 5px;
	padding-bottom: 25px;
	margin-bottom: 1px;
	background: white;
}
.verticaltabstitlediv {
	cursor: pointer;
	color: <?=$primary?>;
	margin: 0;
	padding: 0;
	margin-top: 2px;
}
.verticaltabstitlediv table {
	table-layout: fixed;
	width: 100%;
}
.verticaltabstitlediv td {
	padding: 0;
	margin: 0;
	overflow: hidden;
	white-space: nowrap;
	font-size: 90%;
}

.verticaltabstitlediv td.left {
	display:none;
}
.verticaltabstitlediv td.middle {
	padding-right: 3px;
	background: <?=$theme1?> url('img/verticaltab_middle.gif') repeat-x;
}
.verticaltabstitlediv td.right {
	background: <?=$theme1?> url('img/verticaltab_right.gif') no-repeat;
	width: 14px;
	height: 26px;
}
.verticaltabstitledivexpanded {
	margin-left: -2px;
	background: white;
	position: relative;
	z-index: 2;
}
.verticaltabstitledivexpanded td.middle {
	background: <?=$theme2?> url('img/verticaltab_middle.gif') repeat-x;
	font-size: 110%;
	font-weight: bold;
	color: <?=$primary?>;
}
.verticaltabstitledivexpanded td.right {
	background: <?=$theme2?> url('img/verticaltab_right.gif') no-repeat;
}

.verticaltabstitledivcollapsed {
	font-weight: normal;
	color: <?=$theme1?>;
}
.verticaltabstitleicon {
	margin-left: 5px;
	margin-right: 5px;
	vertical-align: middle;
}
.verticaltabscontentdiv {
	padding: 0;
	margin: 2px;
	margin-right: 0;
	padding-bottom: 25px;
}
.verticaltabstabspane {
	padding: 0;
}
.verticaltabspanelspane {
	border-right: 2px solid <?=$theme2?>;
	padding: 0;
	margin-bottom: 1px;
	background: white;
	z-index: 1;
	position: relative;
}
table.SplitPane {
	border-collapse: collapse;
	width: 98%;
	margin: 0;
}
td.SplitPane {
	margin: 0;
	padding: 0;
	vertical-align: top;
}

.sortheader {
	color: white;
	background-color: <?=$newfade2?>;
}
.sortheader:link {
	text-decoration: none;
	color: white;
	background-color: <?=$newfade2?>;
}

.floatingreportdata {
	float: left;
	margin: 5px;
	text-align: center;
	font-weight: bold;
}

.helpclick {
	cursor: help;
}

.voicereplyclickableicon {
	cursor: pointer;
	float: right;
}

.voicereplyicon {
	float: right;
}



div.autocomplete {
  position:absolute;
  width:250px;
  background-color:white;
  border:1px solid <?=$theme2?>;
  margin:0;
  padding:0;
}
div.autocomplete ul {
  list-style-type:none;
  margin:0;
  padding:0;
}
div.autocomplete ul li.selected { background-color: <?=$newfade1?>;}
div.autocomplete ul li {
  list-style-type:none;
  display:block;
  margin:0;
  padding:2px;
  height:32px;
  cursor:pointer;
}

#control_overlay {
	background-color:#000;
}
.modal {
	background-color:#fff;
	padding:10px;
	border:10px solid #333;
}

.modalwindow {  
	background-color:#fff;
	border:10px solid #333;
	
	background-position:top left;
} 

.modalwindow .window_contents {
	margin-top:0px;
	padding: 4%;
	width: 92%;
	min-height: 200px;
	overflow: auto;
}

.modalwindow .window_header {
	background-color:#333;
	text-align:center;  
}

.modalwindow .window_title {  
	background-color:graytext;
	padding: 2px;
	font-size: 16px;
	color: #fff;
}

.modalwindow .window_close {  
	display:block;  
	position:absolute;  
	top:4px;  
	right:5px;  
	height:13px;  
	width:13px;  
	background-image:url("img/modalwindowclose.gif");  
	cursor:pointer;  
	cursor:hand;  
}

.messagegrid {
text-align: center;
}
.messagegrid .messagegridheader {
	padding: 2px;
}
.messagegrid .messagegridheader img{
	margin-bottom: -3px;
}
.messagegrid .messagegridlanguage{
	text-align: right;
}

.messagegrid .tinybutton{
	margin:0 auto;
	text-align:left;
	padding-top:3px;
	width:33px;
	height:19px;
	cursor: pointer;
	background:url('img/tinybutton.png') no-repeat right center;
}

.keepalive img {
	padding-right: 5px;
}



/*----- New Job and Emergency job table styles -----*/

.htmlradiobuttonbigcheck img { width: 34px; height: 34px; margin-left: -17px; padding: 0 0 0 50%; }
.htmlradiobuttonbigcheck ol { list-style-type: none; margin: 0; padding: 0 0 0 5px; }
.htmlradiobuttonbigcheck ol li { background: url(img/icons/bullet_blue.gif) center left no-repeat; padding: 0 0 0 20px; line-height: 21px; }

.creation_method { float: left; display: inline; width: 94px; }


/*----- Survey styles -----*/

.survey_banner { margin: 20px 25px; }
.survey_logo { float: left; display: inline; }
.survey_custname { float: right; display: inline; }
.survey_wrap { margin: 0 25px 20px; }

.survey_title { background: #DBDBDB; padding: 15px; font: 18px/21px georgia, arial, sans-serif; text-shadow: 0 1px 0 #fff; 
-webkit-border-radius: 5px; -moz-border-radius: 5px; border-radius: 5px; 
-moz-background-clip: padding; -webkit-background-clip: padding-box; background-clip: padding-box; }

.survey_question { background: #f1f1f1; margin: 0 0 10px 0; padding: 15px; font: 16px/21px georgia, arial, sans-serif; text-shadow: 0 1px 0 #fff; 
-webkit-border-radius: 5px; -moz-border-radius: 5px; border-radius: 5px; 
-moz-background-clip: padding; -webkit-background-clip: padding-box; background-clip: padding-box; }
.survey_question p { margin: 0; padding: 0 0 10px 0; }
.survey_question div { float: left; display: inline; }
.question_num { color: #777; }
.question_choice { border-left: 1px dashed #c2c2c2; margin: 0 0 0 12px; padding: 0 0 0 12px; }
.question_choice span { margin: 0 10px 0 0; }
.question_choice input { margin: 0 5px 0 0; }

input.submit_survey { float: right; padding: 6px 13px; font-size: 16px; color: #e8f0de; border: solid 1px #538312;
	background: #64991e;
	background: -webkit-gradient(linear, left top, left bottom, from(#7db72f), to(#4e7d0e));
	background: -moz-linear-gradient(top,  #7db72f,  #4e7d0e);
	filter:  progid:DXImageTransform.Microsoft.gradient(startColorstr='#7db72f', endColorstr='#4e7d0e');
	text-shadow: 0 1px 1px rgba(0,0,0,.3);
	-webkit-border-radius: .5em; 
	-moz-border-radius: .5em;
	border-radius: .5em; }

input.submit_survey:hover { 
	-webkit-box-shadow: 0 1px 4px rgba(0,0,0,.8); -moz-box-shadow: 0 1px 4px rgba(0,0,0,.8); box-shadow: 0 1px 4px rgba(0,0,0,.8); }

input.submit_survey:active {
	background: #538018;
	background: -webkit-gradient(linear, left top, left bottom, from(#6b9d28), to(#436b0c));
	background: -moz-linear-gradient(top,  #6b9d28,  #436b0c);
	filter:  progid:DXImageTransform.Microsoft.gradient(startColorstr='#6b9d28', endColorstr='#436b0c'); 
	-webkit-box-shadow: inset 0 1px 2px rgba(0,0,0,.4); -moz-box-shadow: inset 0 1px 2px rgba(0,0,0,.4); box-shadow: inset 0 1px 2px rgba(0,0,0,.4); }
	
	
/*----- Admin settings list styles -----*/

.linkslist { float: left; display: inline; list-style-type: none; margin: 0; padding: 0 15px; }
.linkslist li { font-size: 13px; padding: 5px 0; }
.linkslist li.heading { font-size: 14px; font-weight: bold; border-bottom: 1px solid #ccc; }

/* +----------------------------------------------------------------+
	 | non-semantic helper classes                                    |
   +----------------------------------------------------------------+ */

.ir { display: block; border: 0; text-indent: -999em; overflow: hidden; background-color: transparent; background-repeat: no-repeat; text-align: left; direction: ltr; }
.ir br { display: none; }
.hidden { display: none !important; visibility: hidden; }
.visuallyhidden { border: 0; clip: rect(0 0 0 0); height: 1px; margin: -1px; overflow: hidden; padding: 0; position: absolute; width: 1px; }
.visuallyhidden.focusable:active, .visuallyhidden.focusable:focus { clip: auto; height: auto; margin: 0; overflow: visible; position: static; width: auto; }
.invisible { visibility: hidden; }

/* === micro clearfix - http://nicolasgallagher.com/micro-clearfix-hack/ === */
.cf:before, .cf:after { content: ""; display: table; }
.cf:after { clear: both; }
.cf { zoom: 1; }


/*----- IE specific styles -----*/

/*----- Scrolling window style settings -----*/
.ie6 div.scrollTableContainer {	margin-right: 20px; }
.ie7 div.scrollTableContainer {	overflow-x: hidden; }

/*----- fixes the link wrapping in the banner -----*/
.ie7 .banner_links_wrap { width: 50%; }
.ie7 .banner_links { float: right; }

/*----- fixes the column layout wrapping in main content window -----*/
.ie7 .window_aside { margin: 0 1.8% 0 0; }


/* +----------------------------------------------------------------+
   | Theme include                                                  |
   +----------------------------------------------------------------+ */
   
<?
$themecssfilename = "themes/$theme/style.php"; //FIXME rename css files to themes/$theme/css.php - had to rename to style.php, css.php wasn't being included. sd. 
if ( is_readable($themecssfilename) ) {
	include_once($themecssfilename);
} else {
	include_once("themes/default/style.php"); //FIXME should be themes/default/css.php - had to rename to style.php, css.php wasn't being included. sd.
}
?>


/* +----------------------------------------------------------------+
   | Print styles                                                   |
   +----------------------------------------------------------------+ */

@media print {
  * { background: transparent !important; color: black !important; text-shadow: none !important; filter:none !important; -ms-filter: none !important; } 
  a, a:visited { text-decoration: underline; }
  a[href]:after { content: " (" attr(href) ")"; }
  abbr[title]:after { content: " (" attr(title) ")"; }
  .ir a:after, a[href^="javascript:"]:after, a[href^="#"]:after { content: ""; } 
  pre, blockquote { border: 1px solid #999; page-break-inside: avoid; }
  thead { display: table-header-group; }
  tr, img { page-break-inside: avoid; }
  img { max-width: 100% !important; }
  @page { margin: 0.5cm; }
  p, h2, h3 { orphans: 3; widows: 3; }
  h2, h3 { page-break-after: avoid; }
}
