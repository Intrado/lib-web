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

$filter = "";
if (isset($_GET['filter'])) {
	$filter = $_GET['filter'];
}
//$start = 0 + (isset($_GET['pagestart']) ? $_GET['pagestart'] : 0);
//$limit = 20;

$isajax = isset($_GET['ajax']);

$mergeditems = array();
if($isajax === true) {
	$start = 0 + (isset($_GET['pagestart']) ? $_GET['pagestart'] : 0);
	$limit = 5;
	$orderby = "modified";
	session_write_close();//WARNING: we don't keep a lock on the session file, any changes to session data are ignored past this point

	switch ($filter) {
		case "name":
			$orderby = "name";
			break;
	}
	$mergeditems = QuickQueryMultiRow("
			select 'message' as type,'Saved' as status,g.id as id, g.name as name, g.modified as date, g.deleted as deleted,
			 sum(type='phone') as phone, sum(type='email') as email,sum(type='sms') as sms
			from messagegroup g, message m where g.userid=? and g.deleted = 0 and g.modified is not null and m.messagegroupid = g.id
			group by g.id,m.languagecode order by g.modified desc limit $start, $limit",true,false,array($USER->id));


	$total = QuickQuery("select FOUND_ROWS()");
	
	$numpages = ceil($total/$limit);
	$curpage = ceil($start/$limit) + 1;
	$displayend = ($start + $limit) > $total ? $total : ($start + $limit);
	$displaystart = ($total) ? $start +1 : 0;


	//$total = QuickQuery("select FOUND_ROWS()");
	header('Content-Type: application/json');
	$data->list = activityfeed($mergeditems,true);
	$data->pageinfo = array($numpages,$limit,$curpage, "Showing $displaystart - $displayend of $total records on $numpages pages ");

	echo json_encode(!empty($data) ? $data : false);
	exit();
}

/*
//preload audiofile information to determine simple/advanced phone messages
//save messageid => audiofileid
$query = "select m.id, mp.audiofileid, count(*) as cnt, mp.type
from message m inner join messagepart mp on (m.id=mp.messageid)
where m.type='phone' and m.userid=" . $USER->id . " and m.deleted=0
group by m.id
having cnt = 1 and mp.type='A' ";
$SIMPLEPHONEMESSAGES = QuickQueryList($query,true);
*/

////////////////////////////////////////////////////////////////////////////////
// Display Functions
////////////////////////////////////////////////////////////////////////////////

function fmt_actions ($obj,$name) {
	global $SIMPLEPHONEMESSAGES;
/*
	$advancedplaybtn = button("Play", "popup('previewmessage.php?close=1&id=$obj->id', 400, 500);");
	$editbtn = '<a href="message' . $obj->type . '.php?id=' . $obj->id . '">Edit</a>';
	$deletebtn = '<a href="messages.php?delete=' . $obj->id . '" onclick="return confirmDelete();">Delete</a>';
	$renamebtn = '<a href="messagerename.php?id=' . $obj->id . '">Rename</a>';

	if ($obj->type == "phone" && isset($SIMPLEPHONEMESSAGES[$obj->id])) {
		return  "$advancedplaybtn&nbsp;|&nbsp;$renamebtn&nbsp;|&nbsp;$deletebtn";
	} else {
		if ($obj->type == "phone") {
			return "$advancedplaybtn&nbsp;|&nbsp;$editbtn&nbsp;|&nbsp;$renamebtn&nbsp;|&nbsp;$deletebtn";
		} else {
			return "$editbtn&nbsp;|&nbsp;$renamebtn&nbsp;|&nbsp;$deletebtn";
		}
	}
*/

	$advancedplaybtn = action_link("Play","diagona/16/131",null,"popup('previewmessage.php?close=1&id=$obj->id', 400, 500,'preview'); return false;");
	$editbtn = action_link("Edit", "pencil", 'message' . $obj->type . '.php?id=' . $obj->id);
	$deletebtn = action_link("Delete", "cross", 'messages.php?delete=' . $obj->id, "return confirmDelete();");
	$renamebtn = action_link("Rename", "textfield_rename", 'messagerename.php?id=' . $obj->id);

	if ($obj->type == "phone" && isset($SIMPLEPHONEMESSAGES[$obj->id])) {
		return  action_links($advancedplaybtn,$renamebtn,$deletebtn);
	} else {
		if ($obj->type == "phone") {
			return action_links($advancedplaybtn,$editbtn,$renamebtn,$deletebtn);
		} else {
			return action_links($editbtn,$renamebtn,$deletebtn);
		}
	}

}

function fmt_phonetype ($obj,$name) {
	global $SIMPLEPHONEMESSAGES;
	if (isset($SIMPLEPHONEMESSAGES[$obj->id])) {
		return "Simple";
	} else {
		return "Advanced";
	}
}

function fmt_creator ($obj,$name) {
	$creator = DBFind("User","from user where id=$obj->userid");
	return $creator->shortName();
}

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

function activityfeed($mergeditems,$ajax = false) {
	$actioncount = 0;
	$activityfeed = $ajax===true?array():"";
	$limit = 10;

	if($ajax===true) {
		if(empty($mergeditems)) {
				$activityfeed[] = array("itemid" => "",
											"defaultlink" => "",
											"defaultonclick" => "",
											"icon" => "largeicons/information.jpg",
											"title" => _L("No Messages."),
											"content" => "",
											"tools" => "");
		} else {
			while(!empty($mergeditems) && $limit > 0) {
				$item = array_shift($mergeditems);
				$time = date("M j, g:i a",strtotime($item["date"]));
				$itemid = $item["id"];
				$defaultonclick = '';
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


				$activityfeed[] = array("itemid" => $itemid,
											"defaultlink" => $defaultlink,
											"defaultonclick" => $defaultonclick,
											"icon" => $icon,
											"title" => $title,
											"content" => $content,
											"tools" => $tools);

				$limit--;
			}
		}
	} else {
		$activityfeed .= '<tr>
									<td valign="top" width="60px"><img src="img/ajax-loader.gif" /></td>
									<td >
											<div class="feedtitle">
												<a href="">
												' . _L("Loading Lists") . '</a>
											</div>
									</td>
									</tr>';
		$activityfeed .= "
				<script>
				var actionids = $actioncount;

				var jobfiltes = Array('none','date','name');

				function applyfilter(filter) {
						new Ajax.Request('messages.php?ajax=true&filter=' + filter, {
							method:'get',
							onSuccess: function (response) {
								var result = response.responseJSON;
								if(result) {
									var html = '';
									var size = result.list.length;

									actionids = 0;
									for(i=0;i<size;i++){
										var item = result.list[i];
										html += '<tr><td valign=\"top\" width=\"60px\"><a href=\"' + item.defaultlink + '\" ' + item.defaultonclick + '><img src=\"img/' + item.icon + '\" /></a></td><td ><div class=\"feedtitle\"><a href=\"' + item.defaultlink + '\" ' + item.defaultonclick + '>' + item.title + '</a></div><span>' + item.content + '</span></td>';
										if(item.tools) {
											html += '<td valign=\"middle\" width=\"100px\"><div>' + item.tools + '</div></td>';
											actionids++;
										}
										html += '</tr>';
									}
									$('feeditems').update(html);

									var pageselect = $('pageselector').remove();

									pageselect.update(result.pageinfo[3]);
									var select = new Element('select', {onchange: 'alert(\'Not implemented yet\')'});
									for (var x = 0; x < result.pageinfo[0]; x++) {
										var offset = x * result.pageinfo[1];
										var selected = (result.pageinfo[2] == x+1) ? 'selected' : '';
										var opt = new Element('option', {value: 'offset'});
										opt.update('Page ' + (x+1));
										select.insert(opt);
									}

									pageselect.insert(select);

									$('pagewrapper').insert(pageselect);

									var filtercolor = $('filterby').getStyle('color');
									if(!filtercolor)
										filtercolor = '#000';

									size = jobfiltes.length;
									for(i=0;i<size;i++){
										$(jobfiltes[i] + 'filter').setStyle({color: filtercolor, fontWeight: 'normal'});
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
					applyfilter('none');
				});
				</script>";

	}
	return $activityfeed;
}



////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$PAGE = "notifications:messages";
$TITLE = "Message Builder";

include_once("nav.inc.php");

startWindow(_L('My Messages'), 'padding: 3px;', true, true);

$activityfeed = '
				<table width="100%" name="recentactivity" style="padding-top: 7px;">
				<tr>
					<td class="feed" style="width: 180px;vertical-align: top;font-size: 12px;" >
						<div>
						' .
						icon_button(_L('Create New Message'),"add","location.href='messagegroup.php?id=new'")
						 .' <div style="clear:both;"></div>
						</div>
						<br />
						<h1 id="filterby">Sort By:</h1>
						<div id="allfilters" class="feedfilter">
							<a id="listsfilter" href="lists.php?filter=date" onclick="applyfilter(\'date\'); return false;"><img src="img/largeicons/tiny20x20/clock.jpg">Modify Date</a><br />
							<a id="listsfilter" href="lists.php?filter=name" onclick="applyfilter(\'name\'); return false;"><img src="img/largeicons/tiny20x20/pencil.jpg">Name</a><br />
						</div>
					</td>
					<td width="10px" style="border-left: 1px dotted gray;" >&nbsp;</td>
					<td class="feed" valign="top" >
						<table id="feeditems">
				';

				$activityfeed .= activityfeed($mergeditems,false);
				$activityfeed .= '</table>
						<br />
						<div id="pagewrapper"><div id="pageselector"></div></div>
					</td>
				</tr>
			</table>';
			echo $activityfeed;
endWindow();

/*

$scrollThreshold = 8;

if($USER->authorize('sendphone')) {
	$data = DBFindMany("Message",", (name + 0) as foo from message where type='phone' and userid=$USER->id and deleted=0 order by foo, name");
	$scroll = false;
	if (count($data) > $scrollThreshold) {
		$scroll = true;
	}
	startWindow('My Phone Messages ' . help('Messages_MyPhoneMessages'), 'padding: 3px;', true, true);

	if ($USER->authorize('starteasy')) {
		button_bar(button('Call Me To Record', "document.location='callme.php?origin=messages'") . help('AudioFileEditor_CallMeToRecord'),
			button('Create Advanced Message', "document.location='messagephone.php?id=new'") . help('Messages_AddPhoneMessage'),
			button('Audio Library', "popup('audio.php',500,400);") . help('Messages_AudioFileEditor'));
	} else {
		button_bar(button('Create Advanced Message', "document.location='messagephone.php?id=new'") . help('Messages_AddPhoneMessage'),
			button('Audio Library', "popup('audio.php',500,400);") . help('Messages_AudioFileEditor'));
	}



	$phonetitles = array(	"name" => "#Name",
						"description" => "#Description",
						"Type" => "#Type",
						"Actions" => "Actions"
					);

	showObjects($data, $phonetitles, array("Type" => "fmt_phonetype", "Actions" => "fmt_actions", "userid" => "fmt_creator"), $scroll, true);
	endWindow();
	echo '<br>';
}


$titles = array(	"name" => "#Name",
					"description" => "#Description",
					"Actions" => "Actions"
					);


if($USER->authorize('sendemail')) {
	$data = DBFindMany("Message",", (name + 0) as foo from message where type='email' and userid=$USER->id and deleted=0 order by foo, name");
	$scroll = false;
	if (count($data) > $scrollThreshold) {
		$scroll = true;
	}
	startWindow('My Email Messages ' . help('Messages_MyEmailMessages'), 'padding: 3px;', true, true);

	button_bar(button('Create Email Message', NULL,'messageemail.php?id=new') . help('Messages_AddEmailMessage'));

	showObjects($data, $titles, array("Actions" => "fmt_actions", "userid" => "fmt_creator"), $scroll, true);
	endWindow();
	echo '<br>';
}

if(getSystemSetting('_hassms', false) && $USER->authorize('sendsms')) {
	$data = DBFindMany("Message",", (name + 0) as foo from message where type='sms' and userid=$USER->id and deleted=0 order by foo, name");
	$scroll = false;
	if (count($data) > $scrollThreshold) {
		$scroll = true;
	}
	startWindow('My SMS Messages ' . help('Messages_MySmsMessages'), 'padding: 3px;', true, true);

	button_bar(button('Create SMS Message', NULL,'messagesms.php?id=new') . help('Messages_AddSmsMessage'));

	showObjects($data, $titles, array("Actions" => "fmt_actions", "userid" => "fmt_creator"), $scroll, true);
	endWindow();
	echo '<br>';
}
*/

include_once("navbottom.inc.php");
?>