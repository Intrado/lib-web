var RuleWidget = Class.create({
	//----------------------------- PUBLIC FUNCTIONS --------------------------

	// @param div, the DOM container for this widget.
	initialize: function(div) {
		this.container = div;
		
		// RULES TABLE
		this.rulesTable = new Element('tbody');
		this.rulesTableLastTR = new Element('tr', {'class':'RulesTableLastTR'}); // For customization.
		this.container.insert(new Element('table', {'class':'RulesTable'}).insert(new Element('tbody').insert('<tr><th>Field</th><th>Criteria</th><th>Value</th><th></th></tr>')).insert(this.rulesTable).insert(new Element('tbody').insert(this.rulesTableLastTR)));
		
		this.newRuleDiv = new Element('div');
		this.newRuleEditor = new RuleEditor(this, this.newRuleDiv);
		this.container.insert({top:this.newRuleDiv});
		
		this.clear_rules();
	},
	
	clear_rules: function() {
		this.appliedRules = {};
		this.additionalRuleEditors = {};
		this.rulesTable.update();
	},
	
	// @param data, {fieldnum, type, logical, op, val}
	apply_rule: function(data) {
		var tr = new Element('tr');
		if (this.appliedRules[data.fieldnum])
			tr = this.rulesTable.down('input[value="'+data.fieldnum+'"]').up('tr').update();
		
		// FieldmapTD
		var fieldmapTD = new Element('td', {'class':'FieldmapTD'}).insert(RuleEditor.fieldmaps[data.fieldnum].name);
		// Keep track of the row's fieldnum by using a hidden input.
		fieldmapTD.insert(new Element('input', {'type':'hidden', 'value':data.fieldnum}));
		
		// CriteriaTD
		var criteriaTD = new Element('td', {'class':'CriteriaTD'});
		var criteria = RuleEditor.operators[data.type][data.op];
		if (data.op == 'in') {
			criteria = 'is';
			if (data.logical == 'and not')
				criteria = 'is NOT';
		}
		criteriaTD.insert(criteria);

		// ValueTD
		var value = '';
		if (data.type == 'multisearch') {
			value = data.val[0].join(', ');
		} else if (data.type != 'reldate') {
			if (data.op == 'num_range')
				value = data.val.join(' and ');
			else
				value = data.val[0];
		// RELDATE
		} else {
			switch(data.op) {
				case 'reldate':
					value = RuleEditor.reldateOptions[data.val[0]];
					break;
				
				case 'eq':
					value = data.val[1];
					break;
					
				case 'date_range':
					value = data.val[1] + ' and ' + data.val[2];
					break;
					
				case 'date_offset':
					value = data.val[3];
					break;
					
				case 'reldate_range':
					value = data.val[3] + ' and ' + data.val[4];
					break;
			}
		}
		if (value.length > 30)
			value = value.substring(0,30) + '...';
		var valueTD = new Element('td', {'class':'ValueTD'}).update(value.escapeHTML());

		// ActionTD
		var actionTD = new Element('td', {'class':'ActionTD'});
		var editButton = new Element('button', {'type':'button'}).update('Edit Rule');
		var deleteButton = new Element('button', {'type':'button'}).update('Delete Rule');
		editButton.observe('click', this.handle_event_edit_rule.bindAsEventListener(this));
		deleteButton.observe('click', this.handle_event_delete_rule.bindAsEventListener(this));
		actionTD.insert(editButton).insert(' or ').insert(deleteButton);

		tr.insert(fieldmapTD).insert(criteriaTD).insert(valueTD).insert(actionTD);
		
		if (this.additionalRuleEditors[data.fieldnum]) {
			delete this.additionalRuleEditors[data.fieldnum];
			tr.next('tr').remove();
		} else {
			this.rulesTable.insert({top:tr});
		}
		
		this.appliedRules[data.fieldnum] = data;
		this.newRuleEditor.refresh();
	},

	// Returns json for rules that the user chose.
	toJSON: function() {
		return $H(this.appliedRules).toJSON();
	},
	
	//----------------------------- PRIVATE FUNCTIONS --------------------------
	
	handle_event_edit_rule: function(event) {
		var fieldnum = event.element().up('tr').down('input[type="hidden"]').getValue();
		
		if (this.additionalRuleEditors[fieldnum])
			return;
			
		var div = new Element('td', {colspan:4});
		this.additionalRuleEditors[fieldnum] = new RuleEditor(this, div, this.appliedRules[fieldnum]);
		event.element().up('tr').insert({after:new Element('tr').update(div)});
	},
	
	handle_event_delete_rule: function(event) {
		// Important: Get the value of fieldnum before deleting the table row.
		var fieldnum = event.element().up('tr').down('input[type="hidden"]').getValue();
		if (this.additionalRuleEditors[fieldnum]) {
			delete this.additionalRuleEditors[fieldnum];
			event.element().up('tr').next('tr').remove();
		}
		event.element().up('tr').remove();
		delete this.appliedRules[fieldnum];
		this.newRuleEditor.refresh();
	}
});

