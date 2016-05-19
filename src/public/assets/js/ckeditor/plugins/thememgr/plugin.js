
CKEDITOR.plugins.add( 'thememgr', {
	// SMK note that icon name needs to be a lowercase version of button name
	icons: 'thememgr',
	init: function( editor ) {

		CKEDITOR.dialog.add('thememgr', this.path + 'dialogs/thememgr.js' );

                // Create dialog-based command named "thememgr"
                editor.addCommand('thememgr', new CKEDITOR.dialogCommand('thememgr'));

		editor.ui.addButton('themeMgr', {
			label: 'Theme Manager',
			command: 'thememgr',
			toolbar: 'newone'
		});
	}
});


