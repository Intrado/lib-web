/* --- Swazz Javascript Calendar ---
/* --- v 1.0 3rd November 2006
By Oliver Bryant
http://calendar.swazz.org */

function getCalObj(objID)
{
    if (document.getElementById) {return document.getElementById(objID);}
    else if (document.all) {return document.all[objID];}
    else if (document.layers) {return document.layers[objID];}
}

function checkClick(e) {
	e?evt=e:evt=event;
	CSE=evt.target?evt.target:evt.srcElement;
	if (getCalObj('fc'))
		if (!isChild(CSE,getCalObj('fc'))) {
			getCalObj('fc').style.display='none';
			if( document.getElementById("hideforcalendar") ){
				calendarframe = document.getElementById("hideforcalendar");
				calendarframe.style.display = "none";
			}
		}
}

function isChild(s,d) {
	while(s) {
		if (s==d) 
			return true;
		s=s.parentNode;
	}
	return false;
}

function Left(obj)
{
	var curleft = 0;
	if (obj.offsetParent)
	{
		while (obj.offsetParent)
		{
			curleft += obj.offsetLeft
			obj = obj.offsetParent;
		}
	}
	else if (obj.x)
		curleft += obj.x;
	return curleft;
}

function Top(obj)
{
	var curtop = 0;
	if (obj.offsetParent)
	{
		while (obj.offsetParent)
		{
			curtop += obj.offsetTop
			obj = obj.offsetParent;
		}
	}
	else if (obj.y)
		curtop += obj.y;
	return curtop;
}

//IE stuff




document.write('<table id="fc" style="position:absolute;z-index:1;display:none;border-collapse:collapse;background:#FFFFFF;border:1px solid #ABABAB" cellpadding=2>');
document.write('<tr><td style="cursor:pointer" onclick="csubm()"><img src="img/arrowleftmonth.gif"></td><td colspan=5 id="mns" align="center" style="font:bold 13px Arial"></td><td align="right" style="cursor:pointer" onclick="caddm()"><img src="img/arrowrightmonth.gif"></td></tr>');
document.write('<tr><td align=center style="background:#ABABAB;font:12px Arial">S</td><td align=center style="background:#ABABAB;font:12px Arial">M</td><td align=center style="background:#ABABAB;font:12px Arial">T</td><td align=center style="background:#ABABAB;font:12px Arial">W</td><td align=center style="background:#ABABAB;font:12px Arial">T</td><td align=center style="background:#ABABAB;font:12px Arial">F</td><td align=center style="background:#ABABAB;font:12px Arial">S</td></tr>');
for(var kk=1;kk<=6;kk++) {
	document.write('<tr>');
	for(var tt=1;tt<=7;tt++) {
		num=7 * (kk-1) - (-tt);
		document.write('<td id="v' + num + '" style="width:18px;height:18px">&nbsp;</td>');
	}
	document.write('</tr>');
}
document.write('</table>');

document.all?document.attachEvent('onclick',checkClick):document.addEventListener('click',checkClick,false);


// Calendar script
var now = new Date;
var sccm=now.getMonth();
var sccy=now.getFullYear();
var ccm=now.getMonth();
var ccy=now.getFullYear();
var allowpast = true;
var allowfuture = true;

var updobj;

// Main function Pass in the element text element that the calendar is working on 
function lcs(ielem,allowpast,allowfuture) {
	this.allowpast=allowpast;
	this.allowfuture=allowfuture;
	updobj=ielem;
	getCalObj('fc').style.display='';
	getCalObj('fc').style.left=Left(ielem);
	getCalObj('fc').style.top=Top(ielem)+ielem.offsetHeight;


	if(!document.getElementById("hideforcalendar")) {
		calendarframe = document.createElement("iframe");
		calendarframe.id = "hideforcalendar";
		calendarframe.frameBorder = "0";
		calendarframe.src = "about:blank";
		calendarframe.scrolling = "no";
		calendarframe.style.position = "absolute";
		calendarframe.style.zIndex = "0";
		document.body.appendChild(calendarframe);
	} 
	calendarframe = document.getElementById("hideforcalendar");
	calendarframe.style.display = "block";
	calendarframe.style.top = (getCalObj('fc').offsetTop).toString() + "px";
	calendarframe.style.left = (getCalObj('fc').offsetLeft).toString() + "px";
	calendarframe.style.width = (getCalObj('fc').offsetWidth).toString() + "px";
	calendarframe.style.height = (getCalObj('fc').offsetHeight).toString() + "px";

	// First check date is valid
	curdt=ielem.value;
	curdtarr=curdt.split('/');
	isdt=true;
	for(var k=0;k<curdtarr.length;k++) {
		if (isNaN(curdtarr[k]))
			isdt=false;
	}
	if (isdt&(curdtarr.length==3)) {
		ccm=curdtarr[0]-1;
		ccy=curdtarr[2];
		prepcalendar(curdtarr[1],curdtarr[0]-1,curdtarr[2]);
	} else 
		prepcalendar('',ccm,ccy);	
}

