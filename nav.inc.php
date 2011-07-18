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

if ($USER->authorize(array('starteasy', 'sendemail', 'sendphone', 'sendsms'))) {
	$SHORTCUTS['-- Jobs & Messages --'] = "false;";
	if ($USER->authorize("starteasy")) {
		$SHORTCUTS['EasyStart'] = "jobwizard.php?new";
	}
	if ($USER->authorize(array('sendemail', 'sendphone', 'sendsms'))) {
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
$SHORTCUTS['My Address Book'] = "addresses.php?origin=nav";
if ($USER->authorize("viewcontacts"))
	$SHORTCUTS['System Contacts'] = "contacts.php";
$SHORTCUTS['-- Help & Documentation --'] = "false;";
$SHORTCUTS['Message Tips & Ideas'] = "javascript: popup('help/html/Tips_for_Effective_Communication/Messaging_Tips.htm',750,500);";
$SHORTCUTS['Help'] = "javascript: popup('help/index.php',750,500);";

//tree format:
//[[title,default link,access,selected,[[sub title,sub link,sub access,sub selected],...],...]

$NAVTREE = array (
	array("Start","start.php",NULL,$MAINTAB=="start",array()),
	array("Notifications",NULL,array("createlist","sendphone","sendprint","sendemail", "sendsms",getSystemSetting("_hastargetedmessage", false) ? "targetedmessage" : "dummy"),$MAINTAB=="notifications",array(
		array("Lists","lists.php",array("createlist","subscribe"),$SUBTAB=="lists"),
		array("Messages","messages.php",array('sendemail', 'sendphone', "sendsms","subscribe"),$SUBTAB=="messages"),
		array("Jobs","jobs.php",array('sendemail', 'sendphone', "sendsms"),$SUBTAB=="jobs"),
		array("Classroom","classroommessageoverview.php",getSystemSetting("_hastargetedmessage", false) ? "targetedmessage" : "dummy",$SUBTAB=="classroom"),
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
		array("Contacts","contacts.php","viewcontacts",$SUBTAB=="contacts"),
		array("Blocked Lists","blocked.php","blocknumbers",$SUBTAB=="blockednumbers")
		)),
	array("Admin",NULL,array('manageaccount', 'manageprofile', 'managesystem',
							'metadata', 'managetasks', 'manageclassroommessaging'),$MAINTAB=="admin",array(
		array("Users","users.php","manageaccount",$SUBTAB=="users"),
		array("Profiles","profiles.php","manageprofile",$SUBTAB=="profiles"),
		array("Settings","settings.php",array("managesystem","metadata","manageclassroommessaging"),$SUBTAB=="settings"),
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
		echo '<a href="' . $clickurl . '" target="_blank"><img src="logo.img.php?hash=' . $logohash .'" alt="Logo"></a>';
	else
		echo '<img src="logo.img.php?hash=' . $logohash .'" alt="">';
}

doNavTabs($NAVTREE);

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
//set the charset if we are spitting out html
header('Content-type: text/html; charset=UTF-8') ;
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<meta http-equiv="Content-type" content="text/html; charset=UTF-8" />
	<meta http-equiv="X-UA-Compatible" content="IE=EmulateIE7" />
	<title><?= getBrand();?>: <?= $PAGETITLE ?></title>

	<script src="script/prototype.js" type="text/javascript"></script>
	<script src="script/scriptaculous.js" type="text/javascript"></script>
	<script src="script/prototip.js.php" type="text/javascript"></script>
	<script src="script/utils.js"></script>
	<script src="script/sorttable.js"></script>
	<script src="script/form.js.php" type="text/javascript"></script>

	<link href="css.php?hash=<?=crc32(serialize($_SESSION['colorscheme']))?>" type="text/css" rel="stylesheet" media="screen, print">
	<link href="css/form.css.php" type="text/css" rel="stylesheet">
	<link href="css/datepicker.css.php" type="text/css" rel="stylesheet">
	<link href="css/prototip.css.php" type="text/css" rel="stylesheet">
	<link href="css/style_print.css" type="text/css" rel="stylesheet" media="print">

<!--[if lte IE 6]>
    <link href="css/ie6.css" type="text/css" rel="stylesheet"/>
<![endif]-->

<!--[if lte IE 7]>
    <link href="css/ie7.css" type="text/css" rel="stylesheet"/>
<![endif]-->

</head>
<body>
	<script>
		var _brandtheme = "<?=getBrandTheme();?>";
	</script>

	<IFRAME src="blank.html" id="blocker" style="DISPLAY: none; LEFT: 0px; POSITION: absolute; TOP: 0px" frameBorder="0" scrolling="no"></IFRAME>

<!-- ********************************************************************* -->

<table class="navlogoarea" border=0 cellspacing=0 cellpadding=0 width="100%">
	<tr>
		<td bgcolor="white"><div style="padding-left:10px;"><? doLogo() ?></div></td>
		<td><img src="img/shwoosh.gif" alt="" class="noprint"></td>
		<td width="100%" align="right" style="padding-right: 10px;">
			<div class="custname"><?= escapehtml($_SESSION['custname']); ?></div>
			<table border=0 cellspacing=0 cellpadding=0 class="noprint">
				<tr>
					<td><img src="img/accountlinksbg_left.gif" alt="" ></td>
					<td background="img/accountlinksbg_mid.gif">
						<div class="applinks hoverlinks">
							<a href="addresses.php?origin=nav">Address Book</a> |
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
					</td>
					<td><img src="img/accountlinksbg_right.gif" alt="" ></td>
				</tr>
			</table>
		</td>
	</tr>
</table>
<div class="navband1"><img src="img/pixel.gif" alt="" ></div>
<div class="navband2"><img src="img/pixel.gif" alt="" ></div>

<!-- =================================================== -->
<div class="navmenuspacer">
<div class="navmenu">
<? 	if ($USER->authorize("startshort")) { ?>
	<div id="shortcutmenu" class="shortcutmenu"><img src="img/arrow_down.gif" style="margin-right: 5px; margin-left: 5px; float: right;" alt="" >Shortcuts</div>
	<div class="shortcuts hoverlinks" id="shortcuts" style="display: none;">
		<? doShortcuts($SHORTCUTS) ?>
	</div>
<script type="text/javascript" language="javascript">
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
});
</script>

<? } ?>

	<?= $MAINTABS ?>
</div>
</div>

<div class="subnavmenu hoverlinks">
	<?= $SUBTABS ?>
</div>

<div class="pagetitle"><? if(isset($ICON)) print '<img src="img/themes/' .getBrandTheme() . '/icon_' . $ICON . '" align="absmiddle">'; ?> <?= $TITLE ?></div>
<div class="pagetitlesubtext"><?= (isset($DESCRIPTION) ? $DESCRIPTION : "") ?></div>

<div class="maincontent">

	<?= $SYSTEMALERT ?>

	<?
		if (!empty($_SESSION['confirmnotice'])) {
			echo "<div class='confirmnoticecontainer noprint'><div class='confirmnoticecontent noprint'>";
				echo implode("<hr />", $_SESSION['confirmnotice']);
			echo "</div></div>";
		}
		unset($_SESSION['confirmnotice']);
	?>
