<?

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////


$currentpage = substr($_SERVER["SCRIPT_NAME"],strrpos($_SERVER["SCRIPT_NAME"],"/")+1);

$COLORSCHEMES = array(
	"forest" => array("displayname" => "Forest",
							"_brandprimary" => "0D8336", /* mid green */
							"_brandtheme1" => "13a545",
							"_brandtheme2" => "484848",
							"_brandratio" => ".2")
);
$_SESSION['colorscheme'] = array();
$_SESSION['colorscheme']['_brandtheme'] = "forest";
$_SESSION['colorscheme']['_brandprimary'] = "0D8336";
$_SESSION['colorscheme']['_brandratio'] = ".2";
$_SESSION['colorscheme']['_brandtheme1'] = $COLORSCHEMES["forest"]["_brandtheme1"];
$_SESSION['colorscheme']['_brandtheme2'] = $COLORSCHEMES["forest"]["_brandtheme2"];

if (!isset($TITLE))
	$TITLE = "TODO: No Title";
if (!isset($PAGE))
	$PAGE = "nopage";
$PAGETITLE = preg_replace('/\\<.+>/','',$TITLE);

list($MAINTAB,$SUBTAB) = explode(":",$PAGE);

$FIRSTACTIVETABLINK = "";
$ACTIVEMAINTABTITLE = "";

$SESSION_WARNING_TIME = isset($SETTINGS['feature']['session_warning_time']) ? 
	$SETTINGS['feature']['session_warning_time']*1000 : 1200000;

$SHORTCUTS = array();

if (isset($_GET['timer']) || isset($_SESSION['timer'])) {
	$PAGETIME = microtime(true);
	$_SESSION['timer'] = true;
}

//tree format:
//[[title,default link,access,selected,[[sub title,sub link,sub access,sub selected],...],...]

// TODO - can we remove sections of the menu definition which are outside the scope of the current
// request? ex. if we are in the CUSTOMERS section of the site, the menu never shows anything about
// the selections below the TOOLS section; it should simply suffice to add TOOLS to the upper level
// and leave the inner levels out

$NAVTREE = array (
	array("Customers","allcustomers.php",NULL,$MAINTAB=="overview",array(
		array("Customer&nbsp;List","allcustomers.php",NULL,$SUBTAB=="customerlist"),
		array("New&nbsp;Customer","customeredit.php?id=new",NULL,$SUBTAB=="newcustomer")
	)),
	array("Commsuite","customers.php",NULL,$MAINTAB=="commsuite", get_authorized_commsuite()),
	array("TalkAboutIt","taicustomers.php",NULL,$MAINTAB=="tai",array(
		array("Customers","taicustomers.php",NULL,$SUBTAB=="customers"),
		array("Inbox","taiinbox.php",NULL,$SUBTAB=="inbox"),
		array("Requests","tairevealrequests.php",NULL,$SUBTAB=="requests"),
		array("SMS&nbsp;Numbers","taismsnumbers.php",NULL,$SUBTAB=="smsnumbers")
	)),
	array('Tools', NULL, NULL, ($MAINTAB == 'advanced'), get_authorized_advanced()),
	array('Reports', NULL, NULL, ($MAINTAB == 'reports'), get_authorized_reports())
);



////////////////////////////////////////////////////////////////////////////////
// Menu Building Functions
////////////////////////////////////////////////////////////////////////////////


// Build the Reports menu
function get_authorized_commsuite() {
	global $MANAGERUSER, $SUBTAB, $SETTINGS;

	$menu = Array();

	// TODO - add the authorization checks for this block of actions:
	$menu[] = array("Customers","customers.php",NULL,$SUBTAB=="customers");
	$menu[] = array("Import&nbsp;Alerts","importalerts.php",NULL,$SUBTAB=="importalerts");
	$menu[] = array("Active&nbsp;Jobs","customeractivejobs.php",NULL,$SUBTAB=="activejobs");
	$menu[] = array("Locked&nbsp;Users","lockedusers.php","lockedusers",$SUBTAB=="lockedusers");
	$menu[] = array("SmartCall","customerdms.php?clear",NULL,$SUBTAB=="customerdms");
	$menu[] = array("System&nbsp;DMs","systemdms.php",NULL,$SUBTAB=="systemdms");

	if ($MANAGERUSER->authorized('systemdm')) {
		$menu[] = array('DM&nbsp;Blocking', 'dmgroupblock.php', NULL, ($SUBTAB == 'dmblocking'));
	}

	if ($MANAGERUSER->authorized("diskagent")) {
		$menu[] = array('SwiftSync', 'diskagents.php', NULL, ($SUBTAB == 'swiftsync'));
	}

	return($menu);
}


