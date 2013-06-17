
CKEDITOR.plugins.add( 'mkfield', {
	// SMK note that icon name needs to be a lowercase version of button name
	icons: 'mkfield',
	init: function( editor ) {

		CKEDITOR.dialog.add('mkfield', this.path + 'dialogs/mkfield.js' );

                // Create dialog-based command named "aspell"
                editor.addCommand('mkfield', new CKEDITOR.dialogCommand('mkfield'));

		editor.ui.addButton('mkField', {
			label: 'Field Insert',
			command: 'mkfield',
			toolbar: 'newone'
		});
	}
});


