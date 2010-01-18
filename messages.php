<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("inc/common.inc.php");
require_once("inc/securityhelper.inc.php");
require_once("obj/Message.obj.php");
require_once("obj/MessagePart.obj.php");
require_once("obj/AudioFile.obj.php");
require_once("obj/FieldMap.obj.php");
require_once("obj/SurveyQuestionnaire.obj.php");
require_once("obj/SurveyQuestion.obj.php");
require_once("inc/html.inc.php");
require_once("inc/form.inc.php");
require_once("inc/table.inc.php");
require_once("inc/utils.inc.php");
require_once("inc/formatters.inc.php");


////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize(array('sendmessage', 'sendemail', 'sendphone', 'sendsms'))) {
	redirect('unauthorized.php');
}

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

if (isset($_GET['delete'])) {
	$deleteid = DBSafe($_GET['delete']);
	if (isset($_SESSION['messageid']) && ($_SESSION['messageid']== $deleteid))
		$_SESSION['messageid'] = NULL;
	if (userOwns("message",$deleteid)) {
		$message = new Message($deleteid);
		QuickUpdate("update message set deleted=1 where id='$deleteid'");
		notice(_L("The message, %s, is now deleted.", escapehtml($message->name)));
		redirect();
	} else {
		notice(_L("You do not have permission to delete this message."));
	}
}


$isajax = isset($_GET['ajax']);
if($isajax === true) {
	session_write_close();//WARNING: we don't keep a lock on the session file, any changes to session data are ignored past this point

	$start = 0 + (isset($_GET['pagestart']) ? $_GET['pagestart'] : 0);
	$limit = 100;
	$orderby = "modified desc";

	$filter = "";
	if (isset($_GET['filter'])) {
		$filter = $_GET['filter'];
	}
	switch ($filter) {
		case "name":
			$orderby = "name";
			break;
	}
	$mergeditems = QuickQueryMultiRow("
			select SQL_CALC_FOUND_ROWS 'message' as type,'Saved' as status,g.id as id, g.name as name, g.modified as date, g.deleted as deleted,
			 sum(type='phone') as phone, sum(type='email') as email,sum(type='sms') as sms
			from messagegroup g, message m where g.userid=? and g.deleted = 0 and g.modified is not null and m.messagegroupid = g.id
			group by g.id,m.languagecode order by g.$orderby limit $start,$limit",true,false,array($USER->id));


	$total = QuickQuery("select FOUND_ROWS()");
	$numpages = ceil($total/$limit);
	$curpage = ceil($start/$limit) + 1;
	$displayend = ($start + $limit) > $total ? $total : ($start + $limit);
	$displaystart = ($total) ? $start +1 : 0;

	if(empty($mergeditems)) {
			$data->list[] = array("itemid" => "",
										"defaultlink" => "",
										"defaultonclick" => "",
										"icon" => "largeicons/information.jpg",
										"title" => _L("No Messages."),
										"content" => "",
										"tools" => "");
	} else {
		while(!empty($mergeditems)) {
			$item = array_shift($mergeditems);
			$time = date("M j, g:i a",strtotime($item["date"]));
			$itemid = $item["id"];
			$types = $item["phone"] > 0?"," . _L("phone"):"";
			$types .= $item["email"] > 0?"," . _L("email"):"";
			$types .= $item["sms"] > 0?"," . _L("sms"):"";
			$title = escapehtml($item["name"]);
			$defaultlink = "messagegroup.php?id=$itemid";
			$content = '<a href="' . $defaultlink . '" >' . $time .  ' - <b>' .  _L('%1$s Content',typestring(substr($types,1))). '</b>' . '</a>';

			$icon = 'largeicons/letter.jpg';

			$tools = action_links (
				action_link("Edit", "pencil", 'messagegroup.php?id=' . $itemid),
				action_link("Delete", "cross", 'messages.php?delete=' . $itemid, "return confirmDelete();"),
				action_link("Rename", "textfield_rename", 'messagerename.php?id=' . $itemid));


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

function typestring($str) {
		$jobtypes = explode(",",$str);
		$typesstr = "";
		foreach($jobtypes as $jobtype) {
			if($jobtype == "sms")
				$alt = strtoupper($jobtype);
			else
				$alt = escapehtml(ucfirst($jobtype));
			$typesstr .= $alt . ", ";
		}
		$typesstr = trim($typesstr,', ');
		$andpos = strrpos($typesstr,',');
		if($andpos !== false)
			return substr_replace($typesstr," and ",$andpos,1);
		return $typesstr;
}


////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$PAGE = "notifications:messages";
$TITLE = "Message Builder";

include_once("nav.inc.php");

startWindow(_L('My Messages'), 'padding: 3px;', true, true);

?>
<table width="100%" style="padding-top: 7px;">
<tr>
	<td class="feed" style="width: 180px;vertical-align: top;font-size: 12px;" >
		<div>
		<?= icon_button(_L('Create New Message'),"add","location.href='messagegroup.php?id=new'") ?>
		<div style="clear:both;"></div>
		</div>
		<br />
		<h1 id="filterby">Sort By:</h1>
		<div id="allfilters" class="feedfilter">
			<a id="datefilter" href="lists.php?filter=date" onclick="applyfilter('date'); return false;"><img src="img/largeicons/tiny20x20/clock.jpg" />Modify Date</a><br />
			<a id="namefilter" href="lists.php?filter=name" onclick="applyfilter('name'); return false;"><img src="img/largeicons/tiny20x20/pencil.jpg" />Name</a><br />
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
		new Ajax.Request('messages.php?ajax=true&filter=' + filter + '&pagestart=' + activepage, {
			method:'get',
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
?>