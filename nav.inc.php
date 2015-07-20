<?
//phpinfo();
//exit;

// calculate the session warning timeout popup
$SESSION_WARNING_TIME = isset($SETTINGS['feature']['session_warning_time']) ?
	$SETTINGS['feature']['session_warning_time']*1000 : 1200000;

if (isset($_GET['iframe'])) {
	include_once("iframe.inc.php");
	return;
}

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////
$PAGETITLE = preg_replace('/\\<.+>/','',$TITLE);

list($MAINTAB,$SUBTAB) = explode(":",$PAGE);

$FIRSTACTIVETABLINK = "";
$ACTIVEMAINTABTITLE = "";

if (isset($_GET['timer']) || isset($_SESSION['timer'])) {
	$PAGETIME = microtime(true);
	$_SESSION['timer'] = true;
}

//tree format:
//[[title,default link,access,selected,[[sub title,sub link,sub access,sub selected],...],...]
$targetedMessagePerm = getSystemSetting("_hastargetedmessage", false) ? "targetedmessage" : "dummy";
$NAVTREE = array (
	array(_L("Dashboard"),"start.php",NULL,$MAINTAB=="start",array()),
	array(getJobsTitle(),NULL,array("createlist","sendphone","sendprint","sendemail", "sendsms", $targetedMessagePerm), $MAINTAB=="notifications",array(
		array("Lists","lists.php",array("createlist","subscribe"),$SUBTAB=="lists"),
		array("Messages","messages.php",array('sendemail', 'sendphone', "sendsms","subscribe"),$SUBTAB=="messages"),
		array(getJobsTitle(),"jobs.php",array('sendemail', 'sendphone', "sendsms"),$SUBTAB=="jobs"),
		array("Templates","jobtemplates.php",array('sendemail', 'sendphone', "sendsms"), $SUBTAB=="templates"),
		array("Posts","posts.php",getSystemSetting("_hasfeed", false) ? "feedpost" : "dummy",$SUBTAB=="post"),
		array("Classroom","classroommessageoverview.php",getSystemSetting("_hastargetedmessage", false) ? "targetedmessage" : "dummy",$SUBTAB=="classroom"),
		array("Surveys","surveys.php",$USER->canSendSurvey() ? "survey" : "dummy",$SUBTAB=="survey"),
		array("Responses","replies.php?reset=1","leavemessage",$SUBTAB=="responses"),
		array("Tips","tips.php",getSystemSetting("_hasquicktip", false) ? "tai_canbetopicrecipient" : "dummy",$SUBTAB=="tips"),
		array("SDD","pdfmanager.php", getSystemSetting("_haspdfburst", false) ? "canpdfburst" : "dummy", $SUBTAB=="pdfmanager")
		)),
	array("Reports","reports.php",array('createreport',"viewsystemreports", "viewusagestats","viewcalldistribution"),$MAINTAB=="reports",array(
		array("Reports", "reports.php", array("createreport", "viewsystemreports"), $SUBTAB=="reports"),
		array("Usage Stats","reportsystem.php?clear=1","viewusagestats",$SUBTAB=="system"),
		array("Call Distribution","reportsystemdistribution.php","viewcalldistribution",$SUBTAB=="distribution")
		)),
	array("System",NULL,array('viewsystemactive', 'viewsystemcompleted',
						'viewsystemrepeating','viewcontacts','blocknumbers','monitorevent'),$MAINTAB=="system",array(
		array("Active " . getJobsTitle(),"activejobs.php","viewsystemactive",$SUBTAB=="activejobs"),
		array("Completed " . getJobsTitle(),"completedjobs.php","viewsystemcompleted",$SUBTAB=="completedjobs"),
		array("Repeating " . getJobsTitle(),"repeatingjobs.php","viewsystemrepeating",$SUBTAB=="repeatingjobs"),
		array("Contacts","contacts.php","viewcontacts",$SUBTAB=="contacts"),
		array("Blocked Lists","blocked.php","blocknumbers",$SUBTAB=="blocknumbers"),
		array("Monitors","monitors.php","monitorevent",$SUBTAB=="monitors")
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

//System maintenance message
if (strlen($GLOBALALERT = getSystemSetting("globalmessage")) > 0)
	$GLOBALALERT = "<div class='alertmessage noprint'>" . nl2br(escapehtml($GLOBALALERT)) . "</div>";
else
	$GLOBALALERT = "";


////////////////////////////////////////////////////////////////////////////////
// Display Functions
////////////////////////////////////////////////////////////////////////////////

function navMainTab ($title, $link, $isselected) {
	return '<li '. ($isselected ? 'class="navtab_active"' : "") .'><a onfocus="blur()" href="' . $link . '">' . $title . '</a></li>';
}

function navSubTab ($title, $link, $isselected) {
	return '<li '. ($isselected ? 'class="navtab_active"' : "") .'><a onfocus="blur()" class="subnavtab" href="' . $link . '">' . $title . '</a></li>';
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
<!DOCTYPE html>
<html>
<head>

	<? include('inc/newrelic.inc.php') ?>

	<meta http-equiv="Content-type" content="text/html; charset=UTF-8" />
	<meta http-equiv="X-UA-Compatible" content="IE=edge" />

	<title><?= getBrand();?>: <?= $PAGETITLE ?></title>

	<script type="text/javascript" src="script/jquery-1.8.3.min.js"></script>
<?if (!isset($NOPROTOTYPE) || !$NOPROTOTYPE) {?>
	<script type="text/javascript">
		jQuery.noConflict();
	</script>
	<script src="script/prototype.js" type="text/javascript"></script> <!-- updated to prototype 1.7 -->
	<script src="script/scriptaculous.js" type="text/javascript"></script>
	<script type="text/javascript" src="script/datepicker.js"></script>
	<link href="css/datepicker.css.php" type="text/css" rel="stylesheet" />
	<link href="css/newui_datepicker.css" type="text/css" rel="stylesheet" />
	<script src="script/modalwrapper.js" type="text/javascript"></script>
	<script src="script/livepipe/livepipe.js" type="text/javascript"></script>
	<script src="script/livepipe/window.js" type="text/javascript"></script>
	<script src="script/prototip.js.php" type="text/javascript"></script>
	<link href="css/prototip.css.php" type="text/css" rel="stylesheet" />
	<script src="script/form.js.php" type="text/javascript"></script>
<?}?>
	<script src="script/sorttable.js"></script>
	<script src="script/session_warning.js" type="text/javascript"></script>
	<script src="script/utils.js"></script>


<?if (isset($MESSAGESENDER) && $MESSAGESENDER) {?>
	<link href="css/nav_head_foot.css" type="text/css" rel="stylesheet" />
<?} else {?>
	<script src="script/bootstrap-modal.js" type="text/javascript"></script>

	<link href="css.php" type="text/css" rel="stylesheet" media="screen, print" />
	<link href="css.forms.php" type="text/css" rel="stylesheet" media="screen, print" />
<?}?>
	<link href="css/style_print.css" type="text/css" rel="stylesheet" media="print" />

	<!--[if IE 8]>
		<script src="script/respond.min.js" type="text/javascript"></script>
	<![endif]-->

</head>

<!--[if IE 7]> <body class="ie7"> <![endif]-->
<!--[if IE 8]> <body class="ie8"> <![endif]-->
<!--[if gt IE 8]><!--> <body> <!--<![endif]-->
	<script type="text/javascript">
<?
	if (isset($SETTINGS['googleanalytics']['trackingid'])) {
?>
		var _gaq = _gaq || [];
		_gaq.push(['_setAccount', '<?= $SETTINGS['googleanalytics']['trackingid'] ?>']);
		_gaq.push(['_setCookiePath', '/<?= $CUSTOMERURL ?>/']);
		_gaq.push(['_setCustomVar', 1, 'cust', '<?= $CUSTOMERURL ?>', 2]);
		_gaq.push(['_setSiteSpeedSampleRate', <?= $SETTINGS['googleanalytics']['samplerate'] ?>]);
		_gaq.push(['_trackPageview']);
		(function() {
			var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
			ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
			var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
		})();
<?
}
?>
	</script>

<!-- ********************************************************************* -->

<div class="wrap"><!-- ends in navbottom.inc -->

<div class="modal hide fade default-modal" id="defaultmodal">
	<div class="modal-header">
		<button class="close" aria-hidden="true" data-dismiss="modal" type="button">x</button>
		<h3></h3>
	</div>
	<div class="modal-body"></div>
</div>

<div class="modal hide fade" id="feedbackModal" style="width: 90%; max-height: none; margin: 5%; position: absolue; left: 0px; top: 0px;"-->
	<div class="modal-header">
		<button class="close" aria-hidden="true" data-dismiss="modal" type="button">x</button>
		<h3>Feedback</h3>
	</div>
	<iframe style="width: 100%; height: 500px; border: none;" src="feedback.php?iframe=1&from=<?=$_SERVER['REQUEST_URI']?>"></iframe>
	<br clear="all"/>
</div>

<div id="top_banner" class="banner">
<div class="contain cf">

	<div class="banner_logo">
		<table class="logo"><tr><td><? doLogo() ?></td></tr></table>
		<h1><?= getBrand();?></h1>
	</div>

	<div class="banner_custname"><?= escapehtml($_SESSION['custname']); ?></div>

	<div class="banner_links_wrap">
		<ul class="banner_links cf">
			<li class="bl_left"></li>
			<li><a href="addresses.php?origin=nav">Address Book</a></li>
<? if($USER->authorize('managemyaccount')){ ?>
			<li><a href="account.php">Account</a></li>
<? } ?>
			<li><a href="#" onclick="window.open('help/index.php', '_blank', 'width=960,height=650,location=no,menubar=yes,resizable=yes,scrollbars=no,status=no,titlebar=no,toolbar=yes');">Help</a></li>
			<li><a data-toggle="modal" href="#feedbackModal">Feedback</a></li>
			<li class="bl_last"><a class="logout" href="index.php?logout=1">Logout</a></li>
			<li class="bl_right"></li>
		</ul>
	</div>

</div><!-- /container -->
</div><!--  end top_banner -->

<script type="text/javascript">
	jQuery(function() {
		sessionKeepAliveWarning(<?=$SESSION_WARNING_TIME?>);
	});
</script>

<div class="primary_nav">
<div class="contain cf">

	<ul class="navtabs">
	<?= $MAINTABS ?>
	</ul>

</div><!-- /container -->
</div><!-- primary_nav -->

<div class="subnavtabs">
	<div class="contain">
		<ul class="cf">
			<?= $SUBTABS ?>
		</ul>
	</div>
</div>


<div class="content_wrap cf"><!-- tag ends in footer -->
<?if ($TITLE) {?>
	<div class="container cf">
		<div class="sectitle">
			<div class="pagetitle"><?= (isset($TITLE) ? $TITLE : "") ?></div>
			<div class="pagetitlesubtext"><?= (isset($DESCRIPTION) ? $DESCRIPTION : "") ?></div>
		</div><!-- end sectitle -->
	</div>
<?}?>

	<div class="container cf">
	<?= $SYSTEMALERT ?>
	<?= $GLOBALALERT ?>

	<?
		if (!empty($_SESSION['confirmnotice'])) {
			echo "<div class='confirmnoticecontainer noprint'><div class='confirmnoticecontent noprint'>";
				echo implode("<hr />", $_SESSION['confirmnotice']);
			echo "</div></div>";
		}
		unset($_SESSION['confirmnotice']);
	?>
