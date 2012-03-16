<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
include_once("inc/common.inc.php");
include_once("inc/securityhelper.inc.php");
include_once("obj/PeopleList.obj.php");
require_once("inc/table.inc.php");
require_once("inc/utils.inc.php");
require_once("inc/date.inc.php");
include_once("inc/form.inc.php");
include_once("inc/html.inc.php");
include_once("inc/formatters.inc.php");
require_once("obj/FieldMap.obj.php");
require_once("obj/RenderedList.obj.php");
require_once("obj/Person.obj.php");
require_once("obj/Publish.obj.php");

////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////

if (!$USER->authorize('createlist') && !($USER->authorize("subscribe") && userCanSubscribe('list'))) {
	redirect('unauthorized.php');
}

////////////////////////////////////////////////////////////////////////////////
// Action/Request Processing
////////////////////////////////////////////////////////////////////////////////

// requests to remove a publishid
if (isset($_GET['id']) && isset($_GET['remove'])) {
	// check that this is a vaid publish id
	$publish = DBFind("Publish", "from publish where id = ? and action = 'subscribe' and userid = ?", false, array($_GET['id'], $USER->id));
	if ($publish) {
		$publish->destroy();
		notice(_L("The subscription was removed."));
	}
	redirect();
}

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

$_SESSION['previewfrom'] = 'lists.php';

if (isset($_GET['delete'])) {
	$deleteid = $_GET['delete'] + 0;
	if (isset($_SESSION['listid']) && $_SESSION['listid'] == $deleteid)
		$_SESSION['listid'] = NULL;

	$list = new PeopleList($deleteid);
	if (userOwns("list",$deleteid) && $list->type != 'alert') {
		$isPublished = isPublished('list', $list->id);
		if ($list->softDelete()) {
			notice(_L("The list, %s, is now deleted.", escapehtml($list->name)));
			
			if ($isPublished)
				notice(_L("The list, %s, is now un-published. Any subscriptions were also removed.", escapehtml($list->name)));
		}	
	} else {
		notice(_L("You do not have permission to delete this list."));
	}
	redirect();
}


