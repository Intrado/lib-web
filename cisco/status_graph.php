<?
require_once("common.inc.php");


//TODO refresh header
header("Refresh: 10; url=" . $URL . "/status_graph.php");
header("Content-type: text/xml");

?>
<CiscoIPPhoneImageFile>
<Title>SchoolMessenger - Status</Title>
<Prompt>Active Jobs</Prompt>
<LocationX>-1</LocationX>
<LocationY>-1</LocationY>
<URL><?= $URL . "/graph_active_breakdown.png.php" ?></URL>

<SoftKeyItem>
<Name>Update</Name>
<URL><?= $URL . "/status_graph.php" ?></URL>
<Position>1</Position>
</SoftKeyItem>

<SoftKeyItem>
<Name>Back</Name>
<URL><?= $URL . "/status.php" ?></URL>
<Position>3</Position>
</SoftKeyItem>

</CiscoIPPhoneImageFile>
