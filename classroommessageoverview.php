<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("inc/common.inc.php");
require_once("inc/securityhelper.inc.php");
require_once("obj/PeopleList.obj.php");
require_once("inc/table.inc.php");
require_once("inc/utils.inc.php");
require_once("inc/date.inc.php");
require_once("inc/form.inc.php");
require_once("inc/html.inc.php");
require_once("inc/formatters.inc.php");
require_once("obj/FieldMap.obj.php");
require_once("obj/TargetedMessageCategory.obj.php");
require_once("obj/Schedule.obj.php");
require_once("inc/classroom.inc.php");

// To add the filter form:
require_once("obj/Validator.obj.php");
require_once("obj/Phone.obj.php");
require_once("obj/Form.obj.php");
require_once("obj/FormItem.obj.php");

// To add the CSV report:
require_once('obj/Headers.obj.php');
require_once('obj/ReportGenerator.obj.php');
require_once('obj/ReportClassroomMessaging.obj.php');
require_once('obj/Formatters.obj.php');
require_once('inc/formatters.inc.php');
require_once('messagedata/en/targetedmessage.php');


////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////

if (!getSystemSetting('_hastargetedmessage', false) || !$USER->authorize('targetedmessage')) {
	redirect('unauthorized.php');
}

if(isset($_GET['mode'])) {
	$_SESSION['classroomoverview'] = $_GET['mode'];
	redirect();
}

if(!isset($_SESSION['classroomoverview'])) {
	$_SESSION['classroomoverview'] = 'contacts';
}

$start = 0 + (isset($_GET['pagestart']) ? $_GET['pagestart'] : 0);
$limit = 100;

$mode = $_SESSION['classroomoverview'];

$options = (isset($_SESSION['report'])) ? $_SESSION['report']['options'] : array();


////////////////////////////////////////////////////////////////////////////////
// CSV Report Handling
////////////////////////////////////////////////////////////////////////////////

if (isset($_GET['download'])) {
	$headers = new Headers();
	$headers->send_csv_headers('classroom_messaging_report.csv');
	$options['userid'] = $USER->id; // restrict the results to alerts/events linked with this user (teacher)
	$rcm = new ReportClassroomMessaging($options);
	$rcm->set_format('csv');
	$rcm->generate();
	exit;
}


////////////////////////////////////////////////////////////////////////////////
// Form Data
////////////////////////////////////////////////////////////////////////////////

$formdata = array();
$helpsteps = array();
$helpstepscount = 1;

$formdata["dateoptions"] = array(
	"label" => _L("Date Options"),
	"fieldhelp" => _L("Select the date or date range that the report should cover."),
	"value" => json_encode(array(
		"reldate" => isset($options['reldate']) ? $options['reldate'] : 'today',
		"xdays" => isset($options['lastxdays']) ? $options['lastxdays'] : '',
		"startdate" => isset($options['startdate']) ? $options['startdate'] : '',
		"enddate" => isset($options['enddate']) ? $options['enddate'] : ''
	)),
	"control" => array("ReldateOptions"),
	"validators" => array(array("ValReldate")),
	"helpstep" => $helpstepscount++
);
$helpsteps[] = _L('Select a date range to pull a classroom messaging report against.');

// Person ID search filter - reuse the previous form submissions' value if there was one
$searchvalue = (isset($options['personid'])) ? $options['personid'] : '';
/*
$formdata['searchmethod'] = array(
	'value' => 'personid',
	'control' => array('HiddenField')
);
*/
$formdata["personid"] = array(
	"label" => _L("Student ID"),
	"fieldhelp" => _L("Enter the student ID that you want to restrict the report to."),
	"value" => $searchvalue,
	"control" => array("TextField"),
	"validators" => array(),
	"helpstep" => $helpstepscount++
);
$helpsteps[] = _L('Optionally enter a student ID to limit the report to just that student.');


$buttons = array( submit_button(_L('Filter'), 'filter', 'arrow_refresh'));
$form = new Form('reportclassroomsearch', $formdata, $helpsteps, $buttons);

////////////////////////////////////////////////////////////////////////////////
// Form Data Handling
////////////////////////////////////////////////////////////////////////////////

//check and handle an ajax request (will exit early) or merge in related post data
$form->handleRequest();


