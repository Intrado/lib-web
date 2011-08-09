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

function toggleVisible(name)
{
	var x = new getObj(name);
	if (x.style) {
		x.style.display = (x.style.display == "block") ? "none" : "block";
		return x.style.display == "block";
	}
}

function setState(field, set)
{
		var x = new getObj('state');
		x.obj.src = 'state.php?_state=' + escape(field) + '&_set=' + escape(set) + '&_page=' + escape(window.location.pathname);
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

function setHiddenIfChecked (checkbox, name)
{
	var x = new getObj(name);
	if (x.style) {
		if (checkbox.checked) {
			x.style.display = "none";
		} else {
			x.style.display =  "block";
		}
	}
}

function setVisibleIfChecked (checkbox, name)
{
	var x = new getObj(name);
	if (x.style) {
		if (checkbox.checked) {
			x.style.display = "block";
		} else {
			x.style.display =  "none";
		}
	}
}

function insert(text, dest) {
	if (document.selection) {
		dest.focus();
		dest.sel.text = text;
		dest.sel.select();
	} else if (dest.selectionStart || dest.selectionStart == "0") {
		var start = dest.selectionStart;
		dest.value = dest.value.substring(0, start) + text + dest.value.substring(dest.selectionEnd, dest.value.length);
		dest.selectionStart = dest.selectionEnd = start + text.length;
	} else {
		dest.value += text;
	}
}

function enable(exc, obj, yes) {
	if(exc != obj)
	{
		if(obj.disabled != null) {
			obj.disabled = !yes;
		}

		for(node in obj.childNodes) {
			enable(exc, obj.childNodes[node], yes);
		}
	}
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

function popup(url, width, height) {
	window.open(url, '_blank', 'width=' + width + ',height=' + height + 'location=no,menubar=no,resizable=yes,scrollbars=yes,status=no,titlebar=no,toolbar=no');
}

function getProperties(obj) {
		var output = '';
		for(key in obj)
			output += key + ' = ' + obj[key] + '\n';
		return output;
}

function setChecked (checkboxname) {
	var x = new getObj(checkboxname);
	x.obj.checked = true;
}

function clearAllIfNotChecked (checkbox, selectname){
	var x = new getObj(selectname);
	if (!checkbox.checked)
		clearAll(x.obj);
}

function clearAll (select){
	for(var i = 0; i < select.length; i++){
		select.options[i].selected = false;
	}
}

//Note: this only works when call by popup windows
function insertAndSelectItem (select,name,value) {
	var sel = opener.document.getElementById(select);
	if(sel) {
		var index = -1;
		//try to find existing item first
		for(var i = 0; i < sel.options.length; i++) {
			if(sel.options[i].value == value) {
				sel.selectedIndex = index = i;
				break;
			}
		}
		//otherwise, make a new one and select it
		if(index == -1) {
			var opt = document.createElement('OPTION');
			opt.text = name;
			opt.value = value;
			sel.options.add(opt);
			sel.selectedIndex = sel.options.length - 1;
		} else {
			//sel.options[index].text = name;
		}
		return true;
	} else {
		return false;
	}
}

/** 
	Function to ensure that at least one item is selected in the select box named
	by the id in the "id" parameter. The enclosingBlock param is the id of any enclosing block that
	may control the visibility of the select box.
	Returns true or false accordingly.
*/
function isSelected(id, enclosingBlock) {
	var sel = new getObj(id);
	
	if (enclosingBlock) {
		var block = new getObj(enclosingBlock);
		return (block.style.display != 'none' && sel.obj.selectedIndex > 0);
	} else {
		return sel.obj.selectedIndex > 0;	
	}
}


function isCheckboxChecked(id) {
	var checkbox = new getObj(id);
	if(checkbox.obj && checkbox.obj.checked){
		return true;
	}
	return false;
}

/* Function to enable or disable the target checkbox if none 
	of the source checkboxes are checked.
	@param sources An array of the source (i.e., master checkboxed)
	@param targets An array of the target (i.e., slave checkboxes)
*/
function syncCheckboxState(sources, targets)
{
	var chkObj = null;
	var anyChecked = false;
	
	for (i = 0; i < sources.length; i++) {
		source = new getObj(sources[i]).obj;	
		if (source.checked) {
			anyChecked = true;	
		}
	}
	
	if (anyChecked) {
		for (i = 0; i < targets.length; i++) {
			target = new getObj(targets[i]).obj;
			target.disabled = false;	
		}
	} else {
		for (i = 0; i < targets.length; i++) {
			target = new getObj(targets[i]).obj;
			target.checked = false;
			target.disabled = true;
		}
	}
}

function confirmDelete () {
	return confirm('Are you sure you want to delete this item?');
}


/* traverses the DOM looking for 
parent = the parent element
marker = which value to look for in the "dependson" attribute.
visability = true|false
*/

function setDependentVisibility (parent,marker,visability) {
	var setvisability = function(obj) { obj.style.display = visability ? "block" : "none";};
	modifyMarkedNodes(parent,'dependson',marker,setvisability);
}

function setColVisability (table,col,visability) {
	//visible table cells use "" for display property, not "block"
	var newdisplay = visability ? "" : "none";
	var rows = table.rows;
	for (var i = 0, length = rows.length; i < length ; i++) {
		rows[i].cells[col].style.display = newdisplay;
	}
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

function dofieldbox (img, init, field, saved) {
	if (!img.toggleset) {
		img.toggleset = true;
		img.toggle = init;
	}
	img.toggle = !img.toggle;
	img.src = "checkboxfield.png.php?toggle=" + img.toggle + "&field=" + field + "&foo=" + new Date() + "&saved=" + saved;
}

function toggleHiddenField(i){
	var checkbox = new getObj("hiddenfield".concat(i)).obj;
	checkbox.checked = !checkbox.checked;
}

//variable _brandtheme needs to be declared externally of this file.
function btn_rollover(obj) {
	modifyMarkedNodes(obj,'buttonrollover','left',function(obj) {obj.src='mimg/themes/' + _brandtheme + '/button_left_over.gif';});
	modifyMarkedNodes(obj,'buttonrollover','right',function(obj) {obj.src='mimg/themes/' + _brandtheme + '/button_right_over.gif';});
	modifyMarkedNodes(obj,'buttonrollover','middle',function(obj) {obj.style.background = "url('mimg/themes/" + _brandtheme + "/button_mid_over.gif') repeat-x";});
}

//variable _brandtheme needs to be declared externally of this file.
function btn_rollout(obj) {
	modifyMarkedNodes(obj,'buttonrollover','left',function(obj) {obj.src='mimg/themes/' + _brandtheme + '/button_left.gif';});
	modifyMarkedNodes(obj,'buttonrollover','right',function(obj) {obj.src='mimg/themes/' + _brandtheme + '/button_right.gif';});
	modifyMarkedNodes(obj,'buttonrollover','middle',function(obj) {obj.style.background = "url('mimg/themes/" + _brandtheme + "/button_mid.gif') repeat-x";});
}

function windowHide(windowid) {
	var windowbody = new getObj('window_' + windowid);
	var collapseicon = new getObj('window_colapseimg_' + windowid);
	
	var vis = windowbody.style.display != "none";
	
	if (vis) {
		windowbody.style.display =  "none" ;
		collapseicon.obj.src = "mimg/arrow_right.gif";
	} else {
		windowbody.style.display =  "block" ;
		collapseicon.obj.src = "mimg/arrow_down.gif";
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


if( typeof XMLHttpRequest == "undefined" ) {
	XMLHttpRequest = function() {
		try { return new ActiveXObject("Msxml2.XMLHTTP.6.0"); } catch(e) {};
		try { return new ActiveXObject("Msxml2.XMLHTTP.3.0"); } catch(e) {};
		try { return new ActiveXObject("Msxml2.XMLHTTP"); }     catch(e) {};
		try { return new ActiveXObject("Microsoft.XMLHTTP"); }  catch(e) {};
		throw new Error("This browser does not support XMLHttpRequest or XMLHTTP.");
	};
}

function ajax(url, vars, callbackFunction, args)
{
	var request =  new XMLHttpRequest();
	request.open("POST", url, true);
	request.setRequestHeader("Content-Type","application/x-www-form-urlencoded");
	request.onreadystatechange = function() {
		try {
			if (request.readyState == 4 && request.status == 200) {
				if (request.responseText) {
					callbackFunction(request.responseText,args);
				}
			}
		} catch(e) {};
	};
	request.send(vars);
}



