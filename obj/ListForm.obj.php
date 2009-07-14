<?php
/****** TODO *********************************************************************************************
+ Remove unused pendingList; 1) when clicking done 2) when removing all rules from active pending list)
+ Do not allow adding a blank rule (multisearch)
+ Refactor unnecessary functions, better named IDs
+ Benchmark DOM manipulation speed/memory usage in various browsers, particularly for Multisearch persondatavalues.
*********************************************************************************************************/
class ListForm extends Form {
	function ListForm ($name) {
		$formdata['listids'] = array(
			'label' => '',
			'value' => '',
			'validators' => array( array('ValLists') )
		);
		
		$this->generalGuideContents = array(
			'AllLists' => _L('hello'),
			'BuildListAddRuleChooseField' => _L('select a field'),
			'BuildListSaveRules' => _L('click here to save rules'),
			'AddRuleCriteria' => null,
			'AddRuleValue' => null,
			'AddRuleAction' => _L('when you\'re ready, add the rule')
		);
		$this->ruleEditorGuideContents = array(
			// Criteria
			'multisearch' => _L('Multisearch Choose a criteria for multisearch'),
			'reldate' => _L('Reldate Choose a criteria for reldate'),
			'text' => _L('Text Choose a criteria for text, but don\'t forget.'),
			'numeric' => _L('Numeric Choose a criteria for numeric'),
			// Value
			'multisearch_in' => _L('Multisearch IN'),
			'multisearch_not' => _L('Multisearch NOT'),
			'reldate_eq' => _L('Reldate EQ'),
			'reldate_reldate' => _L('Reldate RELDATE'),
			'reldate_date_range' => _L('Reldate DATE_RANGE'),
			'reldate_date_offset' => _L('Reldate DATE_OFFSET'),
			'reldate_reldate_range' => _L('Reldate RELDATE_RANGE'),
			'text_eq' => _L('Text EQ'),
			'text_ne' => _L('Text NE'),
			'text_sw' => _L('Text SW'),
			'text_ew' => _L('Text EW'),
			'text_cn' => _L('Text CN'),
			'numeric_num_eq' => _L('Numeric EQ'),
			'numeric_num_ne' => _L('Numeric NE'),
			'numeric_num_gt' => _L('Numeric GT'),
			'numeric_num_ge' => _L('Numeric GE'),
			'numeric_num_lt' => _L('Numeric LT'),
			'numeric_num_le' => _L('Numeric LE'),
			'numeric_num_range' => _L('Numeric RANGE')
		);
		
		$this->ruleEditorTips = array(
			'field' => _L('static text about F,G,C fields...'),
			'criteria' => _L('static text about criteria'),
			'value' => _L('static text about value'),
			'action' => _L('static text about action')
		);
		
		parent::Form($name, $formdata, null);
	}
	
