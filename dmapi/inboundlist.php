<?
// phone inbound, prompt to select list (page into sets of 9), then save listid

include_once("../obj/Organization.obj.php");
include_once("../obj/Section.obj.php");
include_once("../obj/User.obj.php");
include_once("../obj/Rule.obj.php");
include_once("../obj/Access.obj.php");
include_once("../obj/Job.obj.php");
include_once("../obj/JobType.obj.php");
include_once("../obj/Permission.obj.php");
include_once("../obj/PeopleList.obj.php");
include_once("../obj/RenderedList.obj.php");
include_once("../obj/FieldMap.obj.php");
include_once("inboundutils.inc.php");
require_once("../inc/date.inc.php");


global $BFXML_VARS;

$PAGESIZE = 9;


function confirmContinue()
{
?>
<voice>
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
	$query = ", (name +0) as foo
		from list l
			inner join user u on
				(l.userid = u.id)
			left join publish p on
				(p.listid = l.id and p.userid = ? and p.action = 'subscribe')
		where l.type in ('person','section')
			and (l.userid = ? or p.userid = ?)
			and not l.deleted
		 order by foo,name";
	return DBFindMany("PeopleList", $query, "l", array($_SESSION['userid'], $_SESSION['userid'], $_SESSION['userid']));
}

function loadLists($incr)
{
	global $PAGESIZE;
	if (!isset($PAGESIZE)) $PAGESIZE = 9; // this is strange... why isnt it set the first time from above???
	//error_log("pagesize: ".$PAGESIZE);

	// TODO should find way to save lists on the sessiondata, do not want to query database more than once
/*
	// if we have not loaded the full list of lists from the database (we only want to query the database once)
	if (!isset($_SESSION['allLists'])) {
		$allLists = array_values(loadListsDB()); // convert indexes to 0, 1, 2, ...

		$_SESSION['allLists'] = $allLists;
	} else {
		$allLists = $_SESSION['allLists'];
	}
*/
	$allLists = array_values(loadListsDB()); // convert indexes to 0, 1, 2, ...

	// if first time, set to 0
	if (!isset($_SESSION['currentListPage'])) {
		$_SESSION['currentListPage'] = 0;
	// if increment
	} else if ($incr) {
		$_SESSION['currentListPage']++;
		// if page wrap to beginning
		if (count($allLists) <= ($_SESSION['currentListPage'])*$PAGESIZE) {
			$_SESSION['currentListPage'] = 0;
		}
	}

	//error_log("currentListPage: ".$_SESSION['currentListPage']);

	$_SESSION['hasPaging'] = false;
	if (count($allLists) > $PAGESIZE) {
		$_SESSION['hasPaging'] = true;
	}
	// group lists into sets of 9 (digits 1-9 on the phone)
	$listSubset = array_slice($allLists, $_SESSION['currentListPage']*$PAGESIZE, $PAGESIZE, true);
	return $listSubset; // the list of lists for this user, page includes no more than 9
}

function playLists($incr, $emptylist = false, $playprompt=true)
{
	//error_log("playlists, empty? ".$emptylist);

	$lists = loadLists($incr);
?>
<voice>

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
					<tts gender="female"><?= escapehtml($list->name) ?></tts>
<?
					$listindex++;
				}
				// if lists are on pages, provide * option
				if ($_SESSION['hasPaging']) {
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
			if ($_SESSION['hasPaging']) {
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
	<hangup />
	<?
} else if($REQUEST_TYPE == "continue") {

	// if they selected a list
	if (isset($BFXML_VARS['listnumber'])) {

		$listnumber = $BFXML_VARS['listnumber'] +0;
		//error_log("list number selected: ".$listnumber);

		// if they want to hear the next page of lists
		if ($listnumber == "*") {
			playLists(true, false, false);
		// else confirm the listid is correct
		} else {

			$listindex = ($_SESSION['currentListPage']*$PAGESIZE)+($listnumber-1);
			//error_log("listindex: ".$listindex);

			$lists = array_values(loadListsDB()); // convert indexes to 0, 1, 2, ...
			//var_dump($lists);
			$list = $lists[$listindex];
			//error_log("list name: ".$list->name);

			$_SESSION['listid'] = $list->id;
			$_SESSION['listname'] = $list->name;

			loadUser(); // must load user before rendering list
			global $USER, $ACCESS;
			$list = new PeopleList($_SESSION['listid']);
			$renderedlist = new RenderedList2();
			$renderedlist->initWithList($list);
			$listsize = $renderedlist->getTotal();
			//error_log("number of people in list: ".$listsize);

			if ($listsize == 0) {
				playLists(true, true);
			} else {
				// they already entered job options, but returned to select a different list
				// so keep their options and replay the confirm
				if ( isset($_SESSION['listname']) &&
					isset($_SESSION['priority']) &&
					isset($_SESSION['numdays']) &&
					isset($_SESSION['starttime']) &&
					isset($_SESSION['stoptime'])) {

					forwardToPage("inboundjob.php");
				} else {
					// user selected list, go to job options
					forwardToPage("inboundjobtype.php");
				}
			}
		}
	// play the current page of lists
	} else if (isset($BFXML_VARS['confirmContinue']) || isset($_SESSION['currentListPage']) || isset($_SESSION['listid'])) {
		playLists(true);

	// confirm that they wish to continue setting up their job, or they exit after recording messages
	} else {
		confirmContinue();
	}

} else {
	//huh, they must have hung up
	$_SESSION = array();
	?>
	<ok />
	<?
}

?>
