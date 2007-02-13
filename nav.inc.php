<?

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

$PAGETITLE = preg_replace('/\\<.+>/','',$TITLE);

list($MAINTAB,$SUBTAB) = explode(":",$PAGE);

$FIRSTACTIVETABLINK = "";
$ACTIVEMAINTABTITLE = "";

$SHORTCUTS = array();

if (isset($_GET['timer']))
	$PAGETIME = microtime(true);

if ($USER->authorize(array('starteasy','sendmessage', 'sendemail', 'sendphone'))) {
	$SHORTCUTS['-- Jobs & Messages --'] = "false;";
	if ($USER->authorize("starteasy")) {
		$SHORTCUTS['&nbsp;&nbsp;Start EasyCall'] = "popup('easycallstart.php',500,450);";
		$SHORTCUTS['&nbsp;&nbsp;Call Me to Record'] = "popup('callme.php?origin=message',500,450);";
	}
	if ($USER->authorize(array('sendmessage', 'sendemail', 'sendphone'))) {
		$SHORTCUTS['&nbsp;&nbsp;My Messages'] = "window.location='messages.php'";
		$SHORTCUTS['&nbsp;&nbsp;My Jobs'] = "window.location='jobs.php'";
		$SHORTCUTS['&nbsp;&nbsp;My Archived Jobs'] = "window.location='jobsarchived.php'";
		$SHORTCUTS['&nbsp;&nbsp;New Job'] = "window.location='job.php?id=new'";
	}
}

if ($USER->authorize(array('createreport', 'viewsystemreports'))) {
	$SHORTCUTS['-- Reports & Status --'] = "false;";
	if ($USER->authorize('createreport')) {
		$SHORTCUTS['&nbsp;&nbsp;Create a Report'] = "window.location='reportoptions.php'";
		$SHORTCUTS['&nbsp;&nbsp;View Job Summary'] = "window.location='reportsummary.php'";
	}
	if ($USER->authorize('viewsystemreports')) {
		$SHORTCUTS['&nbsp;&nbsp;Usage Stats'] = "window.location='reportsystem.php'";
		$SHORTCUTS['&nbsp;&nbsp;Call Distribution'] = "window.location='reportsystemdistribution.php'";
	}
}

$SHORTCUTS['-- Lists & Contacts --'] = "false;";
if ($USER->authorize("createlist")) {
	$SHORTCUTS['&nbsp;&nbsp;New List'] = "window.location='list.php?id=new'";
	$SHORTCUTS['&nbsp;&nbsp;My Lists'] = "window.location='lists.php'";
}
$SHORTCUTS['&nbsp;&nbsp;My Address Book'] = "popup('addresses.php?origin=nav',800,400);";
if ($USER->authorize("viewcontacts"))
	$SHORTCUTS['&nbsp;&nbsp;System Contacts'] = "window.location='contacts.php'";
$SHORTCUTS['-- Help & Documentation --'] = "false;";
$SHORTCUTS['&nbsp;&nbsp;Message Tips & Ideas'] = "window.open('help/schoolmessenger_help.htm#getting_started/message_tips_and_ideas.htm', '_blank', 'width=750,height=500,location=no,menubar=yes,resizable=yes,scrollbars=yes,status=no,titlebar=no,toolbar=yes');";
$SHORTCUTS['&nbsp;&nbsp;Help'] = "window.open('help/index.php', '_blank', 'width=750,height=500,location=no,menubar=yes,resizable=yes,scrollbars=yes,status=no,titlebar=no,toolbar=yes');";

//tree format:
//[[title,default link,access,selected,[[sub title,sub link,sub access,sub selected],...],...]

