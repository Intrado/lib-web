<?
// phone inbound, prompt to select list (page into sets of 9), then save listid

include_once("../obj/User.obj.php");
include_once("../obj/Rule.obj.php");
include_once("../obj/Access.obj.php");
include_once("../obj/Job.obj.php");
include_once("../obj/JobLanguage.obj.php");
include_once("../obj/JobType.obj.php");
include_once("../obj/Permission.obj.php");
include_once("../obj/PeopleList.obj.php");
include_once("../obj/RenderedList.obj.php");
include_once("../obj/FieldMap.obj.php");
include_once("inboundutils.inc.php");


global $SESSIONDATA, $BFXML_VARS;

$PAGESIZE = 9;


function confirmContinue()
{
	global $SESSIONID;
?>
<voice sessionid="<?= $SESSIONID ?>">
	<message name="confirmContinueList">
		<field name="confirmContinue" type="menu" timeout="5000" sticky="true">
			<prompt repeat="1">
				<audio cmid="file://prompts/inbound/SelectList.wav" />
			</prompt>
			<choice digits="1" />
			<default>
				<audio cmid="file://prompts/ImSorry.wav" />
			</default>
			<timeout>
				<goto message="error2" />
			</timeout>
		</field>

	</message>

	<message name="error">
		<audio cmid="file://prompts/inbound/Error.wav" />
		<hangup />
	</message>

	<message name="error2">
		<audio cmid="file://prompts/GoodBye.wav" />
		<hangup />
	</message>
</voice>
<?
}

function loadListsDB()
{
	global $SESSIONDATA;
	return DBFindMany("PeopleList",", (name +0) as foo from list where userid=".$SESSIONDATA['userid']." and deleted=0 order by foo,name");
}

function loadLists($incr)
{
	global $SESSIONDATA, $PAGESIZE;
	if (!isset($PAGESIZE)) $PAGESIZE = 9; // this is strange... why isnt it set the first time from above???
	glog("pagesize: ".$PAGESIZE);

	// TODO should find way to save lists on the sessiondata, do not want to query database more than once
/*
	// if we have not loaded the full list of lists from the database (we only want to query the database once)
	if (!isset($SESSIONDATA['allLists'])) {
		$allLists = array_values(loadListsDB()); // convert indexes to 0, 1, 2, ...

		$SESSIONDATA['allLists'] = $allLists;
	} else {
		$allLists = $SESSIONDATA['allLists'];
	}
*/
	$allLists = array_values(loadListsDB()); // convert indexes to 0, 1, 2, ...

	// if first time, set to 0
	if (!isset($SESSIONDATA['currentListPage'])) {
		$SESSIONDATA['currentListPage'] = 0;
	// if increment
	} else if ($incr) {
		$SESSIONDATA['currentListPage']++;
		// if page wrap to beginning
		if (count($allLists) <= ($SESSIONDATA['currentListPage'])*$PAGESIZE) {
			$SESSIONDATA['currentListPage'] = 0;
		}
	}

	glog("currentListPage: ".$SESSIONDATA['currentListPage']);

	$SESSIONDATA['hasPaging'] = false;
	if (count($allLists) > $PAGESIZE) {
		$SESSIONDATA['hasPaging'] = true;
	}
	// group lists into sets of 9 (digits 1-9 on the phone)
	$listSubset = array_slice($allLists, $SESSIONDATA['currentListPage']*$PAGESIZE, $PAGESIZE, true);
	return $listSubset; // the list of lists for this user, page includes no more than 9
}

