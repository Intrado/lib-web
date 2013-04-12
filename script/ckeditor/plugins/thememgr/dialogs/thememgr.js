/**
 * thememgr plugin dialog
 *
 * The presentation layer uses all CKE native controls because using plain HTML
 * makes it difficult to regain access to editor and dialog objects without
 * using a mess of global variables. Suboptimal with respect to controlling the
 * appearance, however the mechanics all work which is the most important.
 *
 * SMK created 2013-01-16
 * Modified 2013-02-14 by Ben Hencke - trimmed down required metadata
 * SMK eliminated external "scratch" div dependency 2013-03-11
 */

(function ($) {
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

			scratch: 0,

			rcie: null,

			rcitheme_data: {},

			// The color being chosen for the color chooser
			choosingcolor: -1,

			// The first thing that happens
			onShow: function() {
				var ui = '';
				var myself = CKEDITOR.dialog.getCurrent().definition;

				try {

					// Check some prerequisites
					var doc = this.getElement().getDocument(); // ckeditor.js
					if (! doc) {
						// Failed to getDocument() from CKE
						alert('Oops! Internal Error (4)');
						throw '';
					}

					var e = doc.getById('thememgr_content'); // ckeditor.js
					if (! e) {
						// Missing content div "thememgr_content" (??);
						alert('Oops! Internal Error (2)');
						throw '';
					}

					// Depending on iframe/containment of the editor, rcieditor may be
					// here or up one parent level; grab a refernece to it either way
					if (typeof(rcieditor) == 'object') {
						myself.rcie = rcieditor;
					}
					else myself.rcie = window.parent.rcieditor;

					if (typeof(myself.rcie) !== 'object') {
						// rcieditor object not found; this plugin requires rcieditor.js to be included
						alert('Oops! Internal Error (3)');
						throw '';
					}

					// Initialize scratch space as a jQuery object that we can manipulate
					myself.scratch = $('<div></div>');

					// grab the entire contents of the document being edited
					var content = editor.getData();

					// and stick it into the scratch space for the editor where we can do some
					// DOM work on it; jQuery can access it here, without painful iframe extension
					myself.scratch.empty().html(content);

					// Scan for and get a list of all the rcithemed elements within the document
					myself.rcitheme_data = myself.theme_scan(myself.scratch);

					// If there are no theme data items
					if (! myself.rcitheme_data.count) {
						ui = 'This stationery has not been enabled with theme properties.';	
					}
					else {

						// Break the list of theme items up by type; we only have a color selection type for now
						var ui_components = {
							colors: ''
						};

						// A collections of tab/views that we will put our ui components into
						var ui_tabs = '';
						var ui_views = '';
						var ui_code = '';

						// Add some functions that we can use
						var tscall = 'CKEDITOR.dialog.getCurrent().definition.theme_tab_show';

						// If there are any theme colors
						if (myself.rcitheme_data.color.size()) {

							// Add (another) tab/view for color selection
							var choosercall = 'CKEDITOR.dialog.getCurrent().definition.theme_color_chooser_show';
							var chooserexit = 'CKEDITOR.dialog.getCurrent().definition.theme_color_chooser_exit';
							var chooserpick = 'CKEDITOR.dialog.getCurrent().definition.theme_color_chooser_pick';
							ui_tabs += '<span id="theme_tab_newcolors" class="inactive" onclick="' + tscall + '(\'newcolors\');">&nbsp;&nbsp;COLORS&nbsp;&nbsp;</span>';
							ui_views += '<div id="theme_view_newcolors" class="theme_view inactive">';
							ui_views += '<div id="theme_view_newcolors_hider" style="display: none;">&nbsp;</div>';
							for (var jj = 0; jj < myself.rcitheme_data.color.size(); jj++) {
								ui_views += '<div class="coloritem" onclick="' + choosercall + '(' + jj + ');">';
								var value = '';
								var themeid = myself.rcitheme_data.color[jj];
								if (myself.rcitheme_data.value[themeid].length) {
									value = ' style="background-color: #' + myself.rcitheme_data.value[themeid] + ';"';
								}
								ui_views += '	<div class="swatch" id="theme_view_newcolors_swatch_' + jj + '"' + value + '>&nbsp;</div>';
								ui_views += 	themeid;
								ui_views += '</div>';
							}
							ui_views += '<div id="theme_view_newcolors_chooser" style="display: none;">';
							ui_views += '<span style="color: white; margin-left: 5px;">Choose a color to apply</span>';
							ui_views += '<span class="chooserexit" onclick="' + chooserexit + '();">X</span>';
							ui_views += '<div class="chooserswatches">';
							var csnum = 0;
							for (var rr = 0; rr < 256; rr += 0x33) {
								for (var gg = 0; gg < 256; gg += 0x33) {
									for (var bb = 0; bb < 256; bb += 0x33) {
										var rgb = myself.hex(rr) + myself.hex(gg) + myself.hex(bb);
										ui_views += '<div class="chooser_swatch" id="theme_newcolors_swatch_' + csnum + '" style="background-color: #' + rgb + ';" onclick="' + chooserpick + '(' + csnum + ');">&nbsp;</div>';
										csnum++;
									}
								}
								//ui_views += '<br clear="all"/>';
							}
							ui_views += '</div>';
							ui_views += '</div>';
							ui_views += '<br clear="all"/>';
							ui_views += '</div>';

						}
						else {
							// we shouldn't be able to get here - colors are our only option right now
							ui = 'no theme selection output';
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
						ui += '		div.themetabs > span { font-size: 10px; font-weight: bold; margin: 5px 0px; cursor: pointer; }';
						ui += '		div.themetabs > span.inactive { color: #666666; background-color: inherit; }';
						ui += '		div.themetabs > span.active { color: #0066CC; background-color: #DDD; }';
						ui += '		div.themeviews { background-color: #DDD; }';
						ui += '		div.themeviews > div.inactive { display: none; }';
						ui += '		div.themeviews > div.active { display: block; }';
						ui += '		div.themeviews div.viewstep { padding: 5px 10px; }';
						ui += '		div.themeviews input, div.themeviews select { background-color: white; border: 1px solid #999; }';

						// New Colors Tab
						ui += '		div#theme_view_newcolors { position: relative; height: 120px; }';
						ui += '		div#theme_view_newcolors_hider { position: absolute; top: 0px; left: 0px; z-index: 1; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); }';
						ui += '		div#theme_view_newcolors > div.coloritem { cursor: pointer; clear: both; height: 16px; }';
						ui += '		div#theme_view_newcolors > div.coloritem:hover { background-color: #99CCFF; font-weight: bold; }';
						ui += '		div#theme_view_newcolors > div.coloritem > div.swatch { float: left; width: 12px; height: 12px; margin: 0px 3px; font-size: 1px; border: 1px solid #666; cursor: pointer; }';
						ui += '		div#theme_view_newcolors_chooser { position: absolute; top: 7px; right: 10px; z-index: 2; border: 1px solid #666; background-color: #999; }';
						ui += '		div#theme_view_newcolors_chooser > span.chooserexit { position: absolute; top: 2px; right: 2px; width: 12px; height: 12px; border: 1px solid #FFF; font-weight: bold; background-color: #666; color: white; text-align: center; cursor: pointer; }';
						ui += '		div#theme_view_newcolors_chooser > div.chooserswatches { width: 360px; height: 60px; padding: 0px; margin: 5px; border: 1px solid #666; }';
						ui += '		div#theme_view_newcolors_chooser > div.chooserswatches > div.chooser_swatch { float: left; margin: 0px; height: 10px; width: 10px; font-size: 1px; cursor: pointer; }';
						ui += '</style>';
					}
				}
				catch (msg) {
					if (msg.length) {
						ui = 'Oops! ' + msg;
					}
					else {
						// Empty message for internal errors
						return;
					}
				}

				e.setHtml('<div>' + ui + '</div>');

				myself.theme_tab_show('newcolors');
			},

			theme_color_chooser_show: function(num) {
				this.choosingcolor = num;
				$('#theme_view_newcolors_hider').css('display', 'block');
				$('#theme_view_newcolors_chooser').css('display', 'block');
			},

			theme_color_chooser_exit: function() {
				$('#theme_view_newcolors_chooser').css('display', 'none');
				$('#theme_view_newcolors_hider').css('display', 'none');
				this.choosingcolor = -1;
			},

			theme_color_chooser_pick: function(num) {
				var color_code = this.color2hex($('#theme_newcolors_swatch_' + num).css('background-color'));
				var el = $('#theme_view_newcolors_swatch_'+ this.choosingcolor);
				el.css('background-color', '#' + color_code);
				el.attr('data-modified', '1');
				this.theme_color_chooser_exit();
			},

			// The last thing that happens
			onOk: function() {
				var myself = CKEDITOR.dialog.getCurrent().definition;

				try {

					// Only the active theme_tab is used upon submission
					switch (myself.theme_tab_showing) {
						case 'newcolors':
							for (var jj = 0; jj < myself.rcitheme_data.color.size(); jj++) {
								var el = $('#theme_view_newcolors_swatch_' + jj);
								var bgcolor = el.css('background-color');
								if (parseInt(el.attr('data-modified')) && (bgcolor != 'transparent')) {
									var color_code = myself.color2hex(bgcolor);
									var res = myself.theme_scan(myself.scratch, myself.rcitheme_data.color[jj], '#' + color_code);
									if (! res) break;
								}
							}
							break;

						// Add other tab submissions like so:
						case 'sample':
							break;
					}

					// For setting, we need to put our scratch area back into the document
					if (res) {
						var content = myself.scratch.html();
						editor.setData(content);
					}

					return(true);
				}
				catch (msg) {
					if (msg.length) {
						alert('Oops! ' + msg);

						// Prevent the dialog from closing since we got here due to incomplete selections
						return(false);
					}
				}

				return(true);
			},

			/**
			 * Scan the container for themed elements and either collect or set data
			 *
			 * Find elements within container that look something like these:
			 * ref: http://html5doctor.com/html5-custom-data-attributes/
			 * 
			 * The following data attributes are defined:
			 * data-sm-i: controls which color name to use (id)
			 * data-sm-a: this tells us to set the specified attribute
			 * data-sm-s: this tells us to set the specified style
			 *
			 * Element with legacy HTML attribute control
			 *     <element data-sm-i="Main Color 1" data-sm-a="bgcolor" bgcolor="#FFFFFF"> 
			 *
			 * Element with style property control
			 * 	    <element data-sm-i="Main Color 1" data-sm-s="background-color" style="background-color: #FFFFFF;"> 
			 * 
			 * @param container jQuery element containing the document elements to scan
			 * @param theme_id string Optional theme identifier to scan the document for
			 * @param theme_value string Optional theme value to set the identifier to when found
			 *
			 * @return Object array if in collection mode, or nothing
			 */
			theme_scan: function(container, theme_id, theme_value) {

				try {
					var myself = CKEDITOR.dialog.getCurrent().definition;

					// Now are we collecting or setting?
					var collecting = ((typeof theme_id !== 'undefined') && (typeof theme_value !== 'undefined')) ? false : true; 

					var rcitheme_data = {
						count: 0,
						color: Array(),
						value: Array(),

						// Add support for other theme data types here as needed
						add_color: function (id, value) {

							// Prevent the addition of an empty string as a selection id
							if (! id.length) return;

							// Prevent insertion of duplicate theme color id's
							for (var ii = 0; ii < this.count; ii++) {
								if (this.color[ii] == id) return;
							}
							this.color.push(id);
							this.count++;
							this.value[id] = value;
						}
					};

					// Find all elements in the container with the data-rcitheme attribute
					//if not in collecting mode, get only matching ids
					var elements = $(collecting ? '[data-sm-i]' : '[data-sm-i="' + theme_id + '"]', container);

					if (typeof elements === 'undefined') {
						throw 'No themed elements in this document!';
					}

					// For each element that we found...
					elements.each(function () {
						var e = $(this);

						var id = e.attr("data-sm-i"); //id
						var style = e.attr("data-sm-s"); //style
						var attribute = e.attr("data-sm-a"); //attribute
						
						if (collecting) {
							var value = '';
							if (style) value = e.css(style);
							else if (attribute) value = e.attr(attribute);
							rcitheme_data.add_color(id, myself.color2hex(value));
						} else {
							if (style) e.css(style, theme_value);
							if (attribute) e.attr(attribute, theme_value);
						}
					});

					// if we are collecting
					if (collecting) {

						// then we expect to have something to return
						if (! rcitheme_data.count) {
							throw 'No theme data in this document!';
						}

						// Sort the theme elements by name
						rcitheme_data.color.sort();

						return(rcitheme_data);
					}
					else {
						// Otherwise, for setting, we need to put our scratch area back into the document
						return(true);
					}
				}
				catch (msg) {
					console && console.log && console.log(msg);
					throw 'This stationery cannot be used with the Theme Manager';
				}
			},

			theme_tab_showing: '',

			theme_tab_show: function(tabname) {

				// Make sure the requested tab name is a valid one
				var tab_el = $('#theme_tab_' + tabname);
				var view_el = $('#theme_view_' + tabname);
				if (! (tab_el && view_el)) return(false);

				// If there's already a tab showing then hide it
				if (this.theme_tab_showing.length) {
					$('#theme_view_' + this.theme_tab_showing).removeClass('active').addClass('inactive');
					$('#theme_tab_' + this.theme_tab_showing).removeClass('active').addClass('inactive');
				}

				// Now show and remember the requested tab
				this.theme_tab_showing = tabname;
				view_el.removeClass('inactive').addClass('active');
				tab_el.removeClass('inactive').addClass('active');

				return(true);
			},

			theme_color_swatch: function(num) {
				var color_code = this.color2hex($('#theme_color_swatch_' + num).css('background-color'));

				$('#theme_color_code').val(color_code);
				this.theme_color_preview_update();
			},

			theme_color_preview_update: function() {
				var color_code = $('#theme_color_code').val();
				$('#theme_color_preview').css('background-color', '#' + color_code);
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
}) (jQuery);

