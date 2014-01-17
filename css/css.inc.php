/* http://meyerweb.com/eric/tools/css/reset/ 
   v2.0 | 20110126
   License: none (public domain)
*/

html, body, div, span, applet, object, iframe,
h1, h2, h3, h4, h5, h6, p, blockquote, pre,
a, abbr, acronym, address, big, cite, code,
del, dfn, em, img, ins, kbd, q, s, samp,
small, strike, strong, sub, sup, tt, var,
b, u, i, center,
dl, dt, dd, ol, ul, li,
fieldset, form, label, legend,
table, caption, tbody, tfoot, thead, tr, th, td,
article, aside, canvas, details, embed, 
figure, figcaption, footer, header, hgroup, 
menu, nav, output, ruby, section, summary,
time, mark, audio, video {
	margin: 0;
	padding: 0;
	border: 0;
	font-size: 100%;
	font: inherit;
	vertical-align: baseline;
}
/* HTML5 display-role reset for older browsers */
article, aside, details, figcaption, figure, 
footer, header, hgroup, menu, nav, section {
	display: block;
}
body {
	line-height: 1;
}
ol, ul {
	list-style: none;
}
blockquote, q {
	quotes: none;
}
blockquote:before, blockquote:after,
q:before, q:after {
	content: '';
	content: none;
}
table {
	border-collapse: collapse;
	border-spacing: 0;
}

/*----- Custom Normalize css : http://necolas.github.com/normalize.css/ -----*/

article, aside, details, figcaption, figure, footer, header, hgroup, nav, section { display: block; }
audio, canvas, video { display: inline-block; *display: inline; *zoom: 1; }
audio:not([controls]) { display: none; }
[hidden] { display: none; }

