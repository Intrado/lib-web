<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("inc/common.inc.php");

////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////

// everyone can see dashboard page

////////////////////////////////////////////////////////////////////////////////
// Action/Request Processing
////////////////////////////////////////////////////////////////////////////////

$useridList = array();
$useridList[] = $USER->id;
$start_datetime = "2012-05-01 00:00:00";
$end_datetime = "2012-05-31 23:59:59";

if (isset($_GET['deleteid'])) {
	//TODO get the userid list and date range for stats
}

////////////////////////////////////////////////////////////////////////////////
// Stats functions
////////////////////////////////////////////////////////////////////////////////

function generateStats($useridList, $start_datetime, $end_datetime) {
	// sql query parameters, always in same order for all stats
	$params = array();
	$params[] = $start_datetime;
	$params[] = $end_datetime;
	$params = array_merge($params, $useridList);
	
	$stats = array();
	
	// broadcasts
	$query = "select count(*) from job j " .
		"where j.status in ('procactive','active','complete','cancelled','cancelling') and " .
		"j.activedate >= ? and j.activedate <= ? and " .
		"j.userid in (" . repeatWithSeparator("?", ",", count($useridList)) . ")";
		
	$stats["total_jobs"] = QuickQuery($query, null, $params);

	// languages
	$query = "select count(distinct(m.languagecode)) from message m " .
		"join messagegroup mg on (mg.id = m.messagegroupid) " .
		"join job j on (j.messagegroupid = mg.id) " .
		"where j.status in ('procactive','active','complete','cancelled','cancelling') and " .
		"j.activedate >= ? and j.activedate <= ? and " .
		"j.userid in (" . repeatWithSeparator("?", ",", count($useridList)) . ")";
		
	$stats["total_languages"] = QuickQuery($query, null, $params);
	
	// total of each message type
	$query = "select m.type as type, count(*) as total from message m " .
		"join messagegroup mg on (mg.id = m.messagegroupid) " .
		"join job j on (j.messagegroupid = mg.id) " .
		"where  " .
		"j.status in ('procactive','active','complete','cancelled','cancelling') and " .
		"j.activedate >= ? and j.activedate <= ? and " .
		"j.userid in (" . repeatWithSeparator("?", ",", count($useridList)) . ") " .
		"group by m.type";
	
	// init to zero, in case none found in query
	$stats["total_phones"] = 0;
	$stats["total_emails"] = 0;
	$stats["total_sms"] = 0;
	$stats["total_posts"] = 0;
	
	$rows = QuickQueryMultiRow($query, true, null, $params);
	foreach ($rows as $row) {
		if (!strcmp("phone", $row['type']))
			$stats['total_phones'] = $row['total'];
		if (!strcmp("email", $row['type']))
			$stats['total_emails'] = $row['total'];
		if (!strcmp("sms", $row['type']))
			$stats['total_sms'] = $row['total'];
		if (!strcmp("post", $row['type']))
			$stats['total_posts'] = $row['total'];
	}
	
	
	// top jobtypes
	$query = "select jt.name as name, count(*) as total from job j " .
		"left join jobtype jt on (jt.id = j.jobtypeid) " .
		"where j.status in ('procactive','active','complete','cancelled','cancelling') and " .
		"j.activedate >= ? and j.activedate <= ? and " .
		"j.userid in (" . repeatWithSeparator("?", ",", count($useridList)) . ") " .
		"group by j.jobtypeid " .
		"order by total desc";
		
	$stats["top_jobtypes"] = QuickQueryMultiRow($query, true, null, $params);

	// top senders
	$query = "select concat_ws(' ', u.firstname, u.lastname) as name, count(*) as total from job j " .
		"left join user u on (u.id = j.userid) " .
		"where j.status in ('procactive','active','complete','cancelled','cancelling') and " .
		"j.activedate >= ? and j.activedate <= ? and " .
		"j.userid in (" . repeatWithSeparator("?", ",", count($useridList)) . ") " .
		"group by j.userid " .
		"order by total desc";
		
	$stats["top_users"] = QuickQueryMultiRow($query, true, null, $params);

	$stats["total_users"] = count($stats["top_users"]);

	error_log("STATS CALC"); // TODO remove
	return $stats;
}

////////////////////////////////////////////////////////////////////////////////
// Data
////////////////////////////////////////////////////////////////////////////////

// sql explained key useraccess
$query = "select max(j.activedate) from job j " .
	"where j.userid in (" . repeatWithSeparator("?", ",", count($useridList)) . ")";
	
$expect = QuickQuery($query, null, $useridList);
// keep one day, key generated
$stats = gen2cache(60*60*24, $expect, null, "generateStats", $useridList, $start_datetime, $end_datetime);



////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$PAGE = "start:start";
$TITLE = _L("Welcome");

include("nav.inc.php");
?>

	<div class="wrapper">
	
	<div class="main_activity">
		<div class="users cf">
			<p>Show activity for
			<select>
				<option value="">Everyone</option>
				<option value="">Me</option>
			</select></p>
		</div>
		
		<div class="window summary">
			<div class="window_title_wrap">
			<h2>Activity Summary</h2>
			<div class="btngroup">
				<button class="active">7 Days</button>
				<button>Month</button>
				<button>Year</button>
			</div>
			</div>
			
			<div class="window_body_wrap cf">
			<div class="col">
				<h4>Broadcasts</h4>
				<p><strong><?=$stats["total_jobs"]?></strong></p>
				<p><?=$stats["total_languages"]?> Languages</p>
				<p><?=$stats["total_users"]?> Senders</p>
			</div>
			
			<div class="col bloc">
				<h4>Message Content</h4>
				<ul>
				<li><img src="themes/newui/phone-blue.png"/><?=$stats["total_phones"]?></li>
				<li><img src="themes/newui/email-red.png"/><?=$stats["total_emails"]?></li>
				<li><img src="themes/newui/sms-orange.png"/><?=$stats["total_sms"]?></li>
				<li><img src="themes/newui/social-green.png"/><?=$stats["total_posts"]?></li>
				</ul>
			</div>
			
			<div class="col bloc">
				<h4>Top Types</h4>
				<ul>
