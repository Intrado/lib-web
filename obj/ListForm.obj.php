<?php

/* use the 3 column fieldset layout, each form item on a line by itself*/
class ListForm extends Form {
	function ListForm ($name) {
		$this->formdata['listids'] = array(
			'label' => '',
			'value' => '',
			'validators' => array(
				array('ValRequired'),
				array('ValLists')
			)
		);

		parent::Form($name, $this->formdata, null);
	}

	function render () {
		$posturl = $_SERVER['REQUEST_URI'];
		$posturl .= mb_strpos($posturl,"?") !== false ? "&" : "?";
		$posturl .= "form=". $this->name;
		
		$listidsName = $this->name . '_listids';
		// ListForm Stuff
		$str = "
			<style type='text/css'>
			td,th {
				vertical-align: top;
				text-align: left;
			}
			select {
				min-width: 70px;
			}
			#rulesDiv {
				border: solid 1px rgb(220,220,220);
				padding: 2px;
			}
			.RulesTable {
				border-collapse: collapse;
				width: 100%;
				border: solid 2px rgb(130,170,220);
				margin-top: 20px;
			}
			.RulesTable td {
				padding: 1px;
				background: rgb(210,230,255);
				border-top: solid 1px rgb(150,180,220);
			}
			.RulesTableLastTR td {
				padding: 5px;
				background: rgb(180,200,220);
			}
			.RuleEditorTable {
				border-collapse: collapse;
				width: 100%;
			}
			.RuleEditorTable td {
				border: 0;
			}
			.RuleEditorTable .SectionTD {
				font-weight: bold;
				font-size: 115%;
				text-align: right;
				padding-right: 5px;
			}
			.RuleEditorTable .HelpTD {
				width: 30%;
			}
			.RuleEditorTable .InputTD {
				width: 50%;
			}
			h3 {
				margin: 0;
				margin-top: 10px;
			}
			td.ValueTD input[type='text'] {
				width: 80px;
			}
			input.Datebox {
				border-bottom: solid 2px orange;
			}
			</style>
			<table width='100%'>
				<tr>
					<td>
						<table width='100% style='border: solid 2px rgb(200,200,200); border-collapse:collapse;>
							<tbody id='finalListsTable'>
								<tr>
									<th>List</th>
									<th>Count</th>
									<th></th>
								</tr>
							</tbody>
							<tbody>
								<!-- For Validation Message -->
								<tr>
									<td id='listChoose_listids_fieldarea' colspan=3 style='text-align:left; background: rgb(255,255,200);'>
										<img id='listChoose_listids_icon' src='img/icons/error.gif'/> <span id='listChoose_listids_msg'>The lists you choose will appear in this table</span>
									</td>
								</tr>
							</tbody>
						</table>
					</td>
					<td width=200>
						<div>
							<h3>Step 1</h3>
							There are two ways to add a list:
							<ul>
								<li>Build a List Using Rules</li>
								<li>Choose an Existing List</li>
							</ul>
						</div>
					</td>
				</tr>
			</table>
			<table style='width:700px; border-collapse:collapse;'>
							<tbody style=''>
								<tr>
									<td colspan=3 style='padding-top:10px'>
										Want to Add a List?
											
												<!--
												<select>
													<option value=''>-- Select a Method --</option>
													<option value='buildrules'>Build a List Using Rules</option>
													<option value='chooselist'>Choose an Existing List</option>
												</select>
												-->
												<button id='buildRulesButton' type='button'>Build a List Using Rules</button> or <button id='chooseListButton' type='button'>Choose an Existing List</button>
										
									</td>
								</tr>
							</tbody>
							<tbody>
								<tr>
									<td colspan=3>
										<div id='buildRulesWindow'>
											<h3>Build a List Using Rules</h3>
											<div id='rulesDiv'></div>
										</div>
										
