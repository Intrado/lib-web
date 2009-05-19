var RuleWidget = Class.create({
	// PUBLIC FUNCTION
	// @param div, the DOM container for this widget.
	// @param jsonValidFields, valid fields that the user can choose.
	initialize: function(div) {
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

		new Ajax.Request('ajax.php?ajax&type=fieldmapsdata', {
			onSuccess: this.handle_ajax_reset.bindAsEventListener(this)
		});
	},
	
	// PUBLIC FUNCTION
	clear_rules: function() {
		// Keep track of the value column of each table row, indexed by fieldnum.
		// Also keep track of the operator choices for each fieldnum.
		this.valueTD = {};
		this.operatorSelect = {};
		this.rulesTable.update();
		
		this.refresh();
	},
	
	// PUBLIC FUNCTION
	// Returns json-encoded string of rules that the user chose.
	toJSON: function() {
		var data = [];
		
		for (var fieldnum in this.valueTD) {
			var logical = 'and';
			var op = this.operatorSelect[fieldnum].getValue();
			if (op == 'not') {
				logical = 'and not';
				op = 'in';
			}
			var val = [];
			
			// MULTISEARCH
			var multicheckbox = this.valueTD[fieldnum].down('ul');
			if (multicheckbox) {
				var multisearch = [];
				var checkboxes = multicheckbox.select('input[type="checkbox"]');
				for (var i = 0; i < checkboxes.length; i++) {
					if (checkboxes[i].checked)
						multisearch.push(checkboxes[i].getValue());
				}
				val.push(multisearch);
			} else {
				var reldateSelect = this.valueTD[fieldnum].down('select');
				if (reldateSelect)
					val.push(reldateSelect.getValue());
					
				var inputs = this.valueTD[fieldnum].select('input');
				for (var i = 0; i < inputs.length; i++)
					val.push(inputs[i].getValue());
			}
			
			data.push({'fieldnum':fieldnum, 'type':this.fieldmaps[fieldnum]['type'], 'logical':logical, 'op':op, 'val':val});
		}
		alert(data.toJSON());
		return data.toJSON();
	},
	
	// PRIVATE FUNCTION
	handle_ajax_reset: function(transport) {
		var data = transport.responseJSON;
		if (!data) {
			alert('Sorry cannot get fieldmaps');
			return;
		}
		
		this.operators = data['operators'];
		this.reldateOptions = data['reldateOptions'];
		this.fieldmaps = {};
		
		// Add "is not" to the multisearch operators.
		// TODO: what is the correct key for 'Is NOT'?
		this.operators['multisearch']['not'] = 'is NOT';
		this.operators['multisearch']['in'] = 'is';
		
		// Check if there are any unsearchable fieldmaps.
		// Also data['fieldmaps'] is indexed by a number, we prefer indexing by fieldnum.
		for (var i in data['fieldmaps']) {
			var fieldnum = data['fieldmaps'][i]['fieldnum'];
			if (data['fieldmaps'][i]['options'].match('searchable'))
				this.fieldmaps[fieldnum] = data['fieldmaps'][i];
		}
		
		// Keep a cache (html) of list values so we don't have to use ajax each time someone changes the operator on a multisearch field.
		this.multisearchSelectCache = {};
		
		this.clear_rules();
	},
	
	// PRIVATE FUNCTION
	// Determines the appropriate rule to insert into the DOM based on this.fieldSelect
	handle_event_add_rule: function() {
		var selectedFieldnum = this.fieldSelect.getValue();
		
		for (var fieldnum in this.fieldmaps) {
			if (fieldnum == selectedFieldnum)
				this.insert_rule(fieldnum);
		}
		
		this.refresh();
	},
	
	// PRIVATE FUNCTION
	handle_event_delete_rule: function(event) {
		var button = Event.element(event);
		
		var actionFieldnum = button.up('tr').down('input[type="hidden"]').getValue();
		
		// Important: Get the value of actionFieldnum before deleting the table row.
		var tr = button.up('tr');
		tr.remove();
		
		for (var fieldnum in this.fieldmaps) {
			if (fieldnum == actionFieldnum) {
				delete this.operatorSelect[fieldnum];
				delete this.valueTD[fieldnum];
			}
		}
		
		this.refresh();
	},
	
	// PRIVATE FUNCTION
	handle_ajax_multisearch_values: function(transport, fieldnum) {
		var data = transport.responseJSON;
					
		if (!data) {
			data = ' ';
		}
		
		var multicheckbox = util_multicheckbox(this.div.id + '_multisearch_' + fieldnum + '_', data);
		this.valueTD[fieldnum].update(multicheckbox);
		
		// cache in memory.
		this.multisearchSelectCache[fieldnum] = multicheckbox;
	},
	
	// PRIVATE FUNCTION
	handle_event_change_operator: function(event) {
		var selectbox = Event.element(event);
		if (selectbox) {
			var fieldnum = selectbox.up('tr').down('input[type="hidden"]').getValue();
			this.show_value_column(fieldnum);
		}
	},
	
	// PRIVATE FUNCTION
	// Inserts a rule into the DOM.
	// @param i, index for this.fieldmaps. Example: this.fieldmaps[fieldnum]
	insert_rule: function(fieldnum) {
		// Don't add the same rule twice.
		if (this.valueTD[fieldnum])
			return;
		
		var tr = new Element('tr');
		this.rulesTable.insert({top:tr});
		
		// FieldTD
		var fieldTD = new Element('td', {'class':'FieldTD'});
		tr.insert(fieldTD);
		fieldTD.insert(this.fieldmaps[fieldnum]['name']);
		// Keep track of the row's fieldnum by using a hidden input.
		fieldTD.insert(new Element('input', {'type':'hidden', 'value':fieldnum}));
		
		// OperatorTD
		var operatorTD = new Element('td', {'class':'OperatorTD'});
		tr.insert(operatorTD);
		for (var type in this.operators) {
			if (this.fieldmaps[fieldnum]['options'].match(type)) {
				this.fieldmaps[fieldnum]['type'] = type;
				this.operatorSelect[fieldnum] = util_selectbox(this.operators[type]);
				// Don't bother handling onchange if type is "text" or "multisearch"
				if (type != 'text' && type != 'multisearch')
					this.operatorSelect[fieldnum].observe('change', this.handle_event_change_operator.bindAsEventListener(this));
				operatorTD.insert(this.operatorSelect[fieldnum]);
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
		var deleteButton = new Element('button', {'type':'button'});
		actionTD.insert(deleteButton);
		deleteButton.observe('click', this.handle_event_delete_rule.bindAsEventListener(this));
		deleteButton.insert('Delete');
	},
	
	
	// PRIVATE FUNCTION
	show_value_column: function(fieldnum) {
		var operator = this.operatorSelect[fieldnum].getValue();
		
		
		
		// MULTISEARCH
		if (this.fieldmaps[fieldnum]['type'] == 'multisearch') {
			this.valueTD[fieldnum].update('Loading..');
			if (this.multisearchSelectCache[fieldnum]) {
				this.valueTD[fieldnum].update(this.multisearchSelectCache[fieldnum]);
			} else {
				new Ajax.Request('ajax.php?ajax&type=persondatavalues&fieldnum=' + fieldnum, {
					onSuccess: this.handle_ajax_multisearch_values.bindAsEventListener(this, fieldnum)
				});
			}
		// NUMERIC
		} else if (this.fieldmaps[fieldnum]['type'] == 'numeric') {
			var current = this.valueTD[fieldnum].select('input[type="text"]');
			var value1 = (current && current[0]) ? current[0].getValue() : '';
			var value2 = (current && current[1]) ? current[1].getValue() : '';
		
			this.valueTD[fieldnum].update(util_textbox([value1]));
			this.valueTD[fieldnum].insert(util_textbox([value2], operator != 'num_range'));
		// DATE
		} else if (this.fieldmaps[fieldnum]['type'] == 'reldate') {
			var current = this.valueTD[fieldnum].select('input[type="text"]');
			var value2 = (current && current[0]) ? current[0].getValue() : '';
			var value3 = (current && current[1]) ? current[1].getValue() : '';
			var value4 = (current && current[2]) ? current[2].getValue() : '';
			var value5 = (current && current[3]) ? current[3].getValue() : '';
			
			this.valueTD[fieldnum].update(util_selectbox(this.reldateOptions, operator != 'reldate'));
			this.valueTD[fieldnum].insert(util_datebox([value2], operator != 'eq' && operator != 'date_range'));
			this.valueTD[fieldnum].insert(util_datebox([value3], operator != 'date_range'));
			this.valueTD[fieldnum].insert(util_textbox([value4], operator != 'date_offset' && operator != 'reldate_range'));
			this.valueTD[fieldnum].insert(util_textbox([value5], operator != 'reldate_range'));
		// TEXT
		} else if (this.fieldmaps[fieldnum]['type'] == 'text') {
			var current = this.valueTD[fieldnum].select('input[type="text"]');
			var value1 = (current && current[0]) ? current[0].getValue() : '';
			
			this.valueTD[fieldnum].update(util_textbox([value1]));
		} else {
			// Clear the value-column.
			this.valueTD[fieldnum].update();
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
		} else {
			this.fieldSelect.disabled = false;
		}
	}
});

function util_multicheckbox(uniquePrefix, values) {
	div = new Element('div', {'style':'border: solid 1px blue;padding:2px'});
	
	if (values.length) {
		var checkAll = new Element('a', {'href':'', 'style':'display:block;float:left'}).insert('Check All');
		checkAll.observe('click', function(event) {
			var checkboxes = event.element().up('div').select('input[type="checkbox"]');
			for (var i = 0; i < checkboxes.length; i++) {
				checkboxes[i].checked = true;
			}
			event.stop();
		});
		var clearAll = new Element('a', {'href':'', 'style':'display:block;float:right'}).insert('Clear');
		clearAll.observe('click', function(event) {
			var checkboxes = event.element().up('div').select('input[type="checkbox"]');
			for (var i = 0; i < checkboxes.length; i++) {
				checkboxes[i].checked = false;
			}
			event.stop();
		});
		
		div.insert(checkAll).insert(clearAll).insert('<br style="clear:both">');
		
		//var ul = new Element('ul', {'style':'width: 200px; border-top:solid 1px lightgray; height:100px; margin:2px; padding:0;list-style:none; overflow:auto;'});
		var ul = '<ul style="width: 200px; border-top:solid 1px lightgray; height:100px; margin:2px; padding:0;list-style:none; overflow:auto;">';
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
}

function util_selectbox(values, hidden) {
	var selectbox = new Element('select');
		
	if (values.length) {
		for (var i = 0; i < values.length; i++) {
			var option = new Element('option', {'value':values[i]});
			option.insert(values[i].escapeHTML());
			if (i == 0)
				option.selected = true;
			
			selectbox.insert(option);
		}
	} else {
		for (var i in values) {
			var option = new Element('option', {'value':i});
			option.insert(values[i].escapeHTML());
			selectbox.insert(option);
		}
	}
	
	if (hidden)
		selectbox.hide();
		
	return selectbox;
}

function util_datebox(dates, hidden) {
	if (!dates)
		dates = [''];
		
	var div = new Element('div');
	for (var i = 0; i < dates.length; i++) {
		if (i > 0)
			div.insert(' and ');
		var datebox = new Element('input', {'type':'text', 'class':'DateBox', 'value':dates[i]});
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
		
		div.insert(datebox);
	}
	
	if (hidden)
		div.hide();
			
	return div;
}

function util_textbox(values, hidden) {
	if (!values)
		values = [''];
		
	var div = new Element('div');
	for (var i = 0; i < values.length; i++) {
		if (i > 0)
			div.insert(' and ');
			
		var textbox = new Element('input', {'type':'text', 'value':(values[i]+'').escapeHTML()});
		div.insert(textbox);
	}
	
	if (hidden)
		div.hide();
		
	return div;
}
