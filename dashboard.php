<?
error_reporting(E_ALL);
ini_set('display_errors', '1');

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

	// senders
	$query = "select count(distinct(j.userid)) from job j " .
		"where j.status in ('procactive','active','complete','cancelled','cancelling') and " .
		"j.activedate >= ? and j.activedate <= ? and " .
		"j.userid in (" . repeatWithSeparator("?", ",", count($useridList)) . ")";
		
	$stats["total_senders"] = QuickQuery($query, null, $params);
	
	// languages
	$query = "select count(distinct(m.languagecode)) from message m " .
		"join messagegroup mg on (mg.id = m.messagegroupid) " .
		"join job j on (j.messagegroupid = mg.id) " .
		"where j.status in ('procactive','active','complete','cancelled','cancelling') and " .
		"j.activedate >= ? and j.activedate <= ? and " .
		"j.userid in (" . repeatWithSeparator("?", ",", count($useridList)) . ")";
		
	$stats["total_languages"] = QuickQuery($query, null, $params);
	
	error_log("STATS CALC"); // TODO remove
	return $stats;
}

////////////////////////////////////////////////////////////////////////////////
// Data
////////////////////////////////////////////////////////////////////////////////

//memcache exptime is in seconds up to 30 days, then becomes a timestamp of a date to expire.
//since completed jobs don't change, we can cache it for a really long time.
//we could have used "QuickQueryRow" as a callback directly, however the key would contain the sql
//which would be too long, and the jobid (the important part) would be lost in the tail hash of automatic key generation
//wrapping the large, static argument in a function shortens key length and increases readability

$stats = gen2cache(time() + 60*60*24*365, null, null, "generateStats", $useridList, $start_datetime, $end_datetime);



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
				<p><?=$stats["total_senders"]?> Senders</p>
			</div>
			
			<div class="col bloc">
				<h4>Message Content</h4>
				<ul>
				<li><img src="themes/newui/phone-blue.png"/> 62</li>
				<li><img src="themes/newui/email-red.png"/> 22</li>
				<li><img src="themes/newui/sms-orange.png"/> 13</li>
				<li><img src="themes/newui/social-green.png"/> 3</li>
				</ul>
			</div>
			
			<div class="col bloc">
				<h4>Top Types</h4>
				<ul>
				<li><span>412</span> General Outre&hellip;</li>
				<li><span>365</span> Attendance</li>
				<li><span>91</span> Newsletter</li>
				<li><span>32</span> PTO</li>
				</ul>
			</div>
			
			<div class="col bloc">
				<h4>Top Senders</h4>
				<ul>
				<li><span>188</span> <a href="">Stevie Smith</a></li>
				<li><span>32</span> <a href="">Gary Baldi</a></li>
				<li><span>19</span> <a href="">Robyn Banks</a></li>
				<li><span>12</span> <a href="">Davey Jones</a></li>
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
