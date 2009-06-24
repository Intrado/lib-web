<?

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////
$PAGETITLE = preg_replace('/\\<.+>/','',$TITLE);

list($MAINTAB,$SUBTAB) = explode(":",$PAGE);

$FIRSTACTIVETABLINK = "";
$ACTIVEMAINTABTITLE = "";

$SHORTCUTS = array();

if (isset($_GET['timer']) || isset($_SESSION['timer'])) {
	$PAGETIME = microtime(true);
	$_SESSION['timer'] = true;
}

if ($USER->authorize(array('starteasy','sendmessage', 'sendemail', 'sendphone', 'sendsms'))) {
	$SHORTCUTS['-- Jobs & Messages --'] = "false;";
	if ($USER->authorize("starteasy")) {
		$SHORTCUTS['Start EasyCall'] = "javascript: popup('easycallstart.php?id=new',500,450);";
		$SHORTCUTS['Call Me to Record'] = "javascript: popup('callme.php?origin=message&id=new',500,450);";
	}
	if ($USER->authorize(array('sendmessage', 'sendemail', 'sendphone', 'sendsms'))) {
		$SHORTCUTS['My Messages'] = "messages.php";
		$SHORTCUTS['My Jobs'] = "jobs.php";
		$SHORTCUTS['My Archived Jobs'] = "jobsarchived.php";
		$SHORTCUTS['New Job'] = "job.php?id=new";
	}
	if ($USER->authorize("leavemessage")){
		$SHORTCUTS['View Responses'] = "replies.php?reset=1";
	}
}

if ($USER->authorize(array('createreport', 'viewsystemreports'))) {
	$SHORTCUTS['-- Reports & Status --'] = "false;";
	if ($USER->authorize('createreport') || $USER->authorize('viewsystemreports')) {
		$SHORTCUTS['Create a Report'] = "reports.php";
		$SHORTCUTS['View Job Summary'] = "reportjobsearch.php";
	}
	if ($USER->authorize('viewusagestats')) {
		$SHORTCUTS['Usage Stats'] = "reportsystem.php?clear=1";
	}
	if ($USER->authorize('viewcalldistribution')) {
		$SHORTCUTS['Call Distribution'] = "reportsystemdistribution.php";
	}
}

$SHORTCUTS['-- Lists & Contacts --'] = "false;";
if ($USER->authorize("createlist")) {
	$SHORTCUTS['New List'] = "list.php?id=new";
	$SHORTCUTS['My Lists'] = "lists.php";
}
$SHORTCUTS['My Address Book'] = "addresses.php";
if ($USER->authorize("viewcontacts"))
	$SHORTCUTS['System Contacts'] = "contacts.php?clear=1";
$SHORTCUTS['-- Help & Documentation --'] = "false;";
$SHORTCUTS['Message Tips & Ideas'] = "javascript: popup('help/schoolmessenger_help.htm#getting_started/message_tips_and_ideas.htm',750,500);";
$SHORTCUTS['Help'] = "javascript: popup('help/index.php',750,500);";

//tree format:
//[[title,default link,access,selected,[[sub title,sub link,sub access,sub selected],...],...]

$NAVTREE = array (
	array("Start","start.php",NULL,$MAINTAB=="start",array()),
	array("Notifications",NULL,array("createlist","sendphone","sendprint","sendemail", "sendsms"),$MAINTAB=="notifications",array(
		array("Lists","lists.php","createlist",$SUBTAB=="lists"),
		array("Messages","messages.php",array('sendmessage', 'sendemail', 'sendphone', "sendsms"),$SUBTAB=="messages"),
		array("Jobs","jobs.php",array('sendmessage', 'sendemail', 'sendphone', "sendsms"),$SUBTAB=="jobs"),
		array("Surveys","surveys.php",getSystemSetting("_hassurvey", true) ? "survey" : "dummy",$SUBTAB=="survey"),
		array("Responses","replies.php?reset=1","leavemessage",$SUBTAB=="responses")
		)),
	array("Reports","reports.php",array('createreport',"viewsystemreports", "viewusagestats","viewcalldistribution"),$MAINTAB=="reports",array(
		array("Reports", "reports.php", array("createreport", "viewsystemreports"), $SUBTAB=="reports"),
		array("Usage Stats","reportsystem.php?clear=1","viewusagestats",$SUBTAB=="system"),
		array("Call Distribution","reportsystemdistribution.php","viewcalldistribution",$SUBTAB=="distribution")
		)),
	array("System",NULL,array('viewsystemactive', 'viewsystemcompleted',
						'viewsystemrepeating','viewcontacts','blocknumbers'),$MAINTAB=="system",array(
		array("Active Jobs","activejobs.php","viewsystemactive",$SUBTAB=="activejobs"),
		array("Completed Jobs","completedjobs.php","viewsystemcompleted",$SUBTAB=="completedjobs"),
		array("Repeating Jobs","repeatingjobs.php","viewsystemrepeating",$SUBTAB=="repeatingjobs"),
		array("Contacts","contacts.php?clear=1","viewcontacts",$SUBTAB=="contacts"),
		array("Blocked Numbers","blocked.php","blocknumbers",$SUBTAB=="blockednumbers")
		)),
	array("Admin",NULL,array('manageaccount', 'manageprofile', 'managesystem',
							'metadata', 'managetasks'),$MAINTAB=="admin",array(
		array("Users","users.php","manageaccount",$SUBTAB=="users"),
		array("Profiles","profiles.php","manageprofile",$SUBTAB=="profiles"),
		array("Settings","settings.php",array("managesystem", "portalaccess","metadata"),$SUBTAB=="settings"),
		array("Imports","tasks.php","managetasks",$SUBTAB=="taskmanager")
		))
);


