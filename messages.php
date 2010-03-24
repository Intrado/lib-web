<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("inc/common.inc.php");
require_once("inc/securityhelper.inc.php");
require_once("obj/MessageGroup.obj.php");
require_once("obj/AudioFile.obj.php");
require_once("obj/FieldMap.obj.php");
require_once("obj/SurveyQuestionnaire.obj.php");
require_once("obj/SurveyQuestion.obj.php");
require_once("obj/Publish.obj.php");
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

///////////////////////////////////////////////////////////////////////////////
// Request processing:
///////////////////////////////////////////////////////////////////////////////

if (isset($_GET['delete'])) {
	$deleteid = $_GET['delete'];
	if (isset($_SESSION['messagegroupid']) && ($_SESSION['messagegroupid']== $deleteid))
		$_SESSION['messagegroupid'] = NULL;
	if (userOwns("messagegroup",$deleteid)) {
		$message = new MessageGroup($deleteid);
		Query("BEGIN");
		QuickUpdate("update messagegroup set deleted=1 where id=?",false,array($deleteid));
		QuickUpdate("update message set deleted=1 where messagegroupid=?",false,array($deleteid));
		QuickUpdate("delete from publish where type = 'messagegroup' and messagegroupid = ?", false, array($deleteid));
		Query("COMMIT");
		notice(_L("The message, %s, is now deleted.", escapehtml($message->name)));
		redirect();
	} else {
		notice(_L("You do not have permission to delete this message."));
	}
}

$isajax = isset($_GET['ajax']);

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////
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
			$orderby = "digitsfirst, name";
			break;
	}
	
	// get all the message group ids for this page
	$msgGroupIds = QuickQueryList(
		"(select SQL_CALC_FOUND_ROWS mg.id as id
		from messagegroup mg
		where mg.userid = ? 
			and mg.type = 'notification'
			and not mg.deleted)
		UNION
		(select mg.id as id
		from publish p
		inner join messagegroup mg on
			(p.messagegroupid = mg.id)
		where p.userid = ?
			and p.action = 'subscribe'
			and p.type = 'messagegroup'
			and not mg.deleted)
		limit $start, $limit", false, false, array($USER->id, $USER->id));

  	// total rows
	$total = QuickQuery("select FOUND_ROWS()");
	
	// get all the message group display data needed for this page
	if ($total) {
		$mergeditems = QuickQueryMultiRow(
			"select 'message' as type,'Saved' as status, 
				mg.id as id, mg.name as name,mg.description, mg.modified as date, mg.deleted as deleted,
				sum(m.type='phone') as phone, sum(m.type='email') as email, sum(m.type='sms') as sms,
				p.action as publishaction, p.id as publishid, u.login as owner, (mg.name +0) as digitsfirst
			from messagegroup mg
			left join message m on
				(m.messagegroupid = mg.id)
			join user u on
				(mg.userid = u.id)
			left join publish p on
				(p.userid = ? and p.messagegroupid = m.messagegroupid)
			where mg.id in (". implode(",", $msgGroupIds) .")
			group by mg.id
			order by $orderby, mg.id",
			true, false, array($USER->id));
	} else {
		$mergeditems = array();
	}
	
	$numpages = ceil($total/$limit);
	$curpage = ceil($start/$limit) + 1;
	$displayend = ($start + $limit) > $total ? $total : ($start + $limit);
	$displaystart = ($total) ? $start +1 : 0;

	if(empty($mergeditems)) {
			$data->list[] = array("itemid" => "",
										"defaultlink" => "",
										"icon" => "img/largeicons/information.jpg",
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
			$content = '<a href="' . $defaultlink . '" >' . $time .  ($item["description"] != ""?" - " . escapehtml($item["description"]):"") . ' - <b>' .  _L('%1$s Content',typestring(substr($types,1))). '</b>' . '</a>';
			$publishaction = $item['publishaction'];
			$publishid = $item['publishid'];
			
			$icon = 'img/largeicons/letter.jpg';
			
			// give the user some text
			$publishmessage = '';
			if ($publishaction == 'publish')
				$publishmessage = _L('Changes to this message are published.');
			
			// tell the user it's a subscription. change the href to view instead of edit
			if ($publishaction == 'subscribe') {
				$publishmessage = _L('You are subscribed to this message. Owner: (%s)', $item['owner']);
				$defaultlink = "messagegroupview.php?id=$itemid";
			}
			
			// Users with published messages or subscribed messages will get a special action item
			$publishactionlink = "";
			if ($USER->authorize("publish") && userCanPublish('messagegroup')) {
				// this message is published, else allow it to be
				if ($publishaction == 'publish')
					$publishactionlink = action_link(_L("Modify Publication"), "fugue/star__pencil", "publisheditorwiz.php?id=$itemid&type=messagegroup");
				else
					$publishactionlink = action_link(_L("Publish"), "fugue/star__plus", "publisheditorwiz.php?id=$itemid&type=messagegroup");
			}
			if ($USER->authorize("subscribe") && userCanSubscribe('messagegroup')) {
				// this message is subscribed to
				if ($publishaction == 'subscribe')
					$publishactionlink = action_link("Un-Subscribe", "fugue/star__minus", "messages.php?publishid=$publishid&remove");
			}
			
			// if the user is only subscribed to this message group, they can't edit, delete
			if ($publishaction == 'subscribe')
				$tools = action_links (
					action_link("Preview", "fugue/control", 'messagegroupview.php?id=' . $itemid),
					$publishactionlink);
			else
				$tools = action_links (
					action_link("Edit", "pencil", 'messagegroup.php?id=' . $itemid),
					$publishactionlink,
					action_link("Delete", "cross", 'messages.php?delete=' . $itemid, "return confirmDelete();")
				);


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
			<?=(($USER->authorize('subscribe') && userCanSubscribe('messagegroup'))?icon_button(_L('Subscribe to Message'),"add", "document.location='messagegroupsubscribe.php'"):'') ?>
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
							<?= _L("Loading Messages") ?></a>
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
	new Ajax.Request('messages.php', {
		method:'get',
		parameters:{ajax:true,filter:filter,pagestart:activepage},
		onSuccess: function (response) {
			var result = response.responseJSON;
			if(result) {
				$('feeditems').update(new Element('tbody'));
				var size = result.list.length;

				for(var i=0;i<size;i++){
					var item = result.list[i];
					var msg = new Element('tr');

					// insert icon
					msg.insert(
							new Element('td', {'valign': 'top', width: '60px'}).insert(
								new Element('a', {'href': item.defaultlink}).insert(
									new Element('img', {'src': item.icon})
								)
							)
						);
					// insert title and content details
					msg.insert(
							new Element('td').insert(
								new Element('div', {'class': 'feedtitle'}).insert(
									new Element('a', {'href': item.defaultlink}).insert(
										item.title
									)
								)
							).insert(
								((item.publishmessage)?
									new Element('div', {'class': 'feedsubtitle'}).insert(
										new Element('a', {'href': item.defaultlink}).insert(
											new Element('img', {'src': 'img/icons/diagona/10/031.gif'})
										).insert(
											item.publishmessage
										)
									):
									''
								)
							).insert(
								new Element('div', {'style': 'clear: both'})
							).insert(
								new Element('div').insert(
									item.content
								)
							)
						);
					// insert tools (if there are any)
					if (item.tools) {
						msg.insert(
							new Element('td', {'valign': 'middle', 'width': '100px'}).insert(
								new Element('div').insert(
									item.tools
								)
							)
						);
					}
					
					$('feeditems').down('tbody').insert(msg);
				}
				
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