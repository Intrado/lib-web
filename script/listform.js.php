<?php
require_once("../inc/subdircommon.inc.php");
require_once("../inc/html.inc.php");

//set expire time to + 1 hour so browsers cache this file
header("Expires: " . gmdate('D, d M Y H:i:s', time() + 60*60) . " GMT"); //exire in 1 hour, but if theme changes so will hash pointing to this file
header("Content-Type: text/javascript");
header("Cache-Control: private");
?>

var listformVars = null;
var ruleWidget = null;
var ruleEditor = null;
var accordion = null;
var addmeOriginalValues = {phone:false, email:false, sms:false}; // Used to restore to valid values when the addme checkbox is unchecked, so that addMeWindow validators do not complain.
var hoverTimer = null;
var chosenLists = [];
var forcevalidator = null;

// Modified form load.
function listform_load(listformID, formData, postURL) {
	var form = $(listformID);
	//set up formvars to save data, avoid memleaks in IE by not attaching anything to dom elements
	if (!document.formvars) {
		document.formvars = {};
		document.formvars[listformID] = {
			id: listformID,
			listidsElement: $(listformID + '_listids'),
			formdata: formData,
			scriptname: postURL, //used for any ajax calls for this form
			ajaxsubmit: true,
			validators: {},
			jsgetvalue: {}
		};
		// Make a global reference.
		listformVars = document.formvars[listformID];
	}
	form_make_validators(form, listformVars);
	listformVars.validators['listChoose_listids'] = 'ajax';
	listformVars.jsgetvalue['listChoose_listids'] = form_default_get_value;

	//submit handler
	form.observe('submit',form_handle_submit.curry(listformVars.id));

	$('listChoose_listids_icon').observe('load', function() {
		var icon = $('listChoose_listids_icon');
		if (!icon.src.match(/exclamation/)) {
			icon.hide();
		} else {
			icon.show();
		}
	});

	// Accordion
	accordion = new Accordion('accordionContainer');

	accordion.add_section('buildlist', true);
	
	var sectionsWindow = $('chooseSectionsWindow');
	if (sectionsWindow) {
		accordion.add_section('choosesections', true);
	}
	accordion.add_section('chooselist', true);
	accordion.add_section('addme');

	accordion.update_section('buildlist', {
		"title": "<?=addslashes(_L('Build List Using Rules'))?>",
		"icon": "img/icons/application_form_edit.gif",
		"content": $('buildListWindow').remove()
	});
	if (sectionsWindow) {
		accordion.update_section('choosesections', {
			"title": "<?=addslashes(_L('Build List Using Sections'))?>",
			"icon": "img/icons/application_form_edit.gif",
			"content":sectionsWindow.remove()
		});
	}
	accordion.update_section('chooselist', {
		"title": "<?=addslashes(_L('Choose an Existing List'))?>",
		"icon": "img/icons/application_view_list.gif",
		"content": $('chooseListWindow').remove()
	});
	accordion.update_section('addme', {
		"title": "<?=addslashes(_L('Add Myself'))?>",
		"icon": "img/icons/user.gif",
		"content": $('addMeWindow').remove()
	});

	// SectionWidget
	if (sectionsWindow) {
		var createlistbutton = icon_button('Create This List','tick').observe('click', function() {
			var value = $('listChoose_sectionwidget').value.strip();
		
			if ($('listChoose_sectionwidgetorganizationselectbox').selectedIndex == 0 || value == "") {
				alert('Please choose a section.');
				return;
			}
		
			new Ajax.Request('ajaxlistform.php?type=createlist', {
				'method': 'post',
				'parameters': {
					'sectionids': value
				},
				'onSuccess': function(transport) {
					$('listsTableStatus').update();
					var listid = transport.responseJSON;
					if (!listid) {
						alert('<?=addslashes(_L('Sorry, you are not able to create lists'))?>');
						return;
					} else if (typeof listid.error === 'string') {
						alert(listid.error);
						return;
					}
					listformVars.pendingList = null;
					listform_add_list(listid);
					accordion.collapse_all();
					$('listChoose_sectionwidgetorganizationselectbox').selectedIndex = 0;
					$('listChoose_sectionwidgetsectioncheckboxescontainer').update();
				},
				'onFailure': function() {
					alert('There is a connection problem.');
				}
			});
		});
		sectionsWindow.insert({'after': createlistbutton});
		createlistbutton.insert({'after': new Element('div', {'style':'clear:both'})});
	}
	
	// RuleWidget
	ruleWidget = new RuleWidget($('ruleWidgetContainer'));
	ruleEditor = ruleWidget.ruleEditor;

	var buildListFieldset = new Element('fieldset',{'style':'border: 0; margin:0px; margin-top:20px; padding:0px;'});
	buildListFieldset.insert('<?=addslashes(icon_button(_L('Done Adding Rules To This List'),'accept',null,null, ' id="saveRulesButton" '))?>');
	ruleWidget.container.insert(buildListFieldset);

	ruleWidget.container.observe('RuleWidget:ChangeField', function(event) {
		if (event.memo.fieldnum)
			listform_set_rule_editor_status(true);
		else
			listform_set_rule_editor_status(false);
	});

	$('saveRulesButton').observe('focus', function() {
		$('saveRulesButton').focus();
	});

	ruleWidget.container.observe('RuleWidget:InColumn', function(event) {
		if (ruleEditor.get_selected_fieldmap())
			listform_set_rule_editor_status(true);
		else
			listform_set_rule_editor_status(false);
	});

	ruleWidget.container.observe('RuleWidget:AddRule', function(event) {
		$('saveRulesButton').focus();

		listform_set_rule_editor_status(false);

		$('listsTableStatus').update('<img src="img/ajax-loader.gif"/>');
		new Ajax.Request('ajaxlistform.php?type=addrule&listid=' + listformVars.pendingList, {
			parameters: {
				'ruledata': event.memo.ruledata.toJSON()
			},
			method: 'post',
			onSuccess: function(transport) {
				$('listsTableStatus').update();
				
				if (!transport.responseJSON) {
					alert('<?=addslashes(_L("Sorry, cannot save this rule"))?>');
					return;
				}

				var listid = listformVars.pendingList;

				listform_refresh_preview(listid);

				if (!$('listsTableBody').down('input[value='+listid+']')) { // Add to the lists table if the pending list hasn't already been added.
					listform_add_list(listid);
				} else {
					listform_refresh_liststats(listid, true);
				}
			}
		});
	});
	ruleWidget.container.observe('RuleWidget:DeleteRule', function(event) {
		listform_set_rule_editor_status(false);

		$('listsTableStatus').update('<img src="img/ajax-loader.gif"/>');
		new Ajax.Request('ajaxlistform.php?type=deleterule&listid=' + listformVars.pendingList, {
			parameters: {
				'fieldnum': event.memo.fieldnum
			},
			method: 'post',
			onSuccess: function(transport) {
				if (!transport.responseJSON) {
					alert('<?=addslashes(_L("Sorry, there was an error when deleting this rule"))?>');
					return;
				}

				listform_refresh_preview(listformVars.pendingList);

				if ($H(ruleWidget.appliedRules).keys().length <= 0) {
					listform_remove_list(null, listformVars.pendingList);
				} else {
					listform_refresh_liststats(listformVars.pendingList, true);
				}
				$('listsTableStatus').update();
			}
		});
	});


	accordion.container.observe('Accordion:ClickTitle', function(event) {
		Tips.hideAll();

		// Warn if closing the AddMe section when there are validation errors.
		if (accordion.currentSection == 'addme') {
			validate_addme_fields();

			var validationIcons = $('addMeWindow').select('img');
			var hasError = false;
			for (var i = 0; i < validationIcons.length; i++) {
				var icon = validationIcons[i];
				if (icon.src.match(/exclamation/)) {
					hasError = true;
					break;
				}
			}
			if (hasError) {
					alert("There are some errors on this form.\nPlease correct them before trying again.");
					event.stop();
					return;
			}
		} else if (accordion.currentSection == 'buildlist') {
			if (ruleEditor.get_selected_fieldmap()) {
					if (!listform_warn_add_rule()) {
						event.stop();
						return;
					}
			}
		}

		switch (event.memo.section) {
			case 'buildlist':
				ruleWidget.refresh_guide(true);

				$('listchooseTotal').update();
				$('listchooseTotalAdded').update();
				$('listchooseTotalRemoved').update();
				$('listchooseTotalRule').update();

				if (!listformVars.pendingList) {
					ruleWidget.clear_rules();
					new Ajax.Request('ajaxlistform.php?type=createlist', {
						onSuccess: function(transport) {
							$('listsTableStatus').update();
							var listid = transport.responseJSON;
							if (!listid) {
								alert('<?=addslashes(_L('Sorry, you are not able to build lists'))?>');
								return;
							}

							listformVars.pendingList = listid;
						},
						onFailure: function() {
							alert('There is a connection problem.');
						}
					});
				}
				break;

			case 'choosesections':
				listformVars.pendingList = null;
				ruleWidget.refresh_guide();
				break;
				
			case 'chooselist':
				listformVars.pendingList = null;
				ruleWidget.refresh_guide();
				break;
				
			case 'addme':
				break;
		}
	});
	// allListsWindow: Build a List Using Rules Buttons
	ruleWidget.container.observe('RuleWidget:Ready', function() {
		<?
		if (!$USER->authorize('createlist')) {
			echo "
				accordion.enable_section('chooselist');
				return;
			";
		}
		?>

		accordion.enable_section('chooselist');
		accordion.enable_section('buildlist');
		if (sectionsWindow) {
			accordion.enable_section('choosesections');
		}

		// buildListWindow: Save Rules Button
		$('saveRulesButton').observe('click', function(event) {
			Tips.hideAll();
			if (ruleEditor.get_selected_fieldmap()) {
				if (!listform_warn_add_rule())
					return;
			}

			listformVars.pendingList = null;
			accordion.collapse_all();
			ruleWidget.refresh_guide();
		});
	});

	listform_load_cached_list();
}

