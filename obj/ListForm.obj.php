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
			td {
				vertical-align: top;
			}
			select {
				min-width: 70px;
			}
			</style>
			<table>
				<tr>
					<td style='width:400px'>
						<div id='helperWindow'>
							<button id='buildRulesButton' type='button'>Build a list using rules</button> or <button id='chooseListButton' type='button'>Choose an existing list</button>
						</div>
						
						<div id='buildRulesWindow'>
							<h3>Build Rules</h3>
							<div id='rulesDiv'></div>
							<div><button id='buildRulesCancelButton' type='button'>Cancel</button><button id='buildRulesDoneButton' type='button'>Done</button></div>
						</div>
						
						<div id='chooseListWindow'>
							<h3>Choose a List</h3>
							<div id='listSelectDiv'></div>
							<div><button id='chooseListCancelButton' type='button'>Cancel</button><button id='chooseListDoneButton' type='button'>Done</button></div>
						</div>
					</td>
					
					<td style='width:400px'>
						<h3>Final Lists</h3>
						<table><tbody id='finalListsTable'>
						</tbody></table>
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
						
						// Load From Session Data.
						if($('$listidsName').value) {
							get_liststats($('$listidsName').value);
						}
					}
				});
				
				// Build Rules Buttons
				$('buildRulesButton').observe('click', function(event) {
					event.stop();
					$('buildRulesWindow').show();
					$('chooseListWindow').hide();
					$('helperWindow').hide();
				});
				$('buildRulesCancelButton').observe('click', function(event) {
					event.stop();
					reset_windows();
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
					$('helperWindow').hide();
				});
				$('chooseListCancelButton').observe('click', function(event) {
					event.stop();
					reset_windows();
				});
				$('chooseListDoneButton').observe('click', function(event) {
					event.stop();
					var id = listSelectbox.getValue();
					add_list(id);
				});
				
				function reset_windows() {
					$('buildRulesWindow').hide();
					$('chooseListWindow').hide();
					$('helperWindow').show();
				}
				
				function refresh_listSelectbox() {
					listSelectbox = new Element('select');
					listSelectbox.insert(new Element('option', {'value':''}).insert('-- Select a List --'));
					$('listSelectDiv').update(listSelectbox);
					if (!premadeLists)
						return;
					for (var id in premadeLists) {
						if (!premadeLists[id]['added'])
							listSelectbox.insert(new Element('option', {'value':id}).update(premadeLists[id]['name']));
					}
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
					if (!listids.length)
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
								var totalTD = new Element('td').update(data['total']);
								var actionsTD = new Element('td');
								
								var removeButton = new Element('button', {'type':'button'}).update('Remove');
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
									if (listids.length) {
										listids = listids.without(id);
										$('$listidsName').value = listids.toJSON();
									}
								});
								actionsTD.update(removeButton);
								
								$('finalListsTable').insert(new Element('tr').insert(nameTD).insert(totalTD).insert(actionsTD));
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

class ValLists extends Validator {
	var $onlyserverside = true;
	
	function validate ($value, $args) {
		global $USER;
		error_log('ValLists =========== $value=' . print_r($value,true));
		return true;
		return 'Testing testing testing testing testing, validate if any list has count == 0';
	}
}
?>
