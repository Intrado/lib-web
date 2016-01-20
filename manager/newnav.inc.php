<?

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////


$currentpage = substr($_SERVER["SCRIPT_NAME"],strrpos($_SERVER["SCRIPT_NAME"],"/")+1);

if (!isset($TITLE))
	$TITLE = "TODO: No Title";
if (!isset($PAGE))
	$PAGE = "nopage";
$PAGETITLE = preg_replace('/\\<.+>/','',$TITLE);

list($MAINTAB,$SUBTAB) = explode(":",$PAGE);

$FIRSTACTIVETABLINK = "";
$ACTIVEMAINTABTITLE = "";

$SHORTCUTS = array();

if (isset($_GET['timer']) || isset($_SESSION['timer'])) {
	$PAGETIME = microtime(true);
	$_SESSION['timer'] = true;
}

//tree format:
//[[title,default link,access,selected,[[sub title,sub link,sub access,sub selected],...],...]
$NAVTREE = array();

// Removes sections of the menu definition which are outside the scope of the
// current request; e.g. if we are in the CUSTOMERS section of the site, the
// menu never shows anything about the selections below the TOOLS section; it
// should simply suffice to add TOOLS to the upper level and leave the inner
// levels out; also trims down the menu to only things that the user has
// permission to access (each permission check should match the checks performed
// in the associated PHP page).

if (count($menu = get_authorized_commsuite())) {
	$first_page = $menu[0][1];
	$active = ($MAINTAB == 'commsuite');
	$NAVTREE[] = array('Commsuite', $first_page, NULL, $active, ($active ? $menu : NULL), "commsuite");
}

if (count($menu = get_authorized_dm())) {
	$first_page = $menu[0][1];
	$active = ($MAINTAB == 'dm');
	$NAVTREE[] = array('DM', $first_page, NULL, $active, ($active ? $menu : NULL), "dm");
}

if (count($menu = get_authorized_advanced())) {
	$first_page = $menu[0][1];
	$active = ($MAINTAB == 'advanced');
	$NAVTREE[] = array('Tools', $first_page, NULL, $active, ($active ? $menu : NULL), "tools");
}

if (count($menu = get_authorized_reports())) {
	$first_page = $menu[0][1];
	$active = ($MAINTAB == 'reports');
	$NAVTREE[] = array('Reports', $first_page, NULL, $active, ($active ? $menu : NULL), "reports");
}

if (count($menu = get_authorized_customers())) {
	$first_page = $menu[0][1];
	$active = ($MAINTAB == 'overview');
	$NAVTREE[] = array('Customers', $first_page, NULL, $active, ($active ? $menu : NULL), "customers");
}


////////////////////////////////////////////////////////////////////////////////
// Menu Building Functions
////////////////////////////////////////////////////////////////////////////////

// Build the TalkAboutIt menu
function get_authorized_dm() {
	global $MANAGERUSER, $SUBTAB, $SETTINGS;

	$menu = Array();

	if ($MANAGERUSER->authorized('systemdm')) {
		$menu[] = array('System&nbsp;DM&nbsp;Summary', 'systemdmsummary.php', NULL, ($SUBTAB == 'systemdmsummary'), "systemdmsummary");
	}

	if ($MANAGERUSER->authorized('systemdm')) {
		$menu[] = array('Dashboard', 'systemdmdashboard.php', NULL, ($SUBTAB == 'systemdmdashboard'), "systemdmdashboard");
	}

	if ($MANAGERUSER->authorized('systemdm')) {
		$menu[] = array('System&nbsp;DMs', 'systemdms.php', NULL, ($SUBTAB == 'systemdms'), "systemdms");
	}

	if ($MANAGERUSER->authorized('systemdm')) {
		$menu[] = array('DM&nbsp;Groups', 'systemdmgroups.php', NULL, ($SUBTAB == 'systemdmgroups'), "systemdmgroups");
	}

	if ($MANAGERUSER->authorized('systemdm')) {
		$menu[] = array('DM&nbsp;Blocking', 'dmgroupblock.php', NULL, ($SUBTAB == 'dmblocking'), "dmblocking");
	}

	if ($MANAGERUSER->authorized('editdm')) {
		$menu[] = array('SmartCall', 'customerdms.php?clear', NULL, ($SUBTAB == 'customerdms'), "customerdms");
	}

	if ($MANAGERUSER->authorizedAny(array('logcollector', 'aspcallgraphs'))) {
		$menu[] = array('Graphs & Logs', 'aspcallsindex.php', NULL, ($SUBTAB == 'graphlogs'), "graphlogs");
	}


	return($menu);
}

