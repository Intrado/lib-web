<?

header('Content-type: text/css');

$colorarray = array("orangetheme" => array("primary" => "FF8C00",
											"theme1" => "B8860B",
											"theme2" => "DAA520"),
					"dodgerblue" => array("primary" => "1E90FF",
											"theme1" => "87CEEB",
											"theme2" => "7FFFD4"),
					"redtheme" =>	array("primary" => "DC143C",
											"theme1" => "800000",
											"theme2" => "B22222"),
					"greentheme" => array("primary" =>"228B22",
											"theme1" => "006400",
											"theme2" => "32CD32"),
					"schoolmessenger" => array("primary" => "346799",
												"theme1" => "999999",
												"theme2" => "CCCCCC")
					);


//$Primary = "FF8C00"; //darkorange;
//$Primary = "1E90FF"; //Dodger Blue
//$Primary = "346799"; //Original
//theme1 is darker than theme2

$theme = "dodgerblue";

$Primary = $colorarray[$theme]["primary"];
$theme1 = "#" . $colorarray[$theme]["theme1"];
$theme2 = "#" . $colorarray[$theme]["theme2"];

$fade1 = "E5E5E5";
$fade2 = "999999";
$fade3 = "595959";

$globalratio = .3;

$newfade1 = fadecolor($Primary, $fade1, $globalratio);
$newfade2 = fadecolor($Primary, $fade2, $globalratio);
$newfade3 = fadecolor($Primary, $fade3, $globalratio);

$Primary = "#" . $Primary;

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


$css = "
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
		color: " . $Primary . ";
	}

	a:hover {
		color: " . $Primary . ";
	}


	.hoverlinks a {
		text-decoration: none;
	}

	.hoverlinks a:hover {
		text-decoration: underline;
	}


	.custname {
		font-size: 12pt;
		color: " . $Primary . ";
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

		color: " . $Primary . ";

		overflow:hidden;
		display: block;
	}

	.navtab a:link, .navtab a:active, .navtab a:visited {
		color: " . $Primary . ";
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
		color: " . $Primary . ";
		margin-left: 10px;
		padding-left: 10px;
		padding-right: 10px;
		height: 22px;
		display: block;
		float: left;
	}

	.subnavmenu a:link, .subnavmenu a:active, .subnavmenu a:visited {
		color: " . $Primary . ";
	}


	.subnavmenu a div {
		padding-top: 4px;
	}

	.subnavmenu .active {
		background: url('img/chrome_light.png');
		border-left: 1px solid " . $theme2 . ";
		border-right: 1px solid " . $theme2 . ";

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
		background: " . $newfade1 . ";
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
		color: " . $Primary . ";

	}

	.pagetitlesubtext {
		margin-left: 15px;
		font-size: 12px;
		font-style: italic;
		color: " . $Primary . ";
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
		border-bottom: 1px solid " . $theme2 . " ;
		height: 22px;
	}

	.windowborder {
		border: 2px solid " . $theme1 . ";
		border-top: 0px;
		border-left: 1px solid " . $theme1 . ";;
	}

	.windowtitle {
		font-size: 12px;
		font-weight: bold;
		padding-left: 5px;
		padding-top: 2px;
		color: " . $Primary . ";
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
		color: " . $Primary . ";
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
		border: " . $theme1 . " 1px solid;
	}


	.windowRowHeader {
		background-color: " . $newfade1 . ";
		color: " . $newfade3 . ";
		width: 85px;
	}


	/* Scrolling window style settings */
	div.scrollTableContainer {
		height: 220px; /* Set scrolling window size */
		overflow: auto; /* Turn on scrolling */
	}
	/* End of scrolling window style settings */

	.list {
		color: " . $newfade3 . ";
		border: 1px solid " . $theme2 . ";
	}

	.listHeader {
		color: white;
		background-color: " . $newfade2 . ";
	}

	.listAlt {
		background-color: " . $newfade1 . ";
	}

	.bottomBorder {
		border-bottom: 1px solid " . $theme2 . ";
	}

	.border {
		border: 1px solid " . $theme2 . ";
	}

	.hoverhelp {
		position: absolute;
		background-color: #FFFFCC;
		border: 1px solid " . $theme2 . ";
		padding: 5px;
		width: 200px;
		font-size: 10px;
		font-weight: normal;
		display: none;
		text-align: left;
		color: " . $Primary . ";
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
		background-color: " . $newfade2 . ";
	}
	.sortheader:link {
		text-decoration: none;
		color: white;
		background-color: " . $newfade2 . ";
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

	dark gray, text on light gray background " . $newfade2 . "

	light gray, light background, highlight " . $newfade1 . "

	dark blue #365F8D
	light blue #D4DDE2
	shaded cell #E8E8E8
	border " . $theme2 . "

	selected blue #0E3293

	*/
";

echo $css;
?>