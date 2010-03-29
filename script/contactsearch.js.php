<script type="text/javascript">

	<? Validator::load_validators(array("ValSections", "ValRules")); ?>

	function choose_search_by_rules() {
		$('searchByRules').checked = true;
		$('ruleWidgetContainer').up('tr').show();
	<? if (getSystemSetting('_hasenrollment')) { ?>
		$('searchBySections').checked = false;
		$('<?=$form->name?>_sectionids_fieldarea').hide();
		$('sectionsearchButtonContainer').up('tr').hide();
	<? } ?>
		$('searchByPerson').checked = false;
		$('<?=$form->name?>_pkey').up('tr').hide();
		$('<?=$form->name?>_phone').up('tr').hide();
		$('<?=$form->name?>_email').up('tr').hide();
		$('personsearchButtonContainer').up('tr').hide();
	}
	
	function choose_search_by_sections() {
		$('searchByRules').checked = false;
		$('ruleWidgetContainer').up('tr').hide();
	<? if (getSystemSetting('_hasenrollment')) { ?>
		$('searchBySections').checked = true;
		$('<?=$form->name?>_sectionids_fieldarea').show();
		$('sectionsearchButtonContainer').up('tr').show();
	<? } ?>
		$('searchByPerson').checked = false;
		$('<?=$form->name?>_pkey').up('tr').hide();
		$('<?=$form->name?>_phone').up('tr').hide();
		$('<?=$form->name?>_email').up('tr').hide();
		$('personsearchButtonContainer').up('tr').hide();
	}

	function choose_search_by_person() {
		$('searchByRules').checked = false;
		$('ruleWidgetContainer').up('tr').hide();
	<? if (getSystemSetting('_hasenrollment')) { ?>
		$('searchBySections').checked = false;
		$('<?=$form->name?>_sectionids_fieldarea').hide();
		$('sectionsearchButtonContainer').up('tr').hide();
	<? } ?>
		$('searchByPerson').checked = true;
		$('<?=$form->name?>_pkey').up('tr').show();
		$('<?=$form->name?>_phone').up('tr').show();
		$('<?=$form->name?>_email').up('tr').show();
		$('personsearchButtonContainer').up('tr').show();
	}

	function list_clear_person() {
		$('listsearch_pkey').value = '';
		$('listsearch_phone').value = '';
		$('listsearch_email').value = '';
	}
	
	function rulewidget_add_rule(event) {
		$('listsearch_ruledata').value = event.memo.ruledata.toJSON();
		list_clear_person();
		form_submit(event, 'addrule');
	}

	function rulewidget_delete_rule(event) {
		$('listsearch_ruledata').value = event.memo.fieldnum;
		list_clear_person();
		form_submit(event, 'deleterule');
	}
	
	document.observe('dom:loaded', function() {
		ruleWidget.delayActions = true;
		ruleWidget.container.observe('RuleWidget:AddRule', rulewidget_add_rule);
		ruleWidget.container.observe('RuleWidget:DeleteRule', rulewidget_delete_rule);
		
<?
		if (isset($_SESSION['listsearch']['individual']))
			echo 'choose_search_by_person();';
		else if (isset($_SESSION['listsearch']['sectionids']))
			echo 'choose_search_by_sections();';
		else 
			echo 'choose_search_by_rules();';
?>
	});
</script>