// Build the Customers menu
function get_authorized_customers() {
	global $MANAGERUSER, $SUBTAB, $SETTINGS;

	$menu = Array();

	$menu[] = array('Customer&nbsp;List', 'allcustomers.php', NULL, ($SUBTAB == 'customerlist'), "customers");

	return($menu);
}

// Build the Commsuite menu
function get_authorized_commsuite() {
	global $MANAGERUSER, $SUBTAB, $SETTINGS;

	$menu = Array();

	$menu[] = array('Customers', 'customers.php', NULL, ($SUBTAB == 'customers'), "customers");

	if ($MANAGERUSER->authorized('imports')) {
		$menu[] = array('Import&nbsp;Alerts', 'importalerts.php', NULL, ($SUBTAB == 'importalerts'), "importalerts");
	}

	if ($MANAGERUSER->authorized('activejobs')) {
		$menu[] = array('Active&nbsp;Jobs', 'customeractivejobs.php' ,NULL, ($SUBTAB == 'activejobs'), "activejobs");
	}

	if ($MANAGERUSER->authorized('lockedusers')) {
		$menu[] = array('Locked&nbsp;Users', 'lockedusers.php', 'lockedusers', ($SUBTAB == 'lockedusers'), "lockedusers");
	}

	if ($MANAGERUSER->authorized('diskagent')) {
		$menu[] = array('SwiftSync', 'diskagents.php', NULL, ($SUBTAB == 'swiftsync'), "swiftsync");
	}

	return($menu);
}

// Build the Reports menu
function get_authorized_reports() {
	global $MANAGERUSER, $SUBTAB, $SETTINGS;

	$menu = Array();

	if ($MANAGERUSER->authorized('billablecalls')) { 
		$menu[] = array('Billable&nbsp;Calls', 'billablecalls.php', NULL, ($SUBTAB == 'billable'), "billable");
	}

	if ($MANAGERUSER->authorized('emergencyjobs')) {
		$menu[] = array('Completed&nbsp;Jobs', 'emergencyjobs.php', NULL, ($SUBTAB == 'joblist'), "joblist");
	}

	if ($MANAGERUSER->authorized("customercontacts")) {
		$menu[] = array('Contact&nbsp;Search', 'customercontactsearch.php', NULL, ($SUBTAB == 'contacts'), "contacts");
	}

	if ($MANAGERUSER->authorized('bouncedemailsearch')) {
		$menu[] = array('User&nbsp;Email', 'bouncedemailsearch.php', NULL, ($SUBTAB == 'email'), "email");
	}

	if ($MANAGERUSER->authorized('passwordcheck')) {
		$menu[] = array('Bad&nbsp;Passwords', 'passwordcheck.php', NULL, ($SUBTAB == 'badpasswd'), "badpassword");
	}

	return($menu);
}

