<?
/*CSDELETEMARKER_START*/ 
$body="";
$line = date("Y-m-d H:i:s,");
foreach ($_GET as $k => $v) {
   $line .= "$k=$v&";
   $body .= "$k=$v\n";
}

$cmd = "/usr/commsuite/java/j2sdk/bin/java -jar /usr/commsuite/server/simpleemail/simpleemail.jar";
$cmd .= " -s \"New SMS Message\"";
$cmd .= " -f \"noreply@schoolmessenger.com\"";
$cmd .= " -t \"marnberg@schoolmessenger.com\"";
$process = popen($cmd, "w");
fwrite($process, $body);
fclose($process);


$fp = fopen("../txt.txt","a");
fwrite($fp, $line . "\n");
fclose($fp);

/*CSDELETEMARKER_END*/

?>