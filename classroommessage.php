<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("inc/common.inc.php");
require_once("inc/securityhelper.inc.php");
require_once("inc/table.inc.php");
require_once("inc/html.inc.php");
require_once("inc/utils.inc.php");
require_once("obj/Validator.obj.php");
require_once("obj/Form.obj.php");
require_once("obj/FormItem.obj.php");
require_once("obj/Event.obj.php");
require_once("obj/Alert.obj.php");
require_once("obj/TargetedMessageCategory.obj.php");
require_once("obj/TargetedMessage.obj.php");
require_once("obj/FieldMap.obj.php");
require_once("obj/Schedule.obj.php");

////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!getSystemSetting('_hastargetedmessage', false) || !$USER->authorize('targetedmessage')) {
	redirect('unauthorized.php');
}

$schedule = DBFind("Schedule","from job j inner join schedule s on (j.scheduleid = s.id) where j.type = 'alert' and j.status = 'repeating'","s");
																								// 15 minutes before Alert Job runs
if(!($schedule && strpos($schedule->daysofweek, (String) (Date('w',time()) + 1)) !== false  && (strtotime($schedule->time) - 900) > time() && strtotime('1.00') < time())) {
	redirect('classroommessageredirect.php');
}

//TODO add redirect if we are past time window

////////////////////////////////////////////////////////////////////////////////
// Settings
////////////////////////////////////////////////////////////////////////////////

$contentfile = "messagedata/en/targetedmessage.php";
$requesturl = "classroommessage.php";
$redirect = "classroommessageoverview.php";
$commentname = "Comment";
$remarkname = "Remark";
$cutoff = Date('H:i',strtotime($schedule->time));

////////////////////////////////////////////////////////////////////////////////
// Action/Request Processing
////////////////////////////////////////////////////////////////////////////////

if (isset($_POST['settab'])) {
	$USER->setSetting("classroomtab", $_POST['settab']);
	exit();
}

if (isset($_POST['eventContacts']) && isset($_POST['eventMessage']) && isset($_POST['isChecked']) && isset($_POST['sectionid'])) {
	header('Content-Type: application/json');
	$contacts = json_decode($_POST['eventContacts']);
	$message = DBSafe($_POST['eventMessage']);
	$sectionid = DBSafe($_POST['sectionid']);
	$ischecked = DBSafe($_POST['isChecked']);


	$args = array();
	foreach($contacts as $contact) {
		$args[] = $contact + 0; // DB Safe! Make sure its an integer
	}
	$args[] = $USER->id;
	$args[] = $message;
	$args[] = $sectionid;
	if($ischecked == "false") {
		$eventids = QuickQueryList("select e.id from personassociation pa left join event e on (pa.eventid = e.id) where pa.personid in (" . repeatWithSeparator("?",",",count($contacts)) . ") and e.userid = ? and e.targetedmessageid = ? and e.sectionid = ? and Date(e.occurence) = CURDATE()",
						false,false,$args);
		if (count($eventids) > 0) {
			$idstr = implode(",",$eventids);
			QuickQuery("BEGIN");
			QuickQuery("delete from alert where eventid in (" . $idstr . ")");
			QuickQuery("delete from personassociation where eventid in (" . $idstr . ")");
			QuickQuery("delete from event where id in (" . $idstr . ")");
			QuickQuery("COMMIT");
		} else {
			echo json_encode(false);
			exit(0);
		}
  	} else {
		$section = DBFind("Section","from section where id = ?",false,array($sectionid));

		if(!$section) {
			echo json_encode(false);
			exit(0);
		}

		$events = QuickQueryList("select pa.personid,e.id from personassociation pa
											left join event e on (pa.eventid = e.id)
											where pa.personid in (" . repeatWithSeparator("?",",",count($contacts)) . ")
											and e.userid = ? and e.targetedmessageid = ? and e.sectionid = ? and Date(e.occurence) = CURDATE()",
											true,false,$args);



		foreach($contacts as $contact) {
			$eventid = isset($events[$contact])?$events[$contact]:null;
			QuickQuery("BEGIN");
			$event = new Event($eventid);
			$event->userid = $USER->id;
			$event->organizationid = $section->organizationid;
			$event->sectionid = $section->id;
			$event->targetedmessageid = $message;
			$event->name = "Teacher Comment";
			if(isset($_POST['eventComments'])) {
				$event->notes = $USER->authorize('targetedmessage')?$_POST['eventComments']:"";
			}
			else if(!$event->notes){
				$event->notes = "";
			}
			$event->occurence = date("Y-m-d H:i:s");
			$event->update();

			// Get the alert since this is a targeted message there should only be one alert
			$alert = ($eventid)?new Alert(QuickQuery("select id from alert where eventid = ?",false,array($event->id))):new Alert();
			$alert->eventid = $event->id;
			$alert->personid = $contact;
			$alert->date = date("Y-m-d");
			$alert->time = date("H:i:s");
			$alert->update();

			if(!$eventid) {
				QuickQuery("insert into personassociation (personid,type,eventid) values (?,?,?)",false,array($contact,'event',$event->id));
			}
			QuickQuery("COMMIT");
		}
	}
	echo json_encode(true);
	exit();
}

