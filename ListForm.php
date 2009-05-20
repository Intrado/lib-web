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
		$this->formdata = array(
			"listids" => array(
				"label" => "",
				"value" => "",
				"validators" => array(array('ValRequired')),
				"control" => array("HiddenField"),
				"helpstep" => null
			)
		);
		
		$this->helpsteps = null;
		$this->ajaxsubmit = true;
		
		$this->serialnum = md5(serialize($this->formdata));
	}
	
	function handleRequest() {
		global $USER;
		global $RULE_OPERATORS;
		
		error_log("FORMDATA" . print_r($this->formdata['listids']['value'],true));
		if (!isset($_REQUEST['form']) || $_REQUEST['form'] != $this->name)
			return false; //nothing to do
	
		// Wizard clicked Next.
		if ($USER->authorize('createlist') && isset($_POST['submit'])) {
			//check the form snum vs loaded formdata
			if (isset($_REQUEST['ajax']) && $this->checkForDataChange()) {
				$result = array("status" => "fail", "datachange" => true);
				header("Content-Type: application/json");
				echo json_encode($result);
				exit();
			}
			
			$errors = $this->validate();
			
			//if this is an ajax request, validate now and return json results for the form
			if (isset($_REQUEST['ajax']) && $errors !== false) {
				error_log('STOp RIGHT THERE!!!!');
				$result = array("status" => "fail", "validationerrors" => $errors);
				header("Content-Type: application/json");
				echo json_encode($result);
				exit();
			} else {
				return true;
			}
		}

		// Everything below is ajax.
		if (!isset($_REQUEST['ajax']))
			return;

		error_log('DOING AJAX STUFF');
		
		$ajaxReturn = false;
		
		// Remove List
		if ($USER->authorize('createlist') && isset($_GET['removelistid'])) {
			$ids = explode('|', $this->formdata['listids']['value']);
			$i = array_search($_GET['removelistid'], $ids);
			if ($i !== false) {
				unset($ids[$i]);
				$this->formdata['listids']['value'] = implode('|', $ids);
			}
		// Use Premade List
		} else if ($USER->authorize('createlist') && isset($_GET['uselistid'])) {
			$list = new PeopleList($_GET['uselistid']);
			if ($list->id) {
				$ids = explode('|', $this->formdata['listids']['value']);
				if (!in_array($list->id, $ids)) {
					$ids[] = $list->id;
					$this->formdata['listids']['value'] = implode('|', $ids);
				}
				$ajaxReturn = $list->id;
			} else {
				error_log("uselistid:::::::::::: id doesn't exist!");
			}
		// Save Rules
		} else if ($USER->authorize('createlist') && isset($_POST['ruledata'])) {
			$ruledata = json_decode($_POST['ruledata']);
			$summary = array();
			$rules = array();
			foreach ($ruledata as $data) {
				if (!$rule = Rule::initFrom($data->fieldnum, $data->type, $data->logical, $data->op, $data->val))
					continue; // TODO: Should this add as many as possible, or break after the first error?
				$rules[] = $rule;
				
				// SUMMARY
				$fieldname = FieldMap::getName($rule->fieldnum);
				$summary[] = $fieldname;
			}
			
			$summary = implode(', ', $summary);
			
			// If summary is empty, then there's no rule to add so don't create the list.
			if (!empty($summary)) {
				$list = new PeopleList(null);
				$list->name = $summary;
				$list->description = 'JobWizard List ' . date('Y M d, H:i:s', time());
				$list->userid = $USER->id;
				$list->deleted = 1;
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
					
					$ids = explode('|', $this->formdata['listids']['value']);
					$ids[] = $list->id;
					$this->formdata['listids']['value'] = implode('|', $ids);
					$ajaxReturn = $list->id;
				}
			}
		}
		error_log($ajaxReturn);
		
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

		foreach ($this->formdata as $name => $itemdata) {
			//check for section titles
			if (is_string($itemdata)) {
				if ($lasthelpstep) {
					$str .= '</table></fieldset>';
					$lasthelpstep = false;
				}
				
				$str .= '
					<h2>'.$itemdata.'</h2>';
				unset($this->formdata[$name]); //hide these from showing up in data sent to form_load
				continue;
			}
			
			
			if (isset($itemdata['control'])) {
				$control = $itemdata['control'];
			} else {
				//set a hidden field
				$control = array("HiddenField");
			}
			
			$formclass = $control[0];
			$item = new $formclass($this,$name, $control);
			
			//inject which function to use for getting the value from this control
			$this->formdata[$name]['jsgetvalue'] = $item->jsGetValue();

			if ($formclass == "HiddenField") {
				$str.= $item->render($itemdata['value']);
				//unset($this->formdata[$name]); //hide these from showing up in data sent to form_load
				continue;
			}

			if ($lasthelpstep && $lasthelpstep != $itemdata['helpstep']) {
				$str .= '
			</table></fieldset>';
			}
			
			if ($lasthelpstep != $itemdata['helpstep']) {
				$lasthelpstep = $itemdata['helpstep'];
				$str .= '<fieldset id="'. $this->name . '_helpsection_'.$lasthelpstep.'"><legend>Step '.$lasthelpstep.'</legend><table width="100%" cellspacing="0" table-layout="fixed" class="formcontenttable">';
			}
			
			$n = $this->name."_".$item->name;
			$l = $itemdata['label'];

			if ($formclass == "FormHtml") {
				$str.= '
				<tr><th class="formtableheader formlabel">'.$l.': </th><td class="formtableicon"></td><td class="formtablecontrol">'.$item->render('').'</td></tr>
				';
				unset($this->formdata[$name]); //hide these from showing up in data sent to form_load
			} else {
				$value = $itemdata['value'];
				$requiredfields = isset($itemdata['requires']) ? $this->getFieldValues($itemdata['requires']) : array();
				$t = $this->tindex++;
				$i = "img/pixel.gif";
				$style = "";
				$msg = false;
			
				//see if valrequired is any of the validators
				$isrequired = false;
				foreach ($itemdata['validators'] as $v) {
					if ($v[0] == "ValRequired") {
						$isrequired = true;
						break;
					}
				}
				
				
				$isblank = (is_array($value) && !count($value)) || (!is_array($value) && mb_strlen($value) == 0);
				
				if ($this->getSubmit() || !$isblank) {
					//validate and show normally
					$valresult = Validator::validate_item($this->formdata,$name,$value,$requiredfields);
					if ($valresult === true) {
						$i = "img/icons/accept.gif";
						$style = 'style="background: rgb(225,255,225);"' ;
						$msg = false;
					} else {
						list($validator,$msg) =  $valresult;
						$i = "img/icons/exclamation.gif";
						$style = 'style="background: rgb(255,200,200);"' ;
					}
				} else if (!$this->getSubmit() && $isblank && $isrequired) {
					//show required highlight
					$i = "img/icons/error.gif";
					$style = 'style="background: rgb(255,255,220);"' ;
					$msg = "Required";
				}
				
				$str.= '
				<tr id="'.$n.'_fieldarea" '.$style.'>
					<th class="formtableheader"><label class="formlabel" for="'.$n.'" tabindex="'.$t.'" >'.$l.': </label></th>
					<td class="formtableicon"><img alt="" id="'.$n.'_icon" src="'.$i.'" /></td>
					<td class="formtablecontrol">
						'.$item->render($value).'
						<div id="'.$n.'_msg" class="underneathmsg">'.($msg ? $msg : "").'</div>
					</td>
				</tr>
				';
			}
		} //foreach
		
		// ListForm Stuff
		$str.= "