$datachange = false;
$errors = false;
// check for form submission
if ($button = $form->getSubmit()) { // checks for submit and merges in post data
	$ajax = $form->isAjaxSubmit(); // whether or not this requires an ajax response

	if ($form->checkForDataChange()) {
		$datachange = true;
	} else if (($errors = $form->validate()) === false) { // checks all of the items in this form
		$postdata = $form->getData(); // gets assoc array of all values {name:value,...}
		if ($ajax) {
			if ($button == 'filter') {
				$dateOptions = json_decode($postdata['dateoptions'], true);
				if (! empty($dateOptions['reldate'])) {
					$_SESSION['report']['options']['reldate'] = $dateOptions['reldate'];

					if ($dateOptions['reldate'] == 'xdays' && isset($dateOptions['xdays'])) {
						$_SESSION['report']['options']['lastxdays'] = $dateOptions['xdays'] + 0;
					} else if ($dateOptions['reldate'] == 'daterange') {
						if (! empty($dateOptions['startdate']))
							$_SESSION['report']['options']['startdate'] = $dateOptions['startdate'];
						if (! empty($dateOptions['enddate']))
							$_SESSION['report']['options']['enddate'] = $dateOptions['enddate'];
					}
				}
				if (isset($postdata['personid'])) {
					$_SESSION['report']['options']['personid'] = $postdata['personid'];
				}

				$form->sendTo('classroommessageoverview.php');
			}
		} else {
			redirect('lists.php');
		}
	}
}

$datesql = $startdate = $enddate = '';
if (isset($options['reldate']) && $options['reldate'] != '') {
	list($startdate, $enddate) = getStartEndDate($options['reldate'], $options);
	$startdate = date('Y-m-d', $startdate);
	$enddate = date('Y-m-d', $enddate);
	$datesql = "AND (a.date >= '{$startdate}' and a.date < date_add('{$enddate}',interval 1 day) )";
}
else $datesql = 'AND a.date = CURDATE()';


////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

$messagedatapath = "messagedata/en/targetedmessage.php";

