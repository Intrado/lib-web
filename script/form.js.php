<?
require_once("../inc/subdircommon.inc.php");
require_once("../inc/utils.inc.php");
require_once("../obj/Validator.obj.php");

//set expire time to + 1 hour so browsers cache this file
header("Expires: " . gmdate('D, d M Y H:i:s', time() + 60*60) . " GMT"); //exire in 1 hour, but if theme changes so will hash pointing to this file
header("Content-Type: text/javascript");
header("Cache-Control: private");

?>

/* ======= BEGIN VALIDATORS =======  */
<? Validator::load_validators(array("ValRequired","ValLength","ValNumber","ValNumeric","ValEmail","ValEmailList","ValPhone","ValFieldConfirmation","ValInArray","ValDomain","ValTimeCheck","ValDomainList")); ?>
/* ======= END VALIDATORS =======  */

// TODO: Need to localize text

function form_event_handler (event) {
	var form = event.findElement("form");
	var formvars = document.formvars[form.name];
	var e = event.element();

	if (event.type == "keyup" && event.keyCode == Event.KEY_TAB || e.tagName.toLowerCase() == "label")
		return;

	if (formvars.keyuptimer) {
		if (formvars.keyupelement == e)
			window.clearTimeout(formvars.keyuptimer);
	}
	formvars.keyupelement = e;
	formvars.keyuptimer = window.setTimeout(function () {
			form_do_validation(form,e); formvars.keyuptimer = null;
		},
		event.type == "keyup" ? 1000 : 200
	);
}

function form_get_value (form,targetname) {
	var formvars = document.formvars[form.name];
	
	return formvars.jsgetvalue[targetname](form,targetname);
}

function form_default_get_value (form,targetname) {
	var value = "";

	try {
		value = $F(targetname) || "";
		return value.strip();
	} catch (e) {

		//prototype doesn't handle radio boxes or multicheckboxes so well, so try to handle them here
		var elements = form.elements[targetname] || form.elements[targetname + "[]"];
		if (elements.length) {

			switch (elements[0].type) {
			case "radio":
				for (var i = 0; i < elements.length; i++) {
					if (elements[i].checked) {
						value = elements[i].value.strip();
						break;
					}
				}
				break;
			case "checkbox":
				value = [];
				for (var i = 0; i < elements.length; i++) {
					if (elements[i].checked)
						value.push(elements[i].value);
				}
				break;
			}
		} else {
			//single item with braces?
			value = [];
			if (elements.checked)
				value.push(elements.value);
		}
	}

	return value;
}

function form_do_validation (form, element) {
	if (!element.name)
		return;
	var targetname = element.name;
	var formvars = document.formvars[form.name];
	targetname = targetname.replace("[]",""); //might need to strip off the some brackets from the name
	var itemname = targetname.split("_")[1];

	if (formvars.validators && formvars.validators[targetname]) {
		var validators = formvars.validators[targetname];
		var requiredfields = formvars.formdata[itemname].requires;
		var value = form_get_value(form,targetname);

		//set progress animation
		//if radio button, get the id of the container div
		var statusindicator = $((element.up(".radiobox") || element).id + "_icon");
		if (statusindicator)
			statusindicator.src = "img/ajax-loader.gif";;

		//see if we need additional fields for validation
		var requiredvalues = {};
		if (requiredfields) {
			for (var i = 0; i < requiredfields.length; i++) {
				var requiredname = requiredfields[i];
				requiredvalues[requiredname] = form_get_value(form,form.name+"_"+requiredname);
			}
		}

		//special case, if we are doing ajax call, then validators isn't an array, just call ajax for the result
		if (validators == "ajax") {
			//tack on some stuff to GET query (see in logs which POSTs are just validation) and hide the value (dont need to see that in logs)
			var posturl = formvars.scriptname + (formvars.scriptname.include('?') ? '&' : '?') + "ajaxvalidator=true&formitem=" + targetname;
			var postData = {
				value: value,
				requiredvalues: requiredvalues
			};
			new Ajax.Request(posturl, {
				method:'post',
				parameters: {json: Object.toJSON(postData)}, //do this so that arrays and such are sent (regular form encoded data is flat)
				onSuccess: function(response){
					var res = response.responseJSON;
					if (res.vres != true) {
						form_validation_display(element,"error",res.vmsg);
					} else {
						form_validation_display(element,"valid","");
					}
				},
				onFailure: function(){ alert('Something went wrong...') } //TODO better error handling
			});
		//otherwise, do normal client-side validation
		} else {
			for (var i = 0; i < validators.length; i++) {
				var v = validators[i];
				var res;
				if (value.length > 0 || v.validator == "ValRequired") {
					res = v.validate(v.name,v.label,value,v.args,requiredvalues);
					if (res != true) {
						form_validation_display(element,"error",res);
						return;
					}
				}
			}
			form_validation_display(element,"valid","");
		}
	}

}

