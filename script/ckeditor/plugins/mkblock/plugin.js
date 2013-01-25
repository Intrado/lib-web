CKEDITOR.plugins.add( 'mkblock', {
	icons: 'mkblock',
	init: function( editor ) {
		editor.addCommand( 'makeBlock', {
			exec: function( editor ) {
				var selectedText = editor.getSelection().getSelectedText(); 
				editor.insertHtml('<div class="editableBlock"><p>');
				editor.insertHtml(selectedText.length ? selectedText : 'This is editable text');
				editor.insertHtml('</p></div>');
			}
		});
		editor.ui.addButton('mkBlock', {
			label: 'Make a new editable block',
			command: 'makeBlock',
			toolbar: 'newone'
		});
	}
});