html { font-size: 100%; overflow-y: scroll; -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
body { margin: 0; font-size: 0.75em; line-height: 1.231; }
body, input, select, textarea, button { font-family: Verdana, "Helvetica Neue", helvetica,  Arial, sans-serif; color: #444 /*#3e693f*/; }

a { color: #3e693f; text-decoration: none; }
a:hover { color: #346799; }
a:focus { outline: thin dotted; }
a:hover, a:active { outline: 0; }
a.cust_link { color: #1e7398; }
a.cust_link:hover { color: #56a8cc; text-decoration: underline; }

b, strong { font-weight: bold; }
hr { display: block; height: 1px; border: 0; border-top: 1px solid #ccc; margin: 1em 0; padding: 0; }
small { font-size: 85%; }
img { border: 0; -ms-interpolation-mode: bicubic; vertical-align: middle; }

label { cursor: pointer; }
button, input, select, textarea { font-size: 100%; margin: 0; vertical-align: baseline;}
button, input { line-height: normal; }
button, input[type="button"], input[type="reset"], input[type="submit"] { cursor: pointer; -webkit-appearance: button; }
input[type="checkbox"], input[type="radio"] { margin: 6px 5px 0 0; box-sizing: border-box; }
input[type="search"] { -webkit-appearance: textfield; -moz-box-sizing: content-box; -webkit-box-sizing: content-box; box-sizing: content-box; }
input[type="search"]::-webkit-search-decoration { -webkit-appearance: none; }
button::-moz-focus-inner, input::-moz-focus-inner { border: 0; padding: 0; }
textarea { overflow: auto; vertical-align: top; resize: vertical; }

table { margin: 0 0 5px; }
td, th { vertical-align: top; padding: .15em; }
td > table td { padding: 0; }


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
.shortcuts a { background: #f1f1f1; margin: 0 1px; padding: 5px 10px; display: block; color: #333; border-bottom: 1px solid #fff; }
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
.destlabel { color: #3e693f; }
.custname { font-size: 12px; color:#3e693f; white-space: nowrap; text-align: right; margin: 0px; padding: 0px; margin-right: 5px; }

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
.csec { /*float: left; display: inline;*/ }
.secwindow { width: 100%; }

.pagetitle { margin-bottom: .5em; font-size: 21px; }
.pagetitlesubtext { margin-left: 1em; margin-bottom: .5em; font-size: 1em; font-style: italic; color: #3e693f; }

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

.window {	background-color: #ffffff; border: 1px solid #999; }
.window_title_wrap 	{ padding: 0; position: relative;} 
.window_title_l, .window_title_r 	{ display: none; }
			
.window_body_wrap { position: relative; margin: 0; }
.window_body_l, .window_body_r { display: none; } /* hide from newer browsers... */
.window_body { position: relative; padding: 20px 1%; }
table.window_body { width: 100%; }

.window_foot_wrap { margin: 0 0 15px; }

.window_aside { width: 18%; margin: 0 2% 0 0; }
.window_main { width: 77%; margin: 0 0 0 2%; }

.feedfilter {	margin: 0; padding: 0; list-style: none; }
.feedfilter li { line-height: 20px; padding-top: 5px; }
.feedfilter li a img { margin-top: -5px; margin-right: 5px; }
.feedfilter li.feedselected a {color: #000000; font-weight: bold; }


.caller_id { float: left; display: inline; width: 70.5%; }
.approved_id { float: left; display: inline; width: 28.5%; margin: 0 0 0 1%; }


/*----- Buttons, for buttons within the windows and tables, further styling is in the specific theme file for each theme -----*/
   
button { color: #3e693f; background: none; border: none; margin: none; padding: none; }
.btn { padding: 0; border: none;/* background: transparent;*/ font-weight: bold; cursor: pointer; }
.btn_wrap { white-space: nowrap; position: relative; display: block; } 
.btn_left, 
.btn_right, 
.btn_middle { display: block; height: 23px; line-height: 23px; font-size: 11px; }
.btn_left, 
.btn_right { position: absolute; top: 0; }
.btn_left { left: 0; background: url(themes/newui/button_left.gif) no-repeat center center; width: 9px; }
.btn_right { right: 0; background: url(themes/newui/button_right.gif) repeat-x center center; width: 9px;}
.btn_middle { margin: 0 9px; padding-right: 2px;  background: url(themes/newui/button_mid.gif) repeat-x center center;}
			
.btn_middle img { margin-top: -4px; padding: 0 3px 0 0; }

.btn:hover .btn_left { background: url(themes/newui/button_left_over.gif) no-repeat center center; }
.btn:hover .btn_middle { background: url(themes/newui/button_mid_over.gif) repeat-x center center; }
.btn:hover .btn_right { background: url(themes/newui/button_right_over.gif) no-repeat center center; }

.btn_hide { display: none !important; visibility: hidden; } /* for hidden buttons! */

.import_file_data .btn { font-size: 10px; line-height: 15px; margin: 0 0 8px 6px; padding: 0; }


/*----- Start page -----*/

.content_col, .content_col_side, .content_col_main { display: block; float: left; }
.content_col_side { width: 15%; min-width: 180px;  }
.content_col_main { width: 85%; }

.dashboard_graph {float: left;}

/*----- Timeline, effects the timeline on the homepage to try and keep it constant for all themes -----*/

/*-----
timeline_table is the container that will stretch to fit space available
table_middle contains the timeline details set to 84% so it looks ok at 1024 width and will stretch for larger screens
table_left and table_right have the arrow controls, set to 8% width for 1024 screens and will still look good on larger screens 
-----*/

.timeline_table { width: 100%; margin-bottom: 3em; }
.timeline_table_middle .canvasleft,
.timeline_table_middle .canvasright { height: 72px; width: 2%; }
.timeline_table_left, .timeline_table_right { width: 51px; }
#_backward, #_forward { display: block; width: 51px; height: 53px; margin: 9px 0 0; }
#_backward { background: url('img/timelinearrowleft.gif') no-repeat; }
#_forward { background: url('img/timelinearrowright.gif') no-repeat; }

#t_refresh { float: left; display: inline; width: 25%; }
#t_zoom { float: left; display: inline; width: 46%; margin: 0 2%; padding: 10px 0 0; text-align: center; }
#t_zoom .zoom_in { background: url('img/icons/fugue/magnifier_zoom.gif') left center no-repeat; padding: 2px 0 2px 19px; }
#t_zoom .reset { background: url('img/icons/fugue/arrow_circle_225.gif') left center no-repeat; padding: 2px 0 2px 19px; }
#t_zoom .zoom_out { background: url('img/icons/fugue/magnifier_zoom_out.gif') left center no-repeat; padding: 2px 0 2px 19px; }
#t_legend { float: right; display: inline; width: 25%; padding: 7px 0 0; text-align: right; }
#t_legend span { background: url('img/largeicons/tiny20x20/flag.jpg') left center no-repeat; padding: 0 0 0 23px; }


/*----- actionlinks control the tooltip links found on recent activity next to the message -----*/

.actionlink_tools { width: 100px; position: absolute; top: .5em; right: 0; }
.actionlink_tools:hover { cursor: pointer; }

.content_feed_notification { margin: 10px 10px 0 0; }
.content_feed_left { display: inline; float: left; overflow: hidden; }
.content_feed_right { margin: 0 0 0 55px; }


/* +----------------------------------------------------------------+
	 | window content                                                 |
   +----------------------------------------------------------------+ */


/* === feed / left panel === */

.content_side {  margin: 0 1% .5% 0; padding: 0 1% .5% 0; width: 15%;  min-width: 180px; border-right: 1px dotted #3399ff; }
.content_main { width: 80%; padding: 0 1% 0 1%; }
.content_row { width: 100%; min-width: 600px; position: relative; margin: 0 auto; margin-bottom: 1em; }
.window_main .content_row { height: 65px; }
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
.content_feed span, .feed_detail { display: block; padding: 0 0 5px 0; width: 100%; }
.content_feed .msg_icon { float: left; display: inline; margin: 0 10px 0 0; width: 48px;}
.content_feed .feed_wrap { float: left; display: inline; margin: 0; }
.content_feed .actionlinks { float: right; display: inline; text-align: right; }
.content_feed .no_content, td.no_content .feedtitle { display: block; padding: 12px 0 0; }
.feed, .feedtitle, .feed_title, .feedtitle a { #3e693f }
.feedtitle, .feed_title { font-size: 14px; font-weight: bold;  }
.feed_icon { width: 48px; vertical-align: top; }
.feed_actions { width: 100px; }
.feedtitle a:hover, .feed_title:hover { text-decoration: underline; }	
.feed_btn_wrap { 	border-bottom: 1px dashed #ccc;	margin: 0 0 10px 0;	padding: 0 0 10px 0; }
.feed_item { border-bottom: 1px solid #ccc; padding: 1em 0.5em; margin-bottom: 5px; }
.feed_item td { padding: 1em 0.5em; }

a.btn.feedcategorymapping:link, a.btn.feedcategorymapping:hover, a.btn.feedcategorymapping:visited, a.btn.feedcategorymapping:active {
    color: #333;
    text-decoration: none;
}
#feedcategorymapping button[type=submit] {
    margin-left: 160px;
}

/* RCIeditor */
span.cke_toolgroup {height: 27px;}
a.cke_dialog_tab {height: 26px;}
a.cke_button {height: 25px;}


/* +----------------------------------------------------------------+
	 | general styles                                                 |
   +----------------------------------------------------------------+ */

input.text, input , select, textarea, table.form  {
	/*border: #346799 1px solid;*/
}

.windowRowHeader { background-color: #d4d4d4; color: #767F76; width: 85px; }

.chop { text-align: left; width: 100%; white-space:nowrap; overflow: hidden; color: #666666; border: 1px solid #346799; }
div.gBranding { display:inline; }

/* Scrolling window style settings */
div.scrollTableContainer {
	height: 220px; /* Set scrolling window size */
	overflow: auto; /* Turn on scrolling */
	position: relative;
}

div.horizontalScrollTableContainer {
	height: auto;
}
/* End of scrolling window style settings */



/*----- action links - edit, copy etc used througout site -----*/

.actionlink {
	padding: 1px 5px 1px 5px; font-size: 11px;
	white-space: nowrap;
	text-decoration: none;
	cursor: pointer;
	font-size: 11px;

	color: #3e693f;
	margin: 2px 0;
	display: inline-block;
}

a.actionlink {
	/*border-right: 1px solid #aaa;*/
}

.actionlink:hover{
	text-decoration: underline;
}

.actionlink img {
	border: 0px;
	padding: 0px;
	padding-right: 3px;
	margin: 0px;
	vertical-align: middle;
}

.actionlinks { white-space: nowrap; }
.actionlinks a { border-left: 1px solid #aaa; }
.actionlinks a.actionlink:first-child { border: none; }

/*----- items need to appear in vertical list on homepage -----*/

.tooltip ul.actionlinks li { float: none; display: block; border: none; }

/*----- removed the padding and background colour set elsewhere -----*/

table.list ul.actionlinks li { padding: 0 4px 0 0; }
table.list ul.actionlinks li:nth-child(2n) { background: transparent; }


/* +----------------------------------------------------------------+
	 | lists / tables                                                 |
   +----------------------------------------------------------------+ */

.listAlt { background-color: #C4CCC4; }

.topBorder { border-top: 1px solid #3399ff; }
.bottomBorder { border-bottom: 1px solid #3399ff; }
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
table.list > tr { background-color: #fbfbfb; }
table.list tr.listHeader { background-color: #d4d4d4; vertical-align: top; }
table.list tr.listAlt { background-color: #f1f1f1; }
table.list th, table.list td { text-align: left; vertical-align: top; border-right: 1px solid #ccc; color: #484848; }
table.list tr.listHeader td { color: #fff; border-top: 1px solid #ccc; }
table.list td { padding: 5px; }
table.list td.day { text-align: center; vertical-align: middle; }
table.list ul { list-style: none; padding: 0; margin: 0; }
table.list ul li { padding: 6px 8px; }
table.list td table td { border: none; }

table.usagelist { width: 100%; border: 1px solid #ccc; background-color: #f8f8f8;  }
table.usagelist tr:nth-child(even) { background-color: #f1f1f1; }

table > .listHeader > th 	{ text-align: left;  color: white; }
.listHeader > .label { padding: 2px; font-weight: normal; }

table td.feed_preview { vertical-align: top; }
table.schedule { width: 218px; }
table.schedule th, table.schedule td { width: 30px; padding: 5px 0; text-align: center; }


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

#footer { padding: 0 0 15px 0; }
#logininfo { float: left; display: inline; text-align: left; font-size: 9px; line-height: 13px; color: gray; }
#termsinfo { float: right; display: inline; text-align: right; font-size: 9px; line-height: 13px; color: gray; }

.alertmessage {
	margin-left: 25%;
	width: 50%;
	text-align: center;
	border: 5px double red;
}

.confirmnoticecontainer { margin: 5px 0 10px 25%; width: 50%; }
.confirmnoticecontent { background: #EBF5FF; margin: 0px; padding: 10px; text-align: center; font-size: 1.2em; 
-webkit-border-radius: 5px; -moz-border-radius: 5px; border-radius: 5px;
-moz-background-clip: padding; -webkit-background-clip: padding-box; background-clip: padding-box; 
-webkit-box-shadow: 0px 1px 2px 0px #999; -moz-box-shadow: 0px 1px 2px 0px #999; box-shadow: 0px 1px 2px 0px #999; }

.confirmnoticecontent hr {
	border: solid 1px #3399ff;
}


.horizontaltabstabspane {
	margin: 0;
	border: 0;
	padding: 0;
	height: 26px;
}
.horizontaltabstitlediv {
	padding:0;
	cursor: pointer;
	color: #3e693f;
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
	padding: 5px 3px 0 0; 
	background: #346799 url('img/horizontaltab_middle.gif') repeat-x;
}
.horizontaltabstitlediv td.right {
	background: #346799 url('img/horizontaltab_right.gif') no-repeat;
	width: 14px;
	height: 26px;
}
.horizontaltabstitlediv td.left {
	background: #346799 url('img/horizontaltab_left.gif') no-repeat;
	width: 14px;
	height: 26px;
}

.horizontaltabstitledivexpanded {
	margin-bottom: -1px;
	border-bottom: solid 1px white;
}

.horizontaltabstitledivexpanded td.middle { background: #3399ff url('img/horizontaltab_middle.gif') repeat-x; padding: 5px 0 0; font-weight: bold; color: #3e693f; font-size: 110%; }
	
.horizontaltabstitledivexpanded td.left {
	background: #3399ff url('img/horizontaltab_left.gif') repeat-x;
}
.horizontaltabstitledivexpanded td.right {
	background: #3399ff url('img/horizontaltab_right.gif') repeat-x;
}
.horizontaltabstitledivcollapsed {
	color: #346799;
}
.horizontaltabstitleicon {
	margin-left: 5px;
	margin-right: 5px;
	vertical-align: middle;
}
.horizontaltabscontentdiv {
	/*clear: both;*/
}
.horizontaltabspanelspane {
	border: 1px solid #3399ff;
	margin:2px;
	margin-top: 0;
	padding: 5px;
	padding-bottom: 25px;
	margin-bottom: 1px;
	background: white;
}
.verticaltabstitlediv {
	cursor: pointer;
	color: #3e693f;
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
	background: #346799 url('img/verticaltab_middle.gif') repeat-x;
}
.verticaltabstitlediv td.right {
	background: #346799 url('img/verticaltab_right.gif') no-repeat;
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
	background: #3399ff url('img/verticaltab_middle.gif') repeat-x;
	font-size: 110%;
	font-weight: bold;
	color: #3e693f;
}
.verticaltabstitledivexpanded td.right {
	background: #3399ff url('img/verticaltab_right.gif') no-repeat;
}

.verticaltabstitledivcollapsed {
	font-weight: normal;
	color: #346799;
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
	border-right: 2px solid #3399ff;
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
	background-color: #A0A9A1;
}
.sortheader:link {
	text-decoration: none;
	color: white;
	background-color: #A0A9A1;
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
  border:1px solid #3399ff;
  margin:0;
  padding:0;
}
div.autocomplete ul {
  list-style-type:none;
  margin:0;
  padding:0;
}
div.autocomplete ul li.selected { background-color: #C4CCC4;}
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

.modalwindow .window_contents ul { list-style-type: disc; margin: 0 0 8px 10px; padding: 0 0 0 20px; font-size: 13px; }
.modalwindow .window_contents ol { list-style-type: decimal; margin: 0 0 8px 10px; padding: 0 0 0 20px; font-size: 13px; }
.modalwindow .window_contents p { margin: 0 0 8px 10px; font-size: 13px; }
.modalwindow .window_contents em { font-style: italic; }

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


/*----- Bootstrap modal styles -----*/

.modal-backdrop { background-color: #000000; bottom: 0; left: 0; position: fixed; right: 0; top: 0; opacity: 0.8; z-index: 1040; filter: alpha(opacity=80); }

.modal { position: fixed; left: 50%; top: 50%; max-height: 500px; width: 700px; margin: -250px 0 0 -350px; padding: 0; background-clip: padding-box;  background-color: #FFFFFF; 
border: 1px solid rgba(0, 0, 0, 0.3); -webkit-border-radius: 6px; border-radius: 6px; box-shadow: 0 3px 7px rgba(0, 0, 0, 0.3); z-index: 1050; }

.modal-header { position: relative; background: #fdfdfd; font-size: 21px; margin: 0; padding: 15px; border-bottom: 1px solid #ddd; -webkit-border-radius: 6px 6px 0 0; border-radius: 6px 6px 0 0; }

.modal-body { max-height: 300px; padding: 15px; overflow: auto; }
.modal-body .existing-lists label { float: none; display: block; width: 100%; margin: 0; padding: 8px; text-align: left; line-height: 22px; border-top: 1px solid #ddd; }
.modal-body .existing-lists label:hover { background: #f5f5f5; }
.modal-body .existing-lists label:first-child { border: none; }
.modal-body .existing-lists input[type="checkbox"] { margin: 0 5px 0 0; }
.modal-body .existing-lists span { float: none; padding: 0 5px; }
.modal-body .add-rule .btn { float: none; width: 200px; }

.modal-footer { position: relative; background: #f5f5f5; font-size: 21px; margin: 0; padding: 15px; text-align: right; border-top: 1px solid #ddd; -webkit-border-radius: 0 0 6px 6px; border-radius: 0 0 6px 6px; }
.modal-footer .btn { float: none; display: inline-block; }
.modal-footer .btn-primary { color: #fff; border: 1px solid #0039ab; 
	background-color: #006DCC;
	background-image: -webkit-gradient(linear, left top, left bottom, from(#0088CC), to(#0044CC)); 
  background-image: -webkit-linear-gradient(top, #0088CC, #0044CC); 
  background-image:    -moz-linear-gradient(top, #0088CC, #0044CC); 
  background-image:     -ms-linear-gradient(top, #0088CC, #0044CC); 
  background-image:      -o-linear-gradient(top, #0088CC, #0044CC); 
  background-image:         linear-gradient(top, #0088CC, #0044CC);}
.modal-footer .btn-primary:hover { background: #0044cc; color: #fff; }
.modal-footer .btn-primary:active { background: #0037a4; color: #f4f4f4; }
.modal-footer .disabled, .modal-footer button[disabled], .modal-body .disabled, .add-btns .disabled { background: #999; color: #fff; border: 1px solid #777; opacity: 0.7; cursor: default; }
.modal-footer .disabled:hover, .modal-footer button[disabled], .modal-body .disabled:hover, .add-btns .disabled:hover { background: #999; color: #fff; }
.modal-footer .disabled:active, .modal-footer button[disabled], .modal-body .disabled:active, .add-btns .disabled:active { background: #999; color: #fff; -webkit-box-shadow: inset 0px 1px 0px 0px rgba(255,255,255,0.3); box-shadow: inset 0px 1px 0px 0px rgba(255,255,255,0.3); }


.modal .close { position: absolute; top: 18px; right: 15px; color: #999; font-size: 14px; }
.modal .close:hover { color: #666; text-decoration: none; }
.modal ul { list-style-type: none; margin: 0; padding: 15px; }
.modal ul li { padding: 10px 0; border-bottom: 1px solid #eee; }
.modal ul li:last-child { border: none; }
.modal ul li label { padding: 0 0 0 5px; }
.modal .msg_confirm { margin: 0; padding: 15px; }

.modal_content { padding: 15px; }
.modal_content input[type="text"] { padding: 5px 8px; font-size: 14px; line-height: 19px; border-radius: 5px 0 0 5px; border: 1px solid #ccc; }
.modal_content input[type="text"]:focus { border: 1px solid #58acef; outline: 0px; box-shadow: 0 1px 1px rgba(0, 0, 0, 0.075) inset, 0 0 8px rgba(82, 168, 236, 0.6); }
.modal_content input.btn { border-radius: 0 5px 5px 0; border-left: none; }





div.default-modal {
	display: none;
	max-height: 80%; 
	max-width: 80%; 
}

div.default-modal .modal-body  {
	max-height: 80%; 
	height: 80%;
	padding: 15px; 
	overflow: auto;
}
div.default-modal iframe {
	width: 100%;
	height: 100%;
}

div.default-modal button.close {
    background: none repeat scroll 0 0 transparent;
    border: 0 none;
    cursor: pointer;
    padding: 0;
    font-size: 20px;
    font-weight: bold;
    line-height: 20px;
}

.messagegrid { margin: 5px 0 0; font-family: "Helvetica Neue",helvetica,Arial,sans-serif; }
.messagegrid th { color: #484848; font-size: 12px; text-align: center;}
.messagegrid td { color: #484848; text-align: center;}
.messagegrid .messagegridheader { padding: 2px 20px; }
.messagegrid .messagegridheader img { margin: -3px 0 0 0; }
.messagegrid .messagegridlanguage{ text-align: right; }

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

/*----------*/

.upload_type { line-height: 17px; }
.upload_type label { display: block; width: 125px; }
.upload_type input { margin: 0 4px 0 0; }

.block_email input, .block_email label { float: left; margin: 7px 5px 0 0; }
.block_email a { display: block; margin: 7px 0 0; }

/*----- New Job and Emergency job table styles -----*/

.htmlradiobuttonbigcheck img { position: absolute; left: 0; top: 25px; /*width: 34px; height: 34px; margin-left: -17px; padding: 0 0 0 50%;*/ }
.htmlradiobuttonbigcheck ol { list-style-type: none; float: left; display: inline; margin: 0; padding: 0 0 0 3px; }
.htmlradiobuttonbigcheck ol li { background: url(img/icons/bullet_blue.gif) center left no-repeat; padding: 0 0 0 20px; font-size: 12px; line-height: 21px; text-align: left; }
.htmlradiobuttonbigcheck button { padding: 0 0 10px 35px; }
.htmlradiobuttonbigcheck .create_btn { float: left; width: 94px; height: 88px; }

.creation_method { position: relative; /*float: left; display: inline; width: 94px;*/ }


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

.linkslist { float: left; display: inline; width: 24%; list-style-type: none; margin: 0; padding: 0; border-top: 1px solid #ccc; border-bottom: 1px solid #ccc; border-left: 1px solid #ccc;-moz-box-sizing: border-box; -webkit-box-sizing: border-box; box-sizing: border-box; }
.linkslist li { font-size: 13px; padding: 5px; }
.linkslist li.heading { font-size: 14px; font-weight: bold; border-bottom: 1px solid #ccc; }

/*----- Report page styling -----*/

label.schedule { margin: 0 10px 0 0; }
label.schedule input[type="radio"] { margin: 0 3px 0 0; }

select#reldate { margin: 0 10px 0 0; }

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
.ie7 .window_main { float: right; margin: 0; }
.ie7 .modal-backdrop { background: none; }

/*----- IE7 classes re-adds the rounded corners for ie7-----*/

.ie7 .window { border: none; }
.ie7 .window_title { background: url("themes/newui/win_t.gif") repeat-x; height: 23px; padding: 0 0 0 20px; line-height: 23px; }
.ie7 .window_title_l { background: url("themes/newui/win_tl.gif") no-repeat; position: absolute; top: 0; left: 0; display: block; width: 12px; height: 23px; }
.ie7 .window_title_r { background: url("themes/newui/win_tr.gif") no-repeat; position: absolute; top: 0; right: 0; display: block; width: 12px; height: 23px; }

.ie7 .window_left { background: url("themes/newui/win_l.gif") top left repeat-y; }
.ie7 .window_right { background: url("themes/newui/win_r.gif") top right repeat-y; }

.ie7 .window_foot_wrap { background: url("themes/newui/win_b.gif") 0 0 repeat-x; height: 15px; }
.ie7 .window_foot_left { background: url("themes/newui/win_bl.gif") top left no-repeat; height: 15px; width: 100%; }
.ie7 .window_foot_right { background: url("themes/newui/win_br.gif") top right no-repeat; height: 15px; width: 100%; }

/*----- IE8 classes re-adds the rounded corners for ie8-----*/

.ie8 .window { border: none; }
.ie8 .window_title { background: url("themes/newui/win_t.gif") repeat-x; height: 23px; padding: 0 0 0 20px; line-height: 23px; }
.ie8 .window_title_l { background: url("themes/newui/win_tl.gif") no-repeat; position: absolute; top: 0; left: 0; display: block; width: 12px; height: 23px; }
.ie8 .window_title_r { background: url("themes/newui/win_tr.gif") no-repeat; position: absolute; top: 0; right: 0; display: block; width: 12px; height: 23px; }

.ie8 .window_left { background: url("themes/newui/win_l.gif") top left repeat-y; }
.ie8 .window_right { background: url("themes/newui/win_r.gif") top right repeat-y; }

.ie8 .window_foot_wrap { background: url("themes/newui/win_b.gif") 0 0 repeat-x; height: 15px; }
.ie8 .window_foot_left { background: url("themes/newui/win_bl.gif") top left no-repeat; height: 15px; width: 100%; }
.ie8 .window_foot_right { background: url("themes/newui/win_br.gif") top right no-repeat; height: 15px; width: 100%; }

.ie8 .btn { overflow: visible; }


/* +----------------------------------------------------------------+
   | Theme include                                                  |
   +----------------------------------------------------------------+ */
   
<?

include_once("themes/newui/style.css");

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


/*----- Media Queries -------*/

@media only screen and (max-width: 980px) {
	.content_feed .feed_wrap { width: 300px; }
}
