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


////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('managesystem')) {
	redirect('unauthorized.php');
}

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

$filter = "";
if (isset($_GET['filter'])) {
	$filter = $_GET['filter'];
}

$isajax = isset($_GET['ajax']);

$items = array();
if($isajax === true) {
	$start = 0 + (isset($_GET['pagestart']) ? $_GET['pagestart'] : 0);
	$limit = 20;
	$orderby = "modifydate desc";
	session_write_close();//WARNING: we don't keep a lock on the session file, any changes to session data are ignored past this point
	switch ($filter) {
		case "name":
			$orderby = "name";
			break;
	}
	$items = QuickQueryMultiRow("select SQL_CALC_FOUND_ROWS id, messagekey, targetedmessagecategoryid, overridemessagegroupid from targetedmessage where 1 order by id limit $start, $limit",true,false,array($USER->id));

	$total = QuickQuery("select FOUND_ROWS()");
	$numpages = ceil($total/$limit);
	$curpage = ceil($start/$limit) + 1;
	$displayend = ($start + $limit) > $total ? $total : ($start + $limit);
	$displaystart = ($total) ? $start +1 : 0;

	header('Content-Type: application/json');
	$data->pageinfo = array($numpages,$limit,$curpage, "Showing $displaystart - $displayend of $total records on $numpages pages ");
	if(empty($items)) {
		$data->list[] = array("itemid" => "",
									"defaultlink" => "",
									"defaultonclick" => "",
									"icon" => "largeicons/information.jpg",
									"title" => _L("No Targeted Messages."),
									"content" => "",
									"tools" => "");
	} else {
		while(!empty($items)) {
			$item = array_shift($items);
			$title = $item["messagekey"];
			$itemid = $item["id"];
			$defaultonclick = "";
			$defaultlink = "";
			$content = '<a href="' . $defaultlink . '">';
			$content .= " In Category " . $item["targetedmessagecategoryid"] . '</a>';
			$tools = action_links (action_link("Edit", "pencil", ""),action_link("Disable", "diagona/16/151", ""));
			$icon = 'largeicons/notepad.jpg';

			$data->list[] = array("itemid" => $itemid,
										"defaultlink" => $defaultlink,
										"defaultonclick" => $defaultonclick,
										"icon" => $icon,
										"title" => $title,
										"content" => $content,
										"tools" => $tools);
		}
	}

	echo json_encode(!empty($data) ? $data : false);
	exit();
}


$categories = QuickQueryList("select targetedmessagecategoryid from targetedmessage where 1 group by targetedmessagecategoryid");

////////////////////////////////////////////////////////////////////////////////
// Display Functions
////////////////////////////////////////////////////////////////////////////////



////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$PAGE = "admin:settings";
$TITLE = "Targeted Message Manager";

include_once("nav.inc.php");

startWindow('Targeted Messages Categories');
?>
<table width="100%"style="padding-top: 7px;">
	<tr>
		<td class="feed" style="width: 180px;vertical-align: top;font-size: 12px;" >
			<div>
				<input id="newcategory" type="text" size="20" style="margin:5px"/>
				<?= icon_button(_L('Create Category'),"add","location.href='targetedmessageedit.php?id=new'") ?>
			<div style="clear:both;"></div>
			</div>
		</td>
		<td width="10px" style="border-left: 1px dotted gray;" >&nbsp;</td>
		<td class="feed" valign="top" >
			<table>
				<tr>
					<td valign="top" width="60px"><img src="img/ajax-loader.gif" /></td>
					<td >
						<div class="feedtitle">
							<a href=""><?= _L("Not Implemented") ?></a>
						</div>
					</td>
				</tr>
			</table>
		</td>
	</tr>
</table>

<?
endWindow();

startWindow('Targeted Messages');