// Build the Reports menu
function get_authorized_reports() {
	global $MANAGERUSER, $SUBTAB, $SETTINGS;

	$menu = Array();

	if ($MANAGERUSER->authorized('billablecalls')) { 
		$menu[] = array('Billable&nbsp;Calls', 'billablecalls.php', NULL, ($SUBTAB == 'billable'));
	}

	if ($MANAGERUSER->authorized('emergencyjobs')) {
		$menu[] = array('Completed&nbsp;Jobs', 'emergencyjobs.php', NULL, ($SUBTAB == 'joblist'));
	}

	if ($MANAGERUSER->authorizedAny(array('logcollector', 'aspcallgraphs'))) {
		$menu[] = array('Graphs&nbsp;&amp;&nbsp;Logs', 'aspcallsindex.php', NULL, ($SUBTAB == 'graphlogs'));
	}

	if ($MANAGERUSER->authorized("customercontacts")) {
		$menu[] = array('Contact&nbsp;Search', 'customercontactsearch.php', NULL, ($SUBTAB == 'contacts'));
	}

	if ($MANAGERUSER->authorized('bouncedemailsearch')) {
		$menu[] = array('User&nbsp;Email', 'bouncedemailsearch.php', NULL, ($SUBTAB == 'email'));
	}

	if ($MANAGERUSER->authorized('passwordcheck')) {
		$menu[] = array('Bad&nbsp;Passwords', 'passwordcheck.php', NULL, ($SUBTAB == 'badpasswd'));
	}

	return($menu);
}

// Build the Advanced menu
function get_authorized_advanced() {
	global $MANAGERUSER, $SUBTAB, $SETTINGS;

	$menu = Array();

	if ($MANAGERUSER->authorized('runqueries') || $MANAGERUSER->authorized('editqueries')) {
		// TODO - find and document what the inner array does (runqueries/editqueries)
		$menu[] = array('Queries', 'querylist.php', array('runqueries', 'editqueries'), ($SUBTAB == 'queries'));
	}

	if (isset($SETTINGS['servermanagement']['manageservers']) && $SETTINGS['servermanagement']['manageservers'] && $MANAGERUSER->authorized('manageserver')) {
		$menu[] = array('Servers', 'serverlist.php', NULL, ($SUBTAB == 'servers'));
	}

	if ($MANAGERUSER->authorized('superuser')) { 
		$menu[] = array('Manager&nbsp;Users', 'users.php', NULL, ($SUBTAB == 'users'));
	}

	if ($MANAGERUSER->authorized("smsblock")) {
		$menu[] = array('SMS&nbsp;Block', 'smsblock.php', NULL, ($SUBTAB == 'smsblock'));
	}

	if ($MANAGERUSER->authorized('tollfreenumbers')) {
		$menu[] = array('Toll&nbsp;Free&nbsp;#s', 'tollfreenumbers.php', NULL, ($SUBTAB == 'tollfree'));
	}

	return($menu);
}

////////////////////////////////////////////////////////////////////////////////
// Display Functions
////////////////////////////////////////////////////////////////////////////////

function navMainTab ($title, $link, $isselected) {
	$theme = getBrandTheme();
	return '<li '. ($isselected ? 'class="navtab_active"' : "") .'><a onfocus="blur()" href="' . $link . '">' . $title . '</a></li>';
}

