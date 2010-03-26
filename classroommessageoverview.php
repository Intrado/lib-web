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
									m.languagecode = 'en' and
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
$orderby = "order by date desc, p.$firstnamefield,p.$lastnamefield,tm.id";


if($mode == 'comments') {
	$orderby = "order by date desc,tm.id";
}

$query = "select SQL_CALC_FOUND_ROWS
Date(e.occurence) as date, p.id as personid,p.pkey, p.$firstnamefield as firstname,p.$lastnamefield as lastname,
tm.id as commentid ,tm.messagekey, tm.overridemessagegroupid ,tm.targetedmessagecategoryid, e.targetedmessageid, e.notes
from personassociation pa
inner join person p on (pa.personid = p.id)
inner join event e on (pa.eventid = e.id)
inner join targetedmessage tm on (e.targetedmessageid = tm.id)
where e.userid = ? $orderby limit $start, $limit";

$personcomments = QuickQueryMultiRow($query,true,false,array($USER->id));

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
<table width="100%" style="padding-top: 7px;">
<tr>
	<td class="feed" style="width: 180px;vertical-align: top;font-size: 12px;" >


		<?
		$schedule = DBFind("Schedule","from job j inner join schedule s on (j.scheduleid = s.id) where j.type = 'alert' and j.status = 'repeating'","s");
		if(classroomavailable($schedule)) {
			echo icon_button("Pick Comments", "add", null, "classroommessage.php");
		} else {
			classroomnextavailable($schedule);
		}

		?>


		<div style="clear:both;"></div>
		<h1 id="view">View By:</h1>
		<div id="alloptions" class="feedfilter">
			<a href="classroommessageoverview.php?mode=contacts" style="font-weight:<?= $mode=='contacts'?'bold':'normal' ?>"><img src="img/largeicons/tiny20x20/addresscard.jpg" />&nbsp;Contacts</a><br />
			<a href="classroommessageoverview.php?mode=comments" style="font-weight:<?= $mode=='comments'?'bold':'normal' ?>"><img src="img/largeicons/tiny20x20/clipboard.jpg" />&nbsp;Comments</a><br />
		</div>
		<br />
		<h1 id="view">View</h1>
		<div id="alloptions" class="feedfilter">
			<a id="collapseall" href="#" onclick="collapseall();return false;" style="font-weight:bold"><img src="img/icons/magifier_zoom_out.gif" />&nbsp;Compact</a><br />
			<a id="expandall" href="#" onclick="expandall();return false;" ><img src="img/icons/magnifier_zoom_in.gif" />&nbsp;Expanded</a><br />
		</div>

	</td>
	<td width="10px" style="border-left: 1px dotted gray;" >&nbsp;</td>
	<td class="feed" valign="top" >
		<? if($personcomments) { showPageMenu ($total,$start, $limit);} ?>
		<table id="feeditems">
			<?
			if($personcomments) {
				$customtxt = getoverridemessages($personcomments);
				require_once($messagedatapath);
				$currentdate = false;

				$first = current($personcomments);
				if($first['date'] != date("Y-m-d", time())) {
				?>
					<tr><td style="border-bottom:0px;vertical-align:top;text-align:center;width:30px;"><img src="img/largeicons/information.jpg" /></td><td style="border-bottom:0px;"><div class="feedtitle"><?=_L("No Classroom Comments for Today") ?></div>
				<?
				}


				if($mode == 'comments') {
					$commentid = false;
					foreach($personcomments as $personcomment) {
						$id = $personcomment['commentid'];
						if($id != $commentid) {
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
						echo '<tr><td style="white-space: nowrap;width:100%;">' . escapehtml($personcomment['firstname']) . '&nbsp;' .  escapehtml($personcomment['lastname']) . '<span style="color:graytext;font-style:italic;white-space:nowrap"> - ID: ' . $personcomment['pkey'] . '</span></td><td style="width:100%;padding-left:10%;">' .  ($personcomment['notes']?'<b>Remark: </b>' . escapehtml($personcomment['notes']):'') . '</td></tr>';
					}
					echo '</table></div></td>';
				} else {
					$contactid = false;
					foreach($personcomments as $personcomment) {
						$id = $personcomment['personid'];
						if($id != $contactid) {
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
				<tr><td valign="top" width="30px"><img src="img/largeicons/information.jpg" /></td><td><div class="feedtitle"><?=_L("No Classroom Comments") ?></div>
			<?
			}

			?>
		</table>
		<br />

		<? if($personcomments) { showPageMenu ($total,$start, $limit);} ?>

	</td>
</tr>
</table>
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