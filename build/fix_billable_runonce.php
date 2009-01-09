<?

$authhost = "";
$authuser = "";
$authpass = "";

$authdb = mysql_connect($authhost,$authuser,$authpass);
mysql_select_db("authserver", $authdb);



$res = mysql_query("select id,dbhost,dbusername,dbpassword from shard", $authdb)
	or exit(mysql_error());
$shards = array();
while ($row = mysql_fetch_assoc($res))
	$shards[$row['id']] = mysql_connect($row['dbhost'],$row['dbusername'],$row['dbpassword'], true)
		or exit(mysql_error());

$res = mysql_query("select id, shardid, urlcomponent, dbusername, dbpassword from customer where enabled order by id", $authdb)
	or exit(mysql_error());
$customers = array();
while ($row = mysql_fetch_assoc($res))
	$customers[$row['id']] = $row;


foreach ($customers as  $customerid => $customer) {
	
	echo "doing $customerid\n";

	$db = $shards[$customer['shardid']];
	mysql_select_db("c_$customerid",$db);
	
	//get a count for all complete jobs for non duplicates
	$query = "insert into customercallstats
		select rp.jobid, rp.userid, j.finishdate, count(*) as attempted
		from job j left join reportperson rp  
			on (j.id = rp.jobid and rp.status!='duplicate' and rp.type='phone')  
		where j.status in ('complete','cancelled')  
		and (exists  
				(select * from reportcontact rc  
				where rc.jobid = rp.jobid and rc.personid = rp.personid  
				and rc.type = rp.type and rc.dispatchtype='system' and rc.numattempts > 0)) 
		group by jobid
		on duplicate key update attempted = values (attempted)";
	mysql_query($query,$db) or exit("error:" . mysql_error());
	
	//add duplicates
	$query = "insert into customercallstats
		select rp.jobid, rp.userid, j.finishdate, count(*) as attempted 
		from job j left join reportperson rp  
			on (j.id = rp.jobid and rp.status='duplicate' and rp.type='phone')  
		where j.status in ('complete','cancelled')  
		and (exists  
				(select * from reportcontact rc  
				where rc.jobid = rp.jobid and rc.personid = rp.duplicateid  
				and rc.type = rp.type and rc.dispatchtype='system' and rc.numattempts > 0))
		group by jobid
		on duplicate key update attempted = attempted + values (attempted)
		";
	mysql_query($query,$db) or exit("error:" . mysql_error());

}

?>