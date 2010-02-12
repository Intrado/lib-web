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
require_once("obj/TargetedMessageCategory.obj.php");

////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('createlist')) {
	redirect('unauthorized.php');
}

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////


if (isset($_POST['classid'])) {
	$id = $_POST['classid'] + 0;
	exit(0);
}

$categories = DBFindMany("TargetedMessageCategory", "from targetedmessagecategory where deleted = 0");

$isajax = isset($_GET['ajax']);

if($isajax === true) {
	session_write_close();//WARNING: we don't keep a lock on the session file, any changes to session data are ignored past this point

	if (isset($_GET['peoplemessageid'])) {
		$query = "select p.f01 as firstname,p.f02 as lastname, e.notes as remark from
			targetedmessage tm
		join 
			event e on (e.targetedmessageid = tm.id)
		join
			personassociation pa on (pa.eventid = e.id)
		join
			person p on (pa.personid = p.id)
		where e.targetedmessageid = ? and e.userid = ? and Date(e.occurence) = CURDATE()";

/*
		$query = "select pa.personid, e.notes as remark from
			targetedmessage tm
		join
			event e on (e.targetedmessageid = tm.id)
		join
			personassociation pa on (pa.eventid = e.id)
		where e.targetedmessageid = ? and e.userid = ? and Date(e.occurence) = CURDATE()";
*/
		$people = QuickQueryMultiRow($query,true,false,array($_GET['peoplemessageid'],$USER->id));


		header('Content-Type: application/json');
		echo json_encode(!empty($people) ? $people : false);
		exit();
	}





	$messagedatapath = "messagedata/en/targetedmessage.php";
	$orderby = "name";

	$sqlargs = array($USER->id);
	$extrasql = "";
	if (isset($_GET['category']) && $_GET['category'] != 'none') {
		$sqlargs[] = $_GET['category'];
		$extrasql .= " and tm.targetedmessagecategoryid = ? ";
		$getcategory = true;
	} else {
		$getcategory = false;
	}

	if(isset($_GET['sortby'])) {
		switch ($_GET['sortby']) {
			case "name":
				$orderby = "tm.messagekey";
				break;
			case "person":
				$orderby = "persons desc";
				break;
		}
	}

	$query = "select tm.targetedmessagecategoryid as category, count(pa.personid) as persons, e.targetedmessageid as targetedmessageid,
		 tm.messagekey, tm.overridemessagegroupid from
		 personassociation pa left join event e on (pa.eventid = e.id)
		 left join targetedmessage tm on (e.targetedmessageid = tm.id)
		 where e.targetedmessageid is not null and e.userid = ? $extrasql and Date(e.occurence) = CURDATE() group by e.targetedmessageid order by " . $orderby;
	$messages = QuickQueryMultiRow($query,true,false,$sqlargs);

	$overrideids = array();
	if(!empty($messages)) {
		foreach($messages as $message) {
			if(isset($message["overridemessagegroupid"]))
				$overrideids[] = $message["overridemessagegroupid"];
		}
	}

	if(!empty($overrideids)) {
		$customtxt = QuickQueryList("select m.messagegroupid, p.txt from message m, messagepart p
									where m.messagegroupid in (" . implode(",",$overrideids) . ") and
									m.languagecode = 'en' and
									p.messageid = m.id and p.sequence = 0",true);
	}
	if(file_exists($messagedatapath))
		include_once($messagedatapath);


	if(empty($messages)) {
			$data->list[] = array("itemid" => "",
										"defaultlink" => "",
										"icon" => "largeicons/information.jpg",
										"title" => $getcategory?_L("No Classroom Comments For This Category"):_L("No Classroom Comments"),
										"content" => "",
										"tools" => "");
	} else {
		foreach($messages as $message) {
			if(isset($message["overridemessagegroupid"]) && isset($customtxt[$message["overridemessagegroupid"]])) {
				$title = $customtxt[$message["overridemessagegroupid"]];
			} else if(isset($messagedatacache["en"]) && isset($messagedatacache["en"][$message["messagekey"]])) {
				$title = $messagedatacache["en"][$message["messagekey"]];
			} else {
				$title = ""; // Could not find message for this message key.
			}
			$persons = $message["persons"];
			$title .= " (" . ($persons==1?_L("%s Person", $persons):_L("%s Persons", $persons)). ")";
			$itemid = $message["targetedmessageid"];
			$defaultlink = "";
			$content = '<a href="' . $defaultlink . '"></a>';
			$icon = 'icons/' . $classroomcategoryicons[$categories[$message["category"]]->image]  . '.gif';
			$data->list[] = array("id" => $itemid,
										"defaultlink" => $defaultlink,
										"icon" => $icon,
										"title" => $title,
										"content" => $content,
								);
			//error_log($categories[$message["category"]]->img);
		}
	}
	//error_log(json_encode(!empty($data) ? $data : false));
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


$PAGE = "notifications:classroom";
$TITLE = _L('Classroom Comments');

include_once("nav.inc.php");

startWindow(_L('Classroom Comments'));

?>
<table width="100%" style="padding-top: 7px;">
<tr>
	<td class="feed" style="width: 180px;vertical-align: top;font-size: 12px;" >


		<?= icon_button("Pick Comments", "add", null, "classroommessage.php") ?>
		<select style="margin-top:20px;">
			<option value="" selected>-- All Classes --</option>
		</select>
		<div style="clear:both;"></div>
		<h1 id="filterby">Filter By Category:</h1>
		<div id="allcategories" class="feedfilter">
			<a id="catfilter-none" href="#" onclick="applyfilter('none','category'); return false;"><img src="img/largeicons/tiny20x20/globe.jpg" />Show All</a>
			<div style="padding-left:20px;">
			<?
				foreach($categories as $category) {
					$id  = 'catfilter-' . $category->id;
					echo '<a id="' . $id . '" href="#" onclick="applyfilter(\'' . $category->id . '\',\'category\'); return false;"><img src="img/icons/' . $classroomcategoryicons[$category->image]  . '.gif" />' . $category->name . '</a><br />';
				}
			?>
			</div>
		</div>
		<h1 id="sortby">Sort By:</h1>
		<div id="allsorters" class="feedfilter">
			<a id="sorter-person" href="#" onclick="applyfilter('person','sort'); return false;"><img src="img/largeicons/tiny20x20/barreport.jpg" />Person Count</a><br />
			<a id="sorter-name" href="#" onclick="applyfilter('name','sort'); return false;"><img src="img/largeicons/tiny20x20/pencil.jpg" />Name</a><br />
		</div>
	</td>
	<td width="10px" style="border-left: 1px dotted gray;" >&nbsp;</td>
	<td class="feed" valign="top" >

		<table id="feeditems">
			<tr>
				<td valign='top' width='60px'><img src='img/ajax-loader.gif' /></td>
				<td >
					<div class='feedtitle'>
						<a href=''>
						<?= _L("Loading Classroom Comments") ?></a>
					</div>
				</td>
			</tr>
		</table>
		<br />
	</td>
</tr>
</table>
<br />


<script type="text/javascript" language="javascript">

var sections = Array();
var categories = Array('none'<?= empty($categories)?"":"," . implode(",",array_keys($categories)) ?>);
var sorters = Array('none','person','name');

var currentsection = 'none';
var currentcategory = 'none';
var currentsorter = 'none';


function setactions(prefix,action,actions,color) {
	var itm = false;
	size = actions.length;
	for(var i=0;i<size;i++){
		itm = $(prefix + actions[i]);
		if(itm)
			itm.setStyle({color: color, fontWeight: 'normal'});
	}
	itm = $(prefix + action);
	if(itm) 
		itm.setStyle({color: '#000000',fontWeight: 'bold'});
	
}


function applyfilter(action,type) {
		var section = type == 'section'? action : currentsection;
		var category = type == 'category'? action : currentcategory;
		var sorter = type == 'sort'? action : currentsorter;

		new Ajax.Request('classroommessageoverview.php', {
			method:'get',
			parameters:{ajax:true,category:category,sortby:sorter},
			onSuccess: function (response) {
				var result = response.responseJSON;
				if(result) {
					var html = '';
					var size = result.list.length;

					for(i=0;i<size;i++){
						var item = result.list[i];
						html += '<tr><td valign=\"top\" width=\"60px\"><a href=\"' + item.defaultlink + '\" onclick="expandview(\'' + item.id + '\');return false;"><img src=\"img/' + item.icon + '\" /></a></td><td ><div class=\"feedtitle\"><a href=\"' + item.defaultlink + '\" onclick="expandview(\'' + item.id + '\');return false;">' + item.title + '</a></div><span>' + item.content + '</span><div id="itmwrap-' + item.id +'"><div id="itmdata-' + item.id +'" style="display:none;"></div></div></td>';
						html += '</tr>';
					}
					$('feeditems').update(html);

					var filtercolor = $('filterby').getStyle('color');
					if(!filtercolor)
						filtercolor = '#000';

					setactions('catfilter-',category,categories,filtercolor);
					setactions('sorter-',sorter,sorters,filtercolor);
	
					currentcategory = category;
					currentsorter = sorter;
				}

			}
		});
}

function expandview(id) {
	var frame = $('itmdata-' + id);

	if(!frame.visible()) {
		new Ajax.Request('classroommessageoverview.php', {
			method:'get',
			parameters:{ajax:true,peoplemessageid:id},
			onSuccess: function (response) {
				var result = response.responseJSON;
				if(result) {


					var itminfo = '<table><tr><th align="left">First Name</th><th align="left">Last Name</th><th align="left">Remark</th></tr>';
					var size = result.length;
					for(var i = 0;i < size;i++) {
						itminfo += "<tr><td>" + result[i].firstname + "</td><td>" + result[i].lastname + "</td><td>" + result[i].remark + "</td></tr>";
					}
					itminfo += "<table>";

					frame.update(itminfo);
					Effect.BlindDown(frame,{ duration: 0.5 });
				}
			}
		});
	} else {
		Effect.BlindUp(frame,{ duration: 0.5 });
	}
}
document.observe('dom:loaded', function() {
	applyfilter('person','sort');
});
</script>
<?

endWindow();




include_once("navbottom.inc.php");