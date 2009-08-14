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
	for (fieldname in listformVars.formdata) {
		var id = form.id + '_'+fieldname;
		listformVars.validators[id] = 'ajax';
		listformVars.jsgetvalue[id] = form_default_get_value;
	}
	//submit handler
	form.observe('submit',form_handle_submit.curry(listformVars.id));
	
	// RuleWidget
	ruleWidget = new RuleWidget($('ruleWidgetContainer'));
	ruleEditor = ruleWidget.ruleEditor;
	
	var buildListFieldset = new Element('fieldset',{'style':'border: 0; margin:0px; margin-top:5px; padding:0px;'});
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
			postBody: 'ruledata='+event.memo.ruledata.toJSON(),
			onSuccess: function(transport) {
				$('listsTableStatus').update();
				var ruleid = transport.responseJSON;
				if (!ruleid) {
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
			postBody: 'fieldnum='+event.memo.fieldnum,
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
	
	// allListsWindow: Grand Total
	listformVars.totals = {};
	
	// allListsWindow: Build a List Using Rules Buttons
	$('buildListButton').hide();
	ruleWidget.container.observe('RuleWidget:Ready', function() { 
			<?
			if (!$USER->authorize('createlist')) {
		 		echo "
		 			$('chooseListChoiceButton').hide();
					$('chooseListWindow').show();
					return;
				";
			}
			?>
			
		$('chooseListChoiceButton').show();
		$('buildListButton').show();
		$('divider').show();
		
		$('buildListButton').observe('click', function(event) {
			Tips.hideAll();
			
			ruleWidget.refresh_guide(true);
			
			$('listchooseTotal').update();
			$('listchooseTotalAdded').update();
			$('listchooseTotalRemoved').update();
			$('listchooseTotalRule').update();
			
			$('buildListWindow').show();
			$('chooseListWindow').hide();
			$('buildListButton').hide();
			$('chooseListChoiceButton').show();
			
			
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
					}
				});
			}
		});
		
		// buildListWindow: Save Rules Button
		$('saveRulesButton').observe('click', function(event) {
			Tips.hideAll();
			if (ruleEditor.get_selected_fieldmap()) {
				listform_warn_add_rule();
				return;
			}
			
			listformVars.pendingList = null;
			listform_hide_build_list_window();
			ruleWidget.refresh_guide();
		});
	});
	
	$('chooseListChoiceButton').observe('click', function(event) {
		Tips.hideAll();
		if (ruleEditor.get_selected_fieldmap()) {
			listform_warn_add_rule();
			return;
		}
			
		$('chooseListChoiceButton').hide();
		$('chooseListWindow').show();
		listform_hide_build_list_window();
		listformVars.pendingList = null;
		ruleWidget.refresh_guide();
	});
	
	// Load Existing Lists
	cachedAjaxGet('ajax.php?type=lists',
		function(transport) {
			listformVars.existingLists = transport.responseJSON;
			if (!listformVars.existingLists)
				listformVars.existingLists = {};
			listform_reset_list_selectbox();
			
			// Load From Session Data.
			var listids = listformVars.listidsElement.value ? listformVars.listidsElement.value.evalJSON() : [];
			if (listids.join && listids.length > 0)
				listform_load_lists(listformVars.listidsElement.value);
			else {
				ruleWidget.refresh_guide(true);
			}
		}
	);	
}

function listform_warn_add_rule() {
	alert('<?=addslashes(_L("Please finish adding this rule, or unselect the field"))?>');
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
	listform_load_lists([listid].toJSON());
	return true;
}

