<?


/**
 * Displays a table of the rederedlist data and field togglers.
 * May also display list checkbox if list is specified.
 * @param $renderedlist
 * @param $list
 * @return unknown_type
 */
function showRenderedListTable($renderedlist, $list = false) {
	global $PAGEINLISTMAP,$USER;
	static $tableidcounter = 1;
	
	
	$validsortfields = array("pkey" => "Unique ID");
	foreach (FieldMap::getAuthorizedFieldMapsLike("f") as $fieldmap) {
		$validsortfields[$fieldmap->fieldnum] = $fieldmap->name;
	}
	
	$ordering = isset($_SESSION['showlistorder']) ? $_SESSION['showlistorder'] : array(array("f02", false),array("f01",false));
	for ($x = 0; $x < 3; $x++) {
		if (!isset($_GET["sort$x"]))
			continue;
		if ($_GET["sort$x"] == "")
			unset($ordering[$x]);
		else if (isset($validsortfields[$_GET["sort$x"]])) {
			$ordering[$x] = array($_GET["sort$x"],isset($_GET["desc$x"]));
		}
	}
	$_SESSION['showlistorder'] = $ordering = array_values($ordering); //remove gaps
	
	
	$pagestart = (isset($_GET['pagestart']) ? $_GET['pagestart'] + 0 : 0);
	$renderedlist->pageoffset = $pagestart;
	$renderedlist->orderby = $ordering;
	$data = $renderedlist->getPageData();
	$total = $renderedlist->getTotal();
	
	$showinlist = $list && $list->userid == $USER->id;
	
	if ($showinlist)
		$PAGEINLISTMAP = $renderedlist->getPageInListMap($list);
	
	$titles = array();
	$formatters = array();
	$repeatedcolumns = array(2,3); //sequence, destination
	$groupby = 0; //personid
	
	//set up formatters and titles for the basic fields.
	if ($showinlist) {
		$titles[0] = "In List";
		$formatters[0] = "fmt_checkbox";	
	}
	
	$titles[1] = "Unique ID";
	$formatters[1] = "fmt_persontip";
	
	$titles[6] = FieldMap::getName(FieldMap::getFirstNameField());
	$titles[7] = FieldMap::getName(FieldMap::getLastNameField());

	$titles[2] = "Sequence";
	$formatters[2] = "fmt_renderedlist_destination_sequence";
	
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
	$optionalfieldstart = $showinlist ? 6 : 5; //table col of last non optional field
	select_metadata($tableid,$optionalfieldstart,$optionalfields);
	showSortMenu($validsortfields,$ordering);
	
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

function fmt_checkbox($row, $index) {
	global $PAGEINLISTMAP;
	
	$personid = $row[$index];
	
	$checked = '';
	if (isset($PAGEINLISTMAP[$personid]))
		$checked = 'checked';
	
	$onclick = "do_ajax_listbox(this, $personid);";
	return "<input type=\"checkbox\" onclick=\"$onclick\" $checked />";
}

?>