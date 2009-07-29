<?
require_once("../inc/subdircommon.inc.php");
require_once("../inc/html.inc.php");

//set expire time to + 1 hour so browsers cache this file
header("Expires: " . gmdate('D, d M Y H:i:s', time() + 60*60) . " GMT"); //exire in 1 hour, but if theme changes so will hash pointing to this file
header("Content-Type: text/javascript");
header("Cache-Control: private");
?>

//----------- RuleWidget EVENTS (This example shows all custom events) ------------
// NOTE: Your client should only allow rule-building if 'RuleWidget:Ready' is fired.
//var ruleWidget = new RuleWidget($('ruleWidgetContainer'));
//ruleWidget.container.observe('RuleWidget:Ready',..);
//ruleWidget.container.observe('RuleWidget:DeleteRule',..);
//ruleWidget.container.observe('RuleWidget:AddRule',..);
//ruleWidget.container.observe('RuleWidget:InColumn',..);
//ruleWidget.container.observe('RuleWidget:ChangeField',..);
//ruleWidget.startup(); // Required, must be called AFTER registering ruleWidget.container.observe('RuleWidget:Ready',..)

var RuleWidget = Class.create({
	//----------------------------- PUBLIC FUNCTIONS --------------------------

	// @param container, the DOM container for this widget.
	initialize: function(container, readonly, allowedFields) {
		if (!allowedFields)
			this.allowedFields = ['f','g','c'];
		else
			this.allowedFields = allowedFields;
			
		this.container = container;
		this.warningDiv = new Element('div', {'style':'color:red; padding:2px'});
				this.warningDiv.hide();
		this.container.insert(this.warningDiv);
		this.ruleHelperDiv = new Element('div', {'style':''});
		this.ruleHelperContentDiv = new Element('div');
		this.ruleHelperInfoDiv = new Element('div', {'style':'clear:both'});
		
		this.ruleHelperDiv.insert(this.ruleHelperContentDiv).insert(this.ruleHelperInfoDiv);
														
		this.rulesTableAboveEditor = new Element('tr'); // Right on top of rulesTableFootLastTR
		this.rulesTableFootLastTR = new Element('tr'); // For rule editor
		this.rulesTableBody = new Element('tbody');
		this.container
			.insert(new Element('table', {})
				.insert(this.rulesTableBody)
				.insert(new Element('tfoot')
					.insert(this.rulesTableAboveEditor)
				)
			)
			.insert(new Element('table', {style:'margin:3px'})
				.insert(new Element('tbody')
					.insert(new Element('tr').insert(new Element('td', {'colspan':'100'}).insert(this.ruleHelperDiv)))
					.insert(this.rulesTableFootLastTR)
				)
			);
		
		if (!readonly)
			this.ruleEditor = new RuleEditor(this, this.rulesTableFootLastTR);
		this.clear_rules();
		
		this.delayActions = false;
				
		this.fieldmaps = null;
		this.operators = null;
		this.reldateOptions = null;
		this.multisearchHTMLCache = {}; // Cache of multisearch DOM, indexed by fieldnum.
	},
	
	// You must call startup() AFTER registering ruleWidget.container.observe('RuleWidget:Ready', ..);
	// @param preloadedRules, optional array of rules to preload
	startup: function(preloadedRules) {
		if (preloadedRules && !preloadedRules.join)
			preloadedRules = null;
		cachedAjaxGet('ajax.php?type=rulewidgetsettings',
			function(transport, rules) {
				var data = transport.responseJSON;
				if (!data) {
					// Silent failure, client should not provide any rule-building tools unless the event RuleWidget:Ready is fired.
					//alert('<?=addslashes(_L('Sorry cannot get fieldmaps'))?>');
					return;
				}
				this.operators = data['operators'];
				this.reldateOptions = data['reldateOptions'];
				this.fieldmaps = {};
				// data['fieldmaps'] is indexed by record id, we prefer indexing by fieldnum.
				for (var i in data['fieldmaps']) {
					var fieldnum = data['fieldmaps'][i].fieldnum;
					if (this.allowedFields.indexOf(fieldnum.charAt(0)) >= 0)
						continue;
					this.fieldmaps[fieldnum] = data['fieldmaps'][i];
					for (var type in this.operators) {
						if (this.fieldmaps[fieldnum].options.match(type))
							this.fieldmaps[fieldnum].type = type;
					}
				}
				// Add "is not" to the multisearch operators.
				this.operators['multisearch']['not'] = '<?=addslashes(_L('is NOT'))?>';
				this.operators['multisearch']['in'] = '<?=addslashes(_L('is'))?>';
				if (this.ruleEditor)
					this.ruleEditor.reset();
				
				// preloaded rules
				var someUnused = false;
				if (rules) {
					for (var i = 0; i < rules.length; ++i) {
						if (!rules[i].fieldnum)
							break; // Bad data.

						if (rules[i].fieldnum && !this.fieldmaps[rules[i].fieldnum]) {
							if (this.ruleEditor)
								someUnused = true;
							continue;
						}
						this.insert_rule(rules[i], true);
					}
				}
				if (someUnused) {
					this.warningDiv.update('<?=addslashes(_L("WARNING: Some rules are not visible due to security restrictions or system configuration."))?>');
					this.warningDiv.show();
				}
				this.container.fire('RuleWidget:Ready');
			}.bindAsEventListener(this, preloadedRules ? preloadedRules : null)
		);
	},
		
	clear_rules: function() {
			this.appliedRules = {};
			this.rulesTableBody.update();
			if (this.ruleEditor)
				this.ruleEditor.reset();
	},
	
	// Updates contents of tr with human-readable fieldmapTD, criteriaTD, and valueTD.
	// @param data, {fieldnum, type, logical, op, val}
	// @param tr, table row DOM element.
	// @param addHiddenFieldnum, optional boolean specifying to add a hidden input with value=fieldnum
	format_readable_rule: function(data, tr, addHiddenFieldnum) {
		if (!data.fieldnum || !data.op || !data.logical)
			return false;
		if (!this.fieldmaps[data.fieldnum])
			return false;
		if (!data.type)
			data.type = this.fieldmaps[data.fieldnum].type;
		// FieldmapTD
		var fieldmapTD = new Element('td', {'class':'list', 'style':'', 'valign':'top'}).insert(this.fieldmaps[data.fieldnum].name);
		// Keep track of the row's data.fieldnum by using a hidden input.
		if (addHiddenFieldnum)
			fieldmapTD.insert(new Element('input', {'type':'hidden', 'value':data.fieldnum}));
		// CriteriaTD
		var criteriaTD = new Element('td', {'class':'list', 'style':'', 'valign':'top'});
		var criteria = this.operators[data.type][data.op];
		if (data.op == 'in') {
			criteria = '<?=addslashes(_L('is'))?>';
			if (data.logical == 'and not')
				criteria = '<?=addslashes(_L('is NOT'))?>';
		}
		criteriaTD.insert(criteria);
		
		// ValueTD
		var value = '';
		if (!data.val.join) {
			if (data.type == 'multisearch') {
				value = data.val.replace(/\|/g, ',');
			} else if (data.op == 'reldate') {
				value = this.reldateOptions[data.val];
			} else {
				value = data.val.replace(/\|/g, ' <?=addslashes(_L('and'))?> ');
			}
		} else if (data.type == 'multisearch') {
			value = data.val.join(',');
		} else {
			value = data.val.join(' <?=addslashes(_L('and'))?> ');
		}
		var widthCSS = (addHiddenFieldnum) ? ' width: 150px; ' : '';
		var heightCSS = (value.length > 400) ? ' overflow: auto; height: 300px; ' : '';
		var valueTD = new Element('td', {'class':'list', 'valign':'top'}).update(new Element('div', {'style': 'overflow:hidden; ' + widthCSS + heightCSS}).update(value.escapeHTML().replace(/,/g, ',<br/>') + '&nbsp;'));
		tr.insert(fieldmapTD).insert(criteriaTD).insert(valueTD);
		
		return true;
	},
	
	refresh_rules_table: function() {
		var ruleCount = $H(this.appliedRules).keys().length + 1;
		
		var rows = this.rulesTableBody.rows;
		for (var i = 0; i < rows.length; i++) {
			rows[i].cells[0].update('<?=addslashes(_L("Rule #"))?>' + (i+1));
		}
	},
	
	// @param data, {fieldnum, type, logical, op, val}
	insert_rule: function(data, suppressFire) {
		if (!data) {
			alert('<?=addslashes(_L('Please specify a value'))?>');
			return false;
		}
		var tr = new Element('tr');
		tr.appendChild(new Element('td', {'valign':'top', 'style':'white-space:nowrap; font-size:90%'}));
		if (!this.format_readable_rule(data, tr, true)) {
			if (!suppressFire)
				alert('<?=addslashes(_L('cannot add this rule'))?>');
			return false;
		}
		if (!this.delayActions || suppressFire) {
			// Actions
			if (this.ruleEditor) {
					var actionTD = new Element('td', { 'style':'', 'valign':'top'}).update('<?=addslashes(icon_button(_L('Remove'), 'delete'))?>').insert('<br style=\"clear:both\"/>');
					var deleteRuleButton = actionTD.down('button');
					tr.insert(actionTD);
					deleteRuleButton.observe('click', function(event, tr, fieldnum) {
							event.stop();
							
							if (!this.delayActions) {
								tr.remove();
								delete this.appliedRules[fieldnum];
								this.refresh_rules_table();
								if (this.ruleEditor)
									this.ruleEditor.reset();
							}
							this.container.fire('RuleWidget:DeleteRule', {'fieldnum':fieldnum});
					}.bindAsEventListener(this, tr, data.fieldnum));
			}
			this.rulesTableBody.insert(tr);
			this.appliedRules[data.fieldnum] = data;
			this.refresh_rules_table();
			if (this.ruleEditor)
				this.ruleEditor.reset();
		}
		if (!suppressFire)
			this.container.fire('RuleWidget:AddRule', {'ruledata':$H(data)});
		return true;
	},

	// Returns json-encoded array of rules.
	// Example: [{fieldnum:"f01", type:"text", logical:"and", op:"eq", val:"Kee-Yip"}]
	toJSON: function() {
		return $H(this.appliedRules).values().toJSON();
	}
});

