<?

// hack for pages not logged in (no session)
if (!isset($_SESSION['colorscheme'])) {
	// TODO these should come from customer display data (still brand the login pages)
	$theme = "classroom";
	$primary = "3e693f";
	$theme1 = "3e693f";
	$theme2 = "b47727";
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
body, table, form, select, input {
	font-family: verdana, arial, helvetica;
	font-size: 12px;
}

body {
	margin: 0px;

}


img {
	border: 0px;
}


a, a:link, a:active, a:visited {
	color:<?=$primary?>;
}

a:hover {
	color: <?=$primary?>;
}


.hoverlinks a {
	text-decoration: none;
}

.hoverlinks a:hover {
	text-decoration: underline;
}

.destlabel {
	color: <?=$primary?>;
}

.custname {
	font-size: 12px;
	color:<?=$primary?>;
	white-space: nowrap;
	text-align: right;
	margin: 0px;
	padding: 0px;
	margin-right: 5px;
}

/* **** main nav **** */

.navmenuspacer {
	margin-left: 10px;
}

.navmenu {
	width: 100%;
	height: 23px;
}

.navtab {
	height: 23px;
	position:relative;
	float: left;
	background: url('img/themes/<?=$theme?>/main_nav_tab_over.gif') no-repeat;
}

.navtab a {
	width: 98px;
	height: 23px;

	font-size: 12px;
	text-align: center;
	text-decoration: none;
	font-weight: bold;

	color:<?=$primary?>;

	overflow:hidden;
	display: block;
}

.navtab a:link, .navtab a:active, .navtab a:visited {
	color: <?=$primary?>;
}

.navtab img {
	width: 98px;
	height: 23px;
	border: 0px;
}


.navtab a:hover img {
	visibility:hidden;
}

.navtab span {
	position: absolute;
	left: 0px;
	top: 3px;
	text-align: center;
	width: 98px;
	margin: 0px;
	padding: 0px;
	cursor: pointer;

}


.applinks {
	padding-top:2px;
	padding-left: 5px;
	padding-right: 5px;
	font-size: 10px;
	white-space: nowrap;
}

.navlogoarea {
	background-color: <?=$topbg?>;
}

.navband1 {
	height: 6px;
	background: <?=$primary?>;
}

.navband2 {
	height: 2px;
	background: <?=$theme2?>;
	margin-bottom: 3px;
}

/* **** subnav **** */

.subnavmenu {
	height: 22px;
	width: 100%;
	background: url('img/themes/<?=$theme?>/chrome.png');
}

.subnavmenu .subnavtab {
	font-size: 10px;
	color: <?=$primary?>;
	margin-left: 10px;
	padding-left: 10px;
	padding-right: 10px;
	height: 22px;
	display: block;
	float: left;
}

.subnavmenu a:link, .subnavmenu a:active, .subnavmenu a:visited {
	color: <?=$primary?>;
}


.subnavmenu a div {
	padding-top: 4px;
}

.subnavmenu .active {
	background: url('img/themes/<?=$theme?>/chrome_light.png');
	border-left: 1px solid <?=$theme2?>;
	border-right: 1px solid <?=$theme2?>;

}


/* **** shortcuts **** */



.shortcutmenu {
	float: right;
	margin: 2px;
	margin-right: 15px;
	border: 1px outset;
	width: 100px;

	text-align: left;

	font-size: 10px;
}

.shortcuts {
	font-size: 9px;
	background: white;
}

.shortcuts a , .shortcuts a:link, .shortcuts a:active, .shortcuts a:visited {
	margin-left: 5px;
	display: block;
	color: <?=$primary?>;
}

.shortcuttitle {
	background: <?=$newfade1?>;
}



/* **** content **** */

.maincontent {
	margin-left: 15px;
	margin-right: 15px;
	margin-top: 5px;
}

.crumbs {
	float: right;
	font-size: 10px;
	margin-top: 5px;
	margin-right: 15px;
}
.crumbs img {
	vertical-align: bottom;
}


.pagetitle {
	margin-top: 10px;
	margin-left: 15px;
	font-size: 18px;
	font-weight: bold;
	color: <?=$primary?>;

}

.pagetitlesubtext {
	margin-left: 15px;
	font-size: 12px;
	font-style: italic;
	color: <?=$primary?>;
}


/* **** window **** */



.menucollapse {

	float: right;
	margin-top: 4px;
	margin-right: 5px;

	border: 2px outset white;

	width: 10px;
	height: 10px;

}

.window {
	width: 100%;
}

.windowtitle {
	font-size: 12px;
	font-weight: bold;
	padding-left: 5px;
	padding-top: 2px;
	color: <?=$primary?>;
}

.windowbody {
}

.windowtitle .hoverhelpicon {
	display: inline;
	float: none;
}

.windowtable {
}

/* **** button **** */


.button {
	text-decoration: none;
	height: 24px;

	float: left;
	background-color: transparent;
	border: 0px;
	width: auto;
	overflow: visible;
	vertical-align: middle;
	padding: 0 .25em 3px .25em;
}

.button .middle img {
		vertical-align: -3px;
		padding-right: 3px;
}

.button td {

}

.button a, .button td {
	text-decoration: none;
	color: <?=$primary?>;
	font-size: 10px;
	font-weight: bold;
	cursor: pointer;
}

.button .middle {
	white-space: nowrap;
	padding-right: 3px;
	background: url('img/themes/<?=$theme?>/button_mid.gif') repeat-x;
}

.button table {
	border-collapse: collapse;
	border-spacing: 0;
}

.button td {
	padding: 0;
}


.regbutton {
	text-decoration: none;
	float: left;
	background-color: transparent;
	border: 0px;
	width: auto;
	overflow: visible;
	vertical-align: middle;
	padding: 1px;
}


/* *********************************************** */


/* general styles */



input.text, input , select, textarea, table.form  {
	/*border: <?=$theme1?> 1px solid;*/
}

.windowRowHeader {
	background-color: <?=$newfade1?>;
	color: <?=$newfade3?>;
	width: 85px;
}

.chop {
	text-align: left;
	width: 100%;
	white-space:nowrap;
	overflow: hidden;
	color: #666666;
	border: 1px solid <?=$theme1?>;
}
div.gBranding {
	display:inline;
}

/* Scrolling window style settings */
div.scrollTableContainer {
	height: 220px; /* Set scrolling window size */
	overflow: auto; /* Turn on scrolling */
}
/* End of scrolling window style settings */

.list {
	color: <?=$newfade3?>;
	border: 1px solid <?=$theme2?>;
}

.listHeader {
	color: white;
	background-color: <?=$newfade2?>;
}

.listAlt {
	background-color: <?=$newfade1?>;
}

.topBorder {
	border-top: 1px solid <?=$theme2?>;
}

.bottomBorder {
	border-bottom: 1px solid <?=$theme2?>;
}

.border {
	border: 1px solid <?=$theme2?>;
}


.hoverhelpicon {
	margin-left: 5px;
	margin-right: 5px;
	display: block;
	float: left;
}


.hovertitle {
	font-weight: bold;
}


#logininfo {
	margin-left: 15px;
	text-align: left;
	font-size: 9px;;
	color: gray;
}