<?
				for ($i = 0; $i < 4; $i++) {
					if (!isset($stats['top_jobtypes'][$i]))
						break;
?>
					<li><span><?=$stats["top_jobtypes"][$i]['total']?></span><?=$stats["top_jobtypes"][$i]['name']?></li>
<?
				}
?>
				</ul>
			</div>
			
			<div class="col bloc">
				<h4>Top Senders</h4>
				<ul>
<?
				for ($i = 0; $i < 4; $i++) {
					if (!isset($stats['top_users'][$i]))
						break;
?>
					<li><span><?=$stats["top_users"][$i]['total']?></span><?=$stats["top_users"][$i]['name']?></li>
<?
				}
?>
				</ul>
			</div>
			</div><!-- /window_body_wrap -->
		</div>
		
		<div class="window broadcasts">
			<div class="window_title_wrap"><h2>Broadcasts</h2></div>
			
			<div class="window_body_wrap">
			<h3>In Progress <span>(Sending Now)</span></h3>
			<table class="info">
				<thead>
					<tr>
					<th>Status</th>
					<th>Author</th>
					<th>Subject</th>
					<th>Rcpts.</th>
					<th>Content</th>
					</tr>
				</thead>
				<tbody>
					<tr>
					<td>Status</td>
					<td>Gary Baldi</td>
					<td>Harvest Festival Bake Sale</td>
					<td>18659</td>
					<td><img src="themes/newui/phone-grey.png"/> <img src="themes/newui/email-grey.png"/> <img src="themes/newui/sms-grey.png"/> <img src="themes/newui/social-grey.png"/></td>
					</tr>
				</tbody>
			</table>
			
			<h3>On Deck <span>(Sending Soon)</span></h3>
			<table class="info">
				<thead>
					<tr>
					<th>Scheduled For</th>
					<th>Author</th>
					<th>Subject</th>
					<th>Rcpts.</th>
					<th>Content</th>
					</tr>
				</thead>
				<tbody>
					<tr>
					<td>Monday, 28/5/12</td>
					<td>Davey Jones</td>
					<td>Queens Jubilee Celebration</td>
					<td>3761</td>
					<td><img src="themes/newui/phone-grey.png"/> <img src="themes/newui/email-grey.png"/> <img src="themes/newui/social-grey.png"/></td>
					</tr>

					<tr>
					<td>Friday, 1/6/12</td>
					<td>Gary Baldi</td>
					<td>July 4th Fundraiser</td>
					<td>3761</td>
					<td><img src="themes/newui/email-grey.png"/> <img src="themes/newui/sms-grey.png"/> <img src="themes/newui/social-grey.png"/></td>
					</tr>
				</tbody>
			</table>
			
			<h3>Completed <span>(Already Sent)</span></h3>
			<table class="info">
				<thead>
					<tr>
					<th>Sent On</th>
					<th>Author</th>
					<th>Subject</th>
					<th>Rcpts.</th>
					<th>Content</th>
					</tr>
				</thead>
				<tbody>
					<tr>
					<td>Monday, 7/6/12</td>
					<td>Gary Baldi</td>
					<td>Chinese Takeaway Day</td>
					<td>1800</td>
					<td><img src="themes/newui/phone-grey.png"/> <img src="themes/newui/email-grey.png"/> <img src="themes/newui/sms-grey.png"/> <img src="themes/newui/social-grey.png"/></td>
					</tr>
					
					<tr>
					<td>Thursday, 12/5/12</td>
					<td>Davey Jones</td>
					<td>May Day Bank Holiday</td>
					<td>1800</td>
					<td><img src="themes/newui/phone-grey.png"/> <img src="themes/newui/email-grey.png"/> <img src="themes/newui/sms-grey.png"/></td>
					</tr>
					
					<tr>
					<td>Friday, 6/4/12</td>
					<td>Robyn Banks</td>
					<td>April Bank Holiday dates</td>
					<td>1800</td>
					<td><img src="themes/newui/phone-grey.png"/> <img src="themes/newui/email-grey.png"/> <img src="themes/newui/sms-grey.png"/></td>
					</tr>
				</tbody>
			</table>
			</div><!-- /window_body_wrap -->
		</div>
	</div><!-- end main_activity -->
	
	
	<div class="main_aside">
		<a class="bigbtn" href="message_sender.php"><span>New Broadcast</span></a>
	
		<div class="templates">
			<h3>Broadcast Templates</h3>
			<ul>
			<li><a href="">General Announcement</a></li>
			<li><a href="">Snow Delay</a></li>
			<li><a href="">School Closure</a></li>
			</ul>
		</div>
	
		<div class="help">
			<h3>Need Help?</h3>
			<p>Visit the <a href="">help section</a> or call (800) 920-3897. Also be sure to <a href="">give us feedback</a> about the new version.</p>
		</div>
	</div><!-- end main_aside -->
	
	</div><!-- end wrapper -->

<?
include("navbottom.inc.php");
?>