?>
<table width="100%"style="padding-top: 7px;">
	<tr>
		<td class="feed" style="width: 180px;vertical-align: top;font-size: 12px;" >
			<div>
				<?= icon_button(_L('Create New Message'),"add","location.href='targetedmessageedit.php?id=new'") ?>
			<div style="clear:both;"></div>
			</div>
			<br />
			<h1 id="filterby"><?= _L("Filter By Category:") ?></h1>
			<div id="allfilters" class="feedfilter">
				<?
				foreach($categories as $category) {
					echo '<a id="' .$category .'filter" href="targetedmessageedit.php?filter=' .$category .'" onclick="applyfilter(\'' .$category .'\'); return false;"><img src="img/largeicons/tiny20x20/globe.jpg">' .$category .'</a><br />';
				}
				?>
			</div>
			<h1><?= _L("Sort By:") ?></h1>
			<div id="allsort" class="feedfilter">
				<a id="namefilter" href="targetedmessageedit.php?filter=name" onclick="applyfilter('name'); return false;"><img src="img/largeicons/tiny20x20/pencil.jpg">Name</a><br />
			</div>
		</td>
		<td width="10px" style="border-left: 1px dotted gray;" >&nbsp;</td>
		<td class="feed" valign="top" >
			<div id="pagewrappertop"></div>
			<table id="feeditems">
				<tr>
					<td valign="top" width="60px"><img src="img/ajax-loader.gif" /></td>
					<td >
						<div class="feedtitle">
							<a href="">
							<?= _L("Loading Targeted Messages") ?></a>
						</div>
					</td>
				</tr>
			</table>
			<br />
			<div id="pagewrapperbottom"></div>
		</td>
	</tr>
</table>

<?
endWindow();

// Script
?>
<script type="text/javascript" language="javascript">

var filtes = Array(<?=  "'" . implode("','", $categories) . "'" ?>);
var activepage = 0;

function applyfilter(filter) {
	new Ajax.Request('targetedmessageedit.php?ajax=true&filter=' + filter + '&pagestart=' + activepage, {
		method:'get',
		onSuccess: function (response) {
			var result = response.responseJSON;
			if(result) {
				var html = '';
				var size = result.list.length;

				for(i=0;i<size;i++){
					var item = result.list[i];
					html += '<tr><td valign=\"top\" width=\"60px\"><a href=\"' + item.defaultlink + '\" ' + item.defaultonclick + '><img src=\"img/' + item.icon + '\" /></a></td><td ><div class=\"feedtitle\"><a href=\"' + item.defaultlink + '\" ' + item.defaultonclick + '>' + item.title + '</a></div><span>' + item.content + '</span></td>';
					if(item.tools) {
						html += '<td valign=\"middle\" width=\"100px\"><div>' + item.tools + '</div></td>';
					}
					html += '</tr>';
				}
				$('feeditems').update(html);
				var pagetop = new Element('div',{style: 'float:right;'});
				var pagebottom = new Element('div',{style: 'float:right;'});

				pagetop.update(result.pageinfo[3]);
				pagebottom.update(result.pageinfo[3]);
				var selecttop = new Element('select', {onchange: 'activepage = this.value;applyfilter(\'' + filter + '\');'});
				var selectbottom = new Element('select', {onchange: 'activepage = this.value;applyfilter(\'' + filter + '\');'});
				for (var x = 0; x < result.pageinfo[0]; x++) {
					var offset = x * result.pageinfo[1];
					var opttop = new Element('option', {value: offset});
					var optbottom = new Element('option', {value: offset});
					optbottom.selected = opttop.selected = (result.pageinfo[2] == x+1) ? 'selected' : '';
					opttop.update('Page ' + (x+1));
					optbottom.update('Page ' + (x+1));
					selecttop.insert(opttop);
					selectbottom.insert(optbottom);
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
	blankFieldValue('newcategory','New Category Name');
	$('newcategory').focus();
	$('newcategory').blur();
	applyfilter('name');
});
</script>
<?

include_once("navbottom.inc.php");
