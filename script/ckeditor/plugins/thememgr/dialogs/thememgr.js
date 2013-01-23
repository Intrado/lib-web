/**
 * thememgr plugin dialog
 *
 * The presentation layer uses all CKE native controls because using plain HTML
 * makes it difficult to regain access to editor and dialog objects without
 * using a mess of global variables. Suboptimal with respect to controlling the
 * appearance, however the mechanics all work which is the most important.
 *
 * SMK created 2013-01-16
 */
CKEDITOR.dialog.add('thememgr', function ( editor ) {

/*
	//  Pull in the customer-defined field definitions
	var fields = Array();
	if (typeof rcidata === 'object') {
		fields = rcidata.get('customer_field_defs');
	}
	else if (typeof window.top.rcidata === 'object') {
		fields = window.top.rcidata.get('customer_field_defs');
	}
//	else {
//		console.log('rcidata undefined.');
//	}

	var ftypes = Array(Array('-- Select a Field --', ''));
	for (fi = 0; fi < fields.length; fi++) {
		ftypes.push(Array(fields[fi]));
	}
*/

	return {
		title: 'Theme Manager',
		minWidth: 400,
		minHeight: 60,

		contents: [
			{
				id: 'general',
				label: 'Theme Manager',
				padding: 0,
				elements: [
					{
						type: 'html',
						html: '<div id="thememgr_content"></div>'
					}

/*
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
*/
				]
			}
		],

		// The first thing that happens
		onShow: function() {
			var ui = '';
			var self = CKEDITOR.dialog.getCurrent().definition;

			try {

				// Check some prerequisites
				if (typeof window.top.RCIEditor !== 'object') {
					throw 'RCIEditor object not found; this plugin requires rcieditor.js to be included';
				}

				var doc = this.getElement().getDocument(); // ckeditor.js
				if (! doc) {
					throw 'Failed to getDocument() from CKE';
				}

				var e = doc.getById('thememgr_content'); // ckeditor.js
				if (! e) {
					throw 'Missing content div "thememgr_content" (??)';
				}

				var scratch = RCIEditor.getSetting('rcieditor_scratch');
				if (typeof scratch === 'undefined') {
					throw 'scratch space could not be found';
				}

				// grab the entire contents of the document being edited
				var content = editor.getData();

				// and stick it into the scratch space for the editor where we can do some
				// DOM work on it; prototype.js can access it here, without painful iframe extension
				//scratch.update(content); // prototype.js
				scratch.empty().html(content); // jquery.js

				// Scan for and get a list of all the rcithemed elements within the document
				var rcitheme_data = self.theme_scan(scratch);

// --------------
/*
				// Find elements within container that look something like these:
				// ref: http://html5doctor.com/html5-custom-data-attributes/

				// Element with legacy HTML attribute control
				// OLD: <element data-rcitheme="attribute:bgcolor=color:Main Color 1" bgcolor="#999999">
				// NEW: <element data-rcitheme="[(id:'Main Color 1',type:'color',tgt:'attribute',name:'bgcolor')]" bgcolor="#999999">

				// Element with multiple style property controls
				// OLD: <element data-rcitheme="style:background-color=color:Main Color 1,style:color=color:Main Color 2" style="font-weight: bold; color: #FFFFFF; background-color: #999999;">
				// NEW: <element data-rcitheme="[{id:'Main Color 1',type:'color',tgt:'style',name:'background-color'},{id:'Main Color 2',type:'color',tgt:'style',name:'color'}]" style="font-weight: bold; color: #FFFFFF; background-color: #999999;">
				//var themedElements = $(scratch).select('[data-rcitheme]'); // prototype.js
				var themedElements = jQuery('[data-rcitheme]', scratch); // jquery.js

				if (typeof themedElements === 'undefined') {
					throw 'No themed elements in this document!';
				}

				var themedElementsCount = 0;

				themedElements.each(function (ii) {
					themedElementsCount++;

					var rcitheme_prejson = jQuery(this).attr('data-rcitheme');
					// Replace 's in the XML with "s which the JSON parser wants
					var rcitheme_json = rcitheme_prejson.replace(/\'/g, '"');

	console.log('rcitheme_json is [' + rcitheme_json + ']');

					//var rcitheme = Array();
					var rcitheme = [];

					var rcitheme_json_parts = rcitheme_json.split('||');

					if (! rcitheme_json_parts.length) return;

					for (var jj = 0; jj < rcitheme_json_parts.length; jj++) {

						// ref: http://www.json.org/js.html
						//var rcitheme = eval('(' + rcitheme_json + ')');
						// ref: https://github.com/douglascrockford/JSON-js
						var rcitheme_json_part = JSON.parse(rcitheme_json_parts[jj], function (key, value) {
		console.log('JSON.parse found key [' + key + '] = value [' + value + ']');
							// return value ONLY if the key is in a list of supported keys
							switch (key) {
								case 'id':
								case 'type':
								case 'tgt':
								case 'name':
									return(value);
							}
							// FIXME: JSON2 doc says that if we return undefined/null the key/value
							// pair will be deleted, yet when we do that here for unsupported keys,
							// the entire JSON object comes out null contrary to the explanation. So
							// for now, this function does nothing to filter unexpected keys out.
							//return(null);
							return(value);
						});

						rcitheme.push(rcitheme_json_part);
					}

					// RCI Theme JSON encoding must always be an array, even if it's only one item
					if (typeof rcitheme === 'undefined') return;


					//if (! rcitheme.length) return;
					rcitheme = jQuery(rcitheme);
					if (! rcitheme.size()) return;

					for (var jj = 0; jj < rcitheme.size(); jj++) {
						var rcitheme_item = rcitheme[jj];

						// Do we already have a theme data entry for this theme id?
						if (typeof rcitheme_data[rcitheme_item.id] !== 'undefined') {
							// Then we don't need to add this one
							return;
						}

						// Add this theme id to the theme data
						switch (rcitheme_item.type) {
							case 'color':
								rcitheme_data.add_color(rcitheme_item.id);
								break;
						}
					}
				});

				if (! themedElementsCount) {
					throw 'No themed elements in this document!';
				}
*/
// --------------

				// If there are no theme data items
				if (! rcitheme_data.count) {
					ui = 'This stationery has not been enabled with theme properties.';	
				}
				else {
					// Break the list of theme items up by type; we only have a color selection type for now
					var ui_components = {
						colors: ''
					};

					// If there are any theme colors
					if (rcitheme_data.color.size()) {

						// Then add the color selector
						var theme_color_options = '';
						for (var jj = 0; jj < rcitheme_data.color.size(); jj++) {
							theme_color_options += '<option>' + rcitheme_data.color[jj] + '</option>';
						}
						ui_components.colors = '<select id="theme_color_id"><option value="" selected="selected"">Select a theme color to update...</option>' + theme_color_options + '</select>';
					}
					else {
						// we shouldn't be able to get here - colors are our only option right now
						ui = 'no theme selection output';
					}

					// A collections of tab/views that we will put our ui components into
					var ui_tabs = '';
					var ui_views = '';
					var ui_code = '';

// TODO: add JS function for theme_show()
					// Add some functions that we can use
					var tscall = 'CKEDITOR.dialog.getCurrent().definition.theme_tab_switch';

					// Add tab/view for color selection
					if (ui_components.colors.length) {
						ui_tabs += '<span id="tab_colors" class="active" onclick="' + tscall + '(\'colors\');">&nbsp;&nbsp;COLORS&nbsp;&nbsp;</span>';

						ui_views += '<div id="view_colors">';
						ui_views += '	<div class="viewstep"> 1) ' + ui_components.colors + '</div>';
						ui_views += '	<div class="viewstep"> 2) ';
						ui_views += '		Select Color: #<input id="theme_color_code" type="text" size="6" maxlength="6" onchange="CKEDITOR.dialog.getCurrent().definition.theme_color_preview_update();"/> <span id="theme_color_preview">&nbsp;</span>';
						ui_views += '		<div class="swatches">';

						var cscall = 'CKEDITOR.dialog.getCurrent().definition.theme_color_swatch';
						var csnum = 0;
						for (var rr = 0; rr < 256; rr += 0x33) {
							for (var gg = 0; gg < 256; gg += 0x33) {
								for (var bb = 0; bb < 256; bb += 0x33) {
									var rgb = self.hex(rr) + self.hex(gg) + self.hex(bb);
									ui_views += '<span id="theme_color_swatch_' + csnum + '" style="background-color: #' + rgb + ';" onclick="' + cscall + '(' + csnum + ');">&nbsp;</span>';
									csnum++;
								}
							}
								ui_views += '<br clear="all"/>';
						}
						ui_views += '		</div>';
						ui_views += '	</div>';
						ui_views += '</div>';
					}

					// Build UI output from all defined tabs/views
					ui = '<div class="themetabs">' + ui_tabs + '</div><div class="themeviews">' + ui_views + '</div>';
					ui += '<style>';
					ui += '		div.themetabs > span > { font-size: 10px; color: #666666; font-weight: bold; margin: 5px 0px; }';
					ui += '		div.themetabs > span.active { color: #FF6600; background-color: #EEEEEE; }';
					ui += '		div.themeviews input { background-color: white; border: 1px solid #999999; padding-top: 10px; }';
					ui += '		div.themeviews { background-color: #EEEEEE; }';
					ui += '		div.themeviews > div#view_colors > div.viewstep > div.swatches > span { display: inline-block; float: left; margin: 0px; height: 10px; width: 10px; font-size: 1px; cursor: pointer; }';
					ui += '		div.themeviews > div#view_colors > div.viewstep > span#theme_color_preview { position: relative; top: -10px; display: inline-block; height: 16px; width: 32px; font-size: 1px; border: 1px solid black; }';
					ui += '</style>';
				}


//window.top.RCIEditor.themeScan(editor);

			}
			catch (msg) {
console.log('ERROR [CKEditor.plugins.thememgr.onShow()]: ' + msg);
				ui = 'There was an error: [' + msg + ']';
			}

			e.setHtml('<div>' + ui + '</div>');
		},

		// The last thing that happens
		onOk: function() {
/*
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
*/
			var self = CKEDITOR.dialog.getCurrent().definition;

			try {
				// Get the selection that was made for a color id
				var color_id = jQuery('#theme_color_id').val();
				if (! color_id.length) {
					throw 'No selection was made!';
				}

				// Get the selection that was made for a color code
				var color_code = jQuery('#theme_color_code').val();
				if (! color_code.length) {
					throw 'No color was chosen!';
				}

				var scratch = RCIEditor.getSetting('rcieditor_scratch');
				if (typeof scratch === 'undefined') {
					throw 'scratch space could not be found';
				}

console.log('Applying the color [' + color_code + '] to the document with themed element id [' + color_id + ']');
				var res = self.theme_scan(scratch, color_id, color_code);

				// For setting, we need to put our scratch area back into the document
				if (res) {
					var content = scratch.html();
					editor.setData(content);
				}

				return(true);
			}
			catch (msg) {
				alert('Oops! ' + msg);
			}

			// Prevent the dialog from closing since we got here due to incomplete selections
			return(false);
		},

		/**
		 * Scan the container for themed elements and either collect or set data
		 *
		 * @param container jQuery element containing the document elements to scan
		 * @param theme_id string Optional theme identifier to scan the document for
		 * @param theme_value string Optional theme value to set the identifier to when found
		 *
		 * @return Object array if in collection mode, or nothing
		 */
		theme_scan: function(container, theme_id, theme_value) {

			try {
				var collecting = true;;
				// Now are we collecting or setting?
				if ((typeof theme_id !== 'undefined') && (typeof theme_value !== 'undefined')) {
					// Setting!
					collecting = false;
				}
console.log('theme_scan() mode is [' + (collecting ? 'collecting' : 'setting') + ']');

				var rcitheme_data = {
					count: 0,
					color: Array(),
					add_color: function (id) {

						// Prevent the addition of an empty string as a selection id
						if (! id.length) return;

						// TODO - prevent duplicate color id's from being added here
console.log('Adding theme color [' + id + ']');
						this.color.push(id);
						this.count++;
					}
				};

				// Find elements within container that look something like these:
				// ref: http://html5doctor.com/html5-custom-data-attributes/

				// Element with legacy HTML attribute control
				// OLD: <element data-rcitheme="attribute:bgcolor=color:Main Color 1" bgcolor="#999999">
				// NEW: <element data-rcitheme="[(id:'Main Color 1',type:'color',tgt:'attribute',name:'bgcolor')]" bgcolor="#999999">

				// Element with multiple style property controls
				// OLD: <element data-rcitheme="style:background-color=color:Main Color 1,style:color=color:Main Color 2" style="font-weight: bold; color: #FFFFFF; background-color: #999999;">
				// NEW: <element data-rcitheme="[{id:'Main Color 1',type:'color',tgt:'style',name:'background-color'},{id:'Main Color 2',type:'color',tgt:'style',name:'color'}]" style="font-weight: bold; color: #FFFFFF; background-color: #999999;">
				//var themedElements = $(scratch).select('[data-rcitheme]'); // prototype.js
				var themedElements = jQuery('[data-rcitheme]', container); // jquery.js

				if (typeof themedElements === 'undefined') {
					throw 'No themed elements in this document!';
				}

				var themedElementsCount = 0;

				themedElements.each(function (ii) {
					themedElementsCount++;

					var themedElement = jQuery(this);

					var rcitheme_prejson = themedElement.attr('data-rcitheme');
					// Replace 's in the XML with "s which the JSON parser wants
					var rcitheme_json = rcitheme_prejson.replace(/\'/g, '"');

	console.log('rcitheme_json is [' + rcitheme_json + ']');

					//var rcitheme = Array();
					var rcitheme = [];

					var rcitheme_json_parts = rcitheme_json.split('||');

					if (! rcitheme_json_parts.length) return;

					for (var jj = 0; jj < rcitheme_json_parts.length; jj++) {

						// ref: http://www.json.org/js.html
						//var rcitheme = eval('(' + rcitheme_json + ')');
						// ref: https://github.com/douglascrockford/JSON-js
						var rcitheme_json_part = JSON.parse(rcitheme_json_parts[jj], function (key, value) {
		console.log('JSON.parse found key [' + key + '] = value [' + value + ']');
							// return value ONLY if the key is in a list of supported keys
							switch (key) {
								case 'id':
								case 'type':
								case 'tgt':
								case 'name':
									return(value);
							}
							// FIXME: JSON2 doc says that if we return undefined/null the key/value
							// pair will be deleted, yet when we do that here for unsupported keys,
							// the entire JSON object comes out null contrary to the explanation. So
							// for now, this function does nothing to filter unexpected keys out.
							//return(null);
							return(value);
						});

						// If we are collecting theme data...
						if (collecting) {

							// Then push this entry onto the queue
							rcitheme.push(rcitheme_json_part);
						}
						else {
							// Otherwise we are setting so let's do something with it

							// Does this one have the theme ID we're looking for?
							if (rcitheme_json_part.id == theme_id) {
console.log('Hey, found it! [' + theme_id + ']');
// TODO - modify the attribute/property affiliated with this element based on the theme description

								// What kind of target is the theme_value going to be stored into?
								switch (rcitheme_json_part.tgt) {
									case 'style':
										// We're putting theme_value into the element's style attribute
console.log('Setting themed element css [' + rcitheme_json_part.name + '] to [' + theme_value + '] current is [' + themedElement.css(rcitheme_json_part.name) + ']');
										themedElement.css(rcitheme_json_part.name, '#' + theme_value);
										break;

									case 'attribute':
										break;
								}
							}
						}
					}

					// The rest of this is only useful for collecting theme data to return
					if (collecting) {
						// RCI Theme JSON encoding must always be an array, even if it's only one item
						if (typeof rcitheme === 'undefined') return;


						rcitheme = jQuery(rcitheme);
						if (! rcitheme.size()) return;

						for (var jj = 0; jj < rcitheme.size(); jj++) {
							var rcitheme_item = rcitheme[jj];

							// Add this theme id to the theme data
							switch (rcitheme_item.type) {
								case 'color':
									rcitheme_data.add_color(rcitheme_item.id);
									break;
							}
						}
					}
				});

				// if we are collecting
				if (collecting) {

					// then we expect to have something to return
					if (! themedElementsCount) {
						throw 'No themed elements in this document!';
					}

					return(rcitheme_data);
				}
				else {
					// Otherwise, for setting, we need to put our scratch area back into the document
					return(true);
				}
			}
			catch (msg) {
console.log('Soft error: [' + msg + ']');
			}
		},

		theme_color_swatch: function(num) {
			var color_code = this.rgb2hex(jQuery('#theme_color_swatch_' + num).css('background-color'));
			jQuery('#theme_color_code').val(color_code);
			this.theme_color_preview_update();
		},

		theme_color_preview_update: function() {
			var color_code = jQuery('#theme_color_code').val();
			jQuery('#theme_color_preview').css('background-color', '#' + color_code);
		},

		// ref: http://stackoverflow.com/questions/1740700/get-hex-value-rather-than-rgb-value-using-jquery
		rgb2hex: function (rgb) {
			rgb = rgb.match(/^rgb\((\d+),\s*(\d+),\s*(\d+)\)$/);
			return(this.hex(rgb[1]) + this.hex(rgb[2]) + this.hex(rgb[3]));
		},

		hex: function (x) {
			var hexDigits = new Array('0','1','2','3','4','5','6','7','8','9','a','b','c','d','e','f'); 
			return isNaN(x) ? "00" : hexDigits[(x - x % 16) / 16] + hexDigits[x % 16];
		}
	};
});

function retarded() {
	CKEDITOR.dialog.getCurrent().definition.rgb2hex('abc');
/*
	var cked = CKEDITOR.dialog.getCurrent();
	console.log('cked is a ' + cked);
	cked.definition.rgb2hex('abc');
*/
}