function getoverridemessages($messages) {
	$overrideids = array();
	if(!empty($messages)) {
		foreach($messages as $message) {
			if(isset($message["overridemessagegroupid"]))
				$overrideids[] = $message["overridemessagegroupid"];
		}
	}

	if(!empty($overrideids)) {
		 return QuickQueryList("select m.messagegroupid, p.txt from message m, messagepart p
									where m.messagegroupid in (" . implode(",",$overrideids) . ") and
									m.languagecode = 'en' and m.type='email' and
									p.messageid = m.id and p.sequence = 0",true);
	}
	return null;
}
function getmessagetext($message,$customtxt) {
	global $messagedatacache;
	if(isset($message["overridemessagegroupid"]) && isset($customtxt[$message["overridemessagegroupid"]])) {
		return $customtxt[$message["overridemessagegroupid"]];
	} else if(isset($messagedatacache["en"]) && isset($messagedatacache["en"][$message["messagekey"]])) {
		return $messagedatacache["en"][$message["messagekey"]];
	}
	return "";
}

$categories = DBFindMany("TargetedMessageCategory", "from targetedmessagecategory where deleted = 0");
$personcomments = false;
$commentpersons = false;


$firstnamefield = FieldMap::getFirstNameField();
$lastnamefield = FieldMap::getLastNameField();
$orderby = "order by date desc, p.{$firstnamefield},p.{$lastnamefield},tm.id";

if (isset($options['personid']) && strlen($options['personid'])) {
	$personsql = "AND p.pkey = '" . DBSafe($options['personid']) . "'";
}
else $personsql = '';

if($mode == 'comments') {
	$orderby = "order by date desc,tm.id";
}

$query = "
select SQL_CALC_FOUND_ROWS
	Date(e.occurence) as date,
	p.id as personid,
	p.pkey,
	p.{$firstnamefield} as firstname,
	p.{$lastnamefield} as lastname,
	tm.id as commentid,
	tm.messagekey,
	tm.overridemessagegroupid,
	tm.targetedmessagecategoryid,
	e.targetedmessageid,
	e.notes
from
	alert a
	inner join event e on (a.eventid = e.id)
	inner join person p on (a.personid = p.id)
	inner join targetedmessage tm on (e.targetedmessageid = tm.id)
where
	e.userid = ?
	and not p.deleted
	{$datesql}
	{$personsql}
{$orderby}
limit
	{$start}, {$limit}";

$personcomments = QuickQueryMultiRow($query, true, false, array($USER->id));

$total = QuickQuery("select FOUND_ROWS()");



////////////////////////////////////////////////////////////////////////////////
// Display Functions
////////////////////////////////////////////////////////////////////////////////

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////


$PAGE = "notifications:classroom";
$TITLE = _L('Classroom Messaging');

include_once("nav.inc.php");

startWindow(_L('My Classroom Messages'));

?>

	<div class="csec window_aside">

		<?
		$schedule = DBFind("Schedule","from job j inner join schedule s on (j.scheduleid = s.id) where j.type = 'alert' and j.status = 'repeating'","s");
		if(classroomavailable($schedule)) {
			echo icon_button("Pick Comments", "add", null, "classroommessage.php");
		} else {
			classroomnextavailable($schedule);
		}

		?>

		<div style="clear:both;"></div>
		<h3 id="view">View By:</h3>
		<ul id="alloptions" class="feedfilter">
			<li><a href="classroommessageoverview.php?mode=contacts" style="font-weight:<?= $mode=='contacts'?'bold':'normal' ?>"><img src="img/largeicons/tiny20x20/addresscard.jpg" />&nbsp;Contacts</a></li>
			<li><a href="classroommessageoverview.php?mode=comments" style="font-weight:<?= $mode=='comments'?'bold':'normal' ?>"><img src="img/largeicons/tiny20x20/clipboard.jpg" />&nbsp;Comments</a></li>
		</ul>

		<h3 id="view">View</h3>
		<ul id="alloptions" class="feedfilter">
			<li><a id="collapseall" href="#" onclick="collapseall();return false;" style="font-weight:bold"><img src="img/icons/magifier_zoom_out.gif" />&nbsp;Compact</a></li>
			<li><a id="expandall" href="#" onclick="expandall();return false;" ><img src="img/icons/magnifier_zoom_in.gif" />&nbsp;Expanded</a></li>
		</ul>

	</div><!-- .csec .window_aside -->
	

	<div class="csec window_main">
		<?
			if ($personcomments) {
				showPageMenu ($total, $start, $limit);
				echo '<a href="?download" target="_blank" class="" style="float:right; margin:10px 0;"><img src="img/icons/document_excel_csv.png" style="margin-right:5px;">Open full detail report in Excel</a>';
			} 
			echo $form->render();
		?>
		<table id="feeditems">
			<?
			if ($personcomments) {
				$customtxt = getoverridemessages($personcomments);
				require_once($messagedatapath);
				$currentdate = false;

				if($mode == 'comments') {
					$commentid = false;
					foreach($personcomments as $personcomment) {
						$id = $personcomment['commentid'];
						if($id != $commentid || $currentdate != $personcomment['date']) {
							if($commentid !== false) {
								echo '</table></div></td>';
							}
							if($currentdate != $personcomment['date']) {
								echo '<tr><td colspan="2" style="border-bottom:0px;font-weight:3em;"><h3>' . _L('Comments on %s',$personcomment['date']) . '</h3></td></tr>';
								$currentdate = $personcomment['date'];
							}

							if(isset($categories[$personcomment["targetedmessagecategoryid"]]) && isset($classroomcategoryicons[$categories[$personcomment["targetedmessagecategoryid"]]->image]))
								$icon = $classroomcategoryicons[$categories[$personcomment["targetedmessagecategoryid"]]->image];
							else
								$icon = false;
							?>
							<tr><td style="border-bottom:0px;vertical-align:top;text-align:center;width:30px;"><?= $icon?'<img src="img/icons/' . $icon . '.gif" />':'' ?></td><td style="border-bottom:0px;"><div class="feedtitle">
										<a href="#" onclick="togglepersons('<?=$personcomment['date'] . $id ?>');return false;">
								<?= escapehtml(getmessagetext($personcomment,$customtxt))?>
								</a></div>
							<?
							echo '<div id="persons-' . $personcomment['date'] . $id . '" class="expandview" style="display:none;"><table style="margin-left:2%;width:98%">';
							$commentid = $id;
						}
						echo '<tr><td style="white-space: nowrap;">' . escapehtml($personcomment['firstname']) . '&nbsp;' .  escapehtml($personcomment['lastname']) . '<span style="color:graytext;font-style:italic;white-space:nowrap"> - ID: ' . $personcomment['pkey'] . '</span></td><td style="width:100%;padding-left:10%;">' .  ($personcomment['notes']?'<b>Remark: </b>' . escapehtml($personcomment['notes']):'') . '</td></tr>';
					}
					echo '</table></div></td>';
				} else {
					$contactid = false;
					foreach($personcomments as $personcomment) {
						$id = $personcomment['personid'];
						if($id != $contactid || $currentdate != $personcomment['date']) {
							if($contactid !== false) {
								echo '</table></div></td>';
							}
							if($currentdate != $personcomment['date']) {
								echo '<tr><td colspan="2" style="border-bottom:0px;font-weight:3em;"><h3>' . _L('Contacts on %s', $personcomment['date'] ) . '</h3></td></tr>';
								$currentdate = $personcomment['date'];
							}
							?>
							<tr><td style="border-bottom:0px;vertical-align:top;text-align:center;width:30px;"><img id="img-<?= $personcomment['date'] . $id  ?>" src="img/arrow_right.gif" style="padding-top:5px;"/></td><td style="border-bottom:0px;"><div class="feedtitle">
										<a href="#" onclick="togglecomments('<?=$personcomment['date'] . $id ?>');return false;">
								<?= escapehtml($personcomment['firstname']) . '&nbsp;' .  escapehtml($personcomment['lastname'])  ?>
								</a><span style="color:graytext;font-style:italic;white-space:nowrap"> - ID: <?= $personcomment['pkey'] ?></span></div>
							<?
							echo '<div id="comments-' . $personcomment['date'] . $id  . '" class="expandview" style="display:none;"><table style="margin-left:2%;width:98%">';
							$contactid = $id;
						}
						//$icon = $classroomcategoryicons[$categories[$personcomment["targetedmessagecategoryid"]]->image];
						if(isset($categories[$personcomment["targetedmessagecategoryid"]]) && isset($classroomcategoryicons[$categories[$personcomment["targetedmessagecategoryid"]]->image]))
							$icon = $classroomcategoryicons[$categories[$personcomment["targetedmessagecategoryid"]]->image];
						else
							$icon = false;
						echo '<tr><td width="30px">' . ($icon?'<img src="img/icons/' . $icon . '.gif" />':'') . '</td><td style="white-space: nowrap;">' . escapehtml(getmessagetext($personcomment,$customtxt)) . '</td><td style="width:100%;padding-left:10%;">' .  ($personcomment['notes']?'<b>Remark: </b>' . escapehtml($personcomment['notes']):'') . '</td></tr>';
					}
					echo '</table></div></td>';
				}
			} else {
			?>
				<tr><td valign="top" width="30px"><img src="img/largeicons/information.jpg" /></td><td class="no_content"><div class="feedtitle"><?=_L("No Classroom Comments") ?></div>
			<?
			}

			?>
		</table>
		
		</div><!-- .csec .window_main -->
		<br />

		<? if($personcomments) { showPageMenu ($total,$start, $limit);} ?>



<script type="text/javascript" language="javascript">

function togglecomments(id) {
	var table = $('comments-' + id);
	if(table.visible()) {
		$('img-' + id).src = "img/arrow_right.gif";
		table.hide();
		//Effect.BlindUp(table,{ duration: 0.5 });
	} else {
		$('img-' + id).src = "img/arrow_down.gif";
		table.show();
		//Effect.BlindDown(table,{ duration: 0.5 });
	}
}
function togglepersons(id) {
	var table = $('persons-' + id);
	if(table.visible()) {
		table.hide();
		//Effect.BlindUp(table,{ duration: 0.5 });
	} else {
		table.show();
		//Effect.BlindDown(table,{ duration: 0.5 });
	}
}

function expandall() {
	$('expandall').setStyle('font-weight:bold;');
	$('collapseall').setStyle('font-weight:normal;');

	$$('.expandview').each(function(item) {
		item.show();
		if(item.id.substring(0,9) == 'comments-') {
			$('img-' + item.id.substring(9)).src = "img/arrow_down.gif";

		}
	});

}
function collapseall() {
	$('collapseall').setStyle('font-weight:bold;');
	$('expandall').setStyle('font-weight:normal;');
	$$('.expandview').each(function(item) {
		item.hide();
		if(item.id.substring(0,9) == 'comments-') {
			$('img-' + item.id.substring(9)).src = "img/arrow_right.gif";
		}
	});
}

</script>


<?

endWindow();


include_once("navbottom.inc.php");

?>
