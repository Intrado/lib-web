<?
header("Expires: " . gmdate('D, d M Y H:i:s', time() + 60*60) . " GMT"); //exire in 1 hour, but if theme changes so will hash pointing to this file
header("Content-Type: text/css");
header("Cache-Control: private");

?>

.newform_container {
	width: 100%;
	padding: 0;
	margin: 0;
	overflow: visible;
}

form.newform {
	position: relative; /* allows offsetParent to be correct for helper*/
	float: left;
	width: 100%;
	clear: both;
}

.helpicon {
	margin-left: 10px; 
}

.helper {
	z-index: 99;
	font-family: sans-serif;
	display: block;
	overflow: hidden;
	float: left;
	width: 200px;
	margin: 0;
	padding: 0;
	
	background-color: white;
	border: 2px solid rgb(150,150,255);
	
	display: none;
}

.helper .helpercontent {
	font-size: 9pt;
	font-family: sans-serif;
	padding: 5px;
	height: 125px;
	overflow: auto;
}

.helper .toolbar {
	padding-bottom: 3px;
	border-top: 1px solid #CCCCCC;
	background-color: rgb(240,240,255);
	height: 20px;
}

.helper .info {
	padding-top: 4pt;
	font-size: 8pt;
	text-align: center;
}

.helper .title {
	border-bottom: 1px solid #CCCCCC;
	font-size: 12pt;
	font-weight: bold;
	text-align: center;
	background-color: rgb(240,240,255);
}

.helper .title img {
	padding-top: .1em; 
	padding-right: 3px; 
	padding-left: 3px; 
	vertical-align: top;
}

.helper .toolbar img {
	padding: 3px 3px 0px 3px;
}

/* fieldset is a container for a set of field elements, typ associated with a help page */
.newform fieldset {
	border: 1px outset;
	padding: 5px;
	
	border: 0px;
	padding: 0px;
}

.newform h2 {
	padding-left: 5px;
	background: repeat-x url('../img/header_bg.gif');
}

.newform legend {
	font-weight: bold;
	font-size: 130%;
	color: #333;
	padding: 0px;
	padding-left: 15px;
	padding-right: 15px;
	margin: 0px;
	margin-left: 10px;
	
	border-left: 1px solid gray;
	border-right: 1px solid gray;
	background: repeat-x url('../img/chrome_light.png') ;
	
	display: none;
}


/* ----------------------------- */

.newform .formcontenttable .formtableheader {
	width: 120px;
	text-align: right;
	vertical-align: top;
	padding-top: .3em; 
}

.newform .formcontenttable .formtableicon {
	width: 16px;
	vertical-align: top;
	padding-top: .3em; 
}


.newform .formcontenttable .formtablecontrol {
	vertical-align: top;
}


.newform .formlabel {
	width: 100%;	
}

.newform .fieldhelp {
	font-weight: normal;
	border: 1px solid blue;
	padding: 3px;
	margin-left: 1px;
	margin-right: 1px;
	text-align: justify;
	font-size: 80%;
	background-color: #FFFFF0;
}

.newform .underneathmsg {
	clear: both;
	font-style: italic;
	font-size: 90%;
	line-height: 120%;
}

.newform .radiobox {
	width: auto;
	margin-top: 3px;
	margin-right: 15px;
	border: 1px dotted gray;
}

.newform .msgbody {
	clear: both;
	font-style: normal;
	font-size: 90%;
	overflow: auto;
	border: 1px solid gray;
}

.newform .msgdetails {
	border-width: 1px;
	border-spacing: 2px;
	border-style: solid;
	border-color: gray;
	background-color: white;
}
.newform .msginfo {
	clear: both;
	font-style: normal;
	font-size: 90%;
}

.newform .msgattachment {
	clear: both;
	font-style: italic;
	font-size: 90%;
}
.newform .msglabel {
	margin-top: 3px;
	font-size: 110%;
	width: 90px;
	vertical-align: top;
}

.wiznav_disabled {
	color: gray;
}

.wiznav_disabled img {
	padding-right: 3px;
	vertical-align: top;
}

.wiznav_active {
	font-weight: bold;
	background-color: #def;
}

.wiznav_active img {
	padding-right: 3px;
	vertical-align: top;
}

.wiznav_enabled a {
	text-decoration: none;
}
.wiznav_enabled a:hover {
	text-decoration: underline;
}

.wiznav_enabled img {
	padding-right: 3px;
	vertical-align: top;
}


ol.wiznav_0 {
	padding: 3px;
	margin: 0px;
	margin: 3px;
	border: 1px outset;
		
	list-style: none;

}
li.wiznav_0 {
	padding: 0px;
	margin: 0px;
}

ol.wiznav_1 {
	padding: 0px;
	margin: 0px;
	padding-left: 1em;
	
	list-style: none;

	font-size: 80%;

}
li.wiznav_1 {
	padding: 0px;
	margin: 0px;
}
ol.wiznav_2 {
	padding: 0px;
	margin: 0px;
	padding-left: 1em;
	
	list-style: none;
}
li.wiznav_2 {
	padding: 0px;
	margin: 0px;
}
