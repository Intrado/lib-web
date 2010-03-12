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

//tree format:
//[[title,default link,access,selected,[[sub title,sub link,sub access,sub selected],...],...]

$NAVTREE = array (
	array(_L("Messages"),_L("Messages"),"M", "start.php",NULL,$MAINTAB=="messages",array()),
	array(_L("Contacts"),_L("Contacts"),"C", "contactpreferences.php?clear=1", NULL, $MAINTAB=="contacts", array())
);

////////////////////////////////////////////////////////////////////////////////
// Display Functions
////////////////////////////////////////////////////////////////////////////////

function navMainTab ($display, $hint, $accesskey, $link, $isselected) {
	$theme = getBrandTheme();
	return '<div class="navtab"><a accesskey="'.$accesskey.'" onfocus="blur()" href="' . $link . '" title="'.$hint.'"><img alt="" src="img/themes/' . $theme . '/main_nav_tab' . ($isselected ? "_active" : "") . '.gif"><span>' . $display . '</span></a></div>';
}

function navSubTab ($title, $link, $isselected) {
	return '<a onfocus="blur()" class="subnavtab ' . ($isselected ? "active" : "") . '" href="' . $link . '" title="'.$title.'"><div>' . $title . '</div></a>';
}

function doNavTabs ($navtree) {
	global $USER, $FIRSTACTIVETABLINK, $ACTIVEMAINTABTITLE, $MAINTABS,$SUBTABS;

	$MAINTABS = "";
	$SUBTABS = "";
	foreach ($navtree as $maintab) {
			//do the subtabs first, if there are any
			$maintablink = false;
			foreach ($maintab[6] as $subtab) {
					//set the maintablink to the first enabled subtab's link
					if ($maintablink === false)
						$maintablink = $subtab[1];
					//build subtab html if this maintab is selected
					if ($maintab[5]) {
						$FIRSTACTIVETABLINK = $maintablink;
						$ACTIVEMAINTABTITLE = $maintab[0];
						$SUBTABS .= navSubTab($subtab[0],$subtab[1],$subtab[3]);
					}
			}
			//if we didnt get a link, then use the default
			$maintablink = $maintablink === false ? $maintab[3] : $maintablink;

			$MAINTABS .= navMainTab($maintab[0],$maintab[1],$maintab[2],$maintablink,$maintab[5]);
	}
}

// only show tabs if they have contacts
if (isset($contactList) && $contactList)
	doNavTabs($NAVTREE);


////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
header('Content-type: text/html; charset=UTF-8') ;
?>
<html>
<head>
	<meta http-equiv="Content-type" value="text/html; charset=UTF-8" />
	<meta http-equiv="X-UA-Compatible" content="IE=EmulateIE7" />

	<title>Contact Manager: <?= $PAGETITLE ?></title>
	
	<script src='script/utils.js'></script>
	<script src='script/sorttable.js'></script>
	<script src="script/prototype.js" type="text/javascript"></script>
	<script src="script/scriptaculous.js" type="text/javascript"></script>
	<script src="script/prototip.js.php" type="text/javascript"></script>
	<script src="script/form.js.php" type="text/javascript"></script>

	<link href="css/form.css.php" type="text/css" rel="stylesheet">
	<link href="css/prototip.css.php" type="text/css" rel="stylesheet">
	<link href='css/style_print.css' type='text/css' rel='stylesheet' media='print'>
	<link href="css.php?hash=<?=crc32(serialize($_SESSION['colorscheme']))?>" type="text/css" rel="stylesheet" media="screen, print">

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
		<td bgcolor="white"><div style="padding-left:10px;"><img src="img/sm_white.gif"></div></td>
		<td><img src="img/shwoosh.gif" alt="" class="noprint"></td>
		<td width="100%" align="right" style="padding-right: 10px;">
<?
		if (!isset($hidenav) || !$hidenav) {
?>
			<div class="custname"><?= isset($_SESSION['custname']) ? escapehtml($_SESSION['custname']) : ""; ?></div>
<?
		}
?>
			<table border=0 cellspacing=0 cellpadding=0 class="noprint">
				<tr>
					<td><img src="img/accountlinksbg_left.gif" alt=""></td>
					<td background="img/accountlinksbg_mid.gif">
						<div class="applinks hoverlinks">
							<a href="account.php"><?=_L("My Account")?></a> |
<?
	$result = portalGetCustomerAssociations();
	if ($result['result'] == "") {
		$customerlist = $result['custmap'];
		$customeridlist = array_keys($customerlist);
	} else {
		$customeridlist = array();
	}
	if (count($customeridlist) > 1) {
?>
							<a href="choosecustomer.php"><?=_L("Change Customer")?></a> |
<?
	}
?>
						
							<a href="#" onclick="window.open('<?=isset($LOCALE)?"locale/$LOCALE/help/index.html":"help/index.html"?>', '_blank', 'width=750,height=500,location=no,menubar=yes,resizable=yes,scrollbars=yes,status=no,titlebar=no,toolbar=yes');"><?=_L("Help")?></a> |
<?
	$logout = "index.php?logout=1";
	if (isset($_SESSION['customerurl'])) {
		$logout .= "&u=".urlencode($_SESSION['customerurl']);
	}
?>
							<a href="<?=$logout?>" title="<?=_L("Logout")?>"><?=_L("Logout")?></a>
						</div>
					</td>
					<td><img src="img/accountlinksbg_right.gif" alt=""></td>				
				</tr>		
			</table>
		</td>
	</tr>
</table>
<div class="navband1"><img src="img/pixel.gif" alt=""></div>
<div class="navband2"><img src="img/pixel.gif" alt=""></div>

<!-- =================================================== -->
<div class="navmenuspacer">
<div class="navmenu">
<? if (isset($contactList) && $contactList) echo $MAINTABS ?>
</div>
</div>


<div class="subnavmenu hoverlinks">
<? if (isset($contactList) && $contactList) echo $SUBTABS ?>
</div>

	<div class="pagetitle"><? if(isset($ICON)) print '<img alt="" src="img/themes/' .getBrandTheme() . '/icon_' . $ICON . '" align="absmiddle">'; ?> <?= $TITLE ?></div>
	<div class="pagetitlesubtext"><?= (isset($DESCRIPTION) ? $DESCRIPTION : "") ?></div>

	<div class="maincontent">