										<div id='chooseListWindow'>
											<h3>Choose an Existing List</h3>
											<div id='listSelectDiv'></div>
											<button id='chooseListDoneButton' type='button'>Add List</button>
										</div>
									</td>
								</tr>
							</tbody>
				</table>";

		// Taken from Form.obj.php, adapted to fit needs of ListForm.
		$str .= "
			<div class='newform_container'>
				<form class='newform' id='{$this->name}' name='{$this->name}' method='POST' action='{$posturl}' style='width: 100%; /* TODO fix main css */'>";
		//submit buttons
		foreach ($this->buttons as $code) {
			$str .= $code;
		}
		$str .="
					<input name='formsnum_{$this->name}' type='hidden' value='{$this->serialnum}'/>
					<input id='$listidsName' name='$listidsName' type='hidden' value='{$this->formdata['listids']['value']}'/>
				</form>
			</div>
			<div style='clear: both;'></div>";

		// ListForm javascript
		$str .= "
			<script type='text/javascript'>
				// Modified form load.
				function listform_load(name,scriptname,formdata) {
					var form = $(name);
					//set up formvars to save data, avoid memleaks in IE by not attaching anything to dom elements
					if (!document.formvars)
						document.formvars = {};
						
					var formvars = document.formvars[name] = {
						formdata: formdata,
						scriptname: scriptname, //used for any ajax calls for this form
						ajaxsubmit: true,
						validators: {},
						jsgetvalue: {}
					};
					
					for (fieldname in formdata) {
						var id = form.id+'_'+fieldname;
						formvars.validators[id] = 'ajax';
						formvars.jsgetvalue[id] = form_default_get_value;
					}
					
					//submit handler
					form.observe('submit',form_handle_submit.curry(name));
				}
				listform_load('{$this->name}', '$posturl', " . json_encode($this->formdata) . ");
			</script>
			<script type='text/javascript' src='script/calendar.js'></script>
			<script type='text/javascript' src='script/rulewidget.js'></script>
			<script type='text/javascript'>
				reset_windows();
				var ruleWidget = new RuleWidget($('rulesDiv'));
				var listSelectbox = null;
				var premadeLists = null;
				new Ajax.Request('ajax.php?type=lists', {
					onSuccess: function(transport) {
						premadeLists = transport.responseJSON;
						if (!premadeLists) {
							alert('you are not logged in');
							return;
						}
						
						refresh_listSelectbox();
						
						// Load From Session Data.
						if($('$listidsName').value) {
							get_liststats($('$listidsName').value);
						}
					}
				});
				
				
				// Build a List Using Rules Buttons
				$('buildRulesButton').observe('click', function(event) {
					event.stop();
					ruleWidget.clear_rules();
					$('buildRulesWindow').show();
					$('chooseListWindow').hide();
				});
				var buildRulesDoneButton = new Element('button', {'type':'button'}).update('Make These Rules Into a List');
				buildRulesDoneButton.observe('click', function(event) {
					event.stop();
					var data = ruleWidget.toJSON();
					if (data == '{}') {
						alert('Please add a rule');
						return;
					}
					new Ajax.Request('ajaxlistform.php?type=saverules', { 'method':'post',
						'postBody': 'ruledata='+data,
						onSuccess: function(transport) {
							var id = transport.responseJSON;
							if (!id) {
								alert('sorry could not save these rules!');
								return;
							}
							add_list(id);
							ruleWidget.clear_rules();
						}
					});
				});
				ruleWidget.rulesTableLastTR.update(new Element('td', {'colspan':4}).update(buildRulesDoneButton));
				
				// Choose List Buttons
				$('chooseListButton').observe('click', function(event) {
					event.stop();
					$('buildRulesWindow').hide();
					$('chooseListWindow').show();
				});
				$('chooseListDoneButton').observe('click', function(event) {
					event.stop();
					var id = listSelectbox.getValue();
					add_list(id);
				});
				
				function reset_windows() {
					$('buildRulesWindow').hide();
					$('chooseListWindow').hide();
					//$('helperWindow').show();
				}
				
