var SectionWidget = Class.create({
	initialize: function(formitemname, organizationselector, sectionscontainer, selectedsectionids) {
		this.formitemname = formitemname;
		this.organizationselector = $(organizationselector);
		this.sectionscontainer = $(sectionscontainer);
		this.selectedsectionids = selectedsectionids;
		
		this.organizationselector.observe('change', function() {
			// Clear selected sections.
			this.selectedsectionids = null;
			this.getSectionsViaAjax();
		}.bindAsEventListener(this));
		
		if (this.selectedsectionids)
			this.getSectionsViaAjax();
	},
	
	////////////////////////////////////////////////
	// Modifiers
	////////////////////////////////////////////////
	
	getSectionsViaAjax: function() {
		var organizationid = this.organizationselector.getValue();
		if (!organizationid) {
			// Clear the column contents.
			$(this.sectionscontainer).update();
			return;
		}
		
		this.sectionscontainer.update('<img src="img/ajax-loader.gif"/>');
		
		cachedAjaxGet('ajax.php?type=sections&organizationid=' + organizationid, function(transport) {
			var sections = transport.responseData;
			if (!sections) {
				alert('There is a problem loading sections.');
				return;
			}
			
			if (sections.length == 0) {
				this.updatesectionscontainer('There are no sections found.');
				return;
			}
			
			var radiobox = new Element('div', {'id':this.formitemname, 'class':'radiobox'});
			
			var checkboxname = this.formitemname + '[]';
			
			for (var i = 0, count = sections.length; i < count; i++) {
				// Create a checkbox and label for each section.
				var checkbox = new Element('input', {
					'type': 'checkbox',
					'name': checkboxname,
					'value': sections[i].id
				});
				var label = new Element('label', {
					'for': checkbox.identify()
				}).insert(sections[i].skey.escapeHTML());
				
				if (this.selectedsections && this.selectedsections[section.id]) {
					checkbox.checked = true;
					// TODO: Internet Explorer may need a tweak to appear checked before inserting into dom.
					checkbox.defaultChecked = true;
				}
				
				checkbox.observe('click', form_event_handler);
				checkbox.observe('blur', form_event_handler);
				checkbox.observe('change', form_event_handler);
				
				radiobox.insert(checkbox).insert(label).insert('<br/>');
			}
			
			// Show the section column and preselect selectedskeys.
			this.sectionscontainer.update(radiobox);
		}.bindAsEventListener(this));
	}
});