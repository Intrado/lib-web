<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("inc/common.inc.php");
require_once("inc/securityhelper.inc.php");
require_once("obj/MessageGroup.obj.php");
require_once("obj/Message.obj.php");
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
if (!getSystemSetting("_hasfeed") || !$USER->authorize(array('feedpost'))) {
	redirect('unauthorized.php');
}

///////////////////////////////////////////////////////////////////////////////
// Request processing:
///////////////////////////////////////////////////////////////////////////////
$isajax = isset($_GET['ajax']);

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////
if($isajax === true) {
	session_write_close();//WARNING: we don't keep a lock on the session file, any changes to session data are ignored past this point

	$start = 0 + (isset($_GET['pagestart']) ? $_GET['pagestart'] : 0);
	$limit = 100;
	$orderby = "date desc";

	$filter = "";
	if (isset($_GET['filter'])) {
		$filter = $_GET['filter'];
	}
	switch ($filter) {
		case "name":
			$orderby = "digitsfirst, name";
			break;
	}
	
	// get all the job info for jobs which contain feed post data
	$jobids = QuickQueryList(
		"select SQL_CALC_FOUND_ROWS id
		from job j
		inner join jobpost jp on (j.id = jp.jobid)
		where jp.posted and jp.type in ('feed','page')
		and j.userid = ?
		group by jobid",
		false, false, array($USER->id));
	
	// total rows
	$total = QuickQuery("select FOUND_ROWS()");
	
	// get all the job data
	if ($total) {
		$postdata = QuickQueryMultiRow(
			"select j.id as jobid, j.messagegroupid as messagegroupid, jpf.destination as feeddestination,
				jpp.destination as pagedestination, j.activedate as date, j.name as name, j.description as description,
				(j.name +0) as digitsfirst
			from job j
			left join jobpost jpf on (jpf.jobid = j.id and jpf.type = 'feed')
			left join jobpost jpp on (jpp.jobid = j.id and jpp.type = 'page')
			where j.id in (".implode(",", $jobids).")
			order by $orderby, j.id",
			true, false, array($USER->id));
	} else {
		$postdata = array();
	}
	
	$numpages = ceil($total/$limit);
	$curpage = ceil($start/$limit) + 1;
	$displayend = ($start + $limit) > $total ? $total : ($start + $limit);
	$displaystart = ($total) ? $start +1 : 0;

	if(empty($postdata)) {
		$data->list[] = array(
			"icon" => "img/largeicons/globe.jpg",
			"title" => _L("No Posts."),
			"details" => "",
			"defaultlink" => "",
			"content" => "",
			"tools" => "");
	} else {
		foreach ($postdata as $post) {
			$mgid = $post["messagegroupid"];
			$time = date("M j, Y g:i a",strtotime($post["date"]));
			$title = escapehtml($post["name"]);
			
			$icon = 'img/largeicons/globe.jpg';
			
			// if the user owns this message group, they can edit, delete
			$actions = array();
			$messagegroup = new MessageGroup($post['messagegroupid']);
			if (userOwns("messagegroup", $messagegroup->id)) {
				if ($messagegroup->hasMessage("post","page"))
					$actions[] = action_link("Page", "layout_sidebar", 'editmessagepage.php?postedit&id=' . $messagegroup->getMessage("post", "page", "en")->id);
				if ($messagegroup->hasMessage("post","voice"))
					$actions[] = action_link("Media", "../nifty_play", 'editmessagepostvoice.php?postedit&id=' . $messagegroup->getMessage("post", "voice", "en")->id);
				if ($messagegroup->hasMessage("post","feed"))
					$actions[] = action_link("Feed", "rss", 'editmessagefeed.php?postedit&id=' . $messagegroup->getMessage("post", "feed", "en")->id);
			} else {
				$actions[] = action_link("View", "fugue/magnifier", 'messagegroupview.php?id=' . $mgid);
			}
			if ($messagegroup->hasMessage("post","feed")) {
				$actions[] = action_link("Post Categories", "pencil", 'editjobfeedcategory.php?postedit&id=' . $post['jobid']);
			}
			$tools = action_links ($actions);
			
			// get the job post feed categories
			$categoryids = explode(",", $post["feeddestination"]);
			if (count($categoryids)) {
				$categorynames = QuickQueryList("select name from feedcategory where id in (".repeatWithSeparator("?",",",count($categoryids)).")", false, false, $categoryids);
			} else {
				$categorynames = array();
			}
			$categories = implode(", ", $categorynames);
			
			$defaultlink = "job.php?id=".$post["jobid"];
			$content = '<a href="' . $defaultlink . '" >' . $time .  ($post["description"] != ""?" - " . escapehtml($post["description"]):"") . '</a>';
			
			$data->list[] = array(
				"icon" => $icon,
				"title" => $title,
				"details" => $categories,
				"defaultlink" => $defaultlink,
				"content" => $content,
				"tools" => $tools
			);

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

$PAGE = "notifications:post";
$TITLE = "Posted Content";

include_once("nav.inc.php");

startWindow(_L('My Posts'));

?>
<table width="100%" style="padding-top: 7px;">
<tr>
	<td style="width: 180px;vertical-align: top;font-size: 12px;" >
		<div class="feedbuttoncontainer">
			<?= icon_button(_L('Generate Feed URL/Widget'),"add","location.href='feedurlwiz.php?new'") ?>
			<div style="clear:both;"></div>
		</div>
		<div style="clear:both;"></div>
		<br />
		<div class="feed">
			<h1 id="filterby">Sort By:</h1>
			<div id="allfilters" class="feedfilter">
				<a id="namefilter" href="#" onclick="applyfilter('name'); return false;"><img src="img/largeicons/tiny20x20/pencil.jpg" />Name</a><br />
				<a id="datefilter" href="#" onclick="applyfilter('date'); return false;"><img src="img/largeicons/tiny20x20/clock.jpg" />Modify Date</a><br />
			</div>
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
						<?= _L("Loading Posts") ?></a>
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
var currentfilter = 'date';

function page(event) {
	activepage = event.element().value;
	applyfilter(currentfilter);
}

function applyfilter(filter) {
	new Ajax.Request('posts.php', {
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
								((item.details)?
									new Element('div', {'class': 'feedsubtitle'}).insert(
										new Element('a', {'href': item.defaultlink}).insert(
											item.details
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

				var selecttop = new Element('select', {id:'selecttop'});
				var selectbottom = new Element('select', {id:'selectbottom'});
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