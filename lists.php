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
require_once("inc/feed.inc.php");

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
										"icon" => "img/largeicons/information.jpg",
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
			
			$icon = 'img/largeicons/addrbook.jpg';

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

$feedButtons = array();
if ($USER->authorize('createlist')) {
	$feedButtons[] = icon_button(_L('Create a List'),"add","location.href='editlistrules.php?id=new'");
	if (getSystemSetting('_hasenrollment')) {
		$feedButtons[] = icon_button(_L('Create a List by Section'),"add","location.href='editlistsections.php?id=new'");
	}
	if ($USER->authorize('subscribe') && userCanSubscribe('list')) {
		$feedButtons[] = icon_button(_L('Subscribe to a List'),"fugue/star", "document.location='listsubscribe.php'");
	}
}


$feedFilters = array(
	"name" => array("icon" => "img/largeicons/tiny20x20/pencil.jpg", "name" => "Name"),
	"date" => array("icon" => "img/largeicons/tiny20x20/clock.jpg", "name" => "Date")
);

feed($feedButtons,$feedFilters);
?>
<script type="text/javascript" src="script/feed.js.php"></script>
<script type="text/javascript">
var filtes = <?= json_encode(array_keys($feedFilters))?>;
var activepage = 0;
var currentfilter = 'date';
document.observe('dom:loaded', function() {
	feed_applyfilter('<?=$_SERVER["REQUEST_URI"]?>','name');
});
</script>
<?

endWindow();


include_once("navbottom.inc.php");
