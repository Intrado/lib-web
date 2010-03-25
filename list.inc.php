<?


/**
 * Displays a table of the rederedlist data and field togglers.
 * May also display list checkbox if list is specified.
 * @param $renderedlist
 * @param $list
 * @return unknown_type
 */
function showRenderedListTable($renderedlist, $list = false) {
	global $PAGEINLISTMAP;
	static $tableidcounter = 1;
	
	$pagestart = (isset($_GET['pagestart']) ? $_GET['pagestart'] + 0 : 0);
	$renderedlist->pageoffset = $pagestart;
	$data = $renderedlist->getPageData();
	$total = $renderedlist->getTotal();
	
	if ($list)
		$PAGEINLISTMAP = $renderedlist->getPageInListMap($list);
	
	$titles = array();
	$formatters = array();
	$repeatedcolumns = array(2,3); //sequence, destination
	$groupby = 0; //personid
	
	//set up formatters and titles for the basic fields.
	if ($list) {
		$titles[0] = "In List";
		$formatters[0] = "fmt_checkbox";	
	}
	
	$titles[1] = "Unique ID";
	$formatters[1] = "fmt_persontip";
	
	$titles[6] = FieldMap::getName(FieldMap::getFirstNameField());
	$titles[7] = FieldMap::getName(FieldMap::getLastNameField());

	$titles[2] = "Sequence";
	$formatters[2] = "fmt_list_destination_sequence";
	
	$titles[3] = "Destination";
	$formatters[3] = "fmt_destination";
	
	$titles[5] = "Organization";
	
	//after that, show F fields, then G fields
	//optional F fields start at index 8 (skip f01, f02)
	//save some data for field show/hide tool
	
	//show field togglers, reuse whats in reportutils.inc.php (needs to be refactored)
	//FIXME UGLY HACK: need to set global session var to control behavior of this function
	//FIXME UGLY HACK: this function also has the side effect of loading $_SESSION['report']['fields'] display prefs
	$_SESSION['saved_report'] = false; //this causes checkbox states to be loaded/saved in userprefs
	
	$tableid = "renderedlist". $tableidcounter++;
	$optionalfields = array_merge(FieldMap::getOptionalAuthorizedFieldMapsLike('f'), FieldMap::getAuthorizedFieldMapsLike('g'));
	$optionalfieldstart = $list ? 6 : 5; //table col of last non optional field
	select_metadata($tableid,$optionalfieldstart,$optionalfields);
	
	//now use session display prefs to set up titles and whatnot for the optional fields
	$i = 8;
	foreach ($optionalfields as $field) {
		//add a formatter for language field
		if ($field->fieldnum == FieldMap::getLanguageField())
			$formatters[$i] = "fmt_languagecode";
		
		if (isset($_SESSION['report']['fields'][$field->fieldnum]) && $_SESSION['report']['fields'][$field->fieldnum])
			$titles[$i++] = $field->name;
		else
			$titles[$i++] = "@" . $field->name;
	}

	showPageMenu($total,$pagestart,$renderedlist->pagelimit);
	echo '<table id="'.$tableid.'" width="100%" cellpadding="3" cellspacing="1" class="list">';
	showTable($data, $titles, $formatters, $repeatedcolumns, $groupby);
	echo "\n</table>";
	showPageMenu($total,$pagestart,$renderedlist->pagelimit);
}

/**
 * Reads $_GET['addpersonid'] or isset($_GET['removepersonid'], and handles them accordingly to the current list.
 * Will exit early when handling an ajax request.
 * @return unknown_type
 */
function handle_list_checkbox_ajax () {
	global $USER;
	
	if (!isset($_GET['ajax']))
		return;
	
	if (isset($_GET['addpersonid'])) {
		$id = $_GET['addpersonid'];
		$existingtype = QuickQuery("select type from listentry where personid=? and listid=?", false, array($id, $_SESSION['listid']));
		if ($existingtype == "negate") {
			//must be a skip, so delete the skip entry
			QuickUpdate("delete from listentry where personid=? and listid=?",false,array($id, $_SESSION['listid']));
		} else if ($existingtype == null) {
			//see if user can see this person
			if ($USER->canSeePerson($id)) {
				QuickUpdate("insert into listentry (listid, type, personid) values (?,'add',?)",false,array($_SESSION['listid'], $id));
			}
		} else {
			header('Content-Type: application/json');
			exit(json_encode(false));
		}
		header('Content-Type: application/json');
		exit(json_encode(true));
	}
	
	if (isset($_GET['removepersonid'])) {
		error_log("removepersonid");
		$id = $_GET['removepersonid'];
		$existingtype = QuickQuery("select type from listentry where personid=? and listid=?", false, array($id, $_SESSION['listid']));
		if ($existingtype == "add") {
			//must be an add, so delete the add entry
			QuickUpdate("delete from listentry where personid=? and listid=?",false,array($id, $_SESSION['listid']));
		} else if ($existingtype == null) {
			QuickUpdate("insert into listentry (listid, type, personid) values (?,'negate',?)",false,array($_SESSION['listid'], $id));
		} else {
			header('Content-Type: application/json');
			exit(json_encode(false));
		}
		
		header('Content-Type: application/json');
		exit(json_encode(true));
	}

}