<!-- TODO: remove these styles -->
<style type='text/css'>
td,th {
	vertical-align: top;
	text-align: left;
	padding: 2px;
}
th {
	border-bottom: solid 1px black;
}
select {
	min-width: 70px;
}
h2,h3 {
	margin: 0;
	padding: 0;
	font-family: verdana;
	font-weight: normal;
}
h2 {
	font-size: 14pt;
}
h3 {
	font-size: 12pt;
}
td.ValueTD input[type='text'] {
	width: 70px;
}
</style>
<table style='margin-bottom: 20px; border: solid 10px white'>
	<tr>
		<td colspan=2 style='width: 100%; background: rgb(230,230,255); border:solid 1px blue;'>
			<h2>Lists To Use</h2>
			<table><tbody id='listsToUseTable'>
				<tr>
					<th>Name</th>
					<th>Count</th>
					<th> </th>
				</tr>
			</tbody></table>
		</td>
	</tr>
	
	<tr>
		<td colspan=2 style='width:100%; text-align: center;'>
			You can choose a premade list, or you can build rules right on this page.
		</td>
	</tr>
	
	<tr>
		<td style='width:300px; background:rgb(230,230,230); border: solid 10px white'>
			<h3>
				<div style='float: right'><button id='usePremadeListButton' type='button'>Use List</button></div>
				Premade Lists
			</h3>
			<div id='premadeListsDiv' style='clear:both'></div>
		</td>
		
		<td style='width:400px; background:rgb(230,230,230); border: solid 10px white'>
			<h3>
				<div style='float: right'><button id='saveRulesButton' type='button'>Apply Rules</button></div>
				Build Rules
			</h3>
			<div id='rulesDiv' style='clear:both'></div>
		</td>
	</tr>