#termsinfo {
	margin-right: 20px;
	padding-left: 20px;
	float: right;
	text-align: right;
	font-size: 9px;;
	color: gray;
}

.alertmessage {
	margin-left: 25%;
	width: 50%;
	text-align: center;
	border: 5px double red;
}

.confirmnoticecontainer {
	margin: 5px;
	margin-left: 25%;
	width: 50%;
	border: 3px solid <?=$theme1?>;
}

.confirmnoticecontent {
	text-align: center;
	margin: 0px;
	border: 2px solid <?=$theme2?>;
	background: <?=$topbg?>;
}

.confirmnoticecontent hr {
	border: solid 1px <?=$theme2?>;
}

.accordiontitlediv {
	padding: 2px;
	padding-left: 10px;
	padding-top: 5px;
	cursor: pointer;
	color: <?=$primary?>;
	letter-spacing: 1px;
	background: <?=$topbg?> no-repeat;
	background-position: 2px 6px;
	border: solid 1px <?=$theme2?>;
}
.accordiontitlediv td.middle {
	text-align: left;
}
.accordiontitlediv td.left, .accordiontitlediv td.right {
	display: none;
}
.accordiontitledivexpanded {
	background-image: url('img/arrow_down.gif');
}
.accordiontitledivcollapsed {
	background-image: url('img/arrow_right.gif');
}
.accordiontitledivlocked {
	color: rgb(180,180,180);
}
.accordiontitleicon {
	margin-left: 5px;
	margin-right: 5px;
	vertical-align: middle;
}
.accordioncontentdiv {
	border: 1px solid <?=$theme2?>;
	padding: 5px;
	padding-bottom: 25px;
	margin-bottom: 1px;
}
.horizontaltabstabspane {
	margin: 0;
	border: 0;
	padding: 0;
}
.horizontaltabstitlediv {
	font-size: 12px;
	padding:0;
	cursor: pointer;
	color: <?=$primary?>;
	float: left;
	width: 20ex;
	margin:0;
}
.horizontaltabstitlediv table {
	table-layout: fixed;
	width: 100%;
}
.horizontaltabstitlediv td {
	padding: 0;
	margin: 0;
	text-align: left;
	white-space: nowrap;
	overflow: hidden;
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
	font-weight: bold;
	color: <?=$theme2?>;
	margin-bottom: -1px;
	border-bottom: solid 1px white;
}

