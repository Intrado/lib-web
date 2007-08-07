<?
// phone inbound, prompt to select list (page into sets of 9), then save listid

include_once("inboundutils.inc.php");
include_once("../obj/PeopleList.obj.php");

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

function playLists($incr)
{
	global $SESSIONDATA;

	$lists = loadLists($incr);
	global $SESSIONID;
?>
<voice sessionid="<?= $SESSIONID ?>">
	<message name="listdirectory">
<?	if (count($lists) == 0) { ?>
		<audio cmid="file://prompts/inbound/NoLists.wav" />
		<hangup />
<?	} ?>

		<field name="listnumber" type="menu" timeout="5000" sticky="true">
			<prompt repeat="2">
				<audio cmid="file://prompts/inbound/PleaseSelectList.wav" />
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

function confirmList($listname)
{
	global $SESSIONID;
?>
<voice sessionid="<?= $SESSIONID ?>">
	<message name="listconfirm">

		<field name="uselist" type="menu" timeout="5000" sticky="true">
			<prompt repeat="2">
				<audio cmid="file://prompts/inbound/ListChoice.wav" />
				<tts gender="female"><?= htmlentities($listname, ENT_COMPAT, "UTF-8") ?></tts>
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

error_log("gjb list ".$REQUEST_TYPE);

if($REQUEST_TYPE == "continue") {

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
	} else if (isset($BFXML_VARS['confirmContinue'])) {
		playLists(true);
	// confirm that they wish to continue setting up their job, or they exit after recording messages
	} else {
		confirmContinue();
	}

} else {
	$SESSIONDATA=null;
}

?>