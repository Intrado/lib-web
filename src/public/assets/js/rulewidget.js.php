<?
require_once(dirname(__FILE__) . "/../../../private/includes/subdircommon.inc.php");
require_once(dirname(__FILE__) . "/../../../private/includes/html.inc.php");

//set expire time to + 1 hour so browsers cache this file
header("Expires: " . gmdate('D, d M Y H:i:s', time() + 60*60) . " GMT"); //exire in 1 hour, but if theme changes so will hash pointing to this file
header("Content-Type: text/javascript");
header("Cache-Control: private");
?>

//pollyfill for old IE
if (!Array.isArray) {
  Array.isArray = function(arg) {
    return Object.prototype.toString.call(arg) === '[object Array]';
  };
}

//----------- RuleWidget EVENTS (This example shows all custom events) ------------
// NOTE: Your client should only allow rule-building if 'RuleWidget:Ready' is fired.
//var ruleWidget = new RuleWidget($('ruleWidgetContainer'));
//ruleWidget.container.observe('RuleWidget:Ready',..);
//ruleWidget.container.observe('RuleWidget:DeleteRule',..);
//ruleWidget.container.observe('RuleWidget:AddRule',..);
//ruleWidget.container.observe('RuleWidget:UpdateRule',..); //currently behaves as AddRule
//ruleWidget.container.observe('RuleWidget:InColumn',..);
//ruleWidget.container.observe('RuleWidget:ChangeField',..);
//ruleWidget.container.observe('RuleWidget:RemoveAllRules',..);
//ruleWidget.startup(); // Required, must be called AFTER registering ruleWidget.container.observe('RuleWidget:Ready',..)

