var RuleWidget = Class.create({
	// PUBLIC FUNCTION
	// @param div, the DOM container for this widget.
	// @param jsonValidFields, valid fields that the user can choose.
	initialize: function(div, data) {
		// CONSTANTS
		this.actionColumn = 4;	
	
		this.div = div;
		
		// Toolbar.
		this.toolbar = new Element('div');
		this.div.insert(this.toolbar);
		
		// Field-select.
		this.fieldSelect = new Element('select');
		this.toolbar.insert(this.fieldSelect);
		this.fieldSelect.observe('change', this.handle_event_add_rule.bindAsEventListener(this));
		
		// RULES TABLE
		this.rulesTable = new Element('tbody');
		this.div.insert(new Element('table').insert(this.rulesTable));

		this.reset(data);
	},
	
	reset: function(data) {
		alert("reseting");
		this.operators = data['operators'];
		this.reldateOptions = data['reldateOptions'];
		this.fieldmaps = {};
		
		// Add "is not" to the multisearch operators.
		// TODO: what is the correct key for 'Is NOT'?
		this.operators['multisearch']['not'] = 'is NOT';
		this.operators['multisearch']['in'] = 'is';
		//console.info(this.operators);
		
		// Check if there are any unsearchable fieldmaps.
		// Also data['fieldmaps'] is indexed by a number, we prefer indexing by fieldnum.
		for (var i in data['fieldmaps']) {
			var fieldnum = data['fieldmaps'][i]['fieldnum'];
			if (data['fieldmaps'][i]['options'].match('searchable'))
				this.fieldmaps[fieldnum] = data['fieldmaps'][i];
		}
		
		//console.info(this.fieldmaps);
		
		// Keep track of the value column of each table row, indexed by fieldnum.
		// Also keep track of the operator choices for each fieldnum.
		// Also keep a cache of list values so we don't have to use ajax each time someone changes the operator on a multisearch field.
		this.clear_rules();
		
		this.refresh();
	},
	
	// PRIVATE FUNCTION
	clear_rules: function() {
		this.valueTD = {};
		this.operatorChoice = {};
		this.multisearchCache = {};
		this.rulesTable.update();
	},
	
	// PUBLIC FUNCTION
	// Returns json-encoded string of rules that the user chose.
	toJSON: function() {
		return 'OK will do this later..';
	},
	
	// PRIVATE FUNCTION
	// Determines the appropriate rule to insert into the DOM based on this.fieldSelect.selectedIndex.
	handle_event_add_rule: function() {
		var selectedFieldnum = this.fieldSelect.getValue();
		
		for (var fieldnum in this.fieldmaps) {
			if (fieldnum == selectedFieldnum) {
				this.insert_rule(fieldnum);
			}
		}
		
		this.refresh();
	},
	
	// PRIVATE FUNCTION
	// TODO
	handle_event_delete_rule: function(event) {
		var button = Event.element(event);
		
		var actionFieldnum = button.up('tr').select('td')[this.actionColumn-1].down('input[type="hidden"]').getValue();
		
		// Important: Get the value of actionFieldnum before deleting the table row.
		var tr = button.up('tr');
		tr.remove();
		
		for (var fieldnum in this.fieldmaps) {
			if (fieldnum == actionFieldnum) {
				delete this.operatorChoice[fieldnum];
				delete this.valueTD[fieldnum];
			}
		}
		
		this.refresh();
	},
	
	// PRIVATE FUNCTION
	handle_ajax_multisearch_values: function(transport, fieldnum) {
		var optionsHTML = transport.responseText;
					
		if (!optionsHTML) {
			alert('You are not logged in!');
			return;
		}
		
		this.valueTD[fieldnum].update(optionsHTML);
		
		// save to cache.
		this.multisearchCache[fieldnum] = optionsHTML;
	},
	
	// PRIVATE FUNCTION
	handle_event_change_operator: function(event) {
		var selectBox = Event.element(event);
		if (selectBox) {
			var fieldnum = selectBox.up('tr').select('td')[this.actionColumn-1].down('input[type="hidden"]').getValue();
			this.show_value_column(fieldnum);
		}
	},
	
	// PRIVATE FUNCTION
	handle_save_list: function() {
		var data = [];
		
		for (var fieldnum in this.valueTD) {
			var logical = 'and';
			var op = this.operatorChoice[fieldnum].getValue();
			if (op == 'not') {
				logical = 'and not';
				op = 'in';
			}
			var val = '';
			
			// MULTISEARCH
			var selectBox = this.valueTD[fieldnum].down('select');
			if (selectBox) {
				val = selectBox.getValue();
				if (val.join)
					val = val.join('|');
			} else {
				var inputs = this.valueTD[fieldnum].select('input');
				var values = [];
				for (var i = 0; i < inputs.length; i++)
					values.push(inputs[i].getValue());
				val = values.join('|');
			}
			
			data.push({'fieldnum':fieldnum, 'logical':logical, 'op':op, 'val':val});
		}
		
		var getlistid='';
		if (this.listid)
			getlistid = '&listid=' + this.listid;
		new Ajax.Request('ajax.php?ajax&type=listsubmit'+getlistid, {'method':'post',
			'postBody': 'ruledata='+data.toJSON()
		});
	},
	
	// PRIVATE FUNCTION
	transition_list_values: function(event, fieldnum, selectBox) {
		this.valueTD[fieldnum].update(selectBox);
	},
	
	// PRIVATE FUNCTION
	// Inserts a rule into the DOM.
	// @param i, index for this.fieldmaps. Example: this.fieldmaps[fieldnum]
	insert_rule: function(fieldnum) {
		// Don't add the same rule twice.
		if (this.valueTD[fieldnum])
			return;
		
		var tr = new Element('tr');
		this.rulesTable.insert({bottom:tr});
		
		// FieldTD
		var fieldTD = new Element('td', {'class':'FieldTD'});
		tr.insert(fieldTD);
		fieldTD.insert(this.fieldmaps[fieldnum]['name']);
		
		// OperatorTD
		var operatorTD = new Element('td', {'class':'OperatorTD'});
		tr.insert(operatorTD);
		for (var type in this.operators) {
			if (this.fieldmaps[fieldnum]['options'].match(type)) {
				this.fieldmaps[fieldnum]['type'] = type;
				this.operatorChoice[fieldnum] = util_selectbox(this.operators[type]);
				// Don't bother handling onchange if type is "text" or "multisearch"
				if (type != 'text' && type != 'multisearch')
					this.operatorChoice[fieldnum].observe('change', this.handle_event_change_operator.bindAsEventListener(this));
				operatorTD.insert(this.operatorChoice[fieldnum]);
			}
		}
		
		// ValuesTD
		var valueTD = new Element('td', {'class':'ValueTD'});
		tr.insert(valueTD);
		this.valueTD[fieldnum] = valueTD;
		this.show_value_column(fieldnum);
		
		// ActionTD
		var actionTD = new Element('td', {'class':'ActionTD'});
		tr.insert(actionTD);
		actionTD.insert(new Element('input', {'type':'hidden', 'value':fieldnum}));
		var deleteButton = new Element('button', {'type':'button'});
		actionTD.insert(deleteButton);
		deleteButton.observe('click', this.handle_event_delete_rule.bindAsEventListener(this));
		deleteButton.insert('Delete');
	},
	
	
	// PRIVATE FUNCTION
	show_value_column: function(fieldnum) {
		var operator = this.operatorChoice[fieldnum].getValue();
		
		var currentValue = '';
		var currentInput = this.valueTD[fieldnum].down('input[type="text"]');
		if (currentInput)
			currentValue = currentInput.getValue();
		
		// Final values.
		var value1 = currentValue;
		var value2 = currentValue;
		
		// Clear the value-column.
		this.valueTD[fieldnum].update();
		
		// MULTISEARCH
		if (this.fieldmaps[fieldnum]['type'] == 'multisearch') {
			this.valueTD[fieldnum].update('Loading..');
			if (this.multisearchCache[fieldnum]) {
				this.valueTD[fieldnum].update(this.multisearchCache[fieldnum]);
			} else {
				new Ajax.Request('ajax.php?ajax&type=listvalues&fieldnum=' + fieldnum, {
					onSuccess: this.handle_ajax_multisearch_values.bindAsEventListener(this, fieldnum)
				});
			}
		// NUMERIC
		} else if (this.fieldmaps[fieldnum]['type'] == 'numeric') {
			switch(operator) {
				case 'num_eq':
				case 'num_ge':
				case 'num_gt':
				case 'num_le':
				case 'num_lt':
				case 'num_ne':
					this.valueTD[fieldnum].update(util_textbox([value1]));
					break;
					
				case 'num_range':
					this.valueTD[fieldnum].update(util_textbox([value1,value2]));
					break;
			}
		// DATE
		} else if (this.fieldmaps[fieldnum]['type'] == 'reldate') {
			switch(operator) {
				case 'date_offset':
					this.valueTD[fieldnum].update(util_textbox([value1]));
					break;
					
				case 'date_range':
					this.valueTD[fieldnum].update(util_datebox([value1,value2]));
					break;
					
				case 'eq':
					this.valueTD[fieldnum].update(util_datebox([value1]));
					break;
					
				case 'reldate':
					this.valueTD[fieldnum].update(util_selectbox(this.reldateOptions));
					var valueOption = this.valueTD[fieldnum].down('option[value="'+value1+'"]');
					if (valueOption)
						valueOption.selected = true;
					break;
					
				case 'reldate_range':
					this.valueTD[fieldnum].update(util_textbox([value1,value2]));
					break;
			}
		// TEXT
		} else if (this.fieldmaps[fieldnum]['type'] == 'text') {
			this.valueTD[fieldnum].update(util_textbox([value1]));
		}
	},
	
	// PRIVATE FUNCTION
	// Refreshes the options available in fieldSelect.
	refresh: function() {
		this.toolbar.update();
		
		// Field-select.
		this.fieldSelect = new Element('select');
		this.toolbar.insert(this.fieldSelect);
		this.fieldSelect.observe('change', this.handle_event_add_rule.bindAsEventListener(this));
		
		this.fieldSelect.update(new Element('option', {'value':''}).insert('--Select a Field--'));
		
		for (var fieldnum in this.fieldmaps) {
			// Don't allow adding the same rule twice.
			if (this.valueTD[fieldnum])
				continue;
			
			// Different background colors for F,G,C fields.
			var fgcClass = 'FField';
			if (fieldnum.match('^g'))
				fgcClass = 'GField';
			else if (fieldnum.match('^c'))
				fgcClass = 'CField';
			this.fieldSelect.insert(new Element('option', {'value':fieldnum, 'class':fgcClass}).insert(this.fieldmaps[fieldnum]['name']));
		}
		
		if (this.fieldSelect.options.length < 2) {
			this.fieldSelect.disabled = true;
			//this.addRuleButton.disabled = true;
		} else {
			this.fieldSelect.disabled = false;
			//this.addRuleButton.disabled = false;
		}
		
		var clearRules = new Element('clearRules', {'type':'button'}).update('Clear Rules');
		this.toolbar.insert(clearRules);
		
		var saveButton = new Element('button', {'type':'button'}).update('Save This List');
		this.toolbar.insert(saveButton);
		saveButton.observe('click', this.handle_save_list.bindAsEventListener(this));
	}
});

function util_selectbox(values, multiple, selected) {
	var selectBox = new Element('select');
	if (multiple)
		selectBox.setAttribute('multiple', 'true');
		
	if (values.length) {
		for (var i = 0; i < values.length; i++) {
			var option = new Element('option', {'value':values[i]});
			option.insert(values[i]);
			selectBox.insert(option);
		}
	} else {
		for (var i in values) {
			var option = new Element('option', {'value':i});
			option.insert(values[i]);
			selectBox.insert(option);
		}
	}
	
	return selectBox;
}

function util_datebox(values) {
	if (!values)
		values = [''];
		
	var div = new Element('span');
	for (var i = 0; i < values.length; i++) {
		if (i > 0)
			div.insert(' and ');
		var dateBox = new Element('input', {'type':'text', 'class':'DateBox', 'value':values[i]});
		div.insert(dateBox);
	}
	return div;
}

function util_textbox(values) {
	if (!values)
		values = [''];
		
	var div = new Element('span');
	for (var i = 0; i < values.length; i++) {
		if (i > 0)
			div.insert(' and ');
			
		var dateBox = new Element('input', {'type':'text', 'value':values[i]});
		div.insert(dateBox);
	}
	return div;
}
