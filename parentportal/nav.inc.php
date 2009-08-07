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

//tree format:
//[[title,default link,access,selected,[[sub title,sub link,sub access,sub selected],...],...]

$NAVTREE = array (
	array(_L("Messages"),"start.php",NULL,$MAINTAB=="messages",array()),
	array(_L("Contacts"), "contactpreferences.php?clear=1", NULL, $MAINTAB=="contacts", array()
	)
);

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

// only show tabs if they have contacts
if (isset($contactList) && $contactList)
	doNavTabs($NAVTREE);


////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
header('Content-type: text/html; charset=UTF-8') ;
?>
<script>
	var _brandtheme = "<?=getBrandTheme();?>";
</script>
<html>
<head>
	<meta http-equiv="Content-type" value="text/html; charset=UTF-8" />
	<meta http-equiv="X-UA-Compatible" content="IE=EmulateIE7" />

	<title><?=_L("SchoolMessenger Contact Manager")?>: <?= $PAGETITLE ?></title>
	<script src='script/utils.js'></script>
	<script src='script/sorttable.js'></script>
	<link href='css/style_print.css' type='text/css' rel='stylesheet' media='print'>
	<link href='css.php' type='text/css' rel='stylesheet' media='screen'>
</head>
<body>
	<IFRAME src="blank.html" id="blocker" style="DISPLAY: none; LEFT: 0px; POSITION: absolute; TOP: 0px" frameBorder="0" scrolling="no"></IFRAME>

<!-- ********************************************************************* -->

	<div>
		<table width="100%" border=0 cellpadding=0 cellspacing=0 background="img/header_bg.gif">
		<tr>
		<td><img src="img/logo.gif"></td>
<?
		if(!isset($hidenav) || !$hidenav){
?>
		<td><div class="custname"><?= isset($_SESSION['custname']) ? escapehtml($_SESSION['custname']) : ""; ?></div></td>
<?
		}
?>
		</tr>
		</table>
	</div>

	<div class="navmenuspacer">
	<div class="navmenu">

		<? if (isset($contactList) && $contactList) echo $MAINTABS ?>

		<div class="applinks hoverlinks">
			<a href="account.php"><?=_L("My Account")?></a> |
	<?
			$result = portalGetCustomerAssociations();
			if($result['result'] == ""){
				$customerlist = $result['custmap'];
				$customeridlist = array_keys($customerlist);
			} else {
				$customeridlist = array();
			}

			if(count($customeridlist) > 1){
				?><a href="choosecustomer.php"><?=_L("Change Customer")?></a> |<?
			}
	?>
			<a href="#" onclick="window.open('<?=isset($LOCALE)?"locale/$LOCALE/help/index.html":"help/index.html"?>', '_blank', 'width=750,height=500,location=no,menubar=yes,resizable=yes,scrollbars=yes,status=no,titlebar=no,toolbar=yes');"><?=_L("Help")?></a> |
<?
	$logout = "index.php?logout=1";
	if (isset($_SESSION['customerurl'])) {
		$logout .= "&u=".urlencode($_SESSION['customerurl']);
	}
?>
			<a href="<?=$logout?>"><?=_L("Logout")?></a>
		</div>

	</div>
	</div>


	<div class="subnavmenu hoverlinks"></div>
	<div class="pagetitle"><? if(isset($ICON)) print '<img src="img/themes/3dblue/icon_' . $ICON . '" align="absmiddle">'; ?> <?= $TITLE ?></div>
	<div class="pagetitlesubtext"><?= (isset($DESCRIPTION) ? $DESCRIPTION : "") ?></div>

	<div class="content">