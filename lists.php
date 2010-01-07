<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
include_once("inc/common.inc.php");
include_once("inc/securityhelper.inc.php");
include_once("obj/PeopleList.obj.php");
require_once("inc/table.inc.php");
require_once("inc/utils.inc.php");
include_once("inc/form.inc.php");
include_once("inc/html.inc.php");
include_once("inc/formatters.inc.php");
require_once("obj/FieldMap.obj.php");
require_once("obj/PeopleList.obj.php");
require_once("obj/RenderedList.obj.php");

////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('createlist')) {
	redirect('unauthorized.php');
}

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

$_SESSION['previewfrom'] = 'lists.php';

if (isset($_GET['delete'])) {
	$deleteid = $_GET['delete'] + 0;
	if (isset($_SESSION['listid']) && $_SESSION['listid'] == $deleteid)
		$_SESSION['listid'] = NULL;
	if (userOwns("list",$deleteid)) {
		$list = new PeopleList($deleteid);
		//QuickUpdate("delete from listentry where listid='$deleteid'");
		QuickUpdate("update list set deleted=1 where id=?", false, array($list->id));
		notice(_L("The list, %s, is now deleted.", escapehtml($list->name)));
	} else {
		notice(_L("You do not have permission to delete this list."));
	}
	redirect();
}

$filter = "";
if (isset($_GET['filter'])) {
	$filter = $_GET['filter'];
}

$isajax = isset($_GET['ajax']);

$mergeditems = array();
if($isajax === true) {

	session_write_close();//WARNING: we don't keep a lock on the session file, any changes to session data are ignored past this point
	switch ($filter) {
		case "date":
			$mergeditems = QuickQueryMultiRow("select 'list' as type,'Saved' as status, id, name, modifydate as date, lastused from list where userid=? and deleted = 0  and modifydate is not null order by modifydate desc",true,false,array($USER->id));
			break;
		case "name":
			$mergeditems = QuickQueryMultiRow("select 'list' as type,'Saved' as status, id, name, modifydate as date, lastused, (name +0) as digitsfirst from list where userid=? and deleted = 0  and modifydate is not null order by digitsfirst",true,false,array($USER->id));
			break;
		default:
			$mergeditems = QuickQueryMultiRow("select 'list' as type,'Saved' as status, id, name, modifydate as date, lastused from list where userid=? and deleted = 0  and modifydate is not null order by modifydate desc",true,false,array($USER->id));
		break;
	}

	header('Content-Type: application/json');
	$data = activityfeed($mergeditems,true);
	echo json_encode(!empty($data) ? $data : false);
	exit();
}


////////////////////////////////////////////////////////////////////////////////
// Display Functions
////////////////////////////////////////////////////////////////////////////////



