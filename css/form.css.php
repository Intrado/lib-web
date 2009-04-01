<?
header("Expires: " . gmdate('D, d M Y H:i:s', time() + 60*60) . " GMT"); //exire in 1 hour, but if theme changes so will hash pointing to this file
header("Content-Type: text/css");
header("Cache-Control: private");

?>

.newform_container {
	width: 100%;
	padding: 0;
	margin: 0;
}

form.newform {
	position: relative; /* allows offsetParent to be correct for helper*/
	float: left;
	width: 75%; /* leave room for guide */
	overflow: auto;
}

.helpicon {
	margin-left: 10px; 
}

.helper {
	font-family: sans-serif;
	display: block;
	overflow: hidden;
	float: left;
	width: 24%;
	margin: 0;
	padding: 0;
	
	background-color: white;
	border: 2px solid rgb(150,150,255);
}

.helper .content {
	font-size: 9pt;
	font-family: sans-serif;
	padding: 3px;
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

.helper .toolbar img {
	padding: 3px 3px 0px 3px;
}

/* fieldset is a container for a set of field elements, typ associated with a help page */
.newform fieldset {
	border: none;
	padding: 0px;
}

/* these 3 items share aprox 1/3 the space in the form */
.newform label {
	padding: 3px 0px 3px 1%;
	width: 28%;
	display: block;
	float: left;
	overflow: hidden;
}

.newform input {
	display: block;
	float: left;
	width: 40%;
	margin: 3px 1% 3px 0px;
}
input[type=hidden] {
	display: none;
}

.newform textarea {
	display: block;
	float: left;
	width: 40%;
	margin: 3px 1% 3px 0px;
}

.newform select {
	display: block;
	float: left;
	width: 40%;
	margin: 3px 1% 3px 0px;
}

.newform fieldset .radiobox {
	display: block;
	float: left;
	width: 40%;
	margin: 3px 1% 3px 0px;
	border: 1px dotted gray;
}

.newform .formhtml {
	display: block;
	float: left;
	width: 70%;
	margin: 3px 0px 3px 0px;
}

.newform .radiobox input {
	margin-right: 10px;
	width: auto;
	display: inline;
	float: none;
}
.newform .radiobox label {
	width: auto;
	display: inline;
	float: none;
	margin-right: 10px;
}


.newform fieldset div .msgarea {
	width: 25%;
	display: block;
	float: left;
}

/* used to make a 1px spacer for min-height in ie */
.newform fieldset div .prop {
	height: 1.4em;
	width: 1px;
	float: right;
}

/* used to clear floats inside the field area */
.newform fieldset div .clear {
    clear:both;
    height:1px;
    overflow:hidden;
}

/* tweak for IE to get bg color in case of wrapping */
.newform fieldset div {
	width: 100%;
}

.newform fieldset div img {
	margin-top: .3em;
	vertical-align: top;
}

.newform fieldset div span {
	vertical-align: bottom;
	font-style: italic;
	font-size: 80%;
}
