<?
require_once("../obj/Validator.obj.php");

//set expire time to + 1 hour so browsers cache this file
header("Expires: " . date("r",time() + 60*60));
header("Content-Type: text/javascript");

?>

/* ======= BEGIN VALIDATORS =======  */
<? load_validators(array("ValRequired","ValLength","ValNumber","ValNumeric")); ?>
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

function form_get_value (form, element) {
	var value;
		
	switch (element.type) {
		default:
		case "text": value = element.value.strip(); break;
		case "checkbox": value = element.checked ? "true": ""; break;
		case "radio": 
			value = "";
			//for radio boxes, we actually wrap them in a div with an ID for the control name
			//to get the value, we get the div, and check each child input for being 'checked'
			var radios = $(element.name).childElements();
			for (var i = 0; i < radios.length; i++) {
				if (radios[i].checked)
					value = radios[i].value;
			}
			break;
	}
	return value;
}

function form_do_validation (form, element) {
	var targetname = element.name;
	
	if (form.validators && form.validators[targetname]) {
		var validators = form.validators[targetname];
		var value = form_get_value(form,element);
		
		if (validators == "ajax") {
			//special case, if we are doing ajax call, then validators isn't an array, just call ajax for the result
			
			new Ajax.Request(form.scriptname,
				{
					method:'get',
					parameters: {ajax: true,
								formitem: targetname,
								value: value},
					onSuccess: function(transport){
						var response = transport.responseJSON;
						if (response.validatorresult != true) {
							form_validation_display(element,"error",response.validatormsg);
						} else {
							//checked out ok
							if (value.length > 0)
								form_validation_display(element,"valid","OK");
							else
								form_validation_display(element,"blank","");
						}
					},
					onFailure: function(){ alert('Something went wrong...') }
				});

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
	
	var fieldarea = $(e.id + "_fieldarea");
	var icon = $(e.id + "_icon");
	var msg = $(e.id + "_msg");
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

function form_highlight_section (num) {
	var e;
	for (var i = 1; e = $('helpsection_'+i); i++) {
		e.style.borderColor = 'rgb(255,255,255)';
		if (i == num) {
			new Effect.Morph(e, {style: 'border-color: rgb(255,200,100);', duration: 1.2, transition: Effect.Transitions.pulse});
		}
	}
}


//exclamation.png - !
//error.png - hazard
//accept.png - check
function form_load(name,scriptname,formpres,formdata) {
	var form = $(name);
	form.scriptname = scriptname; //used for any ajax calls for this form
	form.validators = {};
	//make appropriate validators for each field
	for (fieldname in formpres) {		
		var label = formpres[fieldname][1];
		var id = form.id+"_"+fieldname;
		var e = $(id);
		
		if (e.tagName.toLowerCase() == 'div' && e.hasClassName('radiobox')) {
			//attach event listeners to each of the radio boxes
			e.childElements().each(function (obj) {
				e.observe("click",form_event_handler);
				obj.observe("blur",form_event_handler);
				obj.observe("change",form_event_handler);
			});
		} else {
			e.observe("blur",form_event_handler);
			//e.observe("change",form_event_handler);
			e.observe("keyup",form_event_handler);
		}
		
		//if any of the validators is onlyserverside, then install a single ajax validator
		//still include ValRequired
		
		var validatordata = formdata[fieldname][1];
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
	
	
}