function listform_refresh_liststats(listID, ignoreCache) {
	doCache = ignoreCache ? false : true;
	
	cachedAjaxGet('ajax.php?type=liststats&listids='+[listID].toJSON(),
		function(transport, listID) {
			var stats = transport.responseJSON;
			if (!stats) {
				alert('<?=addslashes(_L('No data available for this list, please check your internet connection and try again'))?>');
				return;
			}
			
			var data = stats[listID];
			var data = stats[listID];
			var nameTD = $('listsTableBody').down('input[value='+listID+']').up('td');
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
	var sum = 0;
	for (var id in listformVars.totals)
		sum += listformVars.totals[id];
	$('listGrandTotal').update(format_thousands_separator(sum));
	
	var rows = $('listsTableBody').rows;
	var rowCount = rows.length;
	for (var i = 0; i < rowCount; i++) {
		var tr = $(rows[i]);
		tr.removeClassName('listAlt');
		if (i % 2 != 0)
			tr.addClassName('listAlt');
	}
}

// Inserts specified lists into the Lists Table
// @param listidsJSON, json-encoded array of listids
function listform_load_lists(listidsJSON) {
	var listids = listidsJSON.evalJSON();
	if (!listids.join)
		return;
	$('listsTableStatus').update('<img src="img/ajax-loader.gif"/>');
	cachedAjaxGet('ajax.php?type=liststats&listids='+listidsJSON,
		function(transport) {
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
			    nameTD.insert(data.name.escapeHTML()).insert(new Element('input',{'type':'hidden','value':listid}));
			    var actionTD = new Element('td', {'class':'ActionTD', 'style':commonStyle + '; text-align:center'});
			    actionTD.insert('<img src="img/icons/diagona/10/101.gif" title="<?=addslashes(_L('Click to remove this list'))?>" />');
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
					nameTD.observe('mouseover', function (event, listid) {
						listform_hover_existing_list(event, listid, this.up('tr'));
					}.bindAsEventListener(nameTD,listid));
				}
				
				var removeButton = actionTD.down('img');
				removeButton.observe('click', listform_remove_list.bindAsEventListener(actionTD,listid,true));
				
				// Mark this list as 'added' so that the list selectbox no longer shows this list as an option.
				if (listformVars.existingLists && listformVars.existingLists[listid]) {
					listformVars.existingLists[listid].added = true;
				}
			}

			listform_update_grand_total();
			listform_reset_list_selectbox();
			ruleWidget.refresh_guide(true);
		}
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

function listform_hover_existing_list(nullableEvent, listid, tr) {
	$('listchooseTotal').update();
	$('listchooseTotalAdded').update();
	$('listchooseTotalRemoved').update();
	$('listchooseTotalRule').update();
	
	cachedAjaxGet('ajax.php?type=liststats&listids='+$H(listformVars.existingLists).keys().toJSON(), function(transport, listid, tr) {
		var stats = transport.responseJSON;
		if (!stats) {
			alert('<?=addslashes(_L('No data available for this list, please check your internet connection and try again'))?>');
			Tips.hideAll();
			return;
		}
		var data = stats[listid];
			$('listchooseTotal').update(format_thousands_separator(data.total));
			$('listchooseTotalAdded').update(format_thousands_separator(data.totaladded));
			$('listchooseTotalRemoved').update(format_thousands_separator(data.totalremoved));
			$('listchooseTotalRule').update(format_thousands_separator(data.totalrule));

		var targetElement = (!tr) ? this.up('li') : tr;
		var hookPreference = (!tr) ? {target:"topLeft",tip:"bottomLeft"} : {target:"leftMiddle",tip:"rightMiddle"};
		var stemPreference = (!tr) ? "bottomLeft" : "rightMiddle";
		
		Tips.hideAll();
		new Tip (targetElement, $('listchooseTotalsContainer').innerHTML, {
			style: "protogrey",
			hideOthers: true,
			hook: hookPreference,
			offset:{x:0,y:0},
			delay: 0.4,
			stem: stemPreference,
			title: '<?=_L("List Name:")?> ' + data.name.escapeHTML()
		});
		targetElement.prototip.show();
	}.bindAsEventListener(this, listid, tr));
}

function listform_onclick_existing_list(event, listid) {
	Tips.hideAll();
	
	if (this.checked) {
		if (!listformVars.existingLists[listid].added && listform_add_list(listid))
			ruleWidget.refresh_guide(true);
	} else {
		listform_remove_list(event, listid);
	}
}

function listform_remove_list(event, listid, doconfirm) {
	Tips.hideAll();

	if (doconfirm) {
		event.stop();
		if (!confirm('<?=addslashes(_L("Are you sure you want to remove this list?"))?>'))
			return;
	}
	
	var hiddenInput = $('listsTableBody').down('input[value='+listid+']');
	if (hiddenInput) {
		var tr = hiddenInput.up('tr');
		if (tr.prototip)
			tr.prototip.remove();
		tr.remove();
	}
	var checkbox = $('listSelectboxContainer').down('input[value='+listid+']');
	if (checkbox) {
		checkbox.checked = false;
	}

	if (listformVars.existingLists && listformVars.existingLists[listid]) {
		listformVars.existingLists[listid].added = false;
		listform_reset_list_selectbox();
	}
	if (event && listformVars.pendingList == listid) {
		listform_hide_build_list_window();
		listformVars.pendingList = null;
	}
	var listids = listformVars.listidsElement.getValue() ? listformVars.listidsElement.getValue().evalJSON() : [];
	if (listids.join) {
		listids = listids.without(listid);
		listformVars.listidsElement.value = listids.toJSON();
	} else {
		// Somehow listids is not an array, which should never happen.
		listformVars.listidsElement.value = [].toJSON();
	}
	listformVars.totals[listid] = 0;
	listform_update_grand_total();
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
			label.observe('mouseover', listform_hover_existing_list.bindAsEventListener(label, value));
			ul.insert(li);
		}
		multicheckbox.insert(ul);

		$('listSelectboxContainer').update(multicheckbox);
	} else {
		$('listSelectboxContainer').update('<?=addslashes(_L("There are no existing lists, you will need to build one."))?>');
	}
}

function listform_set_rule_editor_status(addingRule) {
	var listids = listformVars.listidsElement.getValue() ? listformVars.listidsElement.getValue().evalJSON() : [];
	if (!listids.join)
		listids = [];
	listids = listids.without('pending');
	if (addingRule)
		listids.push('pending');
	listformVars.listidsElement.setValue(listids.toJSON());
}

function listform_hide_build_list_window() {
	$('buildListWindow').hide();
	$('buildListButton').show();
}
