<?
require_once("common.inc.php");



function doButtons () {
	global $USER, $ACCESS, $URL;


	if ($USER->authorize('sendphone')) {
?>
	<SoftKeyItem>
		<Name>New Job</Name>
		<URL><?= $URL . "/wiz1_job.php" ?></URL>
	<Position>1</Position>
	</SoftKeyItem>
<?
	}
	if ($USER->authorize('createreport')) {
?>
	<SoftKeyItem>
			<Name>Status</Name>
			<URL><?= $URL . "/status.php" ?></URL>
			<Position>2</Position>
	</SoftKeyItem>
<?
	}
?>
	<SoftKeyItem>
			<Name>Log out</Name>
			<URL><?= $URL . "/index.php?logout=1" ?></URL>
	<Position>3</Position>
	</SoftKeyItem>
<?
}

header("Content-type: text/xml");

if ("schoolmessenger" == strtolower($_SESSION['productname']) && doesSupport("CiscoIPPhoneImageFile") && !(isModel("7961") || isModel("7941"))) { ?>

	<CiscoIPPhoneImageFile>
	<LocationX>-1</LocationX>
	<LocationY>-1</LocationY>
	<URL><?= $URL . "/logo.png" ?></URL>
	<Title>Welcome to <?=$_SESSION['productname']?></Title>
	<Prompt>Welcome to <?=$_SESSION['productname']?></Prompt>

	<? doButtons() ?>

	</CiscoIPPhoneImageFile>

<? } else if ("schoolmessenger" == strtolower($_SESSION['productname']) && doesSupport("CiscoIPPhoneImage")) { ?>

	<CiscoIPPhoneImage>
	  <LocationX>-1</LocationX>
	  <LocationY>-1</LocationY>
	  <Width>112</Width>
	  <Height>60</Height>
	  <Depth>2</Depth>
	  <Data>000000000000000000000000FCFF03000000000000000000000000000000000000000000000000F0FFFFFF000000000000000000000000000000000000000000000000FCFFFFFF0F00000000000000000000000000000000000000000000C0FFFFFF030000000000000000000000000000000000000000000000F0FFFF0F000000000000000000000000000000000000000000000000FCFFFF00F0FF00000000000000000000000000000000000000000000FCFF3FC0F0FF3F000000000000000000000000000000000000000000FFFF03FCC3FFFF0300000000000000000000000000000000000000C0FFFF00FF0FFFFF0F00000000000000000000000000000000000000C0FFFFC0FF3FFCFFFF00000000000000000000000000000000000000C0FF3F0CFFFFF0FFFF00000000000000000000000000000000000000F0FF0F3FFCFFC3FFFF03000000000000000000000000000000000000F0FFC3FFF0FF0FFFFF0F000000000000000000000000000000000000F0FFF0FFC3FF3FFCFF3F000000000000000000000000000000000000F03FFCFF03FFFFF0FF3F0000000000000000000000000000000000C0F00FFFFF00FCFFC3FFFF0000000000000000000000000000000000F0F0C3FF3F00F0FF0FFFFF0000000000000000000000000000000000F0F0F0FF0F00C0FF03FCFF0000000000000000000000000000000000FC30FCFF030000FF00F0FF0000000000000000000000000000000000FC00FFFF0000003CF0C0FF0000000000000000000000000000000000FCC3FF3F00000000FCC3FF0000000000000000000000000000000000FCC3FF0F00000000FF0FFF0000000000000000000000000000000000FCC3FF03000000C0FF0FFF0000000000000000000000000000000000FC0FFF00000000F0FF03FF0000000000000000000000000000000000FC0F3CF0000000FCFF00FC0000000000000000000000000000000000FC3F00FC030000FFFF30FC0000000000000000000000000000000000FCFF00FF0F00C0FF3F3C3C0000000000000000000000000000000000FCFFC3FF3F00F0FF0F3F3C0000000000000000000000000000000000FCFF0FFFFF00FCFFC33F0C0000000000000000000000000000000000F0FF3FFCFF03FFFFF03F000000000000000000000000000000000000F0FFFFF0FF0FFF3FFC3F000000000000000000000000000000000000C0FFFFC3FF3FFC0FFF3F00000000000000000000000000000000000000FFFF0FFFFFF0C3FF3F00000000000000000000000000000000000000FFFF3FFCFF03F0FF0F00000000000000000000000000000000000000FCFFFFF0FF0FFCFF0F00000000000000000000000000000000000000C0FFFFC3FF0FFFFF030000000000000000000000000000000000000000FFFF0FFFC0FFFF030000000000000000000000000000000000000000F0FF3F3CF0FFFF00000000000000000000000000000000000000000000FC3F00FFFF3F000000000000000000000000000000000000000000000000F0FFFF0F0000000000000000000000000000000000000000000000C0FFFFFF0300000000000000000000000000000000000000000000C0FFFFFFFF000000000000000000000000000000000000000000000000FCFFFF0F00000000000000000000000000000000000000000000000000FFFF00000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000FC030000000000000000003C003C00000000000000000000000000000F00000000000000000000F0000F00000000000000000000000000FC0300FCF3F303FF00FFC003F0000FFC0F3FF0C3FF3CF0C03FFCCFFFCC3C0003C0C0C0C0C3C00303F0C30F3000033C0003F0C030003000C300F0C300C0C0F000C300030330C30C300C033C00C3F0C33000300CC30000CF00C0FFF000C3000F0330CF0CF00F3CF003FF30CF3C00F00FFF0000CF00C0C0F000C3000303303C0C3000C0000F0330FC3CF03000330003C303C3C0C0C0C3000303303C0C3000C0000C0330F030303000C300FF03FFF3C303FF00FFC0FFFC003FFC0F3FFCC3FFF0C0C03FFC0F0F0300000000000000000000000000000000000000000000000000000000</Data>
	  <Title>Welcome to <?=$_SESSION['productname']?></Title>
	  <Prompt>Welcome to <?=$_SESSION['productname']?></Prompt>

	<? doButtons() ?>

	</CiscoIPPhoneImage>

<? } else { ?>

	<CiscoIPPhoneText>
	  <Title><?=$_SESSION['productname']?> - Main</Title>
	  <Prompt>What would you like to do?</Prompt>
	<Text>
	Welcome to <?=$_SESSION['productname']?>. Please select one of the options below.
	</Text>

	<? doButtons() ?>

	</CiscoIPPhoneText>
<? } ?>