function activityfeed($mergeditems,$ajax = false) {
	$actioncount = 0;
	$activityfeed = $ajax===true?array():"";
	$limit = 10;
	$duplicatejob = array();

	if($ajax===true) {
		if(empty($mergeditems)) {
				$activityfeed[] = array("itemid" => "",
											"defaultlink" => "",
											"defaultonclick" => "",
											"icon" => "largeicons/information.jpg",
											"title" => _L("No Lists."),
											"content" => "",
											"tools" => "");
		} else {
			while(!empty($mergeditems) && $limit > 0) {
				$item = array_shift($mergeditems);
				$time = date("M j, g:i a",strtotime($item["date"]));
				$title = $item["status"];
				$content = "";
				$tools = "";
				$itemid = $item["id"];
				$icon = "";
				$defaultlink = "";
				$defaultonclick = "";
				if($item["type"] == "list" ) {
					$title = escapehtml($item["name"]);
					$defaultlink = "list.php?id=$itemid";
					$content = '<a href="' . $defaultlink . '">' . $time;

					$content .= '&nbsp;-&nbsp;';
					if(isset($item["lastused"]))
						$content .= 'This list was last used: <i>' . date("M j, g:i a",strtotime($item["lastused"])) . "</i>";
					else
						$content .= 'This list has never been used ';
					$content .= " and has " . listcontacts($itemid,"list") . '</a>';
					$tools = action_links (
						action_link("Edit", "pencil", "list.php?id=$itemid"),
						action_link("Preview", "application_view_list", "showlist.php?id=$itemid"),
						action_link("Delete", "cross", "lists.php?delete=$itemid", "return confirmDelete();")
						);
					//$tools = str_replace("&nbsp;|&nbsp;","<br />",$tools);
					$icon = 'largeicons/addrbook.jpg';
				}

				$activityfeed[] = array("itemid" => $itemid,
											"defaultlink" => $defaultlink,
											"defaultonclick" => $defaultonclick,
											"icon" => $icon,
											"title" => $title,
											"content" => $content,
											"tools" => $tools);

				$limit--;
			}
		}
	} else {
		$activityfeed .= '<tr>
									<td valign="top" width="60px"><img src="img/ajax-loader.gif" /></td>
									<td >
											<div class="feedtitle">
												<a href="">
												' . _L("Loading Lists") . '</a>
											</div>
									</td>
									</tr>';
		$activityfeed .= "
				<script>
				var actionids = $actioncount;

				var jobfiltes = Array('none','date','name');

				function addfeedtools() {
					for(var id=0;id<actionids;id++){
						$('actionlink_' + id).tip = new Tip('actionlink_' + id, $('actions_' + id).innerHTML, {
							style: 'protogrey',
							radius: 4,
							border: 4,
							hideOn: false,
							hideAfter: 0.5,
							stem: 'rightTop',
							hook: {  target: 'leftMiddle', tip: 'topRight'  },
							width: 'auto',
							offset: { x: 0, y: 0 }
						});
					}
				}
				function removefeedtools() {
					for(var id=0;id<actionids;id++){
						Tips.remove('actionlink_' + id);
					}
				}
				function applyfilter(filter) {
						new Ajax.Request('lists.php?ajax=true&filter=' + filter, {
							method:'get',
							onSuccess: function (response) {
								var result = response.responseJSON;
								if(result) {
									var html = '';
									var size = result.length;

									//removefeedtools();
									actionids = 0;
									for(i=0;i<size;i++){
										var item = result[i];
										html += '<tr><td valign=\"top\" width=\"60px\"><a href=\"' + item.defaultlink + '\" ' + item.defaultonclick + '><img src=\"img/' + item.icon + '\" /></a></td><td ><div class=\"feedtitle\"><a href=\"' + item.defaultlink + '\" ' + item.defaultonclick + '>' + item.title + '</a></div><span>' + item.content + '</span></td>';
										if(item.tools) {
											html += '<td valign=\"middle\" width=\"100px\"><div>' + item.tools + '</div></td>';
											actionids++;
										}
										html += '</tr>';
									}
									$('feeditems').update(html);
									//addfeedtools();

									var filtercolor = $('filterby').getStyle('color');
									if(!filtercolor)
										filtercolor = '#000';

									size = jobfiltes.length;
									for(i=0;i<size;i++){
										$(jobfiltes[i] + 'filter').setStyle({color: filtercolor, fontWeight: 'normal'});
									}
									$(filter + 'filter').setStyle({
	 									 color: '#000000',
										 fontWeight: 'bold'
									});

								}

							}
						});
				}
				document.observe('dom:loaded', function() {
					applyfilter('none');
				});
				</script>";

	}
	return $activityfeed;
}