function fmt_persontip ($row, $index) {
	global $USER;

	$pkey = escapehtml($row[1]);
	$personid = $row[0];
	
	return "<a href=\"viewcontact.php?id=$personid\" class=\"actionlink\">" 
		. "<img src=\"img/icons/diagona/16/049.gif\" /> $pkey</a>";
}


function fmt_list_destination_sequence($row, $index){
	// destination type is +2 to the sequence index
	$typeindex = $index+2;
	if($row[$typeindex] != "" || $row[$typeindex] != false){
		return escapehtml(destination_label($row[$typeindex], $row[$index]));
	} else {
		return "";
	}
}


function fmt_checkbox($row, $index) {
	global $PAGEINLISTMAP;
	
	$personid = $row[$index];

	$checked = '';
	if (isset($PAGEINLISTMAP[$personid]))
		$checked = 'checked';
	
	$onclick = "do_ajax_listbox(this, $personid);";
	return "<input type=\"checkbox\" onclick=\"$onclick\" $checked />";
}


/************************ PAST THIS POINT THERE BE DRAGONS ************************************/

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
	
	if ($keep != 'listsearchsectionids') {
		$_SESSION['listsearchsectionids'] = array();
	}

	if ($keep != 'listsearchrules') {
		$_SESSION['listsearchrules'] = array();
	}
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
		if ($type == 'add') // Already in the additions section.
			return;
		else if ($type == 'negate') // Already in the skips section.
			listentry_delete($personid);
	} else {
		listentry_insert($personid, 'add');
	}
}

function list_remove_person($personid) {
	if ($type = listentry_get_type($personid)) {
		if ($type == 'negate') // Already in the skips section.
			return;
		else if ($type == 'add') // Already in the additions section.
			listentry_delete($personid);
	} else {
		listentry_insert($personid, 'negate');
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
			$renderedlist->prepareAdditionsMode();
			break;
		case 'listSkipsContainer':
			$renderedlist->prepareSkipsMode();
			break;
		case 'listPreviewContainer':
		case 'listSearchContainer':
			if (!empty($_SESSION['listsearchshowall'])) {
				$renderedlist->prepareRulesMode(false);
			} else if (!empty($_SESSION['listsearchperson'])) {
				$renderedlist->preparePeopleMode($_SESSION['listsearchpkey'], $_SESSION['listsearchphone'], $_SESSION['listsearchemail']);
			} else if (!empty($_SESSION['listsearchrules'])) {
				$rules = $_SESSION['listsearchrules'];
				$organizationids = isset($rules['organization']) ? $rules['organization']['val'] : false;
				unset($rules['organization']);
				
				$renderedlist->prepareRulesMode($rules, $organizationids);
			} else {
				if ($containerID == 'listPreviewContainer')
					$renderedlist->prepareRulesMode(false);
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
			if (FieldMap::getLanguageField() == $fieldmap->fieldnum)
				$formatters[$i] = "fmt_languagecode";
			
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

	return ajax_table_show_menu($containerID, $renderedlist->total, $renderedlist->pageoffset, $renderedlist->pagelimit) 
		. ajax_show_table($containerID, $data, $titles, $formatters, $sorting, isset($repeatedColumns) ? $repeatedColumns : false, isset($groupBy) ? $groupBy : false, ($searchable ? 3 : 0), ($searchable ? true : false), ($searchable ? false : true));
}

function list_get_results_html($containerID, $renderedlist) {
	$resultsHtml = list_prepare_ajax_table($containerID, $renderedlist);
	if (empty($renderedlist->pageids))
		return _L("No results");
	return $resultsHtml;
}
?>