// To know when a form's validation is displayed, register a callback on the element: element.observe('Form:ValidationDisplayed', function(event) { alert(event.memo.style); } );
// Style may be one of the following: "error", "valid", "blank"
function form_validation_display(element,style, msgtext) {
	e = $(element);

	//if radio button, get the id of the container div
	var name;
	if (e.up(".radiobox"))
		name = e.up(".radiobox").id;
	else
		name = e.id;

	var fieldarea = $(name + "_fieldarea");
	var icon = $(name + "_icon");
	var msg = $(name + "_msg");
	var css = 'background: rgb(255,255,255);';

	if (style == "error") {
		css = 'background: rgb(255,200,200);';
		if (icon) {
			icon.src = "img/icons/exclamation.gif";
			icon.alt = icon.title = "Validation Error";
		}
	} else if (style == "valid") {
		css = 'background: rgb(255,255,255);'; //rgb(225,255,225)
		if (icon) {
			icon.src = "img/icons/accept.gif";
			icon.alt = icon.title = "Valid";
		}
	} else if (style == "blank") {
		css = 'background: rgb(255,255,255); display: none';
		if (icon) {
			icon.src = "img/pixel.gif";
			icon.alt = icon.title = "";
		}
	}

	//set up the validation transition effects

	//for IE, make sure we dont fade between blank msgs or it will expand the msg box and move around
	if (msgtext.length == 0)
		msg.hide();
	else
		msg.show();

		//dont refade anything unless the message has changed or is in process of changing
	if ((msgtext.length == 0 || msgtext != msg.innerHTML) || fieldarea.bgeffect) {
		//set BG color
		if (fieldarea.bgeffect) {
			fieldarea.bgeffect.cancel();
			fieldarea.bgeffect = null;
		}
		fieldarea.bgeffect = new Effect.Morph(fieldarea,{style: css, duration: 0.5, afterFinish: function() {fieldarea.bgeffect = null;}});

		//set up 2 queued effects that will fade to new bg color, then swap text and fade in
		Effect.Queues.get(msg.id).each(function(effect) { effect.cancel(); });
		new Effect.Opacity(msg,{duration: 0.25, from:1, to:0, afterFinish: function () {msg.innerHTML = msgtext}, queue: { position: 'end', scope: msg.id }});
		new Effect.Opacity(msg,{duration: 0.25, from:0, to:1, afterFinish: function () {if(msgtext == "") msg.hide();}, queue: { position: 'end', scope: msg.id }});
	}

	e.fire('Form:ValidationDisplayed', {'style':style});
}

function form_make_validators(form, formvars) {
	var formdata = formvars.formdata;

	//make appropriate validators for each field
	for (fieldname in formdata) {
		var label = formdata[fieldname].label;
		var id = form.id+"_"+fieldname;
		
		var e = $(id);
		if (!e) {
			continue;
		}

		if (e.tagName.toLowerCase() == 'div' && e.hasClassName('radiobox')) {
			//attach event listeners to each of the radio boxes
			var children = e.childElements();
			for (var i = 0; i < children.length; i++) {
				var obj = children[i];
				obj.observe("click",form_event_handler);
				obj.observe("blur",form_event_handler);
				obj.observe("change",form_event_handler);
			}
		} else {
			if (e.type == "checkbox") {
				e.observe("change",form_event_handler);
				e.observe("click",form_event_handler);
			} else if (e.type.startsWith("select")) {
				e.observe("change",form_event_handler);
			}

			e.observe("blur",form_event_handler);
			e.observe("keyup",form_event_handler);
		}

		//if any of the validators is onlyserverside, then install a single ajax validator
		//still include ValRequired

		var validatordata = formdata[fieldname]['validators'];
		//create an initial array of validator instances from the data
		var validators = [];
		for (var i = 0; i < validatordata.length; i++) {
			var data = validatordata[i];
			var validatorname = data[0];
			validators.push(new document.validators[validatorname](fieldname,label,data));
		}

		//see if some of the validators have onlyserverside enabled
		var onlyserverside = false;
		for (var i = 0; i < validators.length; i++) {
			if (validators[i].onlyserverside)
				onlyserverside = true;
		}

		if (onlyserverside) {
			validators = "ajax"; //instead of an array
		}

		formvars.validators[id] = validators;
		formvars.jsgetvalue[id] = eval(formdata[fieldname].jsgetvalue);
	}
}

