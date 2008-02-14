// Title: Tigra Color Picker
// URL: http://www.softcomplex.com/products/tigra_color_picker/
// Version: 1.1
// Date: 06/26/2003 (mm/dd/yyyy)
// Note: Permission given to use this script in ANY kind of applications if
//    header lines are left unchanged.
// Note: Script consists of two files: picker.js and picker.html

// Modified by Joshua Lai jlai@schoolmessenger.com

var TCP = new TColorPicker();

function TCPopup(field, palette) {
	this.field = field;
	this.initPalette = !palette || palette > 3 ? 0 : palette;
	var w = 450, h = 250,
	move = screen ? 
		',left=' + ((screen.width - w) >> 1) + ',top=' + ((screen.height - h) >> 1) : '', 
	o_colWindow = window.open('picker.html', null, "help=no,status=no,scrollbars=no,resizable=no" + move + ",width=" + w + ",height=" + h + ",dependent=yes", true);
	o_colWindow.opener = window;
	o_colWindow.focus();
}

function TCBuildCell (R, G, B, w, h) {
	return '<td style="border:0px solid red; " bgcolor="#' + this.dec2hex((R << 16) + (G << 8) + B) + '"><a style="border: 0px solid green;" href="javascript:P.S(\'' + this.dec2hex((R << 16) + (G << 8) + B) + '\')" onmouseover="P.P(\'' + this.dec2hex((R << 16) + (G << 8) + B) + '\')"><img style="border: 0px solid black;" src="img/pixel.gif" width="' + w + '" height="' + h + '" border="0"></a></td>';
}

function TCSelect(c) {
	// Removed # from return value.  -JJL
	this.field.value = c.toUpperCase();
	this.win.close();
}

function TCPaint(c, b_noPref) {
	c = (b_noPref ? '' : '#') + c.toUpperCase();
	if (this.o_samp) 
		this.o_samp.innerHTML = '<font face=Tahoma size=2>' + c +' <font color=white>' + c + '</font></font>'
	if(this.doc.layers)
		this.sample.bgColor = c;
	else { 
		if (this.sample.backgroundColor != null) this.sample.backgroundColor = c;
		else if (this.sample.background != null) this.sample.background = c;
	}
}

function TCGenerateSafe() {
	var s = '';
	/*
	for (j=0; j < 7; j++) {
		s += "<tr>";
		for (k = 0; k < 86; k ++){
			var num = k*3;
			var num2 = (85-k)*3;
			switch(j) {
				case 0:
					s += this.bldCell(num, num2, num2, 8, 15);
					break;
				case 1:
					s += this.bldCell(num2, num, num2, 8, 15);
					break;
				case 2:
					s += this.bldCell(num2, num2, num, 8, 15);		
					break;
				case 3:
					s += this.bldCell(num, 0, 0, 8, 15);
					break;
				case 4:
					s += this.bldCell(0, num, 0, 8, 15);
					break;
				case 5:
					s += this.bldCell(0, 0, num, 8, 15);
					break;
				case 6:
					s += this.bldCell(num, num, num, 8, 15);
					break;
			}
		}

		s += "</tr>";
	}
	*/
	for(i=0; i < 52; i++){
		s += "<tr>";
		for(j=0; j<52; j++){
			var g = i-j;
			if(g < 0){
				g=0;
			}
			var b = j-i;
			if(b < 0){
				b=0;
			}
			r=Math.min(i, j);
			s += this.bldCell(r*5, g*5, b*5, 8, 10);
		}
		s += "</tr>";
	}
	
	return s;
}

