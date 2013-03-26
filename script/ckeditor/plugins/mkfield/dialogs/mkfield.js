/**
 * mkfield plugin dialog
 *
 * The presentation layer uses all CKE native controls because using plain HTML
 * makes it difficult to regain access to editor and dialog objects without
 * using a mess of global variables. Suboptimal with respect to controlling the
 * appearance, however the mechanics all work which is the most important.
 *
 * We use the API to pull field maps for this user. We use the USER class to pull
 * the active session user's ID from PHP, and then we use jQuery to request the
  + req + ']'* fieldmap for this user as a JSON structure from the API. We then reformat the
 * JSON data into an array of field names needed by CKEditor's UI constructs to
 * assemble a SELECT box with the field names in it.
 *
 * EXTERNAL DEPENDENCIES
   * jquery.js
 *
 * SMK created 2013-01-03
 */

( function ($) {

	var ftypes = Array(Array('-- Select a Field --', ''));

	// The list of field names is passed into RCIEditor constructor's
	// overrideSettings as 'type:name' pairs array; iterate over them...
	var data = window.parent.rcieditor.getSetting('fieldinsert_list');
	for (var field in data) {
		ftypes.push(Array(data[field]));
	}

	// We can't add the dialog until we have the fieldmap request back
	CKEDITOR.dialog.add('mkfield', function ( editor ) {

		return {
			title: 'Field Insert',
			minWidth: 400,
			minHeight: 60,
			contents: [
				{
					id: 'general',
					label: 'Field Insert',
					padding: 0,
					elements: [

						// Default Value Entry
						{
							type: 'text',
							size: 25,
							id: 'fvalue',
							label: 'Default Value:',
							'default': ''
						},

						// Data Field Selection
						{
							type: 'select',
							id: 'ftype',
							label: 'Data Field:',
							items: ftypes
						}
					]
				}
			],

			onOk: function() {
				var ftype = this.getContentElement( 'general', 'ftype').getValue();
				if (ftype.length) {
					var fvalue = this.getContentElement( 'general', 'fvalue').getValue();
					// TODO - any raw validation on ftype/fvalue before inserting it?
					var field = '<<' + ftype;
					if (fvalue.length) {
						field += ':' + fvalue;
					}
					field += '>>';
					editor.insertText(field);
				}
			}
		};
	});
}) (jQuery);