	function render () {
		global $USER;
		$posturl = $_SERVER['REQUEST_URI'];
		$posturl .= mb_strpos($posturl,"?") !== false ? "&" : "?";
		$posturl .= "form=". $this->name;
		$listidsName = $this->name . '_listids';
		
		// HTML
		$str = "
			".icon_button(_L('Guide'),'information', null, null, ' id="startGuideButton" style="float:right"')."
			
			<table id='listFormWorkspace' width='100%' style='clear:both'>
				<tr>
					<td colspan=3>
						<h2 style=\"padding-left: 5px; background: repeat-x url('img/header_bg.gif')\">"._L('List')."</h2>
					</td>
				</tr>
				<tr>
					<!-- MAIN CONTENT AREA -->
					
					<td valign=top width='70%'>
						
						<div id='pageLoadingWindow'>"._L('Please wait while the page is loaded')."</div>
						<fieldset id='AllLists' style='margin:0; padding:0; margin-bottom:10px;'>
							<div style='border: solid 2px lightgray; padding: 5px; margin: 5px; margin-top: 0;'>
								".icon_button(_L('Build List Using Rules'),'application_form_edit', null, null, ' id="buildListButton" ')."
								<br style='clear:both'/>
								<div id='buildListWindow' style='padding:0;margin:0;display:none'>
									<div id='ruleWidgetContainer'></div>
								</div>
							</div>
							<div style='border: solid 2px lightgray; padding: 5px; margin: 5px; margin-top: 0;'>
								".icon_button(_L('Choose an Existing List'),'arrow_turn_left', null, null, ' id="chooseListChoiceButton" ')."
								<br style='clear:both'/>
								<div id='chooseListWindow' style='display:none'>
									<table><tr>
										<td valign=top>
											<div id='listSelectboxContainer'></div>
											".icon_button(_L('Choose This List'),'accept', null, null, ' id="chooseListButton" ')."
											<br style='clear:both'/>
										</td>
										<td valign=top>
											<div id='listchooseStatus'></div>
											<table>
												<tr><th valign=top style='text-align:left'>"._L('List Total')."</th><td valign=top id='listchooseTotal'></td></tr>
												<tr><td valign=top style='text-align:left'>"._L('Matched by Rules')."</td><td valign=top id='listchooseTotalRule'></td></tr>
												<tr><td valign=top style='text-align:left'>"._L('Additions')."</td><td valign=top id='listchooseTotalAdded'></td></tr>
												<tr><td valign=top style='text-align:left'>"._L('Skips')."</td><td valign=top id='listchooseTotalRemoved'></td></tr>
											</table>
										</td>
									</tr></table>
								</div>
							</div>
						</fieldset>
					</td>
					
					<!-- GUIDE -->
					<td valign=top id='guideTD'>
						<div id='guide' style='clear:both' class='helper'>
							<div id='guideTitle' class='title'>
								<a id='closeGuideLink' style='float: right;' href='#'>
									<img src='img/icons/cross.gif' alt='"._L('Close Guide')."' title='"._L('Close')."'/>
								</a>
								<img src='img/icons/information.gif' alt='' style='float: left;'/>
								"._L('Guide')."
							</div>
							<div id='guideContent' class='helpercontent' style='height:125px; overflow: auto'></div>
							<div class='toolbar'>
								<div id='guideNavigation' style='padding:0;margin:0; float:left'></div>
								<div id='guideInfo' class='info'></div>
								<br style='clear:both'/>
							</div>
						</div>
					</td>
					<td valign=top>
						<div id='allListsWindow'>
							<table width='100%' class='border' style='table-layout:fixed; border-collapse: collapse'>
								<thead>
									<tr class='listHeader'><th width='120px' style='white-space: nowrap; text-align:left'>"._L('List Name')."</th><th width='32px'></th><th colspan=100 style='overflow: hidden; width: 60px; white-space: nowrap; text-align:left'>"._L('Total')."</th></tr>
									<tr><td colspan=100 id='listsTableStatus'></td></tr>
								</thead>
								<tbody id='listsTableBody'></tbody>
								<tfoot>
									<tr><th colspan=2 style='text-align:left; white-space: nowrap; padding: 2px; padding-top:10px'>"._L('Grand Total')."</th><td id='listGrandTotal' colspan=100 style='padding: 2px; padding-top:10px'></td></tr>
								</tfoot>
							</table>
						</div>
					</td>
				</tr>
			</table>
			
			<!-- FORM -->
			<br style='clear: both'/>
			<div class='newform_container'>
				<!-- Validation Message -->
				<div id='listChoose_listids_fieldarea'>
					<img id='listChoose_listids_icon' src='img/pixel.gif'/>
					<span id='listChoose_listids_msg'></span>
				</div>
				<form class='newform' id='{$this->name}' name='{$this->name}' method='POST' action='{$posturl}'>
					".implode('', $this->buttons)."
					<input name='formsnum_{$this->name}' type='hidden' value='{$this->serialnum}'/>
					<input id='{$listidsName}' name='{$listidsName}' type='hidden' value='{$this->formdata['listids']['value']}'/>
				</form>
			</div>
			<br style='clear: both'/>
		";
		
		// JAVASCRIPT
		$str .= "
			<script type='text/javascript' src='script/datepicker.js'></script>
			<script type='text/javascript' src='script/rulewidget.js.php'></script>
			<script type='text/javascript'>
				function listform_refresh_guide_arrow() {
					var arrowImage = document.formvars['{$this->name}'].ruleWidget.rulesTableFootTR.down('img');
					var arrowDiv = arrowImage.up('div');

					if (!document.formvars['{$this->name}'].guideDisabled && document.formvars['{$this->name}'].guideFieldset && (document.formvars['{$this->name}'].guideFieldset.id == 'BuildListAddRuleChooseField' || document.formvars['{$this->name}'].guideSection == 'AddRule')) {
						arrowDiv.show();
						var firstColumnX = $('BuildListAddRuleChooseField') ? $('BuildListAddRuleChooseField').positionedOffset().left : 0;
						var columnX = document.formvars['{$this->name}'].guideFieldset.positionedOffset().left - firstColumnX + 16;
						arrowImage.src = 'img/icons/fugue/arrow_270.gif';
						arrowDiv.style.borderTop = 'dashed 2px rgb(200,200,255)';
						if (document.formvars['{$this->name}'].guideArrowMorphEffect)
							document.formvars['{$this->name}'].guideArrowMorphEffect.cancel();
						document.formvars['{$this->name}'].guideArrowMorphEffect = new Effect.Morph(arrowDiv, {'style': 'margin-left: ' + columnX + 'px', duration: 0.6});
					} else {
						arrowDiv.hide();
					}
				}

				function listform_refresh_guide(reset, specificFieldset) {
					if (document.formvars['{$this->name}'].guideMorphEffect) {
						document.formvars['{$this->name}'].guideMorphEffect.cancel();
						document.formvars['{$this->name}'].guideMorphEffect.element.style.borderColor = 'rgb(255,255,255)';
					}

					var sectionFieldsets = [];
					$$('fieldset').each(function(fieldset) {
						if (fieldset != specificFieldset)
							fieldset.style.border = 'solid 2px rgb(255,255,255)';
						if (fieldset.id.include(document.formvars['{$this->name}'].guideSection)) {
							sectionFieldsets.push(fieldset);
							if (specificFieldset == fieldset)
								document.formvars['{$this->name}'].guideStepIndex = sectionFieldsets.length - 1;
						}
					});
					if (sectionFieldsets.length < 1 || document.formvars['{$this->name}'].guideDisabled) {
						document.formvars['{$this->name}'].guideFieldset = null;
						listform_refresh_guide_arrow();
						return;
					}
					document.formvars['{$this->name}'].guideStepIndex = (reset) ? 0 : Math.min(sectionFieldsets.length-1, Math.max(0, document.formvars['{$this->name}'].guideStepIndex));
					var currentFieldset = sectionFieldsets[document.formvars['{$this->name}'].guideStepIndex];
					// Visual effect.
					currentFieldset.style.borderColor = 'rgb(0,0,255)';
					document.formvars['{$this->name}'].guideMorphEffect = new Effect.Morph(currentFieldset, {style: 'border-color: rgb(150,150,255)', duration: 1.2, transition: Effect.Transitions.spring});
					document.formvars['{$this->name}'].guideFieldset = currentFieldset;
					listform_refresh_guide_arrow();

					// Guide Content
					var helpContent = document.formvars['{$this->name}'].generalGuideContents[currentFieldset.id];
					if (document.formvars['{$this->name}'].guideSection == 'AddRule') {
						var data = document.formvars['{$this->name}'].ruleWidget.ruleEditor.get_data();
						if (currentFieldset.id == 'AddRuleCriteria')
							helpContent = document.formvars['{$this->name}'].ruleEditorGuideContents[data.type];
						else if (currentFieldset.id == 'AddRuleValue') {
							// multisearch IS NOT
							if (data.logical == 'and not')
								data.op = 'not';
							helpContent = document.formvars['{$this->name}'].ruleEditorGuideContents[data.type + '_' + data.op];
						}
					}
					if (!helpContent)
						return;
					$('guideContent').update(helpContent);

					// Guide Info
					$('guideInfo').update('Step ' + (document.formvars['{$this->name}'].guideStepIndex + 1) + ' of ' + sectionFieldsets.length);
					// Guide Navigation
					$('guideNavigation').update();
					var imagePreviousStep = 'img/pixel.gif';
					if (document.formvars['{$this->name}'].guideStepIndex > 0)
						imagePreviousStep = 'img/icons/fugue/arrow_090.gif';
					$('guideNavigation').insert(new Element('img', {'src':imagePreviousStep, 'width':16, 'alt':'".addslashes(_L('Previous Step'))."'}).observe('click', function(event) {
						document.formvars['{$this->name}'].guideStepIndex--;
						listform_refresh_guide();
					}));
					var imageNextStep = 'img/pixel.gif';
					if (document.formvars['{$this->name}'].guideStepIndex < sectionFieldsets.length - 1)
						imageNextStep = 'img/icons/fugue/arrow_270.gif';
					$('guideNavigation').insert(new Element('img', {'src':imageNextStep, 'width':16, 'alt':'".addslashes(_L('Next Step'))."'}).observe('click', function(event) {
						document.formvars['{$this->name}'].guideStepIndex++;
						listform_refresh_guide();
					}));
				}
				
				// Adds listid to $('{$listidsName}')
				function listform_add_list(listid) {
					if (!listid.strip()) {
						alert('".addslashes(_L('Please select a list'))."');
						return false;
					}
					
					var listids = $('{$listidsName}').value ? $('{$listidsName}').value.evalJSON() : [];
					if (!listids.join)
						listids = [];
						
					listids.push(listid);
					$('{$listidsName}').value = listids.toJSON();
					
					listform_load_lists([listid].toJSON());
					return true;
				}
				
				function listform_refresh_liststats(listID) {
					cachedAjaxGet('ajax.php?type=liststats&listids='+[listID].toJSON(),
						function(transport) {
							var stats = transport.responseJSON;
							if (!stats) {
								alert('".addslashes(_L('No data available for this list, please check your internet connection and try again'))."');
								return;
							}
							
							var data = stats[listID];
							
							var hiddenTD = $('listsTableBody').down('input[value=\"'+data.id+'\"]').up('td');
							var nameTD = hiddenTD.next('td');
							var statisticsTD = nameTD.next('td',2);
							nameTD.update(data.name);
							statisticsTD.update(data.total);
							
							document.formvars['{$this->name}'].totals[data.id] = data.total;
							listform_update_grand_total();
						}.bindAsEventListener(this),
						null,
						false
					);
				}
				
				function listform_update_grand_total() {
					var sum = 0;
					for (var id in document.formvars['{$this->name}'].totals)
						sum += document.formvars['{$this->name}'].totals[id];
					$('listGrandTotal').update(sum);
				}
				
				// Inserts specified lists into the Lists Table
				// @param listidsJSON, json-encoded array of listids
				function listform_load_lists(listidsJSON) {
					var listids = listidsJSON.evalJSON();
					if (!listids.join)
						return;
					$('listsTableStatus').update('".addslashes(_L('Loading..'))."');
					cachedAjaxGet('ajax.php?type=liststats&listids='+listidsJSON,
						function(transport) {
							$('listsTableStatus').update();
							var stats = transport.responseJSON;
							if (!stats) {
								alert('".addslashes(_L('No data available for this list, please check your internet connection and try again'))."');
								return;
							}
							Object.keys(stats).each(function (id) {
								var data = stats[id];
								document.formvars['{$this->name}'].totals[id] = data.total;
								listform_update_grand_total();
							
								// Keep a hidden input field to keep track of id for this table row.
								var hiddenTD = new Element('td').update(new Element('input',{'type':'hidden','value':id})).hide();
								var nameTD = new Element('td', {'class':'List NameTD', 'style':'overflow: hidden; white-space: nowrap;'});
								nameTD.insert(data.name);
								var actionTD = new Element('td', {'class':'List ActionTD'});
								actionTD.insert('".addslashes(action_link('','delete'))."');
								var statisticsTD = new Element('td', {'class':'List', 'colspan':100}).update(data.total);
								$('listsTableBody').insert(new Element('tr').insert(hiddenTD).insert(nameTD).insert(actionTD).insert(statisticsTD));
								// Add an extra row for viewing rules.
								var rulesTR = new Element('tr', {'class':'ListRules'}).insert(new Element('td', {'class':'viewRulesTD',colspan:100}));
								rulesTR.hide();
								$('listsTableBody').insert(rulesTR);
								if (!data.advancedlist) {
									nameTD.style.cursor = 'pointer';
									nameTD.observe('click', function (event, listid) {
										var rulesTR = this.up('tr').next('tr');
										rulesTR.toggle();
										if (!rulesTR.visible())
											return;
										rulesTR.down('td').update('Loading..');
										cachedAjaxGet('ajax.php?type=listrules&listids='+[listid].toJSON(),
											function (transport) {
												var listRules = transport.responseJSON;
												if (!listRules) {
													alert('".addslashes(_L('Sorry cannot get list rules'))."');
													return;
												}
												// Expects a single listid; loop finished in one iteration.
												for (var listid in listRules) {
													var viewRulesTD = this.down('td');
													if (!viewRulesTD) {
														alert('".addslashes(_L('No td found'))."');
														break;
													}
													var tbody = new Element('tbody');
													for (var i in listRules[listid]) {
														var rule = listRules[listid][i];
														if (!rule.fieldnum)
															break;
														var tr = new Element('tr');
														document.formvars['{$this->name}'].ruleWidget.format_readable_rule(rule, tr);
														tbody.insert(tr);
													}
													if (!tbody.down('td'))
														viewRulesTD.update('".addslashes(_L('No Rules Found for This List'))."');
													else
														viewRulesTD.update(new Element('table').insert(tbody));
												}
											}.bindAsEventListener(rulesTR),
											null,
											false
										);
									}.bindAsEventListener(nameTD,id));
								}
								var removeButton = actionTD.down('a');
								removeButton.observe('click', function(event, listid) {
									var tr = this.up('tr');
									tr.next('tr').remove(); // RulesTR
									tr.remove();
									if (document.formvars['{$this->name}'].existingLists && document.formvars['{$this->name}'].existingLists[listid]) {
										document.formvars['{$this->name}'].existingLists[listid].added = false;
										listform_reset_list_selectbox();
									}
									if (document.formvars['{$this->name}'].pendingList == listid) {
										listform_hide_build_list_window();
										document.formvars['{$this->name}'].pendingList = null;
									}
									var listids = $('{$listidsName}').getValue() ? $('{$listidsName}').getValue().evalJSON() : [];
									if (listids.join) {
										listids = listids.without(listid);
										$('{$listidsName}').value = listids.toJSON();
										listform_show_validation_message();
									} else {
										// Somehow listids is not an array, which should never happen.
										alert('".addslashes(_L('Fatal ERROR??'))."');
									}
									document.formvars['{$this->name}'].totals[listid] = 0;
									listform_update_grand_total();
								}.bindAsEventListener(actionTD,id));
								
								// Mark this list as 'added' so that the list selectbox no longer shows this list as an option.
								if (document.formvars['{$this->name}'].existingLists && document.formvars['{$this->name}'].existingLists[id])
									document.formvars['{$this->name}'].existingLists[id].added = true;
							}.bind(this));
							listform_reset_list_selectbox();
							$('pageLoadingWindow').hide();
							listform_refresh_guide(true);
							listform_show_validation_message();
						}
					);
				}
				
				function listform_reset_list_selectbox() {
					$('listSelectboxContainer').update();
					var selectbox = new Element('select');
					selectbox.insert(new Element('option', {'value':''}).insert('-- ".addslashes(_L('Choose a List'))." --'));
					for (var listid in document.formvars['{$this->name}'].existingLists) {
						if (!document.formvars['{$this->name}'].existingLists[listid].added)
							selectbox.insert(new Element('option', {'value':listid}).update(document.formvars['{$this->name}'].existingLists[listid].name.escapeHTML()));
					}
					listform_set_mode_status('choosingList', false);
					
					selectbox.observe('change', function() {
						$('listchooseTotal').update();
						$('listchooseTotalAdded').update();
						$('listchooseTotalRemoved').update();
						$('listchooseTotalRule').update();
							
						if (!this.getValue())
							return;
						
						$('listchooseStatus').update('".addslashes(_L('Please wait, gathering statistics..'))."');
						cachedAjaxGet('ajax.php?type=liststats&listids='+[this.getValue()].toJSON(), function(transport, id) {
							$('listchooseStatus').update();
							var stats = transport.responseJSON;
							if (!stats) {
								alert('".addslashes(_L('No data available for this list, please check your internet connection and try again'))."');
								return;
							}
							
							var data = stats[0];
							$('listchooseTotal').update(data.total);
							$('listchooseTotalAdded').update(data.totaladded);
							$('listchooseTotalRemoved').update(data.totalremoved);
							$('listchooseTotalRule').update(data.totalrule);
						}, this.getValue());
					}.bindAsEventListener(selectbox));
					$('listSelectboxContainer').update(selectbox);
				}
				
				// Updates $('{$listidsName}') according to the mode
				//@param mode, enum {'choosingList', 'buildingList', etc..}
				//@param enabled, boolean true to enable, false to disable
				function listform_set_mode_status(mode, enabled) {
					return;
					var listids = $('{$listidsName}').getValue() ? $('{$listidsName}').getValue().evalJSON() : [];
					if (!listids.join)
						listids = [];
					listids = listids.without(mode);
					if (enabled)
						listids.push(mode);
					$('{$listidsName}').setValue(listids.toJSON());
				}
				
				function listform_show_validation_message() {
				}
				
				function listform_hide_build_list_window() {
					$('buildListWindow').blindUp({duration:0.4});
					$('buildListButton').appear({duration:0.6});
				}
				
				// Modified form load.
				function listform_load() {
					var form = $('{$this->name}');
					//set up formvars to save data, avoid memleaks in IE by not attaching anything to dom elements
					if (!document.formvars)
						document.formvars = {};
					document.formvars['{$this->name}'] = {
						formdata: ".json_encode($this->formdata).",
						scriptname: '{$posturl}', //used for any ajax calls for this form
						ajaxsubmit: true,
						validators: {},
						jsgetvalue: {}
					};
					for (fieldname in document.formvars['{$this->name}'].formdata) {
						var id = form.id+'_'+fieldname;
						document.formvars['{$this->name}'].validators[id] = 'ajax';
						document.formvars['{$this->name}'].jsgetvalue[id] = form_default_get_value;
					}
					//submit handler
					form.observe('submit',form_handle_submit.curry('{$this->name}'));
					
					// RuleWidget
					document.formvars['{$this->name}'].ruleWidget = new RuleWidget($('ruleWidgetContainer'));
					document.formvars['{$this->name}'].ruleWidget.ruleEditor.fieldTD.down('fieldset').insert({top:new Element('label', {'style':'font-weight: bold; padding:2px; white-space:nowrap'})}).id = 'BuildListAddRuleChooseField';
					document.formvars['{$this->name}'].ruleWidget.ruleEditor.criteriaTD.down('fieldset').insert({top:new Element('label', {'style':'font-weight: bold; padding:2px; white-space:nowrap'})}).id = 'AddRuleCriteria';
					document.formvars['{$this->name}'].ruleWidget.ruleEditor.valueTD.down('fieldset').insert({top:new Element('label', {'style':'font-weight: bold; padding:2px; white-space:nowrap'})}).id = 'AddRuleValue';
					document.formvars['{$this->name}'].ruleWidget.ruleEditor.actionTD.down('fieldset').insert({top:new Element('label', {'style':'font-weight: bold; padding:2px; white-space:nowrap'})}).id = 'AddRuleAction';
					var buildListFieldset = new Element('fieldset',{'id':'BuildListSaveRules', 'style':'margin-top:10px;margin-bottom:10px;padding:5px'});
					buildListFieldset.insert('".addslashes(icon_button(_L('Done Adding Rules To This List'),'accept',null,null, ' id="saveRulesButton" '))."');
					document.formvars['{$this->name}'].ruleWidget.container.insert(buildListFieldset);
					document.formvars['{$this->name}'].ruleWidget.rulesTableFootTR.update(new Element('td', {'colspan':100}).update(new Element('div', {'style':'margin:0; padding:0; margin-top:8px'}).insert(new Element('img', {'src':'img/pixel.gif', 'style':'width:16px; height:16px; margin:0; padding:0; margin-left:-8px'}))));
					
					// Guide/Focus
					document.formvars['{$this->name}'].guideDisabled = true;
					document.formvars['{$this->name}'].generalGuideContents = ".json_encode($this->generalGuideContents).";
					document.formvars['{$this->name}'].ruleEditorGuideContents = ".json_encode($this->ruleEditorGuideContents).";
					document.formvars['{$this->name}'].guideSection = 'AllLists';
					document.formvars['{$this->name}'].guideStepIndex = 0;
					document.formvars['{$this->name}'].guideFieldset = null;
					document.formvars['{$this->name}'].guideMorphEffect = null;
					$('startGuideButton').observe('click', function(event) {
						if (!document.formvars['{$this->name}'].guideDisabled)
							return;
						document.formvars['{$this->name}'].guideDisabled = false;
						$('startGuideButton').fade({duration:0.5});
						$('guideTD').style.width = '0px';
						$('guideTD').morph('width:200px', {afterFinish: function() {
							$('guide').style.display = 'block';
							listform_refresh_guide(true);
						}});
					});
					// Close Guide Link
					$('closeGuideLink').observe('click', function(event) {
						document.formvars['{$this->name}'].guideDisabled = true;
						$('startGuideButton').appear({duration:0.5});
						$('guide').hide();
						$('guideTD').morph('width:0px', {afterFinish: function() {
							listform_refresh_guide(true);
						}});
					});
					document.formvars['{$this->name}'].ruleWidget.container.observe('RuleWidget:ChangeField', function(event) {
						document.formvars['{$this->name}'].guideSection = 'AddRule';
						if (!event.memo.fieldnum)
							document.formvars['{$this->name}'].guideSection = 'BuildList';
						listform_refresh_guide(true);
					});
					document.formvars['{$this->name}'].ruleWidget.container.observe('RuleWidget:ChangeCriteria', function() {
						listform_refresh_guide();
					});
					$('BuildListSaveRules').observe('click', function(event) {
						listform_refresh_guide(false, $('BuildListSaveRules'));
					});
					$('saveRulesButton').observe('focus', function() {
						listform_refresh_guide(false, $('BuildListSaveRules'));
						$('saveRulesButton').focus();
					});
					
					// Tips (and InColumn Guide)
					document.formvars['{$this->name}'].ruleEditorTips = ".json_encode($this->ruleEditorTips).";
					document.formvars['{$this->name}'].ruleWidget.container.observe('RuleWidget:InColumn', function(event) {
						if ($('ruleEditorTip'))
							$('ruleEditorTip').remove();
						if (event.memo.column == 'field' || document.formvars['{$this->name}'].guideSection == 'AddRule')
							event.memo.td.insert(new Element('div', {id:'ruleEditorTip', style:'clear:both'}).update(document.formvars['{$this->name}'].ruleEditorTips[event.memo.column]));
						
						var fieldset = event.memo.td.down('fieldset');
						listform_refresh_guide(false, fieldset);
						
						var editor = document.formvars['{$this->name}'].ruleWidget.ruleEditor;
						editor.fieldTD.down('label').update();
						editor.criteriaTD.down('label').update();
						editor.valueTD.down('label').update();
						editor.actionTD.down('label').update();
						if (editor.fieldTD.down('select').getValue()) {
							editor.fieldTD.down('label').update('".addslashes(_L('Step 1'))."');
							editor.criteriaTD.down('label').update('".addslashes(_L('Step 2'))."');
							editor.valueTD.down('label').update('".addslashes(_L('Step 3'))."');
							editor.actionTD.down('label').update('".addslashes(_L('Step 4'))."');
						}
					});
					document.formvars['{$this->name}'].ruleWidget.container.observe('RuleWidget:AddRule', function(event) {
						document.formvars['{$this->name}'].guideSection = 'BuildList';
						listform_refresh_guide(false, $('BuildListSaveRules'));
						$('saveRulesButton').focus();
						listform_show_validation_message();
						if ($('ruleEditorTip'))
							$('ruleEditorTip').remove();
						$('listsTableStatus').update('".addslashes(_L('Updating..'))."');
						
						new Ajax.Request('ajaxlistform.php?type=addrule&listid=' + document.formvars['{$this->name}'].pendingList, {
							postBody: 'ruledata='+event.memo.ruledata.toJSON(),
							onSuccess: function(transport) {
								$('listsTableStatus').update();
								var ruleid = transport.responseJSON;
								if (!ruleid) {
									alert('Sorry, cannot save this rule');
									return;
								}
								
								var listid = document.formvars['{$this->name}'].pendingList;
								if (!$('listsTableBody').down('input[value=\"'+listid+'\"]')) // Add to the lists table if the pending list hasn't already been added.
									listform_add_list(listid);
								else
									listform_refresh_liststats(listid);
							}
						});
					});
					document.formvars['{$this->name}'].ruleWidget.container.observe('RuleWidget:DeleteRule', function(event) {
						document.formvars['{$this->name}'].guideSection = 'BuildList';
						if ($('ruleEditorTip'))
							$('ruleEditorTip').remove();
						listform_refresh_guide(true);
						listform_show_validation_message();
						$('listsTableStatus').update('".addslashes(_L('Updating..'))."');
						new Ajax.Request('ajaxlistform.php?type=deleterule&listid=' + document.formvars['{$this->name}'].pendingList, {
							postBody: 'fieldnum='+event.memo.fieldnum,
							onSuccess: function(transport) {
								$('listsTableStatus').update();
								if (!transport.responseJSON) {
									alert('Sorry, there was an error when deleting this rule');
									return;
								}
								
								listform_refresh_liststats(document.formvars['{$this->name}'].pendingList);
							}
						});
					});
					
					// allListsWindow: Grand Total
					document.formvars['{$this->name}'].totals = {};
					
					// allListsWindow: Choose List Selectbox and Button
					$('chooseListButton').observe('click', function(event) {
						var selectbox = $('listSelectboxContainer').down();
						if (selectbox.options.length < 2) {
							alert('".addslashes(_L('Sorry, you do not have any existing lists.'))."');
							return;
						}
						$('listchooseTotal').update();
						$('listchooseTotalAdded').update();
						$('listchooseTotalRemoved').update();
						$('listchooseTotalRule').update();
						if (listform_add_list(selectbox.getValue()))
							listform_refresh_guide(true);
					});
					// allListsWindow: Build a List Using Rules Buttons
					$('buildListButton').hide();
					document.formvars['{$this->name}'].ruleWidget.container.observe('RuleWidget:Ready', function() {
						
						" . ($USER->authorize('createlist') ? '' : 'return;') . "
						
						$('buildListButton').show();
						
						$('buildListButton').observe('click', function(event) {
							document.formvars['{$this->name}'].guideSection = 'BuildList';
							
							var listSelectbox = $('listSelectboxContainer').down();
							if (listSelectbox)
								listSelectbox.selectedIndex = 0;
							listform_set_mode_status('choosingList', false);
							listform_set_mode_status('buildingList', true);
							listform_refresh_guide(true);
							listform_show_validation_message();
							
							$('listchooseTotal').update();
							$('listchooseTotalAdded').update();
							$('listchooseTotalRemoved').update();
							$('listchooseTotalRule').update();
							
							$('buildListWindow').blindDown({duration:0.4});
							$('chooseListWindow').blindUp({duration:0.4});
							$('buildListButton').fade({duration:0.6});
							$('chooseListChoiceButton').appear({duration:0.6});
							
							
							if (!document.formvars['{$this->name}'].pendingList) {
								
								
								document.formvars['{$this->name}'].ruleWidget.clear_rules();
								new Ajax.Request('ajaxlistform.php?type=createlist', {
									onSuccess: function(transport) {
										$('listsTableStatus').update();
										var listid = transport.responseJSON;
										if (!listid) {
											alert('".addslashes(_L('Sorry, you are not able to build lists'))."');
											return;
										}
										
										document.formvars['{$this->name}'].pendingList = listid;
									}
								});
							}
						});
						
						$('chooseListChoiceButton').observe('click', function(event) {
							
							$('chooseListChoiceButton').fade({duration:0.6});
							document.formvars['{$this->name}'].guideSection = 'ChooseList';
							//document.formvars['{$this->name}'].ruleWidget.clear_rules();
							$('chooseListWindow').blindDown({duration:0.4});
							listform_hide_build_list_window();
							var listSelectbox = $('listSelectboxContainer').down();
							if (listSelectbox)
								listSelectbox.selectedIndex = 0;
							listform_refresh_guide(true);
							document.formvars['{$this->name}'].pendingList = null;
							listform_refresh_guide();
						});
						
						// buildListWindow: Save Rules Button
						$('saveRulesButton').observe('click', function(event) {
							document.formvars['{$this->name}'].pendingList = null;
							listform_hide_build_list_window();
							listform_refresh_guide();
						});
					});
					
					// Load Existing Lists
					cachedAjaxGet('ajax.php?type=lists',
						function(transport) {
							document.formvars['{$this->name}'].existingLists = transport.responseJSON;
							if (!document.formvars['{$this->name}'].existingLists)
								document.formvars['{$this->name}'].existingLists = {};
							
							listform_reset_list_selectbox();
							
							// Load From Session Data.
							var listids = $('{$listidsName}').value ? $('{$listidsName}').value.evalJSON() : [];
							if (listids.join && listids.length > 0)
								listform_load_lists($('{$listidsName}').value);
							else {
								$('pageLoadingWindow').hide();
								
								listform_refresh_guide(true);
								listform_show_validation_message();
							}
						}
					);
				}
				
				// Initiatiate Page.
				listform_load();
				$('pageLoadingWindow').show();
				$('guide').hide();
				listform_refresh_guide(true);
				document.formvars['{$this->name}'].ruleWidget.startup();
			</script>";
		return $str;
	}
}
?>
