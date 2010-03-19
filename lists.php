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
			$orderby = "name";
			break;
	}
	$mergeditems = QuickQueryMultiRow("select  SQL_CALC_FOUND_ROWS 'list' as type,'Saved' as status, id, name, modifydate as date, lastused from list where type in ('person','section') and userid=? and deleted = 0 order by $orderby limit $start, $limit",true,false,array($USER->id));

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
			if(isset($item["lastused"]))
				$content .= 'This list was last used: <i>' . date("M j, g:i a",strtotime($item["lastused"])) . "</i>";
			else
				$content .= 'This list has never been used';
			$content .= '</a>';
			$tools = action_links (
				action_link("Edit", "pencil", "list.php?id=$itemid"),
				action_link("Preview", "application_view_list", "showlist.php?id=$itemid"),
				action_link("Delete", "cross", "lists.php?delete=$itemid", "return confirmDelete();")
				);
			//$tools = str_replace("&nbsp;|&nbsp;","<br />",$tools);
			$icon = 'largeicons/addrbook.jpg';

			$data->list[] = array("itemid" => $itemid,
										"defaultlink" => $defaultlink,
										"icon" => $icon,
										"title" => $title,
										"content" => $content,
										"tools" => $tools);
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
						html += '<tr><td valign=\"top\" width=\"60px\"><a href=\"' + item.defaultlink + '\"><img src=\"img/' + item.icon + '\" /></a></td><td ><div class=\"feedtitle\"><a href=\"' + item.defaultlink + '\">' + item.title + '</a></div><span>' + item.content + '</span></td>';
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