//Handle ajax request. when swithcing sections
if (isset($_GET['sectionid'])) {
	header('Content-Type: application/json');
	$id = $_GET['sectionid'];
	$response = false;

	$usersection = QuickQuery("select count(*) from userassociation ua where sectionid = ? and userid = ?",
							false,array($id,$USER->id));
	if($usersection > 0) {
		// User can access this section
		$firstnamefield = FieldMap::getFirstNameField();
		$lastnamefield = FieldMap::getLastNameField();

		$res = Query("select p.id, p.pkey,concat(p.$firstnamefield,' ', p.$lastnamefield) name
											from person p join personassociation pa on (p.id = pa.personid)
											where pa.type = 'section' and sectionid = ? order by p.$firstnamefield,p.$lastnamefield",false,array($id));
		while($row = DBGetRow($res)){
			$obj = null;
			$obj->pkey = escapehtml($row[1]);
			$obj->name = escapehtml($row[2]);
			$response->people[$row[0]] = $obj;
		}
		if(isset($response->people) && count($response->people) > 0) {
			$contactids = array_keys($response->people);
			$query = "select tm.targetedmessagecategoryid, pa.personid, e.targetedmessageid, e.notes from
					  personassociation pa inner join event e on (pa.eventid = e.id)
					  inner join targetedmessage tm on (e.targetedmessageid = tm.id)
					  where e.userid = ? and e.sectionid = ? and Date(e.occurence) = CURDATE() and pa.personid in (" . implode(",",$contactids) . ")";
			$response->cache = QuickQueryMultiRow($query,false,false,array($USER->id,$id));
		} else {
			$response = false;
		}
	}

	echo json_encode($response);
	exit(0);
}


$customtxt = QuickQueryList("select t.id, p.txt from targetedmessage t, message m, messagepart p
										where t.deleted = 0 and
											t.overridemessagegroupid = m.messagegroupid and
											m.languagecode = 'en' and
											p.messageid = m.id and p.sequence = 0",true);

if (isset($_GET['search'])) {
	header('Content-Type: application/json');
	$searchwords = explode(' ',$_GET['search']);
	$searchcount = count($searchwords);

	$messages = DBFindMany("TargetedMessage","from targetedmessage where enabled = 1 order by targetedmessagecategoryid");

	require_once($contentfile);
	$response = array();
	foreach($messages as $message) {
		if(isset($message->overridemessagegroupid) && isset($customtxt[$message->id])) {
			$title = $customtxt[$message->id];
		} else if(isset($messagedatacache["en"]) && isset($messagedatacache["en"][$message->messagekey])) {
			$title = $messagedatacache["en"][$message->messagekey];
		} else {
			$title = ""; // Could not find message for this message key.
		}
		for($i = 0;$i < $searchcount && (trim($searchwords[$i]) == "" || stripos($title,trim($searchwords[$i])) !== false);$i++);
		if($i == $searchcount) {
			$obj = null;
			$obj->title = escapehtml($title);
			$obj->categoryid = $message->targetedmessagecategoryid;
			$response[$message->id] = $obj;
		}
	}
	echo json_encode(empty($response)?false:$response);
	exit(0);
}


$sections = DBFindMany("Section", "from section s join userassociation ua on (ua.sectionid = s.id) where ua.userid = ?","s",array($USER->id));

$categories = QuickQueryMultiRow("select id, name, image from targetedmessagecategory where deleted = 0",true);
$categoriesjson = array();
foreach($categories as $category) {
	$obj = null;
	$obj->name = $category["name"];
	if(isset($category["image"]) && isset($classroomcategoryicons[$category["image"]]))
		$obj->img = "img/icons/" . $classroomcategoryicons[$category["image"]]  . ".gif";
	else
		$obj->img = "img/pixel.gif";
	$categoriesjson[$category["id"]] = $obj;
}

$categories = array(0 => "Positive",1 => "Corrective",2 => "Informational");

$library = array();

foreach($categoriesjson as $id => $obj) {
	$library[$id] = DBFindMany("TargetedMessage","from targetedmessage where enabled = 1 and targetedmessagecategoryid = ?",false,array($id));
}

////////////////////////////////////////////////////////////////////////////////
// Display Functions
////////////////////////////////////////////////////////////////////////////////


////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$PAGE = "notifications:classroom";
$TITLE = _L('Classroom Comments');

include_once("nav.inc.php");
?>
<link href='css/classroom.css' type='text/css' rel='stylesheet' media='screen'>
<?
startWindow(_L('Classroom Comments'));
echo button_bar(icon_button("Done Picking Comments", "tick", null, $redirect), '<div id="clock" class="clock"></div>');
?>

<label>Section: <select id="classselect" name="classselect">
<?

if($sections) {
	foreach($sections as $section)
		echo '<option value="'.$section->id.'">'.escapehtml($section->skey).'</option>';
} else {
	echo '<option value="">' . _L('-- No Section Available --') . '</option>';
}

?>
</select></label><br />

<table width="100%" id="picker" style="clear:both; margin-top: 3px;">
	<tr>
		<td style="top: 0px; vertical-align: top; padding-right: 10px;white-space: nowrap;">

			<a id="checkall" href="#" style="float:left;white-space: nowrap;">Select All</a><br />
			<div id="contactwrapper" >
				<div id="contactbox" style="width:100%;text-decoration:none;"></div>
			</div>
			<hr />
			<img src="img/icons/fugue/light_bulb.gif" alt="" />Press shift key to <br />&nbsp;&nbsp;&nbsp;&nbsp;multiselect
			<hr />
		</td>

		<td style="vertical-align:top;width:100%">
			<div id="theinstructions"><img src="img/icons/fugue/arrow_180.gif" alt="" style="vertical-align:middle;"/> Click on a Contact to Start</div>


			<div id='tabsContainer' style='margin-right:0px;display:none;vertical-align:middle;'></div>
			<div id="libraryContent" style="display:none;">
			<?
				$libraryids = array();
				$messageids = array();
				require_once($contentfile);
				foreach($library as $categoryid => $messages) {
					// add library to id since user may change the title of the category
					echo '<div id="lib-' . $categoryid . '" style="display:block;">
						  <span id="nowedit-' . $categoryid . '" class="nowedit"></span>
						  <div style="clear:both"></div>';
					foreach($messages as $message) {

						if(isset($message->overridemessagegroupid) && isset($customtxt[$message->id])) {
							$title = $customtxt[$message->id];
						} else if(isset($messagedatacache["en"]) && isset($messagedatacache["en"][$message->messagekey])) {
							$title = $messagedatacache["en"][$message->messagekey];
						} else {
							$title = ""; // Could not find message for this message key.
						}
						echo '<div id="msg-' . $message->id .'" class="classroomcomment" category="'.$categoryid.'">
								<img id="msgchk-' . $message->id .'" class="msgchk" src="img/checkbox-clear.png" alt=""/>
								<div id="msgtxt-' . $message->id .'" class="msgtxt" >'
								. escapehtml($title) .
								' </div>
								<img src="img/icons/fugue/marker.gif" alt="Mark" title="Mark this Comment" style="float:right;margin:2px" onclick="markcomment(\'msg-\',\'' . $message->id .'\')" />
								<div style="clear:both;">' .
									($USER->authorize('targetedcomment')?'<div id="msgprem' . $message->id .'" class="remarklink"></div><a href="#" class="remarklink">Remark</a>':'&nbsp;') .
								'</div>
								<span id="msgrem' . $message->id .'" class="remark" style="display:none;">
									<textarea class="remark"></textarea>
									<a class="remark" href="#" onclick="saveComment(\''. $message->id .'\');false;">Done</a>
								</span>
							  </div>';
					}
					echo '<div style="clear:both;"></div></div>';
				}
			?>

				<div id="lib-search">
					<span id="nowedit-search" style="float:left;color:graytext;font-weight:lighter;font-style:italic;"></span>
					<div id="searchContainer" style="clear:both;margin:10px;padding-top:10px;display:none;"><input id="searchbox" class="searchbox" type="text" value="" size="50" style="float:left"/><?= icon_button("Search", "magnifier", 'dosearch(); return false;', null) ?></div>
					<div id="searchResult" style="clear:both;"></div>
				</div>
			</div>
		</td>
	</tr>
</table>


<?
endWindow();


// TODO --- Once development is done script should move to its own file for caching purposes 
?>
<script type="text/javascript" src="script/accordion.js"></script>
<script type="text/javascript" src="script/classroom.js"></script>
<script type="text/javascript" language="javascript">
	var hascomments = <?= $USER->authorize('targetedcomment')?"true":"false" ?>;
	var categoryinfo = $H(<?= json_encode($categoriesjson) ?>);
	var requesturl = '<?= $requesturl ?>';
	var timetocutoff = new Date(<?= (strtotime($cutoff)) . '000' ?>).getTime() / 1000;
	document.observe("dom:loaded", function() {
		tabs.show_section('lib-<?=  $USER->getSetting("classroomtab", "search") ?>');
	});
</script>
<?
include_once("navbottom.inc.php");
?>