// Build the Advanced (tools) menu
function get_authorized_advanced() {
	global $MANAGERUSER, $SUBTAB, $SETTINGS;

	$menu = Array();

	if ($MANAGERUSER->authorized('runqueries') || $MANAGERUSER->authorized('editqueries')) {
		// TODO - find and document what the inner array does (runqueries/editqueries)
		$menu[] = array('Queries', 'querylist.php', array('runqueries', 'editqueries'), ($SUBTAB == 'queries'), "queries");
	}

	if (isset($SETTINGS['servermanagement']['manageservers']) && $SETTINGS['servermanagement']['manageservers'] && $MANAGERUSER->authorized('manageserver')) {
		$menu[] = array('Servers', 'serverlist.php', NULL, ($SUBTAB == 'servers'), "servers");
	}

	if ($MANAGERUSER->authorized('superuser')) { 
		$menu[] = array('Manager&nbsp;Users', 'users.php', NULL, ($SUBTAB == 'users'), "users");
	}

	if ($MANAGERUSER->authorized("emailblock")) {
		$menu[] = array('Email&nbsp;Block', 'emailblock.php', NULL, ($SUBTAB == 'emailblock'), "emailblock");
	}

	if ($MANAGERUSER->authorized("smsblock")) {
		$menu[] = array('SMS&nbsp;Block', 'smsblock.php', NULL, ($SUBTAB == 'smsblock'), "smsblock");
	}

	if ($MANAGERUSER->authorized("shortcodegrouptools")) {
		$menu[] = array('Shortcode&nbsp;Group&nbsp;Tools', 'shortcodegrouptools.php', NULL, ($SUBTAB == 'shortcodegrouptools'), "shortcodegrouptools");
	}

	if ($MANAGERUSER->authorized('tollfreenumbers')) {
		$menu[] = array('Toll&nbsp;Free&nbsp;#s', 'tollfreenumbers.php', NULL, ($SUBTAB == 'tollfree'), "tollfree");
	}

	return($menu);
}

////////////////////////////////////////////////////////////////////////////////
// Display Functions
////////////////////////////////////////////////////////////////////////////////

function navMainTab ($title, $link, $isselected, $datatype) {
	return '<li '. ($isselected ? 'class="navtab_active"' : "") .'><a data-type="'. $datatype. '" onfocus="blur()" href="' . $link . '">' . $title . '</a></li>';
}

function navSubTab ($title, $link, $isselected, $datatype) {
	return '<li '. ($isselected ? 'class="navtab_active"' : "") .'><a data-type="'. $datatype. '" onfocus="blur()" class="subnavtab" href="' . $link . '">' . $title . '</a></li>';
}

function doNavTabs ($navtree) {
	global $MANAGERUSER, $FIRSTACTIVETABLINK, $ACTIVEMAINTABTITLE, $MAINTABS,$SUBTABS;

	$MAINTABS = "";
	$SUBTABS = "";
	foreach ($navtree as $maintab) {
		//make sure this tab is enabled
		if ($maintab[2] == NULL || $MANAGERUSER->authorized($maintab[2])) {
			//do the subtabs first, if there are any
			$maintablink = false;
			if (is_array($maintab[4]) && count($maintab[4])) foreach ($maintab[4] as $subtab) {
				if ($subtab[2] == NULL || $MANAGERUSER->authorized($subtab[2])) {
					//set the maintablink to the first enabled subtab's link
					if ($maintablink === false)
						$maintablink = $subtab[1];
					//build subtab html if this maintab is selected
					if ($maintab[3]) {
						$FIRSTACTIVETABLINK = $maintablink;
						$ACTIVEMAINTABTITLE = $maintab[0];
						$SUBTABS .= navSubTab($subtab[0],$subtab[1],$subtab[3], $subtab[4]);
					}
				}
			}
			//if we didnt get a link, then use the default
			$maintablink = $maintablink === false ? $maintab[1] : $maintablink;

			$MAINTABS .= navMainTab($maintab[0],$maintablink,$maintab[3],$maintab[5]);
		}
	}
}

function doCrumb ($firstactivetablink, $activemaintabtitle, $title) {
	$crumb = array ("Start" => "start.php");
	if ($firstactivetablink)
		$crumb["$activemaintabtitle"] = "$firstactivetablink";

	$crumbhtml = "";
	foreach($crumb as $name => $url) {
		$crumbhtml .= '<a href="' . $url . '"><img src="img/arrow_right.gif">' . $name . '</a> ';
	}
	$title = explode(':',$title);

	$crumbhtml .= '<img src="img/arrow_right.gif">' . $title[0];

	return $crumbhtml;
}

function doLogo () {
	echo '<img src="manager.png" alt="" onclick="window.location=\'customers.php\'" >';
}

doNavTabs($NAVTREE);

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
//set the charset if we are spitting out html
header('Content-type: text/html; charset=UTF-8') ;
?>
<!DOCTYPE html>
<!--[if lt IE 7]> <html class="no-js ie6 oldie" lang="en"> <![endif]-->
<!--[if IE 7]>    <html class="no-js ie7 oldie" lang="en"> <![endif]-->
<!--[if IE 8]>    <html class="no-js ie8 oldie" lang="en"> <![endif]-->
<!--[if (gte IE 9)|!(IE)]><!--> 
<html class="no-js" lang="en">
<!--<![endif]-->
<head>
	<meta http-equiv="Content-type" content="text/html; charset=UTF-8" />
	<meta http-equiv="X-UA-Compatible" content="IE=EmulateIE7" />

