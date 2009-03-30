<?
require_once("../obj/Validator.obj.php");

//set expire time to + 1 hour so browsers cache this file
header("Expires: " . gmdate('D, d M Y H:i:s', time() + 60*60) . " GMT"); //exire in 1 hour, but if theme changes so will hash pointing to this file
header("Content-Type: text/javascript");
header("Cache-Control: private");

?>

/* ======= BEGIN VALIDATORS =======  */
<? Validator::load_validators(array("ValRequired","ValLength","ValNumber","ValNumeric")); ?>
/* ======= END VALIDATORS =======  */


function form_event_handler (event) {
	var form = event.findElement("form");
	var e = event.element();
				
	if (event.type == "keyup" && event.keyCode == Event.KEY_TAB)
		return;
	
	if (form.keyuptimer) {
		if (form.keyupelement == e)
			window.clearTimeout(form.keyuptimer);
	}
	form.keyupelement = e;
	form.keyuptimer = window.setTimeout(function () { 
			form_do_validation(form,e); form.keyuptimer = null;
		}, 
		event.type == "keyup" ? 750 : 200
	);

}

function form_get_value (form,targetname) {
	var value = "";
	
	try {
		value = $F(targetname);
		return value;
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
		}
	}
	
	return value;
}

function form_do_validation (form, element) {
	var targetname = element.name;
	
	//might need to strip off the some brackets from the name
	targetname = targetname.replace("[]","");
	
	if (form.validators && form.validators[targetname]) {
		var validators = form.validators[targetname];
		var value = form_get_value(form,targetname);
		
		//special case, if we are doing ajax call, then validators isn't an array, just call ajax for the result
		if (validators == "ajax") {	
			//tack on some stuff to GET query (see in logs which POSTs are just validation) and hide the value (dont need to see that in logs)
			var posturl = form.scriptname + (form.scriptname.include('?') ? '&' : '?') + "ajaxvalidator=true&formitem=" + targetname;
			new Ajax.Request(posturl, {
				method:'post',
				parameters: {value: value},
				onSuccess: function(response){
					var res = response.responseJSON;
					if (res.vres != true) {
						form_validation_display(element,"error",res.vmsg);
					} else {
						//checked out ok
						if (value.length > 0)
							form_validation_display(element,"valid","OK");
						else
							form_validation_display(element,"blank","");
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
					res = v.validate(v.name,v.label,value,v.args);
					if (res != true) {
						form_validation_display(element,"error",res);
						return;
					}
				}
			}
			if (value.length > 0) 
				form_validation_display(element,"valid","OK");
			else
				form_validation_display(element,"blank","");
		}
	}

}

function form_validation_display(element,style, msgtext) {
	e = $(element);
		
	//if radio button, get the id of the container div
	var name;
	if (e.up().match(".radiobox"))
		name = e.up().id;
	else
		name = e.id;
	
	var fieldarea = $(name + "_fieldarea");
	var icon = $(name + "_icon");
	var msg = $(name + "_msg");
	var css = 'background: rgb(255,255,255);';
		
	if (style == "error") {
		css = 'background: rgb(255,200,200);';
		icon.src = "img/icons/exclamation.gif";
	} else if (style == "valid") {
		css = 'background: rgb(225,255,225);';
		icon.src = "img/icons/accept.gif";
	} else if (style == "blank") {
		css = 'background: rgb(255,255,255);';
		icon.src = "img/pixel.gif";
	}
	
	//set up the validation transition effects
	
	//dont refade anything unless the message has changed or is in process of changing
	if (msgtext != msg.innerHTML || fieldarea.bgeffect) {
		//set BG color
		if (fieldarea.bgeffect) {
			fieldarea.bgeffect.cancel();
			fieldarea.bgeffect = null;
		}
		fieldarea.bgeffect = new Effect.Morph(fieldarea,{style: css, duration: 0.5, afterFinish: function() {fieldarea.bgeffect = null;}});
		
		//set up 2 queued effects that will fade to new bg color, then swap text and fade in
		Effect.Queues.get(msg.id).each(function(effect) { effect.cancel(); });
		new Effect.Opacity(msg,{duration: 0.25, from:1, to:0, afterFinish: function () {msg.innerHTML = msgtext}, queue: { position: 'end', scope: msg.id }});
		new Effect.Opacity(msg,{duration: 0.25, from:0, to:1, queue: { position: 'end', scope: msg.id }});
	}
}

//exclamation.png - !
//error.png - hazard
//accept.png - check
function form_load(name,scriptname,formdata, helpsteps, ajaxsubmit) {
	var form = $(name);
	form.formdata = formdata;
	form.scriptname = scriptname; //used for any ajax calls for this form
	form.helpsteps = helpsteps;
	form.ajaxsubmit = ajaxsubmit;
	form.currentstep = 0;
	form.validators = {};
	//make appropriate validators for each field
	for (fieldname in formdata) {		
		var label = formdata[fieldname].label;
		var id = form.id+"_"+fieldname;
		var e = $(id);
		
		if (e.tagName.toLowerCase() == 'div' && e.hasClassName('radiobox')) {
			//attach event listeners to each of the radio boxes
			e.childElements().each(function (obj) {
				obj.observe("click",form_event_handler);
				obj.observe("blur",form_event_handler);
				obj.observe("change",form_event_handler);
			});
		} else {
			if (e.type == "checkbox") {
				e.observe("change",form_event_handler);
			} else if (e.type == "select" || e.type == "select-multiple") {
				e.observe("change",form_event_handler);
			}
			
			e.observe("blur",form_event_handler);
			e.observe("keyup",form_event_handler);
		}
		
		//if any of the validators is onlyserverside, then install a single ajax validator
		//still include ValRequired
		
		var validatordata = formdata[fieldname]['validators'];
		//create an initial array of validator instances from the data
		var validators = validatordata.map(function (data) {
			var validatorname = data[0];
			return new document.validators[validatorname](e,fieldname,label,data);
		});
		
		//see if some of the validators have onlyserverside enabled
		var onlyserverside =  validators.some(function (validator) {
			return validator.onlyserverside;
		});
		
		if (onlyserverside) {
			validators = "ajax"; //market instead of an array
		}
		
		e.validators = form.validators[id] = validators;
	}
	
	//install click handlers for (name + '_helper') hrefs
	$(form.id + "_helper").down("a",0).observe("click",function (event) {form_step_handler(form,-1,null); Event.stop(event);});
	$(form.id + "_helper").down("a",1).observe("click",function (event) {form_step_handler(form,+1,null); Event.stop(event);});
	
	//install click handlers for fieldsets
	form.select('fieldset').map(function(e) { e.observe("click",form_fieldset_handler)});
	
	//submit handler
	form.observe("submit",form_handle_submit.curry(form));
}

function form_fieldset_handler (event) {
	var form = event.findElement("form");
	var e = event.element();
	
	if (["label","input","textarea","select","option"].indexOf(e.tagName.toLowerCase()) == -1 && !e.match(".radiobox")) {		
		var fieldset = e.match("fieldset") ? e : e.up("fieldset");
		var step = fieldset.id.substring(fieldset.id.lastIndexOf("_")+1);
		form_step_handler(form,null,step);
	}
}


function form_step_handler (form, direction, specificstep) {
	form = $(form);
	if (!form || !form.id)
		return false;
	
	if (form.scrolling)
		return false;
		
	if (specificstep) {
		form.currentstep = specificstep;
	} else {
		form.currentstep += direction;
	}
	
	form.currentstep = Math.min(form.currentstep,form.helpsteps.length-1);
	form.currentstep = Math.max(form.currentstep,1);
	
	$(form.id + "_helpercontent").innerHTML = form.helpsteps[form.currentstep];
	
	//find the section of the form for this step, blink it, and scroll to it
	var e;
	for (var i = 1; e = $(form.id + '_helpsection_'+i); i++) {
		e.style.border = 'none';
		
		if (i == form.currentstep) {
			form.scrolling = true;
			var helper_y = e.offsetTop;
			var viewport_offset = Math.max(0, document.viewport.getHeight() - e.getHeight());

			//new Effect.Morph(e, {style: 'border-color: rgb(255,200,100);', duration: 0.8, transition: Effect.Transitions.pulse});
			e.style.border = "2px solid rgb(255,200,100)";
			
			new Effect.Move(form.id + '_helper', { y:helper_y, mode:'absolute', duration: 0.8,
				afterFinish: function() {
					form.scrolling = false;
				}
			});
			if (!specificstep)
				new Effect.ScrollTo(e, {offset: -viewport_offset/2.0, duration: 0.6});
		}
	}
	return false;
}


//used for submit buttons onclick, because we override default submit behavior, we need to
//insert something to mark which submit button was pressed.
//ie is retarded, and will actually put the button's html contents as the value, so we need another arg for that
function form_submit (event, value) {
	event = Event.extend(event);

	var form = event.findElement("form");
	var e = event.element();

	var submit = document.createElement('input');
	submit.setAttribute('name','submit');
	submit.value = value || e.value;
	submit.setAttribute('type','hidden');
	form.appendChild(submit);
	
	form_handle_submit(form,event);
}

function form_handle_submit(form,event) {
	form = $(form);
	//only continue here if we are going to override the default submit behavior
	if (!form.ajaxsubmit)
		return;
	
	Event.stop(event); //we'll take it from here with ajax
		
	//prep an ajax call with entire form contents and post back to server
	//server side will validate
	//if successful, results with have some action to take and/or code
	//otherwise responce has validation results for each item,
	//update each element's msg area, and throw up an alert box explaining there are unresolved issues.

	//add an ajax marker
	var posturl = form.scriptname + (form.scriptname.include('?') ? '&' : '?') + "ajax=true";
	new Ajax.Request(posturl, {
		method:'post',
		parameters: form.serialize(true),
		onSuccess: function(response) {
			var res = response.responseJSON;
			
			if ("fail" == res.status) {
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
							if (value.length > 0)
								form_validation_display(element,"valid","OK");
							else
								form_validation_display(element,"blank","");
						}
						} catch (error) { alert(res.name + " " + error)};
					});
				
					alert("There are some errors on this form.\nPlease correct them before trying again.");
				}
				
				if (res.datachange) {
					alert("The data on this form has changed.\nYou're changes cannot be saved.");
					window.location=form.scriptname;
				}
				
			} else if ("success" == res.status) {
				
				if (res.nexturl)
					window.location=res.nexturl;
				
			}
		},
		onFailure: function(){ alert('Something went wrong...') } //TODO better error handling
	});
	
}