function listform_load_cached_list() {
	// allListsWindow: Grand Total
	listformVars.totals = {};

	// Load Existing Lists
	cachedAjaxGet('ajax.php?type=lists',
		function(transport) {
			listformVars.existingLists = transport.responseJSON;
			if (!listformVars.existingLists)
				listformVars.existingLists = {};
			listform_reset_list_selectbox();

			// Load From Session Data.
			var listids = listformVars.listidsElement.value ? listformVars.listidsElement.value.evalJSON() : [];
			if (listids.join && listids.length > 0) {
				chosenLists = listids;
				listform_load_lists(chosenLists.toJSON(), true);
			} else {
				ruleWidget.refresh_guide(true);
			}
		}
	);
}

function listform_warn_add_rule() {
	if (confirm('<?=addslashes(_L("You are in the middle of adding a rule, are you sure you want to discard it?"))?>')) {
		ruleWidget.clear_rules();
		return true;
	}
	return false;
}

// Adds listid to listformVars.listidsElement
function listform_add_list(listid) {
	if (!listid.strip()) {
		alert('<?=addslashes(_L('Please select a list'))?>');
		return false;
	}

	var listids = listformVars.listidsElement.value ? listformVars.listidsElement.value.evalJSON() : [];
	if (!listids.join)
		listids = [];
	listids = listids.without(listid);
	listids.push(listid);
	listformVars.listidsElement.value = listids.toJSON();

	if (listid.indexOf('addme') < 0)
		listform_load_lists([listid].toJSON());

	return true;
}

