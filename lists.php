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



$isajax = isset($_GET['ajax']);

if($isajax === true) {
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
	$mergeditems = QuickQueryMultiRow("select  SQL_CALC_FOUND_ROWS 'list' as type,'Saved' as status, id, name, modifydate as date, lastused from list where userid=? and deleted = 0 order by $orderby limit $start, $limit",true,false,array($USER->id));

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
				$content .= 'This list has never been used ';
			$content .= " and has " . listcontacts($itemid,"list") . '</a>';
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
