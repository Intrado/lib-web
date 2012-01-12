// Title: Tigra Color Picker
// URL: http://www.softcomplex.com/products/tigra_color_picker/
// Version: 1.1
// Date: 06/26/2003 (mm/dd/yyyy)
// Note: Permission given to use this script in ANY kind of applications if
//    header lines are left unchanged.
// Note: Script consists of two files: picker.js and picker.html

// Modified by Joshua Lai jlai@schoolmessenger.com
// Modified by Nickolas Heckman nheckman@schoolmessenger.com

var TCP = new TColorPicker();

function TCPopup(field) {
	this.field = field;
	this.initPalette = 0;
	var w = 600, h = 250,
	move = screen ? 
		",left=" + ((screen.width - w) >> 1) + ",top=" + ((screen.height - h) >> 1) : "", 
	o_colWindow = window.open("picker.php", null, "help=no,status=no,scrollbars=no,resizable=no" + move + ",width=" + w + ",height=" + h + ",dependent=yes", true);
	o_colWindow.opener = window;
	o_colWindow.focus();
}

function TCBuildCell (R, G, B, w, h) {
	return "<td style=\"border:0px solid red; \" bgcolor=\"#" + this.dec2hex((R << 16) + (G << 8) + B) + "\"><a style=\"border: 0px solid green;\" href=\"javascript:P.S(\"" + this.dec2hex((R << 16) + (G << 8) + B) + "\") onmouseover=\"P.P(\"" + this.dec2hex((R << 16) + (G << 8) + B) + "\")\"><img style=\"border: 0px solid black;\" src=\"img/pixel.gif\" width=\"" + w + "\" height=\"" + h + "\" border=\"0\"></a></td>";
}

function TCSelect(c) {
	// Removed # from return value.  -JJL
	this.field.value = c.toUpperCase();
	// Fire an event on the field. -NRH
	if (document.createEventObject){
		// dispatch for IE
		var evt = document.createEventObject();
		this.field.fireEvent('onchange',evt)
	} else {
		// dispatch for firefox + others
		var evt = document.createEvent("HTMLEvents");
		evt.initEvent("change", true, true ); // event type,bubbling,cancelable
		this.field.dispatchEvent(evt);
	}
	this.win.close();
}

function TCPaint(c, b_noPref) {
	c = (b_noPref ? "" : "#") + c.toUpperCase();
	if (this.o_samp) 
		this.o_samp.innerHTML = "<font face=Tahoma size=2>" + c +" <font color=white>" + c + "</font></font>";
	if(this.doc.layers)
		this.sample.bgColor = c;
	else { 
		if (this.sample.backgroundColor != null) this.sample.backgroundColor = c;
		else if (this.sample.background != null) this.sample.background = c;
	}
}

function TCDec2Hex(v) {
	v = v.toString(16);
	for(; v.length < 6; v = "0" + v);
	return v;
}

function TColorPicker(field) {
	this.show = document.layers ? 
		function (div) { this.divs[div].visibility = "show" } :
		function (div) { this.divs[div].visibility = "visible" };
	this.hide = document.layers ? 
		function (div) { this.divs[div].visibility = "hide" } :
		function (div) { this.divs[div].visibility = "hidden" };
	// event handlers
	this.S       = TCSelect;
	this.P       = TCPaint;
	this.popup   = TCPopup;
	this.dec2hex = TCDec2Hex;
	this.bldCell = TCBuildCell;
	this.divs = [];
}