function form_load(name,scriptname,formdata, helpsteps, ajaxsubmit) {
	var form = $(name);
	//set up formvars to save data, avoid memleaks in IE by not attaching anything to dom elements
	if (!document.formvars) {
		document.formvars = {};
	}

	var formvars = document.formvars[name] = {
		formdata: formdata.length === 0 ? {} : formdata, // If this form has no formdata (such as one containing only formhtml), php's json_encode will have made it into an array instead of an object.
		scriptname: scriptname, //used for any ajax calls for this form
		helpsteps: helpsteps,
		ajaxsubmit: ajaxsubmit,
		helperdisabled: true,
		currentstep: null,
		validators: {},
		jsgetvalue: {},
		submitting: false
	};
	
	form_make_validators(form, formvars);

	//install helper focus handler
	form.select("input","textarea","select").map(function(e) {
		e.observe("focus",form_fieldset_event_handler);
	});

	//install click handlers for table form labels
	form.select('label.formlabel').map(function(e) {
		if (e.htmlFor) {
			var itemname = e.htmlFor.split("_")[1];
			if (formdata[itemname] && formdata[itemname]['fieldhelp']) {
				e.style.cursor="help";
				new Tip(e, formdata[itemname].fieldhelp, {
					title: formdata[itemname].label,
					style: 'protogrey',
					stem: 'bottomLeft',
					hook: { tip: 'bottomLeft', mouse: true },
					offset: { x: 14, y: 0 }
				});
			}
		}
	});
	
	//submit handler
	form.observe("submit",form_handle_submit.curry(name));
}

function form_fieldset_event_handler (event) {
	event = Event.extend(event);
	var form = event.findElement("form");
	var formvars = document.formvars[form.name];
	var e = event.element();

	var fieldset = e.up("fieldset");
	var step = fieldset.id.substring(fieldset.id.lastIndexOf("_")+1)-1;
	form_go_step(form,null,step);
}

function form_step_handler (event, direction) {
	event = Event.extend(event);
	var form = event.findElement("form");
	var helpercontent = $(form.id + "_helpercontent");

	//direct focus to helper for screen readers
	helpercontent.onblur = function () {this.removeAttribute("title");};
	helpercontent.tabIndex = -1;
	form_go_step(form,direction,null);
	helpercontent.focus();
	return false;
}
function form_go_step (form, direction, specificstep) {
	form = $(form);
	if (!form || !form.id)
		return false;
	var formvars = document.formvars[form.name];
	if (formvars.helperdisabled)
		return false;

	var helper = $(form.id + '_helper');
	var helperinfo = $(form.id + '_helperinfo');
	var helpercontent = $(form.id + "_helpercontent");

	var laststep = formvars.currentstep;
	if (specificstep || specificstep == 0) {
		formvars.currentstep = specificstep;
	} else {
		formvars.currentstep += direction;
	}

	formvars.currentstep = Math.min(formvars.currentstep,formvars.helpsteps.length-1);
	formvars.currentstep = Math.max(formvars.currentstep,0);

	if (laststep == formvars.currentstep)
		return false;

	//show/hide the buttons
	var leftarrow = helper.down(".toolbar img");
	var rightarrow = helper.down(".toolbar img",1);
	if (formvars.currentstep == 0) {
		leftarrow.src="img/pixel.gif";
	} else {
		leftarrow.src="img/icons/fugue/arrow_090.gif";
	}

	if (formvars.currentstep == formvars.helpsteps.length-1) {
		rightarrow.src="img/pixel.gif";
	} else {
		rightarrow.src="img/icons/fugue/arrow_270.gif";
	}

	//info text

	helpercontent.title = helperinfo.innerHTML = 'Step ' + (formvars.currentstep+1) + " of " + (formvars.helpsteps.length);
	helpercontent.innerHTML = formvars.helpsteps[formvars.currentstep];


	//find the section of the form for this step, blink it, and scroll to it
	var e;
	for (var i = 1; e = $(form.id + '_helpsection_'+i); i++) {
		e.style.border = "none";

		if (i == formvars.currentstep+1) {
			//cancel any previous effects
			Effect.Queues.get("helper").each(function(effect) { effect.cancel(); });

			var helper_y = e.offsetTop;
			var viewport_offset = Math.max(0, document.viewport.getHeight() - e.getHeight());

			e.style.border = "4px solid rgb(0,0,255)";
			new Effect.Morph(e, {style: 'border-color: rgb(150,150,255)', duration: 1.2, transition: Effect.Transitions.spring, queue: { scope: "helper"}});

			new Effect.Move(helper, { y:helper_y, mode:'absolute', duration: 0.8, queue: { scope: "helper"}});

			if (!(specificstep || specificstep == 0))
				new Effect.ScrollTo(e, {offset: -viewport_offset/2.0, duration: 0.6, queue: { scope: "helper"}});
		}
	}
	return false;
}