function TCGenerateWind() {

	var s = '';
	var max = 15;
	for(colorstep=17; colorstep > 0; colorstep--){
		s += "<tr>";
		var r=max;
		var g=0;
		var b=0;
		do{
			s += this.bldCell(r*colorstep, g*colorstep, b*colorstep, 4, 10);
			if(g!=max && r==max && b==0){
				g++;
			} else if(g==max && r>0){
				r--;
			} else if(r==0 && g==max && b!=max){
				b++;
			} else if(b==max && g>0){
				g--;
			} else if(b==max && g==0 && r!=max){
				r++;
			} else if(r==max && g==0){
				b--;
			}
		} while(!(r==max && b==1));
		// Draw one more time for the end case;
		//s += this.bldCell(r*colorstep, g*colorstep, b*colorstep, 8, 10);
		
		s += "</tr>";
	}
	return s;
}
function TCGenerateMac() {
	var s = '';
	var c = 0,n = 1;
	var r,g,b;
	for (j = 0; j < 15; j ++) {
		s += "<tr>";
		for (k = 0; k < 3; k ++)
			for (i = 0; i <= 5; i++){
				if(j<12){
				s += this.bldCell( 255-(Math.floor(j / 2) * 51), 255-(k * 51 + (j % 2) * 51 * 3),255-(i * 51), 8, 10);
				}else{
					if(n<=14){
						r = 255-(n * 17);
						g=b=0;
					}else if(n>14 && n<=28){
						g = 255-((n-14) * 17);
						r=b=0;
					}else if(n>28 && n<=42){
						b = 255-((n-28) * 17);
						r=g=0;
					}else{
						r=g=b=255-((n-42) * 17);
					}
					s += this.bldCell( r, g,b, 8, 10);
					n++;
				}
			}
		s += "</tr>";
	}
	return s;
}

function TCGenerateGray() {
	var s = '';
	for (j = 0; j <= 15; j ++) {
		s += "<tr>";
		for (k = 0; k <= 15; k ++) {
			g = Math.floor((k + j * 16) % 256);
			s += this.bldCell(g, g, g, 9, 7);
		}
		s += '</tr>';
	}
	return s
}

function TCDec2Hex(v) {
	v = v.toString(16);
	for(; v.length < 6; v = '0' + v);
	return v;
}

function TCChgMode(v) {
	for (var k in this.divs) this.hide(k);
	this.show(v);
}

function TColorPicker(field) {
	this.build0 = TCGenerateWind;
	this.build1 = TCGenerateSafe;
	this.build2 = TCGenerateGray;
	this.build3 = TCGenerateMac;
	this.show = document.layers ? 
		function (div) { this.divs[div].visibility = 'show' } :
		function (div) { this.divs[div].visibility = 'visible' };
	this.hide = document.layers ? 
		function (div) { this.divs[div].visibility = 'hide' } :
		function (div) { this.divs[div].visibility = 'hidden' };
	// event handlers
	this.C       = TCChgMode;
	this.S       = TCSelect;
	this.P       = TCPaint;
	this.popup   = TCPopup;
	this.draw    = TCDraw;
	this.dec2hex = TCDec2Hex;
	this.bldCell = TCBuildCell;
	this.divs = [];
}

function TCDraw(o_win, o_doc) {
	this.win = o_win;
	this.doc = o_doc;
	var 
	s_tag_openT  = o_doc.layers ? 
		'layer visibility=hidden top=54 left=5 width=182' : 
		'div style=visibility:hidden;position:absolute;left:6px;top:54px;width:182px;height:0',
	s_tag_openS  = o_doc.layers ? 'layer top=32 left=6' : 'div',
	s_tag_close  = o_doc.layers ? 'layer' : 'div'
		
	this.doc.write('<' + s_tag_openS + ' id=sam name=sam><table cellpadding=0 cellspacing=0 border=0 width=181 align=center ><tr><td align=center height=18><div id="samp"><font face=Tahoma size=2>sample <font color=white>sample</font></font></div></td></tr></table></' + s_tag_close + '>');
	this.sample = o_doc.layers ? o_doc.layers['sam'] : 
		o_doc.getElementById ? o_doc.getElementById('sam').style : o_doc.all['sam'].style

	for (var k = 0; k < 4; k ++) {
		this.doc.write('<' + s_tag_openT + ' id="p' + k + '" name="p' + k + '"><table cellpadding=0 cellspacing=0 border=0 align=center>' + this['build' + k]() + '</table></' + s_tag_close + '>');
		this.divs[k] = o_doc.layers 
			? o_doc.layers['p' + k] : o_doc.all 
				? o_doc.all['p' + k].style : o_doc.getElementById('p' + k).style
	}
	if (!o_doc.layers && o_doc.body.innerHTML) 
		this.o_samp = o_doc.all 
			? o_doc.all.samp : o_doc.getElementById('samp');
	this.C(this.initPalette);
	if (this.field.value) this.P(this.field.value, true)
}
