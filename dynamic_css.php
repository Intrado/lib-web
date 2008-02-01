<?

header('Content-type: text/css');

echo "
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
		color: #346799;
	}

	a:hover {
		color: #346799;
	}


	.hoverlinks a {
		text-decoration: none;
	}

	.hoverlinks a:hover {
		text-decoration: underline;
	}


	.custname {
		font-size: 12pt;
		color: #346799;
		white-space: nowrap;
		text-align: right;
		margin: 10px;
		margin-right: 25px;
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
		width: 100px;
		height: 23px;
		position:relative;
		float: left;
		background: url('img/main_nav_tab_over.gif') no-repeat;
	}

	.navtab a {
		width: 98px;
		height: 23px;

		font-size: 12px;
		text-align: center;
		text-decoration: none;
		font-weight: bold;

		color: black;

		overflow:hidden;
		display: block;
	}

	.navtab a:link, .navtab a:active, .navtab a:visited {
		color: black;
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
		padding-top:5px;
		padding-right: 15px;
		text-align: right;
		font-size: 10px;
		white-space: nowrap
	}


	/* **** subnav **** */

	.subnavmenu {
		height: 22px;
		width: 100%;
		background: url('img/chrome.png');
	}

	.subnavmenu .subnavtab {
		font-size: 10px;
		color: black;
		margin-left: 10px;
		padding-left: 10px;
		padding-right: 10px;
		height: 22px;
		display: block;
		float: left;
	}

	.subnavmenu a:link, .subnavmenu a:active, .subnavmenu a:visited {
		color: black;
	}


	.subnavmenu a div {
		padding-top: 4px;
	}

	.subnavmenu .active {
		background: url('img/chrome_light.png');
		border-left: 1px solid #cccccc;
		border-right: 1px solid #cccccc;

	}


	/* **** shortcuts **** */

	.shortcutmenuholder {

		float: right;
		margin: 2px;
		margin-right: 15px;
		position:relative;
		width: 160px;

	}

	.shortcutmenu {
		position: absolute;
		left: 0px;
		top: 0px;
		width: 160px;

		border: 2px outset;



		text-align: left;
		display: block;

		font-size: 10px;
	}

	.shortcutmenu img {
		margin-right: 10px;
		float: right;
	}

	.shortcuts {
		font-size: 9px;
		background: white;
		padding: 5px;
		display: none;
	}

	.shortcuts a , .shortcuts a:link, .shortcuts a:active, .shortcuts a:visited {
		margin-left: 5px;
		display: block;
		color: #346799;
	}

	.shortcuttitle {
		background: #E5E5E5;
	}



	/* **** content **** */

	.content {
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

	}

	.pagetitlesubtext {
		margin-left: 15px;
		font-size: 12px;
		font-style: italic
	}


	/* **** window **** */



	.menucollapse {

		float: right;
		margin-top: 4px;
		margin-right: 5px;

		border: 2px outset;

		width: 10px;
		height: 10px;

	}


	.window {
		width: 100%;
	}


	.windowbar {
		background: url('img/chrome_light.png') repeat-x;
		border-bottom: 1px solid #cccccc ;
		height: 22px;
	}

	.windowborder {
		border: 2px solid #999999;
		border-top: 0px;
		border-left: 1px solid #999999;;
	}

	.windowtitle {
		font-size: 12px;
		font-weight: bold;
		padding-left: 5px;
		padding-top: 2px;
		color: #346799;
	}

	.windowbody {
		display: block;
	}

	/* **** button **** */


	.button {
		text-decoration: none;
		height: 23px;

		float: left;

	}

	.button td {

	}

	.button a, .button td {
		text-decoration: none;
		color:#346799;
		font-size: 9px;
		font-weight: bold;
		cursor: pointer;
	}

	.button .middle {
		background: url('img/button_mid.gif') repeat-x;
	}

	.button table {
		border-collapse: collapse;
		border-spacing: 0;
	}

	.button td {
		padding: 0;
	}



	/* **** hover help **** */


	/* *********************************************** */





	/* *********************************************** */







	/* general styles */



	input.text , select, textarea, table.form  {
		border: #999999 1px solid;
	}


	.windowRowHeader {
		background-color: #E5E5E5;
		color: #595959;
		width: 85px;
	}


	/* Scrolling window style settings */
	div.scrollTableContainer {
		height: 220px; /* Set scrolling window size */
		overflow: auto; /* Turn on scrolling */
	}
	/* End of scrolling window style settings */

	.list {
		color: #595959;
		border: 1px solid #cccccc;
	}

	.listHeader {
		color: white;
		background-color: #999999;
	}

	.listAlt {
		background-color: #E5E5E5;
	}

	.bottomBorder {
		border-bottom: 1px solid #cccccc;
	}

	.border {
		border: 1px solid #cccccc;
	}

	.hoverhelp {
		position: absolute;
		background-color: #FFFFCC;
		border: 1px solid #cccccc;
		padding: 5px;
		width: 200px;
		font-size: 10px;
		font-weight: normal;
		display: none;
		text-align: left;
		color: #365F8D;
	}

	.hoverhelpicon {
		margin-left: 5px;
		margin-right: 5px;
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


	.sortheader {
		color: white;
		background-color: #999999;
	}
	.sortheader:link {
		text-decoration: none;
		color: white;
		background-color: #999999;
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


	/*


	blue, logo & cust name color, window color #346799

	dark gray, text on light gray background #595959

	light gray, light background, highlight #E5E5E5

	dark blue #365F8D
	light blue #D4DDE2
	shaded cell #E8E8E8
	border #cccccc

	selected blue #0E3293

	*/
"
?>