function listform_refresh_liststats(listID, ignoreCache) {
	var doCache = ignoreCache ? false : true;
	cachedAjaxGet('ajax.php?type=liststats&listids='+[listID].toJSON(),
		function(transport, listID) {
			var stats = transport.responseJSON;
			if (!stats) {
				alert('<?=addslashes(_L('No data available for this list, please check your internet connection and try again'))?>');
				return;
			}

			var data = stats[listID];
			var nameTD = $('listsTableBody').down('input[value='+listID+']').up('tr').down('td');
			var statisticsTD = nameTD.next('td',0);
			nameTD.update(data.name.escapeHTML());
			statisticsTD.update('<b>' + format_thousands_separator(data.total) + '</b>');

			listformVars.totals[listID] = data.total;
			listform_update_grand_total();
		}.bindAsEventListener(this, listID),
		null,
		doCache
	);
}

function listform_update_grand_total() {
	var sum = $('listChoose_addme') ? ($('listChoose_addme').checked ? 1 : 0) : 0;
	for (var id in listformVars.totals) {
		sum += listformVars.totals[id];
	}
	$('listGrandTotal').update(format_thousands_separator(sum));

	var rows = $('listsTableBody').rows;
	var rowCount = rows.length;
	var i = 0;
	for (; i < rowCount; i++) {
		var tr = $(rows[i]);
		tr.removeClassName('listAlt');
		if (i % 2 != 0)
			tr.addClassName('listAlt');
	}
	$('listsTableMyself').removeClassName('listAlt');
	if (i % 2 != 0)
		$('listsTableMyself').addClassName('listAlt');
}