function navSubTab ($title, $link, $isselected) {
	return '<li '. ($isselected ? 'class="navtab_active"' : "") .'><a onfocus="blur()" class="subnavtab" href="' . $link . '">' . $title . '</a></li>';
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
			foreach ($maintab[4] as $subtab) {
				if ($subtab[2] == NULL || $MANAGERUSER->authorized($subtab[2])) {
					//set the maintablink to the first enabled subtab's link
					if ($maintablink === false)
						$maintablink = $subtab[1];
					//build subtab html if this maintab is selected
					if ($maintab[3]) {
						$FIRSTACTIVETABLINK = $maintablink;
						$ACTIVEMAINTABTITLE = $maintab[0];
						$SUBTABS .= navSubTab($subtab[0],$subtab[1],$subtab[3]);
					}
				}
			}
			//if we didnt get a link, then use the default
			$maintablink = $maintablink === false ? $maintab[1] : $maintablink;

			$MAINTABS .= navMainTab($maintab[0],$maintablink,$maintab[3]);
		}
	}
}

function doShortcuts ($shortcuts) {
	global $USER;
	if ($USER->authorize("startshort")) {
		foreach ($shortcuts as $name => $value) {
			if (strpos($name,"<b>") === 0) {
				?><div class="shortcuttitle"><?= $name ?></div><?
			} else {
				?><a href="<?= escapehtml($value) ?>"><?= $name ?></a><?
			}
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
	echo '<img src="manager.png" alt="" onclick="window.location=\'allcustomers.php?newnav=true\'" >';
}

function setBodyClass () {
	$theme = $_SESSION['colorscheme']['_brandtheme'];
	echo 'class="' . $theme . '"';
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
	<title>Manager: <?= $PAGETITLE ?></title>

	<script src="script/prototype.js" type="text/javascript"></script> <!-- updated to prototype 1.7 -->
	<script src="script/scriptaculous.js" type="text/javascript"></script>
	<script src="script/prototip.js.php" type="text/javascript"></script>
	<script src="script/utils.js"></script>
	<script src="script/sorttable.js"></script>
	<script src="script/form.js.php" type="text/javascript"></script>
	<script src="script/livepipe/livepipe.js" type="text/javascript"></script>
	<script src="script/livepipe/window.js" type="text/javascript"></script>
	<script src="script/modalwrapper.js" type="text/javascript"></script>
	
	<link href="css.php?hash=<?=crc32(serialize($_SESSION['colorscheme']))?>&newnav=true" type="text/css" rel="stylesheet" media="screen, print" />
	<link href="css.forms.php?hash=<?=crc32(serialize($_SESSION['colorscheme']))?>" type="text/css" rel="stylesheet" media="screen, print" />
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
</head>
<body <?=setBodyClass();?> >
	<script>
		var _brandtheme = "<?=getBrandTheme();?>";
	</script>

<!-- ********************************************************************* -->

<div id="top_banner" class="banner cf">
<div class="container">

	<div class="banner_logo">
		<? doLogo() ?>
		<h1>SchoolMessenger</h1>
	</div>  
	
	<div class="banner_links_wrap">
		<ul class="banner_links cf">
			<li class="bl_left"></li>
			<li class="bl_last"><a href="allcustomers.php?newnav=false">Old Nav</a></li>
			<li class="bl_last"><a href="index.php?logout=1">Logout</a></li>
			<li class="bl_right"></li>
		</ul>
	</div>

</div><!-- /container -->	
</div><!--  end top_banner -->

<script type="text/javascript">
Event.observe(window, 'load', function() {
	new Tip('shortcutmenu', $('shortcuts'), {
		style: 'default',
		radius: 4,
		border: 4,
		target: 'shortcutmenu',
		hideOn: false,
		hideAfter: 0.5,
		hook: { target: 'bottomRight', tip: 'topRight' },
		offset: { x: 6, y: 0 },
		width: 'auto'
	});
	
	sessionKeepAliveWarning(<?=$SESSION_WARNING_TIME?>);
});
</script>

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
<div class="container cf">
	<?
		if (!empty($_SESSION['confirmnotice'])) {
			echo "<div class='confirmnoticecontainer noprint'><div class='confirmnoticecontent noprint'>";
				echo implode("<hr />", $_SESSION['confirmnotice']);
			echo "</div></div>";
		}
		unset($_SESSION['confirmnotice']);
	?>