$NAVTREE = array (
	array("Start","start.php",NULL,$MAINTAB=="start",array()),
	array("Notifications",NULL,array("createlist","sendphone","sendprint","sendemail"),$MAINTAB=="notifications",array(
		array("Lists","lists.php","createlist",$SUBTAB=="lists"),
		array("Messages","messages.php",array('sendmessage', 'sendemail', 'sendphone'),$SUBTAB=="messages"),
		array("Jobs","jobs.php",array('sendmessage', 'sendemail', 'sendphone'),$SUBTAB=="jobs"),
		array("Surveys","surveys.php","survey",$SUBTAB=="survey")
		)),
	array("Reports","reportsummary.php",array('createreport','viewsystemreports'),$MAINTAB=="reports",array(
		array("Job Report","reportsummary.php","createreport",$SUBTAB=="jobsummary"),
		array("Report Builder","reportoptions.php","createreport",$SUBTAB=="builder"),
		//give the report viewer the default of a "today" report if there was no previous report
		array("View Report","report.php" . (isset($_SESSION['reportsql']) ? "" : "?reporttype=relative&jobtype_relative_data=today"),"createreport",$SUBTAB=="view"),
		array("Usage Stats","reportsystem.php","viewsystemreports",$SUBTAB=="system"),
		array("Call Distribution","reportsystemdistribution.php","viewsystemreports",$SUBTAB=="distribution")

		)),
	array("System",NULL,array('viewsystemactive', 'viewsystemcompleted',
						'viewsystemrepeating','viewcontacts','blocknumbers'),$MAINTAB=="system",array(
		array("Active Jobs","activejobs.php","viewsystemactive",$SUBTAB=="activejobs"),
		array("Completed Jobs","completedjobs.php","viewsystemcompleted",$SUBTAB=="completedjobs"),
		array("Repeating Jobs","repeatingjobs.php","viewsystemrepeating",$SUBTAB=="repeatingjobs"),
		array("Contacts","contacts.php","viewcontacts",$SUBTAB=="contacts"),
		array("Blocked Numbers","blocked.php","blocknumbers",$SUBTAB=="blockednumbers")
		)),
	array("Admin",NULL,array('manageaccount', 'manageprofile', 'managesystem',
							'metadata', 'managetasks'),$MAINTAB=="admin",array(
		array("Users","users.php","manageaccount",$SUBTAB=="users"),
		array("Security","profiles.php","manageprofile",$SUBTAB=="security"),
		array("Settings","settings.php","managesystem",$SUBTAB=="settings"),
		array("Metadata","datamanager.php","metadata",$SUBTAB=="datamanager"),
		array("Imports","tasks.php","managetasks",$SUBTAB=="taskmanager")
		))
);


if (strlen($SYSTEMALERT = getSystemSetting("alertmessage")) > 0)
	$SYSTEMALERT = "<div class='alertmessage noprint'>" . nl2br(htmlentities($SYSTEMALERT)) . "</div>";
else
	$SYSTEMALERT = "";


////////////////////////////////////////////////////////////////////////////////
// Display Functions
////////////////////////////////////////////////////////////////////////////////

function navMainTab ($title, $link, $isselected) {
	return "<a class='mainlink' onfocus='blur()' title='$title' href='$link'>"
		."<div class='maintab" . ($isselected ? "_selected" :"") . "'>$title</div></a>";
}

function navSubTab ($title, $link, $isselected) {
	return "<a class='sublink' onfocus='blur()' title='$title' href='$link'>"
		."<div class='subtab" . ($isselected ? "_selected" :"") ."'>$title</div></a>";
}

function doNavTabs ($navtree) {
	global $USER, $FIRSTACTIVETABLINK, $ACTIVEMAINTABTITLE;

	$subtabs = "";
	foreach ($navtree as $maintab) {
		//make sure this tab is enabled
		if ($maintab[2] == NULL || $USER->authorize($maintab[2])) {
			//do the subtabs first, if there are any
			$maintablink = false;
			foreach ($maintab[4] as $subtab) {
				if ($subtab[2] == NULL || $USER->authorize($subtab[2])) {
					//set the maintablink to the first enabled subtab's link
					if ($maintablink === false)
						$maintablink = $subtab[1];
					//build subtab html if this maintab is selected
					if ($maintab[3]) {
						$FIRSTACTIVETABLINK = $maintablink;
						$ACTIVEMAINTABTITLE = $maintab[0];
						$subtabs .= navSubTab($subtab[0],$subtab[1],$subtab[3]);
					}
				}
			}
			//if we didnt get a link, then use the default
			$maintablink = $maintablink === false ? $maintab[1] : $maintablink;

			echo navMainTab($maintab[0],$maintablink,$maintab[3]);
		}
	}
	echo "</div><div id='midnav'><div id='midnav2'>$subtabs</div></div>";
}

