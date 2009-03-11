<?

//set expire time to + 1 hour so browsers cache this file
header("Expires: " . date("r",time() + 60*60));
header("Content-Type: text/css");
?>


/* fieldset is a container for a set of field elements, typ associated with a help page */
.newform fieldset {
	border: 2px dashed rgb(255,255,255);
	padding: 2px;
}


/* these 3 items share aprox 1/3 the space in the form */
.newform label {
	width: 31%;
	display: block;
	float: left;
}

.newform input {
	display: block;
	float: left;
	left: 31%;
	width: 31%;
	margin: 3px;
}

input[type=hidden] {
	display: none;
}


.newform fieldset .radiobox {
	display: block;
	float: left;
	left: 31%;
	width: 31%;
	margin: 3px;
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
	width: 31%;
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
