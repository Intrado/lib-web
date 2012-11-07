<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("inc/common.inc.php");
require_once("inc/securityhelper.inc.php");
require_once("obj/PeopleList.obj.php");
require_once("obj/Person.obj.php");
require_once("inc/table.inc.php");
require_once("inc/utils.inc.php");
require_once("inc/form.inc.php");
require_once("inc/html.inc.php");
require_once("inc/formatters.inc.php");
require_once("obj/TargetedMessageCategory.obj.php");



////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////

if (!getSystemSetting('_hastargetedmessage', false) || !$USER->authorize('manageclassroommessaging')) {
	redirect('unauthorized.php');
}

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

if (isset($_GET['enable']) && isset($_GET['id'])) {
	session_write_close();//WARNING: we don't keep a lock on the session file, any changes to session data are ignored past this point
	header('Content-Type: application/json');
	$result = QuickUpdate("update targetedmessage set enabled=? where id=?",false,array(($_GET['enable']=="false"?0:1),$_GET['id']));
	echo json_encode($result!==false);
	exit();
} else if(isset($_GET['deletecategoryid'])) {
	session_write_close();//WARNING: we don't keep a lock on the session file, any changes to session data are ignored past this point
	header('Content-Type: application/json');
	$items = QuickQuery("select count(*) from targetedmessage where targetedmessagecategoryid = ? and deleted = 0",false,array($_GET['deletecategoryid']));

	if($items === "0")
		QuickUpdate("update targetedmessagecategory set deleted=1 where id=?",false,array($_GET['deletecategoryid']));

	//echo json_encode($items === "0" && QuickUpdate("delete from targetedmessagecategory where id=?",false,array($_GET['deletecategoryid'])));
	echo json_encode($items === "0");
	exit();
}

$categories = QuickQueryMultiRow("select id, name, image from targetedmessagecategory where deleted = 0",true);
$categoriesjson = array();

foreach($categories as $category) {
	$obj = null;
	$obj->name = escapehtml($category["name"]);
	if(isset($category["image"]) && isset($classroomcategoryicons[$category["image"]]))
		$obj->img = "img/icons/" . $classroomcategoryicons[$category["image"]]  . ".gif";
	else
		$obj->img = "img/pixel.gif";
	$categoriesjson[$category["id"]] = $obj;
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

	if (isset($_GET['moveid']) && isset($_GET['movetoid'])) {
		if(in_array($_GET['movetoid'], array_keys($categoriesjson)))
			QuickUpdate("update targetedmessage set targetedmessagecategoryid=? where id=?", false, array($_GET['movetoid'],$_GET['moveid']));
	} else if(isset($_GET['deletemessageid'])) {
		QuickUpdate("update targetedmessage set deleted=1 where id=?",false,array($_GET['deletemessageid']));
	}

	$getcategory = $_GET['category'];

	$start = 0 + (isset($_GET['pagestart']) ? $_GET['pagestart'] : 0);
	$limit = 100;

	$items = QuickQueryMultiRow("select SQL_CALC_FOUND_ROWS id, messagekey, targetedmessagecategoryid, overridemessagegroupid, enabled from targetedmessage where targetedmessagecategoryid = ? and deleted = 0 order by id limit $start, $limit",true,false,array($getcategory));

	$total = QuickQuery("select FOUND_ROWS()");

	$query = "select t.id, p.txt from targetedmessage t 
				inner join message m on (t.overridemessagegroupid = m.messagegroupid)
				inner join messagepart p on (p.messageid = m.id) 
				where not t.deleted and m.languagecode = 'en' and m.type='email' and p.sequence = 0";
	$customtxt = QuickQueryList($query,true,false,array($getcategory));

	$numpages = ceil($total/$limit);
	$curpage = ceil($start/$limit) + 1;
	$displayend = ($start + $limit) > $total ? $total : ($start + $limit);
	$displaystart = ($total) ? $start +1 : 0;

	$data->pageinfo = array($numpages,$limit,$curpage, "Showing $displaystart - $displayend of $total records on $numpages pages ");
	$data->list = array();

	$filename = "messagedata/en/targetedmessage.php";
	require_once($filename);

	while(!empty($items)) {
		$item = array_shift($items);

		if(isset($item["overridemessagegroupid"]) && isset($customtxt[$item["id"]])) {
			$title = $customtxt[$item["id"]];
		} else if(isset($messagedatacache["en"]) && isset($messagedatacache["en"][$item["messagekey"]])) {
				$title = $messagedatacache["en"][$item["messagekey"]];
		} else {
			$title = ""; // Could not find message for this message key.
		}
		$data->list[] = array(
			"id" => $item["id"],
			"enabled" => ($item["enabled"]==1),
			"title" => escapehtml($title),
			"deletable" => (substr($item["messagekey"],0,6) == "custom")
		);
	}
	echo json_encode($data);
	exit();
}



////////////////////////////////////////////////////////////////////////////////
// Display Functions
////////////////////////////////////////////////////////////////////////////////



////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$PAGE = "admin:settings";
$TITLE = "Classroom Message Manager";

include_once("nav.inc.php");

startWindow('Classroom Messages');

echo icon_button(_L('Create Category'),"add",null,"classroommessagecategory.php?id=new",'style="margin:10px;"');

