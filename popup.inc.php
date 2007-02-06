<?
$pagetitle = preg_replace('/\\<.+>/','',(isset($TITLE) ? $TITLE : ""));
?>
<html>
<head>
<title>SchoolMessenger: <?= $pagetitle ?></title>
	<script src='script/utils.js'></script>
	<script src='script/nav.js'></script>
	<script src='script/sorttable.js'></script>
	<link href='css/style_print.css' type='text/css' rel='stylesheet' media='print'>
	<link href='css/style.css' type='text/css' rel='stylesheet' media='screen'>
</head>
<body onload="try {UpdateTimer();} catch (e) {}" style="margin: 0px; background-color: white;">
	<IFRAME id="blocker" style="DISPLAY: none; LEFT: 0px; POSITION: absolute; TOP: 0px" src="javascript:false;" frameBorder="0" scrolling="no"></IFRAME>
	<div id="popupcontainer">
		<div id="mainscreen">
			<img id="brand" src="img/school_messenger.gif"/>
		</div>
		<div id="contentbody">
			<div>
				<div id="navtitle"><?= (isset($TITLE) ? $TITLE : "") ?></div>
				<div id="navdesc"><?= (isset($DESCRIPTION) ? $DESCRIPTION : "") ?></div>
			</div>
			<div id="shadowblock">
				<table border="0" cellpadding="0" cellspacing="0">
					<tr><td id="shadowcontent">
