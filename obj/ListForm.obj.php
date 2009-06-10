<?php
/****** TODO *********************************************************************************************
+ use new cached ajax function
+ appropriate icons, use action_link() instead of icon_button() for Actions columns
+ Benchmark DOM manipulation speed/memory usage in various browsers, particularly for Multisearch persondatavalues.
+ QUESTION: preliminary 'click here to begin' step necessary? currently not available.
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
		$posturl = $_SERVER['REQUEST_URI'];
		$posturl .= mb_strpos($posturl,"?") !== false ? "&" : "?";
		$posturl .= "form=". $this->name;
		$listidsName = $this->name . '_listids';
		
		// HTML
		$str = "
			".icon_button(_L('Guide'),'information', null, null, ' id="startGuideButton" style="float:right"')."
			
			<table id='listFormWorkspace' width='100%'>
				<tr>
					<!-- MAIN CONTENT AREA -->
					<td valign=top>
						<div id='pageLoadingWindow'>"._L('Please wait while the page is loaded')."</div>
						<div id='allListsWindow'>
							<table width='100%' class='border'>
								<thead>
									<tr>
										<th class='windowRowHeader' valign=top>"._L('List')."</th>
										<th class='windowRowHeader' valign=top>"._L('Statistics')."</th>
										<th class='windowRowHeader' valign=top>"._L('Actions')."</th>
									</tr>
								</thead>
								<tbody id='listsTableBody'></tbody>
								<tfoot>
									<!-- Validation Messages -->
									<tr id='listsTableFootTR'></tr>
								</tfoot>
							</table>
							
							<fieldset id='AllLists'>
								".icon_button(_L('Build List Using Rules'),'information', null, null, ' id="buildListButton" ')."
								<div id='listSelectboxContainer' style='float:left'></div>
								".icon_button(_L('Choose List'),'information', null, null, ' id="chooseListButton" ')."
							</fieldset>
						</div>
						
						<div id='buildListWindow'>
							<div id='ruleWidgetContainer'></div>
						</div>
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
				</tr>
			</table>
			
			<!-- FORM -->
			<div class='newform_container'>
				<form class='newform' id='{$this->name}' name='{$this->name}' method='POST' action='{$posturl}'>
					".implode('', $this->buttons)."
					<input name='formsnum_{$this->name}' type='hidden' value='{$this->serialnum}'/>
					<input id='{$listidsName}' name='{$listidsName}' type='hidden' value='{$this->formdata['listids']['value']}'/>
				</form>
			</div>
			<div style='clear: both;'></div>
		";
		
		// JAVASCRIPT
		$str .= "
			<script type='text/javascript' src='script/calendar.js'></script>
			<script type='text/javascript' src='script/rulewidget.js.php'></script>
			<script type='text/javascript'>
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
					if (sectionFieldsets.length < 1)
						return;
					if (document.formvars['{$this->name}'].guideDisabled)
						return;
					document.formvars['{$this->name}'].guideStepIndex = (reset) ? 0 : Math.min(sectionFieldsets.length-1, Math.max(0, document.formvars['{$this->name}'].guideStepIndex));
					var currentFieldset = sectionFieldsets[document.formvars['{$this->name}'].guideStepIndex];
					// Visual effect.
					currentFieldset.style.borderColor = 'rgb(0,0,255)';
					document.formvars['{$this->name}'].guideMorphEffect = new Effect.Morph(currentFieldset, {style: 'border-color: rgb(150,150,255)', duration: 1.2, transition: Effect.Transitions.spring, afterFinish: function() {
						if (document.formvars['{$this->name}'].guideFieldset != this)
							this.style.borderColor = 'rgb(255,255,255)';
					}.bind(currentFieldset)});
					document.formvars['{$this->name}'].guideFieldset = currentFieldset;
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
					$('guideNavigation').insert(new Element('img', {'src':imagePreviousStep, 'width':16, 'alt':'".addslashes(_L('Previous Step'))."'}).observe('click', function() {
						document.formvars['{$this->name}'].guideStepIndex--;
						listform_refresh_guide();
					}));
					var imageNextStep = 'img/pixel.gif';
					if (document.formvars['{$this->name}'].guideStepIndex < sectionFieldsets.length - 1)
						imageNextStep = 'img/icons/fugue/arrow_270.gif';
					$('guideNavigation').insert(new Element('img', {'src':imageNextStep, 'width':16, 'alt':'".addslashes(_L('Next Step'))."'}).observe('click', function() {
						document.formvars['{$this->name}'].guideStepIndex++;
						listform_refresh_guide();
					}));
				}
				
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
					$('buildListWindow').hide();
					return true;
				}
				
				// @param listidsJSON, json-encoded array of listids
				function listform_load_lists(listidsJSON) {
					var listids = listidsJSON.evalJSON();
					if (!listids.join)
						return;
					
					// Mark any existing lists that were inserted.
					if (document.formvars['{$this->name}'].existingLists) {
						listids.each(function(id) {
							if (document.formvars['{$this->name}'].existingLists[id])
								document.formvars['{$this->name}'].existingLists[id].added = true;
						});
					}
					
					listform_reset_list_selectbox();
					
					new Ajax.Request('ajax.php?type=liststats&listids='+listidsJSON, {
						// Adds a row for this list to the Lists Table
						onSuccess: function(transport) {
							var stats = transport.responseJSON;
							if (!stats) {
								alert('".addslashes(_L('No data available for this list'))."');
								return;
							}
							
							for (var i = 0; i < stats.length; i++) {
								var data = stats[i];
								
								// Keep a hidden input field to keep track of id for this table row.
								var nameTD = new Element('td', {'class':'List NameTD'}).update(new Element('input',{'type':'hidden','value':data['id']})).insert(data['name']);
								var statisticsTD = new Element('td', {'class':'List'}).update(data.total + ' ".addslashes(_L('Total'))."');
								if (data.added > 0)
									statisticsTD.insert(', ' + data.added + ' ".addslashes(_L('Added'))."');
								if (data.removed > 0)
									statisticsTD.insert(', ' + data.removed + ' ".addslashes(_L('Removed'))."');
								var actionsTD = new Element('td', {'class':'List'});
								actionsTD.insert('".addslashes(icon_button(_L('Preview'),'information'))."');
								actionsTD.insert('".addslashes(icon_button(_L('Show/Hide Rules'),'information'))."');
								actionsTD.insert('".addslashes(icon_button(_L('Remove'),'information'))."');
								$('listsTableBody').insert(new Element('tr').insert(nameTD).insert(statisticsTD).insert(actionsTD));
								// Add an extra row for viewing rules.
								var rulesTR = new Element('tr', {'class':'ListRules'}).insert(new Element('td', {'class':'viewRulesTD',colspan:100}));
								rulesTR.hide();
								$('listsTableBody').insert(rulesTR);
								
								var previewButton = actionsTD.down('button', 0);
								previewButton.observe('click', function(event, listid) {
									// Use Math.random() for generating a unique window name, so that each time you click Preview a new window will popup, instead of reusing the same window.
									window.open('showlist.php?id='+listid, 'ListFormPreviewList'+Math.random());
								}.bindAsEventListener(actionsTD,data.id));
								var rulesButton = actionsTD.down('button', 1);
								rulesButton.observe('click', function (event, listid) {
									var rulesTR = this.up('tr').next('tr');
									rulesTR.toggle();
									if (!rulesTR.visible())
										return;
									rulesTR.down('td').update('Loading..');
									new Ajax.Request('ajax.php?type=listrules&listids='+[listid].toJSON(), {
										onSuccess: function (transport) {
											var listRules = transport.responseJSON;
											if (!listRules) {
												alert('".addslashes(_L('Sorry cannot get list rules'))."');
												return;
											}
											// Expects a single listid; loop finished in one iteration.
											for (var listid in listRules) {
												var viewRulesTD = $('listsTableBody').down('input[value=\"'+listid+'\"]').up('tr').next('tr').down('td');
												if (!viewRulesTD) {
													alert('".addslashes(_L('No td found'))."');
													break;
												}
												var thead = new Element('thead');
												thead.insert('<tr><th class=\'windowRowHeader\'>".addslashes(_L('Field'))."</th><th class=\'windowRowHeader\'>".addslashes(_L('Criteria'))."</th><th class=\'windowRowHeader\'>".addslashes(_L('Value'))."</th></tr>');
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
													viewRulesTD.update(new Element('table').insert(thead).insert(tbody));
											}
										}
									});
								}.bindAsEventListener(actionsTD,data.id));
								var removeButton = actionsTD.down('button', 2);
								removeButton.observe('click', function(event, listid) {
									var tr = this.up('tr');
									tr.next('tr').remove(); // RulesTR
									tr.remove();
									if (document.formvars['{$this->name}'].existingLists && document.formvars['{$this->name}'].existingLists[listid]) {
										document.formvars['{$this->name}'].existingLists[listid]['added'] = false;
										listform_reset_list_selectbox();
									}
									
									var listids = $('{$listidsName}').getValue() ? $('{$listidsName}').getValue().evalJSON() : [];
									if (listids.join) {
										listids = listids.without(listid);
										$('{$listidsName}').value = listids.toJSON();
										listform_show_validation_message();
									} else {
										alert('".addslashes(_L('Fatal ERROR??'))."');
									}
								}.bindAsEventListener(actionsTD,data.id));
							}
							$('pageLoadingWindow').hide();
							$('allListsWindow').show();
							listform_refresh_guide(true);
							listform_show_validation_message();
						}
					});
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
						if (this.getValue())
							listform_set_mode_status('choosingList', true);
						else
							listform_set_mode_status('choosingList', false);
					}.bindAsEventListener(selectbox));
					$('listSelectboxContainer').update(selectbox);
				}
				
				//@param mode, enum {'choosingList', 'buildingList', etc..}
				//@param enabled, boolean true to enable, false to disable
				function listform_set_mode_status(mode, enabled) {
					var listids = $('{$listidsName}').getValue() ? $('{$listidsName}').getValue().evalJSON() : [];
					if (!listids.join)
						listids = [];
					listids = listids.without(mode);
					if (enabled)
						listids.push(mode);
					if (!enabled)
						delete listids[mode];
					$('{$listidsName}').setValue(listids.toJSON());
				}
				
				function listform_show_validation_message() {
					var td = '<td id=\"listChoose_listids_fieldarea\" colspan=100><img id=\"listChoose_listids_icon\"/><span id=\"listChoose_listids_msg\"></span></td>';
					
					$('listsTableFootTR').update();
					document.formvars['{$this->name}'].ruleWidget.rulesTableLastTR.update();
					if ($('allListsWindow').visible()) {
						$('listsTableFootTR').update(td);
						status = $('listsTableBody').down('tr') ? 'success' : 'warn';
					} else if ($('buildListWindow').visible()) {
						document.formvars['{$this->name}'].ruleWidget.rulesTableLastTR.update(td);
						if (document.formvars['{$this->name}'].ruleWidget.rulesTableBody.select('tr').length > 1)
							status = 'success';
						else
							status = 'warn';
					} else
						return;

					switch (status) {
						case 'success':
							// Validation effects.
							$('listChoose_listids_fieldarea').morph('background:rgb(200,255,200)', {duration:0.4});
							$('listChoose_listids_icon').src='img/icons/accept.gif';
							if ($('allListsWindow').visible())
								$('listChoose_listids_msg').update('".addslashes(_L('You may still add additional lists, when you are done click <b>Next</b>'))."');
							else if ($('buildListWindow').visible())
								$('listChoose_listids_msg').update('".addslashes(_L('Your rule has been applied, you may add more, but don\'t forget to <b>save these rules as a list!</b>'))."');
							break;
							
						default: // warning
							$('listChoose_listids_fieldarea').morph('background: rgb(255,255,200)', {duration:0.4});
							$('listChoose_listids_icon').src = 'img/icons/error.gif';
							if ($('allListsWindow').visible())
								$('listChoose_listids_msg').update('".addslashes(_L('The lists you add will appear in this table'))."');
							else if ($('buildListWindow').visible())
								$('listChoose_listids_msg').update('".addslashes(_L('Rules will appear in this table'))."');
					}
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
					document.formvars['{$this->name}'].ruleWidget.ruleEditor.fieldTD.down('fieldset').id = 'BuildListAddRuleChooseField';
					document.formvars['{$this->name}'].ruleWidget.ruleEditor.criteriaTD.down('fieldset').id = 'AddRuleCriteria';
					document.formvars['{$this->name}'].ruleWidget.ruleEditor.valueTD.down('fieldset').id = 'AddRuleValue';
					document.formvars['{$this->name}'].ruleWidget.ruleEditor.actionTD.down('fieldset').id = 'AddRuleAction';
					document.formvars['{$this->name}'].ruleWidget.container.insert(new Element('fieldset',{id:'BuildListSaveRules'}).update(
						'".addslashes(icon_button(_L('Save Rules as a List'),'information',null,null, ' id="saveRulesButton" '))."'
					).insert('".addslashes(icon_button(_L('Cancel List'),'information',null,null, ' id="cancelBuildListButton" '))."'));
					
					// Guide/Focus
					document.formvars['{$this->name}'].guideDisabled = true;
					document.formvars['{$this->name}'].generalGuideContents = ".json_encode($this->generalGuideContents).";
					document.formvars['{$this->name}'].ruleEditorGuideContents = ".json_encode($this->ruleEditorGuideContents).";
					document.formvars['{$this->name}'].guideSection = 'AllLists';
					document.formvars['{$this->name}'].guideStepIndex = 0;
					document.formvars['{$this->name}'].guideFieldset = null;
					document.formvars['{$this->name}'].guideMorphEffect = null;
					$('startGuideButton').observe('click', function(event) {
						event.stop();
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
						event.stop();
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
					$('BuildListSaveRules').observe('click', function() {
						listform_refresh_guide(false, $('BuildListSaveRules'));
					});
					$('saveRulesButton').observe('focus', function() {
						listform_refresh_guide(false, $('BuildListSaveRules'));
						$('saveRulesButton').focus();
					});
					
					// Tips
					document.formvars['{$this->name}'].ruleEditorTips = ".json_encode($this->ruleEditorTips).";
					document.formvars['{$this->name}'].ruleWidget.container.observe('RuleWidget:InColumn', function(event) {
						listform_refresh_guide(false, event.memo.td.down('fieldset'));
						if ($('ruleEditorTip'))
							$('ruleEditorTip').remove();
						if (event.memo.column == 'field' || document.formvars['{$this->name}'].guideSection == 'AddRule')
							event.memo.td.insert(new Element('div', {id:'ruleEditorTip', style:'clear:both'}).update(document.formvars['{$this->name}'].ruleEditorTips[event.memo.column]));
					});
					document.formvars['{$this->name}'].ruleWidget.container.observe('RuleWidget:AddRule', function() {
						document.formvars['{$this->name}'].guideSection = 'BuildList';
						listform_refresh_guide(false, $('BuildListSaveRules'));
						$('saveRulesButton').focus();
						listform_show_validation_message();
						if ($('ruleEditorTip'))
							$('ruleEditorTip').remove();
					});
					document.formvars['{$this->name}'].ruleWidget.container.observe('RuleWidget:DeleteRule', function() {
						document.formvars['{$this->name}'].guideSection = 'BuildList';
						listform_refresh_guide(true);
						listform_show_validation_message();
					});
					
					// allListsWindow: Choose List Selectbox and Button
					$('chooseListButton').observe('click', function() {
						var selectbox = $('listSelectboxContainer').down('select');
						if (listform_add_list(selectbox.getValue()))
							listform_refresh_guide(true);
					});
					// allListsWindow: Build a List Using Rules Buttons
					$('buildListButton').observe('click', function() {
						document.formvars['{$this->name}'].guideSection = 'BuildList';
						document.formvars['{$this->name}'].ruleWidget.clear_rules();
						$('buildListWindow').show().style.width = '50%';
						$('buildListWindow').morph('width:100%', {duration:0.6});
						$('allListsWindow').hide();
						var listSelectbox = $('listSelectboxContainer').down('select');
						if (listSelectbox)
							listSelectbox.selectedIndex = 0;
						listform_set_mode_status('choosingList', false);
						listform_set_mode_status('buildingList', true);
						listform_refresh_guide(true);
						listform_show_validation_message();
					});
					// buildListWindow: Save Rules Button
					$('saveRulesButton').observe('click', function() {
						var data = document.formvars['{$this->name}'].ruleWidget.toJSON();
						if (data == '{}') {
							alert('".addslashes(_L('Please add a rule'))."');
							return;
						}
						listform_set_mode_status('buildingList', false);
						new Ajax.Request('ajaxlistform.php?type=saverules', { 'method':'post',
							'postBody': 'ruledata='+data,
							onSuccess: function(transport) {
								var id = transport.responseJSON;
								if (!id) {
									alert('".addslashes(_L('sorry could not save these rules!'))."');
									return;
								}
								if (listform_add_list(id)) {
									document.formvars['{$this->name}'].ruleWidget.clear_rules();
									document.formvars['{$this->name}'].guideSection = 'AllLists';
									$('buildListWindow').hide();
									$('allListsWindow').show();
									listform_refresh_guide(true);
									listform_set_mode_status('buildingList', false);
									listform_show_validation_message();
								}
							}
						});
					});
					// buildListWindow: Cancel Build List Button
					$('cancelBuildListButton').observe('click', function() {
						document.formvars['{$this->name}'].ruleWidget.clear_rules();
						document.formvars['{$this->name}'].guideSection = 'AllLists';
						$('buildListWindow').hide();
						$('allListsWindow').show();
						listform_refresh_guide(true);
						listform_set_mode_status('buildingList', false);
						listform_show_validation_message();
					});
					
					// Load Existing Lists
					new Ajax.Request('ajax.php?type=lists', {
						onSuccess: function(transport) {
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
								$('allListsWindow').show();
								listform_refresh_guide(true);
								listform_show_validation_message();
							}
						}
					});
				}
				
				// Initiatiate Javascript.
				listform_load();
				$('pageLoadingWindow').show();
				$('allListsWindow').hide();
				$('buildListWindow').hide();
				$('guide').hide();
				listform_refresh_guide(true);
			</script>";
		return $str;
	}
}
?>
