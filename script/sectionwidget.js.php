var SectionWidget = Class.create({
	// selectedsectionidsmap should be an object literal indexed by sectionid for fast lookup.
	initialize: function(formitemname, selectedsectionscontainer, organizationselectbox, sectioncheckboxescontainer, addbuttoncontainer, selectedsectionidsmap) {
		this.formitemname = formitemname;
		this.formelement = $(formitemname);
		this.form = this.formelement.up('form');
		this.selectedsectionscontainer = selectedsectionscontainer ? $(selectedsectionscontainer) : null;
		this.sectioncheckboxescontainer = $(sectioncheckboxescontainer);
		
		this.organizationselectbox = $(organizationselectbox);
		var changetimer = null;
		this.organizationselectbox.observe('change', function() {
			if (changetimer)
				clearTimeout(changetimer);
			changetimer = setTimeout(function() {
				if (!this.addbuttoncontainer) {
					this.formelement.value = "";
					this.selectedsectionidsmap = {};
				}
				this.getSectionsViaAjax();
			}.bind(this), 200);
		}.bindAsEventListener(this));
		
		this.selectedsectionidsmap = selectedsectionidsmap || {};
		if (this.selectedsectionscontainer) {
			for (var sectionid in selectedsectionidsmap) {
				this.addListItem(sectionid, selectedsectionidsmap[sectionid]);
			}
		}
		
		this.addbuttoncontainer = addbuttoncontainer ? $(addbuttoncontainer) : null;
		if (this.addbuttoncontainer) {
			this.addbutton = icon_button('Add', 'add');
			this.addbutton.observe('click', function(event) {
				var selectedcheckboxes = this.sectioncheckboxescontainer.select('input:checked');
				for (var i = 0, count = selectedcheckboxes.length; i < count; ++i) {
					var selectedcheckbox = selectedcheckboxes[i];
					var label = selectedcheckbox.next('label');
					var sectionid = selectedcheckbox.value;
					var selectedsectionid = this.selectedsectionidsmap[sectionid];
					
					if (selectedsectionid) // Prevent duplicate sectionid.
						continue;
					
					this.selectedsectionidsmap[sectionid] = label.innerHTML;
					
					if (this.selectedsectionscontainer)
						this.addListItem(sectionid, label);
				}
				
				if (this.selectedsectionscontainer) {
					this.sectioncheckboxescontainer.update();
					this.organizationselectbox.selectedIndex = 0;
				}
				
				this.updateFormElementValue();
			}.bindAsEventListener(this));
			
			this.addbuttoncontainer.insert(this.addbutton);
		}
		
		if (this.organizationselectbox.getValue()) {
			this.getSectionsViaAjax();
		}
	},
	
	// skey may be text or a dom element such as a label.
	addListItem: function(sectionid, skey) {
		var listitem = new Element('li');
		
		var removelink = action_link('Remove', 'diagona/10/101');
		removelink.observe('click', function(event, sectionid) {
			event.stop();
			
			delete this.selectedsectionidsmap[sectionid];
			event.element().up('li').remove();
			this.updateFormElementValue();
		}.bindAsEventListener(this, sectionid));
		
		this.selectedsectionscontainer.insert(listitem.insert(skey).insert(removelink));
	},
	
	updateFormElementValue: function() {
		var selectedsectionids = [];
		var selectedsectionidsmap = this.selectedsectionidsmap;
		for (var sectionid in selectedsectionidsmap) {
			if (selectedsectionidsmap[sectionid])
				selectedsectionids.push(sectionid);
		}
		this.formelement.value = selectedsectionids.join(',');
		form_do_validation(this.form, this.formelement);
	},
	
	getSectionsViaAjax: function() {
		var organizationid = this.organizationselectbox.getValue();
		if (!organizationid) {
			// Clear the column contents and force form validation.
			var checkboxesdiv = this.sectioncheckboxescontainer.down('div');
			if (checkboxesdiv) {
				checkboxesdiv.update();
				form_do_validation(this.form, this.formelement);
			}
			return;
		}
		
		this.sectioncheckboxescontainer.update('<img src="img/ajax-loader.gif"/>');
		
		cachedAjaxGet('ajax.php?type=getsections&organizationid=' + organizationid, function(transport) {
			var sections = transport.responseJSON,
				checkboxesdiv = new Element('div', {'style':'width: 200px; white-space: nowrap; border: 1px dotted gray; overflow:auto'}),
				count = 0,
				i = 0;
			
			if (sections) {
				var selectedsectionidsmap = this.selectedsectionidsmap;
				var hasaddbutton = this.addbuttoncontainer ? true : false;
				
				for (var id in sections) {
					// Create a checkbox and label for each section.
					var checkbox = new Element('input', {
						'type': 'checkbox',
						'value': id
					});
					
					if (!hasaddbutton && selectedsectionidsmap[id]) {
						checkbox.checked = true;
						checkbox.defaultChecked = true; // Internet Explorer needs the defaultChecked property set.
					}
					
					var label = new Element('label', {
						'for': checkbox.identify()
					}).insert(sections[id].escapeHTML());
					
					checkboxesdiv.insert(checkbox).insert(label).insert('<br/>');
					
					// Keep track of the count, since sections is an object literal.
					count++;
				}
				
				if (count > 10) {
					checkboxesdiv.setStyle({height: '100px'});
				} else {
					checkboxesdiv.setStyle({height: 'auto'});
				}
				
				this.sectioncheckboxescontainer.update(checkboxesdiv);
			}
			
			if (!hasaddbutton) {
				var clicktimer = null;
				checkboxesdiv.observe('click', function(event) {
					var checkbox = event.element();
					if (!checkbox.match('input'))
						return;
					
					if (checkbox.checked)
						this.selectedsectionidsmap[checkbox.value] = true;
					else if (this.selectedsectionidsmap[checkbox.value])
						delete this.selectedsectionidsmap[checkbox.value];
						
					if (clicktimer)
						clearTimeout(clicktimer);
					clicktimer = setTimeout(function () {
						this.updateFormElementValue();
					}.bind(this), 200);
				}.bindAsEventListener(this));
			}
			
			form_do_validation(this.form, this.formelement);
			
			if (count === 0) {
				this.sectioncheckboxescontainer.update('There are no sections found.');
			}
		}.bindAsEventListener(this));
	}
});