function form_enable_helper(event) {
	event = Event.extend(event);
	var form = event.findElement("form");
	var formvars = document.formvars[form.name];
	var helper = $(form.id + '_helper');
	var startbtn = $(form.id + '_startguide');

	if (startbtn)
		new Effect.Fade(startbtn,{duration: 0.5});

	//if user clicks start guide with it already open, just go to the first item //TODO go to a clicked (i) icon
	if (!formvars.helperdisabled) {
		form_go_step(form,null,0);
		return;
	}

	formvars.helperdisabled = false;

	new Effect.Morph(form.id + "_helpercell", {style: "width: 200px",
		afterFinish: function() {
			helper.style.display = "block";
			form_go_step(form,null,0);
		}
	});

	form.select("legend").map(function (e) {
		e = $(e);
		//e.style.display = "inline";
	});

	form.select("fieldset").map(function (e) {
		e.style.border = "none";
		//e.style.padding = "5px";
		//e.style.marginBottom = "3px";
	});
}

function form_disable_helper(event) {
	event = Event.extend(event);
	var form = event.findElement("form");
	var formvars = document.formvars[form.name];
	var helper = $(form.id + '_helper');
	var startbtn = $(form.id + '_startguide');

	if (startbtn)
		new Effect.Appear(startbtn,{duration: 0.5});

	formvars.helperdisabled = true;
	formvars.currentstep = -1;

	helper.style.display = "none";

	new Effect.Morph(form.id + "_helpercell", {style: "width: 100px",
		afterFinish: function () {
			form.select("legend").map(function (e) {
				e.style.display = "none";
			});
			form.select("fieldset").map(function (e) {
				e.style.border = "0px";
				e.style.padding = "0px";
				e.style.marginBottom = "0px";
			});
		}
	});
}


//used for submit buttons onclick, because we override default submit behavior, we need to
//insert something to mark which submit button was pressed.
//ie is retarded, and will actually put the button's html contents as the value, so we need another arg for that
//NOTICE: there are 2 param usage patterns, either specify the event and value, or value and form.
function form_submit (event, value, form) {
	if (!form) {
		event = Event.extend(event);
		form = event.findElement("form");
	}
	var formvars = document.formvars[form.name];

	var submit = document.createElement('input');
	submit.setAttribute('name','submit');
	submit.value = value;
	submit.setAttribute('type','hidden');
	form.appendChild(submit);

	form_handle_submit(form,event);
}

