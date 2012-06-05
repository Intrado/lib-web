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
	
	$showinlist = $list && $list->userid == $USER->id && $USER->authorize('createlist');
	$showpersontip = !$list || ($list && $list->userid == $USER->id);
	
	if ($showinlist)
		$PAGEINLISTMAP = $renderedlist->getPageInListMap($list);
	
	$titles = array();
	$formatters = array();
	$repeatedcolumns = array();
	$groupby = 0; //personid
	
	//set up formatters and titles for the basic fields.
	if ($showinlist) {
		$titles[0] = "In List";
		$formatters[0] = "fmt_checkbox";	
	}
	
	$titles[1] = "Unique ID";
	if ($showpersontip)
		$formatters[1] = "fmt_persontip"; //only show magnifying glass if we own list, or not using a list

	$titles[7] = FieldMap::getName(FieldMap::getFirstNameField());
	$titles[8] = FieldMap::getName(FieldMap::getLastNameField());
	
	$titles[3] = "Destinations";
	$formatters[3] = "fmt_renderedlist_destinations_link";
	
	$titles[6] = getSystemSetting("organizationfieldname","Organization");

	//after that, show F fields, then G fields
	//optional F fields start at index 9 (skip f01, f02)
	//save some data for field show/hide tool
		
	$tableid = "renderedlist". $tableidcounter++;
	$optionalfields = array_merge(FieldMap::getOptionalAuthorizedFieldMapsLike('f'), FieldMap::getAuthorizedFieldMapsLike('g'));
	$optionalfieldstart = $showinlist ? 5 : 4; //table col of last non optional field
		
?>
	<div class="table_controls cf">

		<div class="sortby">
<?
		showSortMenu($validsortfields,$ordering);
?>		
		</div>
		
		<div class="fieldvis">
<?
		show_field_visibility_selector($tableid, $optionalfields, $optionalfieldstart);
?>		
		</div>
<?

	//now use session display prefs to set up titles and whatnot for the optional fields
	$i = 9;
	foreach ($optionalfields as $field) {
		//add a formatter for language field
		if ($field->fieldnum == FieldMap::getLanguageField())
			$formatters[$i] = "fmt_languagecode";
		
		if (isset($_SESSION['fieldvisibility'][$field->fieldnum]))
			$titles[$i++] = $field->name;
		else
			$titles[$i++] = "@" . $field->name;
	}
?>
		<div class="pagenavinfo">
<?
		showPageMenu($total,$pagestart,$renderedlist->pagelimit);
?>		
		</div>

	</div><!-- end table_controls -->
<?

	echo '<table id="'.$tableid.'" width="100%" cellpadding="3" cellspacing="1" class="list">';
	showTable($data, $titles, $formatters, $repeatedcolumns, $groupby);
	echo "\n</table>";
	showPageMenu($total,$pagestart,$renderedlist->pagelimit);
}

function load_user_field_prefs ($fields) {
	
	if (count($fields) == 0)
		return array(); //avoid bothering looking anything up
	
	$fnums = array();
	foreach ($fields as $field) {
		$fnums[$field->fieldnum] = false;
	}
	
	$query = "select name, value from usersetting where name in (" . DBParamListString(count($fnums)) . ")";
	return QuickQueryList($query, true);
}

function show_field_visibility_selector ($tableid, $fields, $coloffset) {
	$id = $tableid . "_displaytools";

	
?>
	<div style="display: none;" id="<?=$id?>">
	<table class="list">
		<tr class="listHeader">
<?
	foreach ($fields as $field) {
?>
		<th>
<?
		echo escapehtml($field->name);
?>
		</th>
<?
	}
?>
		</tr>
		
		<tr>
<?
	$column = 1;
	foreach ($fields as $field) {
		$checked = isset($_SESSION['fieldvisibility'][$field->fieldnum]) ? "checked" : "";
?>
		<td align="center"><input type="checkbox" <?=$checked?> onclick="set_list_fieldvisibility(this, '<?= $field->fieldnum ?>', '<?=$tableid?>', <?=$coloffset + $column?>);" /></td>
<?
		$column++;
	}
?>
		</tr>
	</table>
	</div>
	<div style="cursor:pointer; white-space:nowrap;" id="<?=$id?>_icon"><img src="img/icons/cog.gif" alt="">&nbsp;Show/Hide&nbsp;Fields</div>
	<script type="text/javascript"> new Tip("<?=$id?>_icon",$("<?=$id?>").innerHTML ,{
			style: 'protogrey',
			radius: 4,
			border: 4,
			hideOn: false,
			hideAfter: 0.5,
			stem: "bottomMiddle",
			hook: { target: 'topMiddle', tip: 'bottomMiddle' },
			offset: { x: 0, y: 0 },
			width: 'auto'
		});
	</script>
<?
}


/**
 * Reads $_GET['addpersonid'] or isset($_GET['removepersonid'], and handles them accordingly to the current list.
 * Will exit early when handling an ajax request.
 * @return unknown_type
 */
function handle_list_checkbox_ajax () {
	global $USER;
	
	if (!isset($_GET['ajax']) )
		return;
	
	if (isset($_GET['showfield'])) {
		$optionalfields = array_merge(FieldMap::getOptionalAuthorizedFieldMapsLike('f'), FieldMap::getAuthorizedFieldMapsLike('g'));
		foreach ($optionalfields as $field) {
			if ($_GET['showfield'] == $field->fieldnum)
				$_SESSION['fieldvisibility'][$field->fieldnum] = true;
		}
		
		header('Content-Type: application/json');
		exit(json_encode(true));
	}
	
	if (isset($_GET['hidefield'])) {
		unset($_SESSION['fieldvisibility'][$_GET['hidefield']]);
		
		header('Content-Type: application/json');
		exit(json_encode(true));
	}
	
	
	if ($USER->authorize('createlist')) { //make sure user can edit lists
		
		if (isset($_GET['addpersonid'])) {
			$id = $_GET['addpersonid'];

			$list = new PeopleList($_SESSION['listid']);
			$list->modifydate = date("Y-m-d H:i:s");
			$list->update(array("modifydate"));
			
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
			
			$list = new PeopleList($_SESSION['listid']);
			$list->modifydate = date("Y-m-d H:i:s");
			$list->update(array("modifydate"));
			
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
}

function fmt_checkbox($row, $index) {
	global $PAGEINLISTMAP;
	
	$personid = $row[$index];
	
	$checked = '';
	if (isset($PAGEINLISTMAP[$personid]))
		$checked = 'checked';
	
	return "<input type=\"checkbox\" onclick=\"do_ajax_listbox(this, $personid);\" $checked />";
}

?>