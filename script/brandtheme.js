var BrandTheme = Class.create({

	// Initialize with formname and available colorschemes
	initialize: function(formname,colorschemes) {
		this.formname = formname;
		this.colorschemes = colorschemes;
	},
	
	// When a new theme is selected. Update the color and ratio fields with default values
	loadTheme: function() {
		theme = $(this.formname+"theme").value;
		$(this.formname+"color").value = this.colorschemes[theme]._brandprimary;
		$(this.formname+"ratio").value = this.colorschemes[theme]._brandratio;
		this.store();
	},
	
	// Save changes in the hidden field
	store: function() {
		$(this.formname).value = Object.toJSON({
			"theme": $(this.formname+"theme").value,
			"color": $(this.formname+"color").value,
			"ratio": $(this.formname+"ratio").value
		});
	}
});


