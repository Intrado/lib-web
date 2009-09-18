<?

function list_clear_search_session($keep = false) {
	if ($keep != 'listsearchshowall')
		$_SESSION['listsearchshowall'] = false;

	if ($keep != 'listsearchperson') {
		$_SESSION['listsearchperson'] = false;
		$_SESSION['listsearchpkey'] = false;
		$_SESSION['listsearchphone'] = false;
		$_SESSION['listsearchemail'] = false;
		$_SESSION['listsearchsms'] = false;
	}

	if ($keep != 'listsearchrules')
		$_SESSION['listsearchrules'] = array();
}

function listentry_get_type($personid) {
	return QuickQuery("SELECT type FROM listentry WHERE personid=? AND listid=?", false, array($personid, $_SESSION['listid']));
}

function listentry_delete($personid) {
	QuickUpdate("delete from listentry where listid=? and personid=?", false, array($_SESSION['listid'], $personid));
}

function listentry_insert($personid, $type) {
	$person = new Person($personid);
	if ($person->type != 'system' && $type == 'A' && !userOwns('person', $personid))
		return;
	QuickUpdate("INSERT INTO listentry (listid, type, personid) VALUES(?,?,?)", false, array($_SESSION['listid'], $type, $personid));
}

function list_add_person($personid) {
	if ($type = listentry_get_type($personid)) {
		if ($type == 'A') // Already in the additions section.
			return;
		else if ($type == 'N') // Already in the skips section.
			listentry_delete($personid);
	} else {
		listentry_insert($personid, 'A');
	}
}

function list_remove_person($personid) {
	if ($type = listentry_get_type($personid)) {
		if ($type == 'N') // Already in the skips section.
			return;
		else if ($type == 'A') // Already in the additions section.
			listentry_delete($personid);
	} else {
		listentry_insert($personid, 'N');
	}
}

function fmt_list_destination_sequence($row, $index){
	// index 8 is the type of destination: phone, email, or sms.
	if($row[8] != "" || $row[8] != false){
		return destination_label($row[8], $row[$index]);
	} else {
		return "";
	}
}

function list_handle_ajax_table($renderedlist, $validContainers) {
	global $USER;

	if (!isset($_GET['ajax']))
		return;
	if (empty($_GET['addpersonid']) && empty($_GET['removepersonid']) && empty($_GET['containerID']) && empty($_GET['addtoggler']) && empty($_GET['removetoggler']))
		return;

	header('Content-Type: application/json');
		$ajaxdata = false;
		QuickUpdate("BEGIN");
			if (!empty($_GET['addpersonid'])) {
				$restrictUserSQL = $USER->userSQL("P");
				if ($validPerson = QuickQuery("SELECT P.id FROM person P WHERE P.id=? AND (P.userid=0 OR P.userid=? OR (1 $restrictUserSQL))", false, array($_GET['addpersonid'], $USER->id))) {
					$ajaxdata = true;
					list_add_person($validPerson);
				}
			} else if (!empty($_GET['removepersonid'])) {
				$ajaxdata = true;
				list_remove_person($_GET['removepersonid']);
			} else if (isset($_GET['containerID']) && in_array($_GET['containerID'], $validContainers)) {
				$ajaxdata = array('html' => list_prepare_ajax_table($_GET['containerID'], $renderedlist));
			}
		QuickUpdate("COMMIT");
	exit(json_encode($ajaxdata));
}

