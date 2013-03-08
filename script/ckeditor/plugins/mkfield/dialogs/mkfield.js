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
   * API
   * USER.js.php
 *
 * SMK created 2013-01-03
 */

( function ($) {

	// Get the base URL for AJAX API requests
	var baseUrl = window.top.rcieditor.getBaseUrl();

	// Formulate the AJAX API request URL with this session's user ID
	var req = baseUrl + 'api/2/users/' + window.top.USER.id + '/roles/0/accessprofile/fieldmaps/';

	// Make an AJAX request to the API to pull the fieldmap JSON;
	// Make our AJAX request synchronous, otherwise CKE will think
	// that the requested dialog is undefined if we return directly
	$.ajax({
		dataType: "json",
		async: false,
		url: req,
		data: '',
		success: function (data) {

			var ftypes = Array(Array('-- Select a Field --', ''));
			$(data).each(function () {

				// filter out "C" and "G" field types which are invalid here
				if ((this.fieldnum[0] == 'c') || (this.fieldnum[0] == 'g')) {
					return;
				}
				ftypes.push(Array(this.name));
			});

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
		}
	});
}) (jQuery);

