<?
// phone inbound, prompt to select list (page into sets of 9), then save listid

include_once("inboundutils.inc.php");
include_once("../obj/PeopleList.obj.php");

global $SESSIONDATA, $BFXML_VARS;

$PAGESIZE = 3; // TODO set this to 9


function loadListsDB()
{
	global $SESSIONDATA;
	return DBFindMany("PeopleList",", (name +0) as foo from list where userid=".$SESSIONDATA['userid']." and deleted=0 order by foo,name");
}

function loadLists($incr)
{
	global $SESSIONDATA, $PAGESIZE;
	if (!isset($PAGESIZE)) $PAGESIZE = 3; // this is strange... why isnt it set the first time from above???
	glog("pagesize: ".$PAGESIZE);
/*
	// TODO should find way to save lists on the sessiondata, do not want to query database more than once

	// if we have not loaded the full list of lists from the database (we only want to query the database once)
	if (!isset($SESSIONDATA['allLists'])) {
		glog("load allLists");
		$SESSIONDATA['allLists'] = DBFindMany("PeopleList",", (name +0) as foo from list where userid=".$SESSIONDATA['userid']." and deleted=0 order by foo,name");
		$SESSIONDATA['currentListPage'] = 0;
	} else {
		$SESSIONDATA['currentListPage']++;
	}
*/

	$allLists = array_values(loadListsDB()); // convert indexes to 0, 1, 2, ...
	var_dump($allLists);
	//$allLists = loadListsDB();

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
	var_dump($allLists);
	glog("pagesize: ".$PAGESIZE);
	// group lists into sets of 9 (digits 1-9 on the phone)
	$listSubset = array_slice($allLists, $SESSIONDATA['currentListPage']*$PAGESIZE, $PAGESIZE, true);
	glog("listSubset count: ".count($listSubset));
	var_dump($listSubset);
	return $listSubset; // the list of lists for this user, page includes no more than 9
}

function playLists($incr)
{
	global $SESSIONDATA;

	$lists = loadLists($incr);

	global $SESSIONID;
?>
<voice sessionid="<?= $SESSIONID ?>">
	<message name="listdirectory">
		<field name="listnumber" type="menu" timeout="5000" sticky="true">
			<prompt repeat="2">
				<audio cmid="file://prompts/inbound/PleaseSelectList.wav" />
<?
				$listindex = 1;
				foreach ($lists as $list)
				{
?>
					<audio cmid="file://prompts/inbound/PressMiddle2.wav" />
					<audio cmid="file://prompts/inbound/<?= $listindex ?>.wav" />
					<audio cmid="file://prompts/inbound/For.wav" />
					<tts gender="female"><?= htmlentities($list->name) ?></tts>
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

function confirmList($listname)
{
	global $SESSIONID;
?>
<voice sessionid="<?= $SESSIONID ?>">
	<message name="listconfirm">

		<field name="uselist" type="menu" timeout="5000" sticky="true">
			<prompt repeat="2">
				<audio cmid="file://prompts/inbound/ListChoice.wav" />
				<tts gender="female"><?= htmlentities($listname) ?></tts>
				<audio cmid="file://prompts/inbound/ValidateList.wav" />
			</prompt>

			<choice digits="1" />
			<choice digits="2" />

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

	// if they selected a list
	if (isset($BFXML_VARS['listnumber'])) {

		$listnumber = $BFXML_VARS['listnumber'];
		glog("list number selected: ".$listnumber);

		// if they want to hear the next page of lists
		if ($listnumber == "*") {
			playLists(true);
		// else confirm the listid is correct
		} else {

			$listindex = ($SESSIONDATA['currentListPage']*$PAGESIZE)+($listnumber-1);
			glog("listindex: ".$listindex);

			$lists = array_values(loadListsDB()); // convert indexes to 0, 1, 2, ...
			//var_dump($lists);
			$list = $lists[$listindex];
			glog("list name: ".$list->name);
			confirmList($list->name);

			$SESSIONDATA['listid'] = $list->id;
			$SESSIONDATA['listname'] = $list->name;
			$SESSIONDATA['step'] = "uselist";
		}
	// if they confirmed the list selection
	} else if (isset($BFXML_VARS['uselist'])) {

		if ($BFXML_VARS['uselist'] == "1") {
			// user confirmed they wish to use the selected list, go to job options
			forwardToPage("inboundjob.php");
		} else {
			// user does not want selected list
			playLists(false); // do not increment the page
		}
	// play the current page of lists
	} else {
		playLists(true);
	}
?>