<?
if (isset($_GET['monitor'])) {
	$time = $_GET['monitor'] ? $_GET['monitor'] : 15;
	echo '<meta http-equiv="refresh" content="'.$time.'">';
} else {
	$autologoutminutes = isset($SETTINGS['feature']['autologoutminutes']) ? $SETTINGS['feature']['autologoutminutes'] : 30;
	echo '<meta http-equiv="refresh" content="'. 60*$autologoutminutes  .';url=index.php?logout=1&reason=timeout">';
}
?>

	<title>Manager: <?= $PAGETITLE ?></title>

	<script type="text/javascript" src="script/jquery-1.8.3.min.js"></script>
	<script type="text/javascript">
		jQuery.noConflict();
	</script>
	<script src="script/prototype.js" type="text/javascript"></script> <!-- updated to prototype 1.7 -->
	<script src="script/scriptaculous.js" type="text/javascript"></script>
	<script src="script/prototip.js.php" type="text/javascript"></script>
	<script src="script/utils.js"></script>
	<script src="script/session_warning.js" type="text/javascript"></script>
	<script src="script/sorttable.js"></script>
	<script src="script/form.js.php" type="text/javascript"></script>
	<script src="script/livepipe/livepipe.js" type="text/javascript"></script>
	<script src="script/livepipe/window.js" type="text/javascript"></script>
	<script src="script/modalwrapper.js" type="text/javascript"></script>
	<script src="script/bootstrap-modal.js" type="text/javascript"></script>

	<link href="css.php?newnav=true" type="text/css" rel="stylesheet" media="screen, print" />
	<link href="css.forms.php" type="text/css" rel="stylesheet" media="screen, print" />
	<link href="css/datepicker.css.php" type="text/css" rel="stylesheet" />
	<link href="css/prototip.css.php" type="text/css" rel="stylesheet" />
	<link href="css/style_print.css" type="text/css" rel="stylesheet" media="print" />
	
	<!--[if gte IE 9]>
	  <style type="text/css">
	    .gradient {
	       filter: none;
	    }
	  </style>
	<![endif]-->

	<script type="text/javascript">
		sessionKeepAliveWarning(<?=$SESSION_WARNING_TIME?>);
	</script>
</head>
<body class="manager">
<!-- ********************************************************************* -->

<div class="modal hide fade default-modal" id="defaultmodal">
	<div class="modal-header">
		<button class="close" aria-hidden="true" data-dismiss="modal" type="button">x</button>
		<h3></h3>
	</div>
	<div class="modal-body"></div>
</div>

<div class="wrap"><!-- tag ends in footer -->
<div id="top_banner" class="banner cf">
<div class="container">

	<div class="banner_logo">
		<? doLogo() ?>
	</div>  
	
	<div class="banner_links_wrap">
		<ul class="banner_links cf">
			<li class="bl_left"></li>
			<li><a class="logout" href="index.php?logout=1">Logout</a></li>
			<li class="bl_right"></li>
		</ul>
	</div>

</div><!-- /container -->	
</div><!--  end top_banner -->

<div class="primary_nav cf">
<div class="container">
	<ul class="navtabs">
	<?= $MAINTABS ?>
	</ul>

</div><!-- /container -->
</div><!-- primary_nav -->

<div class="subnavtabs">
	<div class="container">
		<ul class="cf">
			<?= $SUBTABS ?>
		</ul>
	</div>
</div>

<div class="content_wrap cf"><!-- tag ends in footer -->
<div class="container cf"><!-- tag ends in footer -->
	<?
		if (!empty($_SESSION['confirmnotice'])) {
			echo "<div class='confirmnoticecontainer noprint'><div class='confirmnoticecontent noprint'>";
				echo implode("<hr />", $_SESSION['confirmnotice']);
			echo "</div></div>";
		}
		unset($_SESSION['confirmnotice']);
	?>