if (isset($_GET['ajax'])) {
	session_write_close();//WARNING: we don't keep a lock on the session file, any changes to session data are ignored past this point
	$start = 0 + (isset($_GET['pagestart']) ? $_GET['pagestart'] : 0);
	$limit = 100;
	$orderby = "modifydate desc";
	$filter = "";
	if (isset($_GET['filter'])) {
		$filter = $_GET['filter'];
	}
	switch ($filter) {
		case "name":
			$orderby = "digitsfirst, name";
			break;
	}
	// get all lists owned by this user or lists this user has publish records for (both publications and subscriptions)
	$mergeditems = QuickQueryMultiRow("
		select SQL_CALC_FOUND_ROWS 
			'list' as type, 'Saved' as status, l.id as id, l.name as name, l.description, (l.name +0) as digitsfirst, l.modifydate as date, l.lastused as lastused,
			p.action as publishaction, p.id as publishid, u.login as owner
		from list l
			inner join user u on
				(l.userid = u.id)
			left join publish p on
				(p.listid = l.id and p.userid = ?)
		where l.type in ('person','section')
			and (l.userid = ? or p.userid = ?)
			and not l.deleted
		group by id
		order by $orderby
		limit $start, $limit",
		true,false,array($USER->id, $USER->id, $USER->id));

	$total = QuickQuery("select FOUND_ROWS()");
	$numpages = ceil($total/$limit);
	$curpage = ceil($start/$limit) + 1;
	$displayend = ($start + $limit) > $total ? $total : ($start + $limit);
	$displaystart = ($total) ? $start +1 : 0;

	if(empty($mergeditems)) {
			$data->list[] = array("itemid" => "",
										"defaultlink" => "",
										"icon" => "largeicons/information.jpg",
										"title" => _L("No Lists."),
										"content" => "",
										"tools" => "");
	} else {
		// get user associated orgs
		$authorizedorgs = Organization::getAuthorizedOrgKeys();

		while(!empty($mergeditems) && $limit > 0) {
			$item = array_shift($mergeditems);
			$time = date("M j, Y g:i a",strtotime($item["date"]));
			$title = $item["status"];
			$itemid = $item["id"];
			$title = escapehtml($item["name"]);
			$defaultlink = "list.php?id=$itemid";
			$publishaction = $item['publishaction'];
			$publishid = $item['publishid'];

			// give the user some text
			$publishmessage = '';
			if ($publishaction == 'publish')
				$publishmessage = _L('Changes to this list are published.');
			
			// tell the user it's a subscription. change the href to view instead of edit
			if ($publishaction == 'subscribe' || !$USER->authorize('createlist')) {
				$publishmessage = _L('You are subscribed to this list. Owner: (%s)', $item['owner']);
				$defaultlink = "showlist.php?id=$itemid";
			}
			
			// Users with published or subscribed lists will get a special action item
			$publishactionlink = "";
					switch ($publishaction) {
				case 'publish':
					// if the user has published this message groups and they are authorized for atleast one org (or the customer has no orgs)
					if ($USER->authorize("publish") && userCanPublish('list') && ($authorizedorgs || !Organization::custHasOrgs()))
						$publishactionlink = action_link(_L("Modify Publication"), "fugue/star__pencil", "publisheditorwiz.php?id=$itemid&type=list");
					break;
				case 'subscribe':
					// this list is subscribed to, allow unsubscribe always!
					$publishactionlink = action_link("Un-Subscribe", "fugue/star__minus", "lists.php?id=$publishid&remove");
					break;
				default:
					// if the user can publish lists and they are authorized for atleast one org (or the customer has no orgs)
					if ($USER->authorize("publish") && userCanPublish('list') && ($authorizedorgs || !Organization::custHasOrgs()))
						$publishactionlink = action_link(_L("Publish"), "fugue/star__plus", "publisheditorwiz.php?id=$itemid&type=list");
			}
			
			// if the user owns this list, they can edit, delete
			if (userOwns("list", $itemid)) {
				$tools = action_links (
					action_link("Edit", "pencil", "list.php?id=$itemid"),
					action_link("Preview", "application_view_list", "showlist.php?id=$itemid"),
					$publishactionlink,
					action_link("Delete", "cross", "lists.php?delete=$itemid", "return confirmDelete();")
				);
			} else {
				$tools = action_links (
					action_link("Preview", "application_view_list", "showlist.php?id=$itemid"),
					$publishactionlink);
			}

			$content = '<a href="' . $defaultlink . '">' . ($item["date"]!== null?$time:"");

			if ($item["description"] != "") {
				$content .= '&nbsp;-&nbsp;' . escapehtml($item["description"]);
			}
			$content .= '<br />';
			if(isset($item["lastused"]))
				$content .= 'This list was last used: <em>' . date("M j, Y g:i a",strtotime($item["lastused"])) . "</em>";
			else
				$content .= 'This list has never been used';
			$content .= '</a>';
			
			$icon = 'largeicons/addrbook.jpg';

			$data->list[] = array("itemid" => $itemid,
										"defaultlink" => $defaultlink,
										"icon" => $icon,
										"title" => $title,
										"content" => $content,
										"tools" => $tools,
										"publishmessage" => $publishmessage);
		}
	}
	$data->pageinfo = array($numpages,$limit,$curpage, "Showing $displaystart - $displayend of $total records on $numpages pages ");

	header('Content-Type: application/json');
	echo json_encode(!empty($data) ? $data : false);
	exit();
}


////////////////////////////////////////////////////////////////////////////////
// Display Functions
////////////////////////////////////////////////////////////////////////////////


////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////


$PAGE = "notifications:lists";
$TITLE = "List Builder";

include_once("nav.inc.php");

startWindow('My Lists&nbsp;' . help('Lists_MyLists'));

