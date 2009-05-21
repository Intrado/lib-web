var RuleWidget = Class.create({

	//----------------------------- PUBLIC FUNCTIONS --------------------------

	// @param div, the DOM container for this widget.
	initialize: function(div) {
		this.div = div;
		
		// Toolbar.
		this.toolbar = new Element('div');
		this.div.insert(this.toolbar);
		
		// RULES TABLE
		this.rulesTable = new Element('tbody');
		this.div.insert(new Element('table').insert(this.rulesTable));
		this.clear_rules();
		
		new Ajax.Request('ajax.php?type=rulewidgetsettings', {
			onSuccess: this.handle_ajax_load_rulewidgetsettings.bindAsEventListener(this)
		});
	},
	
	clear_rules: function() {
		// Keep track of the value column of each table row, indexed by fieldnum.
		// Also keep track of the operator choices for each fieldnum.
		this.valueTD = {};
		this.operatorSelectbox = {};
		this.rulesTable.update();
		this.refresh();
	},
	
	// Returns json for rules that the user chose.
	toJSON: function() {
		var data = [];
		
		for (var fieldnum in this.valueTD) {
			var logical = 'and';
			var op = this.operatorSelectbox[fieldnum].getValue();
			if (op == 'not') {
				logical = 'and not';
				op = 'in';
			}
			var val = [];
			
			// MULTISEARCH
			var multicheckbox = this.valueTD[fieldnum].down('ul');
			if (multicheckbox) {
				var multisearchValues = [];
				var checkboxes = multicheckbox.select('input[type="checkbox"]');
				for (var i = 0; i < checkboxes.length; i++) {
					if (checkboxes[i].checked)
						multisearchValues.push(checkboxes[i].getValue());
				}
				// Rule::initFrom() requires val[0] for multisearch.
				val.push(multisearchValues);
			} else {
				var reldateSelectbox = this.valueTD[fieldnum].down('select');
				if (reldateSelectbox)
					val.push(reldateSelectbox.getValue());
					
				var inputs = this.valueTD[fieldnum].select('input');
				for (var i = 0; i < inputs.length; i++)
					val.push(inputs[i].getValue());
			}
			
			data.push({'fieldnum':fieldnum, 'type':this.fieldmaps[fieldnum]['type'], 'logical':logical, 'op':op, 'val':val});
		}
		return data.toJSON();
	},
	
	//----------------------------- PRIVATE FUNCTIONS --------------------------

	handle_ajax_load_rulewidgetsettings: function(transport) {
		var data = transport.responseJSON;
		if (!data) {
			alert('Sorry cannot get fieldmaps');
			return;
		}
		
		this.operators = data['operators'];
		this.reldateOptions = data['reldateOptions'];
		this.fieldmaps = {};
		// data['fieldmaps'] is indexed by record id, we prefer indexing by fieldnum.
		for (var i in data['fieldmaps']) {
			var fieldnum = data['fieldmaps'][i]['fieldnum'];
			this.fieldmaps[fieldnum] = data['fieldmaps'][i];
		}
		
		// Add "is not" to the multisearch operators.
		this.operators['multisearch']['not'] = 'is NOT';
		this.operators['multisearch']['in'] = 'is';
		
		// Keep a cache (html) of list values so we don't have to use ajax each time someone changes the operator on a multisearch field.
		this.multisearchMulticheckboxCache = {};

		this.refresh();
	},
	
	handle_ajax_load_multisearch_values: function(transport, fieldnum) {
		var data = transport.responseJSON;
					
		if (!data)
			data = ' '; // Show the table row anyway.
		
		var multicheckbox = this.make_multicheckbox(this.div.id + '_multisearch_' + fieldnum + '_', data);
		this.valueTD[fieldnum].update(multicheckbox);
		
		// cache in memory.
		this.multisearchMulticheckboxCache[fieldnum] = multicheckbox;
	},
	
	handle_event_add_rule: function() {
		var selectedFieldnum = this.fieldmapSelectbox.getValue();
		
		for (var fieldnum in this.fieldmaps) {
			if (fieldnum == selectedFieldnum)
				this.insert_rule(fieldnum);
		}
		
		this.refresh();
	},
	
	handle_event_delete_rule: function(event) {
		var button = Event.element(event);
		
		var actionFieldnum = button.up('tr').down('input[type="hidden"]').getValue();
		
		// Important: Get the value of actionFieldnum before deleting the table row.
		var tr = button.up('tr');
		tr.remove();
		
		for (var fieldnum in this.fieldmaps) {
			if (fieldnum == actionFieldnum) {
				delete this.operatorSelectbox[fieldnum];
				delete this.valueTD[fieldnum];
			}
		}
		
		this.refresh();
	},
	
	handle_event_change_operator: function(event) {
		var selectbox = Event.element(event);
		if (selectbox) {
			var fieldnum = selectbox.up('tr').down('input[type="hidden"]').getValue();
			this.show_value_column(fieldnum);
		}
	},
	
	// Inserts a rule into the DOM.
	insert_rule: function(fieldnum) {
		// Don't add the same rule twice.
		if (this.valueTD[fieldnum])
			return;
		
		var tr = new Element('tr');
		
		// FieldmapTD
		var fieldmapTD = new Element('td', {'class':'FieldmapTD'}).insert(this.fieldmaps[fieldnum]['name']);
		// Keep track of the row's fieldnum by using a hidden input.
		fieldmapTD.insert(new Element('input', {'type':'hidden', 'value':fieldnum}));
		tr.insert(fieldmapTD);
		
		// OperatorTD
		var operatorTD = new Element('td', {'class':'OperatorTD'});
		for (var type in this.operators) {
			if (this.fieldmaps[fieldnum]['options'].match(type)) {
				this.fieldmaps[fieldnum]['type'] = type;
				this.operatorSelectbox[fieldnum] = this.make_selectbox(this.operators[type]);
				// Don't bother handling onchange if type is "text" or "multisearch"
				if (type != 'text' && type != 'multisearch')
					this.operatorSelectbox[fieldnum].observe('change', this.handle_event_change_operator.bindAsEventListener(this));
				operatorTD.insert(this.operatorSelectbox[fieldnum]);
			}
		}
		tr.insert(operatorTD);
		
		// ValueTD
		this.valueTD[fieldnum] = new Element('td', {'class':'ValueTD'});
		this.show_value_column(fieldnum);
		tr.insert(this.valueTD[fieldnum]);
		
		// ActionTD
		var actionTD = new Element('td', {'class':'ActionTD'});
		var deleteButton = new Element('button', {'type':'button'}).update('Delete');
		deleteButton.observe('click', this.handle_event_delete_rule.bindAsEventListener(this));
		actionTD.insert(deleteButton);
		tr.insert(actionTD);

		this.rulesTable.insert({top:tr});
	},
	
	// Determines the appropriate input boxes to show, makes an ajax request for persondatavalues if necessary for multisearch.
	show_value_column: function(fieldnum) {
		var operator = this.operatorSelectbox[fieldnum].getValue();
		
		// MULTISEARCH
		if (this.fieldmaps[fieldnum]['type'] == 'multisearch') {
			this.valueTD[fieldnum].update('Loading..'); // TODO: Replace with an animated gif
			if (this.multisearchMulticheckboxCache[fieldnum]) {
				this.valueTD[fieldnum].update(this.multisearchMulticheckboxCache[fieldnum]);
			} else {
				new Ajax.Request('ajax.php?type=persondatavalues&fieldnum=' + fieldnum, {
					onSuccess: this.handle_ajax_load_multisearch_values.bindAsEventListener(this, fieldnum)
				});
			}
		// NUMERIC
		} else if (this.fieldmaps[fieldnum]['type'] == 'numeric') {
			var current = this.valueTD[fieldnum].select('input[type="text"]');
			var value1 = (current && current[0]) ? current[0].getValue() : '';
			var value2 = (current && current[1]) ? current[1].getValue() : '';
		
			this.valueTD[fieldnum].update(this.make_textbox(value1));
			if (operator == 'num_range')
				this.valueTD[fieldnum].insert(' and ');
			this.valueTD[fieldnum].insert(this.make_textbox(value2, operator != 'num_range'));
		// DATE
		} else if (this.fieldmaps[fieldnum]['type'] == 'reldate') {
			var current = this.valueTD[fieldnum].select('input[type="text"]');
			var value2 = (current && current[0]) ? current[0].getValue() : '';
			var value3 = (current && current[1]) ? current[1].getValue() : '';
			var value4 = (current && current[2]) ? current[2].getValue() : '';
			var value5 = (current && current[3]) ? current[3].getValue() : '';
			
			this.valueTD[fieldnum].update(this.make_selectbox(this.reldateOptions, operator != 'reldate'));
			this.valueTD[fieldnum].insert(this.make_datebox(value2, operator != 'eq' && operator != 'date_range'));
			if (operator == 'date_range')
				this.valueTD[fieldnum].insert(' and ');
			this.valueTD[fieldnum].insert(this.make_datebox(value3, operator != 'date_range'));
			this.valueTD[fieldnum].insert(this.make_textbox(value4, operator != 'date_offset' && operator != 'reldate_range'));
			if (operator == 'reldate_range')
				this.valueTD[fieldnum].insert(' and ');
			this.valueTD[fieldnum].insert(this.make_textbox(value5, operator != 'reldate_range'));
		// TEXT
		} else if (this.fieldmaps[fieldnum]['type'] == 'text') {
			var current = this.valueTD[fieldnum].select('input[type="text"]');
			var value1 = (current && current[0]) ? current[0].getValue() : '';
			
			this.valueTD[fieldnum].update(this.make_textbox(value1));
		} else {
			// Clear the column anyway.
			this.valueTD[fieldnum].update();
		}
	},
	
	// Refreshes the options available in fieldmapSelectbox.
	refresh: function() {
		this.toolbar.update();
		
		this.fieldmapSelectbox = new Element('select');
		this.fieldmapSelectbox.observe('change', this.handle_event_add_rule.bindAsEventListener(this));
		this.toolbar.insert(this.fieldmapSelectbox);
		
		this.fieldmapSelectbox.update(new Element('option', {'value':''}).insert('--Select a Field--'));
		
		for (var fieldnum in this.fieldmaps) {
			// Don't allow adding the same rule twice.
			if (this.valueTD[fieldnum])
				continue;
			
			// Different CSS classes for F,G,C fields.
			var fgcClass = 'FField';
			if (fieldnum.match('^g'))
				fgcClass = 'GField';
			else if (fieldnum.match('^c'))
				fgcClass = 'CField';
			this.fieldmapSelectbox.insert(new Element('option', {'value':fieldnum, 'class':fgcClass}).insert(this.fieldmaps[fieldnum]['name']));
		}
		
		if (this.fieldmapSelectbox.options.length < 2)
			this.fieldmapSelectbox.disabled = true;
		else
			this.fieldmapSelectbox.disabled = false;
	},

	make_multicheckbox: function(uniquePrefix, values) {
		div = new Element('div', {'style':'border: solid 1px blue;padding:2px'});
		
		if (values.length) {
			var checkAllLink = new Element('a', {'href':'', 'style':'display:block;float:left'}).insert('Check All');
			checkAllLink.observe('click', function(event) {
				event.stop();
				var checkboxes = event.element().up('div').select('input[type="checkbox"]');
				for (var i = 0; i < checkboxes.length; i++)
					checkboxes[i].checked = true;
			});

			var clearLink = new Element('a', {'href':'', 'style':'display:block;float:right'}).insert('Clear');
			clearLink.observe('click', function(event) {
				event.stop();
				var checkboxes = event.element().up('div').select('input[type="checkbox"]');
				for (var i = 0; i < checkboxes.length; i++)
					checkboxes[i].checked = false;
			});
			
			div.insert(checkAllLink).insert(clearLink);
			
			// TODO: Determine if it's faster to insert as html or use DOM methods.
			//var ul = new Element('ul', {'style':'clear:both; width: 200px; border-top:solid 1px lightgray; height:100px; margin:2px; padding:0;list-style:none; overflow:auto;'});
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
