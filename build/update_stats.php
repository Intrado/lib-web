<?

if ($argc < 3)
	exit ("Please specify customerid and yesterday's date");
	
$customerid = $argv[1];
$yesterday = $argv[2];

$authhost="localhost:3306";
$authuser="root";
$authpass="";
$authdb="authserver";

//connect to auth
$auth = mysql_connect($authhost, $authuser, $authpass)
			or die("Could not connect to auth: " . mysql_error($authdb));
mysql_select_db($authdb, $auth);

//fetch customer information
$query = "select id, dbhost, dbusername, dbpassword, hostname from customer where id = '$customerid'";
$res = mysql_query($query, $auth)
		or die("Could not get customer information: " . mysql_error($auth));
$customer = mysql_fetch_row($res);

//connect to customer
$custdb = mysql_connect($customer[1], $customer[2], $customer[3])
			or die("Could not connect to customer: " . mysql_error($custdb));
mysql_select_db("c_$customer[0]", $custdb)
			or die("Could not select customer db: " . mysql_error($custdb));
			
			
$query = "insert systemstats(datetime, answered, machine, busy, noanswer) 
			select 
			floor(rc.starttime/(1000*3600))*3600 as datetime,
			sum(rc.result = 'A') as answered,
			sum(rc.result = 'M') as machine,
			sum(rc.result = 'B') as busy,
			sum(rc.result = 'N') as noanswer
			from reportcontact rc
			where rc.starttime/1000 > unix_timestamp('$yesterday')
			and rc.starttime/1000 <= unix_timestamp(now())
			group by datetime 
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

?>