?>
	
	<? if ($USER->authorize('createlist')) { ?>
		<div class="feed_btn_wrap cf">
		<?= icon_button(_L('Create a List'),"add","location.href='editlistrules.php?id=new'") ?>
		<? if (getSystemSetting('_hasenrollment')) {
					echo icon_button(_L('Create a List by Section'),"add","location.href='editlistsections.php?id=new'");
				} ?>
				
				<?=(($USER->authorize('subscribe') && userCanSubscribe('list'))?icon_button(_L('Subscribe to a List'),"fugue/star", "document.location='listsubscribe.php'"):'') ?>
		</div>
	<? } ?>

	
		<div class="csec window_aside">
			
			<h3 id="filterby">Sort By:</h3>
			<ul id="allfilters" class="feedfilter">
				<li><a id="namefilter" href="#" onclick="applyfilter('name'); return false;"><img src="img/largeicons/tiny20x20/pencil.jpg" />Name</a></li>
				<li><a id="datefilter" href="#" onclick="applyfilter('date'); return false;"><img src="img/largeicons/tiny20x20/clock.jpg" />Modify Date</a></li>
			</ul>
			
		</div><!-- .csec .window_aside -->

		
		<div class="csec window_main">
		
		<div id="pagewrappertop" class="content_recordcount_top"></div>

		<div id="feeditems" class="content_feed">
			<table><tbody>
				<tr>
					<td class=""><img src='img/ajax-loader.gif' /></td>
					<td>
					<div class='feedtitle'>
					<a href=''><?= _L("Loading Lists") ?></a>
					</div>
					</td>
				</tr>
			</tbody></table>
		</div>
		<div id="pagewrapperbottom" class="content_recordcount_btm"></div>
		
		</div><!-- .csec .window_main -->



<script type="text/javascript" >
var filtes = Array('date','name');
var activepage = 0;
var currentfilter = 'date';

function page(event) {
	activepage = event.element().value;
	applyfilter(currentfilter);
}

function applyfilter(filter) {
		new Ajax.Request('lists.php', {
			method:'get',
			parameters:{ajax:true,filter:filter,pagestart:activepage},
			onSuccess: function (response) {
				var result = response.responseJSON;
				if(result) {
					var html = '';
					var size = result.list.length;
					
					for(i=0;i<size;i++){
						var item = result.list[i];
						html += '<div class=\"feed_item cf\"><a class=\"msg_icon\" href=\"' + item.defaultlink + '\"><img src=\"img/' + item.icon + '\" /></a><div class="feed_wrap"><a class=\"feed_title\" href=\"' + item.defaultlink + '\">' + item.title + '</a>';
						if(item.publishmessage) {
							html += '<div class=\"feedsubtitle cf\"><a href=\"' + item.defaultlink + '\"><img src=\"img/icons/diagona/10/031.gif\" />' + item.publishmessage + '</div>';
						}
						html += '<div class=\"feed_detail\">' + item.content + '</div></div>';
						if(item.tools) {
							html += item.tools;
						}
						html += '</div>';
					}
					$('feeditems').update(html);
					var pagetop = new Element('div',{'class': 'content_recordcount'}).update(result.pageinfo[3]);
					var pagebottom = new Element('div',{'class': 'content_recordcount'}).update(result.pageinfo[3]);

					var selecttop = new Element('select', {'id':'selecttop'});
					var selectbottom = new Element('select', {'id':'selectbottom'});
					for (var x = 0; x < result.pageinfo[0]; x++) {
						var offset = x * result.pageinfo[1];
						var selected = (result.pageinfo[2] == x+1);
						selecttop.insert(new Element('option', {'value': offset,selected:selected}).update('Page ' + (x+1)));
						selectbottom.insert(new Element('option', {'value': offset,selected:selected}).update('Page ' + (x+1)));
					}
					pagetop.insert(selecttop);
					pagebottom.insert(selectbottom);
					$('pagewrappertop').update(pagetop);
					$('pagewrapperbottom').update(pagebottom);

					currentfilter = filter
					$('selecttop').observe('change',page);
					$('selectbottom').observe('change',page);

					var filtercolor = $('filterby').getStyle('color');
					if(!filtercolor)
						filtercolor = '#000';

					size = filtes.length;
					for(i=0;i<size;i++){
						$(filtes[i] + 'filter').setStyle({color: filtercolor, fontWeight: 'normal'});
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
	applyfilter('name');
});
</script>
<?

endWindow();


include_once("navbottom.inc.php");
