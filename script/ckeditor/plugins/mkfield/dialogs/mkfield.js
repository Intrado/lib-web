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
  + req + ']'* fieldmap for this user as a JSON structure from the API.
 * We used to use valid field names from the API directly. Now we get them from
 * our parent, RCIEditor which must have a setting 'fieldinsert_list' as an array
 * of name/value pairs where the name is the field type, and the value is the name
 * of the field as it is to be displayed to the user. If the list is empty (default)
 * then we will not show any field selection options.
 *
 * EXTERNAL DEPENDENCIES
   * jquery.js
   * rcieditor.js
 *
 * SMK created 2013-01-03
 */

( function ($) {
	var ftypes = Array(Array('-- Select a Field --', ''));

	// Depending on iframe/containment of the editor, rcieditor may be
	// here or up one parent level; grab a refernece to it either way
	var rcie;
	if (typeof(rcieditor) == 'object') {
		rcie = rcieditor;
	}
	else rcie = window.parent.rcieditor;

	// The list of field names is passed into RCIEditor constructor's
	// overrideSettings as 'type:name' pairs array; iterate over them...
	// to reformat the data into an array of field names needed by
	// CKEditor's UI constructs to assemble a SELECT box with the field
	// names in it.
	var data = rcie.getSetting('fieldinsert_list');
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

