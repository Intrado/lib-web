<?

header("Content-type: text/xml");

?>
<CiscoIPPhoneMenu>
<Title>ZERVISES</Title>
<Prompt><?= $_SERVER['QUERY_STRING'] ?></Prompt>

<MenuItem>
<Name><?=$_SESSION['productname']?></Name>
<URL>http://10.25.25.123/dialer/cisco/</URL>
</MenuItem>


</CiscoIPPhoneMenu>