function list_prepare_ajax_table($containerID, $renderedlist) {
	global $USER;
	ajax_table_handle_togglers($containerID);

	switch ($containerID) {
		case 'listAdditionsContainer':
			$renderedlist->prepareAdditionsMode(100);
			break;
		case 'listSkipsContainer':
			$renderedlist->prepareSkipsMode(100);
			break;
		case 'listPreviewContainer':
		case 'listSearchContainer':
			if (!empty($_SESSION['listsearchshowall']))
				$renderedlist->prepareRulesMode(100, false);
			else if (!empty($_SESSION['listsearchperson']))
				$renderedlist->preparePeopleMode(100, $_SESSION['listsearchpkey'], $_SESSION['listsearchphone'], $_SESSION['listsearchemail']);
			else if (!empty($_SESSION['listsearchrules']))
				$renderedlist->prepareRulesMode(100, $_SESSION['listsearchrules']);
			else {
				if ($containerID == 'listPreviewContainer')
					$renderedlist->prepareRulesMode(100, false);
				else
					return _L('No search criteria');
			}
			break;
		default:
			return false;
	}

	$allFieldmaps = FieldMap::getMapNames();
	$titles = array(
		0 => "In List",
		2 => "ID #",
		3 => $allFieldmaps[$renderedlist->firstname],
		4 => $allFieldmaps[$renderedlist->lastname]
	);
	$sorting = array(
		2 => "pkey",
		3 => $renderedlist->firstname,
		4 => $renderedlist->lastname
	);
	$formatters = array(
		0 => "fmt_checkbox",
		2 => "fmt_persontip"
	);
	$searchable = in_array($renderedlist->mode, array("rules", "people"));
	if ($searchable) {
		$titles[5] = "Address";
		$titles[6] = "Sequence";
		$titles[7] = "Destination";

		$formatters[6] = "fmt_list_destination_sequence";
		$formatters[7] = "fmt_destination";

		$sorting[5] = "address";

		$fieldmaps = FieldMap::getOptionalAuthorizedFieldMapsLike('f') + FieldMap::getOptionalAuthorizedFieldMapsLike('g');
		// Reserve index 8, destination type, for formatter
		$i = 9;
		// NOTE: $row[8] indicates the destination's type, which is phone, email or sms.
		foreach ($fieldmaps as $fieldmap) {
			$titles[$i] = '@' . $fieldmap->name;

			// NOTE: Only allow sorting by f-fields.
			if ($fieldmap->fieldnum[0] == 'f')
				$sorting[$i] = $fieldmap->fieldnum;
			$i++;
		}

		// Sequence and Destination Columns are repeated.
		$repeatedColumns = array(6,7);
		// Group by Person ID, not PKEY
		$groupBy = 1;
	} else {
		$authorizedFieldmaps = FieldMap::getAuthorizedMapNames();
		if (isset($authorizedFieldmaps[$renderedlist->language])) {
			$titles[5] = $authorizedFieldmaps[$renderedlist->language];
			$sorting[5] = $renderedlist->language;
		}
	}

	$orderbySQL = ajax_table_get_orderby($containerID, $sorting);
	if (!empty($orderbySQL))
		$renderedlist->orderby = $orderbySQL;

	$renderedlist->hasstats = false;

	if (!isset($_SESSION['ajaxtablepagestart']) || !isset($_GET['ajax']))
		$_SESSION['ajaxtablepagestart'] = array();
	if (isset($_GET['start']) && isset($_GET['containerID']))
		$_SESSION['ajaxtablepagestart'][$_GET['containerID']] = $_GET['start'] + 0;

	$data = $renderedlist->getPage(isset($_SESSION['ajaxtablepagestart'][$containerID]) ? $_SESSION['ajaxtablepagestart'][$containerID] : 0, $renderedlist->pagelimit);

	if (empty($data)) {
		if ($containerID == 'listSearchContainer')
			return _L('Searched, but found no results');
		else if ($containerID == 'listPreviewContainer')
			return _L('Nobody in your list!');
	}


	return ajax_table_show_menu($containerID, $renderedlist->total, $renderedlist->pageoffset, $renderedlist->pagelimit) . ajax_show_table($containerID, $data, $titles, $formatters, $sorting, isset($repeatedColumns) ? $repeatedColumns : false, isset($groupBy) ? $groupBy : false, ($searchable ? 3 : 0), ($searchable ? true : false), ($searchable ? false : true));
}

function list_get_results_html($containerID, $renderedlist) {
	$resultsHtml = list_prepare_ajax_table($containerID, $renderedlist);
	if (empty($renderedlist->pageids))
		return _L("No results");
	return $resultsHtml;
}
?>