/**
 * mkfield plugin dialog
 *
 * The presentation layer uses all CKE native controls because using plain HTML
 * makes it difficult to regain access to editor and dialog objects without
 * using a mess of global variables. Suboptimal with respect to controlling the
 * appearance, however the mechanics all work which is the most important.
 *
 * SMK created 2013-01-03
 */
CKEDITOR.dialog.add('mkfield', function ( editor ) {

	//  Pull in the customer-defined field definitions
	var fields = Array();
	var rcie = null;
	if (typeof rcieditor === 'object') {
		rcie = rcieditor;
	}
	else if (typeof window.top.rcieditor === 'object') {
		rcie = window.top.rcieditor;
	}
	if (rcie) {
		fields = rcie.getSetting('extra_data');
	}

	var ftypes = Array(Array('-- Select a Field --', ''));
	for (fi = 0; fi < fields.length; fi++) {
		ftypes.push(Array(fields[fi]));
	}

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

//console.log('Inserting: [' + field + ']');
				editor.insertText(field);
			}
		}
	};
});