// Inserts specified lists into the Lists Table
// @param listidsJSON, json-encoded array of listids
function listform_load_lists(listidsJSON, useCache) {
	var listids = listidsJSON.evalJSON();
	listids = listids.without('addme');
	if (!listids.join || listids.length < 1)
		return;
	$('listsTableStatus').update('<img src="img/ajax-loader.gif"/>');
	cachedAjaxGet('ajax.php?type=liststats&listids='+listids.toJSON(),
		function(transport, resetExistingLists) {
			$('listsTableStatus').update();
			var stats = transport.responseJSON;
			if (!stats) {
				alert('<?=addslashes(_L('No data available for this list, please check your internet connection and try again'))?>');
				return;
			}

			for (var listid in stats) {
				var data = stats[listid];
				listformVars.totals[listid] = data.total;
				
				var commonStyle = 'padding: 3px; overflow: hidden; ';
				var nameTD = new Element('td', {'class':'NameTD', 'style':'overflow: hidden; white-space: nowrap;' + commonStyle});
				nameTD.insert(data.name.escapeHTML());
				var actionTD = new Element('td', {'class':'ActionTD', 'style':commonStyle + '; text-align:center'});
				actionTD.insert('<img src="img/icons/diagona/10/101.gif" title="<?=addslashes(_L('Click to remove this list'))?>" />');
				actionTD.insert(new Element('input',{'type':'hidden','value':listid}));
				var statisticsTD = new Element('td', {'style':commonStyle}).update('<b>' + format_thousands_separator(data.total) + '</b>');

				var tbody = $('listsTableBody');
				tbody.insert(new Element('tr').insert(nameTD).insert(statisticsTD).insert(actionTD));

				if (!data.advancedlist) {
					listform_refresh_preview(listid);
					nameTD.observe('mouseover', function (event, listid) {
						if (!listformPreviewCache[listid])
							return;

						var tr = this.up('tr');
						new Tip (tr, listformPreviewCache[listid], {
				        	style: "protogrey",
				        	hideOthers:true,
				        	hook:{target:"leftMiddle",tip:"rightMiddle"},
				        	offset:{x:0,y:0},
							delay: 0.4,
				        	stem:"rightMiddle",
				        	title: '<?=_L("List Name:")?> ' + this.innerHTML
			          	});
					tr.prototip.show();
					}.bindAsEventListener(nameTD,listid));
				} else {
					listform_delay_hover(nameTD, listid, nameTD.up('tr'));
				}

				var removeButton = actionTD.down('img');
				removeButton.observe('click', listform_remove_list.bindAsEventListener(actionTD,listid,true));

				// Mark this list as 'added' so that the list selectbox no longer shows this list as an option.
				if (listformVars.existingLists && listformVars.existingLists[listid]) {
					listformVars.existingLists[listid].added = true;
				}
			}

			if (resetExistingLists)
				listform_reset_list_selectbox();
			listform_update_grand_total();
			ruleWidget.refresh_guide(true);
		},
		useCache ? true : false,
		useCache ? true : false
	);
}

var listformPreviewCache = {};
function listform_refresh_preview (listid) {
	cachedAjaxGet('ajax.php?type=listrules&listids='+[listid].toJSON(),
		function (transport, listid) {
			var listRules = transport.responseJSON;
			if (!listRules) {
				alert('<?=addslashes(_L('Sorry cannot get list rules'))?>');
				Tips.hideAll();
				return;
			}

			var previewBox = new Element('div');
			var tbody = new Element('tbody');
			for (var i in listRules[listid]) {
				var rule = listRules[listid][i];
				if (!rule.fieldnum)
					break;
				var tr = new Element('tr');
				ruleWidget.format_readable_rule(rule, tr);
				tbody.insert(tr);

				if (listformVars.pendingList == listid) {
					ruleWidget.refresh_rules_table(rule);
				}
			}
			if (!tbody.down('td')) {
				previewBox.update('<?=addslashes(_L('No Rules Found for This List'))?>');
			}
			else {
				previewBox.update(new Element('table').insert(tbody));
			}
			listformPreviewCache[listid] = previewBox.innerHTML;

		},
	listid, false);
}