var RuleWidget = Class.create({
	//----------------------------- PUBLIC FUNCTIONS --------------------------

	// @param container, the DOM container for this widget.
	// @param readonly, disables rule editor
	// @param, allowedFieldTypes, single letters, example: ['f','g','c']
	// @param, ignoredFields, specific fieldnums to ignore, example: ['c01']
	// @param, showRemoveAllButton, "Remove All Rules" button, fires "RuleWidget:RemoveAllRules"
	initialize: function(container, readonly, allowedFieldTypes, ignoredFields, showRemoveAllButton) {
		this.ruleEditorGuideContents = <?=json_encode(array(
			// Fieldmap
			'additionalChooseFieldmap' => _L('To add another filter rule select a field'), // Used instead of 'chooseFieldmap' if there are existing rules
			'chooseFieldmap' => _L('Select a field to filter on'),
			// Criteria
			'association' => _L('Select a comparison option'),
			'multisearch' => _L('Select a comparison option'),
			'reldate' => _L('Select a comparison option'),
			'text' => _L('Select a comparison option.'),
			'numeric' => _L('Select a comparison option'),
			// Value
			'association_in' => _L('Enter a value'),
			'multisearch_in' => _L('Enter a value'),
			'multisearch_not' => _L('Enter a value'),
			'reldate_eq' => _L('Enter a value'),
			'reldate_reldate' => _L('Enter a value'),
			'reldate_date_range' => _L('Enter a value'),
			'reldate_date_offset' => _L('Enter a value'),
			'reldate_reldate_range' => _L('Enter a value'),
			'text_eq' => _L('Enter a value'),
			'text_ne' => _L('Enter a value'),
			'text_sw' => _L('Enter a value'),
			'text_ew' => _L('Enter a value'),
			'text_cn' => _L('Enter a value'),
			'numeric_num_eq' => _L('Enter a value'),
			'numeric_num_ne' => _L('Enter a value'),
			'numeric_num_gt' => _L('Enter a value'),
			'numeric_num_ge' => _L('Enter a value'),
			'numeric_num_lt' => _L('Enter a value'),
			'numeric_num_le' => _L('Enter a value'),
			'numeric_num_range' => _L('Enter a value')
		))?>;

		// Guide/Focus
		this.guideDisabled = false;
		this.guideStepIndex = 0;
		this.guideFieldset = null;

		if (!allowedFieldTypes)
			this.allowedFieldTypes = ['f','g','c','organization'];
		else
			this.allowedFieldTypes = allowedFieldTypes;

		if (!ignoredFields)
			this.ignoredFields = [];
		else
			this.ignoredFields = ignoredFields;

		this.container = container;
		this.warningDiv = new Element('div', {'class' : 'ruleWidgetWarning'});
		this.warningDiv.hide();
		this.container.insert(this.warningDiv);
		this.ruleHelperDiv = new Element('div', {'class':'ruleWidgetHelper'});
		this.ruleHelperContentDiv = new Element('div');
		this.ruleHelperInfoDiv = new Element('div');

		this.ruleHelperDiv.insert(this.ruleHelperContentDiv).insert(this.ruleHelperInfoDiv);

		this.rulesTableAboveEditor = new Element('tr'); // Right on top of rulesTableFootLastTR
		this.rulesTableFootLastTR = new Element('tr'); // For rule editor
		this.rulesTableHead = new Element('thead');
		this.rulesTableBody = new Element('tbody');
		this.container
			.insert(new Element('table', {'class':'rulewidgetTable'})
				.insert(this.rulesTableHead)
				.insert(this.rulesTableBody)
				.insert(new Element('tfoot')
					.insert(this.rulesTableAboveEditor)
				)
			)
			.insert(this.ruleHelperDiv)
			.insert(new Element('table')
				.insert(new Element('tbody')
					.insert(this.rulesTableFootLastTR)
				)
			);

		if (showRemoveAllButton) {
			var td = new Element('td', {'colspan':100}).update(action_link('<?=addslashes(_L('Remove All Rules'))?>', 'diagona/16/101', '').observe('click', function(event) {
				event.stop();
				this.container.fire('RuleWidget:RemoveAllRules');
			}.bindAsEventListener(this)).setStyle({'margin':'0'}));
			td.down('img').remove(); // No icon necessary
			this.rulesTableHead.insert(new Element('tr').insert(td));
		}

		if (!readonly)
			this.ruleEditor = new RuleEditor(this, this.rulesTableFootLastTR);
		this.clear_rules();

		this.delayActions = false;

		this.fieldmaps = null;
		this.operators = null;
		this.reldateOptions = null;
		this.multisearchDomCache = {}; // Cache of multisearch DOM, indexed by fieldnum.
		this.associationTitles = {}; // Cache of titles for associations.
		this.languagemap = {}; // Cache of language code => language name.
		this.languagefield = ''; // Identifies which field is for language.
	},

	// You must call startup() AFTER registering ruleWidget.container.observe('RuleWidget:Ready', ..);
	// @param preloadedRules, optional array of rules to preload
	startup: function(preloadedRules) {
		if (preloadedRules && !preloadedRules.join)
			preloadedRules = null;

		cachedAjaxGet('ajax.php?type=rulewidgetsettings',
			function(transport, rules) {
				var data = transport.responseJSON;
				if (!data) {
					// Silent failure, client should not provide any rule-building tools unless the event RuleWidget:Ready is fired.
					//alert('<?=addslashes(_L('Sorry cannot get fieldmaps'))?>');
					return;
				}
				
				this.languagefield = data.languagefield;
				this.languagemap = data.languagemap;
				
				this.operators = data['operators'];
				this.reldateOptions = data['reldateOptions'];
				this.fieldmaps = {};
				
				// data['fieldmaps'] is indexed by record id, we prefer indexing by fieldnum.
				// This is for all f, g and c fields
				var fieldnum = "";
				for (var i in data['fieldmaps']) {
					fieldnum = data['fieldmaps'][i].fieldnum;
					
					// Specific field types are allowed. Skip any not included in allowedFieldTypes
					// Test first letter
					if (this.allowedFieldTypes.indexOf(fieldnum.charAt(0)) < 0)
						continue;
					// Specific fields may be ignored. skip any in ignoredFields
					// Test fieldnum name
					if (this.ignoredFields.indexOf(fieldnum) >= 0)
						continue;

					this.fieldmaps[fieldnum] = data['fieldmaps'][i];
					for (var type in this.operators) {
						if (this.fieldmaps[fieldnum].options.match(type))
							this.fieldmaps[fieldnum].type = type;
					}
				}

				// The customer has organizations so show a selector for it if organization type fields are allowed
				if (data.hasorg && this.allowedFieldTypes.indexOf('organization') >= 0) {
					this.fieldmaps['organization'] = {};
					this.fieldmaps['organization'].type = 'association';
					this.fieldmaps['organization'].name = '<?=addslashes(escapehtml(getSystemSetting("organizationfieldname","Organization")))?>';
				}
				
				// Add "in" to the association type
				this.operators['association'] = {};
				this.operators['association']['in'] = '<?=addslashes(_L('is'))?>';

				// Add "is not" to the multisearch operators.
				this.operators['multisearch']['not'] = '<?=addslashes(_L('is NOT'))?>';
				this.operators['multisearch']['in'] = '<?=addslashes(_L('is'))?>';
				if (this.ruleEditor)
					this.ruleEditor.reset();

				// preloaded rules
				var someUnused = false;
				if (rules) {
					for (var i = 0; i < rules.length; ++i) {
						if (!rules[i].fieldnum)
							break; // Bad data.

						if (rules[i].fieldnum && !this.fieldmaps[rules[i].fieldnum]) {
							if (this.ruleEditor) {
								someUnused = true;
							}
							continue;
						}
						this.insert_rule(rules[i], true);
					}
				}
				if (someUnused) {
					this.warningDiv.update('<?=addslashes(_L("WARNING: Some rules are not visible due to security restrictions or system configuration."))?>');
					this.warningDiv.show();
				}
				
				this.container.fire('RuleWidget:Ready');
			}.bindAsEventListener(this, preloadedRules ? preloadedRules : null)
		);
	},

	clear_rules: function() {
			this.appliedRules = {};
			this.rulesTableBody.update();
			if (this.ruleEditor)
				this.ruleEditor.reset();
	},

	// Updates contents of tr with human-readable fieldmapTD, criteriaTD, and valueTD.
	// @param data, {fieldnum, type, logical, op, val}
	// @param tr, table row DOM element.
	// @param addHiddenFieldnum, optional boolean specifying to add a hidden input with value=fieldnum
	format_readable_rule: function(data, tr, addHiddenFieldnum) {
		if (!data.fieldnum || !data.op || !data.logical) {
			if (data.fieldnum == "organization") {
				data.op = "in";
				data.logical = "and";
			} else {
				return false;
			}
		}
		if (!this.fieldmaps[data.fieldnum])
			return false;
		if (!data.type)
			data.type = this.fieldmaps[data.fieldnum].type;

		// FieldmapTD
		var fieldmapTD = new Element('td', {'class':'list'}).insert(this.fieldmaps[data.fieldnum].name);
		// Keep track of the row's data.fieldnum by using a hidden input.
		if (addHiddenFieldnum)
			fieldmapTD.insert(new Element('input', {'type':'hidden', 'value':data.fieldnum}));
		
		// CriteriaTD
		var criteriaTD = new Element('td', {'class':'list'});
		var criteria = this.operators[data.type][data.op];
		if (data.op == 'in') {
			criteria = '<?=addslashes(_L('is'))?>';
			if (data.logical == 'and not')
				criteria = '<?=addslashes(_L('is NOT'))?>';
		}
		criteriaTD.insert(criteria);

		// ValueTD
		var value = this.format_readable_value(data);
		var widthCSS = (addHiddenFieldnum) ? '  ' : ' width:80px; ';
		var heightCSS = (value.length > 400) ? ' height: auto; ' : '';
		if (addHiddenFieldnum)
		// heightCSS += ' overflow: auto; ';
		//var valueDiv = new Element('div', {'style': widthCSS + heightCSS}).update(value);
		var valueTD = new Element('td', {'class':'list'}).update(value);
		tr.insert(fieldmapTD).insert(criteriaTD).insert(valueTD);

		return true;
	},

	format_readable_value: function(data) {
		var value = '';

		// If data.val is not an array, then it is data passed in from RuleWidget::startup().
		if (typeof(data.val.join) == 'undefined') { // Called from RuleWidget::startup().
			if (data.type == 'association') {
				// data.val for 'association' is an object of value:title pairs.
				// We only want to display the titles.
				value = $H(data.val).values().join(',');
			} else if (data.type == 'multisearch') {
				if (data.fieldnum == this.languagefield) {
					codes = data.val.split('|');
					names = [];
					for (var i = 0, count = codes.length; i < count; i++) {
						var code = codes[i];
						names.push(this.languagemap[code] || code);
					}
					value = names.join(',');
				} else {
					value = data.val.replace(/\|/g, ',');
				}
			} else if (data.op == 'reldate') {
				value = this.reldateOptions[data.val];
			} else {
				value = data.val.replace(/\|/g, ' <?=addslashes(_L('and'))?> ');
			}
		} else if (data.type == 'association') { // Called from "Add" button.
			// data.val is an array of IDs, but we want to show the titles instead.
			// Example: for organization, data.val would be an array of organization IDs, but we want to display the orgkeys instead.
			var titles = [];
			var associationTitles = this.associationTitles[data.fieldnum];
			
			for (var i = 0, count = data.val.length; i < count; i++) {
				titles.push(associationTitles[data.val[i]]);
			}
			
			value = titles.join(',');
		} else if (data.type == 'multisearch') { // Called from "Add" button.
			if (data.fieldnum == this.languagefield) {
					names = [];
					for (var i = 0, count = data.val.length; i < count; i++) {
						var code = data.val[i];
						names.push(this.languagemap[code] || code);
					}
					value = names.join(',');
			} else {
				value = data.val.join(',');
			}
		} else { // Called from "Add" button.
			value = data.val.join(' <?=addslashes(_L('and'))?> ');
		}
		
		return value.escapeHTML().replace(/,/g, ', ') + ' ';
	},

	refresh_guide: function (reset, specificFieldset) {
		var sectionFieldsets = this.rulesTableFootLastTR.select('fieldset');
		for (var i = 0; i < sectionFieldsets.length; i++) {
			var fieldset = sectionFieldsets[i];
			if (specificFieldset == fieldset)
				this.guideStepIndex = i;
			else {
				fieldset.style.borderWidth = '0';
				fieldset.down('div').style.margin = '0';
			}
		}
		if (sectionFieldsets.length < 1 || this.guideDisabled) {
			this.guideFieldset = null;
			return;
		}
		this.guideStepIndex = (reset) ? 0 : Math.min(sectionFieldsets.length-1, Math.max(0, this.guideStepIndex));
		var currentFieldset = sectionFieldsets[this.guideStepIndex];
		// Visual effect.
		currentFieldset.style.borderWidth = '0';
		currentFieldset.down('div').style.margin = '0';
		this.guideFieldset = currentFieldset;

		helpContent = null;
		// Guide Content

		var ruleCount = $H(this.appliedRules).keys().length;
		var data = this.ruleEditor.get_data();
		if (data) {
			if (currentFieldset.id == 'AddRuleCriteria') {
				helpContent = this.ruleEditorGuideContents[data.type];
			}  else if (currentFieldset.id == 'AddRuleValue') {
				// multisearch IS NOT
				if (data.logical == 'and not')
						data.op = 'not';
				helpContent = this.ruleEditorGuideContents[data.type + '_' + data.op];
			} else if (currentFieldset.id == 'AddRuleFieldmap') {
				if (ruleCount <= 0)
					helpContent = this.ruleEditorGuideContents['chooseFieldmap'];
				else
					helpContent = this.ruleEditorGuideContents['additionalChooseFieldmap'];
			}
		} else {
			var fieldmap = this.ruleEditor.get_selected_fieldmap();
			if (!fieldmap || currentFieldset.id == 'AddRuleFieldmap') {
				if (ruleCount <= 0)
					helpContent = this.ruleEditorGuideContents['chooseFieldmap'];
				else
					helpContent = this.ruleEditorGuideContents['additionalChooseFieldmap'];
			} else {
				helpContent = this.ruleEditorGuideContents[fieldmap.type];
			}
		}

		if (!helpContent) {
				return;
		}

		this.ruleHelperContentDiv.update(helpContent);
		this.ruleHelperContentDiv.addClassName('ruleWidgetHelperContent');
	},

	refresh_rules_table: function(latestData) {
		var rows = this.rulesTableBody.rows;
		for (var i = 0; i < rows.length; i++) {
			rows[i].cells[0].update('<?=addslashes(_L("Rule #"))?>' + (i+1));

			if (rows[i].cells.length == 5) {
				var hiddenField = rows[i].cells[1].down('input');
				if (hiddenField) {
					var fieldnum = hiddenField.getValue();
					if (latestData && fieldnum == latestData.fieldnum) {
						// cells[3] is ValueTD
						var valueDiv = rows[i].cells[3];
						if (valueDiv)
							valueDiv.update(this.format_readable_value(latestData));
					}
				}
			}
		}
	},

	// @param data, {fieldnum, type, logical, op, val}
	insert_rule: function(data, suppressFire) {
		var labels = {
			'delete':"<?=addslashes(icon_button(_L('Delete'), 'diagona/10/101'))?>",
			'edit':"<?=addslashes(icon_button(_L('Edit'), 'diagona/10/018'))?>",
			'update':"<?=addslashes(icon_button(_L('Update'), 'diagona/10/102'))?>",
			'cancel':"<?=addslashes(icon_button(_L('Cancel'), 'fugue/slash'))?>"
		};
		var needWarning = false;
		if (!data)
			needWarning = true;
		if (data.op && data.op == 'reldate' && (!data.val || !data.val.strip()))
			needWarning = true;
		if (needWarning) {
			alert('<?=addslashes(_L('Please specify a value'))?>');
			return false;
		}

		if (data.fieldnum == 'organization') {
			data.type = 'association'
			data.op = 'in';
			data.logical = 'and';
		}
		
		var tr = new Element('tr', { 'class' : 'listRow'});
		tr.appendChild(new Element('td', { 'class' : 'listHeading' }));
		if (!this.format_readable_rule(data, tr, true)) {
			if (!suppressFire)
				alert('<?=addslashes(_L('cannot add this rule'))?>');
			return false;
		}

		if (!this.delayActions || suppressFire) {
			// Actions
			if (this.ruleEditor) {
				var actionTD = new Element('td').update('<div>' + labels['delete']);
				var actionEditTD = this.create_edit_action(data, tr, labels);
				// Observe clicks on the "Remove" button.
				actionTD.down('button').observe('click', function(event, tr, fieldnum) {
					event.stop();

					if (!this.delayActions) {
						tr.remove();
						delete this.appliedRules[fieldnum];
						this.refresh_rules_table();
						if (this.ruleEditor)
							this.ruleEditor.reset();
					}

					this.refresh_guide(true);
					
					this.container.fire('RuleWidget:DeleteRule', {'fieldnum':fieldnum});
				}.bindAsEventListener(this, tr, data.fieldnum));

				tr.insert(actionEditTD);
				tr.insert(actionTD);
			}
			
			this.rulesTableBody.insert(tr);
			this.convert_values(data);
			this.appliedRules[data.fieldnum] = data;
			
			this.refresh_rules_table();
			if (this.ruleEditor)
				this.ruleEditor.reset();
		}
		
		if (!suppressFire) {
			this.refresh_guide();
			this.container.fire('RuleWidget:AddRule', {
				'ruledata': $H(data)
			});
		}
		
		return true;
	},
	create_edit_action: function (data, tr, labels) {
		var ruleTable = this.rulesTableBody;
		function disableEdit(disable){
			ruleTable.select('.edit-button button').each(function(b){
				if(disable)
					b.disable();
				else
					b.enable();
			});
		}
		var actionEditTD = new Element('td').update('<div class="edit-button">' + labels['edit']);
		// Observe clicks on the "Remove" button.
		actionEditTD.down('button').observe('click', function (event, tr, fieldnum, type) {
			event.stop();
			var rowElements = tr.childElements();
			var selectedOptions = (type == 'multisearch' && !Array.isArray(data.val)) ? data.val.split('|') : data.val;
			var container = this.ruleEditor.create_rule_section(fieldnum, type, data.op, selectedOptions);
			var readableValues = rowElements[3].clone(true);
			rowElements[3].update(container);
			var updateTD = new Element('td').update('<div class="update-button">' + labels['update']);
			var cancelTD = new Element('td').update('<div class="cancel-button">' + labels['cancel']);

			rowElements[4].replace(updateTD);
			tr.insert(cancelTD);
			disableEdit(true);
			updateTD.down('button').observe('click', function (event) {
				event.stop();
				var vals = this.ruleEditor.get_values(type, container);
				data.val = vals;
				this.convert_values(data);
				container.fire('RuleWidget:UpdateRule', {
					'ruledata': $H(data)
				});
			}.bindAsEventListener(this));

			cancelTD.down('button').observe('click', function (event) {
				event.stop();
				var rowElements = tr.childElements();
				rowElements[4].replace(actionEditTD);
				rowElements[3].replace(readableValues);
				cancelTD.remove();
				disableEdit(false);
			}.bindAsEventListener(this));

		}.bindAsEventListener(this, tr, data.fieldnum, data.type));

		return actionEditTD;
	},
	convert_values: function (data) {
		// If this is an association, we want to convert data.val into an array of IDs if it is currently an object of value:title pairs.
		// This is so the validator does not need to worry about whether data.val is an array or an object of value:title pairs.
		if (data.type == 'association' && typeof(data.val.join) == "undefined") {
			var ids = [];
			for (var id in data.val) {
				ids.push(id);
			}
			data.val = ids;
		}
	},
	// Returns json-encoded array of rules.
	// Example: [{fieldnum:"f01", type:"text", logical:"and", op:"eq", val:"Kee-Yip"}]
	toJSON: function(data) {
		var jsondata = $H(this.appliedRules).values();
		return Object.toJSON(jsondata);
	}
});