function evtTgt(e)
{
	var el;
	if(e.target)el=e.target;
	else if(e.srcElement)el=e.srcElement;
	if(el.nodeType==3)el=el.parentNode; // defeat Safari bug
	return el;
}
function EvtObj(e){if(!e)e=window.event;return e;}
function cs_over(e) {
	evtTgt(EvtObj(e)).style.background='#FFCC66';
}
function cs_out(e) {
	evtTgt(EvtObj(e)).style.background='#C4D3EA';
}
function cs_click(e) {
	updobj.value=calvalarr[evtTgt(EvtObj(e)).id.substring(1,evtTgt(EvtObj(e)).id.length)];
	getCalObj('fc').style.display='none';
	
	calendarframe = document.getElementById("hideforcalendar");
	calendarframe.style.display = "none";
	
}

var mn=new Array('JAN','FEB','MAR','APR','MAY','JUN','JUL','AUG','SEP','OCT','NOV','DEC');
var mnn=new Array('31','28','31','30','31','30','31','31','30','31','30','31');
var mnl=new Array('31','29','31','30','31','30','31','31','30','31','30','31');
var calvalarr=new Array(42);

function f_cps(obj) {
	obj.style.background='#C4D3EA';
	obj.style.font='10px Arial';
	obj.style.color='#333333';
	obj.style.textAlign='center';
	obj.style.textDecoration='none';
	obj.style.border='1px solid #6487AE';
	obj.style.cursor='pointer';
}

function f_cpps(obj) {
	obj.style.background='#C4D3EA';
	obj.style.font='10px Arial';
	obj.style.color='#ABABAB';
	obj.style.textAlign='center';
	obj.style.textDecoration='line-through';
	obj.style.border='1px solid #6487AE';
	obj.style.cursor='default';
}

function f_hds(obj) {
	obj.style.background='#FFF799';//'#FF0000';
	obj.style.font='bold 10px Arial';
	obj.style.color='#333333';
	obj.style.textAlign='center';
	obj.style.border='1px solid #6487AE';
	obj.style.cursor='pointer';
}

// day selected
function prepcalendar(hd,cm,cy) {
	now=new Date();
	sd=now.getDate();
	td=new Date();
	td.setDate(1);
	td.setFullYear(cy);
	td.setMonth(cm);
	cd=td.getDay();
	getCalObj('mns').innerHTML=mn[cm]+ ' ' + cy;
	marr=((cy%4)==0)?mnl:mnn;
	for(var d=1;d<=42;d++) {
		var strd = 'v'+parseInt(d);
		f_cps(getCalObj(strd));          // Set to standard square
		if ((d >= (cd -(-1))) && (d<=cd-(-marr[cm]))) {
			
			dip=false;
			if(allowpast&&!allowfuture&&(cm==sccm)&&(cy==sccy))
				dip=!(d-cd <= sd);
			else if(!allowpast&&allowfuture)
				dip=((d-cd < sd)&&(cm==sccm)&&(cy==sccy));
			htd=((hd!='')&&(d-cd==hd));
			if (dip) {
				f_cpps(getCalObj(strd));  // Blocked square
			} else if (htd) {
				f_hds(getCalObj(strd));   // Highlight current date selected
			}
			getCalObj(strd).onmouseover=(dip)?null:cs_over;
			getCalObj(strd).onmouseout=(dip)?null:cs_out;
			getCalObj(strd).onclick=(dip)?null:cs_click;
			
			getCalObj(strd).innerHTML=d-cd;	
			calvalarr[d]=''+(cm-(-1))+'/'+(d-cd)+'/'+cy;
		}
		else {
			getCalObj('v'+d).innerHTML='&nbsp;';
			getCalObj(strd).onmouseover=null;
			getCalObj(strd).onmouseout=null;
			getCalObj(strd).onclick=null;

			getCalObj(strd).style.cursor='default';
		}
	}
}

// set calander to current month and year
prepcalendar('',ccm,ccy);
//getCalObj('fc'+cc).style.visibility='hidden';

function caddm() {
	marr=((ccy%4)==0)?mnl:mnn;
	
	ccm+=1;
	if (ccm>=12) {
		ccm=0;
		ccy++;
	}
	cdayf();
	prepcalendar('',ccm,ccy);
}

function csubm() {
	marr=((ccy%4)==0)?mnl:mnn;
	
	ccm-=1;
	if (ccm<0) {
		ccm=11;
		ccy--;
	}
	cdayf();
	prepcalendar('',ccm,ccy);
}

function cdayf() {
if (allowfuture&&((ccy>sccy)|((ccy==sccy)&&(ccm>=sccm)))){
	return;
} else if (allowpast&&((ccy<sccy)|((ccy==sccy)&&(ccm<=sccm)))){
	return;
} else if (allowpast&&allowfuture) {
	return;
}else {
	ccy=sccy;
	ccm=sccm;
	//cfd=scfd;
	}
}