function form_handle_submit(form,event) {
	form = $(form);
	var formvars = document.formvars[form.name];

	//only continue here if we are going to override the default submit behavior
	if (!formvars.ajaxsubmit)
		return;
	if (event)
		Event.stop(event); //we'll take it from here with ajax

	//don't allow more than one submit at a time
	if (formvars.submitting)
		return;
	formvars.submitting = true;

	//prep an ajax call with entire form contents and post back to server
	//server side will validate
	//if successful, results with have some action to take and/or code
	//otherwise responce has validation results for each item,
	//update each element's msg area, and throw up an alert box explaining there are unresolved issues.

	//add an ajax marker
	
	var posturl = formvars.scriptname + (formvars.scriptname.include('?') ? '&' : '?') + "ajax=true";
	new Ajax.Request(posturl, {
		method:'post',
		parameters: form.serialize(true),
		onSuccess: function(response) {
			var res = response.responseJSON;
			try {
			if (res == null) {
				if (response.responseText.indexOf(" Login</title>") != -1) {
					alert('Your changes cannot be saved because your session has expired or logged out.');
					window.location="index.php?logout=1";
				} else {
					alert('There was a problem submitting the form. Please try again.');
				}
			} else if ("fail" == res.status) {
				//show the validation results
				if (res.validationerrors) {
					res.validationerrors.each(function(res) {
						try {
						var targetname = form.name+"_"+res.name;
						var element = $(targetname);
						var value = form_get_value(form,targetname);
						if (res.vres != true) {
							form_validation_display(element,"error",res.vmsg);
						} else {
							//checked out ok
							form_validation_display(element,"valid","");
						}
						} catch (error) { alert(res.name + " " + error)};
					});

					if (res.dontsaveurl) {
						if (confirm("There are some errors on this form.\nDo you want to continue anyway without saving changes?")) {
							window.location=res.dontsaveurl;
						}
					} else {
						if (!form.fire('AjaxForm:SubmitSuccess', {'errormessage':"There are some errors on this form.\nDo you want to continue anyway without saving changes?"}).stopped)
							alert("There are some errors on this form.\nPlease correct them before trying again.");
					}
				}

				if (res.datachange) {
					alert("The data on this form has changed.\nYour changes cannot be saved.");
					window.location=formvars.scriptname;
				}
			} else if ("success" == res.status) {
				if (!form.fire('AjaxForm:SubmitSuccess', res).stopped && res.nexturl)
					window.location=res.nexturl;
			} else if ("modify" == res.status) {
				$(res.name).update(res.content);
			}
			} catch (e) { alert(e.message + "\n" + response.responseText)}
			formvars.submitting = false;
		},
		onFailure: function(){
			alert('There was a problem submitting the form. Please try again.'); //TODO better error handling
			formvars.submitting = false;
		}
	});

}

// Takes a json encoded object of id to hover text associations and adds protoTip hover helps
function form_do_hover(hovers) {
	Object.keys(hovers).each(function(k) {
		var l = $(k);
		l.style.cursor="help";
		new Tip(l,hovers[k] ,{
			style: "protogrey",
			stem: "bottomLeft",
			hook: { tip: "bottomLeft", mouse: true },
			offset: { x: 10, y: 0 }
		});
	});
}

// Gives visual clue of how many characters are left
function form_count_field_characters(count,target,event) {
	var e = event.element();
	var status = $(target);
	var remaining = count - e.value.length;
	if (remaining < 0)
		$(target).innerHTML="Too many characters:&nbsp;<b style='color:red;'>" + (0 - remaining) + "</b>";
	else if (remaining <= 20)
		$(target).innerHTML="Characters remaining:&nbsp;<b style='color:orange;'>" + remaining + "</b>";
	else
		$(target).innerHTML="Characters remaining:&nbsp;" + remaining;
}