var RuleEditor = Class.create({

	labels: {
		'and':'<?=addslashes(_L("and"))?>',
		'notfound':'<?=addslashes(_L("No data found"))?>'
	},
	//----------------------------- PUBLIC FUNCTIONS --------------------------

	// @param ruleWidget, the parent RuleWidget.
	// @param containerTR, the DOM container TR for this editor.
	initialize: function(ruleWidget, containerTR) {
		this.ruleWidget = ruleWidget;

		this.fieldTD = new Element('td');
		this.criteriaTD = new Element('td');
		this.valueTD = new Element('td');
		this.actionTD = new Element('td');
		if (!this.ruleWidget.noHelper) {
			this.fieldTD.update('<span style="cursor:help; font-style:italic; font-weight: bold;"><?=addslashes(_L('Field'))?></span>');
			this.criteriaTD.update('<span style="cursor:help; font-style:italic; display:none; font-weight: bold;"><?=addslashes(_L('Criteria'))?></span>');
			this.valueTD.update('<span style="cursor:help; font-style:italic; display:none; font-weight: bold;"><?=addslashes(_L('Value'))?></span>');
			this.actionTD.update('<span style="cursor:help; font-style:italic; display:none; font-weight: bold;">&nbsp;</span>');
		}

		var fieldsetCSS = '';
		var fieldsetDivOptions = {};
		this.fieldTD.insert(new Element('div', {'class':'RuleWidgetColumnDiv'})).insert(new Element('fieldset', {'id':'AddRuleFieldmap'}).insert(new Element('div', fieldsetDivOptions)));
		this.criteriaTD.insert(new Element('div', {'class':'RuleWidgetColumnDiv'})).insert(new Element('fieldset', {'id':'AddRuleCriteria'}).insert(new Element('div', fieldsetDivOptions)));
		this.valueTD.insert(new Element('div', {'class':'RuleWidgetColumnDiv'})).insert(new Element('fieldset', {'id':'AddRuleValue'}).insert(new Element('div', fieldsetDivOptions)));
		this.actionTD.insert(new Element('div', {'class':'RuleWidgetColumnDiv'})).insert(new Element('fieldset', {'id':'AddRuleAction'}).insert(new Element('div', fieldsetDivOptions)));

		containerTR.insert(this.fieldTD).insert(this.criteriaTD).insert(this.valueTD).insert(this.actionTD);

		this.fieldTD.down('span').observe('click', this.trigger_event_in_column.bindAsEventListener(this, this.fieldTD));
	},

	trigger_event_in_column: function(nullableEvent, td) {
		if (this.datepickers) {
			this.datepickers.invoke('close');
		}

		if (nullableEvent && nullableEvent.element().tagName.toUpperCase() == 'LABEL')
			return;
		var column = '';
		if (td == this.fieldTD)
			column = 'field';
		else if (td == this.criteriaTD)
			column = 'criteria';
		else if (td == this.valueTD)
			column = 'value';
		else if (td == this.actionTD)
			column = 'action';
		else
			return;

		if (column != 'action')
			this.ruleWidget.refresh_guide(false, td.down('fieldset'));
		this.ruleWidget.container.fire('RuleWidget:InColumn', {'td':td, 'column':column});
	},

	get_selected_fieldmap: function() {
		if (!this.fieldTD.down('select'))
			return false;
		var fieldnum = this.fieldTD.down('select').getValue();

		if (!this.ruleWidget.fieldmaps[fieldnum])
			return false;
		var type = this.ruleWidget.fieldmaps[fieldnum].type;

		return {'fieldnum': fieldnum, 'type': type};
	},

	// Returns data for the rule, {fieldnum, type, logical, op, val}
	get_data: function() {
		var fieldmap = this.get_selected_fieldmap();
		if (!fieldmap)
			return false;
			
		var logical = 'and';
		var op;
		
		if (this.criteriaTD.down('input')) {
			var selected = this.criteriaTD.down('input:checked');
			if (!selected)
				return false;
			
			var op = selected.getValue();
			if (op == 'not') {
				logical = 'and not';
				op = 'in';
			}
		} else { // association
			var op = 'in';
		}
		var val = this.get_values(fieldmap.type, this.valueTD);
		return {'fieldnum':fieldmap.fieldnum,
			'type':fieldmap.type,
			'logical':logical,
			'op':op,
			'val':val
		};
	},
	get_values: function (type, valueTD) {
		var val = [];

		// MULTISEARCH or association
		if (type == 'multisearch' || type == 'association') {
			var multisearchValues = [];
			if (valueTD.down('input')) {
				var checkboxes = valueTD.select('input:checked');
				var count = checkboxes.length;
				for (var i = 0; i < count; ++i) {
					var checkbox = checkboxes[i];
					multisearchValues.push(checkbox.value);
				}
			} else {
				var select = valueTD.down('select');
				if (select)
					multisearchValues.push(select.getValue());
			}
			val = multisearchValues;
		} else {
			// RELDATE_RELDATE
			if (valueTD.down('input[type="radio"]')) {
				var radio = valueTD.down('input:checked');
				if (radio)
					val = radio.getValue();
				else
					val = '';
			} else {
				// TEXT, NUMERIC, RELDATE_*
				var inputs = valueTD.select('input');
				if (inputs.length == 1) {
					val = inputs[0].getValue().strip();
				} else if (inputs.length > 1) {
					for (var i = 0; i < inputs.length; ++i)
						val.push(inputs[i].getValue().strip());
				}
			}
		}
		return val;
	},

	//----------------------------- PRIVATE FUNCTIONS --------------------------

	show_criteria_column: function(fieldnum) {
		if (this.datepickers) {
			this.datepickers.invoke('close');
		}

		var section = this.criteriaTD.down('fieldset').down('div');
		if (!fieldnum) {
			section.update();
			this.criteriaTD.down('span').stopObserving('click').hide();
			return;
		}

		var type = this.ruleWidget.fieldmaps[fieldnum].type;
		var criteriaSelectbox = this.make_radioboxes(this.ruleWidget.operators[type]);
		section.update(criteriaSelectbox);

		// radio buttons
		var radiobuttons = criteriaSelectbox.select('input');
		
		// if this has only one possible criteria, there is no radiobutton. just load values
		if (radiobuttons.length == 0){
			this.show_value_column(this.get_selected_fieldmap().fieldnum);
			// set help hint to value column
			this.trigger_event_in_column(null, this.valueTD);
		// if there are criteria to choose from...
		} else {
			// set help hint to criteria column
			this.trigger_event_in_column(null, this.criteriaTD);
			// Invoke onclick for each radiobox.
			radiobuttons.invoke('observe', 'click', function(event) {
				var fieldmap = this.get_selected_fieldmap();
				// replace the value column, but only if you have to.
				if (fieldmap.type != 'multisearch' || (!this.valueTD.down('input') && !this.valueTD.down('select'))) {
					this.show_value_column(fieldmap.fieldnum);
				}
				// set help hint to value column
				this.trigger_event_in_column(null, this.valueTD);
			}.bindAsEventListener(this));
		}

		// fire event in criteria column if user clicks on the label
		this.criteriaTD.down('span').show().observe('click', this.trigger_event_in_column.bindAsEventListener(this, this.criteriaTD));
	},

	// Determines the appropriate input boxes to show, makes an ajax request for persondatavalues if necessary for multisearch.
	show_value_column: function(fieldnum) {
		if (this.datepickers) {
			this.datepickers.invoke('close');
		}

		var section = this.valueTD.down('fieldset').down('div');
		if (!fieldnum) {
			section.update();
			this.show_action_column(true);
			this.valueTD.down('span').stopObserving('click').hide();
			return false;
		}

		var type = this.ruleWidget.fieldmaps[fieldnum].type;
		// get the available operators for this type
		var operators = $H(this.ruleWidget.operators[type]).values();
		var op = null;

		// if it could be more than one, check which is selected
		if (operators.size() > 1) {
			op = this.criteriaTD.down('input:checked');
			if (!op)
				return;
			op = op.getValue();
		// otherwise just grab the first value. (there is only one)
		} else {
			op = operators[0];
		}

		this.ruleWidget.container.style.width = 'auto';
		var container = this.create_rule_section(fieldnum, type, op, null);
		this.show_action_column()
		section.update(container);

		this.valueTD.down('span').show().observe('click', this.trigger_event_in_column.bindAsEventListener(this, this.valueTD));
	},
	create_rule_section: function (fieldnum, type, op, selectedOptions) {
		var container = new Element('div'),
			selectedValue = selectedOptions === null ? '' : selectedOptions, //current selected value
			vals = selectedValue.indexOf('|') > -1 ? selectedValue.split("|") : ['', '']; //for range values

		switch (type) {
			case 'association':
			// fall through, shares cache and ajax call with multisearch
			case 'multisearch':
				container.setStyle({'border': 'solid 1px gray', 'background': 'white', 'padding': '2px'});
				container.update('<img src="assets/img/ajax-loader.gif"/>');
				if (this.ruleWidget.multisearchDomCache[fieldnum]) {
					container.update(this.ruleWidget.multisearchDomCache[fieldnum]);
					this.add_multicheckbox_toolbar(container);
				} else {
					var data = this.fetch_field_data(fieldnum, null);
					if (!data) {
						container.insert('<div>' + this.labels['notfound'] + '</div>');
						return;
					}

					if (type == 'association')
						this.ruleWidget.associationTitles[fieldnum] = data;

					var multicheckboxDom = this.make_multicheckbox(data, selectedOptions);
					container.update(multicheckboxDom);
					this.ruleWidget.multisearchDomCache[fieldnum] = container.innerHTML;
					this.add_multicheckbox_toolbar(container);
				}
				break;
			case 'numeric':
				if (op == 'num_range') {
					container.update(this.make_textbox(vals[0], true));
					container.insert('<div>' + this.labels['and'] + '</div>');
					container.insert(this.make_textbox(vals[1], true));
				} else {
					container.update(this.make_textbox(selectedValue, true));
				}
				break;
			case 'reldate':
				if (op == 'reldate') {
					var selectbox = this.make_radioboxes(this.ruleWidget.reldateOptions, false, selectedValue);
					container.update(selectbox);
				} else if (op == 'eq' || op == 'date_range') {
					if (op == 'date_range') {
						container.update(this.make_datebox(vals[0]));
						container.insert('<div>' + this.labels['and'] + '</div>');
						container.insert(this.make_datebox(vals[1]));
					} else {
						container.update(this.make_datebox(selectedValue));
					}
				} else if (op == 'date_offset' || op == 'reldate_range') {
					if (op == 'reldate_range') {
						container.update(this.make_textbox(vals[0], true));
						container.insert('<div>' + this.labels['and'] + '</div>');
						container.insert(this.make_textbox(vals[1], true));
					} else {
						container.update(this.make_textbox(selectedValue, true));
					}
				}
				break;
			case 'text':
				container.update(this.make_textbox(selectedValue));
				break;
		}
		return container;
	},
	fetch_field_data: function (fieldnum, callback) {
		var results = {};
		var url = 'ajax.php?type=getdatavalues&fieldnum=' + fieldnum;
		new Ajax.Request(url, {
			asynchronous: (callback !== null),
			onSuccess: function (transport) {
				results = transport.responseJSON;
				if (callback)
					callback(results);
			}
		});
		return results;
	},

	show_action_column: function(clear) {
		if (clear) {
			this.actionTD.down('fieldset').down('div').update();
			this.actionTD.down('span').stopObserving('click').hide();
			return;
		}

		this.actionTD.down('fieldset').down('div').update('<?=addslashes(icon_button(_L('Add'), 'add'))?><div style="width:100px; clear:both"></div>');
		var addRuleButton = this.actionTD.down('button');
		
		// Events
		addRuleButton.observe('click', function(event) {
				this.ruleWidget.insert_rule(this.get_data());
		}.bindAsEventListener(this));
		addRuleButton.observe('focus', this.trigger_event_in_column.bindAsEventListener(this, this.actionTD));
		this.actionTD.down('span').show().observe('click', this.trigger_event_in_column.bindAsEventListener(this, this.actionTD));
	},

	reset: function() {
		if (!this.ruleWidget.fieldmaps)
			return;
		if (this.datepickers) {
			this.datepickers.invoke('close');
		}
		this.datepickers = [];
		var fieldSelectbox = new Element('select', {'style':'font-size:90%'});

		fieldSelectbox.update(new Element('option', {'value':''}).insert('<?=addslashes(_L('Select a Field'))?>'));

		var g = [];
		var c = [];
		var org = '';
		for (var fieldnum in this.ruleWidget.fieldmaps) {
			// Don't allow adding the same rule twice.
			if (this.ruleWidget.appliedRules[fieldnum])
				continue;

			var option = new Element('option', {'value':fieldnum}).update(this.ruleWidget.fieldmaps[fieldnum].name);

			if (fieldnum.charAt(0) == 'f')
				fieldSelectbox.insert(option);
			else if (fieldnum == 'organization')
				org = option;
			else if (fieldnum.charAt(0) == 'g')
				g.push(option);
			else if (fieldnum.charAt(0) == 'c')
				c.push(option);
		}
		if (org !== '') {
			if (fieldSelectbox.down('option',1)) // Add separator only if necessary
				fieldSelectbox.insert(new Element('option', {'value':'', 'disabled':true}).update('-----------'));
			fieldSelectbox.insert(org);
		}
		if (g.length > 0) {
			if (org == '') // Add separator only if no organization field
				fieldSelectbox.insert(new Element('option', {'value':'', 'disabled':true}).update('-----------'));
			g.each(function(option) {
				fieldSelectbox.insert(option);
			});
		}
		if (c.length > 0) {
			if (fieldSelectbox.down('option',1)) // Add separator only if necessary
				fieldSelectbox.insert(new Element('option', {'value':'', 'disabled':true}).update('-----------'));
			c.each(function(option) {
				fieldSelectbox.insert(option);
			});
		}

		fieldSelectbox.disabled = fieldSelectbox.options.length < 2;
		fieldSelectbox.observe('change', function(event) {
			if (this.datepickers) {
				this.datepickers.invoke('close');
			}
			var fieldnum = this.fieldTD.down('select').getValue();
			this.show_value_column(null);
			this.show_criteria_column(fieldnum);
			if (fieldnum == '') {
				this.ruleWidget.refresh_guide(true);
				this.ruleWidget.container.fire('RuleWidget:ChangeField', {
					'fieldnum': ''
				});
			}
			this.ruleWidget.container.style.width = 'auto';
		}.bindAsEventListener(this));

		this.fieldTD.down('fieldset').down('div').update(fieldSelectbox);
		this.criteriaTD.down('fieldset').down('div').update();
		this.valueTD.down('fieldset').down('div').update();
		this.actionTD.down('fieldset').down('div').update();
		//this.ruleWidget.container.style.width = '400px';

		this.criteriaTD.down('span').stopObserving('click').hide();
		this.valueTD.down('span').stopObserving('click').hide();
		this.actionTD.down('span').stopObserving('click').hide();
		this.trigger_event_in_column(null,this.fieldTD);
	},

	// Adds a toolbar only if the number of checkboxes exceeds threshold
	add_multicheckbox_toolbar: function(multicheckboxContainer, threshold) {
		if (!threshold)
			threshold = 10;
		// If necessary, add CheckAll and Clear, and limit height
		if (multicheckboxContainer.down('input', threshold)) {
			var checkAll = new Element('a', {'href':'#', 'style':'float:left; white-space: nowrap;'}).insert('<?=addslashes(_L('Check All'))?>');
			checkAll.observe('click', function(event) {
				event.stop();
				var checkboxes = this.select('input');
				var count = checkboxes.length;
				for (var i = 0; i < count; ++i) {
					checkboxes[i].checked = true;
				}
			}.bindAsEventListener(multicheckboxContainer));
			var clear = new Element('a', {'href':'#', 'style':'float:right; white-space: nowrap;'}).insert('<?=addslashes(_L('Clear'))?>');
			clear.observe('click', function(event) {
				event.stop();
				var checkboxes = this.select('input');
				var count = checkboxes.length;
				for (var i = 0; i < count; ++i) {
					checkboxes[i].checked = false;
				}
			}.bindAsEventListener(multicheckboxContainer));
			multicheckboxContainer.down('.MultiCheckbox').style.height = '300px';
			multicheckboxContainer.insert({top:new Element('div').insert(checkAll).insert(clear).insert('<div style="width:130px;height:1px;clear:both"></div>')});
		}
		
		return multicheckboxContainer;
	},

	// NOTE: If you want add a toolbar, do add_multicheckbox_toolbar(new Element('div').update(make_multicheckbox()));
	// Returns a div element containing the values as checkboxes, or returns a select element with a single option if there's just one value.
	make_multicheckbox: function(values, selected) {
		var multicheckbox = new Element('ul', {
			//'style': 'overflow:auto; padding-right: 2em; padding-bottom: 1em',
			'class': 'MultiCheckbox'
		});
		
		//var labelstyle = 'margin:0;padding:1px; font-size:90%;';
		//var checkboxstyle = 'font-size:90%;';
		//var divstyle = 'white-space:nowrap;';
		var selectedValues = {};
		if(selected){
			//var vals = selected.split(',');
			for (var i = 0; i < selected.length; i++) {
				selectedValues[selected[i]] = true;
			}
		}
		// Determine if values is an array, only support array with object of {value:___,name:___} objcets
		if (typeof(values.join) != 'undefined') { // values is an array.
			var max = values.length;
			
			for (var i = 0; i < max; ++i) {
				var value = values[i];
				if (max == 1) {
					return new Element('select').insert(
						new Element('option', {'value': (typeof(value.value) != 'undefined'?value.value:value)}).update(typeof(value.name) != 'undefined'?value.name:value.escapeHTML())
					);
				}
				var checkboxValue = typeof(value.value) != 'undefined' ? value.value : value;
				var checked = selectedValues[checkboxValue] === true;
				var checkbox = new Element('input', {
					'type': 'checkbox',
					'checked':checked,
					'value': checkboxValue
				});
				
				var label = new Element('label', {
					'for': checkbox.identify()
				}).update((typeof(value.name) != 'undefined'?value.name:value).escapeHTML());
				
				multicheckbox.insert(
					new Element('li').insert(checkbox).insert(label)
				);
			}
		} 

		return multicheckbox;
	},

	make_selectbox: function(values, hidden) {
		var selectbox = new Element('select', {'style':'font-size:90%'});
		for (var i in values) {
			selectbox.insert(new Element('option', {'value':i.escapeHTML(), 'style':'font-size:90%'}).update(values[i].escapeHTML()));
		}
		if (hidden)
			selectbox.hide();
		return selectbox;
	},

	make_radioboxes: function(values, hidden, checked) {
		var radioboxDIV = new Element('div');
		// get the number of possible values
		var numvals = $H(values).size();

		// for each value, insert a div with control and label
		for (var i in values) {
			// don't insert a control if there is only one possible value
			if (numvals > 1) {
				// create the control with a label
				var check = checked === i;
				var radio = new Element('input', {'type':'radio','checked':check, 'name':radioboxDIV.identify(), 'value':i.escapeHTML()});
				var label = new Element('label', {'style':'font-size:90%', 'for':radio.identify()}).update(values[i].escapeHTML());
				radioboxDIV.insert(new Element('div', {'style':'white-space:nowrap'}).insert(radio).insert(label));
			// otherwise, just stick the text into the div
			} else {
				radioboxDIV.update(values[i].escapeHTML());
			}
		}

		if (hidden)
			radioboxDIV.hide();
		return radioboxDIV;
	},

	make_datebox: function(value, hidden) {
		if (!value)
			value = '';
		var datebox = new Element('input', {'type':'text', 'size':'10', 'style':'font-size:90%', 'value':value.escapeHTML()});
		datebox.observe('focus', function(event, ruleEditor) {
			ruleEditor.datepickers.push(pickDate(this, true,true));
		}.bindAsEventListener(datebox, this));

		if (hidden)
			datebox.hide();
		return datebox;
	},

	make_textbox: function(value, small, hidden) {
		if (!value)
			value = '';
		var textbox = new Element('input', {'type':'text', 'style':'font-size:90%', 'value':value.escapeHTML(), 'maxlength':255});
		textbox.size = small ? '8' : '12';
		if (hidden)
			textbox.hide();
		return textbox;
	}
});