// PRIVATE CLASS, used only by RuleWidget.
var RuleEditor = Class.create({
	// PRIVATE Static Class Variables.
	fieldmaps: null,
	operators: null,
	reldateOptions: null,
	multisearchDOMCache: null, // Cache of multisearch DOM, indexed by fieldnum.
	
	//----------------------------- PUBLIC FUNCTIONS --------------------------

	// @param ruleWidget, the parent RuleWidget.
	// @param div, the DOM container for this editor.
	// @param data, optional data to make this prepopulate this editor.
	initialize: function(ruleWidget, div, data) {
		this.ruleWidget = ruleWidget;
		this.container = div;
		if (data)
			this.data = data;
		
		// EDITOR
		this.editorTable = new Element('tbody');
		this.editorFieldTR = new Element('tr').insert('<td class="SectionTD">Field</td>').insert('<td class="InputTD"></td>').insert('<td class="HelpTD"></td>');
		this.editorCriteriaTR = new Element('tr').insert('<td class="SectionTD">Criteria</td>').insert('<td class="InputTD"></td>').insert('<td class="HelpTD"></td>');
		this.editorValueTR = new Element('tr').insert('<td class="SectionTD">Value</td>').insert('<td class="InputTD"></td>').insert('<td class="HelpTD"></td>');
		this.editorActionTR = new Element('tr').insert('<td class="SectionTD"></td>').insert('<td class="InputTD"></td>').insert('<td class="HelpTD"></td>');
		this.editorTable.insert(this.editorFieldTR).insert(this.editorCriteriaTR).insert(this.editorValueTR).insert(this.editorActionTR);
		this.container.insert(new Element('table', {'class':'RuleEditorTable'}).insert(this.editorTable));
		
		// ADD-RULE BUTTON
		var button = new Element('button', {'type':'button'}).update('Add This Rule');
		if (this.data)
			button.update('Apply Changes');
			
		button.observe('click', this.handle_event_apply_rule.bindAsEventListener(this));
		this.editorActionTR.down('td',1).update(button);
		
		if (!RuleEditor.fieldmaps) {
			new Ajax.Request('ajax.php?type=rulewidgetsettings', {
				onSuccess: this.handle_ajax_load_rulewidgetsettings.bindAsEventListener(this)
			});
		} else {
			this.refresh();
		}
	},
	
	// Returns data for the rule, {fieldnum, type, logical, op, val}
	get_data: function() {
		var fieldnum = this.get_fieldnum();
		var logical = 'and';
		
		var selected = this.editorCriteriaTR.select('input').find(function(radio) { return radio.checked; });
		if (!selected)
			return false;
		var op = selected.getValue();
		
		if (op == 'not') {
			logical = 'and not';
			op = 'in';
		}
		
		var val = [];
		// MULTISEARCH
		if (this.editorValueTR.down('ul')) {
			var multisearchValues = [];
			var checkboxes = this.editorValueTR.select('input[type="checkbox"]');
			for (var i = 0; i < checkboxes.length; i++) {
				if (checkboxes[i].checked)
					multisearchValues.push(checkboxes[i].getValue());
			}
			// Rule::initFrom() requires val[0] for multisearch.
			val.push(multisearchValues);
		} else {
			// RELDATE_RELDATE
			if (this.editorValueTR.down('select'))
				val.push(this.editorValueTR.down('select').getValue());
				
			// TEXT, NUMERIC, RELDATE_*
			var inputs = this.editorValueTR.select('input');
			for (var i = 0; i < inputs.length; i++) {
				val.push(inputs[i].getValue());
			}
		}

		return {'fieldnum':fieldnum,
			'type':RuleEditor.fieldmaps[fieldnum]['type'],
			'logical':logical,
			'op':op,
			'val':val};
	},
	
	//----------------------------- PRIVATE FUNCTIONS --------------------------
	
	handle_ajax_load_rulewidgetsettings: function(transport) {
		var data = transport.responseJSON;
		if (!data) {
			alert('Sorry cannot get fieldmaps');
			return;
		}
		
		RuleEditor.operators = data['operators'];
		RuleEditor.reldateOptions = data['reldateOptions'];
		RuleEditor.fieldmaps = {};
		// data['fieldmaps'] is indexed by record id, we prefer indexing by fieldnum.
		for (var i in data['fieldmaps']) {
			var fieldnum = data['fieldmaps'][i]['fieldnum'];
			RuleEditor.fieldmaps[fieldnum] = data['fieldmaps'][i];
			for (var type in RuleEditor.operators) {
				if (RuleEditor.fieldmaps[fieldnum]['options'].match(type))
					RuleEditor.fieldmaps[fieldnum]['type'] = type;
			}
		}
		
		// Add "is not" to the multisearch operators.
		RuleEditor.operators['multisearch']['not'] = 'is NOT';
		RuleEditor.operators['multisearch']['in'] = 'is';
		
		RuleEditor.multisearchDOMCache = {};

		this.refresh();
	},
	
	handle_ajax_load_multisearch_values: function(transport, fieldnum) {
		var data = transport.responseJSON;
					
		if (!data)
			data = ' '; // Show the table row anyway.
		
		var multicheckbox = this.make_multicheckbox(this.container.id + '_multisearch_' + fieldnum + '_', data);
		this.editorValueTR.down('td',1).update(multicheckbox);
		
		// cache in memory.
		RuleEditor.multisearchDOMCache[fieldnum] = multicheckbox;
	},
	
	handle_event_change_field: function() {
		this.show_criteria_column({'fieldnum':this.get_fieldnum()});
		this.show_value_column({'fieldnum':this.get_fieldnum()});
	},

	handle_event_change_criteria: function(event) {
		this.show_value_column({'fieldnum':this.get_fieldnum()});
	},
	
	handle_event_apply_rule: function(event) {
		var data = this.get_data();
		if (!data) {
			alert('Please choose a field');
			return;
		}
		this.ruleWidget.apply_rule(data);
	},
	
	show_criteria_column: function(data) {
		this.editorCriteriaTR.down('td',1).update();
		
		if (!data.fieldnum)
			return;
			
		var type = RuleEditor.fieldmaps[data.fieldnum]['type'];
		var radiobox = this.make_radiobox(this.container.id + '_criteria_' + data.fieldnum + '_', RuleEditor.operators[type]);
		if (data.op) {
			var operator = data.op;
			if (data.logical == 'and not')
				operator = 'not';
			radiobox.down('input[value="'+operator+'"]').checked = true;
		}
		else {
			// Default, select first radio box.
			radiobox.down('input').checked = true;
		}
		if (type != 'text' && type != 'multisearch') // Don't bother handling onchange if type is "text" or "multisearch"
			radiobox.select('input').invoke('observe', 'click', this.handle_event_change_criteria.bindAsEventListener(this));
		this.editorCriteriaTR.down('td',1).update(radiobox);
	},
	
	// Determines the appropriate input boxes to show, makes an ajax request for persondatavalues if necessary for multisearch.
	show_value_column: function(data) {
		this.editorValueTR.down('td',1).update();
		
		if (!data.fieldnum)
			return;
		var type = RuleEditor.fieldmaps[data.fieldnum]['type'];
		var operator = null;
		if (type != 'multisearch' && type != 'text') {
			var selected = this.editorCriteriaTR.select('input').find(function(radio) {
				return radio.checked;
			});
			if (!selected)
				return false;
			operator = selected.getValue();
		}
		
		// MULTISEARCH
		if (RuleEditor.fieldmaps[data.fieldnum]['type'] == 'multisearch') {
			this.editorValueTR.down('td',1).update('Loading..'); // TODO: Replace with an animated gif
			if (RuleEditor.multisearchDOMCache[data.fieldnum]) {
				this.editorValueTR.down('td',1).update(RuleEditor.multisearchDOMCache[data.fieldnum]);
				this.editorValueTR.down('td',1).select('input[type="checkbox"]').each(function(checkbox) {
					checkbox.checked = false;
					if (data.val && data.val[0].indexOf(checkbox.value) >= 0)
						checkbox.checked = true;
				});
			} else {
				new Ajax.Request('ajax.php?type=persondatavalues&fieldnum=' + data.fieldnum, {
					onSuccess: this.handle_ajax_load_multisearch_values.bindAsEventListener(this, data.fieldnum)
				});
			}
		// NUMERIC
		} else if (RuleEditor.fieldmaps[data.fieldnum]['type'] == 'numeric') {
			var current = this.editorValueTR.down('td',1).select('input[type="text"]');
			var value1 = (current && current[0]) ? current[0].getValue() : '';
			var value2 = (current && current[1]) ? current[1].getValue() : '';
			if (data.val)
				value1 = data.val[0];
			if (data.val && data.val[1])
				value2 = data.val[1];
				
			this.editorValueTR.down('td',1).update(this.make_textbox(value1));
			if (operator == 'num_range')
				this.editorValueTR.down('td',1).insert(' and ');
			this.editorValueTR.down('td',1).insert(this.make_textbox(value2, operator != 'num_range'));
		// DATE
		} else if (RuleEditor.fieldmaps[data.fieldnum]['type'] == 'reldate') {
			var current = this.editorValueTR.down('td',1).select('input[type="text"]');
			var value2 = (current && current[0]) ? current[0].getValue() : '';
			var value3 = (current && current[1]) ? current[1].getValue() : '';
			var value4 = (current && current[2]) ? current[2].getValue() : '';
			var value5 = (current && current[3]) ? current[3].getValue() : '';
			if (data.val && data.val[1])
				value2 = data.val[1];
			if (data.val && data.val[2])
				value3 = data.val[2];
			if (data.val && data.val[3])
				value4 = data.val[3];
			if (data.val && data.val[4])
				value5 = data.val[4];
			
			var selectbox = this.make_selectbox(RuleEditor.reldateOptions, operator != 'reldate');
			if (data.val)
				selectbox.value = data.val[0];
			this.editorValueTR.down('td',1).update(selectbox);
			this.editorValueTR.down('td',1).insert(this.make_datebox(value2, operator != 'eq' && operator != 'date_range'));
			if (operator == 'date_range')
				this.editorValueTR.down('td',1).insert(' and ');
			this.editorValueTR.down('td',1).insert(this.make_datebox(value3, operator != 'date_range'));
			this.editorValueTR.down('td',1).insert(this.make_textbox(value4, operator != 'date_offset' && operator != 'reldate_range'));
			if (operator == 'reldate_range')
				this.editorValueTR.down('td',1).insert(' and ');
			this.editorValueTR.down('td',1).insert(this.make_textbox(value5, operator != 'reldate_range'));
		// TEXT
		} else if (RuleEditor.fieldmaps[data.fieldnum]['type'] == 'text') {
			var current = this.editorValueTR.down('td',1).select('input[type="text"]');
			var value1 = (current && current[0]) ? current[0].getValue() : '';
			if (data.val)
				value1 = data.val[0];
			
			this.editorValueTR.down('td',1).update(this.make_textbox(value1));
		} else {
			// Clear the column anyway.
			this.editorValueTR.down('td',1).update();
		}
		
		//this.editorActionTR.down('button').disabled = false;
	},
	
	get_fieldnum: function() {
		if (this.data)
			return this.data.fieldnum;

		return this.editorFieldTR.down('select').getValue();
	},
	
	// Refreshes the available fields.
	refresh: function() {
		this.editorFieldTR.down('td',1).update();
		
		if (this.data) {
			this.editorFieldTR.down('td',1).update(RuleEditor.fieldmaps[this.data.fieldnum]['name']);
			this.show_criteria_column(this.data);
			this.show_value_column(this.data);
			
			return;
		}
		
		selectbox = new Element('select');
		selectbox.observe('change', this.handle_event_change_field.bindAsEventListener(this));
			
		selectbox.update(new Element('option', {'value':''}).insert('--Choose a Field--'));
		
		for (var fieldnum in RuleEditor.fieldmaps) {
			// Don't allow adding the same rule twice.
			if (this.ruleWidget.appliedRules[fieldnum])
				continue;
			
			// Different CSS classes for F,G,C fields.
			var fgcClass = 'FField';
			if (fieldnum.match('^g'))
				fgcClass = 'GField';
			else if (fieldnum.match('^c'))
				fgcClass = 'CField';
			selectbox.insert(new Element('option', {'value':fieldnum, 'class':fgcClass}).insert(RuleEditor.fieldmaps[fieldnum]['name']));
		}
		
		if (selectbox.options.length < 2)
			selectbox.disabled = true;
		else
			selectbox.disabled = false;
			
		this.editorFieldTR.down('td',1).insert(selectbox);
		this.editorCriteriaTR.down('td',1).update();
		this.editorValueTR.down('td',1).update();
		//this.editorActionTR.down('button').disabled = true;
	},

	make_multicheckbox: function(uniquePrefix, values, needCheckAll) {
		if (!needCheckAll)
			needCheckAll = 6;
		var heightCSS = '';
			
		div = new Element('div', {'style':'border: solid 1px blue;padding:2px'});
		
		if (values.length) {
			if (values.length >= needCheckAll) {
				var checkAllLink = new Element('a', {'href':'', 'style':'float:left'}).insert('Check All');
				checkAllLink.observe('click', function(event) {
					event.stop();
					var checkboxes = event.element().up('div').select('input[type="checkbox"]');
					for (var i = 0; i < checkboxes.length; i++)
						checkboxes[i].checked = true;
				});

				var clearLink = new Element('a', {'href':'', 'style':'float:right'}).insert('Clear');
				clearLink.observe('click', function(event) {
					event.stop();
					var checkboxes = event.element().up('div').select('input[type="checkbox"]');
					for (var i = 0; i < checkboxes.length; i++)
						checkboxes[i].checked = false;
				});
				
				div.insert(checkAllLink).insert(clearLink).insert('<br style="clear:both"/>');
				
				heightCSS = 'height:100px; ';
			}
			
			// TODO: Determine if it's faster to insert as html or use DOM methods.
			//var ul = new Element('ul', {'style':'clear:both; width: 200px; border-top:solid 1px lightgray; height:100px; margin:2px; padding:0;list-style:none; overflow:auto;'});
			var ul = '<ul style="' + heightCSS + 'margin:2px; padding:0;list-style:none; overflow:auto;">';
			for (var i = 0; i < values.length; i++) {
				//var checkbox = new Element('input', {'type':'checkbox', 'value':values[i].escapeHTML(), 'id':uniquePrefix + i});
				//var label = new Element('label', {'for':uniquePrefix + i}).update(values[i].escapeHTML());
				//ul.insert(new Element('li', {'style':'white-space:nowrap'}).insert(checkbox).insert(label));
				
				var checkbox = '<input type="checkbox" value="'+values[i].escapeHTML()+'" id="'+uniquePrefix+i+'"/>';
				var label = '<label for="'+uniquePrefix+i+'">'+values[i].escapeHTML()+'</label>';
				ul += ('<li style="white-space:nowrap">' + checkbox + label + '</li>');
			}
			
			div.insert(ul+'</ul>');
		}
		return div;
	},

	make_selectbox: function(values, hidden) {
		var selectbox = new Element('select');
			
		for (var i in values) {
			var option = new Element('option', {'value':i.escapeHTML()});
			option.insert(values[i].escapeHTML());
			selectbox.insert(option);
		}
		
		if (hidden)
			selectbox.hide();
		return selectbox;
	},
	
	make_radiobox: function(uniquePrefix, values, hidden) {
		var container = new Element('ul', {'style':'margin:0;padding:0;list-style:none'});
			
		for (var i in values) {
			var radio = new Element('input', {'type':'radio', 'name':uniquePrefix, 'id':uniquePrefix+i, 'value':i.escapeHTML()});
			var label = '<label for="'+uniquePrefix+i+'">'+values[i].escapeHTML()+'</label>';
			container.insert(new Element('li').insert(radio).insert(label));
		}
		
		if (hidden)
			container.hide();
		return container;
	},

	make_datebox: function(value, hidden) {
		if (!value)
			value = '';
		var datebox = new Element('input', {'type':'text', 'class':'Datebox', 'value':value.escapeHTML()});

		datebox.observe('focus', function(event) {
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