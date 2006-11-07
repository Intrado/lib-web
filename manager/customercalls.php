<?
include_once("common.inc.php");
include_once("../obj/User.obj.php");
include_once("../obj/Access.obj.php");
include_once("../obj/Permission.obj.php");
include_once("../inc/formatters.inc.php");

$customerID = $_GET["customer"] + 0;
$date = $_POST["submit"];
$date = strtotime($date);
if($date){
	$date = date("Y-m-d", $date);
	$mysqlextra = "AND job.enddate >= '$date'";
}else{
	$mysqlextra = "";
}
$numberofcalls = 0;

$namelist = Query("SELECT COUNT(*), login, name, job.startdate, job.starttime,
							job.enddate, job.endtime, job.finishdate
							FROM job, jobtask, jobworkitem, user
							WHERE jobtask.jobworkitemid=jobworkitem.id
							AND jobworkitem.jobid=job.id
							AND job.userid=user.id
							AND user.customerid='$customerID'
							AND jobtask.numattempts >= 1
							AND jobworkitem.type='phone'
							$mysqlextra
							GROUP BY job.id");

include_once("nav.inc.php");

?>
<table border=1>
	<tr>
		<td>User Name</td>
		<td>Job Name</td>
		<td>Number of Calls</td>
		<td>Job Start Date </td>
		<td>Job End Date </td>
		<td>Job Finish Date </td>
	</tr>
<?
while($row=DBGetRow($namelist)){
	$numberofcalls +=$row[0];
	$row[3] = $row[3].$row[4];
	$row[5] = $row[5].$row[6];
?>
	<tr>
		<td><?=$row[1]?></td>
		<td><?=$row[2]?></td>
		<td><?=$row[0]?></td>
		<td><?=fmt_date($row,3)?> </td>
		<td><?=fmt_date($row,5)?> </td>
		<td><?=fmt_date($row,7)?> </td>
	</tr>
<?
}
?>
</table>
<table border=1>
	<tr>
		<td>Total Calls:</td>
		<td><?= $numberofcalls ?></td>
	</tr>
</table>

<form method="post">
	<p> Date: <input type="type" name="submit" /> </p>
	<p><input type="submit" /></p>
</form>
<?
include_once("navbottom.inc.php");
?>