				function refresh_listSelectbox() {
					listSelectbox = new Element('select');
					listSelectbox.insert(new Element('option', {'value':''}).insert('-- Select a List --'));
					$('listSelectDiv').update();
					if (!premadeLists)
						return;
					for (var id in premadeLists) {
						if (!premadeLists[id]['added'])
							listSelectbox.insert(new Element('option', {'value':id}).update(premadeLists[id]['name']));
					}
					$('listSelectDiv').update(listSelectbox);
				}
				
				function add_list(id) {
					if (!id.strip()) {
						alert('Please select a list');
						return;
					}
					var listids = $('$listidsName').value;
					if (listids) listids = listids.evalJSON();
					if (!listids.join) listids = [];
					listids.push(id);
					$('$listidsName').value = listids.toJSON();
					reset_windows();
					get_liststats([id].toJSON());
				}
				
				// @param json, json-encoded ARRAY of listids.
				function get_liststats(json) {
					var listids = json.evalJSON();
					if (!listids.join)
						return;
						
					new Ajax.Request('ajax.php?type=liststats&listids='+json, {
						onSuccess: function(transport) {
							var stats = transport.responseJSON;
							if (!stats) {
								alert('No data available for this list');
								return;
							}
							
							for (var i = 0; i < stats.length; i++) {
								var data = stats[i];
								
								// Keep a hidden input field to keep track of id for this table row.
								var nameTD = new Element('td').update(new Element('input',{'type':'hidden','value':data['id']})).insert(data['name']);
								var countTD = new Element('td').update(data.total + ' Total');
								if (data.added > 0)
									countTD.insert(', with ' + data.added + ' Added');
								if (data.removed > 0)
									countTD.insert(', with ' + data.removed + ' Skipped');
								var actionsTD = new Element('td');
								actionsTD.insert( '" . icon_button('Preview','information') . "');
								actionsTD.insert( '" . icon_button('Remove','information') . "');
								var previewButton = actionsTD.down('button', 0);
								previewButton.observe('click', function(event) {
									var id = event.element().up('tr').down('input[type=\"hidden\"]').getValue();
									window.open('showlist.php?id='+id, 'Preview List'+Math.random());
								});
								var removeButton = actionsTD.down('button', 1);
								removeButton.observe('click', function(event) {
									var tr = Event.element(event).up('tr');
									var id = tr.down('input[type=\"hidden\"]').getValue();
									tr.remove();
									if (premadeLists && premadeLists[id]) {
										premadeLists[id]['added'] = false;
										refresh_listSelectbox();
									}
									var listids = $('$listidsName').value;
									if (listids) listids = listids.evalJSON();
									if (listids.join) {
										listids = listids.without(id);
										$('$listidsName').value = listids.toJSON();
										if (listids.length < 1) {
											$('listChoose_listids_icon').src = 'img/icons/error.gif';
											$('listChoose_listids_msg').update('The lists you choose will appear in this table');
											$('listChoose_listids_fieldarea').morph('background: rgb(255,255,200)', {duration:0.4});
										}
									} else {
										alert('Fatal ERROR??');
									}
								});
								
								$('finalListsTable').insert(new Element('tr').insert(nameTD).insert(countTD).insert(actionsTD));
								
								// Validation effects.
								$('listChoose_listids_fieldarea').morph('background:rgb(200,255,200)', {duration:0.4});
								$('listChoose_listids_icon').src='img/icons/accept.gif';
								$('listChoose_listids_msg').update('You may still add additional lists');
							}
						}
					});
					
					// Mark any premade lists that were inserted.
					if (premadeLists) {
						for (var i = 0; i < listids.length; i++) {
							var id = listids[i];
							if (!premadeLists[id])
								continue;
							premadeLists[id]['added'] = true;
						}
					}
					
					refresh_listSelectbox();
				}
			</script>";
		return $str;
	}
}
?>