function listcontacts ($obj,$name) {
	$lists = array();
	if($name == "job") {
		if(in_array($obj->status,array("active","cancelling"))) {
			$result = QuickQueryRow("select
				sum(rc.type='phone' and rc.result not in ('duplicate', 'blocked')) as total_phone,
            	sum(rc.type='email' and rc.result not in ('duplicate', 'blocked')) as total_email,
            	sum(rc.type='sms' and rc.result not in ('duplicate', 'blocked')) as total_sms,
            	j.type LIKE '%phone%' AS has_phone,
				j.type LIKE '%email%' AS has_email,
				j.type LIKE '%sms%' AS has_sms,
            	sum(rc.result not in ('A', 'M', 'duplicate', 'nocontacts', 'blocked') and rc.type='phone' and rc.numattempts < js.value) as remaining_phone,
            	sum(rc.result not in ('sent', 'duplicate', 'nocontacts') and rc.type='email' and rc.numattempts < 1) as remaining_email,
            	sum(rc.result not in ('sent', 'duplicate', 'nocontacts', 'blocked') and rc.type='sms' and rc.numattempts < 1) as remaining_sms,
            	j.percentprocessed as percentprocessed
				from job j
           		left join reportcontact rc on j.id = rc.jobid
      			left join jobsetting js on (js.jobid = j.id and js.name = 'maxcallattempts')
            	where j.id=? group by j.id",true,false,array($obj->id));
			$content = "";
			if($result["has_phone"] && $result["total_phone"] != 0)
				$content .= $result["total_phone"] . " Phone" . ($result["total_phone"]!=1?"s":"") . " (" .  sprintf("%0.2f",(100*$result["remaining_phone"]/$result["total_phone"])) . "%	Remaining), ";
			if($result["has_email"] && $result["total_email"] != 0)
				$content .= $result["total_email"] . " Email" . ($result["total_email"]!=1?"s":"") . " (" .  sprintf("%0.2f",(100*$result["remaining_email"]/$result["total_email"])) . "%	Remaining), ";
			if($result["has_sms"]  && $result["total_sms"] != 0)
				$content .= $result["total_sms"] . " SMS (" .  sprintf("%0.2f",(100*$result["remaining_sms"]/$result["total_sms"])) . "% Remaining)";
			return trim($content,", ");
		} else if(in_array($obj->status,array("cancelled","complete"))) {
			$content = "";
			$result = Query("select rp.type,
							sum(rp.numcontacts and rp.status != 'duplicate') as total,
							100 * sum(rp.numcontacts and rp.status='success') / (sum(rp.numcontacts and rp.status != 'duplicate') +0.00) as success_rate
							from reportperson rp where rp.jobid = ?	group by rp.jobid, rp.type",false,array($obj->id));
			while ($row = DBGetRow($result)) {
				if($row[0] == "phone")
					$content .= $row[1] . " Phone" . ($row[1]!=1?"s":"") . " (" . sprintf("%0.2f",(isset($row[2]) ? $row[2] : "")) . "% Contacted), ";
				else if($row[0] == "email")
					$content .= $row[1] . " Email" . ($row[1]!=1?"s":"") . " (" . sprintf("%0.2f",(isset($row[2]) ? $row[2] : "")) . "% Contacted), ";
				else if($row[0] == "sms")
					$content .= $row[1] . " SMS (" . sprintf("%0.2f",(isset($row[2]) ? $row[2] : "")) . "% Contacted)";

			}
			return trim($content,", ");
		} else {
			$lists = QuickQueryList("select listid from joblist where jobid = ?",false,false,array($obj->id));

		}
	} else if($name == "list") {
		$lists[] = $obj;
	}
	$calctotal = 0;
	foreach ($lists as $id) {
		$list = new PeopleList($id);
		$renderedlist = new RenderedList($list);
		$renderedlist->calcStats();
		$calctotal = $calctotal + $renderedlist->total;
	}
	return "<b>" . $calctotal . ($calctotal!=1?"</b>&nbsp;contacts":"</b>&nbsp;contact");
}


////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////


$PAGE = "notifications:lists";
$TITLE = "List Builder";

include_once("nav.inc.php");

$data = DBFindMany("PeopleList",", (name +0) as foo from list where userid=$USER->id and deleted=0 order by foo,name");
$titles = array(	"name" => "#List Name",
					"description" => "#Description",
					"lastused" => "Last Used",
					"Actions" => "Actions"
					);

startWindow('My Lists&nbsp;' . help('Lists_MyLists'));

$activityfeed = '
				<table width="100%" name="recentactivity" style="padding-top: 7px;">
				<tr>
					<td class="feed" style="width: 180px;vertical-align: top;font-size: 12px;" >
						<div style="">
						' .
						icon_button(_L('Create New List'),"add","list.php?id=new",null,'style="display:inline;"')
						 .' <div style="clear:both;"></div>
						</div>
						<br />
						<h1 id="filterby">Sort By:</h1>
						<div id="allfilters" class="feedfilter">
							<a id="listsfilter" href="lists.php?filter=date" onclick="applyfilter(\'date\'); return false;"><img src="img/largeicons/tiny20x20/clock.jpg">Modify Date</a><br />
							<a id="listsfilter" href="lists.php?filter=name" onclick="applyfilter(\'name\'); return false;"><img src="img/largeicons/tiny20x20/pencil.jpg">Name</a><br />
						</div>
					</td>
					<td width="10px" style="border-left: 1px dotted gray;" >&nbsp;</td>
					<td class="feed" valign="top" >
						<table id="feeditems">
				';

				$activityfeed .= activityfeed($mergeditems,false);
				$activityfeed .= '</table>
					</td>
				</tr>
			</table>';
			echo $activityfeed;




endWindow();


include_once("navbottom.inc.php");