function form_submit_all (tabevent, value, formsplittercontainer) {
	saveHtmlEditorContent();
	var forms = $$('form');
	if (forms.length < 1)
		return;
	
	var beforesubmitallcontainer = tabevent ? tabevent.memo.widget.container : formsplittercontainer;
	if (beforesubmitallcontainer) {
		if (beforesubmitallcontainer.fire('FormSplitter:BeforeSubmitAll', {'forms':forms, 'value':value}).stopped)
			return;
	}
	
	// Map used to store the results of the form submissions.
	var submissions = {};

	for (var i = 0; i < forms.length; i++) {
		var form = forms[i];
		
		var submission = submissions[form.name] = {'submitted':false, 'form':form, 'value':value};
		
		// Clear existing observers.
		form.stopObserving('AjaxForm:SubmitSuccess');
		
		form.observe('AjaxForm:SubmitSuccess', function(ajaxevent, tabevent, submissions) {
			if (!ajaxevent.memo.errormessage)
				ajaxevent.stop();
			var thissubmission = submissions[this.name];
			thissubmission.response = ajaxevent.memo;
			
			var formvars = document.formvars[this.name];
			// Prepare a clean url without any GET parameters.
			var indexofquestionmark = formvars.scriptname.indexOf('?');
			var cleanurl = (indexofquestionmark >= 0) ? formvars.scriptname.substring(0, indexofquestionmark) : formvars.scriptname;
			var posturl = cleanurl + "?ajax=true";
			
			// Update this form's serial number only if this form will not get reloaded in the next tab.
			var nexttab = tabevent ? tabevent.memo.section : null;
			if (!ajaxevent.memo.errormessage) {
				if (tabevent && (nexttab == tabevent.memo.currentSection || !tabevent.memo.widget.sections[nexttab].contentDiv.down('form#' + this.name))) {
					var errortext = '<?=addslashes(_L("Sorry, an erorr occurred. This page will now reload"))?>';
					
					new Ajax.Request(posturl, {
						method: 'post',
						parameters: {'formsnum': this.name},
						onSuccess: function (response) {
							var data = response.responseJSON;
							
							if (!data || !data.formsnum) {
								alert(errortext);
								window.location = cleanurl;
								return;
							}
							
							var formsnumfield = this.down('input[name="' + this.name + '-formsnum' + '"]');
							if (formsnumfield) {
								formsnumfield.value = data.formsnum;
							}
						}.bindAsEventListener(this),
						onFailure: function(response) {
							alert(errortext);
							window.location = cleanurl;
						}.bindAsEventListener(this)
					});
				}
				
				for (var formname in submissions) {
					var submission = submissions[formname];
					if (!submission.submitted) {
						// Submit the next form; other forms will be submitted sequentially upon the next AjaxForm:SubmitSuccess.
						submission.submitted = true;
						form_submit(null, submission.value, submission.form);
						return;
					}
					
					// If any form has an error, abort.
					// NOTE: Will need to cause all forms to validate.
					if (!submission.response || !submission.response.status || submission.response.status == 'error') {
						return;
					}
				}
			}
			
			// At this point, either an error occurred or all forms have been submitted successfully.
			if (tabevent && nexttab != tabevent.memo.currentSection) {
				var gotonexttab = true;
				
				if (ajaxevent.memo.errormessage) {
					ajaxevent.stop();
					
					if (!confirm(ajaxevent.memo.errormessage))
						gotonexttab = false;
				}
				
				if (gotonexttab) {
					// If the tab getting replaced contains the html editor, move the editor down to the bottom of the document body and hide it for later use.
					if (tabevent.memo.widget.container.down('#cke_reusableckeditor')) {
						hideHtmlEditor();
					}
					
					
					var handledbeforetabloadevent = tabevent.memo.widget.container.fire('FormSplitter:BeforeTabLoad', {'tabevent': tabevent, 'nexttab': nexttab, 'currenttab': tabevent.memo.currentSection});

					if (!handledbeforetabloadevent.stopped) {
						tabevent.memo.widget.update_section(nexttab, {
							'icon': 'img/ajax-loader.gif'
						});
						
						form_load_tab(this, tabevent.memo.widget, nexttab, handledbeforetabloadevent.memo.specificsections);
					}
				}
			} else if (!tabevent) {
				if (ajaxevent.memo.nexturl)
					window.location = ajaxevent.memo.nexturl;
			}
		}.bindAsEventListener(form, tabevent, submissions));
		
		if (i == 0) {
			// Submit the first form; other forms will be submitted sequentially upon AjaxForm:SubmitSuccess.
			submission.submitted = true;
			form_submit(null, value, form);
		}
	}
}

function form_load_tab (form, widget, nexttab, specificsections) {
	var formvars = document.formvars[form.name];
	
	if (!document.tabvars)
		document.tabvars = {};
		
	if (document.tabvars.loading) {
		return;
	}
	
	document.tabvars.loading = true;
	
	// Prepare a clean url without any GET parameters.
	var indexofquestionmark = formvars.scriptname.indexOf('?');
	var cleanurl = (indexofquestionmark >= 0) ? formvars.scriptname.substring(0, indexofquestionmark) : formvars.scriptname;
	var posturl = cleanurl + "?ajax=true";
	
	new Ajax.Request(posturl, {
		method: 'post',
		parameters: {'loadtab': nexttab, 'specificsections': specificsections ? specificsections.toJSON() : ''},
		onSuccess: function (response, widget, nexttab, specificsections) {
			document.tabvars.loading = false;
			
			var data = response.responseJSON;
			
			if (!data || !data.element) {
				alert('<?=addslashes(_L("Sorry, there is an error loading this tab."))?>');
				return;
			}
			
			// Clear any html editor listeners.
			registerHtmlEditorKeyListener(null);
			
			widget.update_section(data.element, {
				'icon': 'img/pixel.gif',
				'content': data.content
			});
			
			var formcontainer = $(data.element);
			var container = formcontainer.match('form.FormSplitterParentForm') ? formcontainer.up('.FormSplitterParentFormContainer') : formcontainer.up();
			form_load_layout(container, null, specificsections);
			var previoustab = widget.currentSection;
			widget.show_section(nexttab);
			// NOTE: To prevent flickering, clear the contents of the previous tab after the next tab is shown.
			if (previoustab != nexttab) {
				widget.update_section(previoustab, {
					'content': new Element('div')
				});
			}
			
			widget.container.fire('FormSplitter:TabLoaded', {'form': this, 'data': data, 'tabloaded': nexttab, 'previoustab': previoustab, 'widget':widget});
		}.bindAsEventListener(form, widget, nexttab, specificsections),
		
		onFailure: function() {
			document.tabvars.loading = false;
			alert('<?=addslashes(_L("Sorry, there is an error loading this tab."))?>');
		}
	});
}

