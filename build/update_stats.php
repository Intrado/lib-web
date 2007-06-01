<?

$yesterday = date("Y-m-d", strtotime("yesterday"));

$authhost="localhost:3306";
$authuser="root";
$authpass="";
$authdb="authserver";

//connect to auth
$auth = mysql_connect($authhost, $authuser, $authpass)
			or die("Could not connect to auth: " . mysql_error($authdb));
mysql_select_db($authdb, $auth);
		
$res = mysql_query("select shardhost, sharduser, shardpass from shardinfo order by shardhost", $auth);
$shardinfo = array();
while($row = mysql_fetch_row($res)){
	$shardinfo[$row[0]] = array($row[1], $row[2]);
}

$customerquery = mysql_query("select id, dbhost, hostname from customer", $auth);
$customers = array();
while($row = mysql_fetch_row($customerquery)){
	$customers[] = $row;
}

foreach($customers as $customer) {
	//connect to customer
	$custdb = mysql_connect($customer[1], $shardinfo[$customer[1]][0], $shardinfo[$customer[1]][1])
				or die("Could not connect to customer: " . mysql_error($custdb));
	mysql_select_db("c_$customer[0]", $custdb)
				or die("Could not select customer db: " . mysql_error($custdb));
				
	$res = mysql_query("select value from setting where name='timezone'", $custdb);
	$row = mysql_fetch_row($res);
	$timezone = $row[0];

	mysql_query("set time_zone='$timezone'", $custdb);			
	$query = "insert systemstats(date, hour, answered, machine, busy, noanswer) 
				select
				date(from_unixtime(rc.starttime/1000)) as date,
				hour(from_unixtime(rc.starttime/1000)) as hour,
				sum(rc.result = 'A') as answered,
				sum(rc.result = 'M') as machine,
				sum(rc.result = 'B') as busy,
				sum(rc.result = 'N') as noanswer
				from reportcontact rc
				where rc.starttime/1000 > unix_timestamp('$yesterday')
				and rc.starttime/1000 <= unix_timestamp(now())
				group by date, hour 
				on duplicate key update 
				answered = values(answered),
				machine = values(machine),
				busy = values(busy),
				noanswer = values(noanswer)";
				
	$res = mysql_query($query, $custdb)
				or die("Could not insert into systemstats: " . mysql_error($custdb));
				
	$query = "insert jobstats(jobid, count)
				select j.id,
				count(*) as count
				from job j
				inner join reportperson rp on (rp.jobid = j.id)
				inner join reportcontact rc on (rp.jobid = rc.jobid and rp.personid = rc.personid and rp.type = rc.type)
				where rp.status='success'
				and unix_timestamp(j.startdate) > unix_timestamp('$yesterday')
				and unix_timestamp(j.startdate) < unix_timestamp(now())
				group by j.id
				on duplicate key update
				count = values(count)";
				
	$res = mysql_query($query, $custdb)
				or die("Could not insert into jobstats: " . mysql_error($custdb));
}
?>