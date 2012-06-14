/* wrap $ for compatability */
function getObj (name) {
	this.obj = $(name);
	if(this.obj)
		this.style = this.obj.style;
}

/* compat */
function toggleVisible (name) {
	$(name).toggle();
}

/* compat */
function show (name) {
	$(name).show();
}

/* compat */
function hide (name) {
	$(name).hide();
}

function setState(field, set) {
		var x = new getObj('state');
		x.obj.src = 'state.php?_state=' + escape(field) + '&_set=' + escape(set) + '&_page=' + escape(window.location.pathname);
}


function setHiddenIfChecked (checkbox, name) {
	var x = new getObj(name);
	if (x.style) {
		if (checkbox.checked) {
			x.style.display = "none";
		} else {
			x.style.display =  "block";
		}
	}
}

function setVisibleIfChecked (checkbox, name) {
	var x = new getObj(name);
	if (x.style) {
		if (checkbox.checked) {
			x.style.display = "block";
		} else {
			x.style.display =  "none";
		}
	}
}

function textInsert(text, dest) {
	if ($('reusableckeditorhider') && !$('reusableckeditorhider').down('#cke_reusableckeditor')) {
		CKEDITOR.instances['reusableckeditor'].insertText(text);
	} else if (document.selection && dest.sel) {
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


function popup(url, width, height, target) {
	if(typeof(target) == 'undefined')
		target = '_blank';
	var targetwindow = window.open(url, target, 'width=' + width + ',height=' + height + 'location=no,menubar=no,resizable=yes,scrollbars=yes,status=no,titlebar=no,toolbar=no');
	targetwindow.focus();
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
function syncCheckboxState(sources, targets) {
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

function setColVisability (table,col,visability,page,field) {
	//visible table cells use "" for display property, not "block"
	var newdisplay = visability ? "" : "none";
	var rows = table.rows;
	for (var i = 0, length = rows.length; i < length ; i++) {
		rows[i].cells[col].style.display = newdisplay;
	}
	if (page != undefined && page != "" && field != undefined && field != "") {
		new Ajax.Request('ajax.php',{method:'post',parameters:{type: 'setfieldview',page:page,field:field,value:visability}});
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

//used by reportutils.inc.php for select_metadata()
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

function parse_theme_from_url (url) {
	var t = url.substring(11+url.indexOf("img/themes/"));
	return t.substring(0,t.indexOf("/"));
}

/*
 * FIXME: commented the code within the btn_roll, btn_rollover and btn_rollout functions rather than removing the functions as they are called in many pages.
 */
function btn_roll(obj,over) {
	//obj = $(obj);
	//over = over ? "_over" : "";

	//var leftimg = obj.down('.left');
	//var rightimg = obj.down('.right');
	//var midtb = obj.down('.middle');

	//parse one of the button images for the theme
	//var theme = parse_theme_from_url(leftimg.src);

	//leftimg.src='themes/' + theme + '/button_left' + over + '.gif';
	//rightimg.src='themes/' + theme + '/button_right' + over + '.gif';
	//midtb.style.background = "url('themes/" + theme + "/button_mid" + over + ".gif') repeat-x";
}

function btn_rollover(obj) {
	//btn_roll(obj,true);
}

function btn_rollout(obj) {
	//btn_roll(obj,false);
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

//old
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

// Ajax cache with request function
var cachedajaxgetdata = new Array();
function cachedAjaxGet(uri,ajaxhandler,ajaxhandlerarg,usecache) {
	usecache = typeof(usecache) != 'undefined' ? usecache : true;
	if(usecache) {
		var returnvalue = cachedajaxgetdata[uri];
		if(returnvalue && ajaxhandler) {
			ajaxhandler(returnvalue,ajaxhandlerarg);
			return;
		}
	}
	new Ajax.Request(uri, {
		method:'get',
		onSuccess: function (result) {
			//don't save results unless we're using cache
			if (usecache) {
				cachedajaxgetdata[uri] = result;
			}
			//handler optional
			if (ajaxhandler) {
				ajaxhandler(result,ajaxhandlerarg);
			}
		}
	});
}

function isSequential(number) {
	var isseq = 0;
	var neg = 0;
	var diff = 0;
	if (parseFloat(number) == 0)
		return false;
	diff = parseInt(number.substring(0,1)) - parseInt(number.substring(1,2));
	if (diff == -1) {
		isseq = 1;
		neg = 0;
	} else if (diff == 1) {
		isseq = 1;
		neg = 1;
	} else {
		return isseq;
	}
	for (i = 1; i < (number.length-1); i++) {
		diff = parseInt(number.substring(i,i+1)) - parseInt(number.substring(i+1,i+2));
		if(diff == -1 && neg==0) {
			isseq = 1;
		} else if (diff == 1 && neg == 1) {
			isseq = 1;
		} else {
			isseq = 0;
			break;
		}
	}
	return isseq;
}

function isAllSameDigit(number){
	var same = 0;
	for(i =0; i < (number.length - 1); i++){
		if(number.substring(i,i+1) == number.substring(i+1,i+2)){
			same = 1;
		} else {
			same = 0;
			break;
		}
	}
	if(same == 1){
		return true;
	}
	return false;
}

function ajax_table_update(containerID, uri) {
	if (!$(containerID))
		return;
	$(containerID+'_tableprogressbar').update('<img src="img/ajax-loader.gif"/>');
	if (!uri)
		return;
	cachedAjaxGet(uri + '&containerID=' + containerID, function(transport) {
		if (!$(containerID))
			return;
		var data = transport.responseJSON;
		if (!data || !data.html) {
			$(containerID).update('Unable to load data');
			return;
		}
		$(containerID).update(data.html);
	}, null, false); // Do not cache this request.
}

function ajax_obj_table_update(tableid,url,overwrite,callback){
	new Ajax.Request(url, {
		method:'get',
		onSuccess: function (response) {
			var result = response.responseJSON;
			if(result) {
				var tr= new Element('tr');
				for (var i = 0; i < result.titles.length; i++) {
					tr.insert(new Element('th').insert(result.titles[i]));
				}
				var thead = new Element('thead').insert(tr);
				$(tableid).down("thead").update(thead.innerHTML);
				
				var tbody = new Element('tbody');
				for (var i = 0; i < result.rows.length; i++) {
					var row = result.rows[i];
					var tr = false;
					if (row.action != undefined)
						tr = new Element('tr',{onclick: row.action});
					else
						tr = new Element('tr');
					
					for (var j = 0; j < row.cols.length; j++) {
						tr.insert(new Element('td').insert(row.cols[j]));
					}
					tbody.insert(tr);
				}
				
				if (overwrite === undefined || overwrite === false)
					$(tableid).down("tbody").insert(tbody.innerHTML);
				else
					$(tableid).down("tbody").update(tbody.innerHTML);
				
				callback(result.rows.length);
			}
		}
	});
}



function do_ajax_listbox(checkbox, personid) {
	// NOTE: No need to manually toggle the checkbox because the browser will do that automatically.

	if (checkbox.checked) {
		// Add.
		cachedAjaxGet('?ajax&addpersonid='+personid, function(transport) {
			var data = transport.responseJSON;
			if (!data) {
				this.checked = false;
				alert('Sorry, there was an error when trying to add to the list');
				return;
			}
		}.bindAsEventListener(checkbox), null, false);
	} else {
		// Remove.
		cachedAjaxGet('?ajax&removepersonid='+personid, function(transport) {
			var data = transport.responseJSON;
			if (!data) {
				this.checked = true;
				alert('Sorry, there was an error when trying to remove from the list');
				return;
			}
		}.bindAsEventListener(checkbox), null, false);
	}
}

//handles toggle of field checkboxes, sends ajax to save state for next page load
function set_list_fieldvisibility(checkbox, fnum, tableid, col) {

	try { 
		setColVisability($(tableid), col, checkbox.checked); 
	} catch (e) {}
	if (checkbox.checked) {
		cachedAjaxGet('?ajax&showfield='+fnum, null, null, false);
	} else {
		cachedAjaxGet('?ajax&hidefield='+fnum, null, null, false);
	}
}

var personTips = [];
function make_person_tip(personid, tiptitle){
	if (personTips[personid])
		return;

	personTips[personid] = new Tip('persontip_'+personid,
		{
			ajax: {
				url:'viewcontact.php?ajax&id='+personid,
				options: {
					onComplete:function(transport) {
					}
				}
			},

			title : tiptitle,
			style: "protogrey",
			stem: "leftMiddle",
			hook: { target: "topRight", tip: "leftMiddle" },
			offset: { x: 10, y: 0 },
			showOn: 'click',
			hideOn: 'click',
			width: 360,
			fixed: true,
			hideOthers: true,
			closeButton: true
		}
	);
}

function json_input_values(inputs) {
	var values = [];
	for (var i = 0; i < inputs.length; i++)
		values.push(inputs[i].getValue());
	return values.toJSON();
}


function format_thousands_separator(num) {
	var digits = String(num).toArray().reverse();
	var formatted = [];
	for (var i = 0, len = digits.length; i < len; i++) {
		if (i > 0 && i % 3 == 0) {
			formatted.push(',');
		}
		formatted.push(digits[i]);
	}
	return formatted.reverse().join("");
}


function icon_button(name,icon,id) {
	var newbutton = new Element("button",{"class": "btn", type: "button"});
	if (id)
		newbutton.id = id;

	var buttonface = new Element("span",{"class":"btn_middle"}).insert(new Element("img",{src: "img/icons/"+icon+".gif", "class":"btn_middle_icon"})).insert(name);

	var buttonrecord = new Element("span", {"class":"btn_left"}).insert(buttonface).insert(new Element("span",{"class":"btn_right"}));

	//var buttonbody = new Element("div",{"class":"btn_wrap cf"}).insert(buttonrecord);
	var buttonbody = new Element("div",{"class":"btn_wrap cf"}).insert(new Element("span", {"class":"btn_left"})).insert(buttonface).insert(new Element("span",{"class":"btn_right"}));

	newbutton.insert(buttonbody);

	newbutton.observe("mouseover", btn_rollover.bind(this,newbutton));
	newbutton.observe("mouseout", btn_rollout.bind(this,newbutton));

	return newbutton;
}

function action_link(name,icon,id) {
	var newaction = new Element("a", {href: "#", "class": "actionlink", title: name, style: "margin-left: 3px;"});
	newaction.id = id;

	// TODO: actionlinkmode needs to be checked to display icons and/or titles
	newaction.insert(new Element("img", {src: "img/icons/"+icon+".gif"})).insert(name);

	return newaction;
}

function blankFieldValue(element, value) {
	element = $(element);
	element.observe("focus", setDefaultFieldValue.curry(value));
	element.observe("blur", setDefaultFieldValue.curry(value));
	if (element.value == "") {
		element.value = value;
		element.setStyle({ color: "gray" });
	}
}

function setDefaultFieldValue(value, event) {
	var element = event.element();
	if (event.type == "focus" && element.value == value) {
		element.value = "";
		element.setStyle({
			color: "black"
		});
	}

	if (event.type == "blur" && element.value == "") {
		element.value = value;
		element.setStyle({
			color: "gray"
		});
	}
}

// @param textbox, can also be an ID.
function pickDate (textbox, allowPast, allowFuture, closeOnBlur, afterClose) {
	var element = $(textbox);

	var filter = new DatePickerFilter();
	if (!allowPast)
		filter.append(DatePickerUtils.noDatesBefore(0));
	if (!allowFuture)
		filter.append(DatePickerUtils.noDatesAfter(0));
	return new DatePicker({
		relative: element.identify(),
		keepFieldEmpty:true,
		enableCloseOnBlur: closeOnBlur ? true : false,
		topOffset:20,
		relativePosition: true,
		dateFilter: filter,
		afterClose: afterClose
	});
}

function makeTranslatableString(str) {
	return str.replace(/(<<.*?>>)/g, '<input value="$1"/>').replace(/({{.*?}})/g, '<input value="$1"/>').replace(/(\[\[.*?\]\])/g, '<input value="$1"/>');
}

// TODO: Make a less annoying version
// returns a string of the current date in
function curDate() {
   var months = new Array(13);
   months[0]  = "Jan";
   months[1]  = "Feb";
   months[2]  = "Mar";
   months[3]  = "Apr";
   months[4]  = "May";
   months[5]  = "Jun";
   months[6]  = "Jul";
   months[7]  = "Aug";
   months[8]  = "Sep";
   months[9]  = "Oct";
   months[10] = "Nov";
   months[11] = "Dec";
   var now         = new Date();
   var monthnumber = now.getMonth();
   var monthname   = months[monthnumber];
   var monthday    = now.getDate();
   var year        = now.getFullYear();
   var hour   = now.getHours();
   var minute = now.getMinutes();
   var second = now.getSeconds();
   var ap = "am";
   if (hour   > 11) { ap = "pm";             }
   if (hour   > 12) { hour = hour - 12;      }
   if (hour   == 0) { hour = 12;             }
   if (minute < 10) { minute = "0" + minute; }
   if (second < 10) { second = "0" + second; }
   return monthname + ' ' + monthday + ', ' + year + " " + hour + ':' + minute + ':' + second + " " + ap;
}

function sessionKeepAliveWarning(timeout) {
	setTimeout(function() {
		var keepalivemodal = new ModalWrapper("Automatic Logout",false,false);
	
		var content = new Element('div', {'class': 'keepalive'});
		content.appendChild(new Element('img', {src:"img/icons/lock.png", alt: "Warning"}));
		content.appendChild(new Element('span').update("Your session is about to close due to inactivity."));
		content.appendChild(new Element('p').update(new Element('button')
			.update("Refresh Session")
			.observe("click", function() {
				content.update(new Element('img', {src:"img/ajax-loader.gif", alt: "Refreshing Session"}));
				new Ajax.Request('ajax.php',{
					method:'get',
					parameters:{type: 'keepalive'},
					onSuccess: function (response) {
						//HACK: check to see if we hit the login page (due to logout)
						if (response.responseText.indexOf(" Login</title>") != -1) {
							content.update();
							content.appendChild(new Element('img', {src:"img/icons/error.png", alt: "Error"}));
							content.appendChild(new Element('span')
								.update("Your session was not refreshed because your session has expired or logged out."));
						} else {
							content.update();
							content.appendChild(new Element('img', {src:"img/icons/accept.png", alt: "OK"}));
							content.appendChild(new Element('span')
								.update("Your session was refreshed successfully."));
							setTimeout(function() {
								keepalivemodal.modal.close();
							}, 4000);
							sessionKeepAliveWarning(timeout);
						}
					},
					onFailure: function () {
						content.update("An error occured trying to refresh your session.");
					}
				});
			})
		));
	
		keepalivemodal.window_contents.update(content);
		keepalivemodal.open();
	}, timeout);
}
