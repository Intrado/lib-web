<?php

/* use the 3 column fieldset layout, each form item on a line by itself*/
class ListForm extends Form {
	function ListForm ($name) {
		$this->formdata['listids'] = array(
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
		$str.= "
			<style type='text/css'>
			td,th {
				vertical-align: top;
				text-align: left;
			}
			select {
				min-width: 70px;
			}
			#rulesDiv table {
				border-collapse: collapse;
				width: 100%;
				border: solid 1px rgb(220,220,220);
			}
			#rulesDiv td {
				padding: 4px;
				border-top: solid 1px rgb(220,220,220);
			}
			td.FieldmapTD {
				text-align: right;
			}
			td.ValueTD input[type='text'] {
				width: 80px;
			}
			input.Datebox {
				border-bottom: solid 2px orange;
			}
			</style>
			<table>
				<tr>
					<td style='width:700px'>
						<h3>Final Lists</h3>
						<table style='width:100%; border: solid 2px rgb(200,200,200); border-collapse:collapse;'>
							<tbody id='finalListsTable'>
								<tr>
									<th>List Name</th>
									<th>Count</th>
									<th></th>
								</tr>
							</tbody>
							<tbody style='border: solid 2px rgb(255,200,100); background:rgb(255,255,200);padding:10px;'>
								<tr>
									<td colspan=3>
										<h3>Want to add a list?
											
												<!--
												<select>
													<option value=''>-- Select a Method --</option>
													<option value='buildrules'>Build a list using rules</option>
													<option value='chooselist'>Choose an existing list</option>
												</select>
												-->
												<button id='buildRulesButton' type='button'>Build a list using rules</button> or <button id='chooseListButton' type='button'>Choose an existing list</button>
										</h3>
									</td>
								</tr>
								<tr>
									<td colspan=3>
										<center>
										<div id='buildRulesWindow'>
											<h3>Build Rules</h3>
											<div id='rulesDiv'></div>
											<div id='rulesDiv2'></div>
											<button id='buildRulesDoneButton' type='button'>Save as a List</button>
										</div>
										
										<div id='chooseListWindow'>
											<h3>Choose a List</h3>
											<div id='listSelectDiv'></div>
											<button id='chooseListDoneButton' type='button'>Add List</button>
										</div>
										</center>
									</td>
								</tr>
							</tbody>
						</table>
					</td>
				</tr>
				<tr>
					<td style='padding: 20px; margin: 10px; border:solid 1px rgb(200,220,240)'>
						
					</td>
				</tr>
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
						
					document.formvars[name] = {
						formdata: formdata,
						scriptname: scriptname, //used for any ajax calls for this form
						ajaxsubmit: true,
					};
					//submit handler
					form.observe('submit',form_handle_submit.curry(name));
				}
				listform_load('{$this->name}', '$posturl', '" . json_encode($this->formdata) . "');
			</script>
			<script type='text/javascript' src='script/calendar.js'></script>
			<script type='text/javascript' src='script/RuleWidget.js'></script>
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
				
				
				// Build Rules Buttons
				$('buildRulesButton').observe('click', function(event) {
					event.stop();
					ruleWidget.clear_rules();
					$('buildRulesWindow').show();
					$('chooseListWindow').hide();
				});
				$('buildRulesDoneButton').observe('click', function(event) {
					event.stop();
					new Ajax.Request('ajaxlistform.php?type=saverules', { 'method':'post',
						'postBody': 'ruledata='+ruleWidget.toJSON(),
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
					var listSelectbox = new Element('select');
					listSelectbox.insert(new Element('option', {'value':''}).insert('-- Select a List --'));
					$('listSelectDiv').update();
					if (!premadeLists)
						return;
					for (var id in premadeLists) {
						console.info(premadeLists[id]);
						if (!premadeLists[id]['added'])
							listSelectbox.insert(new Element('option', {'value':id}).update(premadeLists[id]['name']));
					}
					$('listSelectDiv').update(listSelectbox);
				}
				
				function add_list(id) {
					var listids = $('$listidsName').value;
					if (listids) listids = listids.evalJSON();
					if (!listids.length) listids = [];
					listids.push(id);
					$('$listidsName').value = listids.toJSON();
					alert($('$listidsName').value);
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
									countTD.insert(', ' + data.added + ' Added, ');
								if (data.removed > 0)
									countTD.insert(', ' + data.removed + ' Skipped');
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
									}
									alert($('$listidsName').value);
								});
								
								$('finalListsTable').insert(new Element('tr').insert(nameTD).insert(countTD).insert(actionsTD));
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
