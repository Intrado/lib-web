<?
//set expire time to + 1 hour so browsers cache this file
header("Expires: " . gmdate('D, d M Y H:i:s', time() + 60*60) . " GMT"); //exire in 1 hour, but if theme changes so will hash pointing to this file
header("Content-Type: text/javascript");
header("Cache-Control: private");
?>

Validator::load_validators(array("ValSections", "ValRules"));

function choose_search_by_rules() {
	$('searchByRules').checked = true;
	$('ruleWidgetContainer').up('tr').show();
	if ($('searchBySections')) {
		$('searchBySections').checked = false;
		$('listsearch_sectionids_fieldarea').hide();
		$('sectionsearchButtonContainer').up('tr').hide();
	}
	$('searchByPerson').checked = false;
	$('listsearch_pkey').up('tr').hide();
	$('listsearch_phone').up('tr').hide();
	$('listsearch_email').up('tr').hide();
	$('personsearchButtonContainer').up('tr').hide();
}

function choose_search_by_sections() {
	$('searchByRules').checked = false;
	$('ruleWidgetContainer').up('tr').hide();
	if ($('searchBySections')) {
		$('searchBySections').checked = true;
		$('listsearch_sectionids_fieldarea').show();
		$('sectionsearchButtonContainer').up('tr').show();
	}
	$('searchByPerson').checked = false;
	$('listsearch_pkey').up('tr').hide();
	$('listsearch_phone').up('tr').hide();
	$('listsearch_email').up('tr').hide();
	$('personsearchButtonContainer').up('tr').hide();
}

function choose_search_by_person() {
	$('searchByRules').checked = false;
	$('ruleWidgetContainer').up('tr').hide();
	if ($('searchBySections')) {
		$('searchBySections').checked = false;
		$('listsearch_sectionids_fieldarea').hide();
		$('sectionsearchButtonContainer').up('tr').hide();
	}
	$('searchByPerson').checked = true;
	$('listsearch_pkey').up('tr').show();
	$('listsearch_phone').up('tr').show();
	$('listsearch_email').up('tr').show();
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