</table>
		";

 		//submit buttons
                foreach ($this->buttons as $code) {
                        $str .= $code;
                }
		
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
function load_premade_lists() {
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
	var selectbox = new Element('select');
	selectbox.insert(new Element('option', {'value':''}).insert('-- Select a List --'));
	for (var listid in premadeListsCache) {
		var added = false;
		if (premadeListsCache[listid]['added'])
			added = true;
		
		// Don't bother if the list has already been added to $('listsToUseTable').
		if (!added)
			selectbox.insert(new Element('option', {'value':listid}).update(premadeListsCache[listid]['name'].escapeHTML()));
	}

	$('premadeListsDiv').update(selectbox);
}

function insert_list(listid) {
	if (listid < 1) {
		alert('Sorry, cannot use this list');
	}
	
	if (premadeListsCache[listid] && premadeListsCache[listid]['added'])
		return;
		
	// Get statistics, then insert into the DOM.
	new Ajax.Request('ajax.php?ajax&type=liststats&listid='+listid, {
		onSuccess: function (transport) {
			var data = transport.responseJSON;
			if (!data) {
				alert('Sorry, this there are no statistics for this list');
				return;
			}
			
			var nameTD = new Element('td').update(data['name'].escapeHTML()).insert(new Element('input', {'type':'hidden', 'value':data['id']}));
			var totalTD = new Element('td').update(data['total']);
			
			var removeButton = new Element('button', {'type':'button'}).update('Remove');
			removeButton.observe('click', function(event) {
				new Ajax.Request('jobwizard.php?ajax&form=$this->name&removelistid='+listid);
				
				var tr = event.element().up('tr');
				var listid = tr.down('input[type=\"hidden\"]').getValue();
				if (premadeListsCache[listid])
					premadeListsCache[listid]['added'] = false;
				tr.remove();
				refresh_premade_lists();
			});
			
			$('listsToUseTable').insert(new Element('tr').update(nameTD).insert(totalTD).insert(new Element('td').update(removeButton)));
			
			premadeListsCache[listid]['added'] = true;
			refresh_premade_lists();
		}
	});
}

// Keep a cache of the JSON-decoded data.
var premadeListsCache = null;
load_premade_lists();
$('usePremadeListButton').observe('click', function(event) {
	var listid = $('premadeListsDiv').down('select').getValue();
	new Ajax.Request('jobwizard.php?ajax&form=$this->name&uselistid='+listid, {
		onSuccess: function(transport) {
			var listid = transport.responseJSON;
			insert_list(listid);
		}
	});
});

var ruleWidget = new RuleWidget($('rulesDiv'));
$('saveRulesButton').observe('click', function(event) {
	event.stop();
	var json = ruleWidget.toJSON();
	
	new Ajax.Request('jobwizard.php?ajax&form=$this->name', {'method':'post',
		'postBody': 'ruledata='+json,
		onSuccess: function(transport) {
			var listid = transport.responseJSON;
			ruleWidget.clear_rules();
			insert_list(listid);
		}
	});
});

// TODO: Load $('listsToUseTable') from session-data.

form_load('$this->name',
	'$posturl',
	".json_encode($this->formdata).",
	".json_encode($this->helpsteps).",
	".($this->ajaxsubmit ? "true" : "false")."
);
</script>
		";
		
		error_log(json_encode($this->formdata));
		
		return $str;
	}
}


?>
