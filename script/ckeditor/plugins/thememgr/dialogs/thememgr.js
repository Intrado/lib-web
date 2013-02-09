/**
 * thememgr plugin dialog
 *
 * The presentation layer uses all CKE native controls because using plain HTML
 * makes it difficult to regain access to editor and dialog objects without
 * using a mess of global variables. Suboptimal with respect to controlling the
 * appearance, however the mechanics all work which is the most important.
 *
 * @todo test multiple theme rules on a single element; implement this as a JSON
 * array if possible instead of || separator... it wasn't working for some reason...
 *
 * SMK created 2013-01-16
 */
CKEDITOR.dialog.add('thememgr', function ( editor ) {

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
				]
			}
		],

		// The first thing that happens
		onShow: function() {
			var ui = '';
			var self = CKEDITOR.dialog.getCurrent().definition;

			try {

				// Check some prerequisites
				if (typeof window.top.rcieditor !== 'object') {
					throw 'rcieditor object not found; this plugin requires rcieditor.js to be included';
				}

				var doc = this.getElement().getDocument(); // ckeditor.js
				if (! doc) {
					throw 'Failed to getDocument() from CKE';
				}

				var e = doc.getById('thememgr_content'); // ckeditor.js
				if (! e) {
					throw 'Missing content div "thememgr_content" (??)';
				}

				var scratch = rcieditor.getSetting('rcieditor_scratch'); // rcieditor.js
				if (typeof scratch === 'undefined') {
					throw 'scratch space could not be found';
				}

				// grab the entire contents of the document being edited
				var content = editor.getData();

				// and stick it into the scratch space for the editor where we can do some
				// DOM work on it; jQuery can access it here, without painful iframe extension
				scratch.empty().html(content);

				// Scan for and get a list of all the rcithemed elements within the document
				var rcitheme_data = self.theme_scan(scratch);

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
						ui_components.colors = '<select id="theme_color_id">';
						if (rcitheme_data.color.size() > 1) {
							ui_components.colors += '<option value="" selected="selected"">Select a theme color to update...</option>';
						}
						ui_components.colors += theme_color_options + '</select>';
					}
					else {
						// we shouldn't be able to get here - colors are our only option right now
						ui = 'no theme selection output';
					}

					// A collections of tab/views that we will put our ui components into
					var ui_tabs = '';
					var ui_views = '';
					var ui_code = '';

					// Add some functions that we can use
					var tscall = 'CKEDITOR.dialog.getCurrent().definition.theme_tab_show';

					// Add tab/view for color selection
					if (ui_components.colors.length) {
						ui_tabs += '<span id="theme_tab_colors" class="inactive" onclick="' + tscall + '(\'colors\');">&nbsp;&nbsp;COLORS&nbsp;&nbsp;</span>';

						ui_views += '<div id="theme_view_colors" class="theme_view inactive">';
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

					/*
					if (true) { // Add another tab like this:
						ui_tabs += '<span id="theme_tab_sample" class="inactive" onclick="' + tscall + '(\'sample\');">&nbsp;&nbsp;SAMPLE&nbsp;&nbsp;</span>';
						ui_views += '<div id="theme_view_sample" class="theme_view inactive">Sample tab content goes here!</div>';
					}
					*/

					// Build UI output from all defined tabs/views
					ui = '<div class="themetabs">' + ui_tabs + '</div><div class="themeviews">' + ui_views + '</div>';
					ui += '<style>';
					ui += '		div.themetabs > span { font-size: 10px; font-weight: bold; margin: 5px 0px; }';
					ui += '		div.themetabs > span.inactive { color: #666666; background-color: inherit; }';
					ui += '		div.themetabs > span.active { color: #0066CC; background-color: #DDD; }';
					ui += '		div.themeviews { background-color: #DDD; }';
					ui += '		div.themeviews > div.inactive { display: none; }';
					ui += '		div.themeviews > div.active { display: block; }';
					ui += '		div.themeviews div.viewstep { padding: 5px 10px; }';
					ui += '		div.themeviews input, div.themeviews select { background-color: white; border: 1px solid #999; }';

					// tab-specific styles:
					ui += '		div.themeviews > div#theme_view_colors > div.viewstep > div.swatches > span { display: inline-block; float: left; margin: 0px; height: 10px; width: 10px; font-size: 1px; cursor: pointer; }';
					ui += '		div.themeviews > div#theme_view_colors > div.viewstep > span#theme_color_preview { position: relative; top: -10px; display: inline-block; height: 16px; width: 32px; font-size: 1px; border: 1px solid black; }';
					ui += '</style>';
				}
			}
			catch (msg) {
				ui = 'There was an error: [' + msg + ']';
			}

			e.setHtml('<div>' + ui + '</div>');

			self.theme_tab_show('colors');
		},

		// The last thing that happens
		onOk: function() {
			var self = CKEDITOR.dialog.getCurrent().definition;

			try {

				// Only the active theme_tab is used upon submission
				switch (self.theme_tab_showing) {
					case 'colors':
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

						var scratch = rcieditor.getSetting('rcieditor_scratch');
						if (typeof scratch === 'undefined') {
							throw 'scratch space could not be found';
						}

//console.log('Applying the color [' + color_code + '] to the document with themed element id [' + color_id + ']');
						var res = self.theme_scan(scratch, color_id, '#' + color_code);
						break;

					// Add other tab submissions like so:
					case 'sample':
						break;
				}

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
		 * Find elements within container that look something like these:
		 * ref: http://html5doctor.com/html5-custom-data-attributes/
		 *
		 * Element with legacy HTML attribute control
		 * OLD: <element data-rcitheme="attribute:bgcolor=color:Main Color 1" bgcolor="#999999">
		 * NEW: <element data-rcitheme="[(id:'Main Color 1',type:'color',tgt:'attribute',name:'bgcolor')]" bgcolor="#999999">
		 *
		 * Element with multiple style property controls
		 * OLD: <element data-rcitheme="style:background-color=color:Main Color 1,style:color=color:Main Color 2" style="font-weight: bold; color: #FFFFFF; background-color: #999999;">
		 * NEW: <element data-rcitheme="[{id:'Main Color 1',type:'color',tgt:'style',name:'background-color'},{id:'Main Color 2',type:'color',tgt:'style',name:'color'}]" style="font-weight: bold; color: #FFFFFF; background-color: #999999;">
		 *
		 * @param container jQuery element containing the document elements to scan
		 * @param theme_id string Optional theme identifier to scan the document for
		 * @param theme_value string Optional theme value to set the identifier to when found
		 *
		 * @return Object array if in collection mode, or nothing
		 */
		theme_scan: function(container, theme_id, theme_value) {

			try {

				// Now are we collecting or setting?
				var collecting = ((typeof theme_id !== 'undefined') && (typeof theme_value !== 'undefined')) ? false : true; 

				var rcitheme_data = {
					count: 0,
					color: Array(),

					// Add support for other theme data types here as needed
					add_color: function (id) {

						// Prevent the addition of an empty string as a selection id
						if (! id.length) return;

						// Prevent insertion of duplicate theme color id's
						for (var ii = 0; ii < this.count; ii++) {
							if (this.color[ii] == id) return;
						}
						this.color.push(id);
						this.count++;
					}
				};

				// Find all elements in the container with the data-rcitheme attribute
				var themedElements = jQuery('[data-rcitheme]', container);

				if (typeof themedElements === 'undefined') {
					throw 'No themed elements in this document!';
				}

				// For each element that we found...
				themedElements.each(function (ii) {

					// Extend this element with jQuery
					var themedElement = jQuery(this);

					// Get the data-rcitheme attribute off the element
					var data_rcitheme = themedElement.attr('data-rcitheme');
					if (! data_rcitheme.length) return;

					// Replace 's in the XML with "s which the JSON parser wants
					data_rcitheme = data_rcitheme.replace(/\'/g, '"');
					var rcitheme_json =  JSON.parse(data_rcitheme, function (key, value) {

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

					// If the JSON was supplied as a single item rather than an array...
					if (typeof rcitheme_json.size === 'undefined') {
						// wrap the item as a single-node array
						rcitheme_json = [ rcitheme_json ];
					}

					// Each of the JSON objects in the resulting array  is a "theme item"
					for (var jj = 0; jj < rcitheme_json.size(); jj++) {
						var rcitheme_item = rcitheme_json[jj];
						if (collecting) {

							// Add support for other theme data types here as needed
							switch (rcitheme_item.type) {
								case 'color':
									rcitheme_data.add_color(rcitheme_item.id);
									break;
							}
						}
						else {

							// Otherwise we are setting; Does this one have the theme ID we're looking for?
							if (rcitheme_item.id == theme_id) {

								// Modify the attribute/property affiliated with this element based on the theme item's directives

								// What kind of target is the theme_value going to be stored into?
								switch (rcitheme_item.tgt) {

									case 'style':

										// We're putting theme_value into the element's style attribute'a named property
										themedElement.css(rcitheme_item.name, theme_value);
										break;

									case 'attribute':

										// We're putting theme_value into the element's named attribute
										themedElement.attr(rcitheme_item.name, theme_value);
										break;
								}
							}
						}
					};
				});

				// if we are collecting
				if (collecting) {

					// then we expect to have something to return
					if (! rcitheme_data.count) {
						throw 'No theme data in this document!';
					}

					return(rcitheme_data);
				}
				else {
					// Otherwise, for setting, we need to put our scratch area back into the document
					return(true);
				}
			}
			catch (msg) {
//console.log('Soft error: [' + msg + ']');
			}
		},

		theme_tab_showing: '',

		theme_tab_show: function(tabname) {

			// Make sure the requested tab name is a valid one
			var tab_el = jQuery('#theme_tab_' + tabname);
			var view_el = jQuery('#theme_view_' + tabname);
			if (! (tab_el && view_el)) return(false);

			// If there's already a tab showing then hide it
			if (this.theme_tab_showing.length) {
				jQuery('#theme_view_' + this.theme_tab_showing).removeClass('active').addClass('inactive');
				jQuery('#theme_tab_' + this.theme_tab_showing).removeClass('active').addClass('inactive');
			}

			// Now show and remember the requested tab
			this.theme_tab_showing = tabname;
			view_el.removeClass('inactive').addClass('active');
			tab_el.removeClass('inactive').addClass('active');

			return(true);
		},

		theme_color_swatch: function(num) {
			var color_code = this.color2hex(jQuery('#theme_color_swatch_' + num).css('background-color'));

			jQuery('#theme_color_code').val(color_code);
			this.theme_color_preview_update();
		},

		theme_color_preview_update: function() {
			var color_code = jQuery('#theme_color_code').val();
			jQuery('#theme_color_preview').css('background-color', '#' + color_code);
		},

		color2hex: function (color_code) {

			// In FF, the color code comes out as 'rgb(RRR, GGG, BBB)'
			var rgb;
			if (rgb = color_code.match(/^rgb\((\d+),\s*(\d+),\s*(\d+)\)$/)) {
				return(this.hex(rgb[1]) + this.hex(rgb[2]) + this.hex(rgb[3]));
			}

			// In IE, the color code comes out as '#RRGGBB'
			else if (color_code.match(/^#/)) {
				color_code = color_code.substring(1);
				return(color_code);
			}

			// Some other encoding scheme?
			return('000000');
		},

		hex: function (x) {
			var hexDigits = new Array('0','1','2','3','4','5','6','7','8','9','a','b','c','d','e','f'); 
			return isNaN(x) ? "00" : hexDigits[(x - x % 16) / 16] + hexDigits[x % 16];
		}
	};
});

