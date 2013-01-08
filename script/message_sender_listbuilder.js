
// List Picker Plugin

(function($){
    // disable anchor tags which have the disabled class
    $(".add-recipients a").click(function (e) {
        if ($(this).hasClass("disabled"))
            e.stopPropagation();
    });
    
    $.listPicker = function(el, options){

        var base = this;

        base.$el = $(el);
        base.el = el;

        base.$el.data("listPicker", base);

        var $listTable = base.$el.find('.added-lists .lists');
        var numRecipients = 0;

        base.init = function(){

            base.options = $.extend({}, $.listPicker.defaultOptions, options);

            base.pickedListIds = base.options.prepickedListIds;
            base.updateParentElement();
            base.numRecipients = 0;
            
            // Get lists and info -----------------------------------

            $.ajax({
                url: 'ajax.php?type=lists'
            }).done(function(dataLists){

                base.lists = $.extend({}, dataLists);
                base.unfetchedIds = [];

                // Initialize the lists and stats objects
                var hasLists = false;
                $.each(base.lists, function(id, data) {
                    hasLists = true;
                    data.isSaved = true;
                    data.stats = {name: data.name};
                    base.unfetchedIds.push(id);
                    // If this is one of the prepicked lists, get the stats right away and rebuild the table
                    if (base.pickedListIds.indexOf(id) !== -1) {
                        $.ajax({
                            url: 'ajax.php?type=liststats&listids=["' + id + '"]'
                        }).done(function(dataListStats){
                            data.stats = $.extend(data.stats, dataListStats[id]);
                            base.unfetchedIds.splice(base.unfetchedIds.indexOf(id), 1);
                            base.buildTable();
                        });
                    };
                });

                // Enable the "pick from lists" button
                if (hasLists)
                    base.$el.find('[href=#add-recipients-existing_lists]').removeClass('disabled');

            });

            // Init the 3 modals -----------------------------------

            base.modals = {
                pickExisting: {$el: $('#add-recipients-existing_lists')},
                newUsingRules: {$el: $('#add-recipients-rules')},
                newUsingSections: {$el: $('#add-recipients-sections')},
                newUsingQuickPick: {$el: $('#add-recipients-quickpick')},
                newUsingUpload: {$el: $('#add-recipients-upload')},  
                
                previewList: {$el: $('#modal-preview-list')},
                saveList: {$el: $('#modal-save-list')}
            };

            // MODAL > pickExisting ---------------------------------

            (function(){
                
                var modal = base.modals.pickExisting,
                    selectedListIds = [],
                    rows = '',
                    $el = $('#add-recipients-existing_lists'),
                    $addButton = base.modals.pickExisting.$el.find('.btn-primary');

                // Modal is opened
                modal.$el.on('show', function(){
                    
                    // Build the rows
                    rows = '';
                    $.each(base.lists, function(id, data) {
                        if (data.isSaved === true) {
                            // If the list hasn't been picked, make it checkable, else, show disabled
                            if ($.inArray(id, base.pickedListIds) === -1) {
                                rows = rows + '<label class="checkbox" data-list-id="' + id + '"><input type="checkbox" value="' + id + '"> ' + data.name + ' <span class="count">(' + (data.stats.total === undefined ? '--' : data.stats.total) + ')</span></label>';
                            } else {
                                rows = rows + '<label class="checkbox" data-list-id="' + id + '" class="disabled"><input type="checkbox" value="' + id + '" checked="checked" disabled> ' + data.name + ' <span class="count">(' + data.stats.total + ')</span></label>';
                            };
                        };
                    });
                    
                    // Put rows in table
                    modal.$el.find('.existing-lists').html(rows);

                    // Start the rolling fetch
                    modal.rollingFetch();
                    
                    // Disable the "add" button
                    $addButton.text('Add recipients').addClass('disabled');
                });

                // A list is checked/unchecked
                modal.$el.on('change', 'input[type=checkbox]', function(){
                    
                    modal.selectedListIds = [];
                    
                    // Recount selected lists
                    modal.$el.find(':checked:not(:disabled)').each(function(){
                        modal.selectedListIds.push(this.value);
                    });
                    
                    // Rebuild the submit button
                    modal.buildSubmit();
                });

                modal.rollingFetch = function(){
                    if (base.unfetchedIds.length > 0) {
                        var id = base.unfetchedIds[0];
                        base.unfetchedIds.splice(0,1);
                        var $count = modal.$el.find('[data-list-id="' + id + '"] .count').text('(...)');
                        $.ajax({
                            url: 'ajax.php?type=liststats&listids=["' + id + '"]'
                        }).done(function(dataListStats){
                            base.lists[id].stats = $.extend(base.lists[id].stats, dataListStats[id]);
                            $count.text('(' + base.lists[id].stats.total + ')');
                            modal.rollingFetch();
                        });
                    }
                }

                modal.buildSubmit = function(){
                    if (modal.selectedListIds.length === 0) {
                        $addButton.text('Add recipients').addClass('disabled');
                    } else {
                        var sum = 0;
                        $.each(modal.selectedListIds, function(index, id) {
                            // If this list doesn't have stats (total) yet, get the stats and
                            // build the submit button again
                            if (base.lists[id].stats.total === undefined) {
                                var $count = modal.$el.find('[data-list-id="' + id + '"] .count').text('(...)');
                                $.ajax({
                                    url: 'ajax.php?type=liststats&listids=["' + id + '"]'
                                }).done(function(dataListStats){
                                    // merge in the stats
                                    base.lists[id].stats = $.extend(base.lists[id].stats, dataListStats[id]);
                                    // write the count into the row
                                    $count.text('(' + base.lists[id].stats.total + ')');
                                    modal.buildSubmit();
                                });
                            } else {
                                sum += base.lists[id].stats.total;
                            };
                        });
                        $addButton.text('Add ' + sum + ' recipients').removeClass('disabled');
                    };
                };

                // The "Add" button is clicked
                modal.$el.on('click', '.btn-primary:not(.disabled,:disabled)', function(){
                    $.merge(base.pickedListIds, modal.selectedListIds);
                    base.updateParentElement();
                    modal.$el.modal('hide');
                    base.buildTable();
                    $.each(modal.selectedListIds, function(index, id){
                        base.highlightListRow(id);
                    });
                    return false;
                });
            })();

            // MODAL > newUsingRules --------------------------------

            (function(){
                
                var modal = base.modals.newUsingRules;

                var $addRuleBtn = modal.$el.find('.add-rule .btn');
                var $addListBtn = modal.$el.find('.modal-footer .btn-primary');

                modal.newRule = {
                    $el:      modal.$el.find('#new-rule'),
                    field:    {$el: modal.$el.find('#new-rule-field')},
                    criteria: {$el: modal.$el.find('#new-rule-criteria')},
                    value:    {$el: modal.$el.find('#new-rule-value')}
                };

                // Keep an empty rule ready for when the modal is reopened
                var emptyRule = $.extend({}, modal.newRule);

                var $saveRuleBtn = modal.newRule.$el.find('.btn-primary');

                // Populate the field options
                modal.buildFields = function(){
                    // Build the options html
                    var fieldOptions = '';
                    
                    var createFieldOption = function (id) {
                        if (!modal.ruleSettings.fieldmaps[id])
                            return;
                        // If the field is not already in a rule, add it as an option
                        if (modal.newList === undefined || modal.newList.rules[id] === undefined) {
                            fieldOptions = fieldOptions + '<option value="' + id + '">' + modal.ruleSettings.fieldmaps[id].name + '</option>';
                        };
                    }

                    var pattern = new RegExp(/[0-9][0-9]$/);
                    // F fields, separator, organization, G fields, separator, C fields
                    $.each(["f","sep","organization","g","sep","c"], function (index, type) {
                        switch (type) {
                        case "sep":
                            fieldOptions += '<option value="" disabled="disabled">-----------</option>';
                            break;
                        case "organization":
                            createFieldOption(type);
                            break;
                        default:
                            for (var i = 1; i <=20; i++) {
                                var id = type + pattern.exec("0" + i.toString());
                                createFieldOption(id);
                            }
                        }
                    });
                    modal.newRule.field.$el.html('<select><option value="">Select a field</option>' + fieldOptions + '</select>');
                };

                // Populate the criteria options
                modal.buildCriteria = function() {
                    // Switch for org
                    if (modal.newRule.field.type === 'association') {
                        modal.newRule.criteria.$el.html('is');
                        modal.newRule.criteria.val = 'in';
                        modal.buildValues();
                    } else {
                        modal.newRule.criteria.$el.html('<select>' + modal.criteriaOptions[modal.newRule.field.type] + '</select>');
                        // Kick the change event to set the criteria val immediately
                        modal.newRule.criteria.$el.find('select').change();
                    };
                };

                // Populate the value options
                modal.buildValues = function() {
                    var options = '';
                    if (modal.newRule.field.fieldnum === 'f03') {
                        $.each(modal.ruleSettings.languagemap, function(value, name){
                            options = options + '<label class="checkbox"><input type="checkbox" value="' + value + '">' + name + '</label>';
                        });
                        modal.newRule.value.$el.html('<div class="value-options">' + options + '</div>');
                        modal.validateRule();
                    } else {
                        // Indicate that we're loading the values
                        modal.newRule.value.$el.html('Loading...');
                        $.ajax({
                            url: 'ajax.php?type=getdatavalues&fieldnum=' + modal.newRule.field.fieldnum
                        }).done(function(data){
                            // data is true when we get some options back for this field
                            if (data) {
                                var isOrg = modal.newRule.field.type === 'association';
                                $.each(data, function(id,item){
                                	var value = typeof(item.value) != 'undefined'?item.value:item;
                                    var name = typeof(item.name) != 'undefined'?item.name:item;
                                    options = options + '<label class="checkbox"><input type="checkbox" value="' + value + '">' + (name === '' ? '&nbsp;' : name) + '</label>';
                                });
                            // no data, must be text or reldate
                            } else {
                                switch(modal.newRule.field.type) {
                                    case 'text':
                                    case 'numeric':
                                        options = '<input type="text"></input>';
                                        break;
                                    case 'reldate':
                                        var criteriaVal = modal.newRule.criteria.$el.find('select').val();
                                        // Build based on criteria
                                        if (criteriaVal === 'reldate') {
                                            $.each(modal.ruleSettings.reldateOptions, function(value, name){
                                                options = options + '<option value="' + value + '">' + name + '</option>';
                                            });
                                            options = '<select>' + options + '</select>';    
                                        } else if (criteriaVal === 'date_range') {
                                            options = '<input class="date" type="text"></input> and <input class="date" type="text"></input>';
                                        } else if (criteriaVal === 'reldate_range') {
                                            options = '<input type="text"></input> and <input type="text"></input>';
                                        } else {
                                            options = '<input class="date" type="text"></input>';
                                        };
                                        break;
                                }
                            };
                            modal.newRule.value.$el.html('<div class="value-options">' + options + '</div>').find('.date').datepicker();
                            modal.validateRule();
                        });
                    };
                };

                // Rebuild the table of rules added so far
                modal.buildRuleList = function(resetRule){
                    // Get the rules
                    $.ajax({
                        url: 'ajax.php?type=listrules&listids=["' + modal.newList.id + '"]'
                    }).done(function(rulesData){

                        modal.newList.rules = {};
                        
                        var ruleRows = '';
                        
                        // map the rules to our own rules object
                        $.each(rulesData[modal.newList.id], function(ruleId, data){
                            
                            var field = modal.ruleSettings.fieldmaps[data.fieldnum];
                            
                            // tweak the op to map correctly to is/is Not
                            data.op = data.logical === 'and not' ? 'not' : data.op;
                            
                            // tweak the returned value and op if this is the organization
                            if (ruleId === 'organization') {
                                var vals = [];
                                $.each(data.val, function(orgId, orgName){
                                    vals.push(orgName);
                                });
                                data.val = vals.join('|');
                                data.op = 'in';
                            }

                            // If it's the language field, we have to use the languagemap to get name values
                            if (field.fieldnum === 'f03') {
                                var vals = [];
                                var langIds = data.val.split('|');
                                $.each(langIds, function(index, langId){
                                    vals.push(modal.ruleSettings.languagemap[langId]);
                                });
                                data.val = vals.join('|');
                            };
                            
                            // Add the rule object
                            modal.newList.rules[data.fieldnum] = {
                                field: field.name,
                                criteria: modal.ruleSettings.operators[field.type][data.op],
                                value: data.val.split('|')
                            };
                            var rule = $.extend({}, modal.newList.rules[data.fieldnum]);

                            // Convert value ids to their names

                            rule.value = rule.value.length > 1 ? rule.value.join(', ') : rule.value[0];
                            ruleRows = ruleRows + '<tr class="saved-rule" data-fieldnum="' + field.fieldnum + '"><td><a class="action remove" href="#"><i class="icon-remove"></i></a></td><td>' + field.name + '</td><td>' + rule.criteria + '</td><td>' + rule.value + '</td><td></td></tr>';
                        });
                        modal.$el.find('.saved-rule').remove();
                        modal.newRule.$el.before(ruleRows);
                        if (resetRule === true) {
                            modal.resetRule();
                            modal.newRule.$el.hide();
                            $addRuleBtn.show();
                        };
                    });
                };

                // Rebuild the modal's submit button
                modal.buildSubmit = function(){
                    // If there's no list, or no rules, disable button
                    if (modal.newList === undefined || modal.newList.numRules === 0 ) {
                        $addListBtn.addClass('disabled').text('Add Recipients');
                    } else {
                        // Get new list stats
                        $.ajax({
                            url: 'ajax.php?type=liststats&listids=["' + modal.newList.id + '"]'
                        }).done(function(dataListStats){
                            modal.newList.stats = $.extend({}, dataListStats[modal.newList.id]);
                            $addListBtn.removeClass('disabled').text('Add ' + modal.newList.stats.total + ' Recipients');
                        });
                    };
                };

                // Validate the rule
                modal.validateRule = function(){

                    // Invalid by default
                    var isValid = false;

                    // Check for text, checks, or a selected option
                    switch (modal.newRule.field.type) {
                        case 'multisearch':
                        case 'association':
                            var $checked = modal.newRule.value.$el.find(':checked');
                            if ($checked.size() !== 0) {
                                isValid = true;
                                modal.newRule.value.val = [];
                                $checked.each(function(){
                                    modal.newRule.value.val.push($(this).val());
                                });
                            };
                            break;
                        case 'text':
                        case 'numeric':
                            // Apparently empty strings are valid, so...
                            isValid = true;
                            modal.newRule.value.val = modal.newRule.value.$el.find('input:text:not([value=""])').val();
                            break;
                        case 'reldate':
                            if (modal.newRule.criteria.val === 'reldate') {
                                var $select = modal.newRule.value.$el.find('select');
                                if ($select.val() !== '') {
                                    isValid = true;
                                    modal.newRule.value.val = $select.val();
                                };
                            } else if (modal.newRule.criteria.val === 'date_range' || modal.newRule.criteria.val === 'reldate_range') {
                                var $inputs = modal.newRule.value.$el.find('input:text:not([value=""])');
                                if ($inputs.size() === 2) {
                                    isValid = true;
                                    modal.newRule.value.val = [$inputs.eq(0).val(), $inputs.eq(1).val()];
                                };
                            } else {
                                var $input = modal.newRule.value.$el.find('input:text:not([value=""])');
                                if ($input.size() !== 0) {
                                    isValid = true;
                                    modal.newRule.value.val = $input.val();
                                };
                            };
                            break;
                    }
                    if (isValid) {
                        $saveRuleBtn.removeClass('disabled');
                    } else {
                        $saveRuleBtn.addClass('disabled');
                    };
                };

                // Save and apply the current rule
                modal.saveRule = function(){
                    
                    var rField = modal.newRule.field;
                    var rCriteria = modal.newRule.criteria;
                    var rValue = modal.newRule.value;

                    var ruleData = {
                        'fieldnum': rField.val,
                        'type': rField.val === 'organization' ? 'association' : rField.type,
                        'logical': rCriteria.val === 'not' ? 'and not' : 'and',
                        'op': rCriteria.val === 'not' ? 'in' : rCriteria.val,
                        'val': rValue.val
                    };

                    // Submit the value
                    $.ajax({
                        url: 'ajaxlistform.php?type=addrule&listid=' + modal.newList.id,
                        type: 'post',
                        data: {ruledata: JSON.stringify(ruleData)}
                    }).done(function(success){
                        if (success) {
                            modal.newList.numRules++;
                            modal.buildRuleList(true);
                            modal.buildSubmit();
                        } else {
                            // FAIL
                            console.log(success);
                        };
                    });
                };

                // Reset the rule row
                modal.resetRule = function(){
                    // Get a fresh rule object
                    modal.newRule = $.extend({}, emptyRule);
                    // Empty the html for field, criteria, and value
                    modal.newRule.field.$el.empty();
                    modal.newRule.criteria.$el.empty();
                    modal.newRule.value.$el.empty();
                    // Show the rule row
                    modal.newRule.$el.show();
                    // Build the field options
                    modal.buildFields();
                    // Disable save
                    $saveRuleBtn.addClass('disabled').text('Save');
                }

                // Reset the whole modal
                modal.resetModal = function(){
                    // Remove all existing rules, and lists
                    modal.$el.find('.saved-rule').remove();
                    delete modal.newList;
                    modal.resetRule();
                    modal.buildSubmit();
                    $addRuleBtn.hide();
                };

                // Modal is opened
                modal.$el.on('show', function(){

                    // If this is the first time
                    if (modal.ruleSettings === undefined) {
                        // Indicate that we're loading
                        modal.newRule.field.$el.html('Loading...');
                        // Get the list of fields
                        $.ajax({
                            url: 'ajax.php?type=rulewidgetsettings'
                        }).done(function(data){

                            // Copy over the rule settings
                            modal.ruleSettings = $.extend({}, data);

                            // Add school if hasorg
                            if (modal.ruleSettings.hasorg === true) {
                                modal.ruleSettings.fieldmaps.organization = {fieldnum: 'organization', name: modal.ruleSettings.orgname, options: 'organization', type: 'association'};
                            };

                            // Explode the criteria options and set the type
                            $.each(modal.ruleSettings.fieldmaps, function(id, field){
                                modal.ruleSettings.fieldmaps[id].options = field.options.split(',');
                                // Populate the criteria field with options
                                $.each(['multisearch', 'text', 'numeric', 'reldate'], function(index, type){
                                    modal.ruleSettings.fieldmaps[id].options.indexOf(type) !== -1 ? modal.ruleSettings.fieldmaps[id].type = type : null;
                                });
                            });

                            // Patch multisearch options
                            modal.ruleSettings.operators['multisearch'] = {"in": "is", "not": "is NOT"};
                            modal.ruleSettings.operators['association'] = {"in": "is"};

                            // Create the html options for each of the criteria
                            modal.criteriaOptions = {};
                            $.each(modal.ruleSettings.operators, function(key, options){
                                modal.criteriaOptions[key] = '';
                                $.each(options, function(value, name){
                                    modal.criteriaOptions[key] = modal.criteriaOptions[key] + '<option value="' + value + '">' + name + '</option>';
                                });
                            });

                            modal.resetModal();
                        });
                    } else {
                        modal.resetModal();
                    };
                });

                // Changed FIELD
                modal.newRule.field.$el.on('change', 'select', function(){

                    // Cache the field
                    modal.newRule.field = $.extend(modal.newRule.field, modal.ruleSettings.fieldmaps[$(this).val()]);
                    modal.newRule.field.val = modal.newRule.field.$el.find('select').val();

                    // If an actual value was selected, do stuff
                    if (modal.newRule.field.val !== '') {
                        modal.buildCriteria();
                    } else {
                        modal.newRule.criteria.$el.empty();
                        modal.newRule.value.$el.empty();
                    };

                    // Class the rule row based on the current field type
                    modal.newRule.$el.removeClass(function(index, classString){
                        return (classString.match(/\btype-\S+/g) || []).join(' ');
                    }).addClass('type-' + modal.newRule.field.type);

                    $saveRuleBtn.addClass('disabled');
                });

                // Changed CRITERIA
                modal.newRule.criteria.$el.on('change', 'select', function(){
                    modal.newRule.criteria.val = modal.newRule.criteria.$el.find('select').val();
                    // Rebuild the values
                    modal.buildValues();
                });

                // Changed VALUE
                modal.newRule.value.$el.on('change keyup', 'input, select', function(){
                    modal.validateRule();
                });

                // "Save Rule" button is clicked
                modal.$el.on('click', '.new-rule .btn-primary', function(){
                	// if button is disabled, break
                	if ($saveRuleBtn.hasClass("disabled"))
                		return false;
                    // Indicate that we're saving the rule
                    $saveRuleBtn.addClass('disabled').text('Saving...');
                    // If there is no current list, create one and get the id
                    if (modal.newList === undefined) {
                        $.ajax({
                            url: 'ajaxlistform.php?type=createlist'
                        }).done(function(newListId){
                            modal.newList = {id: newListId, rules: {}, numRules: 0};
                            modal.saveRule();
                        });
                    } else {
                        modal.saveRule();
                    }
                    return false;
                });

                // "Cancel Rule" link is clicked
                modal.newRule.$el.on('click', '.cancel', function(){
                    modal.resetRule();
                    modal.newRule.$el.hide();
                    $addRuleBtn.show();
                    return false;
                });

                // "Remove Rule" action icon is clicked
                modal.$el.on('click', '.saved-rule .remove', function(){
                    var $ruleRow = $(this).closest('tr');
                    $ruleRow.find('td').eq(1).text('Deleting...');
                    // Delete the rule from this list
                    $.ajax({
                        url: 'ajaxlistform.php?type=deleterule&listid=' + modal.newList.id,
                        type: 'post',
                        data: {fieldnum: $ruleRow.attr('data-fieldnum')}
                    }).done(function(success){
                        if (success) {
                            $ruleRow.remove();
                            delete modal.newList.rules[$ruleRow.attr('data-fieldnum')];
                            modal.newList.numRules--;
                            modal.buildFields();
                            modal.buildSubmit();
                        } else {
                            console.log('failed to delete rule');
                        };
                    });
                    return false;
                });

                // The "Add a Rule" button is clicked
                $addRuleBtn.on('click', function(){
                    modal.resetRule();
                    $addRuleBtn.hide();
                    return false;
                });

                // The modal's "Add Recipients" button is clicked
                $addListBtn.on('click', function(){
                    var $btn = $(this);
                    if (!$btn.hasClass('disabled')) {
                        base.pickedListIds.push(modal.newList.id);
                        base.updateParentElement();
                        base.lists[modal.newList.id] = $.extend({}, modal.newList);
                        modal.$el.modal('hide');
                        base.buildTable();
                        base.highlightListRow(modal.newList.id);
                    };
                    return false;
                });
            })();

            // MODAL > newUsingSections -----------------------------

            (function(){

                var modal = base.modals.newUsingSections;

                var $orgSelect = modal.$el.find('.org-select');
                var $sectionInputs = modal.$el.find('.section-inputs');
                var $submitBtn = modal.$el.find('.btn-primary');

                // Modal is opened
                modal.$el.on('show', function(){

                    // If this is the first open
                    if (modal.orgs === undefined) {
                        $.ajax({
                            url: 'ajax.php?type=getdatavalues&fieldnum=organization'
                        }).done(function(orgsData){
                            if (orgsData) {
                                modal.orgs = {};
                                $.each(orgsData, function(id,organization){
                                    modal.orgs[organization.value] = organization;
                                });
                                modal.orgsData = orgsData;
                                modal.buildSelect();
                            } else {
                                // ajax failed
                            };
                        });
                    } else {
                        modal.buildSelect();
                    };

                    // Reset
                    $sectionInputs.empty();
                    $submitBtn.addClass('disabled');
                });

                // Build the School picker
                modal.buildSelect = function(){
                    var options = '';
                    $.each(modal.orgsData, function(id,orgData){
                        options = options + '<option value="' + orgData.value + '">' + orgData.name + '</option>';
                    });
                    $orgSelect.html('<option value="">Select a School</option>' + options);
                };

                // School is changed
                $orgSelect.on('change', function(){
                    modal.selectedOrg = $(this).val();
                    // If an org was selected
                    if (modal.selectedOrg !== '') {
                        modal.getSections();
                    } else {
                        $sectionInputs.empty();
                    };
                });

                // Get and/or build the section options
                modal.getSections = function(){
                    var org = modal.orgs[modal.selectedOrg];
                    if (org.sections === undefined) {
                        $.ajax({
                            url: 'ajax.php?type=getsections&organizationid=' + modal.selectedOrg
                        }).done(function(sectionData){
                            if (sectionData) {
                                org.sections = $.extend({}, sectionData);
                                modal.buildSections();
                            } else {
                                // ajax failed
                            };
                        });
                    } else {
                        modal.buildSections();
                    };
                };

                // Build the section options
                modal.buildSections = function(){
                    var inputs = '';
                    $.each(modal.orgs[modal.selectedOrg].sections, function(sectionId, sectionName){
                        inputs = inputs + '<label class="checkbox"><input type="checkbox" value="' + sectionId + '">' + sectionName + '</label>';
                    });
                    $sectionInputs.html(inputs);
                };

                // Revalidate when a section is checked/unchecked
                $sectionInputs.on('change', 'input', function(){
                    modal.$selected = $sectionInputs.find(':checked');
                    if (modal.$selected.size() !== 0) {
                        $submitBtn.removeClass('disabled');
                    } else {
                        $submitBtn.addClass('disabled');
                    }
                });

                // Submit
                $submitBtn.on('click', function(){
                    if (!$(this).hasClass('disabled')) {
                        // Create a list with the selected section ids
                        var sectionIds = [];
                        var sectionNames = [];
                        modal.$selected.each(function(){
                            var sectionId = $(this).val();
                            sectionIds.push(sectionId);
                            sectionNames.push(modal.orgs[modal.selectedOrg].sections[sectionId]);
                        });
                        $.ajax({
                            url: 'ajaxlistform.php?type=createlist',
                            data: {sectionids: sectionIds},
                            type: 'post'
                        }).done(function(newListId){
                            if (newListId) {
                                // Add the list to the table and close the modal
                                $.ajax({
                                    url: 'ajax.php?type=liststats&listids=["' + newListId + '"]'
                                }).done(function(data){
                                    base.pickedListIds.push(newListId);
                                    base.updateParentElement();
                                    // Truncate the name if there are a lot of sections
                                    var listName = 'School is ' + modal.orgs[modal.selectedOrg].name + '; Section is ' +  sectionNames.join('; ');
                                    data[newListId].name = listName.length > 60 ? (listName.substring(0, 59) + '...') : listName;
                                    base.lists[newListId] = {
                                        stats: $.extend({}, data[newListId])
                                    };
                                    modal.$el.modal('hide');
                                    base.buildTable();
                                    base.highlightListRow(newListId);
                                });
                            } else {
                                // ajax failed
                            };
                        });
                    };
                    return false;
                });
            })();

            // MODAL > saveList -------------------------------------

            (function(){
                var modal = base.modals.saveList;
                var $name = modal.$el.find('input[type="text"]');
                var $submitBtn = modal.$el.find('.btn-primary');

                modal.$el.on('show', function(){
                    modal.list = base.lists[modal.listId];
                    // Set the name to the current name of the list
                    $name.val(modal.list.stats.name);
                    $submitBtn.text('Save List');
                    modal.validate();
                });

                modal.validate = function(){
                    if ($name.val() !== '') {
                        $submitBtn.removeClass('disabled');
                    } else {
                        $submitBtn.addClass('disabled');
                    };
                };

                $name.on('change keyup', modal.validate);

                $submitBtn.on('click', function(){
                    if (!$submitBtn.hasClass('disabled')) {
                        $submitBtn.text('Saving...').addClass('disabled');
                        $.ajax({
                            url: 'ajaxlistform.php?type=saveandrename&listid=' + modal.listId,
                            data: {name: $name.val()}
                        }).done(function(success){
                            if (success) {
                                base.lists[modal.listId].name = base.lists[modal.listId].stats.name = $name.val();
                                base.lists[modal.listId].isSaved = true;
                                modal.$el.modal('hide');
                                base.buildTable();
                            } else {
                                // Failed
                                console.log('Saving failed.')
                            };
                        });
                    };
                    return false;
                });
            })();
            
            
            
            // MODAL > quickpick -------------------------------------

            (function(){
                var modal = base.modals.newUsingQuickPick;
                var iframe = modal.$el.find('iframe');
                var listid;
                
                modal.$el.on('show', function(){
                	iframe.attr("src","");
                    $.ajax({
                        url: 'ajaxlistform.php?type=createlist'
                    }).done(function(newListId){
                    	listid = newListId
                    	iframe.attr("src","searchquickadd.php?listsearchmode=individual&id=" + listid);
                    });
                });
                
                var submitBtn = modal.$el.find('.btn-primary');
                submitBtn.on('click', function(){
                	  $.ajax({
                          url: 'ajax.php?type=liststats&listids=["' + listid + '"]'
                      }).done(function(data){
                          base.pickedListIds.push(listid);
                          base.updateParentElement();
                          
                          data[listid].name = 'Quick Pick';
                          base.lists[listid] = {
                              stats: $.extend({}, data[listid])
                          };
                          modal.$el.modal('hide');
                          base.buildTable();
                          base.highlightListRow(listid);
                      });
                });
            })();
            
            // MODAL > uploadlist -------------------------------------

            (function(){
                var modal = base.modals.newUsingUpload;
                var iframe = modal.$el.find('iframe');
                var listid;
                var submitBtn = modal.$el.find('.btn-primary');

                modal.$el.on('show', function(){
                	iframe.attr("src","");
                    $.ajax({
                        url: 'ajaxlistform.php?type=createlist'
                    }).done(function(newListId){
                    	listid = newListId
                    	iframe.attr("src","uploadlist.php?iframe=true");
                    });
                });
                
                modal.validate = function(){
                	var url = iframe.contents().get(0).location.href.toLowerCase();
                	if (url == 'about:blank' || url.indexOf("uploadlist.php?iframe=true") >= 0) {
                        submitBtn.addClass('disabled');
                    } else {
                        submitBtn.removeClass('disabled');
                    };
                };
                
                iframe.on('load', function() {modal.validate();});
                
                submitBtn.on('click', function(){
            		  iframe.contents().find('input.btn_hide').first().trigger('click');
            		  
                	  $.ajax({
                          url: 'ajax.php?type=liststats&listids=["' + listid + '"]'
                      }).done(function(data){
                          base.pickedListIds.push(listid);
                          base.updateParentElement();
                          
                          data[listid].name = 'Uploaded List';
                          base.lists[listid] = {
                              stats: $.extend({}, data[listid])
                          };
                          modal.$el.modal('hide');
                          base.buildTable();
                          base.highlightListRow(listid);
                      });  
                });
            })();

            
            // MODAL > previewlist -------------------------------------
            (function(){
            	
            	
                var modal = base.modals.previewList;
                var iframe = modal.$el.find('iframe');
                var submitBtn = modal.$el.find('.btn-primary');

                modal.$el.on('show', function(){
                	iframe.attr("src","showlist.php?iframe=true&id=" + modal.listId);
                });
            })();
            
            
            
            
            // Wire up the table's list actions (remove and save) ---

            base.$el.find('.added-lists').on('click', '[data-list-id] .action', function(){
                var $action = $(this);
                var listId = $action.closest('tr').attr('data-list-id');
                
                if ($action.hasClass('remove')) {
                    base.pickedListIds.splice(base.pickedListIds.indexOf(listId), 1);
                    base.updateParentElement();
                } else if ($action.hasClass('save')) {
                    base.modals.saveList.listId = listId;
                    base.modals.saveList.$el.modal('show');
                } else if ($action.hasClass('preview')) {
                    base.modals.previewList.listId = listId;
                    base.modals.previewList.$el.modal('show');
                };
                
                

                // TODO: comment out tooltips for now. Have to find the issue with CSS causing positioning issues
                //$action.tooltip('hide');
                base.buildTable();
                return false;
            });
        };

        // Build the "picked lists" table ---------------------------

        base.buildTable = function(){

            var rows = '';
            var sum = 0;

            // Add each picked list to the table
            $.each(base.pickedListIds, function(index, id) {

                // Only do this if the list has stats
                if (base.lists[id].stats.total !== undefined) {

                    // Recalc total recipients
                    sum += base.lists[id].stats.total;
                    
                    rows += '<tr data-list-id="' + id + '"><td><a rel="tooltip" title="Remove List" class="action remove" href="#"><i class="icon-remove" title="Remove List"></i></a>'; 
                    // If this list is saved, no save icon, else, show save icon
                    if (!base.lists[id].isSaved) {
                        rows += '<a rel="tooltip" title="Save List" class="action save" data-toggle="modal" href="#modal-save-list"><i class="icon-folder-open" title="Save List"></i></a>';
                    }
                    rows += '<a rel="tooltip" title="Preview List" class="action preview" data-toggle="modal" href="#modal-preview-list"><i title="Preveiw List"></i></a>';
                    rows += '</td><td>' + base.lists[id].stats.name + '</td><td>' + base.lists[id].stats.total + '</td></tr>';
                    
                };
            });

            // Last row is the total
            rows += '<tr><td colspan="2">Total</td><td>' + sum + '</td></tr>';
            
            // Add the rows to the table
            $listTable.find('tbody').html(rows);

            // TODO: comment out tooltips for now. Have to find the issue with CSS causing positioning issues
            // Activate new tooltips
            //base.$el.find('[rel=tooltip]').tooltip();

            // Cache the sum
            base.numRecipients = sum;

            // Fire a custom event
            base.$el.trigger({
                type: 'updated',
                numRecipients: sum
            });

            //console.log(base);
        };

        base.highlightListRow = function(listId){
            $listTable.find('[data-list-id=' + listId + ']').addClass('new');
            setTimeout(function(){
                $listTable.find('[data-list-id=' + listId + '] td').addClass('flashed');
            }, 1000);
        };
        
        base.updateParentElement = function() {
        	$("#msgsndr_listids").val($.toJSON(base.pickedListIds));
        	$("#msgsndr_listids").trigger("listwidget:updated");
        };

        // Run initializer
        base.init();
    };

    $.listPicker.defaultOptions = {
        prepickedListIds: []
    };

    $.fn.listPicker = function(options){
        return this.each(function(){
            (new $.listPicker(this, options));
        });
    };

})(jQuery);