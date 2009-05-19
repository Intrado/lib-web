<?php

require_once("obj/Message.obj.php");
require_once("obj/MessagePart.obj.php");
require_once("obj/MessageAttachment.obj.php");
require_once("obj/AudioFile.obj.php");
require_once("obj/Voice.obj.php");
require_once("obj/FieldMap.obj.php");
require_once("obj/PeopleList.obj.php");
require_once("obj/Rule.obj.php");
require_once("obj/ListEntry.obj.php");
require_once("inc/date.inc.php");
require_once("inc/securityhelper.inc.php");

/* use the 3 column fieldset layout, each form item on a line by itself*/
class ListForm extends Form {
	function ListForm ($name) {
		$this->name = $name;
		$this->serialnum = md5(serialize($name)); // TODO: What to do with this? it used to be serialize($formdata).
	}
	
	function handleRequest() {
		global $USER;
		global $RULE_OPERATORS;
		
		error_log('handling request');
		if (!isset($_REQUEST['form']) || $_REQUEST['form'] != $this->name)
			return false; //nothing to do
		
		$ajaxReturn = false;
		
		// Wizard clicked Next.
		if ($USER->authorize('createlist') && isset($_POST['submit'])) {
			// TODO: submit.
			error_log('wizard click next');
		// Remove List.
		} else if ($USER->authorize('createlist') && isset($_POST['removelist'])) {
			// TODO: remove list.
			error_log('remove list');
		// Add Existing List.
		} else if ($USER->authorize('createlist') && isset($_POST['addlist'])) {
			// TODO: add list.
			error_log('add existing list');
		// Save Rules
		} else if ($USER->authorize('createlist') && isset($_POST['ruledata'])) {
			error_log('save rules');
			
			$ruledata = json_decode($_POST['ruledata']);
			$summary = array();
			$rules = array();
			foreach ($ruledata as $data) {
				if (!$rule = Rule::initFrom($data->fieldnum, $data->type, $data->logical, $data->op, $data->val))
					continue; // TODO: Should this add as many as possible, or break after the first error?
					
				$rules[] = $rule;
				
				// SUMMARY
				$fieldname = FieldMap::getName($rule->fieldnum);
				$opname = $RULE_OPERATORS[$data->type][$rule->op];
				if ($rule->op == 'in') {
					$opname = 'is';
					if ($rule->logical == 'and not')
						$opname = 'is NOT';
					$val = str_replace('|', ', ', $rule->val);
				} else {
					$val = str_replace('|', ' and ', $rule->val);
				}
				
				$line = "$fieldname $opname $val";
				if (strlen($line) > 30)
					$line = substr($line, 0, 28) . '..';
				$summary[] = $line;
			}
			
			$summary = implode(', ', $summary);
			
			error_log("SUMMARY FINAL: $summary");
			// If summary is empty, then there's no rule to add so don't create the list.
			if (!empty($summary)) {
				$list = new PeopleList(null);
				$list->name = $summary;
				$list->description = 'JobWizard List ' . date('Y M d, H:i:s', time());
				$list->userid = $USER->id;
				$list->deleted = 0; // TODO: Set to deleted=1.
				$list->update();
				
				if ($list->id) {
					// TODO: use a transaction, QuickUpdate('Begin..')    QuickUpdate('Commit'); read about it in mysql docs.
					foreach ($rules as $rule) {
						$rule->create();
						
						$le = new ListEntry();
						$le->listid = $list->id;
						$le->type = "R";
						$le->ruleid = $rule->id;
						$le->create();
					}
					
					$ajaxReturn = true;
					// TODO: Add this list id to session data.
				}
			} else {
				error_log("EMPTY RULES, CANCLED");
			}
		}
		
		header('Content-Type: application/json');
		echo json_encode($ajaxReturn);
		exit;
	}
	
	function render () {
		$theme = getBrandTheme();
		$lasthelpstep = false;
		
		$posturl = $_SERVER['REQUEST_URI'];
		$posturl .= mb_strpos($posturl,"?") !== false ? "&" : "?";
		$posturl .= "form=". $this->name;
		
		$str = '
		<div class="newform_container">
		<form class="newform" id="'.$this->name.'" name="'.$this->name.'" method="POST" action="'.$posturl.'" style="width: 100%; /* TODO fix main css */">
		<input name="formsnum_' . $this->name . '" type="hidden" value="' . $this->serialnum . '">
		<table width="100%" cellspacing="0" table-layout="fixed"><tr><td valign=top> <!-- FORM CONTENT -->';

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
			<h2>Build Your List
				<div>
					<button onclick='save_rules(); return false;' type='button'>Save Rules</button>
				</div>
			</h2>
			<div id='Rules'></div>
		</td>
		
		<td>
			<h2>Select a List</h2>
			<div id='Lists'></div>
		</td>
	</tr>
</table>
		";
		
		$str .= '
				<!-- END FORM CONTENT -->
				</td>
				<!-- No Helper -->
			</tr>
		</table>
		</form>
		
		
		
		</div>
		<div style="clear: both;"></div>
		';

		$str .= "
<script type='text/javascript' src='script/calendar.js'></script>
<script type='text/javascript' src='script/RuleWidget.js'></script>
<script type='text/javascript'>
function show_lists() {
	new Ajax.Request('ajax.php?ajax&type=lists', {
		onSuccess: function(transport) {
			var data = transport.responseJSON;
			if (!data) {
				alert('you are not logged in');
				return;
			}
			
			listSelect = new Element('select');
			$('Lists').update(listSelect);
			listSelect.insert(new Element('option', {'value':''}).insert('-- Select a List --'));
			//listSelect.observe('change', ajaxthing);
	
			for (var i in data) {
				if (data[i]['id'] === undefined){
					alert('sir goodbye'); 
					break;
				}
				listSelect.insert(new Element('option', {'value':data[i]['id']}).update(data[i]['name']));
			}
		}
	});
}

function save_rules() {
	var json = ruleWidget.toJSON();
	
	new Ajax.Request('jobwizard.php?ajax&form=$this->name', {'method':'post',
		'postBody': 'ruledata='+json,
		onSuccess: function(transport) {
			var data = transport.responseText;
			ruleWidget.clear_rules();
		}
	});
}
</script>

<script type='text/javascript'>
	var listSelect;
	//show_lists();
	
	var ruleWidget = new RuleWidget($('Rules'));
</script>
		";
		return $str;
	}
}


?>