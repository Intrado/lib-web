function getObj(name)
{
  if (document.getElementById)
  {
  	this.obj = document.getElementById(name);
  }
  else if (document.all)
  {
	this.obj = document.all[name];
  }
  else if (document.layers)
  {
   	this.obj = document.layers[name];
  }
  if(this.obj)
	this.style = this.obj.style;
}

function show(name)
{
	var x = new getObj(name);
	if (x.style)
		x.style.display = "block";
}

function hide(name)
{
	var x = new getObj(name);
	if (x.style)
		x.style.display =  "none";
}

function setIFrame(div) {
	if(div == null) {
		iframe.style.display = 'none';
		iframe.style.top = 0;
		iframe.style.left = 0;
		iframe.style.width = 1;
		iframe.style.height = 1;
	} else {
		iframe = new getObj('blocker').obj;
		iframe.style.top = div.offsetTop;
		iframe.style.left = div.offsetLeft;
		iframe.style.width = div.offsetWidth;
		iframe.style.height = div.offsetHeight;
		iframe.style.display = 'block';
	}
}

function popup(url, width, height, target) {
	if(typeof(target) == 'undefined')
		target = '_blank';
	var targetwindow = window.open(url,target, 'width=' + width + ',height=' + height + 'location=no,menubar=no,resizable=yes,scrollbars=yes,status=no,titlebar=no,toolbar=no');
	targetwindow.focus();
}

function modifyMarkedNodes (parent,attribute,marker,callback) {
	var children = parent.childNodes;
	for (var i = 0, length = children.length; i < length; i++) {
		var curchild = children[i];
		if(curchild.getAttribute && curchild.getAttribute(attribute) == marker) {
			callback(curchild);
		}
		if (curchild.childNodes.length > 0) {
			modifyMarkedNodes(curchild,attribute,marker,callback);
		}
	}
}

//variable _brandtheme needs to be declared externally of this file.
function btn_rollover(obj) {
	modifyMarkedNodes(obj,'buttonrollover','left',function(obj) {obj.src='img/themes/' + _brandtheme + '/button_left_over.gif';});
	modifyMarkedNodes(obj,'buttonrollover','right',function(obj) {obj.src='img/themes/' + _brandtheme + '/button_right_over.gif';});
	modifyMarkedNodes(obj,'buttonrollover','middle',function(obj) {obj.style.background = "url('img/themes/" + _brandtheme + "/button_mid_over.gif') repeat-x";});
}

//variable _brandtheme needs to be declared externally of this file.
function btn_rollout(obj) {
	modifyMarkedNodes(obj,'buttonrollover','left',function(obj) {obj.src='img/themes/' + _brandtheme + '/button_left.gif';});
	modifyMarkedNodes(obj,'buttonrollover','right',function(obj) {obj.src='img/themes/' + _brandtheme + '/button_right.gif';});
	modifyMarkedNodes(obj,'buttonrollover','middle',function(obj) {obj.style.background = "url('img/themes/" + _brandtheme + "/button_mid.gif') repeat-x";});
}

function windowHide(windowid) {
	var windowbody = new getObj('window_' + windowid);
	var collapseicon = new getObj('window_colapseimg_' + windowid);
	
	var vis = windowbody.style.display != "none";
	
	if (vis) {
		windowbody.style.display =  "none" ;
		collapseicon.obj.src = "img/arrow_right.gif";
	} else {
		windowbody.style.display =  "block" ;
		collapseicon.obj.src = "img/arrow_down.gif";
	}
	
	setState('window_' + windowid, vis ? "closed" : "open");
}

function submitForm (formname,section,value) {
	var theform = document.forms[formname];
	//make a new hidden element to emulate the data that would normally be passed back from a submit button
	var submit = document.createElement('input');
	submit.setAttribute('name','submit[' + formname  + '][' + section + ']');
	submit.value= value == undefined ? 'Submit' : value;
	submit.setAttribute('type','hidden');
	theform.appendChild(submit);
	if(!(theform.onsubmit && theform.onsubmit() == false)){
		theform.submit();
	}
}