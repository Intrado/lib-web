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
					
					$ajaxReturn = $list->id;
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
			<h2>Build Rules
				<button id='SaveRules' type='button' style='display:block; float:right'>Save Rules</button>
			</h2>
			<div id='BuildRules' style='clear:both'></div>
		</td>
		
		<td>
			<h2>Lists To Use</h2>
			<ul id='ListsToUse' style='border: solid 2px brown'></ul>
			
			<h2>Premade Lists</h2>
			<div id='PremadeLists'></div>
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
function show_premade_lists() {
	new Ajax.Request('ajax.php?ajax&type=lists', {
		onSuccess: function(transport) {
			premadeListsCache = transport.responseJSON;
			if (!premadeListsCache) {
				alert('you are not logged in');
				return;
			}

			refresh_premade_lists();
		}
	});
}

function refresh_premade_lists() {
	var selectBox = new Element('select');
	selectBox.insert(new Element('option', {'value':''}).insert('-- Select a List --'));
	for (var i in premadeListsCache) {
		var added = false;
		if (premadeListsCache[i]['added'])
			added = true;
		console.info('List['+i+'], added='+added);
		
		// Don't bother if the list has already been added to $('ListsToUse').
		if (!added)
			selectBox.insert(new Element('option', {'value':data[i]['id']}).update(data[i]['name']));
	}

	var addButton = new Element('button', {'type':'button'});
	addButton.observe('click', function(event) {
		var listid = $('PremadeLists').down('select').getValue();
		use_list(listid);
	});
	
	$('PremadeLists').update(selectBox);
	$('PremadeLists').insert(addButton);
}

function use_list(listid) {
	new Ajax.Request('ajax.php?ajax&type=liststats&listid='+listid, {
		onSuccess: function (transport) {
			var data = transport.responseJSON;
			if (!data) {
				alert('Sorry, this list cannot be added at the moment.');
				return;
			}
			
			var listName = new Element('span').update(data['name']);
			var listTotal = new Element('span').update(data['total']);
			
			var removeButton = new Element('button', {'type':'button'}).update('Remove');
			removeButton.observe('click', function(event) {
				var li = event.element().up('li');
				var listid = li.down('input[type=\"hidden\"]').getValue();
				premadeListsCache[listid]['added'] = false;
				li.remove();
			});
			
			$('ListsToUse').insert(new Element('li').update(new Element('input', {'type':'hidden', 'value':data['id']})).insert(listName).insert(listTotal).insert(removeButton));
			
			premadeListsCache[listid]['added'] = true;
	}});
}

function save_rules() {
	var json = ruleWidget.toJSON();
	
	new Ajax.Request('jobwizard.php?ajax&form=$this->name', {'method':'post',
		'postBody': 'ruledata='+json,
		onSuccess: function(transport) {
			var listid = transport.responseJSON;
			ruleWidget.clear_rules();
			use_list(listid);
		}
	});
}
</script>

<script type='text/javascript'>
	// Keep a cache of the JSON-decoded data.
	var premadeListsCache;
	show_premade_lists();
	
	var ruleWidget = new RuleWidget($('BuildRules'));
	$('SaveRules').observe('click', function(event) {
		event.stop();
		save_rules();
	});
	
	// TODO: Load $('ListsToUse') from session-data.
</script>
		";
		return $str;
	}
}


?>