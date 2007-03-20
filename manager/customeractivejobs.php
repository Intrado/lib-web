<?
include_once("common.inc.php");


if(isset($_GET['customer'])){
	$customerid = $_GET['customer'];
	$extra = "and customer.id = '$customerid'";
} else if(isset($_GET['user'])){
	$userid = $_GET['user'];
	$extra = "and user.id = '$userid'";
} else {
	$extra = "";
}

$query = "select customer.id, customer.name, customer.hostname, job.id, job.name,
			job.starttime, job.startdate, job.endtime, job.enddate,
			sum(wi.status = 'new') as newcount,
			sum(wi.status = 'scheduled') as scheduled,
			sum(wi.status = 'waiting') as waiting,
			sum(wi.status = 'queued') as queued,
			sum(wi.status in ('assigned', 'inprogress')) as inprogess,
			sum(wi.status in ('success', 'fail', 'duplicate') ) as complete
			from customer
			left join user on (user.customerid = customer.id)
			left join job on (job.userid = user.id)
			left join jobworkitem wi on (wi.jobid = job.id) 
			where job.status = 'active'
			and user.deleted = '0'
			$extra
			group by job.id
			order by customer.id, job.id";
			
$result = Query($query);
include("nav.inc.php");
?>

<table border=.2>
	<tr>
		<td>Customer ID</td>
		<td>Customer Name</td>
		<td>Customer URL</td>
		<td>Job Name</td>
		<td>Job ID</td>
		<td>Startdate</td>
		<td>Enddate</td>
		<td>Total Workitems</td>
		<td>New</td>
		<td>Sch</td>
		<td>Wait</td>
		<td>Queued</td>
		<td>Inprog</td>
		<td>Done</td>
		
		
	</tr>
<?
	while($row = DBGetRow($result)){
		$startdatetime = date("M j, g:i a", strtotime($row[5] . " " . $row[6]));
		$enddatetime = date("M j, g:i a", strtotime($row[7] . " " . $row[8]));
		
?>		
		<tr>
			<td><?=$row[0]?></td>
			<td><?=$row[1]?></td>
			<td><a href="https://asp.schoolmessenger.com/<?=$row[2]?>" target="_blank"><?=$row[2]?></a></td>
			<td><?=$row[2]?></td>
			<td><?=$row[3]?></td>
			<td><?=$startdatetime?></td>
			<td><?=$enddatetime?></td>
			<td><?=$row[9]+$row[10]+$row[11]+$row[12]+$row[13]+$row[14]?></td>
			<td><?=$row[9]?></td>
			<td><?=$row[10]?></td>
			<td><?=$row[11]?></td>
			<td><?=$row[12]?></td>
			<td><?=$row[13]?></td>
			<td><?=$row[14]?></td>
		</tr>
<?
	}
?>
</table>

<?
include("navbottom.inc.php");
?>
			