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
//ruleWidget.startup(); // Required, must be called AFTER registering ruleWidget.container.observe('RuleWidget:Ready',..)

var RuleWidget = Class.create({
	//----------------------------- PUBLIC FUNCTIONS --------------------------

	// @param container, the DOM container for this widget.
	initialize: function(container, readonly) {
		this.container = container;
		this.rulesTableLastTR = new Element('tr'); // For customization.
		this.rulesTableFootTR = new Element('tr'); // For customization, on top of rulesTableFootLastTR.
		this.rulesTableFootLastTR = new Element('tr'); // For rule editor
		this.rulesTableBody = new Element('tbody');
		//var thead = new Element('thead').insert('<tr><th style="overflow:hidden" width="25%" class="windowRowHeader"><?=addslashes(_L('Field'))?></th><th style="overflow:hidden" width="25%" class="windowRowHeader"><?=addslashes(_L('Criteria'))?></th><th style="overflow:hidden" width="25%" class="windowRowHeader"><?=addslashes(_L('Value'))?></th><th style="overflow:hidden" width="25%" class="windowRowHeader"><?=addslashes(_L('Actions'))?></th></tr>');
		this.container.insert(new Element('table', {}).insert(this.rulesTableBody).insert(new Element('tfoot').insert(this.rulesTableFootTR).insert(this.rulesTableFootLastTR)));
		if (!readonly)
			this.ruleEditor = new RuleEditor(this, this.rulesTableFootLastTR);
		this.clear_rules();
		
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
				if (rules) {
					for (var i = 0; i < rules.length; ++i) {
						if (!rules[i].fieldnum)
							break; // Bad data.

						if (rules[i].fieldnum && !this.fieldmaps[rules[i].fieldnum]) {
							alert('no stuff for ');
							continue;
							// TODO: Inform that this field is unauthorized.
						}
						this.insert_rule(rules[i]);
					}
				}
				this.container.fire('RuleWidget:Ready');
			}.bindAsEventListener(this, preloadedRules)
		);
	},
	
	clear_rules: function() {
		this.appliedRules = {};
		this.rulesTableBody.update(this.rulesTableLastTR);
		if (this.ruleEditor)
			this.ruleEditor.reset();
	},
	
	// Updates contents of tr with human-readable fieldmapTD, criteriaTD, and valueTD.
	// @param data, {fieldnum, type, logical, op, val}
	// @param tr, table row DOM element.
	// @param addHiddenFieldnum, optional boolean specifying to add a hidden input with value=fieldnum
	format_readable_rule: function(data, tr, addHiddenFieldnum) {
		if (!data.fieldnum || !data.op || !data.logical || !data.val)
			return false;
		if (!this.fieldmaps[data.fieldnum])
			return false;
		if (!data.type)
			data.type = this.fieldmaps[data.fieldnum].type;
		// FieldmapTD
		var fieldmapTD = new Element('td', {'class':'border', 'style':'overflow:hidden', 'width':'25%', 'valign':'top'}).insert(this.fieldmaps[data.fieldnum].name);
		// Keep track of the row's data.fieldnum by using a hidden input.
		if (addHiddenFieldnum)
			fieldmapTD.insert(new Element('input', {'type':'hidden', 'value':data.fieldnum}));
		// CriteriaTD
		var criteriaTD = new Element('td', {'class':'border', 'style':'overflow:hidden', 'width':'25%', 'valign':'top'});
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
				value = data.val.replace(/\|/g, ', ');
			} else if (data.op == 'reldate') {
				value = this.reldateOptions[data.val];
			} else {
				value = data.val.replace(/\|/g, ' <?=addslashes(_L('and'))?> ');
			}
		} else if (data.type == 'multisearch') {
			value = data.val.join(', ');
		} else {
			value = data.val.join(' <?=addslashes(_L('and'))?> ');
		}
		var valueTD = new Element('td', {'class':'border', 'style':'overflow:hidden', 'width':'25%', 'valign':'top'}).update(value.escapeHTML() + '&nbsp;');
		tr.insert(fieldmapTD).insert(criteriaTD).insert(valueTD);
		
		return true;
	},
	
	// @param data, {fieldnum, type, logical, op, val}
	insert_rule: function(data) {
		if (!data) {
			alert('<?=addslashes(_L('Please specify a value'))?>');
			return;
		}
		var tr = new Element('tr');
		if (!this.format_readable_rule(data, tr, true)) {
			alert('<?=addslashes(_L('cannot add this rule'))?>');
			return;
		}
		// Actions
		if (this.ruleEditor) {
			var actionTD = new Element('td', {'class':'border', 'style':'overflow:hidden', 'width':'25%', 'valign':'top'}).update('<?=addslashes(icon_button(_L('Delete This Rule'), 'cross'))?>').insert('<br style=\"clear:both\"/>');
			var deleteRuleButton = actionTD.down('button');
			tr.insert(actionTD);
			deleteRuleButton.observe('click', function(event, tr, fieldnum) {
				event.stop();
				tr.remove();
				delete this.appliedRules[fieldnum];
				if (this.ruleEditor)
					this.ruleEditor.reset();
				this.container.fire('RuleWidget:DeleteRule', {'fieldnum':fieldnum});
			}.bindAsEventListener(this, tr, data.fieldnum));
		}
		this.rulesTableLastTR.insert({before:tr});
		this.appliedRules[data.fieldnum] = data;
		if (this.ruleEditor)
			this.ruleEditor.reset();
		this.container.fire('RuleWidget:AddRule', {'ruledata':$H(data)});
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
		
		var fieldsetCSS = 'padding:5px; margin:0;';
		
		this.fieldTD = new Element('td',{'width':'25%', 'style':'overflow:hidden', 'valign':'top'}).insert(new Element('fieldset', {style:fieldsetCSS}).insert(new Element('div')));
		this.criteriaTD = new Element('td',{'width':'25%', 'style':'overflow:hidden', 'valign':'top'}).insert(new Element('fieldset', {style:fieldsetCSS}).insert(new Element('div')));
		this.valueTD = new Element('td',{'width':'25%', 'style':'overflow:hidden', 'valign':'top'}).insert(new Element('fieldset', {style:fieldsetCSS}).insert(new Element('div')));
		this.actionTD = new Element('td',{'width':'25%', 'style':'overflow:hidden', 'valign':'top'}).insert(new Element('fieldset', {style:fieldsetCSS}).insert(new Element('div')));
		containerTR.insert(this.fieldTD).insert(this.criteriaTD).insert(this.valueTD).insert(this.actionTD);

		this.actionTD.down('fieldset').down('div').update('<?=addslashes(icon_button(_L('Add This Rule'), 'add'))?>').insert('<br style="clear:both"/>');
		var addRuleButton = this.actionTD.down('button');
		
		// Events
		addRuleButton.observe('click', function(event) {
			this.ruleWidget.insert_rule(this.get_data());
		}.bindAsEventListener(this));
		addRuleButton.observe('focus', this.trigger_event_in_column.bindAsEventListener(this, this.actionTD));

		this.fieldTD.observe('click', this.trigger_event_in_column.bindAsEventListener(this, this.fieldTD));
		this.criteriaTD.observe('click', this.trigger_event_in_column.bindAsEventListener(this, this.criteriaTD));
		this.valueTD.observe('click', this.trigger_event_in_column.bindAsEventListener(this, this.valueTD));
		this.actionTD.observe('click', this.trigger_event_in_column.bindAsEventListener(this, this.actionTD));
	},
	
	trigger_event_in_column: function(nullableEvent, td) {
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

	// Returns data for the rule, {fieldnum, type, logical, op, val}
	get_data: function() {
		var fieldnum = this.fieldTD.down('select').getValue();
		var logical = 'and';
		var selected = this.criteriaTD.down('select');
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
			var checkboxes = this.valueTD.select('input[type="checkbox"]');
			for (var i = 0; i < checkboxes.length; ++i) {
				if (checkboxes[i].checked)
					multisearchValues.push(checkboxes[i].getValue());
			}
			if (multisearchValues.length < 1)
				return false;
			val = multisearchValues;
		} else {
			// RELDATE_RELDATE
			if (this.valueTD.down('select')) {
				val = this.valueTD.down('select').getValue();
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

		return {'fieldnum':fieldnum,
			'type':this.ruleWidget.fieldmaps[fieldnum].type,
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
			return;
		}
			
		var type = this.ruleWidget.fieldmaps[fieldnum].type;
		var criteriaSelectbox = this.make_selectbox(this.ruleWidget.operators[type]);
		section.update(criteriaSelectbox);
		criteriaSelectbox.observe('change', function(event) {
			var fieldnum = this.fieldTD.down('select').getValue();
			var type = this.ruleWidget.fieldmaps[fieldnum].type;
			if (type != 'multisearch')
				this.show_value_column(fieldnum);
			this.ruleWidget.container.fire('RuleWidget:ChangeCriteria');
			this.trigger_event_in_column(this, this.criteriaTD);
		}.bindAsEventListener(this));
		criteriaSelectbox.observe('focus', this.trigger_event_in_column.bindAsEventListener(this, this.criteriaTD));
	},
	
	// Determines the appropriate input boxes to show, makes an ajax request for persondatavalues if necessary for multisearch.
	show_value_column: function(fieldnum) {
		var section = this.valueTD.down('fieldset').down('div');
		if (!fieldnum) {
			section.update();
			this.actionTD.down('button').hide();
			return false;
		}
		
		var type = this.ruleWidget.fieldmaps[fieldnum].type;
		var op = this.criteriaTD.down('select');
		if (!op)
			alert('<?=addslashes(_L('ASSERTION FAILED, op missing'))?>');
		op = op.getValue();
		
		var container = new Element('div');
		switch(type) {
			case 'multisearch':
				container.update('<img src="img/icons/loading.gif"/>Loading...');
				if (this.ruleWidget.multisearchHTMLCache[fieldnum]) {
					var multicheckboxHTML = this.ruleWidget.multisearchHTMLCache[fieldnum];
					container.update(multicheckboxHTML);
					this.add_multicheckbox_toolbar(container);
					container.select('input').each(function(checkbox) {
						checkbox.checked = false;
					});
				} else {
					cachedAjaxGet('ajax.php?type=persondatavalues&fieldnum=' + fieldnum,
						function(transport, fieldnum) {
							var section = this.valueTD.down('fieldset').down('div');
							var data = transport.responseJSON;
							var multicheckboxHTML = this.make_multicheckboxHTML(data);
							this.ruleWidget.multisearchHTMLCache[fieldnum] = multicheckboxHTML;
							container = new Element('div').update(multicheckboxHTML);
							section.update(this.add_multicheckbox_toolbar(container));
							// TODO: optimize input[type="checkbox"] to first and last element?
							container.select('input').each(function(input) {
								input.observe('focus', this.trigger_event_in_column.bindAsEventListener(this, this.valueTD));
							}.bind(this));
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
					var selectbox = this.make_selectbox(this.ruleWidget.reldateOptions);
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

		// Show actions column also.
		this.actionTD.down('button').show();
	},
	
	reset: function() {
		if (!this.ruleWidget.fieldmaps)
			return;
		var fieldSelectbox = new Element('select');
		fieldSelectbox.observe('change', function(event) {
			var fieldnum = this.fieldTD.down('select').getValue();
			this.show_criteria_column(fieldnum);
			this.show_value_column(fieldnum);
			this.ruleWidget.container.fire('RuleWidget:ChangeField', {'fieldnum':fieldnum});
			this.trigger_event_in_column(this, this.fieldTD);
		}.bindAsEventListener(this));
		fieldSelectbox.observe('focus', this.trigger_event_in_column.bindAsEventListener(this, this.fieldTD));
		
		fieldSelectbox.update(new Element('option', {'value':''}).insert('--<?=addslashes(_L('Choose a Field'))?>--'));
		for (var fieldnum in this.ruleWidget.fieldmaps) {
			// Don't allow adding the same rule twice.
			if (this.ruleWidget.appliedRules[fieldnum])
				continue;
			// Different CSS classes for F,G,C fields.
			// TODO: Reorder f,g,c fields. Currently ordered c,f,g due to ajax.php api call
			var fgcClass = 'FField';
			if (fieldnum.match('^g'))
				fgcClass = 'GField';
			else if (fieldnum.match('^c'))
				fgcClass = 'CField';
			fieldSelectbox.insert(new Element('option', {'value':fieldnum, 'class':fgcClass}).insert(this.ruleWidget.fieldmaps[fieldnum].name));
		}
		fieldSelectbox.disabled = fieldSelectbox.options.length < 2;
		
		this.fieldTD.down('fieldset').down('div').update(fieldSelectbox);
		this.criteriaTD.down('fieldset').down('div').update();
		this.valueTD.down('fieldset').down('div').update();
		this.actionTD.down('button').hide();
	},
	
	// Adds a toolbar only if the number of checkboxes exceeds threshold
	add_multicheckbox_toolbar: function(multicheckboxContainer, threshold) {
		if (!threshold)
			threshold = 10;
		var length = multicheckboxContainer.select('input[type="checkbox"]').length;
		// If necessary, add CheckAll and Clear, and limit height
		if (length > threshold) {
			var checkAll = new Element('a', {'href':'#', 'style':'float:left; white-space: nowrap;'}).insert('<?=addslashes(_L('Check All'))?>');
			checkAll.observe('click', function(event) {
				event.stop();
				var checkboxes = this.select('input[type="checkbox"]');
				for (var i = 0; i < checkboxes.length; ++i) {
					checkboxes[i].checked = true;
				}
			}.bindAsEventListener(multicheckboxContainer));
			var clear = new Element('a', {'href':'#', 'style':'float:right; white-space: nowrap;'}).insert('<?=addslashes(_L('Clear'))?>');
			clear.observe('click', function(event) {
				event.stop();
				var checkboxes = this.select('input[type="checkbox"]');
				for (var i = 0; i < checkboxes.length; ++i) {
					checkboxes[i].checked = false;
				}
			}.bindAsEventListener(multicheckboxContainer));
			multicheckboxContainer.down('div').insert({top:new Element('div').insert(checkAll).insert(clear).insert('<div style="width:130px;height:1px"></div><br style="clear:both"/>')});
			multicheckboxContainer.down('ul').style.height = '300px';
		}
		return multicheckboxContainer;
	},

	// NOTE: If you want add a toolbar, do add_multicheckbox_toolbar(new Element('div').update(make_multicheckboxHTML())); this function only returns HTML, not a real DOM object.
	make_multicheckboxHTML: function(values) {
		var tempContainer = new Element('div'); // Temporary container so that we can return tempContainer.innerHTML.
		
		multicheckbox = new Element('div', {'style':'border: solid 1px gray; background: white; overflow:hidden'});
		if (!values || !values.join)
			values = [''];

		// TODO: Determine if it's faster to insert as html or use DOM methods.
		// NOTE: So far it looks like DOM is faster, because Internet Explorer 6 seems to get very slow when concatenating long string in javascript.
		var ul = new Element('ul', {'style':'clear:both; margin:0; padding:0; list-style:none; overflow:auto;'});
		// TODO: max is temporary hack to stop browser from consuming too much memory! It needs to be removed when in production
		var max = (values.length > 100) ? 100 : values.length;
		for (var i = 0; i < max; ++i) {
			var checkbox = new Element('input', {'type':'checkbox', 'value':values[i]});
			var label = new Element('label', {'for':checkbox.identify()}).update(values[i].escapeHTML());
			ul.insert(new Element('li', {'style':'white-space:nowrap; overflow: hidden'}).insert(checkbox).insert(label));
		}
		multicheckbox.insert(ul);
		
		return tempContainer.update(multicheckbox).innerHTML;
	},

	make_selectbox: function(values, hidden) {
		var selectbox = new Element('select');
		for (var i in values) {
			selectbox.insert(new Element('option', {'value':i.escapeHTML()}).update(values[i].escapeHTML()));
		}
		if (hidden)
			selectbox.hide();
		return selectbox;
	},
	
	make_datebox: function(value, hidden) {
		if (!value)
			value = '';
		var datebox = new Element('input', {'type':'text', 'value':value.escapeHTML()});
		datebox.observe('focus', function(event) {
			event.stop();
			this.select();
			lcs(this,true,true);
		});
		// lcs() by default will disappear when you release the mouse button, so we need to override the click event.
		datebox.observe('click', function(event) {
			event.stop();
			this.select();
			lcs(this,true,true);
		});
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

