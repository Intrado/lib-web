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

if (isset($_GET['enable']) && isset($_GET['id'])) {
	session_write_close();//WARNING: we don't keep a lock on the session file, any changes to session data are ignored past this point
	header('Content-Type: application/json');
	$return = QuickUpdate("update targetedmessage set enabled=? where id=?",false,array(($_GET['enable']=="false"?0:1),$_GET['id']));
	echo json_encode($return!==false);
	exit();
}

$ajax = isset($_GET['ajax']);

$items = array();
if($ajax === true) {
	session_write_close();//WARNING: we don't keep a lock on the session file, any changes to session data are ignored past this point
	header('Content-Type: application/json');

	if (!isset($_GET['category'])) {
		echo json_encode(false);
		exit();
	}
	$getcategory = $_GET['category'];

	$start = 0 + (isset($_GET['pagestart']) ? $_GET['pagestart'] : 0);
	$limit = 100;

	$items = QuickQueryMultiRow("select SQL_CALC_FOUND_ROWS id, messagekey, targetedmessagecategoryid, overridemessagegroupid, enabled from targetedmessage where targetedmessagecategoryid = ? order by id limit $start, $limit",true,false,array($getcategory));

	$total = QuickQuery("select FOUND_ROWS()");
	$numpages = ceil($total/$limit);
	$curpage = ceil($start/$limit) + 1;
	$displayend = ($start + $limit) > $total ? $total : ($start + $limit);
	$displaystart = ($total) ? $start +1 : 0;

	$data->pageinfo = array($numpages,$limit,$curpage, "Showing $displaystart - $displayend of $total records on $numpages pages ");

	while(!empty($items)) {
		$item = array_shift($items);
		$data->list[] = array(
								"id" => $item["id"],
								"enabled" => ($item["enabled"]==1),
								"title" => $item["messagekey"],
								"actions" => action_links (action_link("Edit", "pencil", ""))
		);
	}
	echo json_encode(!empty($data) ? $data : false);
	exit();
}

$categoriesjson = array();
$categories = QuickQueryMultiRow("select id, name, image from targetedmessagecategory where 1",true);
foreach($categories as $category) {
	$obj = null;
	$obj->name = $category["name"];
	$obj->img = isset($category["image"])?"img/icons/" . $category["image"] . ".gif":"img/pixel.gif";
	$categoriesjson[$category["id"]] = $obj;
}

////////////////////////////////////////////////////////////////////////////////
// Display Functions
////////////////////////////////////////////////////////////////////////////////



////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$PAGE = "admin:settings";
$TITLE = "Targeted Message Manager";

include_once("nav.inc.php");

startWindow('Targeted Messages');

echo icon_button(_L('Create Category'),"add",null,"targetedmessagecategory.php?id=new",'style="margin:10px;"');

?>
<div style="clear:both;"></div>
<div id='tabsContainer' style='margin:10px; margin-right:0px;vertical-align:middle;'></div>

<div id="libraryContent">

<?
foreach($categories as $category) {
	echo "<div id='lib-" . $category["id"] . "'>
			<h3>Edit Category</h3>
			" .
			action_links (
				action_link("Rename", "textfield_rename", "targetedmessagecategory.php?id=" . $category["id"]),
				action_link("Delete", "cross", "")
			)
			. "
			<h3>Massages</h3>
			" .
			icon_button(_L('Create Message'),"add","location.href='targetedmessageedit.php?id=new'")
			. "
			<div id='pagewrappertop-" . $category["id"] . "'></div>
			<div style='clear:both;'></div>
			<div id='items-" . $category["id"] . "'>";
	echo "
			</div>
			<div id='pagewrapperbottom-" . $category["id"] . "'></div>
		</div>";
}
?>

</div>

<?
//echo icon_button(_L('Done'),"tick",null,"settings.php");

endWindow();




?>
<script type="text/javascript" src="script/accordion.js"></script>

<script type="text/javascript" language="javascript">

var categoryinfo = $H(<?= json_encode($categoriesjson) ?>);
var tabs;

var activepage = 0;

function updateenabled(input) {
	new Ajax.Request('targetedmessageedit.php', {
		method:'get',
		parameters: {enable: input.checked,id:input.id.substring(7)},
		onSuccess: function (response) {
			var result = response.responseJSON;
			if(result) {
				input.checked = !input.checked;
			}
		}
	});
}

function updatecategory(category) {
	new Ajax.Request('targetedmessageedit.php?ajax=true&category=' + category + '&pagestart=' + activepage, {
		method:'get',
		onSuccess: function (response) {
			var result = response.responseJSON;
			if(result) {
				var size = result.list.length;

				var items = new Element('table',{width:'100%'});
				var header = new Element('tr').addClassName("listHeader");

				header.insert(new Element('th').update('Enabled'));
				header.insert(new Element('th',{width:'100%',align:'left'}).update('Message'));
				header.insert(new Element('th').update('Actions'));

				items.insert(header);
				for(i=0;i<size;i++){
					var item = result.list[i];
					var row = new Element('tr');
					if(i%2)
						row.addClassName("listAlt");
					row.insert(new Element('td',{align:"right"}).update(new Element('input',{id:'enable-' + item.id,type:'checkbox',checked:item.enabled,onclick:'updateenabled(this);return false;'})));
					row.insert(new Element('td').update(item.title));
					row.insert(new Element('td').update(item.actions));

					items.insert(row);
				}

				$('items-' + category).update(items);

				var pagetop = new Element('div',{style: 'float:right;'}).update(result.pageinfo[3]);
				var pagebottom = new Element('div',{style: 'float:right;'}).update(result.pageinfo[3]);
				var selecttop = new Element('select', {onchange: 'activepage = this.value;updatecategory(\'' + category + '\');'});
				var selectbottom = new Element('select', {onchange: 'activepage = this.value;updatecategory(\'' + category + '\');'});
				for (var x = 0; x < result.pageinfo[0]; x++) {
					var offset = x * result.pageinfo[1];
					var psel = result.pageinfo[2] == x+1;
					selecttop.insert(new Element('option', {value: offset,selected:psel}).update('Page ' + (x+1)));
					selectbottom.insert(new Element('option', {value: offset,selected:psel}).update('Page ' + (x+1)));
				}
				pagetop.insert(selecttop);
				pagebottom.insert(selectbottom);
				$('pagewrappertop-' + category).update(pagetop);
				$('pagewrapperbottom-' + category).update(pagebottom);
				
			}
		}
	});
}

document.observe('dom:loaded', function() {
	// Load tabs
	tabs = new Tabs('tabsContainer',{});
	categoryinfo.each(function(category) {
		var conentid = "lib-" + category.key;
		category.value.img

		tabs.add_section(conentid);
		tabs.update_section(conentid, {
			"title": category.value.name,
			"icon": category.value.img,
			"content": $(conentid).remove()
		});
	});
	var first = categoryinfo.keys().first();
	tabs.show_section('lib-' + first);
	updatecategory(first);

	tabs.container.observe('Tabs:ClickTitle', function(event) {
		activepage = 0;
		updatecategory(event.memo.section.substring(4));
	});


});
</script>

<?

include_once("navbottom.inc.php");