?>
<div style="clear:both;"></div>


<div id='tabsContainer' style='vertical-align:middle;'></div>

<div id="libraryContent">

<?

foreach($categories as $category) {
	echo "<div id='lib-" . $category["id"] . "'>
			" .
			action_links (
				action_link("Edit", "pencil", "classroommessagecategory.php?id=" . $category["id"]),
				action_link("Delete", "cross", null,"deletecategory('". $category["id"] . "');return false;")
			)
			. "
			<h3>Messages</h3>
			" .
			icon_button(_L('Create Message'),"add",null,"classroommessageedit.php?id=new")
			. "
			<div id='pagewrappertop-" . $category["id"] . "'></div>
			<div style='clear:both;'></div>
			<table id='items-" . $category["id"] . "'>
				<tr><td></td></tr>
			</table>
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
	new Ajax.Request('classroommessagemanager.php', {
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

function move(current, item) {
	var value = {action:"move",messageid:item.id.substring(5),tocategory:item.value};
	updatecategory(current,value);
}


function deletemessage(current, item) {
	var confirm = confirmDelete();
	if(confirm){
		var value = {action:"deletemessage",deletemessageid:item.id.substring(7)};
		updatecategory(current,value);
	}
}
function deletecategory(id) {
	var confirm = confirmDelete();
	if(confirm){
		new Ajax.Request('classroommessagemanager.php', {
		method:'get',
		parameters: {deletecategoryid:id},
		onSuccess: function (response) {
			var result = response.responseJSON;
			if(!result)
				alert('<?= _L("Unable to delete category, All messages contained within the category must be moved or deleted.") ?>');
			else
				window.location = 'classroommessagemanager.php';
		}
	});
	}
}

function updatecategory(category, actioninfo) {
	var sendvars = {
		ajax:"true",
		category:category,
		pagestart:activepage
	};
	if(actioninfo != undefined) {
		if(actioninfo.action == "move") {
			sendvars.moveid = actioninfo.messageid;
			sendvars.movetoid = actioninfo.tocategory;
		} else if(actioninfo.action == "deletemessage") {
			sendvars.deletemessageid = actioninfo.deletemessageid;
		}
	}
	new Ajax.Request('classroommessagemanager.php', {
		method:'get',
		parameters: sendvars,
		onSuccess: function (response) {
			var result = response.responseJSON;
			if(result) {
				var size = result.list.length;

				var items = new Element('tbody',{width:'100%'});

				var header = new Element('tr').addClassName("listHeader");

				header.insert(new Element('th').update('Enabled'));
				header.insert(new Element('th',{width:'100%',align:'left'}).update('Message'));
				header.insert(new Element('th',{align:'left'}).update('Actions'));


				items.insert(header);
				var options = '<option value="">-- Move To --</option>';
				categoryinfo.each(function(cat) {
					if(cat.key != category)
						options += '<option value="' + cat.key + '">' + cat.value.name + '</option>';
				});

				for(i=0;i<size;i++){
					var item = result.list[i];
					var row = new Element('tr');
					if(i%2)
						row.addClassName("listAlt");
					row.insert(new Element('td',{align:"right"}).update('<input id="enable-' + item.id + '" type="checkbox" ' + (item.enabled?'checked':'') + ' onclick="updateenabled(this);return false;" />'));
					row.insert(new Element('td').update(item.title));
					row.insert(new Element('td',{style:"white-space: nowrap;"}).update(
					'<a href="classroommessageedit.php?id=' + item.id + '"  class="actionlink" title="Edit" ><img src="img/icons/pencil.gif" alt="Edit">Edit</a>' + (item.deletable?'&nbsp;|&nbsp;<a id="delete-' + item.id + '" href="#"  class="actionlink" title="delete" onclick="deletemessage(' + category + ',this); return false;" ><img src="img/icons/cross.gif" alt="delete">Delete</a>':'') + '&nbsp;|&nbsp;<select id="move-' + item.id + '" onchange="move(' + category + ',this)"/>' + options + '</select>'
					));
					items.insert(row);
				}

				$('items-' + category).update(items);

				var pagetop = new Element('div',{style: 'float:right;'}).update(result.pageinfo[3]);
				var pagebottom = new Element('div',{style: 'float:right;'}).update(result.pageinfo[3]);
				var selecttop = new Element('select');//activepage = this.value;updatecategory(\'' + category + '\');'});
				var selectbottom = new Element('select');//, {onchange: 'activepage = this.value;updatecategory(\'' + category + '\');'});
				for (var x = 0; x < result.pageinfo[0]; x++) {
					var offset = x * result.pageinfo[1];
					var psel = result.pageinfo[2] == x+1;
					selecttop.insert(new Element('option', {value: offset,selected:psel}).update('Page ' + (x+1)));
					selectbottom.insert(new Element('option', {value: offset,selected:psel}).update('Page ' + (x+1)));
				}
				pagetop.insert(selecttop);
				pagebottom.insert(selectbottom);
				selecttop.observe('change',(function(category) {activepage = this.value;updatecategory(category);}).curry(category));
				selectbottom.observe('change',(function(category) {activepage = this.value;updatecategory(category);}).curry(category));

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