function doShortcuts ($shortcuts) {
	global $USER;
	if ($USER->authorize("startshort")) {
		?><select onchange="eval(this.value);"><option value="false;">Shortcuts</option><?

		foreach ($shortcuts as $name => $value) {
			if (strpos($name,"--") === 0) {
				?><option disabled value="<?= htmlentities($value) ?>"><?= $name ?></option><?
			} else {
				?><option value="<?= htmlentities($value) ?>"><?= $name ?></option><?
			}
		}
		?></select><?
	}
}

function doCrumb ($firstactivetablink, $activemaintabtitle, $title) {

	$crumb = array ("Start" => "start.php");
	if ($firstactivetablink)
		$crumb["$activemaintabtitle"] = "$firstactivetablink";

	$crumbhtml = '<table border="0" cellpadding="2" cellspacing="0" class="crumb noprint"><tr valign="middle"><td nowrap>';
	foreach($crumb as $name => $url) {
		$crumbhtml .= '<img src="img/arrow.gif" hspace="4">' . (is_int($name) ? $url : ('<a href="' . $url . '"><span class="crumb">' . $name . '</span></a></td><td nowrap>'));
	}
	$title = explode(':',$title);
	$crumbhtml .= '<img src="img/arrow.gif" hspace="4"><span id="crumb">' . $title[0] . '</span></td></tr></table>';

	return $crumbhtml;
}


////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

?>
<html>
<head>
	<title>SchoolMessenger: <?= $PAGETITLE ?></title>
	<script src='script/utils.js'></script>
	<script src='script/nav.js'></script>
	<script src='script/sorttable.js'></script>
	<link href='css/style_print.css' type='text/css' rel='stylesheet' media='print'>
	<link href='css/style.css' type='text/css' rel='stylesheet' media='screen'>
</head>
<body>
	<IFRAME src="blank.html" id="blocker" style="DISPLAY: none; LEFT: 0px; POSITION: absolute; TOP: 0px" frameBorder="0" scrolling="no"></IFRAME>
	<div id='container'>
		<div id='accountnav'>
			<div id='shortcuts'><? doShortcuts($SHORTCUTS) ?></div>
			<div id='myaddressbook'><a class='accountlink' href='#' onClick="popup('addresses.php?origin=nav',800,400);"><img src='img/address_button.gif' class='btnleftfloat' align='middle'/>My Address Book</a></div>
			<? if ($USER->authorize('managemyaccount')) { ?>
				<div id='myaccount'><a class='accountlink' href='account.php'><img src='img/account_button.gif' class='btnleftfloat' align='middle'/>My Account</a></div>
			<? } ?>
			<div id='help'><a class='accountlink' href='#' onclick="window.open('help/index.php', '_blank', 'width=750,height=500,location=no,menubar=yes,resizable=yes,scrollbars=yes,status=no,titlebar=no,toolbar=yes');"><img src='img/helpcenter.gif' class='btnleftfloat' align='middle'/>Help</a></div>
			<div id='logout'><a class='accountlink' href='index.php?logout=1'><img src='img/logout.gif' class='btnleftfloat' align='middle' />Logout</a></div>
		</div><div id='mainscreen'>
			<div id='orgtitle'><? print htmlentities($_SESSION['custname']); ?></div>
			<img id='brand' src='img/school_messenger.gif' /><img src='img/spacer.gif' width="1" height="40" />
<? if(is_object($USER)) { ?>
			<div id='topnav'><br><? doNavTabs($NAVTREE); ?></div></div>
<? } ?>

		<div id="contentbody">
			<?= $SYSTEMALERT ?>
<? if (isset($ERRORS) && is_array($ERRORS)) { ?>
			<noscript>
<?
	echo "<div class='alertmessage noprint'>" . nl2br(htmlentities(implode(".\n", $ERRORS) . ".")) . "</div>";
?>
			</noscript>
<? } ?>

			<div>
				<div id="crumb" style="float: right;"><?= doCrumb($FIRSTACTIVETABLINK, $ACTIVEMAINTABTITLE, $TITLE) ?></div>
				<div id="navtitle"><? if(isset($ICON)) print '<img src="img/icon_' . $ICON . '" align="absmiddle">'; ?> <?= $TITLE ?></div>
				<div id="navdesc"><?= (isset($DESCRIPTION) ? $DESCRIPTION : "") ?></div>
			</div>
			<div id='shadowblock'>
				<table width='100%' border='0' cellpadding='0' cellspacing='0'>
					<tr><td id='shadowcontent'>