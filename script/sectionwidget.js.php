var SectionWidget = Class.create({
	// selectedsectionidsmap should be an object literal of sectionid => true pairs,
	// so that selectedsectionidsmap[sectionid] indicates that a particular section is selected.
	initialize: function(formitemname, organizationselectbox, sectionscontainer, selectedsectionidsmap) {
		this.formitemname = formitemname;
		this.organizationselectbox = $(organizationselectbox);
		this.sectionscontainer = $(sectionscontainer);
		this.form = this.sectionscontainer.up('form');
		this.selectedsectionidsmap = selectedsectionidsmap;
		
		this.organizationselectbox.observe('change', function() {
			// Clear selected sections.
			this.selectedsectionidsmap = null;
			this.getSectionsViaAjax();
		}.bindAsEventListener(this));
		
		if (this.selectedsectionidsmap)
			this.getSectionsViaAjax();
	},
	
	////////////////////////////////////////////////
	// Modifiers
	////////////////////////////////////////////////
	
	getSectionsViaAjax: function() {
		var organizationid = this.organizationselectbox.getValue();
		if (!organizationid) {
			// Clear the column contents and force form validation.
			var blankinput = new Element('input', {'type': 'hidden', 'name': this.formitemname});
			var radiobox = this.sectionscontainer.down('.radiobox');
			if (radiobox) {
				radiobox.update(blankinput);
				form_do_validation(this.form, blankinput);
			}
			return;
		}
		
		this.sectionscontainer.update('<img src="img/ajax-loader.gif"/>');
		
		cachedAjaxGet('ajax.php?type=getsections&organizationid=' + organizationid, function(transport) {
			var sections = transport.responseJSON,
				radiobox = new Element('div', {'id':this.formitemname, 'class':'radiobox', 'style':'width: 200px; white-space: nowrap; overflow:auto'}),
				checkboxname = this.formitemname + '[]',
				count = 0,
				i = 0;
			
			if (sections) {
				for (var id in sections) {
					var skey = sections[id];
					
					// Create a checkbox and label for each section.
					var checkbox = new Element('input', {
						'type': 'checkbox',
						'name': checkboxname,
						'value': id
					});
					var label = new Element('label', {
						'for': checkbox.identify()
					}).insert(skey.escapeHTML());
					
					if (this.selectedsectionidsmap && this.selectedsectionidsmap[id]) {
						checkbox.checked = true;
						// TODO: Internet Explorer may need a tweak to appear checked before inserting into dom.
						checkbox.defaultChecked = true;
					}
					
					radiobox.insert(checkbox).insert(label).insert('<br/>');
					
					// Keep track of the count, since sections is an object literal.
					count++;
				}
				radiobox.observe('click', form_event_handler);
				radiobox.observe('blur', form_event_handler);
				radiobox.observe('change', form_event_handler);
				
				if (count > 10) {
					radiobox.setStyle({height: '100px'});
				} else {
					radiobox.setStyle({height: 'auto'});
				}
			}
			
			if (count === 0) {
				this.sectionscontainer.update('There are no sections found.');
				return;
			}
			
			// Show the section column and preselect selectedskeys.
			this.sectionscontainer.update(radiobox);
			
			if (this.selectedsectionidsmap) {
				form_do_validation(this.form, checkbox);
			}
		}.bindAsEventListener(this));
	}
});