var RuleEditor = Class.create({
	//----------------------------- PUBLIC FUNCTIONS --------------------------

	// @param ruleWidget, the parent RuleWidget.
	// @param containerTR, the DOM container TR for this editor.
	initialize: function(ruleWidget, containerTR) {
	this.ruleWidget = ruleWidget;
	
	var fieldsetCSS = 'padding:0px; margin:0;';

	this.fieldTD = new Element('td',{'style':'', 'valign':'top'});
		this.criteriaTD = new Element('td',{'style':'', 'valign':'top'});
		this.valueTD = new Element('td',{'style':'', 'valign':'top'});
		this.actionTD = new Element('td',{'style':'', 'valign':'top'});
		if (!this.ruleWidget.noHelper) {
			this.fieldTD.update('<span style="font-style:italic; font-weight: bold;"><?=addslashes(_L('Field'))?></span>');
			this.criteriaTD.update('<span style="font-style:italic; display:none; font-weight: bold;"><?=addslashes(_L('Criteria'))?></span>');
			this.valueTD.update('<span style="font-style:italic; display:none; font-weight: bold;"><?=addslashes(_L('Value'))?></span>');
			this.actionTD.update('<span style="font-style:italic; display:none; font-weight: bold;">&nbsp;</span>');
		}
			
		this.fieldTD.insert(new Element('div', {'class':'RuleWidgetColumnDiv'})).insert(new Element('fieldset', {'id':'AddRuleFieldmap', style:fieldsetCSS}).insert(new Element('div')));
		this.criteriaTD.insert(new Element('div', {'class':'RuleWidgetColumnDiv'})).insert(new Element('fieldset', {'id':'AddRuleCriteria', style:fieldsetCSS}).insert(new Element('div')));
		this.valueTD.insert(new Element('div', {'class':'RuleWidgetColumnDiv'})).insert(new Element('fieldset', {'id':'AddRuleValue', style:fieldsetCSS}).insert(new Element('div', {style:'padding:3px'})));
		this.actionTD.insert(new Element('div', {'class':'RuleWidgetColumnDiv'})).insert(new Element('fieldset', {'id':'AddRuleAction', style:fieldsetCSS}).insert(new Element('div')));
		
		containerTR.insert(this.fieldTD).insert(this.criteriaTD).insert(this.valueTD).insert(this.actionTD);

		this.fieldTD.down('span').observe('click', this.trigger_event_in_column.bindAsEventListener(this, this.fieldTD));
	},
	
	trigger_event_in_column: function(nullableEvent, td) {
		if (nullableEvent && nullableEvent.element().tagName.toUpperCase() == 'LABEL')
			return;
		var column = '';
		if (td == this.fieldTD)
			column = 'field';
		else if (td == this.criteriaTD)
			column = 'criteria';
		else if (td == this.valueTD)
			column = 'value';
		else if (td == this.actionTD)
			column = 'action';
		else
			return;
			
		this.ruleWidget.container.fire('RuleWidget:InColumn', {'td':td, 'column':column});
	},

	get_selected_fieldmap: function() {
		if (!this.fieldTD.down('select'))
			return false;
		var fieldnum = this.fieldTD.down('select').getValue();
		
		if (!this.ruleWidget.fieldmaps[fieldnum])
			return false;
		var type = this.ruleWidget.fieldmaps[fieldnum].type;
		
		return {'fieldnum': fieldnum, 'type': type};
	},
	
	// Returns data for the rule, {fieldnum, type, logical, op, val}
	get_data: function() {
		var fieldmap = this.get_selected_fieldmap();
		if (!fieldmap)
			return false;
		var logical = 'and';
		var selected = this.criteriaTD.down('input:checked');
		if (!selected)
			return false;
		var op = selected.getValue();
		if (op == 'not') {
			logical = 'and not';
			op = 'in';
		}
		
		var val = [];
		// MULTISEARCH
		if (this.valueTD.down('ul')) {
			var multisearchValues = [];
			var checkboxes = this.valueTD.select('input');
			for (var i = 0; i < checkboxes.length; ++i) {
				if (checkboxes[i].checked)
					multisearchValues.push(checkboxes[i].getValue());
			}
			if (multisearchValues.length < 1)
				return false;
			val = multisearchValues;
		} else {
			// RELDATE_RELDATE
			if (this.valueTD.down('input[type="radio"]')) {
				var radio = this.valueTD.down('input:checked');
				if (radio)
					val = radio.getValue();
				else
					val = '';
			} else {
				// TEXT, NUMERIC, RELDATE_*
				var inputs = this.valueTD.select('input');
				if (inputs.length == 1) {
					val = inputs[0].getValue();
				} else if (inputs.length > 1) {
					for (var i = 0; i < inputs.length; ++i)
						val.push(inputs[i].getValue());
				}
			}
		}

		return {'fieldnum':fieldmap.fieldnum,
			'type':fieldmap.type,
			'logical':logical,
			'op':op,
			'val':val
		};
	},
	
	//----------------------------- PRIVATE FUNCTIONS --------------------------

	show_criteria_column: function(fieldnum) {
		var section = this.criteriaTD.down('fieldset').down('div');
		if (!fieldnum) {
			section.update();
			this.criteriaTD.down('span').stopObserving('click').hide();
			return;
		}
		
		var type = this.ruleWidget.fieldmaps[fieldnum].type;
		var criteriaSelectbox = this.make_radioboxes(this.ruleWidget.operators[type]);
		section.update(criteriaSelectbox);
				
		// Invoke onclick for each radiobox.
		criteriaSelectbox.select('input').invoke('observe', 'click', function(event) {
			var fieldmap = this.get_selected_fieldmap();
			if (fieldmap.type != 'multisearch' || !this.valueTD.down('input')) {
				this.show_value_column(fieldmap.fieldnum);
			}
			this.trigger_event_in_column(null, this.valueTD);
		}.bindAsEventListener(this));
		this.criteriaTD.down('span').show().observe('click', this.trigger_event_in_column.bindAsEventListener(this, this.criteriaTD));
	},
	
	// Determines the appropriate input boxes to show, makes an ajax request for persondatavalues if necessary for multisearch.
	show_value_column: function(fieldnum) {
		var section = this.valueTD.down('fieldset').down('div');
		if (!fieldnum) {
			section.update();
			this.show_action_column(true);
			this.valueTD.down('span').stopObserving('click').hide();
			return false;
		}
		
		var type = this.ruleWidget.fieldmaps[fieldnum].type;
		var op = this.criteriaTD.down('input:checked');
		if (!op)
			return;
		op = op.getValue();
				
		var container = new Element('div');
		switch(type) {
			case 'multisearch':
				container.update('<img src="img/icons/loading.gif"/>');
				if (this.ruleWidget.multisearchHTMLCache[fieldnum]) {
					var multicheckboxHTML = this.ruleWidget.multisearchHTMLCache[fieldnum];
					container.update(multicheckboxHTML);
					this.add_multicheckbox_toolbar(container);
					var boxes = container.select('input');
					if (boxes.length == 1) {
						boxes[0].checked = true; // Does not work in Internet Explorer.
						boxes[0].setAttribute('defaultChecked', true); // Workaround for Internet Explorer.
					} else {
						boxes.each(function(checkbox) {
							checkbox.checked = false;
						});
					}
				} else {
					cachedAjaxGet('ajax.php?type=persondatavalues&fieldnum=' + fieldnum,
						function(transport, fieldnum) {
							var section = this.valueTD.down('fieldset').down('div');
							var data = transport.responseJSON;
							if (!data) {
								container.update('<?=addslashes(_L("No data found"))?>');
							}
														//console.info(data);
							var multicheckboxHTML = this.make_multicheckbox(data,false,true,true);
							this.ruleWidget.multisearchHTMLCache[fieldnum] = multicheckboxHTML;
							container = new Element('div').update(multicheckboxHTML);
							section.update(this.add_multicheckbox_toolbar(container));
							// TODO: optimize input[type="checkbox"] to first and last element?
							var boxes = container.select('input');
							boxes.each(function(input) {
								input.observe('focus', this.trigger_event_in_column.bindAsEventListener(this, this.valueTD));
							}.bind(this));
							if (boxes.length == 1) {
								boxes[0].checked = true;
							}
						}.bindAsEventListener(this, fieldnum)
					);
				}
				break;
				
			case 'numeric':
				container.update(this.make_textbox(''));
				if (op == 'num_range') {
					container.insert(' <?=addslashes(_L('and'))?> ');
					container.insert(this.make_textbox(''));
				}
				break;
				
			case 'reldate':
				if (op == 'reldate') {
					var selectbox = this.make_radioboxes(this.ruleWidget.reldateOptions);
					container.update(selectbox);
				} else if (op == 'eq' || op == 'date_range') {
					container.update(this.make_datebox(''));
					if (op == 'date_range') {
						container.insert(' <?=addslashes(_L('and'))?> ');
						container.insert(this.make_datebox(''));
					}
				} else if (op == 'date_offset' || op == 'reldate_range') {
					container.update(this.make_textbox(''));
					if (op == 'reldate_range') {
						container.insert(' <?=addslashes(_L('and'))?> ');
						container.insert(this.make_textbox(''));
					}
				}
				break;
				
			case 'text':
				container.update(this.make_textbox(''));
				break;
		}
			  
		// TODO: optimize input[type="checkbox"] to first and last element?
		container.select('input').each(function(input) {
			input.observe('focus', this.trigger_event_in_column.bindAsEventListener(this, this.valueTD));
		}.bind(this));
		container.select('select').each(function(input) {
			input.observe('focus', this.trigger_event_in_column.bindAsEventListener(this, this.valueTD));
		}.bind(this));
		
		section.update(container);

		this.valueTD.down('span').show().observe('click', this.trigger_event_in_column.bindAsEventListener(this, this.valueTD));
		this.show_action_column();
	},
	
	show_action_column: function(clear) {
		if (clear) {
			this.actionTD.down('fieldset').down('div').update();
			this.actionTD.down('span').stopObserving('click').hide();
			return;
		}
			
		this.actionTD.down('fieldset').down('div').update('<?=addslashes(icon_button(_L('Add'), 'add'))?>').insert('<br style="clear:both"/>');
		var addRuleButton = this.actionTD.down('button');
		
		// Events
		addRuleButton.observe('click', function(event) {
				this.ruleWidget.insert_rule(this.get_data());
		}.bindAsEventListener(this));
		addRuleButton.observe('focus', this.trigger_event_in_column.bindAsEventListener(this, this.actionTD));
		this.actionTD.down('span').show().observe('click', this.trigger_event_in_column.bindAsEventListener(this, this.actionTD));
	},
		
	reset: function() {
		if (!this.ruleWidget.fieldmaps)
			return;
		var fieldSelectbox = new Element('select');
		
		fieldSelectbox.update(new Element('option', {'value':''}).insert('--<?=addslashes(_L('Choose a Field'))?>--'));
		for (var fieldnum in this.ruleWidget.fieldmaps) {
			// Don't allow adding the same rule twice.
			if (this.ruleWidget.appliedRules[fieldnum])
				continue;
			// Different CSS classes for F,G,C fields.
			// TODO: Reorder f,g,c fields. Currently ordered c,f,g due to ajax.php api call
			var fgcClass = 'FField';
			if (fieldnum[0] === 'g')
				fgcClass = 'GField';
			else if (fieldnum[0] === 'c')
				fgcClass = 'CField';
			fieldSelectbox.insert(new Element('option', {'value':fieldnum, 'class':fgcClass}).insert(this.ruleWidget.fieldmaps[fieldnum].name));
		}
		fieldSelectbox.disabled = fieldSelectbox.options.length < 2;
		fieldSelectbox.observe('change', function(event) {
			var fieldnum = this.fieldTD.down('select').getValue();
			this.show_criteria_column(fieldnum);
			this.show_value_column(null);
			this.show_action_column(true);
			if (fieldnum !== '')
				this.trigger_event_in_column(null,this.criteriaTD);
			else
				this.ruleWidget.container.fire('RuleWidget:ChangeField', {'fieldnum':''});
		}.bindAsEventListener(this));
		
		this.fieldTD.down('fieldset').down('div').update(fieldSelectbox);
		this.criteriaTD.down('fieldset').down('div').update();
		this.valueTD.down('fieldset').down('div').update();
		this.actionTD.down('fieldset').down('div').update();
		
		this.criteriaTD.down('span').stopObserving('click').hide();
		this.valueTD.down('span').stopObserving('click').hide();
		this.actionTD.down('span').stopObserving('click').hide();
		this.trigger_event_in_column(null,this.fieldTD);
	},
	
	// Adds a toolbar only if the number of checkboxes exceeds threshold
	add_multicheckbox_toolbar: function(multicheckboxContainer, threshold) {
		if (!threshold)
			threshold = 10;
		var length = multicheckboxContainer.select('input').length;
		// If necessary, add CheckAll and Clear, and limit height
		if (length > threshold) {
			var checkAll = new Element('a', {'href':'#', 'style':'float:left; white-space: nowrap;'}).insert('<?=addslashes(_L('Check All'))?>');
			checkAll.observe('click', function(event) {
				event.stop();
				var checkboxes = this.select('input');
				for (var i = 0; i < checkboxes.length; ++i) {
					checkboxes[i].checked = true;
				}
			}.bindAsEventListener(multicheckboxContainer));
			var clear = new Element('a', {'href':'#', 'style':'float:right; white-space: nowrap;'}).insert('<?=addslashes(_L('Clear'))?>');
			clear.observe('click', function(event) {
				event.stop();
				var checkboxes = this.select('input');
				for (var i = 0; i < checkboxes.length; ++i) {
					checkboxes[i].checked = false;
				}
			}.bindAsEventListener(multicheckboxContainer));
			multicheckboxContainer.down('div').insert({top:new Element('div').insert(checkAll).insert(clear).insert('<div style="width:130px;height:1px;clear:both"></div>')});
			multicheckboxContainer.down('ul').style.height = '300px';
		}
		return multicheckboxContainer;
	},

	// NOTE: If you want add a toolbar, do add_multicheckbox_toolbar(new Element('div').update(make_multicheckbox()));
	// @param paired, if true, values[i] = {text:"Item i", value:"234", checked:true, onclick:callback, onhover:callback}
	// @param returnHTML, returns as inline HTML, which means implies also that values[i].onclick is ignored.
	make_multicheckbox: function(values, paired, returnHTML, fixedSize) {
		multicheckbox = new Element('div', {'style': 'border: solid 1px gray; background: white; overflow:hidden;'});
		if (!values || !values.join)
			values = [''];

		// TODO: Determine if it's faster to insert as html or use DOM methods.
		// NOTE: So far it looks like DOM is faster, because Internet Explorer 6 seems to get very slow when concatenating long string in javascript.
		var ul = new Element('ul', {'style': 'clear:both; margin:0; padding:0; list-style:none; overflow:auto; ' + (fixedSize ? 'width: 180px;' : '')});
		// TODO: max is temporary hack to stop browser from consuming too much memory! It needs to be removed when in production
		//var max = (values.length > 100) ? 100 : values.length;
		var max = values.length;
		for (var i = 0; i < max; ++i) {
			var data = values[i];
			var text = paired ? data.text: data;
			var value = paired ? data.value: data;
			var checkbox = new Element('input', {'type':'checkbox', 'value':value, 'style':'font-size:90%'});
			if (max == 1) {
				checkbox = new Element('input', {'type':'radio', 'value':value, 'style':'font-size:90%'});
				checkbox.checked = true;
			}
			var label = new Element('label', {'style':'margin:0;padding:1px; font-size:90%;', 'for':checkbox.identify()}).update(text.escapeHTML());
			var li = new Element('li', {'style':'white-space:nowrap; font-size:90%; margin:0;margin:1px;overflow: hidden; vertical-align:middle'}).insert(checkbox).insert(label);
			if (paired && !returnHTML) {
				if (data.checked)
					checkbox.checked = true;
				if (data.onclick)
					checkbox.observe('click', data.onclick.bindAsEventListener(checkbox, value));
				if (data.onhover)
					label.observe('mouseover', data.onhover.bindAsEventListener(label, value));
			}
			ul.insert(li);
		}
		multicheckbox.insert(ul);
		
		if (returnHTML)
			return new Element('div').update(multicheckbox).innerHTML;
		else
			return multicheckbox;
	},

	make_selectbox: function(values, hidden) {
		var selectbox = new Element('select', {'style':'font-size:90%'});
		for (var i in values) {
			selectbox.insert(new Element('option', {'value':i.escapeHTML(), 'style':'font-size:90%'}).update(values[i].escapeHTML()));
		}
		if (hidden)
			selectbox.hide();
		return selectbox;
	},
		
	make_radioboxes: function(values, hidden) {
		var radioboxDIV = new Element('div');
		for (var i in values) {
			var radio = new Element('input', {'type':'radio', 'name':radioboxDIV.identify(), 'value':i.escapeHTML()});
			var label = new Element('label', {'style':'font-size:90%', 'for':radio.identify()}).update(values[i].escapeHTML());
			radioboxDIV.insert(new Element('div', {'style':'white-space:nowrap'}).insert(radio).insert(label));
		}
		if (hidden)
			radioboxDIV.hide();
		return radioboxDIV;
	},
	
	make_datebox: function(value, hidden) {
		if (!value)
			value = '';
		var datebox = new Element('input', {'type':'text', 'style':'font-size:90%', 'value':value.escapeHTML()});
		datebox.observe('focus', function(event) {
			event.stop();
			this.select();
			
			new DatePicker({
				relative:this.identify(),
				keepFieldEmpty:true,
				enableCloseOnBlur:0,
				topOffset:0
			});
		}.bindAsEventListener(datebox));
		if (hidden)
			datebox.hide();
		return datebox;
	},

	make_textbox: function(value, hidden) {
		if (!value)
			value = '';
		var textbox = new Element('input', {'type':'text', 'value':value.escapeHTML()});
		if (hidden)
			textbox.hide();
		return textbox;
	}
});

