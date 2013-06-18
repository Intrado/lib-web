<?
if (isset($_REQUEST["classroom"]))
	$_SESSION['classroomtheme'] = true;
if (isset($_REQUEST["blue"]))
	unset($_SESSION['classroomtheme']);

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

function doLogo () {
	// there is no customized theme for contact manager, no customer logo
	echo '<img src="img/sm_white.gif" alt="">';
}

function navMainTab ($display, $hint, $accesskey, $link, $isselected) {
	return '<li '. ($isselected ? 'class="navtab_active"' : "") .'><a accesskey="'.$accesskey.'" onfocus="blur()" href="' . $link . '" title="'.$hint.'"><span>' . $display . '</span></a></li>';
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
<!DOCTYPE html>
<head>
	<meta http-equiv="Content-type" value="text/html; charset=UTF-8" />
	<meta http-equiv="X-UA-Compatible" content="IE=edge" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
	
	<title>Contact Manager: <?= $PAGETITLE ?></title>
	
	<script src='script/utils.js'></script>
	<script src='script/sorttable.js'></script>
	<script src="script/prototype.js" type="text/javascript"></script>
	<script src="script/scriptaculous.js" type="text/javascript"></script>
	<script src="script/prototip.js.php" type="text/javascript"></script>
	<script src="script/form.js.php" type="text/javascript"></script>

	<link href="css.php?hash=<?=crc32(serialize($_SESSION['colorscheme']))?>" type="text/css" rel="stylesheet" media="screen, print">
	<link href="css.forms.php?hash=<?=crc32(serialize($_SESSION['colorscheme']))?>" type="text/css" rel="stylesheet" media="screen, print" />
  
  	<link href='parentportal.css' type='text/css' rel='stylesheet' media='screen'>

	<link href="css/prototip.css.php" type="text/css" rel="stylesheet">
	<link href='css/style_print.css' type='text/css' rel='stylesheet' media='print'>

<!--[if lte IE 6]>
    <link href="css/ie6.css" type="text/css" rel="stylesheet"/>
<![endif]-->

<!--[if lte IE 7]>
    <link href="css/ie7.css" type="text/css" rel="stylesheet"/>
<![endif]-->

</head>
<body>
<!-- ********************************************************************* -->
<div class="wrap"><!-- ends in navbottom.inc -->

<div id="top_banner" class="banner">
<div class="contain cf">


<?
		if (!isset($hidenav) || !$hidenav) {
?>
			<div class="banner_custname"><?= isset($_SESSION['custname']) ? escapehtml($_SESSION['custname']) : ""; ?></div>
<?
		}
?>


	<div class="banner_logo">
	<div class="logo"></div>
	<h1>SchoolMessenger</h1>
	</div>


						<div class="banner_links_wrap">
							<div class="banner_links_button"><?= icon_button(_L("Account"), "fugue/clipboard","$$('ul.banner_links').first().toggleClassName('minhide');return false;") ?></div>
							<ul class="banner_links cf minhide">
								<li class="bl_left"></li>
<?
	// do not display 'my account' link of login via powerschool
	if (!isset($_SESSION['userlogintype']) || ($_SESSION['userlogintype'] != 'powerschool')) {
?>
								<li><a href="account.php"><?=_L("My Account")?></a></li>
<?
    } // end if display 'my account'
    
    // check if multi customer
	$result = portalGetCustomerAssociations();
	if ($result['result'] == "") {
		$customerlist = $result['custmap'];
		$customeridlist = array_keys($customerlist);
	} else {
		$customeridlist = array();
	}
	if (count($customeridlist) > 1) {
?>
								<li><a href="choosecustomer.php"><?=_L("Change Customer")?></a></li>
<?
	}
?>
						
								<li><a href="#" onclick="window.open('<?=isset($LOCALE)?"locale/$LOCALE/help/index.html":"help/index.html"?>', '_blank', 'width=750,height=500,location=no,menubar=yes,resizable=yes,scrollbars=yes,status=no,titlebar=no,toolbar=yes');"><?=_L("Help")?></a></li>
<?
	$logout = "index.php?logout=1";
	//if (isset($_SESSION['customerurl'])) {
	//	$logout .= "&u=".urlencode($_SESSION['customerurl']);
	//}
?>
								<li class="bl_last"><a href="<?=$logout?>" title="<?=_L("Logout")?>"><?=_L("Logout")?></a></li>
								<li class="bl_right"></li>
							</ul>
						</div><!-- .banner_links_wrap -->

</div><!-- /container -->	
</div><!--  end top_banner -->

<div class="primary_nav">
<div class="contain cf">
	
	<ul class="navtabs">
		<? if (isset($contactList) && $contactList) echo $MAINTABS ?>
	</ul>
	
</div><!-- /container -->
</div><!-- primary_nav -->
	

<div class="content_wrap cf"><!-- starts main content wrapper, tag ends in navbottom.inc.php -->



	<div class="container cf">
	
	
		<div class="pagetitle"><?= (isset($TITLE) ? $TITLE : "") ?></div>
		<div class="pagetitlesubtext"><?= (isset($DESCRIPTION) ? $DESCRIPTION : "") ?></div>
	