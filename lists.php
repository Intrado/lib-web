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
if (!$USER->authorize('createlist')) {
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


if (isset($_GET['ajax'])) {
	session_write_close();//WARNING: we don't keep a lock on the session file, any changes to session data are ignored past this point
	$start = 0 + (isset($_GET['pagestart']) ? $_GET['pagestart'] : 0);
	$limit = 20;
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
	$mergeditems = QuickQueryMultiRow("
		select SQL_CALC_FOUND_ROWS 
			'list' as type, 'Saved' as status, l.id as id, l.name as name, (l.name +0) as digitsfirst, l.modifydate as date, l.lastused as lastused,
			p.action as publishaction, p.id as publishid, u.login as owner
		from list l
			inner join user u on
				(l.userid = u.id)
			left join publish p on
				(p.listid = l.id)
		where l.type in ('person','section')
			and (l.userid = ? or p.userid = ?)
			and not l.deleted
		group by id
		order by $orderby
		limit $start, $limit",
		true,false,array($USER->id, $USER->id));

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
		while(!empty($mergeditems) && $limit > 0) {
			$item = array_shift($mergeditems);
			$time = date("M j, g:i a",strtotime($item["date"]));
			$title = $item["status"];
			$itemid = $item["id"];
			$defaultlink = "";
			$title = escapehtml($item["name"]);
			$defaultlink = "list.php?id=$itemid";
			$content = '<a href="' . $defaultlink . '">' . ($item["date"]!== null?$time . '&nbsp;-&nbsp;':"");
			$publishaction = $item['publishaction'];
			$publishid = $item['publishid'];

			// give the user some text
			$publishmessage = '';
			if ($publishaction == 'publish')
				$publishmessage = _L('Changes to this message are published.');
			
			// tell the user it's a subscription. change the href to view instead of edit
			if ($publishaction == 'subscribe') {
				$publishmessage = _L('You are subscribed to this message. Owner: (%s)', $item['owner']);
				$defaultlink = "messagegroupview.php?id=$itemid";
			}
			
			// Users with published or subscribed lists will get a special action item
			$publishactionlink = "";
			if ($USER->authorize("publish") && userCanPublish('list')) {
				// this message is published, else allow it to be
				if ($publishaction == 'publish')
					$publishactionlink = action_link(_L("Modify Publication"), "fugue/star__pencil", "publisheditorwiz.php?id=$itemid&type=list");
				else
					$publishactionlink = action_link(_L("Publish"), "fugue/star__plus", "publisheditorwiz.php?id=$itemid&type=list");
			}
			if ($USER->authorize("subscribe") && userCanSubscribe('list')) {
				// this message is subscribed to
				if ($publishaction == 'subscribe')
					$publishactionlink = action_link("Un-Subscribe", "fugue/star__minus", "lists.php?id=$publishid&remove");
			}

			if(isset($item["lastused"]))
				$content .= 'This list was last used: <i>' . date("M j, g:i a",strtotime($item["lastused"])) . "</i>";
			else
				$content .= 'This list has never been used';
			$content .= '</a>';
			
			
			
			
			// if the user is only subscribed to this list, they can't edit or delete
			if ($publishaction == 'subscribe')
				$tools = action_links (
					action_link("Preview", "application_view_list", "showlist.php?id=$itemid"),
					$publishactionlink);
			else
				$tools = action_links (
					action_link("Edit", "pencil", "list.php?id=$itemid"),
					action_link("Preview", "application_view_list", "showlist.php?id=$itemid"),
					$publishactionlink,
					action_link("Delete", "cross", "lists.php?delete=$itemid", "return confirmDelete();"));
				
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
<table width="100%" style="padding-top: 7px;">
<tr>
	<td class="feed" style="width: 180px;vertical-align: top;font-size: 12px;" >
		<div>
			<?= icon_button(_L('Create New List with Rules'),"add","location.href='editlistrules.php?id=new'") ?>
			<div style="clear:both;"></div>
			<?
				if (getSystemSetting('_hasenrollment')) {
					echo icon_button(_L('Create New List with Sections'),"add","location.href='editlistsections.php?id=new'");
				}
			?>
			<div style="clear:both;"></div>
			<?=(($USER->authorize('subscribe') && userCanSubscribe('messagegroup'))?icon_button(_L('Subscribe to List'),"add", "document.location='listsubscribe.php'"):'') ?>
			<div style="clear:both;"></div>
		</div>
		<br />
		<h1 id="filterby">Sort By:</h1>
		<div id="allfilters" class="feedfilter">
			<a id="datefilter" href="#" onclick="applyfilter('date'); return false;"><img src="img/largeicons/tiny20x20/clock.jpg" />Modify Date</a><br />
			<a id="namefilter" href="#" onclick="applyfilter('name'); return false;"><img src="img/largeicons/tiny20x20/pencil.jpg" />Name</a><br />
		</div>
	</td>
	<td width="10px" style="border-left: 1px dotted gray;" >&nbsp;</td>
	<td class="feed" valign="top" >
		<div id="pagewrappertop"></div>

		<table id="feeditems">
			<tr>
				<td valign='top' width='60px'><img src='img/ajax-loader.gif' /></td>
				<td >
					<div class='feedtitle'>
						<a href=''>
						<?= _L("Loading Lists") ?></a>
					</div>
				</td>
			</tr>
		</table>
		<br />
		<div id="pagewrapperbottom"></div>
	</td>
</tr>
</table>


<script type="text/javascript" language="javascript">
var filtes = Array('date','name');
var activepage = 0;

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
						html += '<tr><td valign=\"top\" width=\"60px\"><a href=\"' + item.defaultlink + '\"><img src=\"img/' + item.icon + '\" /></a></td><td ><div class=\"feedtitle\"><a href=\"' + item.defaultlink + '\">' + item.title + '</a></div>';
						if(item.publishmessage) {
							html += '<div class=\"feedsubtitle\"><a href=\"' + item.defaultlink + '\"><img src=\"img/icons/diagona/10/031.gif\" />' + item.publishmessage + '</div><div style=\"clear both;\"></div>';
						}
						html += '<span>' + item.content + '</span></td>';
						if(item.tools) {
							html += '<td valign=\"middle\" width=\"100px\"><div>' + item.tools + '</div></td>';
						}
						html += '</tr>';
					}
					$('feeditems').update(html);
					var pagetop = new Element('div',{style: 'float:right;'}).update(result.pageinfo[3]);
					var pagebottom = new Element('div',{style: 'float:right;'}).update(result.pageinfo[3]);

					var selecttop = new Element('select', {onchange: 'activepage = this.value;applyfilter(\'' + filter + '\');'});
					var selectbottom = new Element('select', {onchange: 'activepage = this.value;applyfilter(\'' + filter + '\');'});
					for (var x = 0; x < result.pageinfo[0]; x++) {
						var offset = x * result.pageinfo[1];
						var selected = (result.pageinfo[2] == x+1);
						selecttop.insert(new Element('option', {value: offset,selected:selected}).update('Page ' + (x+1)));
						selectbottom.insert(new Element('option', {value: offset,selected:selected}).update('Page ' + (x+1)));
					}
					pagetop.insert(selecttop);
					pagebottom.insert(selectbottom);
					$('pagewrappertop').update(pagetop);
					$('pagewrapperbottom').update(pagebottom);

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
	applyfilter('date');
});
</script>
<?

endWindow();


include_once("navbottom.inc.php");