function listform_hover_existing_list(label, listid, tr) {
	$('listchooseTotal').update();
	$('listchooseTotalAdded').update();
	$('listchooseTotalRemoved').update();
	$('listchooseTotalRule').update();

	Tips.hideAll();

	if (hoverTimer === null)
		return;

	var targetElement = (!tr) ? label.up('li') : tr;
	var hookPreference = (!tr) ? {target:"topLeft",tip:"bottomLeft"} : {target:"leftMiddle",tip:"rightMiddle"};
	var stemPreference = (!tr) ? "bottomLeft" : "rightMiddle";

	new Tip (targetElement, '<img src="img/ajax-loader.gif"/>', {
		style: "protogrey",
		hideOthers: true,
		hook: hookPreference,
		offset:{x:0,y:0},
		delay: 0.4,
		stem: stemPreference,
		hideOn: {element: 'target', event: 'mouseout'}
	});
	targetElement.prototip.show();

	var listuri = chosenLists.indexOf(listid.toString()) >= 0 ? chosenLists.toJSON() : [listid].toJSON();
	cachedAjaxGet('ajax.php?type=liststats&listids='+ listuri, function(transport, listid, targetElement, hookPreference, stemPreference) {
		Tips.hideAll();
		if (hoverTimer === null)
			return;

		var stats = transport.responseJSON;
		if (!stats) {
			alert('<?=addslashes(_L('No data available for this list, please check your internet connection and try again'))?>');
			return;
		}
		var data = stats[listid];
		$('listchooseTotal').update(format_thousands_separator(data.total));
		$('listchooseTotalAdded').update(format_thousands_separator(data.totaladded));
		$('listchooseTotalRemoved').update(format_thousands_separator(data.totalremoved));
		$('listchooseTotalRule').update(format_thousands_separator(data.totalrule));

		new Tip (targetElement, $('listchooseTotalsContainer').innerHTML, {
			style: "protogrey",
			hideOthers: true,
			hook: hookPreference,
			offset:{x:0,y:0},
			delay: 0.8,
			stem: stemPreference,
			hideOn: {element: 'target', event: 'mouseout'},
			title: '<?=_L("List Name:")?> ' + data.name.escapeHTML()
		});
		targetElement.prototip.show();
	}.bindAsEventListener(label, listid, targetElement, hookPreference, stemPreference));
}

function listform_onclick_existing_list(event, listid) {
	Tips.hideAll();

	if (this.checked) {
		if (!listformVars.existingLists[listid].added && listform_add_list(listid) && ruleWidget)
			ruleWidget.refresh_guide(true);
	} else {
		listform_remove_list(event, listid);
	}
	if(forcevalidator) {
		forcevalidator();
	}
}

function listform_remove_list(event, listid, doconfirm) {
	Tips.hideAll();

	var listaddme = listid.indexOf('addme') >= 0;

	if (!listaddme) {
		if (doconfirm) {
			event.stop();
			if (!confirm('<?=addslashes(_L("Are you sure you want to remove this list?"))?>'))
				return;
		}

		var hiddenInput = $('listsTableBody').down('input[value=' + listid + ']');
		if (hiddenInput) {
			var tr = hiddenInput.up('tr');
			if (tr.prototip)
				tr.prototip.remove();
			tr.remove();
		}
		var checkbox = $('listSelectboxContainer').down('input[value=' + listid + ']');
		if (checkbox) {
			checkbox.checked = false;
		}

		if (listformVars.existingLists && listformVars.existingLists[listid]) {
			listformVars.existingLists[listid].added = false;
		}
		if (event && listformVars.pendingList == listid) {
			accordion.collapse_all();
			listformVars.pendingList = null;
		}
	}

	var listids = listformVars.listidsElement.getValue() ? listformVars.listidsElement.getValue().evalJSON() : [];
	if (listids.join) {
		listids = listids.without(listid);
		listformVars.listidsElement.value = listids.toJSON();
	} else {
		// Somehow listids is not an array, which should never happen.
		listformVars.listidsElement.value = [].toJSON();
	}

	if (!listaddme) {
		listformVars.totals[listid] = 0;
		listform_update_grand_total();
	}

	if(forcevalidator) {
		forcevalidator();
	}
}

