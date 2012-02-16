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

.formspinner {
	margin-top: 5px; 
	margin-left: 10px;
}

.helpicon {
	margin-left: 10px; 
}

.helper {
	z-index: 99;
	font-family: sans-serif;
	overflow: hidden;
	width: 200px;
	margin: 0;
	padding: 0;
	
	background-color: white;
	border: 4px solid rgb(150,150,255);
	
	display: none;
}

.helper .helpercontent {
	font-size: 9pt;
	font-family: sans-serif;
	padding: 5px;
	height: 150px;
	overflow: auto;
}

.helper .helpercontent ul {
	padding-left: 1.2em;
	margin-left: 0px;
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
	border: none;
	padding: 0px;
}

.newform h2 {
	padding-left: 5px;
	background: repeat-x url('../img/header_bg.gif');
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
	font-weight: normal;
	width: 100%;	
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

.newform .radiobox hr {
	border-top: 1px dotted gray;
	border-bottom: none;
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

.newform .htmlradiobuttonbigcheck {
	border: 0px;
	padding: 0px;
	margin: 0px;
}
.newform .htmlradiobuttonbigcheck div{
	border: 0px;
	padding: 0px;
	margin: 0px;
}
.newform .htmlradiobuttonbigcheck table{
	border: 0px;
	padding: 0px;
	margin: 0px;
}
.newform .htmlradiobuttonbigcheck td{
	color: black;
	border: 0px;
	padding: 0px;
	margin: 0px;
}
.newform .htmlradiobuttonbigcheck button{
	background: none;
	border: 0px;
	padding: 0px;
	margin: 0px;
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

.wiznavcontainer {
	padding: 3px;
	margin: 3px;
	margin-top: 15px;
	border: 1px outset;
}

ol.wiznav_0 {
	padding: 3px;
	margin: 0px;
	margin: 3px;
	
	border: none;
		
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

li.wizbuttonlist {
	list-style-type: circle;
	list-style-image: url(../img/icons/bullet_blue.gif);
	list-style-position: outside;
}