if (strlen($SYSTEMALERT = getSystemSetting("alertmessage")) > 0)
	$SYSTEMALERT = "<div class='alertmessage noprint'>" . nl2br(escapehtml($SYSTEMALERT)) . "</div>";
else
	$SYSTEMALERT = "";


////////////////////////////////////////////////////////////////////////////////
// Display Functions
////////////////////////////////////////////////////////////////////////////////

function navMainTab ($title, $link, $isselected) {
	$theme = getBrandTheme();
	return '<div class="navtab"><a onfocus="blur()" href="' . $link . '"><img src="img/themes/' . $theme . '/main_nav_tab' . ($isselected ? "_active" : "") . '.gif"><span>' . $title . '</span></a></div>';
}

function navSubTab ($title, $link, $isselected) {
	return '<a onfocus="blur()" class="subnavtab ' . ($isselected ? "active" : "") . '" href="' . $link . '"><div>' . $title . '</div></a>';
}

function doNavTabs ($navtree) {
	global $USER, $FIRSTACTIVETABLINK, $ACTIVEMAINTABTITLE, $MAINTABS,$SUBTABS;

	$MAINTABS = "";
	$SUBTABS = "";
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
			if (strpos($name,"--") === 0) {
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
	$logohash = crc32("cid".getSystemSetting("_logocontentid"));
	$clickurl = getSystemSetting("_logoclickurl");
	if($clickurl != "" && $clickurl != "http://")
		echo '<a href="' . $clickurl . '" target="_blank"><img src="logo.img.php?hash=' . $logohash .'></a>';
	else
		echo '<img src="logo.img.php?hash=' . $logohash .'">';
}

doNavTabs($NAVTREE);

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
//set the charset if we are spitting out html
header('Content-type: text/html; charset=UTF-8') ;
?>
<html>
<head>
	<meta http-equiv="Content-type" value="text/html; charset=UTF-8" />
	<title><?= getBrand();?>: <?= $PAGETITLE ?></title>

	<script src="script/prototype.js" type="text/javascript"></script>
	<script src="script/scriptaculous.js" type="text/javascript"></script>

	<script src="script/utils.js"></script>
	<script src="script/sorttable.js"></script>
	<link href="css.php?hash=<?=crc32(serialize($_SESSION['colorscheme']))?>" type="text/css" rel="stylesheet" media="screen, print">
	<link href="css/style_print.css" type="text/css" rel="stylesheet" media="print">

	
	
	<script src="script/form.js.php" type="text/javascript"></script>
	<link href="css/form.css.php" type="text/css" rel="stylesheet">
	<link href="css/datepicker.css.php" type="text/css" rel="stylesheet">
</head>
<body>
	<script>
		var _brandtheme = "<?=getBrandTheme();?>";
	</script>

	<IFRAME src="blank.html" id="blocker" style="DISPLAY: none; LEFT: 0px; POSITION: absolute; TOP: 0px" frameBorder="0" scrolling="no"></IFRAME>

<!-- ********************************************************************* -->

<div>
	<table width="100%" border=0 cellpadding=0 cellspacing=0 background="img/header_bg.gif" >
		<tr><td style="font-size:8px;">&nbsp;</td></tr>
	</table>
</div>

<div>
	<table width="100%" border=0 cellpadding=0 cellspacing=0>
	<tr>
		<td><div style="padding-left:10px; padding-bottom:10px;"><? doLogo() ?></div></td>
		<td><div class="custname"><?= escapehtml($_SESSION['custname']); ?></div></td>
	</tr>
	</table>
</div>

<div class="navmenuspacer">
<div class="navmenu">

	<?= $MAINTABS ?>

	<div class="applinks hoverlinks">
		<a href="addresses.php">Address Book</a> |
<?
if($USER->authorize('managemyaccount')){
?>
		<a href="account.php">Account</a> |
<?
}
?>
		<a href="#" onclick="window.open('help/index.php', '_blank', 'width=750,height=500,location=no,menubar=yes,resizable=yes,scrollbars=yes,status=no,titlebar=no,toolbar=yes');">Help</a> |
		<a href="index.php?logout=1">Logout</a>
	</div>

</div>
</div>


<div class="subnavmenu hoverlinks">

<? 	if ($USER->authorize("startshort")) { ?>
	<div class="shortcutmenuholder">
		<div class="shortcutmenu" onmouseover="document.getElementById('shortcuts').style.display='block';"
								onmouseout="if (event.target || window.event.srcElement == this) document.getElementById('shortcuts').style.display='none';"
		><img src="img/arrow_down.gif">Shortcuts
			<div class="shortcuts hoverlinks" id="shortcuts">
				<? doShortcuts($SHORTCUTS) ?>
			</div>
		</div>
	</div>
<? } ?>

	<?= $SUBTABS ?>
</div>


<div class="crumbs hoverlinks">
	<?= doCrumb($FIRSTACTIVETABLINK, $ACTIVEMAINTABTITLE, $TITLE) ?>
</div>

<div class="pagetitle"><? if(isset($ICON)) print '<img src="img/themes/' .getBrandTheme() . '/icon_' . $ICON . '" align="absmiddle">'; ?> <?= $TITLE ?></div>
<div class="pagetitlesubtext"><?= (isset($DESCRIPTION) ? $DESCRIPTION : "") ?></div>

<div class="content">

	<?= $SYSTEMALERT ?>