function listform_reset_list_selectbox() {
	var datas = [];
	for (var listid in listformVars.existingLists) {
		var data = {text:listformVars.existingLists[listid].name, value:listid, checked: false};
		if (listformVars.existingLists[listid].added)
			data.checked = true;
		datas.push(data);
	}

	if (datas.length > 0) {
		multicheckbox = new Element('div', {'style': 'border: solid 1px gray; background: white; overflow:hidden;'});
		var heightCSS = (datas.length > 8) ? 'height:200px;' : '';
		var ul = new Element('ul', {'style': 'clear:both; margin:0; padding:0; list-style:none; overflow:auto; '+heightCSS+'; padding-right:4px'});
		var max = datas.length;
		for (var i = 0; i < max; ++i) {
			var data = datas[i];
			var text = data.text;
			var value = data.value;
			var checkbox = new Element('input', {'type':'checkbox', 'value':value, 'style':'font-size:90%'});
			var label = new Element('label', {'style':'margin:0;padding:1px; font-size:90%;', 'for':checkbox.identify()}).update(text.escapeHTML());
			var li = new Element('li', {'style':'white-space:nowrap; font-size:90%; margin:0;margin:1px;overflow: hidden; vertical-align:middle'}).insert(checkbox).insert(label);
			if (data.checked) {
				checkbox.checked = true;
				checkbox.setAttribute('defaultChecked', true); // Workaround for Internet Explorer.
			}
			checkbox.observe('click', listform_onclick_existing_list.bindAsEventListener(checkbox, value));
			label.identify();
			listform_delay_hover(label, value);
			ul.insert(li);
		}
		multicheckbox.insert(ul);

		$('listSelectboxContainer').update(multicheckbox);
	} else {
		$('listSelectboxContainer').update('<?=addslashes(_L("There are no existing lists, you will need to build one."))?>');
	}
}

function listform_delay_hover(element, listid, tr) {
	element.observe('mouseover', function (event, listid, tr) {
		if (hoverTimer)
			clearTimeout(hoverTimer);
		Tips.hideAll();
		var params = '$("'+element.identify()+'"),'+listid;
		if (tr)
			params += ',$("'+tr.identify()+'")';
		hoverTimer = setTimeout('listform_hover_existing_list(' + params + ');', 200);
	}.bindAsEventListener(element, listid, tr));
	element.observe('mouseout', function () {
		if (hoverTimer)
			clearTimeout(hoverTimer);
		hoverTimer = null;
		Tips.hideAll();
	});
}

function listform_set_rule_editor_status(addingRule) {
	var listids = listformVars.listidsElement.getValue() ? listformVars.listidsElement.getValue().evalJSON() : [];
	if (!listids.join)
		listids = [];
	listids = listids.without('pending');
	if (addingRule) {
		listids.push('pending');
	} else {
		listform_clear_validation_error();
	}
	listformVars.listidsElement.setValue(listids.toJSON());
}

function listform_clear_validation_error() {
	$('listChoose_listids_icon').src = 'img/pixel.gif';
	$('listChoose_listids_msg').update();
	$('listChoose_listids_fieldarea').style.background = "rgb(255,255,255)";
}

function listform_refresh_addme() {
	var addme = $('listChoose_addme');
	var addmePhone = $('listChoose_addmePhone');
	var addmeEmail = $('listChoose_addmeEmail');
	var addmeSms = $('listChoose_addmeSms');

	if (addme.checked) {
		if (addmePhone) {
			if (addmeOriginalValues.phone === false)
				addmeOriginalValues.phone = addmePhone.value;
			addmePhone.up('tr').show();
		}
		if (addmeEmail) {
			if (addmeOriginalValues.email === false)
				addmeOriginalValues.email = addmeEmail.value;
			addmeEmail.up('tr').show();
		}
		if (addmeSms) {
			if (addmeOriginalValues.sms === false)
				addmeOriginalValues.sms = addmeSms.value;
			addmeSms.up('tr').show();
		}
		$('listsTableMyself').show();
		listform_add_list('addme');
		listform_clear_validation_error();
	} else {
		if (addmePhone) {
			if (addmeOriginalValues.phone !== false)
				addmePhone.value = addmeOriginalValues.phone;
			addmePhone.up('tr').hide();
		}
		if (addmeEmail) {
			if (addmeOriginalValues.email !== false)
				addmeEmail.value = addmeOriginalValues.email;
			addmeEmail.up('tr').hide();
		}
		if (addmeSms) {
			if (addmeOriginalValues.sms !== false)
				addmeSms.value = addmeOriginalValues.sms;
			addmeSms.up('tr').hide();
		}
		$('listsTableMyself').hide();
		listform_remove_list(null, 'addme');
	}
	listform_update_grand_total();
	validate_addme_fields();
}

function validate_addme_fields() {
	// Force validator to check for errors.
	if ($('listChoose_addmePhone'))
		form_do_validation($(listformVars.id), $('listChoose_addmePhone'));
	if ($('listChoose_addmeEmail'))
		form_do_validation($(listformVars.id), $('listChoose_addmeEmail'));
	if ($('listChoose_addmeSms'))
		form_do_validation($(listformVars.id), $('listChoose_addmeSms'));
}