function playLists($incr, $emptylist = false, $playprompt=true)
{
	glog("playlists, empty? ".$emptylist);

	global $SESSIONDATA;

	$lists = loadLists($incr);
	global $SESSIONID;
?>
<voice sessionid="<?= $SESSIONID ?>">

<?  if ($emptylist) { ?>
	<message name="emptylist">
		<audio cmid="file://prompts/inbound/EmptyList.wav" />
	</message>
<?  } ?>

	<message name="listdirectory">
<?	if (count($lists) == 0) { ?>
		<audio cmid="file://prompts/inbound/NoLists.wav" />
		<hangup />
<?	} ?>

		<field name="listnumber" type="menu" timeout="5000" sticky="true">
			<prompt repeat="2">
<?				if ($playprompt) { ?>
					<audio cmid="file://prompts/inbound/PleaseSelectList.wav" />
<?				} ?>

<?
				$listindex = 1;
				foreach ($lists as $list)
				{
?>
					<audio cmid="file://prompts/inbound/Press<?= $listindex ?>For.wav" />
					<tts gender="female"><?= htmlentities($list->name, ENT_COMPAT, "UTF-8") ?></tts>
<?
					$listindex++;
				}
				// if lists are on pages, provide * option
				if ($SESSIONDATA['hasPaging']) {
?>
					<audio cmid="file://prompts/inbound/PressStarToHearMoreLists.wav" />
<?
				}
?>
			</prompt>

<?
			$listindex = 1;
			foreach ($lists as $list)
			{
?>
				<choice digits="<?= $listindex ?>" />
<?
				$listindex++;
			}
			// if lists are on pages, provide * option
			if ($SESSIONDATA['hasPaging']) {
?>
				<choice digits="*" />
<?
			}
?>

			<default>
				<audio cmid="file://prompts/ImSorry.wav" />
			</default>
			<timeout>
				<goto message="error" />
			</timeout>
		</field>
	</message>

	<message name="error">
		<audio cmid="file://prompts/inbound/Error.wav" />
		<hangup />
	</message>
</voice>
<?
}


/////////////////

if($REQUEST_TYPE == "new"){
	?>
	<error>inboundlist: wanted continue, got new </error>
	<?
} else if($REQUEST_TYPE == "continue") {

	// if they selected a list
	if (isset($BFXML_VARS['listnumber'])) {

		$listnumber = $BFXML_VARS['listnumber'];
		glog("list number selected: ".$listnumber);

		// if they want to hear the next page of lists
		if ($listnumber == "*") {
			playLists(true, false, false);
		// else confirm the listid is correct
		} else {

			$listindex = ($SESSIONDATA['currentListPage']*$PAGESIZE)+($listnumber-1);
			glog("listindex: ".$listindex);

			$lists = array_values(loadListsDB()); // convert indexes to 0, 1, 2, ...
			//var_dump($lists);
			$list = $lists[$listindex];
			glog("list name: ".$list->name);

			$SESSIONDATA['listid'] = $list->id;
			$SESSIONDATA['listname'] = $list->name;

			loadUser(); // must load user before rendering list
			global $USER, $ACCESS;
			$list = new PeopleList($SESSIONDATA['listid']);
			$renderedlist = new RenderedList($list);
			$renderedlist->mode = "preview";
			$renderedlist->renderList();
			$listsize = $renderedlist->total;
			glog("number of people in list: ".$listsize);

			if ($listsize == 0) {
				playLists(true, true);
			} else {
				// they already entered job options, but returned to select a different list
				// so keep their options and replay the confirm
				if ( isset($SESSIONDATA['listname']) &&
					isset($SESSIONDATA['priority']) &&
					isset($SESSIONDATA['numdays']) &&
					isset($SESSIONDATA['starttime']) &&
					isset($SESSIONDATA['stoptime'])) {

					forwardToPage("inboundjob.php");
				} else {
					// user selected list, go to job options
					forwardToPage("inboundjobtype.php");
				}
			}
		}
	// play the current page of lists
	} else if (isset($BFXML_VARS['confirmContinue']) || isset($SESSIONDATA['currentListPage']) || isset($SESSIONDATA['listid'])) {
		playLists(true);

	// confirm that they wish to continue setting up their job, or they exit after recording messages
	} else {
		confirmContinue();
	}

} else {
	//huh, they must have hung up
	$SESSIONDATA = null;
	?>
	<ok />
	<?
}

?>