function form_load_layout (formswitchercontainer, classname, specificsections) {
	// If only the container is specified, go ahead and load all the various layouts.
	if (!classname) {
		form_load_layout(formswitchercontainer, 'horizontaltabs', specificsections);
		form_load_layout(formswitchercontainer, 'verticaltabs', specificsections);
		form_load_layout(formswitchercontainer, 'accordion');
		form_load_layout(formswitchercontainer, 'verticalsplit');
		return;
	}
	
	formswitchercontainer.select('.' + classname).each(function(container, classname) {
		var kids = container.childElements();

		var layoutsections = kids.findAll(function(kid) {
			return kid.match('.FormSwitcherLayoutSection');
		});

		var layout;
		if (classname == 'horizontaltabs' || classname == 'verticaltabs' || classname == 'accordion') {
			if (classname == 'horizontaltabs')
				layout = new Tabs(container, {'vertical':false, 'showDuration':0, 'hideDuration':0});
			else if (classname == 'verticaltabs')
				layout = new Tabs(container, {'vertical':true, 'showDuration':0, 'hideDuration':0});
			else if (classname == 'accordion') {
				layout = new Accordion(container);
			}
			
			layoutsections.each(function (layoutsection, specificsections) {
				var kids = layoutsection.childElements();
				var sectionname =  layoutsection.match('.FormSplitterParentFormContainer') ? layoutsection.down('form.FormSplitterParentForm').identify() : layoutsection.identify();
				var titlespan = kids.find(function (el) { return el.match('.FormSwitcherLayoutSectionTitle'); });
				var iconimg = kids.find(function (el) { return el.match('.FormSwitcherLayoutSectionIcon'); });

				this.add_section(sectionname);
				this.update_section(sectionname, {
					'title': titlespan,
					'icon': iconimg ? iconimg : 'img/pixel.gif',
					'content': layoutsection
				});

				if (!this.firstSection)
					this.firstSection = sectionname;
			}.bindAsEventListener(layout, specificsections));

			if (classname != 'accordion' && specificsections) {
				for (var i = 0; i < specificsections.length; i++) {
					var specificsection = specificsections[i];
					if (layout.sections[specificsection]) {
						layout.show_section(specificsection);
						break;
					}
				}
			} else if (classname != 'accordion') {
				layout.show_section(layout.firstSection);
			} else {
				var form = container.up('form');
				if (form) {
					var formvars = document.formvars[form.name];
					if (!formvars)
						formvars = document.formvars[form.name] = {};
					formvars.accordion = layout;
					form.fire('FormSplitter:AccordionLoaded');
				}
			}

		} else if (classname == 'verticalsplit') {
			var split = make_split_pane(true, layoutsections.length);

			for (var i = 0; i < layoutsections.length; i++) {
				split.down('.SplitPane', i).insert(layoutsections[i]);
			}

			container.insert(split);
		}

	}.bindAsEventListener(this, classname));
}

function form_init_splitter(formswitchercontainer, specificsections) {
	var formswitchercontainer = $(formswitchercontainer);
	
	form_load_layout(formswitchercontainer, null, specificsections);

	var onTabsClickTitle = function(event) {
		event.stop();
		if (!this.fire('FormSplitter:BeforeSubmit', event.memo).stopped)
			form_submit_all(event, 'tab');
	};
	formswitchercontainer.observe('Tabs:ClickTitle', onTabsClickTitle.bindAsEventListener(formswitchercontainer));
}
