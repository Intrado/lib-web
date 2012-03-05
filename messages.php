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
if (!$USER->authorize(array('sendemail', 'sendphone', 'sendsms'))) {
	redirect('unauthorized.php');
}

///////////////////////////////////////////////////////////////////////////////
// Request processing:
///////////////////////////////////////////////////////////////////////////////

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

if (isset($_GET['delete'])) {
	$deleteid = $_GET['delete'];
	if (isset($_SESSION['messagegroupid']) && ($_SESSION['messagegroupid']== $deleteid))
		$_SESSION['messagegroupid'] = NULL;

	$message = new MessageGroup($deleteid);
	if (userOwns("messagegroup",$deleteid) && $message->type == 'notification') {
		Query("BEGIN");
		QuickUpdate("update messagegroup set deleted=1 where id=?",false,array($deleteid));
		QuickUpdate("delete from publish where type = 'messagegroup' and messagegroupid = ?", false, array($deleteid));
		notice(_L("The message, %s, is now deleted.", escapehtml($message->name)));
		// if there are any publish records for this messagegroup, remove them
		if (isPublished('messagegroup', $message->id)) {
			$publications = DBFindMany("Publish", "from publish where type = 'messagegroup' and messagegroupid = ?", false, array($message->id));
			foreach ($publications as $publish)
				$publish->destroy();
			notice(_L("The message, %s, is now un-published. Any subscriptions were also removed.", escapehtml($message->name)));
		}
		Query("COMMIT");
	} else {
		notice(_L("You do not have permission to delete this message."));
	}
	
	redirect();
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
		"(select SQL_CALC_FOUND_ROWS mg.id as id,modified, (mg.name +0) as digitsfirst,name
		from messagegroup mg
		where mg.userid = ? 
			and mg.type = 'notification'
			and not mg.deleted)
		UNION
		(select mg.id as id,modified, (mg.name +0) as digitsfirst,name
		from publish p
		inner join messagegroup mg on
			(p.messagegroupid = mg.id)
		where p.userid = ?
			and p.action = 'subscribe'
			and p.type = 'messagegroup'
			and not mg.deleted)
		order by $orderby, id
		limit $start, $limit", false, false, array($USER->id, $USER->id));

  	// total rows
	$total = QuickQuery("select FOUND_ROWS()");
	
	// get all the message group display data needed for this page
	if ($total) {
		$mergeditems = QuickQueryMultiRow(
			"select 'message' as type,'Saved' as status, 
				mg.id as id, mg.name as name,mg.description, mg.modified as date, mg.deleted as deleted,
				sum(m.type='phone') as phone,
				sum(m.type='email') as email, 
				sum(m.type='sms') as sms, 
				sum(m.type='post' and m.subtype='facebook') as facebook, 
				sum(m.type='post' and m.subtype='twitter') as twitter,
				sum(m.type='post' and m.subtype='feed') as feed,
				sum(m.type='post' and m.subtype='page') as page,
				sum(m.type='post' and m.subtype='voice') as pagemedia,
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
		// get user associated orgs
		$authorizedorgs = Organization::getAuthorizedOrgKeys();
		
		while(!empty($mergeditems)) {
			$item = array_shift($mergeditems);
			$time = date("M j, Y g:i a",strtotime($item["date"]));
			$itemid = $item["id"];
			$defaultlink = "mgeditor.php?id=$itemid";

			// give the user some text
			$publishmessage = '';
			$publishaction = $item['publishaction'];
			if ($publishaction == 'publish')
			$publishmessage = _L('Changes to this message are published.');
				
			// tell the user it's a subscription. change the href to view instead of edit
			if ($publishaction == 'subscribe') {
				$publishmessage = _L('You are subscribed to this message. Owner: (%s)', $item['owner']);
				$defaultlink = "messagegroupview.php?id=$itemid";
			}
			
			$types = $item["phone"] > 0?'<a href="' . $defaultlink . '&redirect=phone"><img src="img/icons/telephone.png" alt="Phone" title="Phone"></a>':"";
			$types .= $item["email"] > 0?' <a href="' . $defaultlink . '&redirect=email"><img src="img/icons/email.png" alt="Email" title="Email"></a>':"";
			$types .= $item["sms"] > 0?' <a href="' . $defaultlink . '&redirect=sms"><img src="img/icons/fugue/mobile_phone.png" alt="SMS" title="SMS"></a>':"";
			$types .= $item["facebook"] > 0?' <a href="' . $defaultlink . '&redirect=facebook"><img src="img/icons/custom/facebook.png" alt="Facebook" title="Facebook"></a>':"";
			$types .= $item["twitter"] > 0?' <a href="' . $defaultlink . '&redirect=twitter"><img src="img/icons/custom/twitter.png" alt="Twitter" title="Twitter"></a>':"";
			$types .= $item["feed"] > 0?' <a href="' . $defaultlink . '&redirect=feed"><img src="img/icons/rss.png" alt="Feed" title="Feed"></a>':"";
			$types .= $item["page"] > 0?' <a href="' . $defaultlink . '&redirect=page"><img src="img/icons/layout_sidebar.png" alt="Page" title="Page"></a>':"";
			$types .= $item["pagemedia"] > 0?' <a href="' . $defaultlink . '&redirect=voice"><img src="img/nifty_play.png" alt="Page Media" title="Page Media"></a>':"";
			$title = escapehtml($item["name"]);
			$publishid = $item['publishid'];
			$icon = 'img/largeicons/letter.jpg';
			
			// Users with published messages or subscribed messages will get a special action item
			$publishactionlink = "";
			switch ($publishaction) {
				case 'publish':
					// if the user has published this message groups and they are authorized for atleast one org (or the customer has no orgs)
					if ($USER->authorize("publish") && userCanPublish('messagegroup') && ($authorizedorgs || !Organization::custHasOrgs()))
						$publishactionlink = action_link(_L("Modify Publication"), "fugue/star__pencil", "publisheditorwiz.php?id=$itemid&type=messagegroup");
					break;
				case 'subscribe':
					// this message is subscribed to, allow unsubscribe always!
					$publishactionlink = action_link("Un-Subscribe", "fugue/star__minus", "messages.php?id=$publishid&remove");
					break;
				default:
					// if the user can publish message groups and they are authorized for atleast one org (or the customer has no orgs)
					if ($USER->authorize("publish") && userCanPublish('messagegroup') && ($authorizedorgs || !Organization::custHasOrgs()))
						$publishactionlink = action_link(_L("Publish"), "fugue/star__plus", "publisheditorwiz.php?id=$itemid&type=messagegroup");
			}
			
			// if the user owns this message group, they can edit, delete
			if (userOwns("messagegroup", $itemid)) {
				$tools = action_links (
					action_link("Edit", "pencil", 'mgeditor.php?id=' . $itemid),
					$publishactionlink,
					action_link("Delete", "cross", 'messages.php?delete=' . $itemid, "return confirmDelete();")
				);
			} else {
				$tools = action_links (
					action_link("View", "fugue/magnifier", 'messagegroupview.php?id=' . $itemid),
					$publishactionlink);
			}

			$content = '<a href="' . $defaultlink . '" >' . $time .  ($item["description"] != ""?" - " . escapehtml($item["description"]):"") . ' - <b>' . ($types==""?_L("Empty Message"):$types) . '</b>' . '</a>';
			
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

startWindow(_L('My Messages'), 'padding: 3px;', false, true);

?>

	<div class="feed_btn_wrap cf">
	<?= icon_button(_L('Create a Message'),"add","location.href='mgeditor.php?id=new'") ?>
	
	<?=(($USER->authorize('subscribe') && userCanSubscribe('messagegroup'))?icon_button(_L('Subscribe to a Message'),"fugue/star", "document.location='messagegroupsubscribe.php'"):'') ?>
	</div>


<div class="csec window_aside">
		<h3 id="filterby">Sort By:</h3>
		<ul id="allfilters" class="feedfilter">
			<li><a id="namefilter" href="#" onclick="applyfilter('name'); return false;"><img src="img/largeicons/tiny20x20/pencil.jpg" />Name</a></li>
			<li><a id="datefilter" href="#" onclick="applyfilter('date'); return false;"><img src="img/largeicons/tiny20x20/clock.jpg" />Modify Date</a></li>
		</ul>
</div><!-- .cesc .window_aside -->
	

<div class="csec window_main">
	
	<div id="pagewrappertop" class="content_recordcount_top"></div>

	<table id="feeditems" class="content_feed">
			<tbody>
				<tr>
					<td class=""><img src='img/ajax-loader.gif' /></td>
					<td>
						<div class='feedtitle'>
							<a href=''><?//= _L("Loading Lists") ?></a>
						</div>
					</td>
				</tr>
			</tbody>
		</table>
	<div id="pagewrapperbottom" class="content_recordcount_btm"></div>
	
</div><!-- .cesc .window_main -->


<script type="text/javascript">
var filtes = Array('date','name');
var activepage = 0;
var currentfilter = 'date';

function page(event) {
	activepage = event.element().value;
	applyfilter(currentfilter);
}

function applyfilter(filter) {
	new Ajax.Request('messages.php', {
		method:'get',
		parameters:{ajax:true,filter:filter,pagestart:activepage},
		onSuccess: function (response) {
			var result = response.responseJSON;
			if(result) {
				$('feeditems').update(new Element('div', {'class': 'content_feed cf'}));
				var size = result.list.length;

				for(var i=0;i<size;i++){
					var item = result.list[i];
					var msg = new Element('div', {'class': 'feed_item cf'});

					// insert icon
					msg.insert(
								new Element('a', {'class': 'msg_icon', 'href': item.defaultlink}).insert(
									new Element('img', {'src': item.icon})
								)
						);

					var feedWrap = new Element('div', {'class': 'feed_wrap'});

					
					// insert title and content details
					feedWrap.insert(
						new Element('a', {'class': 'feed_title', 'href': item.defaultlink}).insert(
							item.title
							)
						);

					feedWrap.insert(
							new Element('div', {'class': 'feed_detail'}).insert(
								item.content
							)
						);

					feedWrap.insert(
							((item.publishmessage)?
									new Element('a', {'class': 'feed_subtitle', 'href': item.defaultlink}).insert(
										new Element('img', {'src': 'img/icons/diagona/10/031.gif'})
									).insert(
										item.publishmessage
									):
								''
							)
						);

					msg.insert(feedWrap);
					

					// insert tools (if there are any)
					if (item.tools) {
						msg.insert(
								item.tools
						);
					}
					
					$('feeditems').down('div').insert(msg);
				}
				
				var pagetop = new Element('div',{'class':'content_recordcount'}).update(result.pageinfo[3]);
				var pagebottom = new Element('div',{'class':'content_recordcount'}).update(result.pageinfo[3]);

				var selecttop = new Element('select', {'id':'selecttop'});
				var selectbottom = new Element('select', {'id':'selectbottom'});
				for (var x = 0; x < result.pageinfo[0]; x++) {
					var offset = x * result.pageinfo[1];
					var selected = (result.pageinfo[2] == x+1);
					selecttop.insert(new Element('option', {'value': offset,selected:selected}).update('Page ' + (x+1)));
					selectbottom.insert(new Element('option', {'value': offset,selected:selected}).update('Page ' + (x+1)));
				}
				pagetop.insert(selecttop);
				pagebottom.insert(selectbottom);
				$('pagewrappertop').update(pagetop);
				$('pagewrapperbottom').update(pagebottom);

				currentfilter = filter
				$('selecttop').observe('change',page);
				$('selectbottom').observe('change',page);

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
	applyfilter('name');
});
</script>
<?
endWindow();
include_once("navbottom.inc.php");
?>