.horizontaltabstitledivexpanded td.middle {
	background: <?=$theme2?> url('img/horizontaltab_middle.gif') repeat-x;
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
	font-size: 12px;
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
	margin-left: -1px;
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
	padding-right: 10px;
}
.verticaltabstabspane {
	padding: 0;
}
.verticaltabspanelspane {
	border-right: 2px solid <?=$theme2?>;
	padding; 0;
	margin-bottom: 1px;
	background: white;
	z-index: 1;
	position: relative;
}
table.SplitPane {
	border-collapse: collapse;
	width: 100%;
	margin: 0;
}
td.SplitPane {
	margin: 0;
	padding: 0;
	vertical-align: top;
}


#cke_reusableckeditor {
	border: 0;
	margin: 0;
	padding: 0;
}

#messagegroupformcontainer .MessageContentHeader {
	font-weight: bold;
	letter-spacing: 1px;
	font-size: 120%;
	display: block;
	margin: 2px;
	margin-left: 5px;
}

#messagegroupformcontainer #summary table {
	border-collapse: collapse;
}
#messagegroupformcontainer #summary th {
	font-weight: bold;
	vertical-align: top;
	padding: 2px;
	padding-right: 10px;
}
#messagegroupformcontainer #summary th.Destination {
	text-align: left;
}
#messagegroupformcontainer #summary th.Language {
	text-align: right;
}
#messagegroupformcontainer #summary td.StatusIcon {
	vertical-align: top;
	text-align: left;
	padding: 2px;
}

#messagegroupformcontainer #summary img.StatusIcon {
	cursor: pointer;
}

#messagegroupformcontainer form {
	margin: 0;
	padding: 0;
}

#messagegroupformcontainer iframe.UploadIFrame {
	overflow: hidden;
	width: 100%;
	margin: 0;
	padding: 0;
	height: 2em;
}

#messagegroupformcontainer div.MessageTextReadonly {
	padding: 2px;
	margin-top: 5px;
	border: solid 1px <?=$newfade1?>;
}

#messagegroupformcontainer #messagegroupbasics_name_fieldarea .formtableheader {
	width: 150px;
}
#messagegroupformcontainer td.verticaltabstabspane {
	width: 15%;
	white-space: nowrap;
}
#messagegroupformcontainer div.MessageBodyHeader {
	font-weight: bold;
	margin-left: 2px;
	float: left;
	padding-top: 4px;
}
#messagegroupformcontainer .accordioncontentdiv {
	padding: 2px;
}
#messagegroupformcontainer .accordioncontentdiv .radiobox {
	margin-right: 0;
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



.actionlink {
	border: 0px;
	white-space: nowrap;
	text-decoration: none;
	cursor: pointer;

	color: <?=$primary?>;;
}

.actionlink:hover{
	text-decoration: underline;
}

.actionlink img {
	border: 0px;
	padding: 0px;
	padding-top: 0.2em;
	padding-left: 3px;
	padding-right: 3px;
	margin: 0px;
}

.actionlinks {
	white-space: nowrap;
}

.feed h1 {
	margin-top: 3px;
	margin-bottom: 4px;
	font-size: 14px;
	font-weight: bold;
	color: <?=$primary?>;
}
.feed span {
	font-size: 12px;
	text-decoration: none;
}
.feedtitle a {
	font-size: 14px;
	font-weight: bold;
	color: <?=$primary?>;
}
.feedsubtitle {
	padding-top: 2px;
	padding-left: 4px;
	font-size: 10px;
	font-style: italic;
	color: <?=$primary?>;
}
.feedsubtitle img {
	padding-right: 4px;
}
.feedfilter {
	margin-left: 20px;
}
.feedfilter img {
	position: relative;
	top: 6px;
	padding-right: 4px;
}

.feed a {
	text-decoration: none;
}
.feed table {
	width: 100%;
	border: none;
	border-collapse: collapse;
}
.feed td {
	padding-bottom:5px;
	border-bottom: 1px solid <?=$theme2?>;
}


<? if ($theme == "classroom") { /* TODO move this to theme based css includes */ ?>

.navmenuspacer {
	margin-left: 0px;
	padding-left: 10px;
	background: url(img/themes/<?=$theme?>/main_nav_tab_bg.gif);
}

.subnavmenu .active {
	background: white;
	font-weight: bold;
	border-left: 1px solid #CCCCCC;
	border-right: 1px solid #CCCCCC;
}

.navband2 {
	margin-bottom: 0px;
}


<? } ?>
